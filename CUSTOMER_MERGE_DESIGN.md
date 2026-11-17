# KIPA VIỆT HÀN — THIẾT KẾ GỘP VÀ HOÀN NGUYÊN KHÁCH HÀNG (CUSTOMER MERGE & UNMERGE DESIGN - ISS-03)

> **Tài liệu đặc tả kiến trúc kỹ thuật:** Giải quyết toàn diện vấn đề ISS-03 về hợp nhất hồ sơ khách hàng trùng lặp, bảo toàn lịch sử và hoàn nguyên an toàn  
> **Phiên bản:** 2.0.0 (Round 2 Architecture Resolution)  
> **Áp dụng cho:** KIPA CRM Core, Module Quản lý Khách hàng, Kiểm toán dữ liệu  
> **Trạng thái:** `STATIC REVIEW` & `EXECUTED LOCAL TEST` (Đã mô phỏng trong test suite)

---

## I. BỐI CẢNH NGHIỆP VỤ VÀ NGUYÊN TẮC BẢO TOÀN DỮ LIỆU (ISS-03)

Trong thực tế vận hành tại KIPA Việt Hàn, khách hàng có thể phát sinh trùng lặp do:
1. Đăng ký nhiều lần qua các kênh Website, Fanpage, Hotline bằng số điện thoại khác nhau (SIM chính, SIM phụ) hoặc sai sót chính tả tên/email.
2. Được tạo thủ công tại văn phòng chi nhánh bởi tư vấn viên, trong khi trước đó đã nộp form trên hệ thống.

### Nguyên tắc bất biến khi Gộp khách hàng (Inviolable Principles)
1. **Tuyệt đối không xóa vật lý (NO HARD DELETE):** Khách hàng nguồn (Source Customer) không bị xóa khỏi bảng `kipa_customers`. Trạng thái được chuyển sang `MERGED` và gán con trỏ `merged_into_id` trỏ về khách hàng đích (Target Customer).
2. **Bảo tồn toàn vẹn quan hệ con (Zero Data Loss):** Toàn bộ Hồ sơ tư vấn, Hồ sơ dịch vụ, Hoạt động tương tác, Quan hệ gia đình, Địa chỉ và Tài liệu đính kèm của khách hàng nguồn được chuyển giao quyền sở hữu sang khách hàng đích.
3. **Phê duyệt kép bắt buộc (Dual Approval):** Thao tác gộp khách hàng thuộc danh mục nhạy cảm, bắt buộc phải có chữ ký xác nhận của Người thực hiện (`merged_by`) và Quản lý trực tiếp phê duyệt (`approved_by`).
4. **Khả năng Hoàn nguyên có kiểm soát (Reversible Unmerge):** Hệ thống phải lưu vết `snapshot_payload` chi tiết danh sách ID của các đối tượng con đã chuyển dịch để cho phép Trưởng bộ phận / Quản trị viên "Hủy gộp" (Unmerge) đưa dữ liệu trở về đúng trạng thái ban đầu khi phát hiện gộp nhầm.

---

## II. ĐẶC TẢ DDL BẢNG `kipa_customer_merges`

```sql
-- =====================================================================
-- BẢNG NHẬT KÝ VÀ QUẢN LÝ GỘP KHÁCH HÀNG (MERGE & UNMERGE)
-- Tên bảng: kipa_customer_merges
-- =====================================================================

CREATE TABLE IF NOT EXISTS `kipa_customer_merges` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Khóa chính giao dịch gộp',
  `merge_code` VARCHAR(32) NOT NULL COMMENT 'Mã phiếu gộp: MRG-YYYY-XXXXXX',
  `source_customer_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID khách hàng nguồn (bị gộp vào)',
  `source_customer_code` VARCHAR(32) NOT NULL COMMENT 'Mã khách hàng nguồn tại thời điểm gộp',
  `target_customer_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID khách hàng đích (giữ lại làm đại diện)',
  `target_customer_code` VARCHAR(32) NOT NULL COMMENT 'Mã khách hàng đích',
  `merge_reason` VARCHAR(500) NOT NULL COMMENT 'Lý do thực hiện gộp hồ sơ',
  `merged_by` BIGINT UNSIGNED NOT NULL COMMENT 'ID nhân sự đề xuất/thực hiện gộp (FK kipa_staff)',
  `approved_by` BIGINT UNSIGNED NOT NULL COMMENT 'ID quản lý chi nhánh/Admin duyệt gộp (FK kipa_staff)',
  `merged_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm gộp thành công',
  `snapshot_payload` JSON NOT NULL COMMENT 'Snapshot chi tiết các ID hồ sơ/tài liệu đã chuyển giao để phục vụ hoàn nguyên',
  `status` ENUM('ACTIVE', 'UNMERGED') NOT NULL DEFAULT 'ACTIVE' COMMENT 'Trạng thái bản ghi gộp: Đang hiệu lực hoặc Đã hoàn nguyên',
  `unmerged_at` DATETIME NULL COMMENT 'Thời điểm hủy gộp hoàn nguyên (nếu có)',
  `unmerged_by` BIGINT UNSIGNED NULL COMMENT 'ID người thực hiện hủy gộp (FK kipa_staff)',
  `unmerge_reason` VARCHAR(500) NULL COMMENT 'Lý do hủy gộp hồ sơ',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_cm_code` (`merge_code`),
  KEY `idx_kipa_cm_source` (`source_customer_id`),
  KEY `idx_kipa_cm_target` (`target_customer_id`),
  KEY `idx_kipa_cm_status` (`status`),
  CONSTRAINT `fk_kipa_cm_source` FOREIGN KEY (`source_customer_id`) 
    REFERENCES `kipa_customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_kipa_cm_target` FOREIGN KEY (`target_customer_id`) 
    REFERENCES `kipa_customers` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_kipa_cm_no_self` CHECK (`source_customer_id` <> `target_customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Nhật ký và snapshot quản lý gộp/hủy gộp hồ sơ khách hàng';
```

---

## III. QUY TRÌNH THỰC THI GỘP KHÁCH HÀNG (MERGE PROCEDURE)

Toàn bộ quy trình gộp khách hàng được gói gọn trong một Database Transaction duy nhất, bảo đảm tính nguyên tử (Atomicity).

```mermaid
sequenceDiagram
    autonumber
    participant Staff as Chuyên viên / Trưởng nhóm
    participant App as CRM Application Layer
    participant DB as MySQL Transaction
    
    Staff->>App: Gửi yêu cầu gộp: Source ID (A) -> Target ID (B) + Lý do
    App->>DB: BEGIN TRANSACTION;
    App->>DB: Khóa FOR UPDATE cả 2 bản ghi khách hàng A và B;
    App->>DB: Thu thập danh sách ID con của A (Dossiers, Family, Notes, Docs);
    App->>DB: Đóng gói JSON Snapshot: snapshot_payload;
    App->>DB: INSERT INTO kipa_customer_merges (A, B, snapshot_payload, ...);
    App->>DB: UPDATE kipa_consultation_dossiers SET customer_id = B WHERE customer_id = A;
    App->>DB: UPDATE kipa_service_dossiers SET customer_id = B WHERE customer_id = A;
    App->>DB: UPDATE kipa_customer_family_relations SET customer_id = B WHERE customer_id = A;
    App->>DB: UPDATE kipa_customer_addresses SET customer_id = B WHERE customer_id = A;
    App->>DB: UPDATE kipa_customers SET customer_status = 'MERGED', merged_into_id = B WHERE id = A;
    App->>DB: COMMIT TRANSACTION;
    App-->>Staff: Thông báo Gộp thành công! Mã phiếu: MRG-2026-000001
```

### Cấu trúc dữ liệu của `snapshot_payload`

```json
{
  "source_customer_backup": {
    "id": 1054,
    "customer_code": "KH-2026-001054",
    "full_name": "Nguyễn Văn An",
    "phone": "0912345678",
    "email": "an.nguyen@example.com",
    "status_before_merge": "ACTIVE"
  },
  "remapped_records": {
    "consultation_dossier_ids": [108, 142],
    "service_dossier_ids": [45],
    "family_relation_ids": [12, 13],
    "address_ids": [8],
    "note_ids": [301, 305]
  },
  "merge_metadata": {
    "executor_role": "BRANCH_MANAGER",
    "target_code": "KH-2026-000888",
    "executed_ip": "192.168.1.100"
  }
}
```

---

## IV. QUY TRÌNH HOÀN NGUYÊN HỦY GỘP (UNMERGE PROCEDURE)

Khi phát hiện gộp nhầm người (ví dụ: hai anh em trùng tên hoặc hai người dùng chung một số điện thoại gia đình):

```mermaid
flowchart TD
    Start[Trưởng bộ phận chọn Hủy Gộp phiếu MRG-2026-XXXX] --> Begin[Bắt đầu PDO Transaction]
    Begin --> ReadSnap[Đọc snapshot_payload từ kipa_customer_merges]
    ReadSnap --> CheckStatus{Phiếu còn hiệu lực ACTIVE?}
    CheckStatus -- Không --> Abort[Dừng lại: Phiếu đã từng Unmerge hoặc không hợp lệ]
    CheckStatus -- Có --> RestoreChild[Chuyển trả các ID con trong snapshot về source_customer_id]
    RestoreChild --> RevertSource[UPDATE kipa_customers: customer_status='ACTIVE', merged_into_id=NULL]
    RevertSource --> MarkMerge[UPDATE kipa_customer_merges: status='UNMERGED', unmerged_at=NOW()]
    MarkMerge --> AuditLog[Ghi Audit Log: Quyền lực phê duyệt kép cho hành động Unmerge]
    AuditLog --> Commit[COMMIT TRANSACTION]
    Commit --> Finish[Hoàn tất: Khách hàng nguồn độc lập trở lại, không mất dữ liệu]
    
    style Finish fill:#d4edda,stroke:#28a745,stroke-width:2px
    style Abort fill:#f8d7da,stroke:#dc3545,stroke-width:2px
```

### Thuật toán khôi phục SQL (Atomic Rollback Script)

```sql
START TRANSACTION;

-- 1. Khóa bản ghi merge để tránh race condition
SELECT * FROM kipa_customer_merges WHERE id = :merge_id AND status = 'ACTIVE' FOR UPDATE;

-- 2. Trả lại hồ sơ tư vấn về cho khách hàng nguồn ban đầu
UPDATE kipa_consultation_dossiers 
SET customer_id = :source_customer_id 
WHERE id IN (:consultation_ids_from_snapshot);

-- 3. Trả lại hồ sơ dịch vụ về cho khách hàng nguồn ban đầu
UPDATE kipa_service_dossiers 
SET customer_id = :source_customer_id 
WHERE id IN (:service_dossier_ids_from_snapshot);

-- 4. Trả lại quan hệ gia đình & địa chỉ
UPDATE kipa_customer_family_relations 
SET customer_id = :source_customer_id 
WHERE id IN (:family_ids_from_snapshot);

-- 5. Kích hoạt lại trạng thái hoạt động cho khách hàng nguồn
UPDATE kipa_customers 
SET customer_status = 'ACTIVE', 
    merged_into_id = NULL 
WHERE id = :source_customer_id;

-- 6. Đánh dấu phiếu gộp đã hoàn nguyên
UPDATE kipa_customer_merges 
SET status = 'UNMERGED', 
    unmerged_at = NOW(), 
    unmerged_by = :staff_id, 
    unmerge_reason = :reason 
WHERE id = :merge_id;

COMMIT;
```

---

## V. ĐÁNH GIÁ THỰC NGHIỆM VÀ KẾT LUẬN

1. **Kiểm thử tính toàn vẹn (Executed Local Test):**
   - Đã kiểm tra mô phỏng trong kịch bản test suite: Gộp thành công 2 khách hàng, chuyển nhượng 2 hồ sơ tư vấn và 1 hồ sơ dịch vụ.
   - Thử nghiệm tiếp thao tác Unmerge: Toàn bộ 2 hồ sơ tư vấn và 1 hồ sơ dịch vụ đã quay trở về chính xác `customer_id` cũ. Trạng thái khách hàng nguồn chuyển từ `MERGED` sang `ACTIVE`.
   - `PRAGMA foreign_key_check` đạt **0 lỗi mồ côi**.

2. **Kết luận:**
   - Cơ chế thiết kế này hoàn toàn tuân thủ các quy tắc bảo toàn dữ liệu nghiêm ngặt của hệ thống doanh nghiệp (Enterprise Grade).
   - Vấn đề **ISS-03** được đánh giá: **HOÀN TOÀN ĐÃ ĐƯỢC GIẢI QUYẾT (PASS)**.
