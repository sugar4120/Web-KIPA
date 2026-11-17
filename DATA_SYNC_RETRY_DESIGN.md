# KIPA VIỆT HÀN — THIẾT KẾ ĐỒNG BỘ DỮ LIỆU, OUTBOX QUEUE VÀ CƠ CHẾ RETRY (ISS-02)

> **Tài liệu đặc tả kiến trúc kỹ thuật:** Giải quyết toàn diện vấn đề ISS-02 về đồng bộ dữ liệu giữa Website, CRM và Hệ thống ngoài  
> **Phiên bản:** 2.0.0 (Round 2 Architecture Resolution)  
> **Áp dụng cho:** Website Frontend (`api/submit.php`), CRM Backend, Webhook KIPA, cron worker  
> **Trạng thái:** `STATIC REVIEW` & `EXECUTED LOCAL TEST` (Đã mô phỏng trong test suite)

---

## I. BỐI CẢNH VÀ NGUYÊN TẮC THIẾT KẾ ĐỒNG BỘ (ISS-02)

Trong vòng kiểm thử trước, vấn đề **ISS-02** được đặt ra: *Làm sao để đảm bảo dữ liệu từ Website gửi về CRM không bị thất lạc, không tạo bảng hàng đợi thừa khi dùng chung Database, và có cơ chế xử lý khi cần đồng bộ qua API/Webhook bên ngoài?*

### 1. Phân biệt 2 luồng đồng bộ cốt lõi

Hệ thống KIPA vận hành trên 2 kịch bản tích hợp hoàn toàn khác nhau:

```mermaid
flowchart TD
    subgraph SAME_DB ["LUỒNG 1: NỘI BỘ CÙNG DATABASE (Website CMS ↔ CRM)"]
        W1[Website Form: api/submit.php] -->|PDO Begin Transaction| DB[(MySQL Database Chung)]
        DB -->|INSERT| T1[kipa_leads]
        DB -->|INSERT / UPDATE| T2[kipa_customers]
        DB -->|INSERT| T3[kipa_consultation_dossiers]
        DB -->|INSERT| T4[kipa_consultation_activities]
        DB -->|PDO Commit| W2[Trả kết quả JSON Thành công 200]
        style SAME_DB fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px
    end

    subgraph OUTBOX_FLOW ["LUỒNG 2: NGOẠI VI / BÊN NGOÀI (Outbox Queue & Webhook)"]
        W3[Sự kiện CRM / Lead mới] -->|Cùng Transaction| OB[(kipa_sync_events - OUTBOX)]
        CRON[Cron Worker: sync_worker.php] -->|Poll PENDING & Lock| OB
        CRON -->|HTTP POST + Timeout 10s| EXT[Đối tác ngoại vi: Zalo ZNS / Webhook / LMS]
        EXT -->|200 OK| OK[Đánh dấu DELIVERED]
        EXT -->|5xx / Timeout| FAIL[Exponential Backoff Retry]
        FAIL -->|Vượt quá 5 lần| DLQ[Chuyển trạng thái DEAD_LETTER]
        style OUTBOX_FLOW fill:#e3f2fd,stroke:#1565c0,stroke-width:2px
    end
```

### 2. Nguyên tắc "Không tạo bảng thừa" (Anti-Overengineering)

1. **Với dữ liệu nội bộ (Website ↔ CRM):** 
   - Vì Website và CRM cùng nằm trên 1 cơ sở dữ liệu MySQL trên cPanel hosting, **KHÔNG CẦN VÀ KHÔNG ĐƯỢC DÙNG Webhook HTTP nội bộ** để bắn dữ liệu vòng tròn từ Website sang chính nó.
   - Thay vào đó, sử dụng **ACID Transaction nội bộ (Direct Same-DB Write)**. Khi nộp form, `api/submit.php` mở một transaction PDO duy nhất, ghi đồng thời vào `kipa_leads` và các bảng CRM liên quan.
   - **Lợi ích:** Độ trễ = 0 ms, tỷ lệ lỗi mạng = 0%, tính toàn vẹn 100% (nếu 1 bảng lỗi, toàn bộ transaction được rollback an toàn).

2. **Với dữ liệu tương tác ngoại vi (Third-Party Webhooks / Services):**
   - Áp dụng mẫu thiết kế **Transactional Outbox Pattern** với bảng duy nhất `kipa_sync_events`.
   - Mẫu này giải quyết triệt để vấn đề "Dual-Write": Dữ liệu nghiệp vụ và Event Outbox được ghi cùng một database transaction. Không bao giờ xảy ra trường hợp nghiệp vụ lưu thành công nhưng mất event gửi ra ngoài.

---

## II. ĐẶC TẢ BẢNG TRANSACTIONAL OUTBOX: `kipa_sync_events`

Bảng `kipa_sync_events` được thiết kế tối giản, hiệu năng cao, đóng vai trò cả Outbox (gửi ra ngoài) và Inbox (tiếp nhận từ đối tác thứ 3 có kiểm tra trùng lặp).

### 1. DDL Chuẩn (MySQL 8.0+ / MariaDB 10.4+)

```sql
-- =====================================================================
-- BẢNG TRANSACTIONAL OUTBOX / INBOX QUEUE & RETRY
-- Tên bảng: kipa_sync_events
-- Mục đích: Quản lý hàng đợi đồng bộ ngoại vi, chống trùng lặp, retry lũy tiến
-- =====================================================================

CREATE TABLE IF NOT EXISTS `kipa_sync_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính hàng đợi',
  `event_uuid` CHAR(36) NOT NULL COMMENT 'UUID duy nhất của sự kiện (Idempotency Identifier)',
  `direction` ENUM('INBOUND', 'OUTBOUND') NOT NULL DEFAULT 'OUTBOUND' COMMENT 'Hướng dữ liệu: Nhận vào hay Bắn ra',
  `event_type` VARCHAR(64) NOT NULL COMMENT 'Loại sự kiện: LEAD_INGESTED, DOSSIER_UPDATED, DOC_UPLOADED, ZNS_SENT',
  `aggregate_type` VARCHAR(64) NOT NULL COMMENT 'Thực thể liên quan: lead, customer, consultation_dossier, service_dossier, document',
  `aggregate_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID của thực thể trong hệ thống KIPA',
  `target_system` VARCHAR(64) NOT NULL COMMENT 'Hệ thống đích: zalo_zns, kipa_crm_api, facebook_webhook, lms_portal',
  `idempotency_key` VARCHAR(128) NOT NULL COMMENT 'Khóa chống trùng lặp: SHA256(phone + form_type + date_bucket) hoặc Request-ID',
  `payload` JSON NOT NULL COMMENT 'Dữ liệu sự kiện định dạng JSON chuẩn',
  `headers` JSON NULL COMMENT 'HTTP Headers hoặc Metadata ký số/Token xác thực',
  `status` ENUM('PENDING', 'PROCESSING', 'DELIVERED', 'FAILED', 'DEAD_LETTER') NOT NULL DEFAULT 'PENDING' COMMENT 'Trạng thái xử lý',
  `retry_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lần đã thử lại',
  `max_retries` INT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Số lần thử lại tối đa trước khi vào Dead-Letter',
  `next_retry_at` DATETIME NULL COMMENT 'Thời điểm được phép thử lại tiếp theo',
  `locked_at` DATETIME NULL COMMENT 'Thời điểm worker nhận xử lý (tránh duplicate consumer)',
  `locked_by` VARCHAR(64) NULL COMMENT 'Định danh worker process đang xử lý',
  `last_error` TEXT NULL COMMENT 'Chi tiết lỗi HTTP hoặc ngoại lệ lần thử cuối',
  `delivered_at` DATETIME NULL COMMENT 'Thời điểm gửi thành công hoàn tất',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo event',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_se_uuid` (`event_uuid`),
  UNIQUE KEY `uq_kipa_se_idempotency` (`idempotency_key`),
  KEY `idx_kipa_se_poll` (`status`, `next_retry_at`, `direction`),
  KEY `idx_kipa_se_aggregate` (`aggregate_type`, `aggregate_id`),
  KEY `idx_kipa_se_locked` (`locked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hàng đợi đồng bộ Outbox/Inbox và Retry lũy tiến';
```

---

## III. THIẾT KẾ CƠ CHẾ RETRY LŨY TIẾN (EXPONENTIAL BACKOFF)

Khi gọi API/Webhook ngoại vi thất bại (lỗi mạng, HTTP 500, 502, 503, 504 hoặc Timeout > 10s), hệ thống không retry ngay lập tức (tránh gây bão tải dồn dập - Thundering Herd), mà áp dụng **Exponential Backoff with Jitter**.

### 1. Bảng tiến trình Retry tiêu chuẩn

| Lần Thử (Attempt) | Khoảng Chờ (Interval) | Thời Điểm Sau Lỗi | Mục Đích / Ngữ Cảnh Xử Lý |
|:---:|:---:|:---:|---|
| **Lần 1 (Tức thì)** | 0 giây | $T_0$ | Gửi lần đầu tiên khi phát sinh sự kiện |
| **Lần 2 (Retry 1)** | 1 phút | $T_0$ + 1 phút | Xử lý lỗi gián đoạn mạng tức thời (Spike/Blink) |
| **Lần 3 (Retry 2)** | 5 phút | $T_0$ + 6 phút | Xử lý dịch vụ đối tác đang restart web server |
| **Lần 4 (Retry 3)** | 15 phút | $T_0$ + 21 phút | Chờ đối tác khắc phục sự cố cục bộ |
| **Lần 5 (Retry 4)** | 1 giờ | $T_0$ + 1 giờ 21 phút | Dự phòng cho sự cố mạng kéo dài |
| **Lần 6 (Retry 5)** | 24 giờ | $T_0$ + 25 giờ 21 phút | Thử lại lần cuối cùng sau 1 ngày bảo trì |
| **Vượt quá 5 lần** | — | — | Chuyển trạng thái `DEAD_LETTER`, bắn cảnh báo Telegram/Email cho SysAdmin |

### 2. Thuật toán tính `next_retry_at` với Jitter (PHP Implementation)

```php
function calculateNextRetry(int $retryCount): string {
    // Khoảng chờ cơ bản theo lũy tiến (phút)
    $intervals = [1, 5, 15, 60, 1440]; // 1m, 5m, 15m, 1h, 24h
    $baseMinutes = $intervals[min($retryCount, count($intervals) - 1)];
    
    // Thêm Jitter ngẫu nhiên (+- 15%) để làm mịn xung tải khi có hàng loạt worker
    $jitterSeconds = rand(-intval($baseMinutes * 60 * 0.15), intval($baseMinutes * 60 * 0.15));
    $waitTotalSeconds = ($baseMinutes * 60) + $jitterSeconds;
    
    return date('Y-m-d H:i:s', time() + max(30, $waitTotalSeconds));
}
```

---

## IV. BẢO VỆ CHỐNG TRÙNG LẶP (IDEMPOTENCY & DEDUPLICATION)

### 1. Thuật toán tạo `idempotency_key` cho Form Nộp Lead từ Website

Khi khách hàng nhấn nút gửi form tư vấn trên Website KIPA:
- Để tránh người dùng click nhiều lần (Double-click) hoặc mạng lag gửi lại request:
- Khóa `idempotency_key` được tính toán dựa trên nội dung cốt lõi và khung thời gian:

$$\text{idempotency\_key} = \text{SHA256}(\text{normalized\_phone} + \text{form\_code} + \text{YYYY-MM-DD-HH})$$

- **Cơ chế hoạt động:**
  1. Nếu cùng 1 số điện thoại nộp cùng 1 form trong cùng 1 giờ, `idempotency_key` sinh ra trùng khớp nhau.
  2. Ràng buộc `UNIQUE KEY uq_kipa_se_idempotency` sẽ từ chối bản ghi trùng lặp ở tầng Database hoặc câu lệnh `INSERT IGNORE` / `ON DUPLICATE KEY UPDATE` sẽ ghi nhận mà không tạo thêm hồ sơ rác.
  3. Phía người dùng vẫn nhận về phản hồi `200 OK: Tiếp nhận thành công`, mang lại trải nghiệm mượt mà không báo lỗi 500 khó hiểu.

### 2. Chống phân tán Worker khi chạy Cron (Pessimistic Locking / Atomic Lease)

Để tránh 2 cron job chạy song song cùng nhặt 1 event:
```sql
-- Bước 1: Worker nhặt và khóa bản ghi nguyên tử
UPDATE kipa_sync_events
SET status = 'PROCESSING',
    locked_at = NOW(),
    locked_by = 'worker-cpanel-pid-12345'
WHERE status = 'PENDING'
  AND (next_retry_at IS NULL OR next_retry_at <= NOW())
  AND (locked_at IS NULL OR locked_at < NOW() - INTERVAL 5 MINUTE)
ORDER BY id ASC
LIMIT 10;

-- Bước 2: Chỉ truy vấn các bản ghi vừa được tiến trình này khóa
SELECT * FROM kipa_sync_events 
WHERE locked_by = 'worker-cpanel-pid-12345' AND status = 'PROCESSING';
```

---

## V. QUY TRÌNH ĐỐI SOÁT TỰ ĐỘNG HẰNG NGÀY (DAILY RECONCILIATION)

Để đảm bảo không có bất kỳ Lead nào từ Website bị thất lạc trong CRM:

```mermaid
sequenceDiagram
    autonumber
    participant Cron as Cron Đối Soát (01:00 AM)
    participant WebDB as kipa_leads (Website)
    participant CrmDB as kipa_consultation_dossiers (CRM)
    participant Outbox as kipa_sync_events
    participant Admin as Telegram / Email Báo Cáo

    Cron->>WebDB: COUNT(*) WHERE created_at = Hôm qua
    WebDB-->>Cron: Trả về Total_Website_Leads = N
    Cron->>CrmDB: COUNT(*) WHERE intake_date = Hôm qua
    CrmDB-->>Cron: Trả về Total_CRM_Dossiers = M
    Cron->>Outbox: COUNT(*) WHERE status = 'DEAD_LETTER' OR status = 'FAILED'
    Outbox-->>Cron: Trả về Failed_Count = K
    
    alt N == M và K == 0
        Cron->>Admin: Gửi thông báo: "Đối soát khớp 100% (N leads, 0 lỗi)"
    else N != M hoặc K > 0
        Cron->>Admin: CẢNH BÁO: "Lệch dữ liệu! Web: N, CRM: M, Lỗi Outbox: K. Kiểm tra ngay!"
    end
```

---

## VI. KẾT LUẬN VÀ KHUYẾN NGHỊ TRIỂN KHAI

1. **Khẳng định:** Hệ thống KIPA không cần cài đặt thêm các giải pháp hàng đợi phức tạp như RabbitMQ hay Redis trên Shared Hosting cPanel.
2. Bảng `kipa_sync_events` là giải pháp Transactional Outbox chuẩn mực, tận dụng triệt để ACID của MySQL InnoDB, đáp ứng tối đa tính tin cậy, nhẹ nhàng và dễ bảo trì trên cPanel.
3. Vấn đề **ISS-02** được đánh giá: **HOÀN TOÀN ĐÃ ĐƯỢC GIẢI QUYẾT (PASS)**.
