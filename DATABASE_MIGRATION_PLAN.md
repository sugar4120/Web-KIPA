# KIPA VIỆT HÀN — KẾ HOẠCH TRIỂN KHAI, SAO LƯU & PHỤC HỒI DATABASE (DATABASE MIGRATION PLAN)

> **Tài liệu quy trình vận hành chuẩn (SOP):** Hướng dẫn từng bước triển khai bổ sung 25 bảng CRM & Student Portal trên cPanel Shared Hosting  
> **Mục tiêu tối thượng:** An toàn 100% dữ liệu cũ, không làm gián đoạn website, phòng ngừa xóa dây chuyền và có kịch bản Rollback tức thì.  

---

## I. NGUYÊN TẮC AN TOÀN TRƯỚC KHI THỰC HIỆN

1. **Tuyệt đối không DROP / TRUNCATE:** Không bao giờ chạy lệnh `DROP TABLE`, `DROP COLUMN`, `TRUNCATE TABLE`.
2. **Không đổi tên bảng/cột:** Mọi bảng và cột hiện có được giữ nguyên 100% tên gọi và kiểu dữ liệu.
3. **Cơ chế thêm mới an toàn:**
   - Tạo bảng mới: Dùng cú pháp `CREATE TABLE IF NOT EXISTS`.
   - Bổ sung cột cho bảng cũ: Cột mới bắt buộc phải là `NULL` hoặc có `DEFAULT`, bảo đảm các câu lệnh `INSERT` cũ không bị lỗi thiếu trường.
4. **Quy tắc sao lưu trước khi chạm vào DB:** Phải có ít nhất 1 bản sao lưu toàn diện (.sql) tải về máy an toàn và kiểm tra đọc được trước khi thực hiện bất kỳ câu lệnh DDL nào.

---

## II. QUY TRÌNH 6 BƯỚC TRIỂN KHAI TRÊN CPANEL

### BƯỚC 1: XÁC ĐỊNH MỤC TIÊU & KIỂM TRA TÀI NGUYÊN TRÊN CPANEL
1. Đăng nhập vào cPanel Hosting của KIPA Việt Hàn.
2. Kiểm tra dung lượng Disk Space còn trống (yêu cầu tối thiểu còn trống > 500 MB).
3. Kiểm tra mục **MySQL® Databases**:
   - Xác nhận tên Database đang dùng (theo chuỗi kết nối trong `kipa-private/config.php`).
   - Xác nhận User MySQL đang có đầy đủ các quyền: `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER`, `INDEX`, `REFERENCES`.

---

### BƯỚC 2: SAO LƯU DATABASE ĐẦY ĐỦ (FULL BACKUP PROTOCOL)
Thực hiện sao lưu theo 2 phương thức song song để đảm bảo an toàn tuyệt đối:

#### Phương thức 1: Xuất file qua phpMyAdmin (Giao diện trực quan)
1. Trong cPanel, mở công cụ **phpMyAdmin**.
2. Chọn đúng Database của KIPA ở cột bên trái.
3. Chọn tab **Export (Xuất)** trên thanh menu.
4. Chọn phương thức **Custom (Tùy chỉnh)**:
   - Tables: Chọn **Select All (Chọn tất cả)**.
   - Output: Đặt tên file dạng `kipa_db_backup_truoc_khi_tao_crm_YYYYMMDD_HHMM.sql`. Nén dạng `gzipped` hoặc text thường.
   - Format-specific options: Đánh dấu `Structure and data (Cấu trúc và dữ liệu)`.
   - Object creation options: Đánh dấu `Add DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT statement` (phục vụ rollback khi cần) và `Add CREATE TABLE statement`.
5. Bấm nút **Export (Xuất)** và lưu file an toàn vào thư mục lưu trữ nội bộ.

#### Phương thức 2: Chạy lệnh mysqldump qua cPanel Terminal / Cron (Nếu có quyền SSH/CLI)
```bash
mysqldump -u <db_user> -p'<db_password>' <db_name> > ~/kipa-private/backup_pre_crm_$(date +%Y%m%d_%H%M%S).sql
```
- Kiểm tra dung lượng file sao lưu: File phải có kích thước > 0 bytes và phần cuối file có dòng `-- Dump completed on ...`.

---

### BƯỚC 3: MỞ RỘNG AN TOÀN CHO 2 BẢNG HIỆN HỮU (`kipa_leads` và `kipa_users`)

Trong phpMyAdmin -> Tab **SQL**, dán và thực thi đoạn lệnh sau:

```sql
-- 1. Bổ sung liên kết an toàn cho kipa_leads (chống xóa dây chuyền khi cleanup)
ALTER TABLE `kipa_leads` 
  ADD COLUMN IF NOT EXISTS `converted_to_customer_id` BIGINT UNSIGNED NULL AFTER `details_json`,
  ADD COLUMN IF NOT EXISTS `lead_status` VARCHAR(32) NOT NULL DEFAULT 'NEW' AFTER `converted_to_customer_id`;

-- 2. Bổ sung các trường quản lý tài khoản cho kipa_users
ALTER TABLE `kipa_users` 
  ADD COLUMN IF NOT EXISTS `avatar_url` VARCHAR(255) NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `is_active` BOOLEAN NOT NULL DEFAULT 1 AFTER `avatar_url`,
  ADD COLUMN IF NOT EXISTS `last_login_at` DATETIME NULL AFTER `email_verified_at`;
```
*(Lưu ý: Nếu phiên bản MySQL/MariaDB không hỗ trợ cú pháp `IF NOT EXISTS` trong `ALTER TABLE`, kiểm tra bằng `SHOW COLUMNS FROM kipa_leads LIKE 'converted_to_customer_id'` trước khi chạy).*

---

### BƯỚC 4: THỰC THI TẠO 25 BẢNG BỔ SUNG (CRM & STUDENT PORTAL)

1. Mở file **`backend/crm_portal_schema.sql`** trong mã nguồn dự án.
2. Sao chép toàn bộ nội dung file.
3. Trong phpMyAdmin -> Chọn Database KIPA -> Chọn tab **SQL**.
4. Dán toàn bộ nội dung file `backend/crm_portal_schema.sql` vào khung nhập liệu.
5. Bấm nút **Go (Thực hiện)**.
6. Kết quả mong đợi: Trình duyệt báo `MySQL returned an empty result set (i.e. zero rows)` hoặc danh sách thông báo thành công xanh lá.

---

### BƯỚC 5: CẬP NHẬT CHÍNH SÁCH LƯU TRỮ TRONG `api/cleanup.php`

Để ngăn chặn vĩnh viễn rủi ro xóa nhầm khách hàng sau 12 tháng, cập nhật file `api/cleanup.php` trên hosting:

- **Đoạn mã cũ (Nguy hiểm):**
  ```php
  $leads = $db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
  ```
- **Đoạn mã mới (Đã bảo vệ an toàn):**
  ```php
  // Chỉ xóa các Lead rác/chưa chuyển đổi thành Khách hàng sau 12 tháng theo chính sách bảo mật
  $leads = $db->exec('DELETE FROM kipa_leads WHERE (converted_to_customer_id IS NULL OR converted_to_customer_id = 0) AND created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
  ```

---

### BƯỚC 6: KIỂM TRA HOÀN TẤT & HỒI QUY (REGRESSION TESTING)

Sau khi chạy xong, thực hiện ngay danh sách kiểm tra sau:

| STT | Hạng mục kiểm tra | Thao tác thực hiện | Tiêu chí đạt |
|---|---|---|---|
| 1 | Số lượng bảng | Trong phpMyAdmin xem tổng số bảng | Xuất hiện thêm đủ 25 bảng mới với tiền tố `kipa_` |
| 2 | Toàn vẹn bảng cũ | Chạy `SELECT COUNT(*) FROM kipa_leads;` | Số lượng bản ghi cũ giữ nguyên 100% |
| 3 | Form Tư vấn Web | Mở trang `https://kipaviethan.edu.vn/dang-ky-tu-van/` và nộp thử | Gửi form thành công, chuyển hướng trang cảm ơn, bản ghi mới xuất hiện trong `kipa_leads` |
| 4 | Đăng ký tài khoản | Mở trang `https://kipaviethan.edu.vn/dang-ky-tai-khoan/` | Nhận mã OTP, hoàn tất đăng ký tài khoản `kipa_users` bình thường |
| 5 | Đăng nhập Portal | Mở trang `https://kipaviethan.edu.vn/ho-so/` | Đăng nhập tài khoản học viên, hiển thị đầy đủ giao diện Cổng thông tin 10 tab |
| 6 | Trang Quản trị | Mở `https://kipaviethan.edu.vn/api/admin.php` | Đăng nhập quản trị viên bình thường, xem danh sách lead không bị lỗi |

---

## III. KỊCH BẢN PHỤC HỒI KHẨN CẤP (ROLLBACK & DISASTER RECOVERY)

Trong tình huống hy hữu phát sinh lỗi không tương thích trong quá trình tạo bảng:

### Tình huống A: Chỉ cần gỡ bỏ các bảng CRM mới tạo (Không ảnh hưởng bảng cũ)
Chạy script drop có chọn lọc (chỉ drop các bảng mới, **tuyệt đối không chạm vào 5 bảng cũ**):
```sql
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS 
  kipa_lms_grades, kipa_lms_exams, kipa_lms_attendance, kipa_lms_enrollments, kipa_lms_lessons, kipa_lms_classes,
  kipa_student_support, kipa_student_materials, kipa_student_notifications, kipa_student_schedules, kipa_student_documents, kipa_student_journey, kipa_students,
  kipa_notification_settings, kipa_audit_logs, kipa_approvals, kipa_document_reviews, kipa_document_versions, kipa_dossier_documents, kipa_tasks, kipa_dossier_assignments, kipa_service_dossiers,
  kipa_customer_education, kipa_customer_relations, kipa_customer_addresses, kipa_customers,
  kipa_staff_roles, kipa_permissions, kipa_roles, kipa_staff, kipa_departments, kipa_branches;
SET FOREIGN_KEY_CHECKS = 1;
```

### Tình huống B: Phục hồi toàn bộ Database từ bản sao lưu phpMyAdmin
1. Mở phpMyAdmin -> Chọn Database KIPA.
2. Chọn tab **Import (Nhập)**.
3. Chọn file backup đã xuất ở Bước 2 (`kipa_db_backup_truoc_khi_tao_crm_YYYYMMDD_HHMM.sql`).
4. Bấm **Go (Thực hiện)** để đưa toàn bộ hệ thống về đúng trạng thái ban đầu.
