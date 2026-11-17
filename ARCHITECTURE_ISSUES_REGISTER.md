# KIPA VIỆT HÀN — SỔ ĐĂNG KÝ VẤN ĐỀ VÀ KHOẢNG TRỐNG KIẾN TRÚC (ARCHITECTURE ISSUES REGISTER)

> **Tài liệu quản trị rủi ro kỹ thuật:** Ghi nhận toàn bộ các phát hiện (Findings), khoảng trống kiến trúc (Gaps) và vấn đề phát sinh trong quá trình kiểm thử tổng thể Hệ sinh thái KIPA  
> **Phiên bản:** 1.0.0  
> **Chuyên trách:** Senior Database Architect & Technical Lead  
> **Nguyên tắc xử lý:** Mọi vấn đề đều được phân loại rõ ràng, có giải pháp kỹ thuật cụ thể và đánh giá mức độ ảnh hưởng trước khi đưa ra quyết định triển khai Production  

---

## I. MA TRẬN TỔNG HỢP VẤN ĐỀ & KHOẢNG TRỐNG KIẾN TRÚC

| Mã Vấn Đề | Phân Hệ Ảnh Hưởng | Tiêu Đề Vấn Đề / Khoảng Trống Kiến Trúc | Mức Độ | Trạng Thái | Tác Động Triển Khai Production |
|---|---|---|:---:|:---:|:---:|
| **ISS-01** | Database DDL | Trùng lặp định nghĩa 3 bảng giữa CMS (`content-schema.sql`) và CRM | **HIGH** | `RESOLVED IN SPEC` | **Không chặn CRM** (Chỉ cần lưu ý khi deploy CMS) |
| **ISS-02** | Webhook & Sync | Thiếu bảng chuyên dụng `kipa_webhook_queue` để retry tự động | **MEDIUM** | `PROPOSED FOR STAGE 2` | **Không chặn CRM** (Giai đoạn 1 dùng PHP synchronous + Audit Log) |
| **ISS-03** | Customer Master | Thiếu bảng `kipa_customer_merges` ghi nhận lịch sử gộp khách trùng | **MEDIUM** | `PROPOSED FOR STAGE 2` | **Không chặn CRM** (Tạm thời lưu vết vào `kipa_audit_logs`) |
| **ISS-04** | Consultation | Đồng bộ 8 trạng thái tiếng Việt KIPA vào ENUM/Comment của `stage` | **LOW** | `DOCUMENTED & ALIGNED` | **Không chặn CRM** (`VARCHAR(32)` đã hỗ trợ trọn vẹn cả 8 mã) |
| **ISS-05** | Website Codebase | Lệnh xóa Lead cũ 12 tháng trong `api/cleanup.php` & `api/submit.php` | **HIGH** | `PATCH PREPARED` | **Cần áp dụng bản vá** đồng thời khi kích hoạt CRM trên Production |

---

## II. CHI TIẾT TỪNG VẤN ĐỀ VÀ PHƯƠNG ÁN XỬ LÝ KỸ THUẬT

### 1. VẤN ĐỀ ISS-01: Trùng lặp định nghĩa DDL 3 bảng chia sẻ giữa Website CMS và CRM
- **Mô tả:** Trong file `backend/content-schema.sql` (Group G) có câu lệnh `CREATE TABLE IF NOT EXISTS` cho 3 bảng: `kipa_lead_sources` (dòng 1288), `kipa_consultation_interests` (dòng 1186), `kipa_consultation_activities` (dòng 1322). File `crm_portal_schema.sql` cũng định nghĩa 3 bảng này với cấu trúc nâng cao hơn.
- **Nguyên nhân gốc rễ:** Thiết kế CMS Staged được lập từ trước; khi thiết kế phân hệ CRM chuyên sâu, 3 bảng này được nâng cấp thành các bảng Master dùng chung cho toàn hệ thống.
- **Giải pháp kỹ thuật:**
  1. Chỉ định `crm_portal_schema.sql` là **Source of Truth** duy nhất cho 3 bảng này trên database cPanel.
  2. Khi triển khai 85 bảng CMS trong tương lai, kỹ thuật viên chỉ cần bỏ qua hoặc xóa 3 khối DDL này trong file `content-schema.sql`.
- **Đánh giá rủi ro Production:** Không ảnh hưởng đến việc triển khai CRM hiện tại.

---

### 2. VẤN ĐỀ ISS-02: Thiếu bảng Hàng đợi Retry đồng bộ Webhook (`kipa_webhook_queue`)
- **Mô tả:** Nghiệp vụ KT02 yêu cầu: "Retry khi CRM không phản hồi; Chống trùng khi webhook gửi lại". Hiện tại schema CRM có 42 bảng nhưng chưa có bảng lưu hàng đợi retry độc lập khi mạng hosting gặp sự cố.
- **Nguyên nhân gốc rễ:** Schema v2.3.0 tập trung hoàn thiện 42 bảng nghiệp vụ cốt lõi và kiểm toán chung qua `kipa_audit_logs`.
- **Giải pháp kỹ thuật:**
  1. *Giai đoạn 1 (Hiện tại):* Website và CRM cùng nằm trên 1 database MySQL duy nhất trên cPanel, nên việc đồng bộ dữ liệu thực chất là ghi trực tiếp qua kết nối PDO nội bộ (`kipa_db()`), không phụ thuộc vào HTTP Webhook ra bên ngoài. Khả năng timeout qua mạng internet là bằng 0%.
  2. *Giai đoạn 2 (Khi tách microservices):* Bổ sung bảng `kipa_webhook_queue` (đã có thiết kế chi tiết trong `DATA_SYNC_TEST_REPORT.md`).
- **Đánh giá rủi ro Production:** Rủi ro Thấp. Không chặn việc triển khai CRM trên chung 1 database.

---

### 3. VẤN ĐỀ ISS-03: Thiếu bảng Nhật ký gộp khách hàng trùng (`kipa_customer_merges`)
- **Mô tả:** Nghiệp vụ KT03 yêu cầu: "Merge khách hàng trùng có phê duyệt và lịch sử". Bảng `kipa_customers` hiện lưu đầy đủ thông tin định danh nhưng chưa có bảng chuyên dụng ghi vết lịch sử gộp khách hàng.
- **Nguyên nhân gốc rễ:** Các thao tác gộp khách hàng trước đây được thiết kế lưu vào bảng kiểm toán chung `kipa_audit_logs`.
- **Giải pháp kỹ thuật:**
  1. *Giai đoạn 1:* Sử dụng `kipa_audit_logs` với `action = 'MERGE_CUSTOMER'`, `entity_type = 'kipa_customers'`, `old_values = snapshot_json` của khách hàng bị sáp nhập.
  2. *Giai đoạn 2:* Khi lượng khách hàng vượt 50.000 hồ sơ, tạo bảng `kipa_customer_merges` để có màn hình chuyên biệt cho phép Ban Giám Đốc bấm "Hoàn nguyên (Unmerge)".
- **Đánh giá rủi ro Production:** Rủi ro Thấp. Không chặn triển khai.

---

### 4. VẤN ĐỀ ISS-04: Đồng bộ 8 trạng thái tiếng Việt KIPA vào ENUM Pipeline
- **Mô tả:** Nghiệp vụ KT04 định rõ 8 mốc pipeline: Mới tiếp nhận, Đã liên hệ, Đang tìm hiểu nhu cầu, Đang tư vấn, Chờ khách quyết định, Đồng ý sử dụng dịch vụ, Không tiếp tục, Chưa liên lạc được. Schema v2.3.0 có comment 7 trạng thái.
- **Nguyên nhân gốc rễ:** Thiếu sót trong việc liệt kê đầy đủ 2 trạng thái `NEED_ANALYSIS` và `UNREACHABLE` vào phần comment DDL.
- **Giải pháp kỹ thuật:**
  - Kiểu dữ liệu của `kipa_consultation_dossiers.stage` là `VARCHAR(32)`, không phải `ENUM` cứng của MySQL. Do đó, hệ thống chấp nhận lưu trữ bất kỳ mã nào trong số 8 mã chuẩn KIPA mà không cần phải chạy lại lệnh `ALTER TABLE`.
  - Cập nhật chuẩn hóa mapping trong mã nguồn backend PHP và tài liệu đặc tả.
- **Đánh giá rủi ro Production:** Hoàn toàn an toàn. Không chặn triển khai.

---

### 5. VẤN ĐỀ ISS-05: Lệnh xóa Lead cũ 12 tháng trong `cleanup.php` & `submit.php`
- **Mô tả:** Lệnh `$db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH')` trong cả `api/cleanup.php` (dòng 16) và `api/submit.php` (dòng 200) sẽ xóa mất Lead cũ nếu chưa bổ sung điều kiện lọc.
- **Nguyên nhân gốc rễ:** Mã nguồn website ban đầu chỉ phục vụ nộp form tiếp thị, có cơ chế dọn dẹp lead rác sau 12 tháng để giảm tải dung lượng.
- **Giải pháp kỹ thuật:**
  1. Ở tầng Database: Khóa ngoại `fk_kipa_customers_lead` và `fk_kipa_cd_lead` đã được thiết lập `ON DELETE SET NULL`, bảo vệ khách hàng và hồ sơ tư vấn không bao giờ bị xóa mất theo dạng cascade.
  2. Ở tầng Mã nguồn: Đã chuẩn bị sẵn đoạn mã vá (Patch):
     `WHERE (converted_to_customer_id IS NULL OR converted_to_customer_id = 0) AND created_at < ...`
  3. Áp dụng bản vá mã nguồn này cùng lúc khi nạp database CRM lên cPanel.
- **Đánh giá rủi ro Production:** Mức độ Cao. Cần triển khai đồng bộ khi golive.
