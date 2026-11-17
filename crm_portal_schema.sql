-- ============================================================================
-- KIPA VIỆT HÀN — SCHEMA DATABASE TOÀN DIỆN CHO CRM & STUDENT PORTAL
-- Phiên bản: 2.1.0 (Kiểm toán & Chuẩn hóa cuối cùng trước khi deploy cPanel)
-- Kiến trúc: Sử dụng chung 1 Database duy nhất, tiền tố kipa_
-- Storage Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Khóa chính: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. HỆ THỐNG CƠ CẤU TỔ CHỨC, NHÂN SỰ & PHÂN QUYỀN RBAC NÂNG CAO
-- ----------------------------------------------------------------------------

-- 1.1. Chi nhánh & Văn phòng KIPA
CREATE TABLE IF NOT EXISTS `kipa_branches` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `branch_code` VARCHAR(32) NOT NULL,
    `branch_name` VARCHAR(160) NOT NULL,
    `phone` VARCHAR(32) NOT NULL,
    `email` VARCHAR(254) NULL,
    `address` VARCHAR(255) NOT NULL,
    `province` VARCHAR(100) NOT NULL,
    `country` VARCHAR(64) NOT NULL DEFAULT 'Việt Nam',
    `manager_name` VARCHAR(160) NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_branches_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2. Phòng ban nội bộ
CREATE TABLE IF NOT EXISTS `kipa_departments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `branch_id` BIGINT UNSIGNED NULL,
    `dept_code` VARCHAR(32) NOT NULL,
    `dept_name` VARCHAR(128) NOT NULL,
    `description` TEXT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_departments_code` (`dept_code`),
    KEY `idx_kipa_departments_branch` (`branch_id`),
    CONSTRAINT `fk_kipa_departments_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.3. Nhóm làm việc (Teams)
CREATE TABLE IF NOT EXISTS `kipa_teams` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dept_id` BIGINT UNSIGNED NOT NULL,
    `team_code` VARCHAR(32) NOT NULL,
    `team_name` VARCHAR(128) NOT NULL,
    `leader_id` BIGINT UNSIGNED NULL,
    `description` VARCHAR(255) NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_teams_code` (`team_code`),
    KEY `idx_kipa_teams_dept` (`dept_id`),
    CONSTRAINT `fk_kipa_teams_dept` FOREIGN KEY (`dept_id`) REFERENCES `kipa_departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4. Hồ sơ nhân sự KIPA
CREATE TABLE IF NOT EXISTS `kipa_staff` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_code` VARCHAR(32) NOT NULL,
    `admin_id` BIGINT UNSIGNED NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `email` VARCHAR(254) NOT NULL,
    `phone` VARCHAR(32) NOT NULL,
    `branch_id` BIGINT UNSIGNED NULL,
    `dept_id` BIGINT UNSIGNED NULL,
    `team_id` BIGINT UNSIGNED NULL,
    `position` VARCHAR(128) NOT NULL,
    `avatar_url` VARCHAR(255) NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_staff_code` (`staff_code`),
    UNIQUE KEY `uq_kipa_staff_admin_id` (`admin_id`),
    KEY `idx_kipa_staff_email` (`email`),
    KEY `idx_kipa_staff_branch` (`branch_id`),
    KEY `idx_kipa_staff_dept` (`dept_id`),
    KEY `idx_kipa_staff_team` (`team_id`),
    CONSTRAINT `fk_kipa_staff_admin` FOREIGN KEY (`admin_id`) REFERENCES `kipa_admins` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_dept` FOREIGN KEY (`dept_id`) REFERENCES `kipa_departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_team` FOREIGN KEY (`team_id`) REFERENCES `kipa_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.5. Danh mục vai trò
CREATE TABLE IF NOT EXISTS `kipa_roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_code` VARCHAR(32) NOT NULL,
    `role_name` VARCHAR(128) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_system` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_roles_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.6. Danh mục quyền hạn chi tiết
CREATE TABLE IF NOT EXISTS `kipa_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permission_code` VARCHAR(64) NOT NULL,
    `permission_name` VARCHAR(128) NOT NULL,
    `module_name` VARCHAR(64) NOT NULL,
    `description` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_permissions_code` (`permission_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.7. Phân quyền nhân sự (hỗ trợ quyền tạm thời)
CREATE TABLE IF NOT EXISTS `kipa_staff_roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `granted_by` BIGINT UNSIGNED NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_staff_role` (`staff_id`, `role_id`),
    KEY `idx_kipa_sr_role` (`role_id`),
    CONSTRAINT `fk_kipa_sr_staff` FOREIGN KEY (`staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_sr_role` FOREIGN KEY (`role_id`) REFERENCES `kipa_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_sr_granter` FOREIGN KEY (`granted_by`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.8. Ủy quyền & Quyền tạm thời (Delegation)
CREATE TABLE IF NOT EXISTS `kipa_role_delegations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `delegator_id` BIGINT UNSIGNED NOT NULL,
    `delegatee_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_del_delegator` (`delegator_id`),
    KEY `idx_kipa_del_delegatee` (`delegatee_id`),
    CONSTRAINT `fk_kipa_del_delegator` FOREIGN KEY (`delegator_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_del_delegatee` FOREIGN KEY (`delegatee_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_del_role` FOREIGN KEY (`role_id`) REFERENCES `kipa_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. HỒ SƠ KHÁCH HÀNG CRM (CUSTOMER MASTER & PROFILES)
-- ----------------------------------------------------------------------------

-- 2.1. Khách hàng trung tâm (1 khách hàng có thể có nhiều hồ sơ dịch vụ)
CREATE TABLE IF NOT EXISTS `kipa_customers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_code` VARCHAR(32) NOT NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `phone` VARCHAR(32) NOT NULL,
    `email` VARCHAR(254) NULL,
    `birthday` DATE NULL,
    `gender` VARCHAR(16) NULL,
    `id_card_number` VARCHAR(32) NULL,
    `id_card_date` DATE NULL,
    `id_card_place` VARCHAR(128) NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `legacy_lead_id` BIGINT UNSIGNED NULL,
    `assigned_staff_id` BIGINT UNSIGNED NULL,
    `branch_id` BIGINT UNSIGNED NULL,
    `customer_status` VARCHAR(64) NOT NULL DEFAULT 'Mới tiếp nhận',
    `notes` TEXT NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_customers_code` (`customer_code`),
    KEY `idx_kipa_customers_phone` (`phone`),
    KEY `idx_kipa_customers_email` (`email`),
    KEY `idx_kipa_customers_status` (`customer_status`),
    KEY `idx_kipa_customers_user` (`user_id`),
    KEY `idx_kipa_customers_lead` (`legacy_lead_id`),
    KEY `idx_kipa_customers_staff` (`assigned_staff_id`),
    KEY `idx_kipa_customers_branch` (`branch_id`),
    CONSTRAINT `fk_kipa_customers_user` FOREIGN KEY (`user_id`) REFERENCES `kipa_users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_customers_lead` FOREIGN KEY (`legacy_lead_id`) REFERENCES `kipa_leads` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_customers_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_customers_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2. Sổ địa chỉ (Hộ khẩu thường trú, tạm trú)
CREATE TABLE IF NOT EXISTS `kipa_customer_addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `address_type` VARCHAR(32) NOT NULL DEFAULT 'Thường trú',
    `street_address` VARCHAR(255) NOT NULL,
    `ward` VARCHAR(100) NULL,
    `district` VARCHAR(100) NULL,
    `province` VARCHAR(100) NOT NULL,
    `is_default` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_addr_customer` (`customer_id`),
    CONSTRAINT `fk_kipa_addr_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3. Quan hệ nhân thân & liên hệ khẩn cấp (Phục vụ tài chính/visa)
CREATE TABLE IF NOT EXISTS `kipa_customer_relations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `relation_type` VARCHAR(32) NOT NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `phone` VARCHAR(32) NOT NULL,
    `email` VARCHAR(254) NULL,
    `occupation` VARCHAR(128) NULL,
    `birth_year` INT NULL,
    `address` VARCHAR(255) NULL,
    `is_emergency` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_rel_customer` (`customer_id`),
    CONSTRAINT `fk_kipa_rel_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4. Hồ sơ học vấn & ngoại ngữ (Học bạ, GPA, TOPIK, IELTS)
CREATE TABLE IF NOT EXISTS `kipa_customer_education` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `education_level` VARCHAR(64) NOT NULL,
    `school_name` VARCHAR(160) NOT NULL,
    `major` VARCHAR(160) NULL,
    `graduation_year` INT NULL,
    `gpa` DECIMAL(4,2) NULL,
    `language_certificate` VARCHAR(64) NULL,
    `language_score` VARCHAR(32) NULL,
    `certificate_date` DATE NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_edu_customer` (`customer_id`),
    CONSTRAINT `fk_kipa_edu_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. HỒ SƠ DỊCH VỤ, VÒNG ĐỜI & BÀN GIAO (SERVICE DOSSIERS & LIFECYCLE)
-- ----------------------------------------------------------------------------

-- 3.1. Hồ sơ dịch vụ (Case du học - XKLĐ)
CREATE TABLE IF NOT EXISTS `kipa_service_dossiers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_code` VARCHAR(32) NOT NULL,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `service_type` VARCHAR(64) NOT NULL,
    `program_code` VARCHAR(64) NULL,
    `target_country` VARCHAR(64) NOT NULL DEFAULT 'Hàn Quốc',
    `target_intake` VARCHAR(64) NULL,
    `target_school_name` VARCHAR(160) NULL,
    `target_major` VARCHAR(160) NULL,
    `stage` VARCHAR(64) NOT NULL DEFAULT 'Tư vấn định hướng',
    `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `consultant_id` BIGINT UNSIGNED NULL,
    `case_officer_id` BIGINT UNSIGNED NULL,
    `collaborator_id` BIGINT UNSIGNED NULL,
    `contract_number` VARCHAR(64) NULL,
    `contract_signed_at` DATE NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'Đang xử lý',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_dossier_code` (`dossier_code`),
    KEY `idx_kipa_dossier_customer` (`customer_id`),
    KEY `idx_kipa_dossier_stage` (`stage`),
    KEY `idx_kipa_dossier_status` (`status`),
    KEY `idx_kipa_dossier_consultant` (`consultant_id`),
    KEY `idx_kipa_dossier_officer` (`case_officer_id`),
    CONSTRAINT `fk_kipa_dossier_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kipa_dossier_consultant` FOREIGN KEY (`consultant_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_dossier_officer` FOREIGN KEY (`case_officer_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_dossier_collab` FOREIGN KEY (`collaborator_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.2. Lịch sử phân công & bàn giao hồ sơ
CREATE TABLE IF NOT EXISTS `kipa_dossier_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `assignment_role` VARCHAR(64) NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `unassigned_at` DATETIME NULL,
    `is_current` BOOLEAN NOT NULL DEFAULT 1,
    `note` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_assign_dossier` (`dossier_id`),
    KEY `idx_kipa_assign_staff` (`staff_id`),
    CONSTRAINT `fk_kipa_assign_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_assign_staff` FOREIGN KEY (`staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.3. Lịch sử vòng đời trạng thái hồ sơ (Tạm dừng, mở lại, lưu trữ)
CREATE TABLE IF NOT EXISTS `kipa_dossier_status_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `old_status` VARCHAR(32) NOT NULL,
    `new_status` VARCHAR(32) NOT NULL,
    `reason` TEXT NOT NULL,
    `changed_by` BIGINT UNSIGNED NOT NULL,
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_sh_dossier` (`dossier_id`),
    KEY `idx_kipa_sh_changer` (`changed_by`),
    CONSTRAINT `fk_kipa_sh_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_sh_staff` FOREIGN KEY (`changed_by`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. NHIỆM VỤ (SLA), PHÊ DUYỆT NHIỀU CẤP & KIỂM TOÁN
-- ----------------------------------------------------------------------------

-- 4.1. Nhiệm vụ công việc (có thời hạn SLA & cảnh báo nhắc hạn)
CREATE TABLE IF NOT EXISTS `kipa_tasks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NULL,
    `customer_id` BIGINT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `assigned_to` BIGINT UNSIGNED NOT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `priority` VARCHAR(16) NOT NULL DEFAULT 'Trung bình',
    `due_date` DATETIME NULL,
    `sla_hours` INT UNSIGNED NULL,
    `reminder_at` DATETIME NULL,
    `escalated_to` BIGINT UNSIGNED NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'Chưa bắt đầu',
    `completed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_tasks_dossier` (`dossier_id`),
    KEY `idx_kipa_tasks_customer` (`customer_id`),
    KEY `idx_kipa_tasks_assigned` (`assigned_to`),
    KEY `idx_kipa_tasks_status` (`status`),
    KEY `idx_kipa_tasks_due` (`due_date`),
    CONSTRAINT `fk_kipa_tasks_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_tasks_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kipa_tasks_escalated` FOREIGN KEY (`escalated_to`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.2. Phê duyệt nhiều cấp (Cấp 1: Trưởng phòng, Cấp 2: Ban Giám đốc)
CREATE TABLE IF NOT EXISTS `kipa_approvals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `approval_type` VARCHAR(64) NOT NULL,
    `approval_level` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `approver_id` BIGINT UNSIGNED NULL,
    `next_approver_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'Chờ duyệt',
    `comments` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_app_dossier` (`dossier_id`),
    KEY `idx_kipa_app_status` (`status`),
    CONSTRAINT `fk_kipa_app_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_app_requester` FOREIGN KEY (`requested_by`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kipa_app_approver` FOREIGN KEY (`approver_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_app_next_approver` FOREIGN KEY (`next_approver_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.3. Nhật ký kiểm toán bảo mật (Audit Logs)
CREATE TABLE IF NOT EXISTS `kipa_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_type` VARCHAR(32) NOT NULL,
    `actor_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(64) NOT NULL,
    `entity_type` VARCHAR(64) NOT NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_log_entity` (`entity_type`, `entity_id`),
    KEY `idx_kipa_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. KHO TÀI LIỆU HỢP NHẤT (SINGLE SOURCE OF TRUTH DOCUMENT VAULT)
-- Dùng chung 1 kho dữ liệu gốc cho cả CRM và Student Portal
-- ----------------------------------------------------------------------------

-- 5.1. Danh mục giấy tờ theo checklist hồ sơ
CREATE TABLE IF NOT EXISTS `kipa_dossier_documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `doc_group` VARCHAR(64) NOT NULL,
    `doc_code` VARCHAR(64) NOT NULL,
    `doc_name` VARCHAR(160) NOT NULL,
    `is_required` BOOLEAN NOT NULL DEFAULT 1,
    `status` VARCHAR(32) NOT NULL DEFAULT 'Chưa tải lên',
    `current_version_id` BIGINT UNSIGNED NULL,
    `notes` TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_doc_dossier` (`dossier_id`),
    KEY `idx_kipa_doc_customer` (`customer_id`),
    KEY `idx_kipa_doc_status` (`status`),
    CONSTRAINT `fk_kipa_doc_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_doc_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.2. Các phiên bản file tải lên (không ghi đè mất file)
CREATE TABLE IF NOT EXISTS `kipa_document_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `document_id` BIGINT UNSIGNED NOT NULL,
    `version_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `file_url` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(160) NOT NULL,
    `file_size` VARCHAR(32) NOT NULL DEFAULT '',
    `mime_type` VARCHAR(64) NOT NULL DEFAULT 'application/pdf',
    `uploaded_by_type` VARCHAR(32) NOT NULL DEFAULT 'STUDENT',
    `uploaded_by_id` BIGINT UNSIGNED NULL,
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `note` TEXT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_ver_document` (`document_id`),
    CONSTRAINT `fk_kipa_ver_document` FOREIGN KEY (`document_id`) REFERENCES `kipa_dossier_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.3. Lịch sử thẩm định & lý do yêu cầu bổ sung của chuyên viên KIPA
CREATE TABLE IF NOT EXISTS `kipa_document_reviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `version_id` BIGINT UNSIGNED NOT NULL,
    `reviewer_id` BIGINT UNSIGNED NOT NULL,
    `review_status` VARCHAR(32) NOT NULL,
    `review_note` TEXT NULL,
    `rejection_reason` TEXT NULL,
    `reviewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_rev_version` (`version_id`),
    KEY `idx_kipa_rev_reviewer` (`reviewer_id`),
    CONSTRAINT `fk_kipa_rev_version` FOREIGN KEY (`version_id`) REFERENCES `kipa_document_versions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_rev_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.4. VIEW HỢP NHẤT CHO CỔNG HỌC VIÊN (Không tạo bảng trùng lặp!)
-- Ánh xạ trực tiếp từ kho tài liệu gốc sang định dạng hiển thị của Student Portal
CREATE OR REPLACE VIEW `kipa_student_documents` AS
SELECT 
    d.id AS id,
    d.dossier_id AS dossier_id,
    c.email AS student_email,
    d.doc_group AS doc_group,
    d.doc_name AS doc_name,
    d.status AS status,
    COALESCE(v.file_url, '') AS file_url,
    COALESCE(v.file_name, '') AS file_name,
    COALESCE(v.file_size, '') AS file_size,
    COALESCE(DATE_FORMAT(v.uploaded_at, '%d/%m/%Y'), '') AS uploaded_at,
    d.notes AS note,
    d.updated_at AS updated_at
FROM `kipa_dossier_documents` d
JOIN `kipa_customers` c ON d.customer_id = c.id
LEFT JOIN `kipa_document_versions` v ON d.current_version_id = v.id;

-- ----------------------------------------------------------------------------
-- 6. CỔNG THÔNG TIN HỌC VIÊN (STUDENT PORTAL SPECIFIC MODULES)
-- ----------------------------------------------------------------------------

-- 6.1. Hồ sơ học viên tổng hợp nạp nhanh Portal
CREATE TABLE IF NOT EXISTS `kipa_students` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NULL,
    `email` VARCHAR(254) NOT NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `avatar_url` VARCHAR(255) DEFAULT '/assets/home/avatar-hoc-sinh.jpg',
    `birthday` DATE NULL,
    `gender` VARCHAR(16) DEFAULT 'Nữ',
    `phone` VARCHAR(32) NULL,
    `address` VARCHAR(255) NULL,
    `education_level` VARCHAR(128) DEFAULT 'Tốt nghiệp THPT',
    `school_name` VARCHAR(160) DEFAULT 'THPT Lê Quý Đôn',
    `emergency_contact` VARCHAR(255) NULL,
    `student_status` VARCHAR(64) DEFAULT 'Đang học',
    `current_program` VARCHAR(160) DEFAULT 'Du học Hàn Quốc (D2-2)',
    `program_code` VARCHAR(64) DEFAULT 'KR-D2-2026',
    `country` VARCHAR(64) DEFAULT 'Hàn Quốc',
    `registration_date` VARCHAR(32) DEFAULT '15/09/2026',
    `target_departure` VARCHAR(64) DEFAULT 'Tháng 09/2027',
    `dossier_status` VARCHAR(64) DEFAULT 'Đang chuẩn bị',
    `progress_percent` INT UNSIGNED DEFAULT 72,
    `advisor_name` VARCHAR(160) DEFAULT 'Cô Trần Thu Hà',
    `advisor_role` VARCHAR(128) DEFAULT 'Cố vấn học tập KIPA',
    `advisor_phone` VARCHAR(32) DEFAULT '036 851 3865',
    `advisor_email` VARCHAR(254) DEFAULT 'duhocquoctekipaviethan@gmail.com',
    `advisor_avatar` VARCHAR(255) DEFAULT '/media/nhansukipa/thuy-anh-kipa-viet-han.png',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_students_email` (`email`),
    KEY `idx_kipa_students_customer` (`customer_id`),
    CONSTRAINT `fk_kipa_students_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.2. Lộ trình cá nhân 5 mốc tiến độ
CREATE TABLE IF NOT EXISTS `kipa_student_journey` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NULL,
    `student_email` VARCHAR(254) NOT NULL,
    `step_number` INT UNSIGNED NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `status` VARCHAR(64) NOT NULL,
    `progress_percent` INT UNSIGNED DEFAULT 0,
    `description` TEXT NULL,
    `deadline` VARCHAR(64) NULL,
    `in_charge` VARCHAR(160) NULL,
    `tasks_json` JSON NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_journey_email` (`student_email`),
    KEY `idx_kipa_student_journey_dossier` (`dossier_id`),
    CONSTRAINT `fk_kipa_sj_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.3. Lịch học & lịch hẹn
CREATE TABLE IF NOT EXISTS `kipa_student_schedules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_email` VARCHAR(254) NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `schedule_type` VARCHAR(64) NOT NULL,
    `day_label` VARCHAR(64) NOT NULL,
    `time_range` VARCHAR(64) NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `mentor` VARCHAR(160) NOT NULL,
    `status` VARCHAR(64) DEFAULT 'Sắp diễn ra',
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_schedules_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.4. Trung tâm thông báo
CREATE TABLE IF NOT EXISTS `kipa_student_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_email` VARCHAR(254) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `category` VARCHAR(64) NOT NULL,
    `time_label` VARCHAR(64) NOT NULL,
    `is_read` BOOLEAN NOT NULL DEFAULT 0,
    `is_urgent` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_notifications_email` (`student_email`),
    KEY `idx_kipa_student_notifications_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.5. Thư viện tài liệu học tập PDF
CREATE TABLE IF NOT EXISTS `kipa_student_materials` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(64) NOT NULL,
    `format` VARCHAR(16) DEFAULT 'PDF',
    `file_url` VARCHAR(255) NOT NULL,
    `file_size` VARCHAR(32) DEFAULT '',
    `description` TEXT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.6. Kênh gửi yêu cầu hỗ trợ (Support Tickets)
CREATE TABLE IF NOT EXISTS `kipa_student_support` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_email` VARCHAR(254) NOT NULL,
    `ticket_code` VARCHAR(32) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `category` VARCHAR(64) NOT NULL,
    `status` VARCHAR(64) DEFAULT 'Đang xử lý',
    `advisor_reply` TEXT NULL,
    `assigned_staff_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_support_email` (`student_email`),
    KEY `idx_kipa_student_support_status` (`status`),
    CONSTRAINT `fk_kipa_support_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.7. Cài đặt nhận thông báo
CREATE TABLE IF NOT EXISTS `kipa_notification_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `email_notify` BOOLEAN NOT NULL DEFAULT 1,
    `sms_notify` BOOLEAN NOT NULL DEFAULT 1,
    `zalo_notify` BOOLEAN NOT NULL DEFAULT 1,
    `portal_notify` BOOLEAN NOT NULL DEFAULT 1,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_notif_cust` (`customer_id`),
    CONSTRAINT `fk_kipa_notif_cust` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
