# KIPA VIỆT HÀN — ĐẶC TẢ CHI TIẾT CÁC BẢNG BỔ SUNG (DATABASE NEW TABLES SPEC)

> **Tài liệu đặc tả kỹ thuật DDL:** Thiết kế chi tiết 25 bảng bổ sung cho KIPA CRM và KIPA Student Portal  
> **Quy chuẩn kỹ thuật:** MySQL 8.0+ / MariaDB 10.5+, Engine `InnoDB`, Charset `utf8mb4`, Collation `utf8mb4_unicode_ci`  
> **Khóa chính:** `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`  

---

## I. MỤC TIÊU VÀ NGUYÊN TẮC THIẾT KẾ DDL

1. **Không tạo database thứ hai:** Tất cả các bảng được tạo trực tiếp trên database hiện tại của Website KIPA.
2. **Không trùng lặp:** Tái sử dụng bảng đăng nhập `kipa_users` và lead web `kipa_leads`.
3. **Bảo toàn dữ liệu cũ:** Tuyệt đối không can thiệp, không đổi tên bảng/cột cũ.
4. **Chống xóa dây chuyền:** Mọi khóa ngoại trỏ tới `kipa_leads` đều cấu hình `ON DELETE SET NULL`.
5. **Đầy đủ Index:** Mọi trường tìm kiếm, lọc (trạng thái, ngày tháng) và khóa ngoại đều có Index tối ưu hiệu năng.
6. **Không lạm dụng JSON:** Chỉ dùng JSON cho dữ liệu linh hoạt (như `tasks_json`), các trường cần tìm kiếm/lọc/thống kê đều tách cột chuẩn hóa bậc 3 (3NF).

---

## II. ĐẶC TẢ CHI TIẾT 18 BẢNG KIPA CRM

### 1. `kipa_branches` (Hệ thống Chi Nhánh & Văn Phòng Đại Diện)
- **Mục đích:** Quản lý danh mục 7 chi nhánh/văn phòng KIPA (Trụ sở Hải Phòng, Hà Nội, TP.HCM, Seoul, Sydney...).
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `branch_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (Mã chi nhánh: `HP_HQ`, `HN_01`, `HCM_01`, `SEOUL_01`, `SYDNEY_01`)
  - `branch_name`: `VARCHAR(160) NOT NULL` (Tên chi nhánh)
  - `phone`: `VARCHAR(32) NOT NULL`
  - `email`: `VARCHAR(254) NULL`
  - `address`: `VARCHAR(255) NOT NULL`
  - `province`: `VARCHAR(100) NOT NULL`
  - `country`: `VARCHAR(64) NOT NULL DEFAULT 'Việt Nam'`
  - `manager_name`: `VARCHAR(160) NULL` (Giám đốc chi nhánh)
  - `is_active`: `BOOLEAN NOT NULL DEFAULT 1`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`

### 2. `kipa_departments` (Phòng Ban Nội Bộ)
- **Mục đích:** Phân loại đơn vị chức năng trong KIPA.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `branch_id`: `BIGINT UNSIGNED NULL`
  - `dept_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (`TS_TUYENSINH`, `HS_VISA`, `DT_TIENGHAN`, `DN_QUOCTE`, `KT_TAICHINH`)
  - `dept_name`: `VARCHAR(128) NOT NULL`
  - `description`: `TEXT NULL`
  - `is_active`: `BOOLEAN NOT NULL DEFAULT 1`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
- **Khóa ngoại:** `FOREIGN KEY (branch_id) REFERENCES kipa_branches(id) ON DELETE SET NULL`

### 3. `kipa_staff` (Hồ Sơ Cán Bộ Nhân Viên KIPA)
- **Mục đích:** Quản lý nhân sự KIPA (Tư vấn viên, Chuyên viên xử lý hồ sơ, Giáo viên tiếng Hàn, Kế toán).
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `staff_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (Mã nhân viên: `NV-2026-001`)
  - `admin_id`: `BIGINT UNSIGNED NULL UNIQUE KEY` (Liên kết tài khoản admin kỹ thuật)
  - `full_name`: `VARCHAR(160) NOT NULL`
  - `email`: `VARCHAR(254) NOT NULL` (KEY `idx_staff_email`)
  - `phone`: `VARCHAR(32) NOT NULL`
  - `branch_id`: `BIGINT UNSIGNED NULL`
  - `dept_id`: `BIGINT UNSIGNED NULL`
  - `position`: `VARCHAR(128) NOT NULL` (`Trưởng phòng`, `Chuyên viên tư vấn`, `Chuyên viên hồ sơ`, `Giáo viên`)
  - `avatar_url`: `VARCHAR(255) NULL`
  - `is_active`: `BOOLEAN NOT NULL DEFAULT 1`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `updated_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Khóa ngoại:**
  - `FOREIGN KEY (admin_id) REFERENCES kipa_admins(id) ON DELETE SET NULL`
  - `FOREIGN KEY (branch_id) REFERENCES kipa_branches(id) ON DELETE SET NULL`
  - `FOREIGN KEY (dept_id) REFERENCES kipa_departments(id) ON DELETE SET NULL`

### 4. `kipa_roles` (Vai Trò Hệ Thống CRM)
- **Mục đích:** Danh mục vai trò trong hệ thống RBAC.
- **Cấu trúc:** `id`, `role_code` (VARCHAR 32 UNIQUE), `role_name`, `description`, `is_system`, `created_at`.

### 5. `kipa_permissions` & `kipa_staff_roles`
- **Mục đích:** Danh mục quyền hạn và bảng trung gian gán vai trò cho nhân sự.

### 6. `kipa_customers` (Hồ Sơ Khách Hàng Trung Tâm)
- **Mục đích:** Lưu trữ toàn bộ khách hàng tư vấn, ký hợp đồng và học viên KIPA.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `customer_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (`KH-2026-0001`)
  - `full_name`: `VARCHAR(160) NOT NULL`
  - `phone`: `VARCHAR(32) NOT NULL` (KEY `idx_cust_phone`)
  - `email`: `VARCHAR(254) NULL` (KEY `idx_cust_email`)
  - `birthday`: `DATE NULL`
  - `gender`: `VARCHAR(16) NULL` (`Nam`, `Nữ`, `Khác`)
  - `id_card_number`: `VARCHAR(32) NULL` (Số CCCD / Hộ chiếu)
  - `id_card_date`: `DATE NULL`
  - `id_card_place`: `VARCHAR(128) NULL`
  - `user_id`: `BIGINT UNSIGNED NULL` (Liên kết tài khoản Portal `kipa_users(id)`)
  - `legacy_lead_id`: `BIGINT UNSIGNED NULL` (Liên kết Lead gốc `kipa_leads(id)`)
  - `lead_source_id`: `BIGINT UNSIGNED NULL`
  - `assigned_staff_id`: `BIGINT UNSIGNED NULL` (Tư vấn viên phụ trách)
  - `branch_id`: `BIGINT UNSIGNED NULL` (Chi nhánh tiếp nhận)
  - `customer_status`: `VARCHAR(64) NOT NULL DEFAULT 'Mới tiếp nhận'` (`Mới tiếp nhận`, `Đang tư vấn`, `Đã ký hợp đồng`, `Đang xử lý hồ sơ`, `Đã xuất cảnh/Nhập học`, `Tạm dừng`, `Thất bại`)
  - `notes`: `TEXT NULL`
  - `created_by`: `BIGINT UNSIGNED NULL`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `updated_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Khóa ngoại an toàn:**
  - `FOREIGN KEY (user_id) REFERENCES kipa_users(id) ON DELETE SET NULL`
  - `FOREIGN KEY (legacy_lead_id) REFERENCES kipa_leads(id) ON DELETE SET NULL` **(Bảo vệ chống xóa lead)**
  - `FOREIGN KEY (assigned_staff_id) REFERENCES kipa_staff(id) ON DELETE SET NULL`
  - `FOREIGN KEY (branch_id) REFERENCES kipa_branches(id) ON DELETE SET NULL`

### 7. `kipa_customer_addresses` (Sổ Địa Chỉ)
- **Mục đích:** Lưu hộ khẩu thường trú, tạm trú của khách hàng phục vụ hồ sơ visa.
- **Khóa ngoại:** `FOREIGN KEY (customer_id) REFERENCES kipa_customers(id) ON DELETE CASCADE`

### 8. `kipa_customer_relations` (Quan Hệ Nhân Thân & Khẩn Cấp)
- **Mục đích:** Bố, mẹ, vợ, chồng phục vụ chứng minh tài chính và bảo lãnh du học.
- **Khóa ngoại:** `FOREIGN KEY (customer_id) REFERENCES kipa_customers(id) ON DELETE CASCADE`

### 9. `kipa_customer_education` (Hồ Sơ Học Vấn & Ngoại Ngữ)
- **Mục đích:** Học bạ cấp 3, trường đại học cũ, điểm GPA, chứng chỉ TOPIK/IELTS.
- **Khóa ngoại:** `FOREIGN KEY (customer_id) REFERENCES kipa_customers(id) ON DELETE CASCADE`

### 10. `kipa_service_dossiers` (Hồ Sơ Dịch Vụ / Hợp Đồng Du Học - XKLĐ)
- **Mục đích:** Thực thể quản lý xuyên suốt hành trình dịch vụ của khách hàng.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `dossier_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (`HS-2026-KR-0042`)
  - `customer_id`: `BIGINT UNSIGNED NOT NULL`
  - `service_type`: `VARCHAR(64) NOT NULL` (`Du học Hàn Quốc`, `Du học Úc`, `Du học Nhật Bản`, `XKLĐ Hàn Quốc EPS`, `XKLĐ Visa E-7`)
  - `program_code`: `VARCHAR(64) NULL` (`KR-D2-2026`)
  - `target_country`: `VARCHAR(64) NOT NULL DEFAULT 'Hàn Quốc'`
  - `target_intake`: `VARCHAR(64) NULL` (`Tháng 09/2027`)
  - `target_school_name`: `VARCHAR(160) NULL`
  - `target_major`: `VARCHAR(160) NULL`
  - `stage`: `VARCHAR(64) NOT NULL DEFAULT 'Tư vấn định hướng'` (`Tư vấn định hướng`, `Học tiếng`, `Chuẩn bị hồ sơ`, `Nộp hồ sơ xét tuyển`, `Thủ tục visa`, `Chuẩn bị xuất cảnh`, `Hoàn thành`)
  - `progress_percent`: `TINYINT UNSIGNED NOT NULL DEFAULT 0`
  - `consultant_id`: `BIGINT UNSIGNED NULL` (Chuyên viên tư vấn)
  - `case_officer_id`: `BIGINT UNSIGNED NULL` (Chuyên viên hồ sơ)
  - `contract_number`: `VARCHAR(64) NULL`
  - `contract_signed_at`: `DATE NULL`
  - `status`: `VARCHAR(32) NOT NULL DEFAULT 'Đang xử lý'` (`Mới tạo`, `Đang xử lý`, `Chờ visa`, `Đã cấp visa`, `Đã bay`, `Tạm dừng`, `Đã thanh lý`)
  - `notes`: `TEXT NULL`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `updated_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Khóa ngoại:**
  - `FOREIGN KEY (customer_id) REFERENCES kipa_customers(id) ON DELETE RESTRICT`
  - `FOREIGN KEY (consultant_id) REFERENCES kipa_staff(id) ON DELETE SET NULL`
  - `FOREIGN KEY (case_officer_id) REFERENCES kipa_staff(id) ON DELETE SET NULL`

### 11. `kipa_dossier_assignments` (Lịch Sử Phân Công Hồ Sơ)
- **Mục đích:** Theo dõi người phụ trách, hỗ trợ bàn giao hồ sơ khi luân chuyển nhân sự.

### 12. `kipa_tasks` (Nhiệm Vụ Công Việc)
- **Mục đích:** Việc cần làm theo checklist thời hạn (nhắc học viên nộp CCCD, nộp học bạ công chứng, khám sức khỏe).

### 13. `kipa_dossier_documents` (Danh Mục Giấy Tờ Theo Checklist)
- **Mục đích:** Danh sách giấy tờ cá nhân hóa theo từng chương trình du học / XKLĐ.
- **Trạng thái:** `Chưa tải lên`, `Đã tải lên`, `Đang kiểm tra`, `Cần bổ sung`, `Đã xác nhận`.

### 14. `kipa_document_versions` (Các Phiên Bản File Tải Lên)
- **Mục đích:** Lưu lịch sử các lần upload file scan, PDF, ảnh bổ sung, chống ghi đè mất file cũ.

### 15. `kipa_document_reviews` (Lịch Sử Thẩm Định Giấy Tờ)
- **Mục đích:** Ghi nhận lý do yêu cầu bổ sung của chuyên viên KIPA và thời gian duyệt.

### 16. `kipa_approvals` (Quy Trình Phê Duyệt Hồ Sơ)
- **Mục đích:** Phê duyệt hồ sơ trước khi gửi trường Hàn Quốc hoặc nộp Đại sứ quán.

### 17. `kipa_audit_logs` (Nhật Ký Thao Tác Hệ Thống)
- **Mục đích:** Ghi log bảo mật (ai sửa gì, lúc nào, giá trị cũ/mới, IP).

### 18. `kipa_notification_settings` (Cài Đặt Thông Báo)
- **Mục đích:** Cài đặt kênh nhận thông báo của học viên (Email, Zalo, SMS, Web).

---

## III. ĐẶC TẢ 7 BẢNG KIPA STUDENT PORTAL

1. `kipa_students`: Bảng hồ sơ học viên độc lập dùng nạp nhanh vào giao diện Portal `ho-so.html`.
2. `kipa_student_journey`: 5 mốc tiến độ kèm việc cần làm.
3. `kipa_student_documents`: Danh mục giấy tờ hiển thị trên ví hồ sơ số của học viên.
4. `kipa_student_schedules`: Lịch học ngoại ngữ, phỏng vấn, tư vấn của học viên.
5. `kipa_student_notifications`: Danh sách thông báo học viên kèm cờ khẩn cấp và trạng thái đã đọc.
6. `kipa_student_materials`: Thư viện tài liệu học tập, giáo trình TOPIK dạng PDF.
7. `kipa_student_support`: Kênh gửi yêu cầu hỗ trợ và trao đổi 2 chiều với cố vấn.

---

## IV. TỆP SQL DDL KHỞI TẠO HOÀN CHỈNH

Tất cả các câu lệnh DDL `CREATE TABLE IF NOT EXISTS` và `ALTER TABLE` đã được biên soạn hoàn chỉnh, kiểm thử cú pháp kỹ càng và lưu trữ tại đường dẫn:
**`backend/crm_portal_schema.sql`**
