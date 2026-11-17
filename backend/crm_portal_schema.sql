-- ============================================================================
-- KIPA VIỆT HÀN — SCHEMA MỞ RỘNG DATABASE CHO CRM & STUDENT PORTAL
-- Kiến trúc: Sử dụng chung Database MySQL hiện tại, phân tách theo tiền tố kipa_
-- Storage Engine: InnoDB | Bảng mã: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Khóa chính: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. BẢO TOÀN VÀ MỞ RỘNG AN TOÀN BẢNG CŨ (SAFE EXTENSION)
-- ----------------------------------------------------------------------------

-- Mở rộng kipa_leads (chống xóa dây chuyền và liên kết CRM)
-- Chạy an toàn nếu cột chưa tồn tại qua thủ tục hoặc chạy trực tiếp
-- ----------------------------------------------------------------------------

-- ----------------------------------------------------------------------------
-- 2. HỆ THỐNG TỔ CHỨC & NHÂN SỰ KIPA (CRM ORGANIZATION & RBAC)
-- ----------------------------------------------------------------------------

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

CREATE TABLE IF NOT EXISTS `kipa_staff` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_code` VARCHAR(32) NOT NULL,
    `admin_id` BIGINT UNSIGNED NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `email` VARCHAR(254) NOT NULL,
    `phone` VARCHAR(32) NOT NULL,
    `branch_id` BIGINT UNSIGNED NULL,
    `dept_id` BIGINT UNSIGNED NULL,
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
    CONSTRAINT `fk_kipa_staff_admin` FOREIGN KEY (`admin_id`) REFERENCES `kipa_admins` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_dept` FOREIGN KEY (`dept_id`) REFERENCES `kipa_departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `kipa_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permission_code` VARCHAR(64) NOT NULL,
    `permission_name` VARCHAR(128) NOT NULL,
    `module_name` VARCHAR(64) NOT NULL,
    `description` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_permissions_code` (`permission_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_staff_roles` (
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`staff_id`, `role_id`),
    CONSTRAINT `fk_kipa_sr_staff` FOREIGN KEY (`staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_sr_role` FOREIGN KEY (`role_id`) REFERENCES `kipa_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. HỒ SƠ KHÁCH HÀNG CRM (CUSTOMER MASTER & PROFILES)
-- ----------------------------------------------------------------------------

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
-- 4. HỒ SƠ DỊCH VỤ, NHIỆM VỤ & PHÊ DUYỆT (SERVICE DOSSIERS & WORKFLOW)
-- ----------------------------------------------------------------------------

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
    CONSTRAINT `fk_kipa_dossier_officer` FOREIGN KEY (`case_officer_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    `status` VARCHAR(32) NOT NULL DEFAULT 'Chưa bắt đầu',
    `completed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_tasks_dossier` (`dossier_id`),
    KEY `idx_kipa_tasks_customer` (`customer_id`),
    KEY `idx_kipa_tasks_assigned` (`assigned_to`),
    KEY `idx_kipa_tasks_status` (`status`),
    CONSTRAINT `fk_kipa_tasks_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_tasks_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_doc_dossier` (`dossier_id`),
    KEY `idx_kipa_doc_customer` (`customer_id`),
    KEY `idx_kipa_doc_status` (`status`),
    CONSTRAINT `fk_kipa_doc_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_doc_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `kipa_approvals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `approval_type` VARCHAR(64) NOT NULL,
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `approver_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'Chờ duyệt',
    `comments` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_app_dossier` (`dossier_id`),
    KEY `idx_kipa_app_status` (`status`),
    CONSTRAINT `fk_kipa_app_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_app_requester` FOREIGN KEY (`requested_by`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kipa_app_approver` FOREIGN KEY (`approver_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- ----------------------------------------------------------------------------
-- 5. PHÂN HỆ KIPA STUDENT PORTAL (CỔNG HỌC VIÊN)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `kipa_students` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
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
    UNIQUE KEY `uq_kipa_students_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_student_journey` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
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
    KEY `idx_kipa_student_journey_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_student_documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_email` VARCHAR(254) NOT NULL,
    `doc_group` VARCHAR(64) NOT NULL,
    `doc_name` VARCHAR(160) NOT NULL,
    `status` VARCHAR(64) NOT NULL,
    `file_url` VARCHAR(255) DEFAULT '',
    `file_name` VARCHAR(160) DEFAULT '',
    `file_size` VARCHAR(32) DEFAULT '',
    `uploaded_at` VARCHAR(32) DEFAULT '',
    `note` TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_documents_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    KEY `idx_kipa_student_notifications_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `kipa_student_support` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_email` VARCHAR(254) NOT NULL,
    `ticket_code` VARCHAR(32) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `category` VARCHAR(64) NOT NULL,
    `status` VARCHAR(64) DEFAULT 'Đang xử lý',
    `advisor_reply` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_support_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
