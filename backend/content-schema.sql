-- Generated from content-model.json; existing leads and admins are not replaced.
CREATE TABLE IF NOT EXISTS `kipa_programs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `code` VARCHAR(100) NULL,
  `program_type` VARCHAR(50) NOT NULL,
  `short_description` TEXT NOT NULL,
  `description` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `icon` VARCHAR(255) NULL,
  `display_order` INT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_study_abroad_programs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `program_id` BIGINT UNSIGNED NOT NULL,
  `destination_name` VARCHAR(150) NOT NULL,
  `destination_code` VARCHAR(50) NOT NULL,
  `country_id` BIGINT UNSIGNED NULL,
  `overview` LONGTEXT NULL,
  `advantages` LONGTEXT NULL,
  `education_system` LONGTEXT NULL,
  `admission_period` VARCHAR(255) NULL,
  `thumbnail` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  KEY `idx_program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_study_program_details` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `study_abroad_id` BIGINT UNSIGNED NOT NULL,
  `country_id` BIGINT UNSIGNED NULL,
  `school_id` BIGINT UNSIGNED NULL,
  `program_name` VARCHAR(255) NOT NULL,
  `program_code` VARCHAR(100) NULL,
  `education_level` VARCHAR(100) NULL,
  `visa_type` VARCHAR(100) NULL,
  `study_duration` VARCHAR(100) NULL,
  `language_requirement` TEXT NULL,
  `academic_requirement` LONGTEXT NULL,
  `age_requirement` VARCHAR(255) NULL,
  `financial_requirement` LONGTEXT NULL,
  `tuition_fee_min` DECIMAL(15,2) NULL,
  `tuition_fee_max` DECIMAL(15,2) NULL,
  `currency` VARCHAR(10) NULL,
  `intake_information` TEXT NULL,
  `required_documents` JSON NULL,
  `benefits` JSON NULL,
  `process` LONGTEXT NULL,
  `description` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_study_abroad_id` (`study_abroad_id`),
  KEY `idx_school_id` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_work_programs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `program_id` BIGINT UNSIGNED NOT NULL,
  `country_id` BIGINT UNSIGNED NOT NULL,
  `program_name` VARCHAR(255) NOT NULL,
  `program_code` VARCHAR(100) NULL,
  `industry` VARCHAR(255) NULL,
  `job_position` VARCHAR(255) NULL,
  `job_description` LONGTEXT NULL,
  `quantity` INT NULL,
  `salary_min` DECIMAL(15,2) NULL,
  `salary_max` DECIMAL(15,2) NULL,
  `salary_currency` VARCHAR(10) NULL,
  `working_hours` VARCHAR(255) NULL,
  `contract_duration` VARCHAR(100) NULL,
  `age_requirement` VARCHAR(255) NULL,
  `gender_requirement` VARCHAR(100) NULL,
  `experience_requirement` TEXT NULL,
  `language_requirement` TEXT NULL,
  `health_requirement` TEXT NULL,
  `cost_information` LONGTEXT NULL,
  `required_documents` JSON NULL,
  `recruitment_period` VARCHAR(255) NULL,
  `description` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  KEY `idx_program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_partnership_programs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `program_id` BIGINT UNSIGNED NOT NULL,
  `partnership_name` VARCHAR(255) NOT NULL,
  `partnership_type` VARCHAR(150) NOT NULL,
  `target_partner` VARCHAR(255) NULL,
  `overview` LONGTEXT NULL,
  `objectives` LONGTEXT NULL,
  `cooperation_scope` LONGTEXT NULL,
  `benefits` LONGTEXT NULL,
  `implementation_process` LONGTEXT NULL,
  `requirements` LONGTEXT NULL,
  `case_study` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  KEY `idx_program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_countries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `name_en` VARCHAR(150) NULL,
  `country_code` VARCHAR(10) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `capital` VARCHAR(150) NULL,
  `official_language` VARCHAR(255) NULL,
  `currency_code` VARCHAR(10) NULL,
  `currency_name` VARCHAR(100) NULL,
  `flag_image` VARCHAR(500) NULL,
  `thumbnail` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `short_description` TEXT NULL,
  `description` LONGTEXT NULL,
  `education_overview` LONGTEXT NULL,
  `living_overview` LONGTEXT NULL,
  `work_overview` LONGTEXT NULL,
  `display_order` INT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_country_code` (`country_code`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_schools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) NULL,
  `name_local` VARCHAR(255) NULL,
  `slug` VARCHAR(255) NOT NULL,
  `school_code` VARCHAR(100) NULL,
  `country_id` BIGINT UNSIGNED NOT NULL,
  `city` VARCHAR(150) NULL,
  `district` VARCHAR(150) NULL,
  `address` TEXT NULL,
  `postal_code` VARCHAR(30) NULL,
  `latitude` DECIMAL(10,7) NULL,
  `longitude` DECIMAL(10,7) NULL,
  `website_url` VARCHAR(500) NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `school_type` VARCHAR(50) NOT NULL,
  `establishment_year` SMALLINT NULL,
  `logo` VARCHAR(500) NULL,
  `thumbnail` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `short_description` TEXT NULL,
  `introduction` LONGTEXT NULL,
  `advantages` LONGTEXT NULL,
  `facilities` LONGTEXT NULL,
  `international_student_info` LONGTEXT NULL,
  `partnership_status` VARCHAR(50) NOT NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_intakes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `study_program_id` BIGINT UNSIGNED NULL,
  `work_program_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `school_id` BIGINT UNSIGNED NULL,
  `intake_name` VARCHAR(255) NOT NULL,
  `intake_code` VARCHAR(100) NULL,
  `application_open_date` DATE NULL,
  `application_deadline` DATE NULL,
  `document_deadline` DATE NULL,
  `expected_start_date` DATE NULL,
  `expected_end_date` DATE NULL,
  `available_slots` INT NULL,
  `enrolled_count` INT NULL,
  `intake_status` VARCHAR(50) NOT NULL,
  `description` LONGTEXT NULL,
  `note` TEXT NULL,
  `source_url` VARCHAR(500) NULL,
  `last_verified_at` DATETIME NULL,
  `display_order` INT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_study_program_id` (`study_program_id`),
  KEY `idx_work_program_id` (`work_program_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_school_id` (`school_id`),
  CONSTRAINT `ck_owner_program_intakes` CHECK (((`study_program_id` IS NOT NULL) + (`work_program_id` IS NOT NULL) + (`course_id` IS NOT NULL)) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_requirements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `study_program_id` BIGINT UNSIGNED NULL,
  `work_program_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `country_id` BIGINT UNSIGNED NULL,
  `requirement_type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `short_description` TEXT NULL,
  `content` LONGTEXT NOT NULL,
  `min_value` DECIMAL(15,2) NULL,
  `max_value` DECIMAL(15,2) NULL,
  `unit` VARCHAR(50) NULL,
  `is_required` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NULL DEFAULT '0',
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `source_url` VARCHAR(500) NULL,
  `last_verified_at` DATETIME NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_study_program_id` (`study_program_id`),
  KEY `idx_work_program_id` (`work_program_id`),
  KEY `idx_course_id` (`course_id`),
  CONSTRAINT `ck_owner_program_requirements` CHECK (((`study_program_id` IS NOT NULL) + (`work_program_id` IS NOT NULL) + (`course_id` IS NOT NULL)) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_fees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `study_program_id` BIGINT UNSIGNED NULL,
  `work_program_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `school_id` BIGINT UNSIGNED NULL,
  `fee_name` VARCHAR(255) NOT NULL,
  `fee_category` VARCHAR(50) NOT NULL,
  `description` LONGTEXT NULL,
  `amount_min` DECIMAL(15,2) NULL,
  `amount_max` DECIMAL(15,2) NULL,
  `currency_code` VARCHAR(10) NOT NULL,
  `payment_frequency` VARCHAR(100) NULL,
  `payment_stage` VARCHAR(150) NULL,
  `is_mandatory` BOOLEAN NOT NULL DEFAULT '0',
  `is_estimated` BOOLEAN NOT NULL DEFAULT '0',
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `source_url` VARCHAR(500) NULL,
  `note` TEXT NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_study_program_id` (`study_program_id`),
  KEY `idx_work_program_id` (`work_program_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_school_id` (`school_id`),
  CONSTRAINT `ck_owner_program_fees` CHECK (((`study_program_id` IS NOT NULL) + (`work_program_id` IS NOT NULL) + (`course_id` IS NOT NULL)) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `study_program_id` BIGINT UNSIGNED NULL,
  `work_program_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `document_name` VARCHAR(255) NOT NULL,
  `document_code` VARCHAR(100) NULL,
  `document_category` VARCHAR(50) NOT NULL,
  `description` LONGTEXT NULL,
  `is_required` BOOLEAN NOT NULL DEFAULT '0',
  `quantity` INT NULL,
  `document_format` VARCHAR(150) NULL,
  `notarization_required` BOOLEAN NOT NULL DEFAULT '0',
  `translation_required` BOOLEAN NOT NULL DEFAULT '0',
  `validity_period` VARCHAR(150) NULL,
  `preparation_note` TEXT NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_study_program_id` (`study_program_id`),
  KEY `idx_work_program_id` (`work_program_id`),
  KEY `idx_course_id` (`course_id`),
  CONSTRAINT `ck_owner_program_documents` CHECK (((`study_program_id` IS NOT NULL) + (`work_program_id` IS NOT NULL) + (`course_id` IS NOT NULL)) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_process_steps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `study_program_id` BIGINT UNSIGNED NULL,
  `work_program_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `step_number` INT NOT NULL,
  `step_code` VARCHAR(100) NULL,
  `title` VARCHAR(255) NOT NULL,
  `short_description` TEXT NULL,
  `description` LONGTEXT NOT NULL,
  `expected_duration` VARCHAR(100) NULL,
  `expected_duration_days` INT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `icon` VARCHAR(255) NULL,
  `is_required` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_study_program_id` (`study_program_id`),
  KEY `idx_work_program_id` (`work_program_id`),
  KEY `idx_course_id` (`course_id`),
  CONSTRAINT `ck_owner_program_process_steps` CHECK (((`study_program_id` IS NOT NULL) + (`work_program_id` IS NOT NULL) + (`course_id` IS NOT NULL)) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `program_id` BIGINT UNSIGNED NOT NULL,
  `study_program_id` BIGINT UNSIGNED NULL,
  `media_type` VARCHAR(50) NOT NULL,
  `media_url` VARCHAR(500) NOT NULL,
  `thumbnail_url` VARCHAR(500) NULL,
  `title` VARCHAR(255) NULL,
  `alt_text` VARCHAR(255) NULL,
  `caption` TEXT NULL,
  `display_order` INT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  KEY `idx_program_id` (`program_id`),
  KEY `idx_study_program_id` (`study_program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_work_industries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_work_job_positions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `work_program_id` BIGINT UNSIGNED NOT NULL,
  `industry_id` BIGINT UNSIGNED NOT NULL,
  `position_name` VARCHAR(255) NOT NULL,
  `job_description` LONGTEXT NULL,
  `quantity` INT NULL,
  `salary_min` DECIMAL(15,2) NULL,
  `salary_max` DECIMAL(15,2) NULL,
  `currency_code` VARCHAR(10) NULL,
  `working_hours` VARCHAR(255) NULL,
  `contract_duration` VARCHAR(100) NULL,
  `age_min` INT NULL,
  `age_max` INT NULL,
  `gender_requirement` VARCHAR(100) NULL,
  `experience_requirement` TEXT NULL,
  `language_requirement` TEXT NULL,
  `other_requirements` LONGTEXT NULL,
  `recruitment_deadline` DATE NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'open',
  KEY `idx_work_program_id` (`work_program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_work_program_costs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `work_program_id` BIGINT UNSIGNED NOT NULL,
  `fee_name` VARCHAR(255) NOT NULL,
  `amount_min` DECIMAL(15,2) NULL,
  `amount_max` DECIMAL(15,2) NULL,
  `currency_code` VARCHAR(10) NOT NULL,
  `payment_stage` VARCHAR(150) NULL,
  `description` LONGTEXT NULL,
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  KEY `idx_work_program_id` (`work_program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_study_fields` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) NULL,
  `slug` VARCHAR(255) NOT NULL,
  `field_code` VARCHAR(100) NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `short_description` TEXT NULL,
  `description` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `icon` VARCHAR(255) NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_school_programs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` BIGINT UNSIGNED NOT NULL,
  `study_field_id` BIGINT UNSIGNED NULL,
  `study_program_detail_id` BIGINT UNSIGNED NULL,
  `program_name` VARCHAR(255) NOT NULL,
  `program_code` VARCHAR(100) NULL,
  `education_level` VARCHAR(100) NULL,
  `degree_type` VARCHAR(150) NULL,
  `language_of_instruction` VARCHAR(150) NULL,
  `study_duration` VARCHAR(100) NULL,
  `tuition_fee_min` DECIMAL(15,2) NULL,
  `tuition_fee_max` DECIMAL(15,2) NULL,
  `currency_code` VARCHAR(10) NULL,
  `admission_requirement` LONGTEXT NULL,
  `language_requirement` TEXT NULL,
  `intake_information` TEXT NULL,
  `scholarship_information` LONGTEXT NULL,
  `program_description` LONGTEXT NULL,
  `official_source_url` VARCHAR(500) NULL,
  `last_verified_at` DATETIME NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_school_id` (`school_id`),
  KEY `idx_study_field_id` (`study_field_id`),
  KEY `idx_study_program_detail_id` (`study_program_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_partners` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) NULL,
  `slug` VARCHAR(255) NOT NULL,
  `partner_code` VARCHAR(100) NULL,
  `partner_type_id` BIGINT UNSIGNED NULL,
  `country_id` BIGINT UNSIGNED NULL,
  `city` VARCHAR(150) NULL,
  `address` TEXT NULL,
  `website_url` VARCHAR(500) NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `logo` VARCHAR(500) NULL,
  `thumbnail` VARCHAR(500) NULL,
  `short_description` TEXT NULL,
  `introduction` LONGTEXT NULL,
  `cooperation_scope` LONGTEXT NULL,
  `cooperation_start_date` DATE NULL,
  `cooperation_end_date` DATE NULL,
  `relationship_status` VARCHAR(50) NOT NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_partner_type_id` (`partner_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_partner_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_school_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` BIGINT UNSIGNED NOT NULL,
  `media_type` VARCHAR(50) NOT NULL,
  `media_url` VARCHAR(500) NOT NULL,
  `thumbnail_url` VARCHAR(500) NULL,
  `title` VARCHAR(255) NULL,
  `alt_text` VARCHAR(255) NULL,
  `caption` TEXT NULL,
  `category` VARCHAR(100) NULL,
  `is_cover` BOOLEAN NOT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_school_id` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_partner_schools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `partner_id` BIGINT UNSIGNED NOT NULL,
  `school_id` BIGINT UNSIGNED NOT NULL,
  `relationship_type` VARCHAR(150) NULL,
  `cooperation_scope` TEXT NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_school_id` (`school_id`),
  UNIQUE KEY `uq_relation` (`partner_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_partner_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `partner_id` BIGINT UNSIGNED NOT NULL,
  `media_type` VARCHAR(50) NOT NULL,
  `media_url` VARCHAR(500) NOT NULL,
  `title` VARCHAR(255) NULL,
  `alt_text` VARCHAR(255) NULL,
  `caption` TEXT NULL,
  `is_cover` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  KEY `idx_partner_id` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_intake_milestones` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `intake_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `milestone_date` DATE NULL,
  `display_order` INT NULL DEFAULT '0',
  KEY `idx_intake_id` (`intake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_document_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NULL,
  `code` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_fee_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `display_order` INT NULL DEFAULT '0',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_information_sources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `study_program_id` BIGINT UNSIGNED NULL,
  `work_program_id` BIGINT UNSIGNED NULL,
  `school_id` BIGINT UNSIGNED NULL,
  `source_name` VARCHAR(255) NOT NULL,
  `source_url` VARCHAR(500) NULL,
  `source_type` VARCHAR(50) NOT NULL,
  `information_type` VARCHAR(100) NULL,
  `published_date` DATE NULL,
  `verified_at` DATETIME NULL,
  `verified_by` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_study_program_id` (`study_program_id`),
  KEY `idx_work_program_id` (`work_program_id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_verified_by` (`verified_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_courses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `course_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `course_code` VARCHAR(50) NULL,
  `course_level_id` BIGINT UNSIGNED NULL,
  `short_description` VARCHAR(500) NOT NULL,
  `description` LONGTEXT NULL,
  `learning_objectives` LONGTEXT NULL,
  `target_audience` LONGTEXT NULL,
  `course_content` LONGTEXT NULL,
  `learning_method` VARCHAR(100) NULL,
  `duration` VARCHAR(100) NULL,
  `total_sessions` INT NULL,
  `hours_per_session` DECIMAL(5,2) NULL,
  `thumbnail` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `entry_requirement` TEXT NULL,
  `outcome` LONGTEXT NULL,
  `syllabus` LONGTEXT NULL,
  `class_size` INT NULL,
  `program_id` BIGINT UNSIGNED NULL,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_course_level_id` (`course_level_id`),
  KEY `idx_program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_levels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `level_name` VARCHAR(150) NOT NULL,
  `level_code` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `level_order` INT NOT NULL DEFAULT '0',
  `short_description` VARCHAR(500) NULL,
  `description` LONGTEXT NULL,
  `learning_outcomes` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_roadmaps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `roadmap_name` VARCHAR(255) NOT NULL,
  `short_description` VARCHAR(500) NULL,
  `description` LONGTEXT NULL,
  `total_duration` VARCHAR(100) NULL,
  `learning_outcome` LONGTEXT NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_roadmap_steps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `roadmap_id` BIGINT UNSIGNED NOT NULL,
  `step_number` INT NOT NULL,
  `step_name` VARCHAR(255) NOT NULL,
  `short_description` VARCHAR(500) NULL,
  `learning_content` LONGTEXT NULL,
  `learning_objectives` LONGTEXT NULL,
  `expected_duration` VARCHAR(100) NULL,
  `expected_result` LONGTEXT NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_roadmap_id` (`roadmap_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_teachers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `teacher_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `teacher_code` VARCHAR(50) NULL,
  `avatar` VARCHAR(500) NULL,
  `cover_image` VARCHAR(500) NULL,
  `job_title` VARCHAR(150) NULL,
  `specialization` VARCHAR(255) NULL,
  `education` LONGTEXT NULL,
  `certificates` LONGTEXT NULL,
  `experience_years` INT NULL,
  `introduction` LONGTEXT NULL,
  `teaching_method` LONGTEXT NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_teachers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `teacher_id` BIGINT UNSIGNED NOT NULL,
  `teaching_role` VARCHAR(100) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_course_id` (`course_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  UNIQUE KEY `uq_relation` (`course_id`,`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_intakes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `intake_name` VARCHAR(255) NOT NULL,
  `intake_code` VARCHAR(50) NULL,
  `registration_open_date` DATE NULL,
  `registration_deadline` DATE NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `class_size` INT NULL,
  `available_slots` INT NULL,
  `learning_method` VARCHAR(100) NULL,
  `class_location` VARCHAR(255) NULL,
  `schedule_description` VARCHAR(500) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'upcoming',
  `note` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_fees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `intake_id` BIGINT UNSIGNED NULL,
  `fee_name` VARCHAR(255) NOT NULL,
  `fee_type` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `currency_code` VARCHAR(10) NOT NULL,
  `payment_type` VARCHAR(50) NULL,
  `payment_method` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `is_mandatory` BOOLEAN NOT NULL DEFAULT '0',
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_course_id` (`course_id`),
  KEY `idx_intake_id` (`intake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_faqs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `question` VARCHAR(500) NOT NULL,
  `answer` LONGTEXT NOT NULL,
  `category` VARCHAR(100) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `intake_id` BIGINT UNSIGNED NOT NULL,
  `weekday` TINYINT NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `classroom` VARCHAR(100) NULL,
  `learning_method` VARCHAR(100) NULL,
  `note` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_intake_id` (`intake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `course_id` BIGINT UNSIGNED NOT NULL,
  `media_type` VARCHAR(20) NOT NULL,
  `media_url` VARCHAR(500) NOT NULL,
  `thumbnail_url` VARCHAR(500) NULL,
  `title` VARCHAR(255) NULL,
  `alt_text` VARCHAR(255) NULL,
  `caption` TEXT NULL,
  `is_cover` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `location_code` VARCHAR(50) NULL,
  `location_type` VARCHAR(50) NOT NULL,
  `short_description` VARCHAR(500) NULL,
  `introduction` LONGTEXT NULL,
  `main_image` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `opening_date` DATE NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `is_main_office` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_location_addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `address_type` VARCHAR(50) NOT NULL,
  `address_line` VARCHAR(255) NOT NULL,
  `ward` VARCHAR(150) NULL,
  `district` VARCHAR(150) NULL,
  `province` VARCHAR(150) NOT NULL,
  `country` VARCHAR(150) NOT NULL,
  `postal_code` VARCHAR(20) NULL,
  `full_address` VARCHAR(500) NULL,
  `is_primary` BOOLEAN NOT NULL DEFAULT '0',
  `effective_from` DATE NULL,
  `effective_to` DATE NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_location_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `contact_type` VARCHAR(50) NOT NULL,
  `contact_label` VARCHAR(150) NULL,
  `contact_value` VARCHAR(500) NOT NULL,
  `contact_url` VARCHAR(500) NULL,
  `is_primary` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_location_coordinates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `latitude` DECIMAL(10,7) NOT NULL,
  `longitude` DECIMAL(10,7) NOT NULL,
  `map_provider` VARCHAR(50) NULL,
  `map_url` VARCHAR(1000) NULL,
  `embed_url` VARCHAR(1000) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_location_media` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `media_type` VARCHAR(20) NOT NULL,
  `media_url` VARCHAR(500) NOT NULL,
  `thumbnail_url` VARCHAR(500) NULL,
  `title` VARCHAR(255) NULL,
  `alt_text` VARCHAR(255) NULL,
  `caption` TEXT NULL,
  `media_category` VARCHAR(100) NULL,
  `is_cover` BOOLEAN NOT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_location_services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `service_name` VARCHAR(255) NOT NULL,
  `service_code` VARCHAR(50) NULL,
  `slug` VARCHAR(255) NULL,
  `short_description` VARCHAR(500) NULL,
  `description` LONGTEXT NULL,
  `icon` VARCHAR(255) NULL,
  `thumbnail` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_location_id` (`location_id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_location_hours` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT NOT NULL,
  `open_time` TIME NULL,
  `close_time` TIME NULL,
  `is_closed` BOOLEAN NOT NULL DEFAULT '0',
  `note` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_location_service_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `is_available` BOOLEAN NOT NULL DEFAULT '0',
  `note` TEXT NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  KEY `idx_location_id` (`location_id`),
  KEY `idx_service_id` (`service_id`),
  UNIQUE KEY `uq_relation` (`location_id`,`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_location_facilities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `location_id` BIGINT UNSIGNED NOT NULL,
  `facility_name` VARCHAR(255) NOT NULL,
  `facility_type` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(255) NULL,
  `image_url` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `post_code` VARCHAR(50) NULL,
  `excerpt` VARCHAR(1000) NULL,
  `content` LONGTEXT NOT NULL,
  `featured_image` VARCHAR(500) NULL,
  `thumbnail_image` VARCHAR(500) NULL,
  `author_id` BIGINT UNSIGNED NULL,
  `post_type` VARCHAR(50) NOT NULL,
  `source_name` VARCHAR(255) NULL,
  `source_url` VARCHAR(1000) NULL,
  `reading_time` INT NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `is_pinned` BOOLEAN NOT NULL DEFAULT '0',
  `allow_comments` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `scheduled_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_post_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `category_code` VARCHAR(50) NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `short_description` VARCHAR(500) NULL,
  `description` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_post_tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tag_name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `description` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_post_category_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `is_primary` BOOLEAN NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_post_id` (`post_id`),
  KEY `idx_category_id` (`category_id`),
  UNIQUE KEY `uq_relation` (`post_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_post_tag_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `tag_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_post_id` (`post_id`),
  KEY `idx_tag_id` (`tag_id`),
  UNIQUE KEY `uq_relation` (`post_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_authors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `author_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(500) NULL,
  `job_title` VARCHAR(150) NULL,
  `short_bio` VARCHAR(500) NULL,
  `biography` LONGTEXT NULL,
  `expertise` VARCHAR(500) NULL,
  `website_url` VARCHAR(500) NULL,
  `social_links` JSON NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `event_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `event_code` VARCHAR(50) NULL,
  `short_description` VARCHAR(1000) NULL,
  `description` LONGTEXT NULL,
  `event_type` VARCHAR(100) NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `venue_name` VARCHAR(255) NULL,
  `address` VARCHAR(500) NULL,
  `start_datetime` DATETIME NULL,
  `end_datetime` DATETIME NULL,
  `registration_url` VARCHAR(1000) NULL,
  `featured_image` VARCHAR(500) NULL,
  `organizer` VARCHAR(255) NULL,
  `event_status` VARCHAR(30) NOT NULL,
  `publication_status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_media_galleries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `gallery_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `short_description` VARCHAR(500) NULL,
  `description` LONGTEXT NULL,
  `cover_image` VARCHAR(500) NULL,
  `event_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `gallery_date` DATE NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_media_gallery_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `gallery_id` BIGINT UNSIGNED NOT NULL,
  `media_type` VARCHAR(20) NOT NULL,
  `media_url` VARCHAR(500) NOT NULL,
  `thumbnail_url` VARCHAR(500) NULL,
  `title` VARCHAR(255) NULL,
  `alt_text` VARCHAR(255) NULL,
  `caption` TEXT NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_gallery_id` (`gallery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_videos` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `video_type` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `provider` VARCHAR(50) NOT NULL,
  `video_url` VARCHAR(500) NOT NULL,
  `embed_url` VARCHAR(500) NULL,
  `thumbnail` VARCHAR(500) NULL,
  `duration` INT NULL,
  `event_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `interviewee_name` VARCHAR(255) NULL,
  `interviewee_role` VARCHAR(100) NULL,
  `student_id` BIGINT NULL,
  `program_id` BIGINT UNSIGNED NULL,
  `country_id` BIGINT NULL,
  `school_id` BIGINT UNSIGNED NULL,
  `transcript` LONGTEXT NULL,
  `consent_confirmed` BOOLEAN NOT NULL DEFAULT '0',
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_school_id` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_pages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `page_code` VARCHAR(100) NULL,
  `short_description` VARCHAR(500) NULL,
  `content` LONGTEXT NULL,
  `template_type` VARCHAR(100) NULL,
  `featured_image` VARCHAR(500) NULL,
  `banner_image` VARCHAR(500) NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `show_in_menu` BOOLEAN NOT NULL DEFAULT '0',
  `show_in_footer` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_post_revisions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `revision_number` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `excerpt` VARCHAR(1000) NULL,
  `changed_by` BIGINT UNSIGNED NULL,
  `change_note` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_post_id` (`post_id`),
  KEY `idx_changed_by` (`changed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_page_revisions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_id` BIGINT UNSIGNED NOT NULL,
  `revision_number` INT NOT NULL,
  `page_title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `changed_by` BIGINT UNSIGNED NULL,
  `change_note` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_page_id` (`page_id`),
  KEY `idx_changed_by` (`changed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_consultation_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `request_code` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `email` VARCHAR(255) NULL,
  `birth_year` SMALLINT NULL,
  `province` VARCHAR(150) NULL,
  `country_id` BIGINT NULL,
  `program_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `lead_source_id` BIGINT UNSIGNED NULL,
  `message` LONGTEXT NULL,
  `preferred_contact_method` VARCHAR(30) NULL,
  `preferred_contact_time` VARCHAR(100) NULL,
  `privacy_consent` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `assigned_to` BIGINT UNSIGNED NULL,
  `submitted_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `legacy_lead_id` BIGINT UNSIGNED NULL,
  `consent_at` DATETIME NULL,
  `source_page` VARCHAR(1000) NULL,
  UNIQUE KEY `uq_request_code` (`request_code`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_lead_source_id` (`lead_source_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  UNIQUE KEY `uq_legacy_lead_id` (`legacy_lead_id`),
  KEY `idx_legacy_lead_id` (`legacy_lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_consultation_interests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `interest_name` VARCHAR(255) NOT NULL,
  `interest_code` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_consultation_request_interests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `consultation_request_id` BIGINT UNSIGNED NOT NULL,
  `interest_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_consultation_request_id` (`consultation_request_id`),
  KEY `idx_interest_id` (`interest_id`),
  UNIQUE KEY `uq_relation` (`consultation_request_id`,`interest_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_contact_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `message_code` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(32) NULL,
  `email` VARCHAR(255) NULL,
  `subject` VARCHAR(255) NULL,
  `message` LONGTEXT NOT NULL,
  `lead_source_id` BIGINT UNSIGNED NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `assigned_to` BIGINT UNSIGNED NULL,
  `submitted_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `legacy_lead_id` BIGINT UNSIGNED NULL,
  `privacy_consent` BOOLEAN NOT NULL DEFAULT '0',
  `consent_at` DATETIME NULL,
  `source_page` VARCHAR(1000) NULL,
  UNIQUE KEY `uq_message_code` (`message_code`),
  KEY `idx_lead_source_id` (`lead_source_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  UNIQUE KEY `uq_legacy_lead_id` (`legacy_lead_id`),
  KEY `idx_legacy_lead_id` (`legacy_lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_course_registrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `registration_code` VARCHAR(50) NOT NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `intake_id` BIGINT UNSIGNED NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `email` VARCHAR(255) NULL,
  `birth_year` SMALLINT NULL,
  `current_level` VARCHAR(100) NULL,
  `learning_method` VARCHAR(50) NULL,
  `lead_source_id` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `privacy_consent` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `submitted_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `legacy_lead_id` BIGINT UNSIGNED NULL,
  `consent_at` DATETIME NULL,
  `source_page` VARCHAR(1000) NULL,
  UNIQUE KEY `uq_registration_code` (`registration_code`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_intake_id` (`intake_id`),
  KEY `idx_lead_source_id` (`lead_source_id`),
  UNIQUE KEY `uq_legacy_lead_id` (`legacy_lead_id`),
  KEY `idx_legacy_lead_id` (`legacy_lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_event_registrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `registration_code` VARCHAR(50) NOT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `email` VARCHAR(255) NULL,
  `organization` VARCHAR(255) NULL,
  `participant_count` INT NOT NULL,
  `lead_source_id` BIGINT UNSIGNED NULL,
  `note` TEXT NULL,
  `privacy_consent` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(30) NOT NULL DEFAULT 'new',
  `submitted_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `consent_at` DATETIME NULL,
  `source_page` VARCHAR(1000) NULL,
  UNIQUE KEY `uq_registration_code` (`registration_code`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_lead_source_id` (`lead_source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_lead_sources` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `source_name` VARCHAR(150) NOT NULL,
  `source_code` VARCHAR(50) NOT NULL,
  `source_group` VARCHAR(100) NULL,
  `description` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_form_submissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `submission_code` VARCHAR(50) NOT NULL,
  `form_type` VARCHAR(50) NOT NULL,
  `form_record_id` BIGINT NOT NULL,
  `form_name` VARCHAR(150) NULL,
  `source_page` VARCHAR(1000) NULL,
  `lead_source_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `submitted_at` DATETIME NOT NULL,
  `submission_status` VARCHAR(30) NOT NULL,
  `error_message` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `legacy_lead_id` BIGINT UNSIGNED NULL,
  `payload_json` JSON NULL,
  UNIQUE KEY `uq_submission_code` (`submission_code`),
  KEY `idx_lead_source_id` (`lead_source_id`),
  UNIQUE KEY `uq_legacy_lead_id` (`legacy_lead_id`),
  KEY `idx_legacy_lead_id` (`legacy_lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_consultation_activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `consultation_request_id` BIGINT UNSIGNED NOT NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `activity_content` LONGTEXT NOT NULL,
  `old_status` VARCHAR(30) NULL,
  `new_status` VARCHAR(30) NULL,
  `performed_by` BIGINT UNSIGNED NULL,
  `next_follow_up_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_consultation_request_id` (`consultation_request_id`),
  KEY `idx_performed_by` (`performed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_registration_statuses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `status_code` VARCHAR(50) NOT NULL,
  `status_name` VARCHAR(100) NOT NULL,
  `applicable_form_type` VARCHAR(50) NULL,
  `description` VARCHAR(500) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_faqs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `question` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(550) NOT NULL,
  `answer` LONGTEXT NOT NULL,
  `short_answer` TEXT NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `faq_type` VARCHAR(50) NOT NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_faq_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `code` VARCHAR(100) NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(255) NULL,
  `thumbnail` VARCHAR(500) NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_program_faqs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `faq_id` BIGINT UNSIGNED NOT NULL,
  `program_id` BIGINT UNSIGNED NOT NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_faq_id` (`faq_id`),
  KEY `idx_program_id` (`program_id`),
  UNIQUE KEY `uq_relation` (`faq_id`,`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_policies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `policy_type` VARCHAR(50) NOT NULL,
  `summary` TEXT NULL,
  `content` LONGTEXT NOT NULL,
  `version` VARCHAR(50) NOT NULL,
  `effective_date` DATE NOT NULL,
  `expiry_date` DATE NULL,
  `is_current` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_testimonials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `student_name` VARCHAR(255) NOT NULL,
  `student_code` VARCHAR(100) NULL,
  `student_avatar` VARCHAR(500) NULL,
  `review_title` VARCHAR(255) NULL,
  `review_content` LONGTEXT NOT NULL,
  `review_type` VARCHAR(50) NOT NULL,
  `program_id` BIGINT UNSIGNED NULL,
  `course_id` BIGINT UNSIGNED NULL,
  `country_id` BIGINT NULL,
  `rating` TINYINT NULL,
  `review_date` DATE NULL,
  `source` VARCHAR(100) NULL,
  `source_url` VARCHAR(500) NULL,
  `is_verified` BOOLEAN NOT NULL DEFAULT '0',
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  `consent_confirmed` BOOLEAN NOT NULL DEFAULT '0',
  KEY `idx_program_id` (`program_id`),
  KEY `idx_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_success_stories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `student_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `story_title` VARCHAR(500) NOT NULL,
  `short_description` TEXT NULL,
  `story_content` LONGTEXT NOT NULL,
  `student_avatar` VARCHAR(500) NULL,
  `featured_image` VARCHAR(500) NULL,
  `program_id` BIGINT UNSIGNED NULL,
  `country_id` BIGINT NULL,
  `school_id` BIGINT UNSIGNED NULL,
  `achievement_title` VARCHAR(255) NULL,
  `achievement_description` TEXT NULL,
  `departure_date` DATE NULL,
  `milestone_data` JSON NULL,
  `gallery_data` JSON NULL,
  `video_id` BIGINT UNSIGNED NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  `consent_confirmed` BOOLEAN NOT NULL DEFAULT '0',
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `activity_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `activity_code` VARCHAR(100) NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `short_description` TEXT NULL,
  `description` LONGTEXT NULL,
  `featured_image` VARCHAR(500) NULL,
  `location_id` BIGINT UNSIGNED NULL,
  `school_id` BIGINT UNSIGNED NULL,
  `event_id` BIGINT UNSIGNED NULL,
  `program_id` BIGINT UNSIGNED NULL,
  `activity_date` DATE NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `venue` VARCHAR(255) NULL,
  `organizer` VARCHAR(255) NULL,
  `participant_count` INT NULL,
  `gallery_id` BIGINT UNSIGNED NULL,
  `video_id` BIGINT UNSIGNED NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_school_id` (`school_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_gallery_id` (`gallery_id`),
  KEY `idx_video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_seo_metadata` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` BIGINT NOT NULL,
  `meta_title` VARCHAR(255) NULL,
  `meta_description` VARCHAR(500) NULL,
  `meta_keywords` TEXT NULL,
  `canonical_url` VARCHAR(1000) NULL,
  `robots_index` BOOLEAN NOT NULL DEFAULT '1',
  `robots_follow` BOOLEAN NOT NULL DEFAULT '1',
  `og_title` VARCHAR(255) NULL,
  `og_description` VARCHAR(500) NULL,
  `og_image` VARCHAR(500) NULL,
  `schema_type` VARCHAR(100) NULL,
  `schema_json` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_relation` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_redirects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `source_url` VARCHAR(500) NOT NULL,
  `target_url` VARCHAR(1000) NOT NULL,
  `redirect_type` SMALLINT NOT NULL,
  `redirect_reason` VARCHAR(100) NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT '0',
  `hit_count` BIGINT NOT NULL DEFAULT '0',
  `last_hit_at` DATETIME NULL,
  `note` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_source_url` (`source_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_menus` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `menu_name` VARCHAR(255) NOT NULL,
  `menu_code` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `location` VARCHAR(50) NOT NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_menu_code` (`menu_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_menu_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `menu_id` BIGINT UNSIGNED NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `label` VARCHAR(255) NOT NULL,
  `link_type` VARCHAR(50) NOT NULL,
  `link_url` VARCHAR(1000) NULL,
  `entity_type` VARCHAR(50) NULL,
  `entity_id` BIGINT NULL,
  `icon` VARCHAR(255) NULL,
  `open_new_tab` BOOLEAN NOT NULL DEFAULT '0',
  `css_class` VARCHAR(255) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_menu_id` (`menu_id`),
  KEY `idx_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_banners` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `banner_name` VARCHAR(255) NOT NULL,
  `banner_code` VARCHAR(100) NULL,
  `title` VARCHAR(255) NULL,
  `subtitle` VARCHAR(500) NULL,
  `description` TEXT NULL,
  `image_desktop` VARCHAR(500) NOT NULL,
  `image_tablet` VARCHAR(500) NULL,
  `image_mobile` VARCHAR(500) NULL,
  `image_alt` VARCHAR(255) NULL,
  `button_text` VARCHAR(100) NULL,
  `button_url` VARCHAR(1000) NULL,
  `position` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NULL,
  `entity_id` BIGINT NULL,
  `start_at` DATETIME NULL,
  `end_at` DATETIME NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `is_active` BOOLEAN NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_site_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `setting_group` VARCHAR(100) NOT NULL,
  `setting_key` VARCHAR(150) NOT NULL,
  `setting_value` LONGTEXT NULL,
  `value_type` VARCHAR(30) NOT NULL,
  `label` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `is_public` BOOLEAN NOT NULL DEFAULT '0',
  `display_order` INT NOT NULL DEFAULT '0',
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_setting_key` (`setting_key`),
  KEY `idx_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_media_files` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(1000) NOT NULL,
  `file_url` VARCHAR(1000) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_extension` VARCHAR(20) NULL,
  `file_size` BIGINT NOT NULL,
  `width` INT NULL,
  `height` INT NULL,
  `alt_text` VARCHAR(500) NULL,
  `caption` TEXT NULL,
  `title` VARCHAR(255) NULL,
  `folder_path` VARCHAR(500) NULL,
  `uploaded_by` BIGINT UNSIGNED NULL,
  `is_public` BOOLEAN NOT NULL DEFAULT '0',
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kipa_media_usages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `media_file_id` BIGINT UNSIGNED NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` BIGINT NOT NULL,
  `usage_type` VARCHAR(50) NULL,
  `display_order` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_media_file_id` (`media_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `kipa_study_abroad_programs` ADD CONSTRAINT `fk_9975652b099b1b8c34bb86d7` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_study_program_details` ADD CONSTRAINT `fk_839e4319256bfd2ea3ba153f` FOREIGN KEY (`study_abroad_id`) REFERENCES `kipa_study_abroad_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_study_program_details` ADD CONSTRAINT `fk_1496448bf65e42a28fa695b1` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_work_programs` ADD CONSTRAINT `fk_8d77da844bc0bafa068e8a8c` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_partnership_programs` ADD CONSTRAINT `fk_1234e49f6b0ac6a140b3f811` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_intakes` ADD CONSTRAINT `fk_b9296d58676b2f0446186923` FOREIGN KEY (`study_program_id`) REFERENCES `kipa_study_program_details` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_intakes` ADD CONSTRAINT `fk_17233d14dbe270aac5d9ca71` FOREIGN KEY (`work_program_id`) REFERENCES `kipa_work_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_intakes` ADD CONSTRAINT `fk_17d7155dde95997f360f752d` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_intakes` ADD CONSTRAINT `fk_420372cd22b734cbad9ca257` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_requirements` ADD CONSTRAINT `fk_7d00c44728fee5032998aae6` FOREIGN KEY (`study_program_id`) REFERENCES `kipa_study_program_details` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_requirements` ADD CONSTRAINT `fk_9f14741d7b7b9965e9151db4` FOREIGN KEY (`work_program_id`) REFERENCES `kipa_work_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_requirements` ADD CONSTRAINT `fk_61a6cb8f42cdf004009d7e16` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_fees` ADD CONSTRAINT `fk_67b19bb72a340c6a02fe05d4` FOREIGN KEY (`study_program_id`) REFERENCES `kipa_study_program_details` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_fees` ADD CONSTRAINT `fk_8782f23e2f4344cd39fb5f80` FOREIGN KEY (`work_program_id`) REFERENCES `kipa_work_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_fees` ADD CONSTRAINT `fk_37835ac97c89671cfca1045e` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_fees` ADD CONSTRAINT `fk_6a4a58d540480ddce782a3cf` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_documents` ADD CONSTRAINT `fk_68f31b80a46a6ba0cb071f3b` FOREIGN KEY (`study_program_id`) REFERENCES `kipa_study_program_details` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_documents` ADD CONSTRAINT `fk_32662e3530016c356b2919d9` FOREIGN KEY (`work_program_id`) REFERENCES `kipa_work_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_documents` ADD CONSTRAINT `fk_aac5ed3cbb7af36836a332ff` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_process_steps` ADD CONSTRAINT `fk_a7d1d2394f3cff9b61e0b47f` FOREIGN KEY (`study_program_id`) REFERENCES `kipa_study_program_details` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_process_steps` ADD CONSTRAINT `fk_01f067c3306fb8d9ce30f148` FOREIGN KEY (`work_program_id`) REFERENCES `kipa_work_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_process_steps` ADD CONSTRAINT `fk_7366ffa858cbe8a64c618133` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_media` ADD CONSTRAINT `fk_f6f7880f4724566c1468987b` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_media` ADD CONSTRAINT `fk_02bf70a3b0224456651cb470` FOREIGN KEY (`study_program_id`) REFERENCES `kipa_study_program_details` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_work_job_positions` ADD CONSTRAINT `fk_df96727937fde745a48b1332` FOREIGN KEY (`work_program_id`) REFERENCES `kipa_work_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_work_program_costs` ADD CONSTRAINT `fk_3119ca57aea57bee3d517dfb` FOREIGN KEY (`work_program_id`) REFERENCES `kipa_work_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_study_fields` ADD CONSTRAINT `fk_f3412583d50fca4e9fe68c42` FOREIGN KEY (`parent_id`) REFERENCES `kipa_study_fields` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_school_programs` ADD CONSTRAINT `fk_f3825167ffa63824e621d04f` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_school_programs` ADD CONSTRAINT `fk_97cd176221b6e5d0228bbf56` FOREIGN KEY (`study_field_id`) REFERENCES `kipa_study_fields` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_school_programs` ADD CONSTRAINT `fk_966e45f7747e189dd9256b3b` FOREIGN KEY (`study_program_detail_id`) REFERENCES `kipa_study_program_details` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_partners` ADD CONSTRAINT `fk_c853c22e1973d3094c147292` FOREIGN KEY (`partner_type_id`) REFERENCES `kipa_partner_types` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_school_media` ADD CONSTRAINT `fk_b81cd07bbe22e02374823886` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_partner_schools` ADD CONSTRAINT `fk_7a40fa282048cd220abf3b63` FOREIGN KEY (`partner_id`) REFERENCES `kipa_partners` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_partner_schools` ADD CONSTRAINT `fk_db3f3c75f72d279085a55db1` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_partner_media` ADD CONSTRAINT `fk_84088e8c1ed2c5bba308b1f3` FOREIGN KEY (`partner_id`) REFERENCES `kipa_partners` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_intake_milestones` ADD CONSTRAINT `fk_7ef16be74c74f89cfff434d9` FOREIGN KEY (`intake_id`) REFERENCES `kipa_program_intakes` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_information_sources` ADD CONSTRAINT `fk_d542e1424b599814bbfa7196` FOREIGN KEY (`study_program_id`) REFERENCES `kipa_study_program_details` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_information_sources` ADD CONSTRAINT `fk_a8617898bd86e5136fef0018` FOREIGN KEY (`work_program_id`) REFERENCES `kipa_work_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_information_sources` ADD CONSTRAINT `fk_de9dff53bae29a8675045e34` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_information_sources` ADD CONSTRAINT `fk_d1f72a814a194b678744fca8` FOREIGN KEY (`verified_by`) REFERENCES `kipa_admins` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_courses` ADD CONSTRAINT `fk_8c1cd104072ce2e759cff629` FOREIGN KEY (`course_level_id`) REFERENCES `kipa_course_levels` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_courses` ADD CONSTRAINT `fk_5668ad01c7260c255b6d8d3e` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_roadmaps` ADD CONSTRAINT `fk_104eb398e16eae087ea3de5e` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_roadmap_steps` ADD CONSTRAINT `fk_2246262ecee518c04bf03c58` FOREIGN KEY (`roadmap_id`) REFERENCES `kipa_course_roadmaps` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_teachers` ADD CONSTRAINT `fk_43f1b2aa6b3752b4f1b7ab18` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_teachers` ADD CONSTRAINT `fk_f54dc3244f399ce1da9487fb` FOREIGN KEY (`teacher_id`) REFERENCES `kipa_teachers` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_intakes` ADD CONSTRAINT `fk_ef18818270a9f793f43cb804` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_fees` ADD CONSTRAINT `fk_73e5b8e401f98ceee23a8eba` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_fees` ADD CONSTRAINT `fk_21469f5c6b7246e0dcb839b5` FOREIGN KEY (`intake_id`) REFERENCES `kipa_course_intakes` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_faqs` ADD CONSTRAINT `fk_72357d09afdfb50905422b06` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_schedules` ADD CONSTRAINT `fk_c0b8b36f94084de5a9dd3683` FOREIGN KEY (`intake_id`) REFERENCES `kipa_course_intakes` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_media` ADD CONSTRAINT `fk_e7f14be90068c9b268caac60` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_addresses` ADD CONSTRAINT `fk_2b702a832a9a16a173963d22` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_contacts` ADD CONSTRAINT `fk_80d9d995315c57a4aa9ac2fe` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_coordinates` ADD CONSTRAINT `fk_dfa0154236a3e845821d8fb2` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_media` ADD CONSTRAINT `fk_e07561b127abe10d26cb7079` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_services` ADD CONSTRAINT `fk_a29d4827caf1e3c31973267c` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_hours` ADD CONSTRAINT `fk_0525a88e99f062c78898bdda` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_service_assignments` ADD CONSTRAINT `fk_040a8d4a07b3433ec8561bc8` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_service_assignments` ADD CONSTRAINT `fk_1643be788ee3713fbc399598` FOREIGN KEY (`service_id`) REFERENCES `kipa_location_services` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_location_facilities` ADD CONSTRAINT `fk_d5e27fcb364f9d518a8f0254` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_posts` ADD CONSTRAINT `fk_d518bea2c55a1c2adfd5aab0` FOREIGN KEY (`author_id`) REFERENCES `kipa_authors` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_post_categories` ADD CONSTRAINT `fk_d816ea7a8c3655fa8bb0459c` FOREIGN KEY (`parent_id`) REFERENCES `kipa_post_categories` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_post_category_assignments` ADD CONSTRAINT `fk_ea9a6b7540e38927d3156f77` FOREIGN KEY (`post_id`) REFERENCES `kipa_posts` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_post_category_assignments` ADD CONSTRAINT `fk_39621359ca222ec8dcc302f0` FOREIGN KEY (`category_id`) REFERENCES `kipa_post_categories` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_post_tag_assignments` ADD CONSTRAINT `fk_fb0a051af68146262ae05953` FOREIGN KEY (`post_id`) REFERENCES `kipa_posts` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_post_tag_assignments` ADD CONSTRAINT `fk_40f52a43dcd07aaba373e3aa` FOREIGN KEY (`tag_id`) REFERENCES `kipa_post_tags` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_events` ADD CONSTRAINT `fk_8e9ea7e79f68a5bfaabfcb15` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_media_galleries` ADD CONSTRAINT `fk_310306392054c6ea424023b9` FOREIGN KEY (`event_id`) REFERENCES `kipa_events` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_media_galleries` ADD CONSTRAINT `fk_a7f298b3312e825ba0db5b8a` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_media_gallery_items` ADD CONSTRAINT `fk_5065133c7c007f4e66240edc` FOREIGN KEY (`gallery_id`) REFERENCES `kipa_media_galleries` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_videos` ADD CONSTRAINT `fk_f8fd36223fb92745af77fbb2` FOREIGN KEY (`event_id`) REFERENCES `kipa_events` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_videos` ADD CONSTRAINT `fk_247e519fbc4df6f0dcb90d29` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_videos` ADD CONSTRAINT `fk_a08040cc9aa2dbb7183aaf15` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_videos` ADD CONSTRAINT `fk_0139b0635c37fe567765366c` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_pages` ADD CONSTRAINT `fk_b67c30ba68ced6dd192e06e0` FOREIGN KEY (`parent_id`) REFERENCES `kipa_pages` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_post_revisions` ADD CONSTRAINT `fk_547b40954098b874df07b44c` FOREIGN KEY (`post_id`) REFERENCES `kipa_posts` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_post_revisions` ADD CONSTRAINT `fk_01b11791c8613227b0d8caad` FOREIGN KEY (`changed_by`) REFERENCES `kipa_admins` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_page_revisions` ADD CONSTRAINT `fk_83b1263f9c1c4090975a46d3` FOREIGN KEY (`page_id`) REFERENCES `kipa_pages` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_page_revisions` ADD CONSTRAINT `fk_6169749112ee6371468fd829` FOREIGN KEY (`changed_by`) REFERENCES `kipa_admins` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_consultation_requests` ADD CONSTRAINT `fk_69afb7d12034844dfca3993b` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_consultation_requests` ADD CONSTRAINT `fk_918c9ad3debfd6a11fd37915` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_consultation_requests` ADD CONSTRAINT `fk_4f24ee05187ed3acdeb3b39c` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_consultation_requests` ADD CONSTRAINT `fk_330ab23e0649e9e23dcd20bc` FOREIGN KEY (`lead_source_id`) REFERENCES `kipa_lead_sources` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_consultation_requests` ADD CONSTRAINT `fk_d64154cb9b6174025dd51904` FOREIGN KEY (`assigned_to`) REFERENCES `kipa_admins` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_consultation_requests` ADD CONSTRAINT `fk_11a8705a81915a7af279cc42` FOREIGN KEY (`legacy_lead_id`) REFERENCES `kipa_leads` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_consultation_request_interests` ADD CONSTRAINT `fk_733e0cc6e30562a7ca63b46a` FOREIGN KEY (`consultation_request_id`) REFERENCES `kipa_consultation_requests` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_consultation_request_interests` ADD CONSTRAINT `fk_de5e2141b97e9066d0ee48bf` FOREIGN KEY (`interest_id`) REFERENCES `kipa_consultation_interests` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_contact_messages` ADD CONSTRAINT `fk_5f8f1aff409fbf9d6dee825f` FOREIGN KEY (`lead_source_id`) REFERENCES `kipa_lead_sources` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_contact_messages` ADD CONSTRAINT `fk_0e37471bfb778ffa168ed289` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_contact_messages` ADD CONSTRAINT `fk_bac820689591647449f043be` FOREIGN KEY (`assigned_to`) REFERENCES `kipa_admins` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_contact_messages` ADD CONSTRAINT `fk_6f5f9fe8a5a271426992d999` FOREIGN KEY (`legacy_lead_id`) REFERENCES `kipa_leads` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_course_registrations` ADD CONSTRAINT `fk_341865f74bf94cbc8eb90dcf` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_registrations` ADD CONSTRAINT `fk_668ce7e5c2b137d81e5d78d3` FOREIGN KEY (`intake_id`) REFERENCES `kipa_course_intakes` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_registrations` ADD CONSTRAINT `fk_c22e96fa17d86d21d1c43d05` FOREIGN KEY (`lead_source_id`) REFERENCES `kipa_lead_sources` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_course_registrations` ADD CONSTRAINT `fk_4b95da7c23560f5d5e790898` FOREIGN KEY (`legacy_lead_id`) REFERENCES `kipa_leads` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_event_registrations` ADD CONSTRAINT `fk_85d210f71c393ff77839d947` FOREIGN KEY (`event_id`) REFERENCES `kipa_events` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_event_registrations` ADD CONSTRAINT `fk_d1da559afb8a57165da33334` FOREIGN KEY (`lead_source_id`) REFERENCES `kipa_lead_sources` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_form_submissions` ADD CONSTRAINT `fk_faaf93a4d426ebb9b61484f6` FOREIGN KEY (`lead_source_id`) REFERENCES `kipa_lead_sources` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_form_submissions` ADD CONSTRAINT `fk_e0a28f58e61e8d0e6e9571b9` FOREIGN KEY (`legacy_lead_id`) REFERENCES `kipa_leads` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_consultation_activities` ADD CONSTRAINT `fk_3c834021f41b96a7fdfc8876` FOREIGN KEY (`consultation_request_id`) REFERENCES `kipa_consultation_requests` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_consultation_activities` ADD CONSTRAINT `fk_1275338cfdb99990794b59fe` FOREIGN KEY (`performed_by`) REFERENCES `kipa_admins` (`id`) ON DELETE CASCADE;

ALTER TABLE `kipa_faqs` ADD CONSTRAINT `fk_b55b216d32fcfa25685742b2` FOREIGN KEY (`category_id`) REFERENCES `kipa_faq_categories` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_faq_categories` ADD CONSTRAINT `fk_585b3edc9d5641fc36e70cd3` FOREIGN KEY (`parent_id`) REFERENCES `kipa_faq_categories` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_faqs` ADD CONSTRAINT `fk_48c8101ecf5e0ebefb81ceb0` FOREIGN KEY (`faq_id`) REFERENCES `kipa_faqs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_program_faqs` ADD CONSTRAINT `fk_0e95fbf5161208559809630c` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_testimonials` ADD CONSTRAINT `fk_586e484f799568df67ef1911` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_testimonials` ADD CONSTRAINT `fk_71afc4e4ae6a8729142ab5c8` FOREIGN KEY (`course_id`) REFERENCES `kipa_courses` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_success_stories` ADD CONSTRAINT `fk_04ce8c02f6039aaf7256ea2c` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_success_stories` ADD CONSTRAINT `fk_4f171416e4fabe637443f63e` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_success_stories` ADD CONSTRAINT `fk_a7e6f14447f2139adf2ca6d2` FOREIGN KEY (`video_id`) REFERENCES `kipa_videos` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_activities` ADD CONSTRAINT `fk_7d90e095849e8b928c3230d8` FOREIGN KEY (`location_id`) REFERENCES `kipa_locations` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_activities` ADD CONSTRAINT `fk_a0597fd1809c06ccdcac3900` FOREIGN KEY (`school_id`) REFERENCES `kipa_schools` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_activities` ADD CONSTRAINT `fk_c90efe10884da222c2e1aa70` FOREIGN KEY (`event_id`) REFERENCES `kipa_events` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_activities` ADD CONSTRAINT `fk_f5dcaabb43f936999cf6eb2c` FOREIGN KEY (`program_id`) REFERENCES `kipa_programs` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_activities` ADD CONSTRAINT `fk_7d66eb9308c7202733f31b7f` FOREIGN KEY (`gallery_id`) REFERENCES `kipa_media_galleries` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_activities` ADD CONSTRAINT `fk_b480ec5585bb1fdfdfb59aaf` FOREIGN KEY (`video_id`) REFERENCES `kipa_videos` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_menu_items` ADD CONSTRAINT `fk_abdfb619b9c6b22f2204bbc7` FOREIGN KEY (`menu_id`) REFERENCES `kipa_menus` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_menu_items` ADD CONSTRAINT `fk_af4ab24ee10817dbdb7b9d1f` FOREIGN KEY (`parent_id`) REFERENCES `kipa_menu_items` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_site_settings` ADD CONSTRAINT `fk_08ae4c0475aac036d035e232` FOREIGN KEY (`updated_by`) REFERENCES `kipa_admins` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_media_files` ADD CONSTRAINT `fk_a6a1208c6912c33ac6163009` FOREIGN KEY (`uploaded_by`) REFERENCES `kipa_admins` (`id`) ON DELETE RESTRICT;

ALTER TABLE `kipa_media_usages` ADD CONSTRAINT `fk_26e02b8414bf8a351bb3c282` FOREIGN KEY (`media_file_id`) REFERENCES `kipa_media_files` (`id`) ON DELETE RESTRICT;
