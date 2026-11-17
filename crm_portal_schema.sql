-- ============================================================================
-- KIPA VIỆT HÀN — SCHEMA DATABASE TOÀN DIỆN CHO CRM & STUDENT PORTAL
-- Phiên bản: 2.2.0 (Hoàn thiện nghiệp vụ Tư vấn, Phân quyền, SLA, Phê duyệt, Kho tài liệu & Bảo mật)
-- Kiến trúc: Sử dụng chung 1 Database duy nhất, tiền tố kipa_
-- Storage Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Khóa chính: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
-- Lưu ý: TUYỆT ĐỐI CHƯA CHẠY TRỰC TIẾP TRÊN PRODUCTION KHI CHƯA ĐƯỢC DUYỆT
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. HỆ THỐNG CƠ CẤU TỔ CHỨC, NHÂN SỰ & PHÂN QUYỀN RBAC NÂNG CAO
-- ============================================================================

-- 1.1. Chi nhánh & Văn phòng đại diện KIPA (7 cơ sở tại VN, Hàn Quốc, Úc)
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

-- 1.2. Phòng ban nội bộ (Tuyển sinh, Xử lý hồ sơ, Đào tạo, Đối ngoại, Kế toán)
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

-- 1.3. Nhóm làm việc chuyên môn (Teams: Team Hàn 1, Team Úc, Team Visa...)
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

-- 1.4. Hồ sơ cán bộ nhân viên KIPA (quản lý trực tiếp chính: direct_manager_id)
CREATE TABLE IF NOT EXISTS `kipa_staff` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_code` VARCHAR(32) NOT NULL,
    `admin_id` BIGINT UNSIGNED NULL,
    `direct_manager_id` BIGINT UNSIGNED NULL,
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
    UNIQUE KEY `uq_kipa_staff_admin` (`admin_id`),
    KEY `idx_kipa_staff_email` (`email`),
    KEY `idx_kipa_staff_branch` (`branch_id`),
    KEY `idx_kipa_staff_dept` (`dept_id`),
    KEY `idx_kipa_staff_team` (`team_id`),
    KEY `idx_kipa_staff_manager` (`direct_manager_id`),
    CONSTRAINT `fk_kipa_staff_admin` FOREIGN KEY (`admin_id`) REFERENCES `kipa_admins` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_manager` FOREIGN KEY (`direct_manager_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_dept` FOREIGN KEY (`dept_id`) REFERENCES `kipa_departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_staff_team` FOREIGN KEY (`team_id`) REFERENCES `kipa_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.5. Danh mục vai trò CRM (ADMIN, DIRECTOR, CONSULTANT, CASE_OFFICER...)
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

-- 1.6. Danh mục quyền hạn chi tiết (view_leads, review_docs, approve_visa...)
CREATE TABLE IF NOT EXISTS `kipa_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permission_code` VARCHAR(64) NOT NULL,
    `permission_name` VARCHAR(128) NOT NULL,
    `module_name` VARCHAR(64) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_sensitive` BOOLEAN NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_permissions_code` (`permission_code`),
    KEY `idx_kipa_permissions_module` (`module_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.7. Quan hệ Vai trò - Quyền hạn cụ thể (Role-Permissions Matrix)
CREATE TABLE IF NOT EXISTS `kipa_role_permissions` (
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_kipa_rp_perm` (`permission_id`),
    CONSTRAINT `fk_kipa_rp_role` FOREIGN KEY (`role_id`) REFERENCES `kipa_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `kipa_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.8. Bảng gán vai trò nhân sự kèm phạm vi (Scope) và thời hạn tạm thời
CREATE TABLE IF NOT EXISTS `kipa_staff_roles` (
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `scope` VARCHAR(32) NOT NULL DEFAULT 'ASSIGNED_ONLY',
    `granted_by` BIGINT UNSIGNED NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NULL,
    PRIMARY KEY (`staff_id`, `role_id`),
    KEY `idx_kipa_sr_role` (`role_id`),
    KEY `idx_kipa_sr_granter` (`granted_by`),
    CONSTRAINT `fk_kipa_sr_staff` FOREIGN KEY (`staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_sr_role` FOREIGN KEY (`role_id`) REFERENCES `kipa_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_sr_granter` FOREIGN KEY (`granted_by`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.9. Lịch sử cấp, sửa, thu hồi quyền (Audit trail khi chuyển phòng ban)
CREATE TABLE IF NOT EXISTS `kipa_staff_role_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(32) NOT NULL,
    `scope` VARCHAR(32) NOT NULL,
    `reason` VARCHAR(255) NULL,
    `performed_by` BIGINT UNSIGNED NULL,
    `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_srh_staff` (`staff_id`),
    KEY `idx_kipa_srh_time` (`occurred_at`),
    CONSTRAINT `fk_kipa_srh_staff` FOREIGN KEY (`staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_srh_role` FOREIGN KEY (`role_id`) REFERENCES `kipa_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_srh_actor` FOREIGN KEY (`performed_by`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.10. Ủy quyền vai trò tạm thời (Nghỉ phép, công tác có phê duyệt & phạm vi)
CREATE TABLE IF NOT EXISTS `kipa_role_delegations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `delegator_id` BIGINT UNSIGNED NOT NULL,
    `delegatee_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `delegated_scope` VARCHAR(32) NOT NULL DEFAULT 'BRANCH',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `reason` VARCHAR(255) NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_del_delegator` (`delegator_id`),
    KEY `idx_kipa_del_delegatee` (`delegatee_id`),
    KEY `idx_kipa_del_dates` (`start_date`, `end_date`),
    CONSTRAINT `fk_kipa_del_delegator` FOREIGN KEY (`delegator_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_del_delegatee` FOREIGN KEY (`delegatee_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_del_role` FOREIGN KEY (`role_id`) REFERENCES `kipa_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_del_approver` FOREIGN KEY (`approved_by`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. HỆ THỐNG KHÁCH HÀNG TRUNG TÂM CRM & HỒ SƠ PHÁP LÝ
-- ============================================================================

-- 2.1. Hồ sơ Khách hàng trung tâm (Single Source of Customer Identity)
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
    `lead_source_id` BIGINT UNSIGNED NULL,
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
    KEY `idx_kipa_customers_user` (`user_id`),
    KEY `idx_kipa_customers_lead` (`legacy_lead_id`),
    KEY `idx_kipa_customers_staff` (`assigned_staff_id`),
    KEY `idx_kipa_customers_branch` (`branch_id`),
    KEY `idx_kipa_customers_status` (`customer_status`),
    CONSTRAINT `fk_kipa_customers_user` FOREIGN KEY (`user_id`) REFERENCES `kipa_users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_customers_lead` FOREIGN KEY (`legacy_lead_id`) REFERENCES `kipa_leads` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_customers_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_customers_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2. Sổ địa chỉ khách hàng (Hộ khẩu thường trú, Tạm trú)
CREATE TABLE IF NOT EXISTS `kipa_customer_addresses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `address_type` VARCHAR(32) NOT NULL DEFAULT 'Thường trú',
    `street_address` VARCHAR(255) NOT NULL,
    `ward` VARCHAR(100) NULL,
    `district` VARCHAR(100) NULL,
    `province` VARCHAR(100) NOT NULL,
    `country` VARCHAR(64) NOT NULL DEFAULT 'Việt Nam',
    `is_default` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_addr_cust` (`customer_id`),
    CONSTRAINT `fk_kipa_addr_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3. Quan hệ nhân thân & khẩn cấp (Chứng minh tài chính, bảo lãnh visa)
CREATE TABLE IF NOT EXISTS `kipa_customer_relations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `relation_type` VARCHAR(32) NOT NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `phone` VARCHAR(32) NOT NULL,
    `email` VARCHAR(254) NULL,
    `occupation` VARCHAR(128) NULL,
    `birth_year` SMALLINT UNSIGNED NULL,
    `id_card_number` VARCHAR(32) NULL,
    `address` VARCHAR(255) NULL,
    `monthly_income` DECIMAL(15,2) NULL,
    `is_emergency` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_rel_cust` (`customer_id`),
    CONSTRAINT `fk_kipa_rel_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4. Hồ sơ học vấn & ngoại ngữ (GPA học bạ, TOPIK, IELTS, KLAT)
CREATE TABLE IF NOT EXISTS `kipa_customer_education` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `education_level` VARCHAR(64) NOT NULL,
    `school_name` VARCHAR(160) NOT NULL,
    `major` VARCHAR(128) NULL,
    `graduation_year` SMALLINT UNSIGNED NOT NULL,
    `gpa` DECIMAL(3,2) NULL,
    `language_certificate` VARCHAR(64) NULL,
    `language_score` VARCHAR(32) NULL,
    `certificate_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_edu_cust` (`customer_id`),
    CONSTRAINT `fk_kipa_edu_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. HỆ THỐNG TIẾP NHẬN LEAD, CHIẾN DỊCH & HỒ SƠ TƯ VẤN CRM (CONSULTATION)
-- ============================================================================

-- 3.1. Danh mục Nguồn tiếp nhận Lead (Facebook, Zalo, Website, Hotline, Giới thiệu)
CREATE TABLE IF NOT EXISTS `kipa_lead_sources` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_code` VARCHAR(32) NOT NULL,
    `source_name` VARCHAR(128) NOT NULL,
    `channel_type` VARCHAR(64) NOT NULL DEFAULT 'DIGITAL',
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_ls_code` (`source_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.2. Chiến dịch tiếp thị & tuyển sinh (Campaigns)
CREATE TABLE IF NOT EXISTS `kipa_campaigns` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_code` VARCHAR(32) NOT NULL,
    `campaign_name` VARCHAR(160) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `budget` DECIMAL(15,2) NULL,
    `channel` VARCHAR(64) NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'ACTIVE',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_camp_code` (`campaign_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.3. Hồ sơ tư vấn riêng biệt (Tách độc lập với Hồ sơ dịch vụ đã ký hợp đồng)
CREATE TABLE IF NOT EXISTS `kipa_consultation_dossiers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `consultation_code` VARCHAR(32) NOT NULL,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `legacy_lead_id` BIGINT UNSIGNED NULL,
    `lead_source_id` BIGINT UNSIGNED NULL,
    `campaign_id` BIGINT UNSIGNED NULL,
    `assigned_staff_id` BIGINT UNSIGNED NULL,
    `stage` VARCHAR(32) NOT NULL DEFAULT 'NEW',
    `primary_interest` VARCHAR(64) NOT NULL,
    `target_intake` VARCHAR(32) NULL,
    `estimated_budget` DECIMAL(15,2) NULL,
    `last_contacted_at` DATETIME NULL,
    `next_appointment_at` DATETIME NULL,
    `lost_reason` VARCHAR(128) NULL,
    `lost_notes` TEXT NULL,
    `handover_status` VARCHAR(32) NOT NULL DEFAULT 'NONE',
    `handover_notes` TEXT NULL,
    `handed_over_by` BIGINT UNSIGNED NULL,
    `handed_over_at` DATETIME NULL,
    `accepted_by` BIGINT UNSIGNED NULL,
    `accepted_at` DATETIME NULL,
    `created_service_dossier_id` BIGINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_cd_code` (`consultation_code`),
    KEY `idx_kipa_cd_cust` (`customer_id`),
    KEY `idx_kipa_cd_staff` (`assigned_staff_id`),
    KEY `idx_kipa_cd_stage` (`stage`),
    KEY `idx_kipa_cd_next` (`next_appointment_at`),
    CONSTRAINT `fk_kipa_cd_cust` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_cd_lead` FOREIGN KEY (`legacy_lead_id`) REFERENCES `kipa_leads` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_cd_source` FOREIGN KEY (`lead_source_id`) REFERENCES `kipa_lead_sources` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_cd_camp` FOREIGN KEY (`campaign_id`) REFERENCES `kipa_campaigns` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_cd_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_cd_handover_by` FOREIGN KEY (`handed_over_by`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_cd_accept_by` FOREIGN KEY (`accepted_by`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.4. Chi tiết nhu cầu dịch vụ trong hồ sơ tư vấn
CREATE TABLE IF NOT EXISTS `kipa_consultation_interests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `consultation_id` BIGINT UNSIGNED NOT NULL,
    `service_type` VARCHAR(64) NOT NULL,
    `target_country` VARCHAR(64) NOT NULL,
    `target_major` VARCHAR(128) NULL,
    `preferred_school` VARCHAR(160) NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_ci_cons` (`consultation_id`),
    CONSTRAINT `fk_kipa_ci_cons` FOREIGN KEY (`consultation_id`) REFERENCES `kipa_consultation_dossiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.5. Lịch sử hoạt động tư vấn (Cuộc gọi, gặp mặt, tương tác Zalo, lịch hẹn)
CREATE TABLE IF NOT EXISTS `kipa_consultation_activities` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `consultation_id` BIGINT UNSIGNED NOT NULL,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `activity_type` VARCHAR(32) NOT NULL,
    `activity_time` DATETIME NOT NULL,
    `outcome` VARCHAR(64) NOT NULL,
    `content` TEXT NOT NULL,
    `scheduled_next_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_ca_cons` (`consultation_id`),
    KEY `idx_kipa_ca_staff` (`staff_id`),
    KEY `idx_kipa_ca_time` (`activity_time`),
    CONSTRAINT `fk_kipa_ca_cons` FOREIGN KEY (`consultation_id`) REFERENCES `kipa_consultation_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_ca_staff` FOREIGN KEY (`staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. HỆ THỐNG HỒ SƠ DỊCH VỤ, VÒNG ĐỜI & LỊCH SỬ BÀN GIAO (CRM SERVICE DOSSIERS)
-- ============================================================================

-- 4.1. Hồ sơ Dịch vụ / Hợp đồng đã ký kết chính thức
CREATE TABLE IF NOT EXISTS `kipa_service_dossiers` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_code` VARCHAR(32) NOT NULL,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `consultation_dossier_id` BIGINT UNSIGNED NULL,
    `service_type` VARCHAR(64) NOT NULL,
    `program_id` BIGINT UNSIGNED NULL,
    `target_country` VARCHAR(64) NOT NULL,
    `target_intake` VARCHAR(32) NULL,
    `target_school_id` BIGINT UNSIGNED NULL,
    `target_major` VARCHAR(160) NULL,
    `stage` VARCHAR(64) NOT NULL DEFAULT 'Tư vấn',
    `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `consultant_id` BIGINT UNSIGNED NULL,
    `case_officer_id` BIGINT UNSIGNED NULL,
    `collaborator_id` BIGINT UNSIGNED NULL,
    `contract_number` VARCHAR(64) NULL,
    `contract_signed_at` DATE NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'ACTIVE',
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_dossiers_code` (`dossier_code`),
    KEY `idx_kipa_dossiers_customer` (`customer_id`),
    KEY `idx_kipa_dossiers_status` (`status`),
    KEY `idx_kipa_dossiers_stage` (`stage`),
    KEY `idx_kipa_dossiers_consultant` (`consultant_id`),
    KEY `idx_kipa_dossiers_officer` (`case_officer_id`),
    KEY `idx_kipa_dossiers_collab` (`collaborator_id`),
    CONSTRAINT `fk_kipa_dossier_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kipa_dossier_consultation` FOREIGN KEY (`consultation_dossier_id`) REFERENCES `kipa_consultation_dossiers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_dossier_consultant` FOREIGN KEY (`consultant_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_dossier_officer` FOREIGN KEY (`case_officer_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_dossier_collab` FOREIGN KEY (`collaborator_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.2. Lịch sử phân công & bàn giao phụ trách hồ sơ (Tư vấn, Hồ sơ, CTV)
CREATE TABLE IF NOT EXISTS `kipa_dossier_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `assignment_role` VARCHAR(32) NOT NULL DEFAULT 'CASE_OFFICER',
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

-- 4.3. Lịch sử vòng đời hồ sơ (Tạm dừng, Mở lại, Đóng, Lưu trữ)
CREATE TABLE IF NOT EXISTS `kipa_dossier_status_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `from_status` VARCHAR(64) NOT NULL,
    `to_status` VARCHAR(64) NOT NULL,
    `change_type` VARCHAR(32) NOT NULL,
    `reason` TEXT NOT NULL,
    `changed_by` BIGINT UNSIGNED NOT NULL,
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_sh_dossier` (`dossier_id`),
    KEY `idx_kipa_sh_time` (`changed_at`),
    CONSTRAINT `fk_kipa_sh_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_sh_staff` FOREIGN KEY (`changed_by`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. HỆ THỐNG NHIỆM VỤ, QUẢN LÝ SLA, LỊCH LÀM VIỆC & NHẮC HẠN (TASKS & SLA)
-- ============================================================================

-- 5.1. Chính sách SLA theo dịch vụ và chi nhánh
CREATE TABLE IF NOT EXISTS `kipa_sla_policies` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `policy_code` VARCHAR(32) NOT NULL,
    `policy_name` VARCHAR(128) NOT NULL,
    `service_type` VARCHAR(64) NULL,
    `branch_id` BIGINT UNSIGNED NULL,
    `priority` VARCHAR(16) NOT NULL DEFAULT 'MEDIUM',
    `response_time_hours` INT UNSIGNED NOT NULL DEFAULT 8,
    `resolution_time_hours` INT UNSIGNED NOT NULL DEFAULT 48,
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_sla_code` (`policy_code`),
    KEY `idx_kipa_sla_branch` (`branch_id`),
    CONSTRAINT `fk_kipa_sla_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.2. Lịch làm việc & ngày nghỉ lễ tính SLA theo thời gian hành chính thực tế
CREATE TABLE IF NOT EXISTS `kipa_working_calendars` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `branch_id` BIGINT UNSIGNED NULL,
    `calendar_date` DATE NOT NULL,
    `is_working_day` BOOLEAN NOT NULL DEFAULT 1,
    `start_time` TIME NOT NULL DEFAULT '08:00:00',
    `end_time` TIME NOT NULL DEFAULT '17:30:00',
    `lunch_start` TIME NOT NULL DEFAULT '12:00:00',
    `lunch_end` TIME NOT NULL DEFAULT '13:30:00',
    `holiday_name` VARCHAR(128) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_wc_date_branch` (`branch_id`, `calendar_date`),
    KEY `idx_kipa_wc_date` (`calendar_date`),
    CONSTRAINT `fk_kipa_wc_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.3. Nhiệm vụ công việc (Nhiệm vụ con, SLA tạm dừng/tiếp tục, xác nhận hoàn thành)
CREATE TABLE IF NOT EXISTS `kipa_tasks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` BIGINT UNSIGNED NULL,
    `dossier_id` BIGINT UNSIGNED NULL,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `assigned_to` BIGINT UNSIGNED NOT NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `priority` VARCHAR(16) NOT NULL DEFAULT 'MEDIUM',
    `sla_policy_id` BIGINT UNSIGNED NULL,
    `sla_hours` INT UNSIGNED NULL,
    `sla_status` VARCHAR(32) NOT NULL DEFAULT 'RUNNING',
    `sla_paused_at` DATETIME NULL,
    `sla_pause_reason` VARCHAR(255) NULL,
    `sla_total_paused_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
    `due_date` DATETIME NULL,
    `reminder_at` DATETIME NULL,
    `escalated_to` BIGINT UNSIGNED NULL,
    `escalation_level` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `is_recurring` BOOLEAN NOT NULL DEFAULT 0,
    `recurrence_pattern` VARCHAR(32) NULL,
    `recurrence_end_date` DATE NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'TODO',
    `completed_at` DATETIME NULL,
    `completion_confirmed_by` BIGINT UNSIGNED NULL,
    `completion_confirmed_at` DATETIME NULL,
    `completion_rating` TINYINT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_tasks_parent` (`parent_id`),
    KEY `idx_kipa_tasks_dossier` (`dossier_id`),
    KEY `idx_kipa_tasks_customer` (`customer_id`),
    KEY `idx_kipa_tasks_assigned` (`assigned_to`),
    KEY `idx_kipa_tasks_status` (`status`),
    KEY `idx_kipa_tasks_due` (`due_date`),
    KEY `idx_kipa_tasks_reminder` (`reminder_at`),
    CONSTRAINT `fk_kipa_tasks_parent` FOREIGN KEY (`parent_id`) REFERENCES `kipa_tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_tasks_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_tasks_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kipa_tasks_sla` FOREIGN KEY (`sla_policy_id`) REFERENCES `kipa_sla_policies` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_tasks_escalated` FOREIGN KEY (`escalated_to`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_tasks_confirmer` FOREIGN KEY (`completion_confirmed_by`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.4. Người phối hợp trong nhiệm vụ (Task Collaborators)
CREATE TABLE IF NOT EXISTS `kipa_task_collaborators` (
    `task_id` BIGINT UNSIGNED NOT NULL,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `role_description` VARCHAR(128) NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`task_id`, `staff_id`),
    KEY `idx_kipa_tc_staff` (`staff_id`),
    CONSTRAINT `fk_kipa_tc_task` FOREIGN KEY (`task_id`) REFERENCES `kipa_tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_tc_staff` FOREIGN KEY (`staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5.5. Lịch sử thay đổi nhiệm vụ và gia hạn xử lý
CREATE TABLE IF NOT EXISTS `kipa_task_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` BIGINT UNSIGNED NOT NULL,
    `changed_by` BIGINT UNSIGNED NOT NULL,
    `field_name` VARCHAR(64) NOT NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NULL,
    `reason` VARCHAR(255) NULL,
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_th_task` (`task_id`),
    KEY `idx_kipa_th_time` (`changed_at`),
    CONSTRAINT `fk_kipa_th_task` FOREIGN KEY (`task_id`) REFERENCES `kipa_tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_th_staff` FOREIGN KEY (`changed_by`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. HỆ THỐNG QUY TRÌNH PHÊ DUYỆT NHIỀU CẤP & PHÊ DUYỆT KÉP (APPROVALS)
-- ============================================================================

-- 6.1. Định nghĩa quy trình phê duyệt (Workflows: Duyệt hợp đồng, nộp trường, visa, hoàn cọc)
CREATE TABLE IF NOT EXISTS `kipa_approval_workflows` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_code` VARCHAR(32) NOT NULL,
    `workflow_name` VARCHAR(128) NOT NULL,
    `approval_type` VARCHAR(64) NOT NULL,
    `version` INT UNSIGNED NOT NULL DEFAULT 1,
    `requires_dual_approval` BOOLEAN NOT NULL DEFAULT 0,
    `description` VARCHAR(255) NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_aw_code` (`workflow_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.2. Các bước chi tiết trong quy trình duyệt (Workflow Steps)
CREATE TABLE IF NOT EXISTS `kipa_approval_workflow_steps` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `workflow_id` BIGINT UNSIGNED NOT NULL,
    `step_order` INT UNSIGNED NOT NULL DEFAULT 1,
    `step_name` VARCHAR(128) NOT NULL,
    `required_role_id` BIGINT UNSIGNED NULL,
    `specific_approver_id` BIGINT UNSIGNED NULL,
    `sla_hours` INT UNSIGNED NOT NULL DEFAULT 24,
    `is_final_step` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_aws_order` (`workflow_id`, `step_order`),
    KEY `idx_kipa_aws_role` (`required_role_id`),
    CONSTRAINT `fk_kipa_aws_wf` FOREIGN KEY (`workflow_id`) REFERENCES `kipa_approval_workflows` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_aws_role` FOREIGN KEY (`required_role_id`) REFERENCES `kipa_roles` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_aws_approver` FOREIGN KEY (`specific_approver_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.3. Bảng yêu cầu phê duyệt thực tế (Snapshot dữ liệu, Phê duyệt kép, Chống tự duyệt)
CREATE TABLE IF NOT EXISTS `kipa_approvals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `workflow_id` BIGINT UNSIGNED NULL,
    `step_id` BIGINT UNSIGNED NULL,
    `approval_type` VARCHAR(64) NOT NULL,
    `approval_level` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `snapshot_payload` JSON NOT NULL,
    `requested_by` BIGINT UNSIGNED NOT NULL,
    `approver_id` BIGINT UNSIGNED NULL,
    `next_approver_id` BIGINT UNSIGNED NULL,
    `second_approver_id` BIGINT UNSIGNED NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'PENDING',
    `comments` TEXT NULL,
    `second_comments` TEXT NULL,
    `reviewed_at` DATETIME NULL,
    `second_approved_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_app_dossier` (`dossier_id`),
    KEY `idx_kipa_app_status` (`status`),
    KEY `idx_kipa_app_requester` (`requested_by`),
    KEY `idx_kipa_app_approver` (`approver_id`),
    KEY `idx_kipa_app_second` (`second_approver_id`),
    CONSTRAINT `fk_kipa_app_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_kipa_app_wf` FOREIGN KEY (`workflow_id`) REFERENCES `kipa_approval_workflows` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_app_step` FOREIGN KEY (`step_id`) REFERENCES `kipa_approval_workflow_steps` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_app_requester` FOREIGN KEY (`requested_by`) REFERENCES `kipa_staff` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kipa_app_approver` FOREIGN KEY (`approver_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_app_next` FOREIGN KEY (`next_approver_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_kipa_app_second` FOREIGN KEY (`second_approver_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_kipa_app_no_self` CHECK (`requested_by` != `approver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.4. Nhật ký kiểm toán thao tác hệ thống (Audit Logs)
CREATE TABLE IF NOT EXISTS `kipa_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_type` VARCHAR(32) NOT NULL DEFAULT 'STAFF',
    `actor_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(64) NOT NULL,
    `entity_type` VARCHAR(64) NOT NULL,
    `entity_id` BIGINT UNSIGNED NOT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_audit_actor` (`actor_type`, `actor_id`),
    KEY `idx_kipa_audit_entity` (`entity_type`, `entity_id`),
    KEY `idx_kipa_audit_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. KHO TÀI LIỆU SỐ MASTER VAULT & BẢO MẬT TRUY CẬP (DOCUMENTS)
-- ============================================================================

-- 7.1. Danh mục giấy tờ Checklist gốc (Bảo toàn khi đóng hồ sơ: ON DELETE RESTRICT)
CREATE TABLE IF NOT EXISTS `kipa_dossier_documents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NOT NULL,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `doc_group` VARCHAR(64) NOT NULL,
    `doc_code` VARCHAR(64) NOT NULL,
    `doc_name` VARCHAR(160) NOT NULL,
    `is_required` BOOLEAN NOT NULL DEFAULT 1,
    `status` VARCHAR(64) NOT NULL DEFAULT 'Chưa tải lên',
    `access_level` VARCHAR(32) NOT NULL DEFAULT 'INTERNAL',
    `is_archived` BOOLEAN NOT NULL DEFAULT 0,
    `current_version_id` BIGINT UNSIGNED NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_doc_dossier` (`dossier_id`),
    KEY `idx_kipa_doc_customer` (`customer_id`),
    KEY `idx_kipa_doc_status` (`status`),
    CONSTRAINT `fk_kipa_doc_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_kipa_doc_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.2. Quản lý đa phiên bản file scan/PDF (Protected Storage)
CREATE TABLE IF NOT EXISTS `kipa_document_versions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `document_id` BIGINT UNSIGNED NOT NULL,
    `version_number` INT UNSIGNED NOT NULL DEFAULT 1,
    `file_url` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(160) NOT NULL,
    `file_size` VARCHAR(32) NOT NULL,
    `mime_type` VARCHAR(64) NOT NULL,
    `uploaded_by_type` VARCHAR(32) NOT NULL DEFAULT 'STUDENT',
    `uploaded_by_id` BIGINT UNSIGNED NOT NULL,
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `note` TEXT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_ver_document` (`document_id`),
    KEY `idx_kipa_ver_upload` (`uploaded_at`),
    CONSTRAINT `fk_kipa_ver_document` FOREIGN KEY (`document_id`) REFERENCES `kipa_dossier_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.3. Lịch sử thẩm định & lý do yêu cầu bổ sung của chuyên viên KIPA
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

-- 7.4. Nhật ký truy cập, xem & tải tài liệu nhạy cảm (Document Access Audit)
CREATE TABLE IF NOT EXISTS `kipa_document_access_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `version_id` BIGINT UNSIGNED NOT NULL,
    `actor_type` VARCHAR(32) NOT NULL DEFAULT 'STAFF',
    `actor_id` BIGINT UNSIGNED NOT NULL,
    `access_type` VARCHAR(32) NOT NULL DEFAULT 'VIEW',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `accessed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_dal_version` (`version_id`),
    KEY `idx_kipa_dal_actor` (`actor_type`, `actor_id`),
    KEY `idx_kipa_dal_time` (`accessed_at`),
    CONSTRAINT `fk_kipa_dal_ver` FOREIGN KEY (`version_id`) REFERENCES `kipa_document_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. GIAO DIỆN CỔNG HỌC VIÊN (STUDENT PORTAL EXPERIENCES & VIEW)
-- ============================================================================

-- 8.1. Profile Cache học viên (Hiển thị trang cá nhân tức thì, không JOIN nặng)
CREATE TABLE IF NOT EXISTS `kipa_students` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(254) NOT NULL,
    `customer_id` BIGINT UNSIGNED NULL,
    `full_name` VARCHAR(160) NOT NULL,
    `avatar_url` VARCHAR(255) NULL,
    `birthday` VARCHAR(32) DEFAULT '',
    `gender` VARCHAR(16) DEFAULT 'Khác',
    `phone` VARCHAR(32) DEFAULT '',
    `address` VARCHAR(255) DEFAULT '',
    `education_level` VARCHAR(64) DEFAULT '',
    `school_name` VARCHAR(160) DEFAULT '',
    `emergency_contact` VARCHAR(255) DEFAULT '',
    `student_status` VARCHAR(64) DEFAULT 'Đang học',
    `current_program` VARCHAR(160) DEFAULT '',
    `program_code` VARCHAR(32) DEFAULT '',
    `country` VARCHAR(64) DEFAULT '',
    `registration_date` VARCHAR(32) DEFAULT '',
    `target_departure` VARCHAR(32) DEFAULT '',
    `dossier_status` VARCHAR(64) DEFAULT 'Đang chuẩn bị',
    `progress_percent` INT DEFAULT 0,
    `advisor_name` VARCHAR(160) DEFAULT '',
    `advisor_role` VARCHAR(128) DEFAULT '',
    `advisor_phone` VARCHAR(32) DEFAULT '',
    `advisor_email` VARCHAR(254) DEFAULT '',
    `advisor_avatar` VARCHAR(255) DEFAULT '',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kipa_students_email` (`email`),
    KEY `idx_kipa_students_cust` (`customer_id`),
    CONSTRAINT `fk_kipa_students_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.2. Lộ trình cá nhân 5 mốc tiến độ kèm việc cần làm (tasks_json)
CREATE TABLE IF NOT EXISTS `kipa_student_journey` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dossier_id` BIGINT UNSIGNED NULL,
    `student_email` VARCHAR(254) NOT NULL,
    `step_number` INT UNSIGNED NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'PENDING',
    `progress_percent` INT UNSIGNED NOT NULL DEFAULT 0,
    `description` TEXT NULL,
    `deadline` VARCHAR(64) DEFAULT '',
    `in_charge` VARCHAR(160) DEFAULT '',
    `tasks_json` JSON NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_journey_email` (`student_email`),
    KEY `idx_kipa_student_journey_dossier` (`dossier_id`),
    CONSTRAINT `fk_kipa_sj_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.3. Lịch học & lịch hẹn cố vấn, phỏng vấn
CREATE TABLE IF NOT EXISTS `kipa_student_schedules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_email` VARCHAR(254) NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `schedule_type` VARCHAR(64) NOT NULL,
    `day_label` VARCHAR(32) NOT NULL,
    `time_range` VARCHAR(64) NOT NULL,
    `location` VARCHAR(160) NOT NULL,
    `mentor` VARCHAR(160) NOT NULL,
    `status` VARCHAR(64) DEFAULT 'Sắp tới',
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_schedules_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.4. Trung tâm thông báo đẩy cho học viên (Cờ khẩn cấp & đánh dấu đã đọc)
CREATE TABLE IF NOT EXISTS `kipa_student_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_email` VARCHAR(254) NOT NULL,
    `title` VARCHAR(160) NOT NULL,
    `content` TEXT NOT NULL,
    `category` VARCHAR(64) NOT NULL,
    `time_label` VARCHAR(32) NOT NULL,
    `is_read` BOOLEAN NOT NULL DEFAULT 0,
    `is_urgent` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_kipa_student_notifications_email` (`student_email`),
    KEY `idx_kipa_student_notifications_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.5. Thư viện tài liệu học tập PDF (Giáo trình tiếng Hàn, cẩm nang visa)
CREATE TABLE IF NOT EXISTS `kipa_student_materials` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(64) NOT NULL,
    `format` VARCHAR(16) NOT NULL DEFAULT 'PDF',
    `file_url` VARCHAR(255) NOT NULL,
    `file_size` VARCHAR(32) NOT NULL,
    `description` TEXT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.6. Kênh ticket gửi yêu cầu hỗ trợ & phản hồi 2 chiều với chuyên viên
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

-- 8.7. Cài đặt bật/tắt nhận thông báo của từng khách hàng
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

-- 8.8. SQL View Hợp Nhất Ví Hồ Sơ Số (Tương thích 100% Cổng học viên, chống lộ chéo dữ liệu)
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
    COALESCE(v.file_url, '') AS file_url,
    COALESCE(v.file_name, '') AS file_name,
    COALESCE(v.file_size, '') AS file_size,
    COALESCE(DATE_FORMAT(v.uploaded_at, '%Y-%m-%d %H:%i'), '') AS uploaded_at,
    d.notes AS note,
    d.updated_at AS updated_at
FROM `kipa_dossier_documents` d
JOIN `kipa_customers` c ON d.customer_id = c.id
LEFT JOIN `kipa_document_versions` v ON d.current_version_id = v.id;

SET FOREIGN_KEY_CHECKS = 1;
