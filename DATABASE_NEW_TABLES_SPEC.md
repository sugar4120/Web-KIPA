# KIPA VIỆT HÀN — ĐẶC TẢ CHI TIẾT CÁC BẢNG BỔ SUNG (DATABASE NEW TABLES SPEC)

> **Tài liệu đặc tả kỹ thuật DDL:** Thiết kế chi tiết 42 bảng vật lý và 1 SQL View cho KIPA CRM và KIPA Student Portal  
> **Phiên bản:** 2.2.0 (Hoàn thiện nghiệp vụ Tiếp nhận & Tư vấn, Phân quyền, SLA, Phê duyệt kép, Bảo vệ Kho tài liệu & Bảo mật Cổng học viên)  
> **Quy chuẩn kỹ thuật:** MySQL 8.0+ / MariaDB 10.5+, Engine `InnoDB`, Charset `utf8mb4`, Collation `utf8mb4_unicode_ci`  
> **Khóa chính:** `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`  

---

## I. MỤC TIÊU VÀ NGUYÊN TẮC THIẾT KẾ DDL

1. **Một Database duy nhất:** Toàn bộ bảng CRM và Student Portal được triển khai chung trên database hiện tại của Website KIPA.
2. **Không tạo bảng trùng lặp:** Hợp nhất kho tài liệu qua `VIEW kipa_student_documents`, tái sử dụng `kipa_users` làm danh tính Auth.
3. **Bảo toàn dữ liệu cũ 100%:** Tuyệt đối không can thiệp cấu trúc, không đổi tên bảng/cột và không xóa dữ liệu đang hoạt động.
4. **Bảo vệ chống xóa dây chuyền (Cascade Protection):** Mọi khóa ngoại liên kết sang `kipa_leads` và `kipa_users` đều cấu hình `ON DELETE SET NULL`.
5. **Bảo vệ kho tài liệu lịch sử:** Ràng buộc `kipa_dossier_documents.dossier_id` dùng `ON DELETE RESTRICT` để không bao giờ bị xóa mất dấu khi đóng hồ sơ.
6. **Bảo mật Cổng học viên (Zero-Trust):** Thêm `user_id` và `customer_id` vào View để API server lọc strictly theo Session `user_id`, triệt tiêu lỗ hổng IDOR.
7. **Đầy đủ Index tối ưu hóa:** Mọi trường tìm kiếm (email, phone, code), trường trạng thái và khóa ngoại đều có chỉ mục B-Tree riêng.

---

## II. ĐẶC TẢ CHI TIẾT 10 BẢNG TỔ CHỨC & PHÂN QUYỀN RBAC NÂNG CAO

### 1. `kipa_branches` (Chi Nhánh & Văn Phòng Đại Diện)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `branch_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`HP_HQ`, `HN_01`, `HCM_01`, `SEOUL_01`, `SYDNEY_01`)
- `branch_name`: VARCHAR(160) NOT NULL
- `phone`: VARCHAR(32) NOT NULL, `email`: VARCHAR(254) NULL, `address`: VARCHAR(255) NOT NULL, `province`: VARCHAR(100) NOT NULL, `country`: VARCHAR(64) DEFAULT 'Việt Nam'
- `manager_name`: VARCHAR(160) NULL, `is_active`: BOOLEAN DEFAULT 1, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 2. `kipa_departments` (Phòng Ban Nội Bộ)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `branch_id`: BIGINT UNSIGNED NULL FK -> `kipa_branches(id)` ON DELETE SET NULL
- `dept_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`TS_TUYENSINH`, `HS_VISA`, `DT_TIENGHAN`, `DN_QUOCTE`, `KT_TAICHINH`)
- `dept_name`: VARCHAR(128) NOT NULL, `description`: TEXT NULL, `is_active`: BOOLEAN DEFAULT 1, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 3. `kipa_teams` (Nhóm Chuyên Môn Trực Thuộc Phòng Ban)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `dept_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_departments(id)` ON DELETE CASCADE
- `team_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`TEAM_HQ_01`, `TEAM_UC_01`, `TEAM_VISA`)
- `team_name`: VARCHAR(128) NOT NULL, `leader_id`: BIGINT UNSIGNED NULL, `description`: VARCHAR(255) NULL, `is_active`: BOOLEAN DEFAULT 1, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 4. `kipa_staff` (Hồ Sơ Cán Bộ Nhân Viên & Quản Lý Trực Tiếp)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `staff_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`NV-2026-001`)
- `admin_id`: BIGINT UNSIGNED NULL UNIQUE KEY FK -> `kipa_admins(id)` ON DELETE SET NULL
- `direct_manager_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL (Quản lý trực tiếp chính)
- `full_name`: VARCHAR(160) NOT NULL, `email`: VARCHAR(254) NOT NULL, `phone`: VARCHAR(32) NOT NULL
- `branch_id`: BIGINT UNSIGNED NULL FK -> `kipa_branches(id)` ON DELETE SET NULL
- `dept_id`: BIGINT UNSIGNED NULL FK -> `kipa_departments(id)` ON DELETE SET NULL
- `team_id`: BIGINT UNSIGNED NULL FK -> `kipa_teams(id)` ON DELETE SET NULL
- `position`: VARCHAR(128) NOT NULL, `avatar_url`: VARCHAR(255) NULL, `is_active`: BOOLEAN DEFAULT 1, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 5. `kipa_roles` (Danh Mục Vai Trò CRM)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `role_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`ADMIN`, `DIRECTOR`, `BRANCH_MANAGER`, `CONSULTANT`, `CASE_OFFICER`, `TEACHER`, `COLLABORATOR`)
- `role_name`: VARCHAR(128) NOT NULL, `description`: VARCHAR(255) NULL, `is_system`: BOOLEAN DEFAULT 0, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 6. `kipa_permissions` (Danh Mục Quyền Hạn Chi Tiết)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `permission_code`: VARCHAR(64) NOT NULL UNIQUE KEY (`view_leads`, `assign_dossier`, `review_docs`, `approve_visa`, `view_sensitive_financials`)
- `permission_name`: VARCHAR(128) NOT NULL, `module_name`: VARCHAR(64) NOT NULL, `description`: VARCHAR(255) NULL, `is_sensitive`: BOOLEAN DEFAULT 0

### 7. `kipa_role_permissions` (Ma Trận Vai Trò - Quyền Hạn Cụ Thể)
- `role_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_roles(id)` ON DELETE CASCADE
- `permission_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_permissions(id)` ON DELETE CASCADE
- `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP
- PRIMARY KEY (`role_id`, `permission_id`)

### 8. `kipa_staff_roles` (Phân Quyền Nhân Sự & Phạm Vi)
- `staff_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE CASCADE
- `role_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_roles(id)` ON DELETE CASCADE
- `scope`: VARCHAR(32) NOT NULL DEFAULT 'ASSIGNED_ONLY' (`GLOBAL`, `BRANCH`, `DEPARTMENT`, `TEAM`, `ASSIGNED_ONLY`)
- `granted_by`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL
- `assigned_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `expires_at`: DATETIME NULL (Thời hạn tạm thời)
- PRIMARY KEY (`staff_id`, `role_id`)

### 9. `kipa_staff_role_history` (Lịch Sử Thu Hồi & Luân Chuyển Quyền)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `staff_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE CASCADE
- `role_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_roles(id)` ON DELETE CASCADE
- `action`: VARCHAR(32) NOT NULL (`GRANTED`, `MODIFIED`, `REVOKED`, `EXPIRED`)
- `scope`: VARCHAR(32) NOT NULL, `reason`: VARCHAR(255) NULL, `performed_by`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL, `occurred_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 10. `kipa_role_delegations` (Ủy Quyền Vai Trò Tạm Thời)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `delegator_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE CASCADE (Người ủy quyền)
- `delegatee_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE CASCADE (Người được ủy quyền)
- `role_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_roles(id)` ON DELETE CASCADE
- `delegated_scope`: VARCHAR(32) NOT NULL DEFAULT 'BRANCH', `start_date`: DATE NOT NULL, `end_date`: DATE NOT NULL, `reason`: VARCHAR(255) NULL
- `approved_by`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL, `status`: VARCHAR(32) DEFAULT 'ACTIVE', `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

---

## III. ĐẶC TẢ CHI TIẾT 4 BẢNG KHÁCH HÀNG TRUNG TÂM CRM

### 11. `kipa_customers` (Hồ Sơ Khách Hàng Gốc)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `customer_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`KH-2026-0001`)
- `full_name`: VARCHAR(160) NOT NULL, `phone`: VARCHAR(32) NOT NULL, `email`: VARCHAR(254) NULL, `birthday`: DATE NULL, `gender`: VARCHAR(16) NULL
- `id_card_number`: VARCHAR(32) NULL, `id_card_date`: DATE NULL, `id_card_place`: VARCHAR(128) NULL
- `user_id`: BIGINT UNSIGNED NULL FK -> `kipa_users(id)` ON DELETE SET NULL (Liên kết Auth Cổng học viên)
- `legacy_lead_id`: BIGINT UNSIGNED NULL FK -> `kipa_leads(id)` ON DELETE SET NULL (Bảo vệ khách hàng khi xóa lead sau 12 tháng)
- `lead_source_id`: BIGINT UNSIGNED NULL, `assigned_staff_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL
- `branch_id`: BIGINT UNSIGNED NULL FK -> `kipa_branches(id)` ON DELETE SET NULL, `customer_status`: VARCHAR(64) DEFAULT 'Mới tiếp nhận', `notes`: TEXT NULL
- `created_by`: BIGINT UNSIGNED NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 12. `kipa_customer_addresses` (Sổ Địa Chỉ)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `customer_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_customers(id)` ON DELETE CASCADE
- `address_type`: VARCHAR(32) DEFAULT 'Thường trú', `street_address`: VARCHAR(255) NOT NULL, `ward`: VARCHAR(100) NULL, `district`: VARCHAR(100) NULL, `province`: VARCHAR(100) NOT NULL, `country`: VARCHAR(64) DEFAULT 'Việt Nam', `is_default`: BOOLEAN DEFAULT 0, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 13. `kipa_customer_relations` (Quan Hệ Nhân Thân & Bảo Lãnh Tài Chính)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `customer_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_customers(id)` ON DELETE CASCADE
- `relation_type`: VARCHAR(32) NOT NULL (`Bố`, `Mẹ`, `Vợ/Chồng`, `Người bảo lãnh`), `full_name`: VARCHAR(160) NOT NULL, `phone`: VARCHAR(32) NOT NULL, `email`: VARCHAR(254) NULL, `occupation`: VARCHAR(128) NULL, `birth_year`: SMALLINT UNSIGNED NULL, `id_card_number`: VARCHAR(32) NULL, `address`: VARCHAR(255) NULL, `monthly_income`: DECIMAL(15,2) NULL, `is_emergency`: BOOLEAN DEFAULT 0, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 14. `kipa_customer_education` (Học Vấn, GPA & Chứng Chỉ Ngoại Ngữ)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `customer_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_customers(id)` ON DELETE CASCADE
- `education_level`: VARCHAR(64) NOT NULL, `school_name`: VARCHAR(160) NOT NULL, `major`: VARCHAR(128) NULL, `graduation_year`: SMALLINT UNSIGNED NOT NULL, `gpa`: DECIMAL(3,2) NULL, `language_certificate`: VARCHAR(64) NULL, `language_score`: VARCHAR(32) NULL, `certificate_date`: DATE NULL, `notes`: TEXT NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

---

## IV. ĐẶC TẢ CHI TIẾT 5 BẢNG TIẾP NHẬN LEAD & HỒ SƠ TƯ VẤN RIÊNG

### 15. `kipa_lead_sources` (Danh Mục Nguồn Tiếp Nhận Lead)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `source_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`SRC_WEB_FORM`, `SRC_FB_ADS`, `SRC_ZALO_OA`, `SRC_HOTLINE`, `SRC_REFERRAL`, `SRC_SCHOOL_EVENT`)
- `source_name`: VARCHAR(128) NOT NULL, `channel_type`: VARCHAR(64) DEFAULT 'DIGITAL', `is_active`: BOOLEAN DEFAULT 1, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 16. `kipa_campaigns` (Chiến Dịch Tiếp Thị & Tuyển Sinh)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `campaign_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`CAMP_AUTUMN_2026`, `CAMP_TOPIK_TRIAL`)
- `campaign_name`: VARCHAR(160) NOT NULL, `start_date`: DATE NOT NULL, `end_date`: DATE NULL, `budget`: DECIMAL(15,2) NULL, `channel`: VARCHAR(64) NULL, `status`: VARCHAR(32) DEFAULT 'ACTIVE', `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 17. `kipa_consultation_dossiers` (Hồ Sơ Tư Vấn Riêng Biệt - Tách Độc Lập)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `consultation_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`TV-2026-0001`)
- `customer_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_customers(id)` ON DELETE CASCADE
- `legacy_lead_id`: BIGINT UNSIGNED NULL FK -> `kipa_leads(id)` ON DELETE SET NULL
- `lead_source_id`: BIGINT UNSIGNED NULL FK -> `kipa_lead_sources(id)` ON DELETE SET NULL
- `campaign_id`: BIGINT UNSIGNED NULL FK -> `kipa_campaigns(id)` ON DELETE SET NULL
- `assigned_staff_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL
- `stage`: VARCHAR(32) NOT NULL DEFAULT 'NEW' (`NEW`, `CONTACTED`, `CONSULTING`, `TRIAL_CLASS`, `PROPOSAL_SENT`, `WON`, `LOST`)
- `primary_interest`: VARCHAR(64) NOT NULL (`Du học Hàn Quốc`, `Du học Úc`, `XKLĐ`, `Tiếng Hàn`)
- `target_intake`: VARCHAR(32) NULL, `estimated_budget`: DECIMAL(15,2) NULL, `last_contacted_at`: DATETIME NULL, `next_appointment_at`: DATETIME NULL
- `lost_reason`: VARCHAR(128) NULL (`Chi phí cao`, `Gia đình phản đối`, `Học bạ GPA thấp`, `Chọn trung tâm khác`, `Không liên lạc được`), `lost_notes`: TEXT NULL
- `handover_status`: VARCHAR(32) NOT NULL DEFAULT 'NONE' (`NONE`, `PENDING_HANDOVER`, `HANDED_OVER`, `REJECTED_HANDOVER`)
- `handover_notes`: TEXT NULL, `handed_over_by`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL, `handed_over_at`: DATETIME NULL
- `accepted_by`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL, `accepted_at`: DATETIME NULL
- `created_service_dossier_id`: BIGINT UNSIGNED NULL (Liên kết sang hồ sơ dịch vụ sau khi chốt hợp đồng)
- `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 18. `kipa_consultation_interests` (Chi Tiết Nhu Cầu Dịch Vụ Của Đợt Tư Vấn)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `consultation_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_consultation_dossiers(id)` ON DELETE CASCADE
- `service_type`: VARCHAR(64) NOT NULL, `target_country`: VARCHAR(64) NOT NULL, `target_major`: VARCHAR(128) NULL, `preferred_school`: VARCHAR(160) NULL, `notes`: TEXT NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 19. `kipa_consultation_activities` (Lịch Sử Hoạt Động Tư Vấn & Hẹn Lịch)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `consultation_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_consultation_dossiers(id)` ON DELETE CASCADE
- `staff_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE RESTRICT
- `activity_type`: VARCHAR(32) NOT NULL (`CALL`, `MEETING`, `ZALO`, `EMAIL`, `NOTE`), `activity_time`: DATETIME NOT NULL, `outcome`: VARCHAR(64) NOT NULL, `content`: TEXT NOT NULL, `scheduled_next_at`: DATETIME NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

---

## V. ĐẶC TẢ CHI TIẾT 3 BẢNG HỒ SƠ DỊCH VỤ & VÒNG ĐỜI (SERVICE DOSSIERS)

### 20. `kipa_service_dossiers` (Hồ Sơ Hợp Đồng Dịch Vụ Chính Thức)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `dossier_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`HS-2026-HQ-0089`)
- `customer_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_customers(id)` ON DELETE RESTRICT (Ngăn chặn xóa khách hàng đang có hợp đồng)
- `consultation_dossier_id`: BIGINT UNSIGNED NULL FK -> `kipa_consultation_dossiers(id)` ON DELETE SET NULL
- `service_type`: VARCHAR(64) NOT NULL, `program_id`: BIGINT UNSIGNED NULL, `target_country`: VARCHAR(64) NOT NULL, `target_intake`: VARCHAR(32) NULL, `target_school_id`: BIGINT UNSIGNED NULL, `target_major`: VARCHAR(160) NULL
- `stage`: VARCHAR(64) NOT NULL DEFAULT 'Tư vấn', `progress_percent`: TINYINT UNSIGNED NOT NULL DEFAULT 0
- `consultant_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL (Cố vấn tư vấn)
- `case_officer_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL (Chuyên viên xử lý hồ sơ)
- `collaborator_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL (Cộng tác viên giới thiệu)
- `contract_number`: VARCHAR(64) NULL, `contract_signed_at`: DATE NULL, `status`: VARCHAR(64) NOT NULL DEFAULT 'ACTIVE' (`ACTIVE`, `PAUSED`, `CLOSED`, `ARCHIVED`)
- `notes`: TEXT NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 21. `kipa_dossier_assignments` (Lịch Sử Phân Công & Bàn Giao Hồ Sơ)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `dossier_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_service_dossiers(id)` ON DELETE CASCADE
- `staff_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE RESTRICT
- `assignment_role`: VARCHAR(32) NOT NULL DEFAULT 'CASE_OFFICER' (`CONSULTANT`, `CASE_OFFICER`, `COLLABORATOR`)
- `assigned_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `unassigned_at`: DATETIME NULL, `is_current`: BOOLEAN DEFAULT 1, `note`: VARCHAR(255) NULL

### 22. `kipa_dossier_status_history` (Vòng Đời Hồ Sơ: Tạm Dừng, Mở Lại, Đóng, Lưu Trữ)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `dossier_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_service_dossiers(id)` ON DELETE CASCADE
- `from_status`: VARCHAR(64) NOT NULL, `to_status`: VARCHAR(64) NOT NULL
- `change_type`: VARCHAR(32) NOT NULL (`PAUSE`, `RESUME`, `CLOSE`, `ARCHIVE`), `reason`: TEXT NOT NULL
- `changed_by`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE RESTRICT, `changed_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

---

## VI. ĐẶC TẢ CHI TIẾT 5 BẢNG NHIỆM VỤ, QUẢN LÝ SLA & LỊCH LÀM VIỆC

### 23. `kipa_sla_policies` (Chính Sách Cam Kết SLA)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `policy_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`SLA_LEAD_HP`, `SLA_VISA_HQ`)
- `policy_name`: VARCHAR(128) NOT NULL, `service_type`: VARCHAR(64) NULL
- `branch_id`: BIGINT UNSIGNED NULL FK -> `kipa_branches(id)` ON DELETE SET NULL
- `priority`: VARCHAR(16) NOT NULL DEFAULT 'MEDIUM', `response_time_hours`: INT UNSIGNED DEFAULT 8, `resolution_time_hours`: INT UNSIGNED DEFAULT 48, `is_active`: BOOLEAN DEFAULT 1, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 24. `kipa_working_calendars` (Lịch Làm Việc & Ngày Nghỉ Lễ Tính SLA)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `branch_id`: BIGINT UNSIGNED NULL FK -> `kipa_branches(id)` ON DELETE CASCADE
- `calendar_date`: DATE NOT NULL, `is_working_day`: BOOLEAN DEFAULT 1, `start_time`: TIME DEFAULT '08:00:00', `end_time`: TIME DEFAULT '17:30:00', `lunch_start`: TIME DEFAULT '12:00:00', `lunch_end`: TIME DEFAULT '13:30:00', `holiday_name`: VARCHAR(128) NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP
- UNIQUE KEY (`branch_id`, `calendar_date`)

### 25. `kipa_tasks` (Nhiệm Vụ Công Việc, SLA Tạm Dừng/Tiếp Tục & Xác Nhận)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `parent_id`: BIGINT UNSIGNED NULL FK -> `kipa_tasks(id)` ON DELETE CASCADE (Hỗ trợ nhiệm vụ con)
- `dossier_id`: BIGINT UNSIGNED NULL FK -> `kipa_service_dossiers(id)` ON DELETE CASCADE
- `customer_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_customers(id)` ON DELETE CASCADE
- `title`: VARCHAR(255) NOT NULL, `description`: TEXT NULL
- `assigned_to`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE RESTRICT (Người làm chính)
- `created_by`: BIGINT UNSIGNED NULL (Người giao việc), `priority`: VARCHAR(16) DEFAULT 'MEDIUM'
- `sla_policy_id`: BIGINT UNSIGNED NULL FK -> `kipa_sla_policies(id)` ON DELETE SET NULL
- `sla_status`: VARCHAR(32) NOT NULL DEFAULT 'RUNNING' (`RUNNING`, `PAUSED`, `BREACHED`, `COMPLETED_ON_TIME`)
- `sla_paused_at`: DATETIME NULL, `sla_pause_reason`: VARCHAR(255) NULL, `sla_total_paused_minutes`: INT UNSIGNED DEFAULT 0
- `due_date`: DATETIME NULL, `reminder_at`: DATETIME NULL, `escalated_to`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL, `escalation_level`: TINYINT UNSIGNED DEFAULT 1
- `is_recurring`: BOOLEAN DEFAULT 0, `recurrence_pattern`: VARCHAR(32) NULL, `recurrence_end_date`: DATE NULL
- `status`: VARCHAR(32) DEFAULT 'TODO' (`TODO`, `IN_PROGRESS`, `DONE`, `CANCELLED`), `completed_at`: DATETIME NULL
- `completion_confirmed_by`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL (Người nghiệm thu)
- `completion_confirmed_at`: DATETIME NULL, `completion_rating`: TINYINT UNSIGNED NULL (1-5 sao)
- `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 26. `kipa_task_collaborators` (Người Phối Hợp Trong Nhiệm Vụ)
- `task_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_tasks(id)` ON DELETE CASCADE
- `staff_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE CASCADE
- `role_description`: VARCHAR(128) NULL, `assigned_at`: DATETIME DEFAULT CURRENT_TIMESTAMP
- PRIMARY KEY (`task_id`, `staff_id`)

### 27. `kipa_task_history` (Lịch Sử Thay Đổi Nhiệm Vụ & Gia Hạn Deadline)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `task_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_tasks(id)` ON DELETE CASCADE
- `changed_by`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE RESTRICT
- `field_name`: VARCHAR(64) NOT NULL, `old_value`: TEXT NULL, `new_value`: TEXT NULL, `reason`: VARCHAR(255) NULL, `changed_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

---

## VII. ĐẶC TẢ CHI TIẾT 4 BẢNG QUY TRÌNH PHÊ DUYỆT & PHÊ DUYỆT KÉP

### 28. `kipa_approval_workflows` (Định Nghĩa Quy Trình Duyệt Nhiều Bước)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `workflow_code`: VARCHAR(32) NOT NULL UNIQUE KEY (`WF_CONTRACT_SIGN`, `WF_VISA_SUBMISSION`, `WF_FEE_REFUND`)
- `workflow_name`: VARCHAR(128) NOT NULL, `approval_type`: VARCHAR(64) NOT NULL, `version`: INT UNSIGNED DEFAULT 1
- `requires_dual_approval`: BOOLEAN DEFAULT 0 (Cờ quy định phê duyệt kép), `description`: VARCHAR(255) NULL, `is_active`: BOOLEAN DEFAULT 1, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 29. `kipa_approval_workflow_steps` (Chi Tiết Các Bước Duyệt)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `workflow_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_approval_workflows(id)` ON DELETE CASCADE
- `step_order`: INT UNSIGNED NOT NULL DEFAULT 1, `step_name`: VARCHAR(128) NOT NULL
- `required_role_id`: BIGINT UNSIGNED NULL FK -> `kipa_roles(id)` ON DELETE SET NULL, `specific_approver_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL
- `sla_hours`: INT UNSIGNED DEFAULT 24, `is_final_step`: BOOLEAN DEFAULT 0, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP
- UNIQUE KEY (`workflow_id`, `step_order`)

### 30. `kipa_approvals` (Yêu Cầu Duyệt Thực Tế, Snapshot & Chống Tự Duyệt)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `dossier_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_service_dossiers(id)` ON DELETE CASCADE
- `workflow_id`: BIGINT UNSIGNED NULL FK -> `kipa_approval_workflows(id)` ON DELETE SET NULL
- `step_id`: BIGINT UNSIGNED NULL FK -> `kipa_approval_workflow_steps(id)` ON DELETE SET NULL
- `approval_type`: VARCHAR(64) NOT NULL, `approval_level`: TINYINT UNSIGNED DEFAULT 1
- `snapshot_payload`: JSON NOT NULL (Lưu toàn bộ snapshot dữ liệu tại thời điểm gửi duyệt để chống sửa lén)
- `requested_by`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE RESTRICT
- `approver_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL (Người duyệt 1)
- `next_approver_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL
- `second_approver_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL (Người duyệt 2 trong phê duyệt kép)
- `status`: VARCHAR(32) NOT NULL DEFAULT 'PENDING' (`PENDING`, `APPROVED`, `REJECTED`, `RETURNED_FOR_ADDITIONAL`, `CANCELLED`, `RESUBMITTED`)
- `comments`: TEXT NULL, `second_comments`: TEXT NULL, `reviewed_at`: DATETIME NULL, `second_approved_at`: DATETIME NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP
- `CONSTRAINT chk_kipa_app_no_self CHECK (requested_by != approver_id)` (Nguyên tắc chống tự duyệt)

### 31. `kipa_audit_logs` (Nhật Ký Kiểm Toán Hệ Thống)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `actor_type`: VARCHAR(32) DEFAULT 'STAFF', `actor_id`: BIGINT UNSIGNED NOT NULL, `action`: VARCHAR(64) NOT NULL, `entity_type`: VARCHAR(64) NOT NULL, `entity_id`: BIGINT UNSIGNED NOT NULL, `old_values`: JSON NULL, `new_values`: JSON NULL, `ip_address`: VARCHAR(45) NULL, `user_agent`: VARCHAR(255) NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

---

## VIII. ĐẶC TẢ CHI TIẾT 4 BẢNG KHO TÀI LIỆU GỐC & BẢO MẬT TRUY CẬP

### 32. `kipa_dossier_documents` (Kho Checklist Tài Liệu Gốc - Master Vault)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `dossier_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_service_dossiers(id)` ON DELETE RESTRICT (Bảo vệ tài liệu lịch sử khi đóng hồ sơ)
- `customer_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_customers(id)` ON DELETE CASCADE
- `doc_group`: VARCHAR(64) NOT NULL, `doc_code`: VARCHAR(64) NOT NULL, `doc_name`: VARCHAR(160) NOT NULL
- `is_required`: BOOLEAN DEFAULT 1, `status`: VARCHAR(64) DEFAULT 'Chưa tải lên' (`Chưa tải lên`, `Đã tải lên`, `Đang kiểm tra`, `Cần bổ sung`, `Đã xác nhận`)
- `access_level`: VARCHAR(32) DEFAULT 'INTERNAL' (`PUBLIC`, `INTERNAL`, `CONFIDENTIAL`, `RESTRICTED`)
- `is_archived`: BOOLEAN DEFAULT 0, `current_version_id`: BIGINT UNSIGNED NULL, `notes`: TEXT NULL
- `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 33. `kipa_document_versions` (Phiên Bản File Tải Lên - Protected Storage)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `document_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_dossier_documents(id)` ON DELETE CASCADE
- `version_number`: INT UNSIGNED NOT NULL DEFAULT 1
- `file_url`: VARCHAR(255) NOT NULL (Đường dẫn lưu tại `kipa-private/storage/documents/`, không dùng public URL)
- `file_name`: VARCHAR(160) NOT NULL, `file_size`: VARCHAR(32) NOT NULL, `mime_type`: VARCHAR(64) NOT NULL
- `uploaded_by_type`: VARCHAR(32) DEFAULT 'STUDENT', `uploaded_by_id`: BIGINT UNSIGNED NOT NULL, `uploaded_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `note`: TEXT NULL

### 34. `kipa_document_reviews` (Lịch Sử Thẩm Định Giấy Tờ)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `version_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_document_versions(id)` ON DELETE CASCADE
- `reviewer_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_staff(id)` ON DELETE RESTRICT
- `review_status`: VARCHAR(32) NOT NULL (`APPROVED`, `REJECTED`, `NEEDS_ADDITIONAL`), `review_note`: TEXT NULL, `rejection_reason`: TEXT NULL, `reviewed_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 35. `kipa_document_access_logs` (Nhật Ký Truy Cập Tài Liệu Nhạy Cảm)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `version_id`: BIGINT UNSIGNED NOT NULL FK -> `kipa_document_versions(id)` ON DELETE CASCADE
- `actor_type`: VARCHAR(32) DEFAULT 'STAFF', `actor_id`: BIGINT UNSIGNED NOT NULL, `access_type`: VARCHAR(32) DEFAULT 'VIEW' (`VIEW`, `DOWNLOAD`, `SHARE`), `ip_address`: VARCHAR(45) NULL, `user_agent`: VARCHAR(255) NULL, `accessed_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

---

## IX. ĐẶC TẢ CHI TIẾT 7 BẢNG STUDENT PORTAL & 1 SQL VIEW

### 36. `kipa_students` (Profile Cache Học Viên)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `email`: VARCHAR(254) NOT NULL UNIQUE KEY, `customer_id`: BIGINT UNSIGNED NULL FK -> `kipa_customers(id)` ON DELETE SET NULL
- `full_name`: VARCHAR(160) NOT NULL, `avatar_url`: VARCHAR(255) NULL, `birthday`: VARCHAR(32) DEFAULT '', `gender`: VARCHAR(16) DEFAULT 'Khác', `phone`: VARCHAR(32) DEFAULT '', `address`: VARCHAR(255) DEFAULT '', `education_level`: VARCHAR(64) DEFAULT '', `school_name`: VARCHAR(160) DEFAULT '', `emergency_contact`: VARCHAR(255) DEFAULT '', `student_status`: VARCHAR(64) DEFAULT 'Đang học', `current_program`: VARCHAR(160) DEFAULT '', `program_code`: VARCHAR(32) DEFAULT '', `country`: VARCHAR(64) DEFAULT '', `registration_date`: VARCHAR(32) DEFAULT '', `target_departure`: VARCHAR(32) DEFAULT '', `dossier_status`: VARCHAR(64) DEFAULT 'Đang chuẩn bị', `progress_percent`: INT DEFAULT 0, `advisor_name`: VARCHAR(160) DEFAULT '', `advisor_role`: VARCHAR(128) DEFAULT '', `advisor_phone`: VARCHAR(32) DEFAULT '', `advisor_email`: VARCHAR(254) DEFAULT '', `advisor_avatar`: VARCHAR(255) DEFAULT '', `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 37. `kipa_student_journey` (Bản Đồ Lộ Trình 5 Mốc Tiến Độ)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `dossier_id`: BIGINT UNSIGNED NULL FK -> `kipa_service_dossiers(id)` ON DELETE CASCADE
- `student_email`: VARCHAR(254) NOT NULL, `step_number`: INT UNSIGNED NOT NULL, `title`: VARCHAR(160) NOT NULL, `status`: VARCHAR(32) DEFAULT 'PENDING', `progress_percent`: INT UNSIGNED DEFAULT 0, `description`: TEXT NULL, `deadline`: VARCHAR(64) DEFAULT '', `in_charge`: VARCHAR(160) DEFAULT '', `tasks_json`: JSON NULL, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 38. `kipa_student_schedules` (Lịch Học & Lịch Hẹn)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `student_email`: VARCHAR(254) NOT NULL, `title`: VARCHAR(160) NOT NULL, `schedule_type`: VARCHAR(64) NOT NULL, `day_label`: VARCHAR(32) NOT NULL, `time_range`: VARCHAR(64) NOT NULL, `location`: VARCHAR(160) NOT NULL, `mentor`: VARCHAR(160) NOT NULL, `status`: VARCHAR(64) DEFAULT 'Sắp tới'

### 39. `kipa_student_notifications` (Trung Tâm Thông Báo)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `student_email`: VARCHAR(254) NOT NULL, `title`: VARCHAR(160) NOT NULL, `content`: TEXT NOT NULL, `category`: VARCHAR(64) NOT NULL, `time_label`: VARCHAR(32) NOT NULL, `is_read`: BOOLEAN DEFAULT 0, `is_urgent`: BOOLEAN DEFAULT 0, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP

### 40. `kipa_student_materials` (Thư Viện Giáo Trình PDF)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `title`: VARCHAR(255) NOT NULL, `category`: VARCHAR(64) NOT NULL, `format`: VARCHAR(16) DEFAULT 'PDF', `file_url`: VARCHAR(255) NOT NULL, `file_size`: VARCHAR(32) NOT NULL, `description`: TEXT NULL

### 41. `kipa_student_support` (Kênh Yêu Cầu Hỗ Trợ 2 Chiều)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `student_email`: VARCHAR(254) NOT NULL, `ticket_code`: VARCHAR(32) NOT NULL, `subject`: VARCHAR(255) NOT NULL, `message`: TEXT NOT NULL, `category`: VARCHAR(64) NOT NULL, `status`: VARCHAR(64) DEFAULT 'Đang xử lý', `advisor_reply`: TEXT NULL, `assigned_staff_id`: BIGINT UNSIGNED NULL FK -> `kipa_staff(id)` ON DELETE SET NULL, `created_at`: DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 42. `kipa_notification_settings` (Cài Đặt Thông Báo)
- `id`: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
- `customer_id`: BIGINT UNSIGNED NOT NULL UNIQUE KEY FK -> `kipa_customers(id)` ON DELETE CASCADE
- `email_notify`: BOOLEAN DEFAULT 1, `sms_notify`: BOOLEAN DEFAULT 1, `zalo_notify`: BOOLEAN DEFAULT 1, `portal_notify`: BOOLEAN DEFAULT 1, `updated_at`: DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 43. `VIEW kipa_student_documents` (Khung Nhìn Hợp Nhất Chống Lộ Chéo IDOR)
- **Định nghĩa SQL View:**
  ```sql
  CREATE OR REPLACE VIEW `kipa_student_documents` AS
  SELECT 
      d.id AS id,
      d.dossier_id AS dossier_id,
      d.customer_id AS customer_id,
      c.user_id AS user_id,
      c.email AS student_email,
      d.doc_group AS doc_group,
      d.doc_code AS doc_code,
      d.doc_name AS doc_name,
      d.status AS status,
      d.can_download AS can_download,
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
- **Bảo mật tuyệt đối:** Đã bổ sung `user_id`, `customer_id`, `dossier_id`. API server bắt buộc lọc theo `WHERE user_id = :session_user_id` từ Session đăng nhập.
