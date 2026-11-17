# KIPA VIỆT HÀN — ĐẶC TẢ CHI TIẾT CÁC BẢNG BỔ SUNG (DATABASE NEW TABLES SPEC)

> **Tài liệu đặc tả kỹ thuật DDL:** Thiết kế chi tiết 28 bảng vật lý và 1 SQL View cho KIPA CRM và KIPA Student Portal  
> **Phiên bản:** 2.1.0 (Kiểm toán & Chuẩn hóa cuối cùng trước khi deploy cPanel)  
> **Quy chuẩn kỹ thuật:** MySQL 8.0+ / MariaDB 10.5+, Engine `InnoDB`, Charset `utf8mb4`, Collation `utf8mb4_unicode_ci`  
> **Khóa chính:** `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`  

---

## I. MỤC TIÊU VÀ NGUYÊN TẮC THIẾT KẾ DDL

1. **Một Database duy nhất:** Toàn bộ bảng CRM và Student Portal được triển khai chung trên database hiện tại của Website KIPA.
2. **Không tạo bảng trùng lặp:** Hợp nhất kho tài liệu qua `VIEW kipa_student_documents`, tái sử dụng `kipa_users` làm danh tính Auth.
3. **Bảo toàn dữ liệu cũ 100%:** Tuyệt đối không can thiệp cấu trúc, không đổi tên bảng/cột và không xóa dữ liệu đang hoạt động.
4. **Bảo vệ chống xóa dây chuyền (Cascade Protection):** Mọi khóa ngoại liên kết sang `kipa_leads` và `kipa_users` đều cấu hình `ON DELETE SET NULL`.
5. **Đầy đủ Index tối ưu hóa:** Mọi trường tìm kiếm (email, phone, code), trường trạng thái và khóa ngoại đều có chỉ mục B-Tree riêng.
6. **Không lạm dụng JSON:** Chỉ dùng JSON cho dữ liệu cấu hình linh hoạt (`tasks_json`), các trường nghiệp vụ quan trọng đều chuẩn hóa bậc 3 (3NF).

---

## II. ĐẶC TẢ CHI TIẾT 21 BẢNG KIPA CRM

### 1. `kipa_branches` (Hệ thống Chi Nhánh & Văn Phòng Đại Diện)
- **Mục đích:** Quản lý 7 chi nhánh/văn phòng KIPA (Trụ sở Hải Phòng, Hà Nội, TP.HCM, Seoul, Sydney...).
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `branch_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (`HP_HQ`, `HN_01`, `HCM_01`, `SEOUL_01`, `SYDNEY_01`)
  - `branch_name`: `VARCHAR(160) NOT NULL`
  - `phone`: `VARCHAR(32) NOT NULL`
  - `email`: `VARCHAR(254) NULL`
  - `address`: `VARCHAR(255) NOT NULL`
  - `province`: `VARCHAR(100) NOT NULL`
  - `country`: `VARCHAR(64) NOT NULL DEFAULT 'Việt Nam'`
  - `manager_name`: `VARCHAR(160) NULL`
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
- **Khóa ngoại:** `branch_id -> kipa_branches(id) ON DELETE SET NULL`

### 3. `kipa_teams` (Nhóm Chuyên Môn / Nghiệp Vụ)
- **Mục đích:** Quản lý các nhóm làm việc trực thuộc phòng ban (Team Hàn Quốc 1, Team Du học nghề Úc, Team Visa...).
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `dept_id`: `BIGINT UNSIGNED NOT NULL`
  - `team_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (`TEAM_HQ_01`, `TEAM_UC_01`, `TEAM_VISA`)
  - `team_name`: `VARCHAR(128) NOT NULL`
  - `leader_id`: `BIGINT UNSIGNED NULL` (Trưởng nhóm)
  - `description`: `VARCHAR(255) NULL`
  - `is_active`: `BOOLEAN NOT NULL DEFAULT 1`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
- **Khóa ngoại:** `dept_id -> kipa_departments(id) ON DELETE CASCADE`

### 4. `kipa_staff` (Hồ Sơ Cán Bộ Nhân Viên KIPA)
- **Mục đích:** Quản lý cán bộ nhân viên, tư vấn viên, chuyên viên hồ sơ, giáo viên và cộng tác viên KIPA.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `staff_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (`NV-2026-001`)
  - `admin_id`: `BIGINT UNSIGNED NULL UNIQUE KEY` (Liên kết tài khoản admin kỹ thuật)
  - `full_name`: `VARCHAR(160) NOT NULL`
  - `email`: `VARCHAR(254) NOT NULL` (KEY `idx_kipa_staff_email`)
  - `phone`: `VARCHAR(32) NOT NULL`
  - `branch_id`: `BIGINT UNSIGNED NULL`
  - `dept_id`: `BIGINT UNSIGNED NULL`
  - `team_id`: `BIGINT UNSIGNED NULL`
  - `position`: `VARCHAR(128) NOT NULL` (`Giám đốc chi nhánh`, `Trưởng nhóm`, `Cố vấn tư vấn`, `Chuyên viên hồ sơ`, `Giáo viên`)
  - `avatar_url`: `VARCHAR(255) NULL`
  - `is_active`: `BOOLEAN NOT NULL DEFAULT 1`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `updated_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Khóa ngoại:**
  - `admin_id -> kipa_admins(id) ON DELETE SET NULL`
  - `branch_id -> kipa_branches(id) ON DELETE SET NULL`
  - `dept_id -> kipa_departments(id) ON DELETE SET NULL`
  - `team_id -> kipa_teams(id) ON DELETE SET NULL`

### 5. `kipa_roles` (Vai Trò Hệ Thống CRM)
- **Mục đích:** Danh mục vai trò trong hệ thống RBAC (`ADMIN`, `DIRECTOR`, `BRANCH_MANAGER`, `CONSULTANT`, `CASE_OFFICER`, `TEACHER`, `COLLABORATOR`).
- **Cấu trúc trường:** `id`, `role_code` (VARCHAR 32 UNIQUE), `role_name`, `description`, `is_system`, `created_at`.

### 6. `kipa_permissions` (Danh Mục Quyền Hạn Chi Tiết)
- **Mục đích:** Quyền hạn chi tiết trên CRM (`view_leads`, `assign_dossier`, `review_docs`, `approve_visa`, `export_contract`...).
- **Cấu trúc trường:** `id`, `permission_code` (VARCHAR 64 UNIQUE), `permission_name`, `module_name`, `description`.

### 7. `kipa_staff_roles` (Bảng Gán Vai Trò & Phân Quyền Nhân Viên)
- **Mục đích:** Gán vai trò cho nhân viên kèm phạm vi dữ liệu (`scope`: `GLOBAL`, `BRANCH`, `DEPARTMENT`, `ASSIGNED_ONLY`), người cấp (`granted_by`) và thời hạn tạm thời (`expires_at`).
- **Cấu trúc trường:** `staff_id`, `role_id`, `scope`, `granted_by`, `assigned_at`, `expires_at`.
- **Khóa chính:** `PRIMARY KEY (staff_id, role_id)`.
- **Khóa ngoại:** `staff_id -> kipa_staff(id) ON DELETE CASCADE`, `role_id -> kipa_roles(id) ON DELETE CASCADE`, `granted_by -> kipa_staff(id) ON DELETE SET NULL`.

### 8. `kipa_role_delegations` (Ủy Quyền Vai Trò Tạm Thời)
- **Mục đích:** Hỗ trợ nhân sự ủy quyền xử lý hồ sơ/phê duyệt khi đi công tác, nghỉ phép từ ngày đến ngày mà không cần chia sẻ mật khẩu.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `delegator_id`: `BIGINT UNSIGNED NOT NULL` (Người ủy quyền)
  - `delegatee_id`: `BIGINT UNSIGNED NOT NULL` (Người được ủy quyền)
  - `role_id`: `BIGINT UNSIGNED NOT NULL`
  - `start_date`: `DATE NOT NULL`
  - `end_date`: `DATE NOT NULL`
  - `reason`: `VARCHAR(255) NULL`
  - `is_active`: `BOOLEAN NOT NULL DEFAULT 1`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
- **Khóa ngoại:** `delegator_id -> kipa_staff(id) ON DELETE CASCADE`, `delegatee_id -> kipa_staff(id) ON DELETE CASCADE`, `role_id -> kipa_roles(id) ON DELETE CASCADE`.

### 9. `kipa_customers` (Hồ Sơ Khách Hàng Trung Tâm)
- **Mục đích:** Quản lý toàn bộ khách hàng tư vấn, ký hợp đồng và học viên KIPA.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `customer_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (`KH-2026-0001`)
  - `full_name`: `VARCHAR(160) NOT NULL`
  - `phone`: `VARCHAR(32) NOT NULL` (KEY `idx_kipa_customers_phone`)
  - `email`: `VARCHAR(254) NULL` (KEY `idx_kipa_customers_email`)
  - `birthday`: `DATE NULL`
  - `gender`: `VARCHAR(16) NULL`
  - `id_card_number`: `VARCHAR(32) NULL` (CCCD / Hộ chiếu)
  - `id_card_date`: `DATE NULL`
  - `id_card_place`: `VARCHAR(128) NULL`
  - `user_id`: `BIGINT UNSIGNED NULL` (Liên kết tài khoản Portal `kipa_users`)
  - `legacy_lead_id`: `BIGINT UNSIGNED NULL` (Liên kết Lead gốc `kipa_leads`)
  - `lead_source_id`: `BIGINT UNSIGNED NULL`
  - `assigned_staff_id`: `BIGINT UNSIGNED NULL`
  - `branch_id`: `BIGINT UNSIGNED NULL`
  - `customer_status`: `VARCHAR(64) NOT NULL DEFAULT 'Mới tiếp nhận'`
  - `notes`: `TEXT NULL`
  - `created_by`: `BIGINT UNSIGNED NULL`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `updated_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Khóa ngoại:**
  - `user_id -> kipa_users(id) ON DELETE SET NULL`
  - `legacy_lead_id -> kipa_leads(id) ON DELETE SET NULL` (Bảo vệ khách hàng khi xóa lead sau 12 tháng)
  - `assigned_staff_id -> kipa_staff(id) ON DELETE SET NULL`
  - `branch_id -> kipa_branches(id) ON DELETE SET NULL`

### 10. `kipa_customer_addresses` (Sổ Địa Chỉ Khách Hàng)
- **Mục đích:** Lưu hộ khẩu thường trú, tạm trú, địa chỉ gửi hồ sơ visa.
- **Cấu trúc trường:** `id`, `customer_id`, `address_type`, `street_address`, `ward`, `district`, `province`, `country`, `is_default`, `created_at`.
- **Khóa ngoại:** `customer_id -> kipa_customers(id) ON DELETE CASCADE`.

### 11. `kipa_customer_relations` (Quan Hệ Nhân Thân & Bảo Lãnh)
- **Mục đích:** Lưu thông tin Bố, Mẹ, Vợ/Chồng phục vụ chứng minh tài chính mở sổ tiết kiệm ngân hàng, làm thủ tục bảo lãnh visa và người liên hệ khẩn cấp.
- **Cấu trúc trường:** `id`, `customer_id`, `relation_type` (`Bố`, `Mẹ`, `Vợ/Chồng`, `Người bảo lãnh`), `full_name`, `phone`, `email`, `occupation`, `birth_year`, `id_card_number`, `address`, `monthly_income`, `is_emergency`, `created_at`.
- **Khóa ngoại:** `customer_id -> kipa_customers(id) ON DELETE CASCADE`.

### 12. `kipa_customer_education` (Hồ Sơ Học Vấn & Ngoại Ngữ)
- **Mục đích:** Lưu lịch sử học bạ cấp 3, đại học (GPA) và chứng chỉ ngoại ngữ (TOPIK/KLAT/IELTS) phục vụ nộp trường.
- **Cấu trúc trường:** `id`, `customer_id`, `education_level`, `school_name`, `major`, `graduation_year`, `gpa`, `language_certificate`, `language_score`, `certificate_date`, `notes`, `created_at`.
- **Khóa ngoại:** `customer_id -> kipa_customers(id) ON DELETE CASCADE`.

### 13. `kipa_service_dossiers` (Hồ Sơ Dịch Vụ / Hợp Đồng Du Học - XKLĐ)
- **Mục đích:** Quản lý quy trình xử lý dịch vụ theo từng hợp đồng của khách hàng (1 khách hàng có thể có nhiều hồ sơ).
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `dossier_code`: `VARCHAR(32) NOT NULL UNIQUE KEY` (`HS-2026-HQ-0089`)
  - `customer_id`: `BIGINT UNSIGNED NOT NULL`
  - `service_type`: `VARCHAR(64) NOT NULL` (`Du học Hàn Quốc`, `Du học Úc`, `Xuất khẩu lao động`, `Khóa tiếng Hàn`)
  - `program_id`: `BIGINT UNSIGNED NULL`
  - `target_country`: `VARCHAR(64) NOT NULL`
  - `target_intake`: `VARCHAR(32) NULL` (`Kỳ tháng 09/2027`)
  - `target_school_id`: `BIGINT UNSIGNED NULL`
  - `target_major`: `VARCHAR(160) NULL`
  - `stage`: `VARCHAR(64) NOT NULL DEFAULT 'Tư vấn'`
  - `progress_percent`: `TINYINT UNSIGNED NOT NULL DEFAULT 0`
  - `consultant_id`: `BIGINT UNSIGNED NULL` (Cố vấn tư vấn)
  - `case_officer_id`: `BIGINT UNSIGNED NULL` (Chuyên viên xử lý hồ sơ)
  - `collaborator_id`: `BIGINT UNSIGNED NULL` (Cộng tác viên giới thiệu)
  - `contract_number`: `VARCHAR(64) NULL`
  - `contract_signed_at`: `DATE NULL`
  - `status`: `VARCHAR(64) NOT NULL DEFAULT 'ACTIVE'` (`ACTIVE`, `PAUSED`, `CLOSED`, `ARCHIVED`)
  - `notes`: `TEXT NULL`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `updated_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Khóa ngoại:**
  - `customer_id -> kipa_customers(id) ON DELETE RESTRICT` (Ngăn chặn xóa khách hàng đang có hợp đồng hoạt động)
  - `consultant_id -> kipa_staff(id) ON DELETE SET NULL`
  - `case_officer_id -> kipa_staff(id) ON DELETE SET NULL`
  - `collaborator_id -> kipa_staff(id) ON DELETE SET NULL`

### 14. `kipa_dossier_assignments` (Lịch Sử Bàn Giao Phụ Trách Hồ Sơ)
- **Mục đích:** Ghi nhận lịch sử phân công và chuyển giao chuyên viên (Tư vấn, Xử lý hồ sơ, CTV) khi có sự thay đổi nhân sự.
- **Cấu trúc trường:** `id`, `dossier_id`, `staff_id`, `assignment_role` (`CONSULTANT`, `CASE_OFFICER`, `COLLABORATOR`), `assigned_at`, `unassigned_at`, `is_current`, `note`.
- **Khóa ngoại:** `dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE`, `staff_id -> kipa_staff(id) ON DELETE RESTRICT`.

### 15. `kipa_dossier_status_history` (Lịch Sử Vòng Đời Hồ Sơ)
- **Mục đích:** Quản lý vòng đời hồ sơ (Tạm dừng, Mở lại, Đóng và Lưu trữ) kèm lý do chi tiết và nhân sự quyết định.
- **Cấu trúc trường:** `id`, `dossier_id`, `from_status`, `to_status`, `change_type` (`PAUSE`, `RESUME`, `CLOSE`, `ARCHIVE`), `reason`, `changed_by`, `changed_at`.
- **Khóa ngoại:** `dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE`, `changed_by -> kipa_staff(id) ON DELETE RESTRICT`.

### 16. `kipa_tasks` (Nhiệm Vụ Công Việc, SLA & Nhắc Hạn)
- **Mục đích:** Giao việc cho chuyên viên (dịch thuật bằng cấp, nhắc đóng tiền cọc, xin visa), quản lý cam kết phản hồi SLA và cơ chế nhắc hẹn.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `dossier_id`: `BIGINT UNSIGNED NULL`
  - `customer_id`: `BIGINT UNSIGNED NOT NULL`
  - `title`: `VARCHAR(255) NOT NULL`
  - `description`: `TEXT NULL`
  - `assigned_to`: `BIGINT UNSIGNED NOT NULL`
  - `created_by`: `BIGINT UNSIGNED NULL`
  - `priority`: `VARCHAR(16) NOT NULL DEFAULT 'MEDIUM'` (`LOW`, `MEDIUM`, `HIGH`, `URGENT`)
  - `sla_hours`: `INT UNSIGNED NULL` (Số giờ cam kết hoàn thành: 24h, 48h...)
  - `due_date`: `DATETIME NULL`
  - `reminder_at`: `DATETIME NULL` (Thời điểm gửi cảnh báo nhắc hạn)
  - `escalated_to`: `BIGINT UNSIGNED NULL` (Cán bộ nhận cảnh báo vượt cấp khi trễ hạn)
  - `status`: `VARCHAR(32) NOT NULL DEFAULT 'TODO'` (`TODO`, `IN_PROGRESS`, `DONE`, `CANCELLED`)
  - `completed_at`: `DATETIME NULL`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `updated_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Khóa ngoại:**
  - `dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE`
  - `customer_id -> kipa_customers(id) ON DELETE CASCADE`
  - `assigned_to -> kipa_staff(id) ON DELETE RESTRICT`
  - `escalated_to -> kipa_staff(id) ON DELETE SET NULL`

### 17. `kipa_approvals` (Quy Trình Phê Duyệt Nhiều Cấp)
- **Mục đích:** Quy trình phê duyệt hồ sơ nộp trường, ký hợp đồng, xuất cảnh visa theo nhiều cấp bậc.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `dossier_id`: `BIGINT UNSIGNED NOT NULL`
  - `approval_type`: `VARCHAR(64) NOT NULL` (`APPROVE_CONTRACT`, `APPROVE_SCHOOL_SUBMISSION`, `APPROVE_VISA_SUBMISSION`)
  - `approval_level`: `TINYINT UNSIGNED NOT NULL DEFAULT 1` (Cấp 1: Trưởng nhóm, Cấp 2: Giám đốc chi nhánh, Cấp 3: Ban Giám Đốc)
  - `requested_by`: `BIGINT UNSIGNED NOT NULL`
  - `approver_id`: `BIGINT UNSIGNED NULL`
  - `next_approver_id`: `BIGINT UNSIGNED NULL`
  - `status`: `VARCHAR(32) NOT NULL DEFAULT 'PENDING'` (`PENDING`, `APPROVED`, `REJECTED`)
  - `comments`: `TEXT NULL`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `reviewed_at`: `DATETIME NULL`
- **Khóa ngoại:**
  - `dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE`
  - `requested_by -> kipa_staff(id) ON DELETE RESTRICT`
  - `approver_id -> kipa_staff(id) ON DELETE SET NULL`
  - `next_approver_id -> kipa_staff(id) ON DELETE SET NULL`

### 18. `kipa_audit_logs` (Nhật Ký Kiểm Toán Hệ Thống)
- **Mục đích:** Ghi nhận mọi hành vi sửa xóa, đổi trạng thái, tải file trên CRM phục vụ an toàn dữ liệu và tuân thủ kiểm toán.
- **Cấu trúc trường:** `id`, `actor_type` (`STAFF`, `STUDENT`, `SYSTEM`), `actor_id`, `action` (`INSERT`, `UPDATE`, `DELETE`, `REVIEW`, `EXPORT`), `entity_type`, `entity_id`, `old_values` (JSON), `new_values` (JSON), `ip_address`, `user_agent`, `created_at`.

### 19. `kipa_dossier_documents` (Kho Tài Liệu Gốc - Master Document Vault)
- **Mục đích:** Danh mục giấy tờ cần hoàn thiện theo checklist chuẩn.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `dossier_id`: `BIGINT UNSIGNED NOT NULL`
  - `customer_id`: `BIGINT UNSIGNED NOT NULL`
  - `doc_group`: `VARCHAR(64) NOT NULL` (`Giấy tờ cá nhân`, `Hồ sơ học vấn`, `Chứng minh tài chính`, `Hồ sơ sức khỏe`, `Chứng chỉ ngoại ngữ`)
  - `doc_code`: `VARCHAR(64) NOT NULL` (`CCCD`, `HOC_BA`, `BANG_TOT_NGHIEP`, `SO_TIET_KIEM`, `KHAM_LAO`, `TOPIK`)
  - `doc_name`: `VARCHAR(160) NOT NULL`
  - `is_required`: `BOOLEAN NOT NULL DEFAULT 1`
  - `status`: `VARCHAR(64) NOT NULL DEFAULT 'Chưa tải lên'` (`Chưa tải lên`, `Đã tải lên`, `Đang kiểm tra`, `Cần bổ sung`, `Đã xác nhận`)
  - `current_version_id`: `BIGINT UNSIGNED NULL`
  - `notes`: `TEXT NULL`
  - `created_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `updated_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
- **Khóa ngoại:**
  - `dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE`
  - `customer_id -> kipa_customers(id) ON DELETE CASCADE`

### 20. `kipa_document_versions` (Phiên Bản File Tải Lên)
- **Mục đích:** Quản lý các file vật lý scan/PDF đã nộp, cho phép nộp lại nhiều lần không đè file cũ.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `document_id`: `BIGINT UNSIGNED NOT NULL`
  - `version_number`: `INT UNSIGNED NOT NULL DEFAULT 1`
  - `file_url`: `VARCHAR(255) NOT NULL`
  - `file_name`: `VARCHAR(160) NOT NULL`
  - `file_size`: `VARCHAR(32) NOT NULL`
  - `mime_type`: `VARCHAR(64) NOT NULL`
  - `uploaded_by_type`: `VARCHAR(32) NOT NULL` (`STUDENT`, `STAFF`)
  - `uploaded_by_id`: `BIGINT UNSIGNED NOT NULL`
  - `uploaded_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
  - `note`: `TEXT NULL`
- **Khóa ngoại:** `document_id -> kipa_dossier_documents(id) ON DELETE CASCADE`

### 21. `kipa_document_reviews` (Lịch Sử Thẩm Định Giấy Tờ)
- **Mục đích:** Lưu lịch sử duyệt tài liệu của chuyên viên, lý do từ chối/yêu cầu bổ sung gửi cho học viên.
- **Cấu trúc trường:**
  - `id`: `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
  - `version_id`: `BIGINT UNSIGNED NOT NULL`
  - `reviewer_id`: `BIGINT UNSIGNED NOT NULL`
  - `review_status`: `VARCHAR(32) NOT NULL` (`APPROVED`, `REJECTED`, `NEEDS_ADDITIONAL`)
  - `review_note`: `TEXT NULL`
  - `rejection_reason`: `TEXT NULL`
  - `reviewed_at`: `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`
- **Khóa ngoại:** `version_id -> kipa_document_versions(id) ON DELETE CASCADE`, `reviewer_id -> kipa_staff(id) ON DELETE RESTRICT`

---

## III. ĐẶC TẢ CHI TIẾT 7 BẢNG STUDENT PORTAL & 1 SQL VIEW

### 22. `kipa_students` (Profile Cache Học Viên)
- **Mục đích:** Cache hồ sơ học viên độc lập giúp Cổng học viên render tốc độ cao mà không phải JOIN phức tạp.
- **Cấu trúc trường:** `id`, `email` (UNIQUE), `customer_id`, `full_name`, `avatar_url`, `birthday`, `gender`, `phone`, `address`, `education_level`, `school_name`, `emergency_contact`, `student_status`, `current_program`, `program_code`, `country`, `registration_date`, `target_departure`, `dossier_status`, `progress_percent`, `advisor_name`, `advisor_role`, `advisor_phone`, `advisor_email`, `advisor_avatar`, `created_at`, `updated_at`.
- **Khóa ngoại:** `customer_id -> kipa_customers(id) ON DELETE SET NULL`.

### 23. `kipa_student_journey` (Bản Đồ Lộ Trình 5 Mốc Tiến Độ)
- **Mục đích:** Hiển thị 5 bước hành trình du học trên Cổng học viên (`Tư vấn & Định hướng`, `Đào tạo tiếng Hàn`, `Chuẩn bị & Xử lý hồ sơ`, `Nộp xét tuyển & Phỏng vấn`, `Xin visa & Xuất cảnh`).
- **Cấu trúc trường:** `id`, `dossier_id`, `student_email`, `step_number`, `title`, `status` (`DONE`, `IN_PROGRESS`, `PENDING`), `progress_percent`, `description`, `deadline`, `in_charge`, `tasks_json` (JSON việc cần làm), `updated_at`.
- **Khóa ngoại:** `dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE`.

### 24. `kipa_student_schedules` (Lịch Học & Lịch Hẹn Cá Nhân)
- **Mục đích:** Lịch học ngoại ngữ, lịch hẹn cố vấn, lịch phỏng vấn visa.
- **Cấu trúc trường:** `id`, `student_email`, `title`, `schedule_type` (`Lịch học`, `Lịch hẹn`, `Phỏng vấn`, `Hạn nộp`), `day_label`, `time_range`, `location`, `mentor`, `status`.

### 25. `kipa_student_notifications` (Trung Tâm Thông Báo)
- **Mục đích:** Thông báo học tập, hồ sơ và thông báo quan trọng.
- **Cấu trúc trường:** `id`, `student_email`, `title`, `content`, `category` (`Hồ sơ`, `Lớp học`, `Hệ thống`), `time_label`, `is_read`, `is_urgent`, `created_at`.

### 26. `kipa_student_materials` (Thư Viện Tài Liệu Học Tập PDF)
- **Mục đích:** Kho tài liệu tải về dạng file PDF (Giáo trình tiếng Hàn, cẩm nang xuất cảnh, đề thi TOPIK).
- **Cấu trúc trường:** `id`, `title`, `category`, `format`, `file_url`, `file_size`, `description`, `download_count`, `is_active`, `created_at`.

### 27. `kipa_student_support` (Kênh Yêu Cầu Hỗ Trợ 2 Chiều)
- **Mục đích:** Ticket giải đáp thắc mắc giữa học viên và chuyên viên KIPA.
- **Cấu trúc trường:** `id`, `student_email`, `ticket_code`, `subject`, `message`, `category`, `status`, `advisor_reply`, `assigned_staff_id`, `created_at`, `updated_at`.
- **Khóa ngoại:** `assigned_staff_id -> kipa_staff(id) ON DELETE SET NULL`.

### 28. `kipa_notification_settings` (Cài Đặt Nhận Thông Báo)
- **Mục đích:** Cấu hình bật/tắt nhận thông báo qua Email, SMS, Zalo, Portal của từng khách hàng.
- **Cấu trúc trường:** `id`, `customer_id` (UNIQUE), `email_notify`, `sms_notify`, `zalo_notify`, `portal_notify`, `updated_at`.
- **Khóa ngoại:** `customer_id -> kipa_customers(id) ON DELETE CASCADE`.

### 29. `VIEW kipa_student_documents` (Khung Nhìn Hợp Nhất Ví Hồ Sơ Số)
- **Mục đích:** Ánh xạ từ `kipa_dossier_documents` và `kipa_document_versions` sang định dạng hiển thị Cổng học viên.
- **Định nghĩa SQL View:**
  ```sql
  CREATE OR REPLACE VIEW `kipa_student_documents` AS
  SELECT 
      d.id AS id,
      c.email AS student_email,
      d.doc_group AS doc_group,
      d.doc_name AS doc_name,
      d.status AS status,
      COALESCE(v.file_url, '') AS file_url,
      COALESCE(v.file_name, '') AS file_name,
      COALESCE(v.file_size, '') AS file_size,
      COALESCE(DATE_FORMAT(v.uploaded_at, '%Y-%m-%d %H:%i'), '') AS uploaded_at,
      d.notes AS note,
      d.updated_at AS updated_at
  FROM `kipa_dossier_documents` d
  JOIN `kipa_customers` c ON d.customer_id = c.id
  LEFT JOIN `kipa_document_versions` v ON d.current_version_id = v.id;
  ```
- **Cam kết:** Đồng bộ 100% thời gian thực, không gây trùng lặp dữ liệu, giữ nguyên tương thích mã nguồn.
