-- =====================================================================
-- KIPA VIỆT HÀN — BỘ DDL VÀ DỮ LIỆU BỔ SUNG CỔNG THÔNG TIN HỌC VIÊN
-- File: backend/student_portal_cpanel_import.sql
-- Mục đích: Nhập trực tiếp vào phpMyAdmin trên cPanel (https://kipaviethan.edu.vn:2083/)
-- Phạm vi: Bổ sung các bảng cần thiết cho Cổng thông tin học viên (Trang Hồ Sơ)
--
-- NGUYÊN TẮC AN TOÀN TUYỆT ĐỐI:
-- 1. Sử dụng CREATE TABLE IF NOT EXISTS -> KHÔNG làm mất hoặc ghi đè dữ liệu cũ.
-- 2. KHÔNG chạm vào 5 bảng Website hiện hữu: kipa_leads, kipa_users, kipa_admins, kipa_otp_codes, kipa_rate_limits.
-- 3. Sử dụng INSERT IGNORE -> Có thể chạy lại nhiều lần mà không bị lỗi trùng lặp (Idempotent).
-- 4. Tắt kiểm tra khóa ngoại trước khi chạy và bật lại sau khi nạp xong.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ---------------------------------------------------------------------
-- 1. BẢNG CHI NHÁNH KIPA (kipa_branches)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_branches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_code` VARCHAR(32) NOT NULL,
  `branch_name` VARCHAR(160) NOT NULL,
  `address` VARCHAR(255) DEFAULT '',
  `hotline` VARCHAR(32) DEFAULT '0368513865',
  `email` VARCHAR(254) DEFAULT 'duhocquoctekipaviethan@gmail.com',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_branches_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi nhánh văn phòng KIPA';

-- ---------------------------------------------------------------------
-- 2. BẢNG PHÒNG BAN (kipa_departments)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_departments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id` BIGINT UNSIGNED NULL,
  `department_code` VARCHAR(32) NOT NULL,
  `department_name` VARCHAR(160) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_dept_code` (`department_code`),
  CONSTRAINT `fk_kipa_dept_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. BẢNG NHÂN SỰ & CỐ VẤN HỌC TẬP (kipa_staff)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_staff` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id` BIGINT UNSIGNED NULL,
  `department_id` BIGINT UNSIGNED NULL,
  `staff_code` VARCHAR(32) NOT NULL,
  `full_name` VARCHAR(160) NOT NULL,
  `role_title` VARCHAR(128) NOT NULL DEFAULT 'Cố vấn học tập',
  `email` VARCHAR(254) NOT NULL,
  `phone` VARCHAR(32) DEFAULT '',
  `avatar_url` VARCHAR(255) DEFAULT '',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_staff_code` (`staff_code`),
  KEY `idx_kipa_staff_branch` (`branch_id`),
  CONSTRAINT `fk_kipa_staff_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. BẢNG KHÁCH HÀNG CRM (kipa_customers)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_customers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_code` VARCHAR(32) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL COMMENT 'Liên kết tài khoản kipa_users',
  `full_name` VARCHAR(160) NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `email` VARCHAR(254) DEFAULT NULL,
  `id_card_number` VARCHAR(32) DEFAULT NULL,
  `gender` ENUM('MALE', 'FEMALE', 'OTHER') NOT NULL DEFAULT 'OTHER',
  `date_of_birth` DATE DEFAULT NULL,
  `current_address` VARCHAR(255) DEFAULT '',
  `branch_id` BIGINT UNSIGNED NULL,
  `customer_status` ENUM('LEAD', 'POTENTIAL', 'STUDYING', 'ALUMNI', 'MERGED', 'SUSPENDED') NOT NULL DEFAULT 'STUDYING',
  `merged_into_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_cust_code` (`customer_code`),
  KEY `idx_kipa_cust_phone` (`phone`),
  KEY `idx_kipa_cust_email` (`email`),
  KEY `idx_kipa_cust_user` (`user_id`),
  CONSTRAINT `fk_kipa_cust_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. BẢNG HỒ SƠ DỊCH VỤ / DU HỌC (kipa_service_dossiers)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_service_dossiers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dossier_code` VARCHAR(32) NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED NOT NULL,
  `service_type` VARCHAR(64) NOT NULL DEFAULT 'DU_HOC_HAN_QUOC',
  `program_name` VARCHAR(160) NOT NULL DEFAULT 'Du học Hàn Quốc (D2-2)',
  `program_code` VARCHAR(32) NOT NULL DEFAULT 'KR-D2-2026',
  `target_country` VARCHAR(64) NOT NULL DEFAULT 'Hàn Quốc',
  `target_departure_date` VARCHAR(32) DEFAULT 'Tháng 09/2027',
  `progress_percent` INT UNSIGNED NOT NULL DEFAULT 72,
  `status` ENUM('DRAFT', 'PREPARING', 'TRANSLATING', 'SUBMITTED_SCHOOL', 'VISA_INTERVIEW', 'VISA_ISSUED', 'DEPARTED', 'COMPLETED', 'SUSPENDED', 'CANCELLED') NOT NULL DEFAULT 'PREPARING',
  `lead_staff_id` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_sd_code` (`dossier_code`),
  KEY `idx_kipa_sd_customer` (`customer_id`),
  KEY `idx_kipa_sd_branch` (`branch_id`),
  CONSTRAINT `fk_kipa_sd_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_kipa_sd_branch` FOREIGN KEY (`branch_id`) REFERENCES `kipa_branches` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_kipa_sd_staff` FOREIGN KEY (`lead_staff_id`) REFERENCES `kipa_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. BẢNG DANH MỤC TÀI LIỆU HỒ SƠ HỌC VIÊN (kipa_dossier_documents)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_dossier_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dossier_id` BIGINT UNSIGNED NOT NULL,
  `document_code` VARCHAR(32) NOT NULL,
  `document_name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(64) NOT NULL DEFAULT 'Hồ sơ pháp lý',
  `is_required` TINYINT(1) NOT NULL DEFAULT 1,
  `verification_status` ENUM('NOT_SUBMITTED', 'PENDING_REVIEW', 'APPROVED', 'REJECTED', 'NEEDS_SUPPLEMENT') NOT NULL DEFAULT 'PENDING_REVIEW',
  `file_url` VARCHAR(255) DEFAULT '',
  `file_name` VARCHAR(255) DEFAULT '',
  `submitted_at` DATETIME DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_dd_code` (`document_code`),
  KEY `idx_kipa_dd_dossier` (`dossier_id`),
  CONSTRAINT `fk_kipa_dd_dossier` FOREIGN KEY (`dossier_id`) REFERENCES `kipa_service_dossiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. BẢNG HỒ SƠ HỌC VIÊN STUDENT PORTAL (kipa_students)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_students` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(254) NOT NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `full_name` VARCHAR(160) NOT NULL,
  `avatar_url` VARCHAR(255) DEFAULT '/assets/home/avatar-hoc-sinh.jpg',
  `birthday` VARCHAR(32) DEFAULT '2006-05-18',
  `gender` VARCHAR(16) DEFAULT 'Nữ',
  `phone` VARCHAR(32) DEFAULT '0987 654 321',
  `address` VARCHAR(255) DEFAULT 'Phường Quang Trung, TP. Quy Nhơn, Bình Định',
  `education_level` VARCHAR(64) DEFAULT 'Tốt nghiệp THPT',
  `school_name` VARCHAR(160) DEFAULT 'THPT Chuyên Lê Quý Đôn',
  `emergency_contact` VARCHAR(255) DEFAULT 'Bố: Nguyễn Văn Dũng (0912 345 678)',
  `student_status` VARCHAR(64) DEFAULT 'Đang học',
  `current_program` VARCHAR(160) DEFAULT 'Du học Hàn Quốc (D2-2)',
  `program_code` VARCHAR(32) DEFAULT 'KR-D2-2026',
  `country` VARCHAR(64) DEFAULT 'Hàn Quốc',
  `registration_date` VARCHAR(32) DEFAULT '15/09/2026',
  `target_departure` VARCHAR(32) DEFAULT 'Tháng 09/2027',
  `dossier_status` VARCHAR(64) DEFAULT 'Đang chuẩn bị',
  `progress_percent` INT DEFAULT 72,
  `advisor_name` VARCHAR(160) DEFAULT 'Cô Trần Thu Hà',
  `advisor_role` VARCHAR(128) DEFAULT 'Cố vấn học tập KIPA',
  `advisor_phone` VARCHAR(32) DEFAULT '036 851 3865',
  `advisor_email` VARCHAR(254) DEFAULT 'duhocquoctekipaviethan@gmail.com',
  `advisor_avatar` VARCHAR(255) DEFAULT '/assets/doi-ngu/ban-quan-tri-thuy-anh.jpg',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_students_email` (`email`),
  KEY `idx_kipa_students_cust` (`customer_id`),
  CONSTRAINT `fk_kipa_students_customer` FOREIGN KEY (`customer_id`) REFERENCES `kipa_customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Hồ sơ hiển thị trực tiếp trên Student Portal';

-- ---------------------------------------------------------------------
-- 8. BẢNG LỘ TRÌNH 5 BƯỚC HỌC TẬP (kipa_student_journey)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_student_journey` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_email` VARCHAR(254) NOT NULL,
  `dossier_id` BIGINT UNSIGNED NULL,
  `step_number` INT UNSIGNED NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'Đang xử lý',
  `progress_percent` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `deadline` VARCHAR(64) DEFAULT '',
  `in_charge` VARCHAR(160) DEFAULT '',
  `tasks_json` JSON NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kipa_sj_email` (`student_email`),
  KEY `idx_kipa_sj_dossier` (`dossier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lộ trình học tập cá nhân & checklist';

-- ---------------------------------------------------------------------
-- 9. BẢNG LỊCH HẸN & LỊCH HỌC HỌC VIÊN (kipa_student_schedules)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_student_schedules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_email` VARCHAR(254) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `schedule_type` VARCHAR(64) NOT NULL DEFAULT 'Lịch hẹn',
  `day_label` VARCHAR(32) NOT NULL,
  `time_range` VARCHAR(64) NOT NULL,
  `location` VARCHAR(160) NOT NULL,
  `mentor` VARCHAR(160) NOT NULL,
  `status` VARCHAR(64) DEFAULT 'Sắp tới',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kipa_ss_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch học và lịch hẹn cố vấn';

-- ---------------------------------------------------------------------
-- 10. BẢNG THÔNG BÁO HỌC VIÊN (kipa_student_notifications)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_student_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_email` VARCHAR(254) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `content` TEXT NOT NULL,
  `category` VARCHAR(64) NOT NULL DEFAULT 'Hồ sơ',
  `time_label` VARCHAR(32) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `is_urgent` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kipa_sn_email` (`student_email`),
  KEY `idx_kipa_sn_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Trung tâm thông báo Cổng học viên';

-- ---------------------------------------------------------------------
-- 11. BẢNG HỎI ĐÁP & HỖ TRỢ HỌC VIÊN (kipa_student_support)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_student_support` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_email` VARCHAR(254) NOT NULL,
  `ticket_code` VARCHAR(32) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `category` VARCHAR(64) NOT NULL DEFAULT 'Hồ sơ giấy tờ',
  `status` VARCHAR(64) DEFAULT 'Đang xử lý',
  `advisor_reply` TEXT NULL,
  `replied_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kipa_sup_code` (`ticket_code`),
  KEY `idx_kipa_sup_email` (`student_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ticket hỗ trợ học viên 2 chiều';

-- ---------------------------------------------------------------------
-- 12. BẢNG TÀI LIỆU HỌC TẬP & CẨM NANG (kipa_student_materials)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kipa_student_materials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(64) NOT NULL DEFAULT 'Giáo trình',
  `format` VARCHAR(16) NOT NULL DEFAULT 'PDF',
  `file_url` VARCHAR(255) NOT NULL,
  `file_size` VARCHAR(32) NOT NULL DEFAULT '2.5 MB',
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- NẠP DỮ LIỆU BAN ĐẦU (SEED DATA - AN TOÀN VỚI INSERT IGNORE)
-- =====================================================================

-- 1. Thêm Chi nhánh KIPA
INSERT IGNORE INTO `kipa_branches` (`id`, `branch_code`, `branch_name`, `address`, `hotline`, `email`, `is_active`) VALUES
(1, 'HN01', 'Trụ Sở Chính Hà Nội', 'Tầng 5, Tòa nhà KIPA, Cầu Giấy, Hà Nội', '0368513865', 'duhocquoctekipaviethan@gmail.com', 1),
(2, 'QN01', 'Chi Nhánh Quy Nhơn - Bình Định', '186 Ngô Gia Tự, TX. An Nhơn, Bình Định', '0368513865', 'duhocquoctekipaviethan@gmail.com', 1),
(3, 'HCM01', 'Chi Nhánh TP. Hồ Chí Minh', '93 Calmette, P. Bến Thành, Quận 1, TP.HCM', '0368513865', 'duhocquoctekipaviethan@gmail.com', 1);

-- 2. Thêm Nhân sự & Cố vấn KIPA
INSERT IGNORE INTO `kipa_staff` (`id`, `branch_id`, `staff_code`, `full_name`, `role_title`, `email`, `phone`, `avatar_url`, `is_active`) VALUES
(1, 1, 'ST-001', 'Cô Trần Thu Hà', 'Cố vấn học tập KIPA', 'duhocquoctekipaviethan@gmail.com', '0368513865', '/assets/doi-ngu/ban-quan-tri-thuy-anh.jpg', 1),
(2, 2, 'ST-002', 'Cô Vũ Thúy Anh', 'Chuyên viên Xử lý Hồ sơ', 'thuyanh.kipa@gmail.com', '0368513865', '/assets/doi-ngu/ban-quan-tri-thuy-anh.jpg', 1);

-- 3. Khách hàng đại diện
INSERT IGNORE INTO `kipa_customers` (`id`, `customer_code`, `user_id`, `full_name`, `phone`, `email`, `gender`, `date_of_birth`, `current_address`, `branch_id`, `customer_status`) VALUES
(1, 'KH-2026-000001', NULL, 'Nguyễn Thị Mai Anh', '0987654321', 'maianh.kipa@gmail.com', 'FEMALE', '2006-05-18', 'Phường Quang Trung, TP. Quy Nhơn, Bình Định', 2, 'STUDYING');

-- 4. Hồ sơ dịch vụ du học
INSERT IGNORE INTO `kipa_service_dossiers` (`id`, `dossier_code`, `customer_id`, `branch_id`, `service_type`, `program_name`, `program_code`, `target_country`, `target_departure_date`, `progress_percent`, `status`, `lead_staff_id`) VALUES
(1, 'HSDV-2026-00001', 1, 2, 'DU_HOC_HAN_QUOC', 'Du học Hàn Quốc (D2-2)', 'KR-D2-2026', 'Hàn Quốc', 'Tháng 09/2027', 72, 'PREPARING', 1);

-- 5. Hồ sơ học viên hiển thị trên Student Portal
INSERT IGNORE INTO `kipa_students` (
  `id`, `email`, `customer_id`, `full_name`, `avatar_url`, `birthday`, `gender`, `phone`, `address`,
  `education_level`, `school_name`, `emergency_contact`, `student_status`, `current_program`, `program_code`,
  `country`, `registration_date`, `target_departure`, `dossier_status`, `progress_percent`,
  `advisor_name`, `advisor_role`, `advisor_phone`, `advisor_email`, `advisor_avatar`
) VALUES (
  1, 'maianh.kipa@gmail.com', 1, 'Nguyễn Thị Mai Anh', '/assets/home/avatar-hoc-sinh.jpg', '2006-05-18', 'Nữ',
  '0987 654 321', 'Phường Quang Trung, TP. Quy Nhơn, Bình Định', 'Tốt nghiệp THPT', 'THPT Chuyên Lê Quý Đôn',
  'Bố: Nguyễn Văn Dũng (0912 345 678)', 'Đang học', 'Du học Hàn Quốc (D2-2)', 'KR-D2-2026', 'Hàn Quốc',
  '15/09/2026', 'Tháng 09/2027', 'Đang chuẩn bị', 72, 'Cô Trần Thu Hà', 'Cố vấn học tập KIPA',
  '036 851 3865', 'duhocquoctekipaviethan@gmail.com', '/assets/doi-ngu/ban-quan-tri-thuy-anh.jpg'
);

-- 6. 5 Mốc Lộ Trình Học Tập (Journey)
INSERT IGNORE INTO `kipa_student_journey` (`id`, `student_email`, `dossier_id`, `step_number`, `title`, `status`, `progress_percent`, `description`, `deadline`, `in_charge`, `tasks_json`) VALUES
(1, 'maianh.kipa@gmail.com', 1, 1, 'Tư vấn & định hướng nguyện vọng', 'Hoàn thành', 100, 'Tìm hiểu nguyện vọng, định hướng chọn trường đại học và lộ trình phù hợp với hồ sơ học viên.', 'Đã hoàn thành', 'Cô Trần Thu Hà', '["Khảo sát năng lực học tập và mục tiêu du học", "Chọn 3 trường đại học mục tiêu tại Seoul", "Ký kết hợp đồng đào tạo & cam kết dịch vụ"]'),
(2, 'maianh.kipa@gmail.com', 1, 2, 'Học tiếng Hàn & Luyện thi TOPIK', 'Hoàn thành', 100, 'Đào tạo tiếng Hàn từ cơ bản đến nâng cao, cam kết đạt TOPIK 2 hoặc TOPIK 3 trước khi xuất cảnh.', 'Đã hoàn thành', 'Thầy Park Min Woo', '["Hoàn thành khóa học Tiếng Hàn sơ cấp 1 & 2", "Thi đỗ chứng chỉ TOPIK 2 (165/200 điểm)", "Luyện giao tiếp phản xạ phỏng vấn visa"]'),
(3, 'maianh.kipa@gmail.com', 1, 3, 'Chuẩn bị & hoàn thiện hồ sơ dịch thuật', 'Đang xử lý', 75, 'Thu thập giấy tờ cá nhân, học bạ, bằng tốt nghiệp, chứng minh tài chính và dịch thuật công chứng tư pháp.', 'Trước 15/01/2027', 'Cô Vũ Thúy Anh', '["Thu thập công chứng học bạ & bằng tốt nghiệp THPT", "Mở sổ tiết kiệm đóng băng Kookmin Bank 10.000 USD", "Viết bản kế hoạch học tập & thư giới thiệu bản thân", "Hợp pháp hóa lãnh sự Bộ Ngoại Giao"]'),
(4, 'maianh.kipa@gmail.com', 1, 4, 'Phỏng vấn trường Đại học & Xin Visa', 'Chưa bắt đầu', 0, 'Nộp hồ sơ xin mã code/ thư mời nhập học từ trường Hàn Quốc và tiến hành nộp visa tại KVAC.', 'Dự kiến 04/2027', 'Cô Trần Thu Hà', '["Nhận thư mời nhập học (Standard Admission Letter)", "Nộp hồ sơ xin Visa D2-2 tại Trung tâm KVAC", "Nhận kết quả Visa du học Hàn Quốc"]'),
(5, 'maianh.kipa@gmail.com', 1, 5, 'Xuất cảnh & Nhập học tại Hàn Quốc', 'Chưa bắt đầu', 0, 'KIPA hướng dẫn chuẩn bị hành lý, vé máy bay, đón tại sân bay Incheon và ổn định ký túc xá.', 'Dự kiến 09/2027', 'KIPA Văn phòng Seoul', '["Tập huấn tiền xuất cảnh & kỹ năng sinh tồn tại Hàn", "Đặt vé máy bay & bảo hiểm du học sinh quốc tế", "Đón tại sân bay Incheon và nhận phòng ký túc xá"]');

-- 7. Danh mục Hồ sơ & Giấy tờ (Documents)
INSERT IGNORE INTO `kipa_dossier_documents` (`id`, `dossier_id`, `document_code`, `document_name`, `category`, `is_required`, `verification_status`, `file_name`, `file_url`, `submitted_at`, `verified_at`, `notes`) VALUES
(1, 1, 'DOC-01', 'Hộ chiếu còn hạn trên 12 tháng', 'Hồ sơ cá nhân', 1, 'APPROVED', 'ho_chieu_nguyen_thi_mai_anh.pdf', '/kipa-private/vault/documents/ho_chieu_mai_anh.pdf', '2026-10-01 10:00:00', '2026-10-02 14:00:00', 'Hộ chiếu còn hạn đến năm 2035, hợp lệ 100%.'),
(2, 1, 'DOC-02', 'Căn cước công dân gắn chip (Bản dịch công chứng)', 'Hồ sơ cá nhân', 1, 'APPROVED', 'cccd_dich_thuat_cong_chung.pdf', '/kipa-private/vault/documents/cccd_mai_anh.pdf', '2026-10-01 10:00:00', '2026-10-02 14:00:00', 'Đã chứng thực bản dịch tư pháp.'),
(3, 1, 'DOC-03', 'Học bạ THPT (3 năm)', 'Hồ sơ học tập', 1, 'APPROVED', 'hoc_ba_thpt_chuyen_le_quy_don.pdf', '/kipa-private/vault/documents/hoc_ba_mai_anh.pdf', '2026-10-05 09:30:00', '2026-10-06 11:00:00', 'Điểm trung bình 3 năm: 8.6, đạt điều kiện nộp trường top 1.'),
(4, 1, 'DOC-04', 'Bằng tốt nghiệp THPT tạm thời / chính thức', 'Hồ sơ học tập', 1, 'APPROVED', 'bang_tot_nghiep_thpt.pdf', '/kipa-private/vault/documents/bang_mai_anh.pdf', '2026-10-05 09:30:00', '2026-10-06 11:00:00', 'Đã hợp pháp hóa lãnh sự.'),
(5, 1, 'DOC-05', 'Sổ tiết kiệm ngân hàng (Chứng minh tài chính 10.000 USD)', 'Tài chính', 1, 'PENDING_REVIEW', 'so_tiet_kiem_kb_bank.pdf', '/kipa-private/vault/documents/so_tiet_kiem_mai_anh.pdf', '2026-11-10 15:00:00', NULL, 'Chuyên viên đang đối soát với ngân hàng đối tác.'),
(6, 1, 'DOC-06', 'Giấy khám sức khỏe lao phổi (Bệnh viện chỉ định)', 'Sức khỏe', 1, 'NOT_SUBMITTED', '', '', NULL, NULL, 'Cần khám tại Bệnh viện Phổi hoặc Hồng Ngọc trước 15/12/2026.'),
(7, 1, 'DOC-07', 'Chứng chỉ tiếng Hàn TOPIK 2', 'Năng lực ngoại ngữ', 1, 'APPROVED', 'chung_chi_topik_2.pdf', '/kipa-private/vault/documents/topik_mai_anh.pdf', '2026-10-20 08:00:00', '2026-10-21 10:00:00', 'Điểm 165/200, đạt tiêu chuẩn.');

-- 8. Lịch hẹn & Lịch học
INSERT IGNORE INTO `kipa_student_schedules` (`id`, `student_email`, `title`, `schedule_type`, `day_label`, `time_range`, `location`, `mentor`, `status`) VALUES
(1, 'maianh.kipa@gmail.com', 'Phỏng vấn thử tiếng Hàn cùng giáo viên bản xứ', 'Luyện phỏng vấn', 'Thứ 7, 28/11/2026', '14:00 - 15:30', 'Phòng Hội Thảo 3 - Trụ sở KIPA Hà Nội (hoặc Zoom)', 'Thầy Park Min Woo', 'Sắp tới'),
(2, 'maianh.kipa@gmail.com', 'Nộp bổ sung bản gốc sổ tiết kiệm đóng băng', 'Hồ sơ', 'Thứ 4, 02/12/2026', '09:00 - 11:30', 'Văn phòng KIPA Quy Nhơn (186 Ngô Gia Tự)', 'Cô Vũ Thúy Anh', 'Sắp tới'),
(3, 'maianh.kipa@gmail.com', 'Lễ trao chứng chỉ TOPIK & Định hướng giai đoạn 2', 'Sự kiện', 'Chủ nhật, 15/10/2026', '08:30 - 11:00', 'Hội trường KIPA Việt Hàn', 'Ban Giám Đốc KIPA', 'Đã hoàn thành');

-- 9. Thông báo học viên
INSERT IGNORE INTO `kipa_student_notifications` (`id`, `student_email`, `title`, `content`, `category`, `time_label`, `is_read`, `is_urgent`) VALUES
(1, 'maianh.kipa@gmail.com', 'Nhắc nhở: Hoàn thiện thủ tục sổ tiết kiệm đóng băng trước ngày 05/12', 'Mai Anh vui lòng phối hợp cùng gia đình hoàn tất xác nhận số dư tài khoản ngân hàng để kịp hạn nộp hồ sơ xin thư mời trường ĐH Kookmin.', 'Hồ sơ', '2 giờ trước', 0, 1),
(2, 'maianh.kipa@gmail.com', 'Chúc mừng bạn đã đạt 165 điểm kỳ thi thử TOPIK 2 tháng 11', 'Kết quả thi thử của bạn đã đạt điều kiện nộp hồ sơ diện D2-2. Tiếp tục duy trì phong độ cho buổi phỏng vấn cùng trường nhé!', 'Học tập', '2 ngày trước', 1, 0);

-- 10. Hỏi đáp & Hỗ trợ học viên
INSERT IGNORE INTO `kipa_student_support` (`id`, `student_email`, `ticket_code`, `subject`, `message`, `category`, `status`, `advisor_reply`, `replied_at`) VALUES
(1, 'maianh.kipa@gmail.com', 'TK-2026-0001', 'Em muốn hỏi về quy trình mở sổ tiết kiệm đóng băng KB Bank ạ?', 'Chào cô Hà, em muốn hỏi mở sổ tiết kiệm đóng băng 10.000 USD thì bố mẹ em cần mang theo những giấy tờ gì ra ngân hàng ạ?', 'Hồ sơ giấy tờ', 'Đã phản hồi', 'Chào Mai Anh, em và phụ huynh mang theo: CCCD của bố mẹ, CCCD của em, Hộ chiếu của em và Hợp đồng dịch vụ KIPA ra chi nhánh KB Bank gần nhất nhé. Cô đã gửi mẫu giấy ủy quyền qua Zalo cho mẹ em rồi nhé!', '2026-11-12 16:30:00');

-- 11. Tài liệu học tập
INSERT IGNORE INTO `kipa_student_materials` (`id`, `title`, `category`, `format`, `file_url`, `file_size`, `description`) VALUES
(1, 'Giáo trình Tiếng Hàn Tổng Hợp Sơ Cấp 1 & 2 (PDF + Audio)', 'Giáo trình', 'PDF', '/kipa-private/materials/giao_trinh_tieng_han_tong_hop.pdf', '45.2 MB', 'Bộ giáo trình chuẩn đào tạo du học sinh KIPA Việt Hàn.'),
(2, 'Cẩm nang 100 câu hỏi phỏng vấn Visa Du học Hàn Quốc thường gặp', 'Visa', 'PDF', '/kipa-private/materials/cam_nang_phong_van_visa.pdf', '8.5 MB', 'Tổng hợp câu hỏi phỏng vấn Đại sứ quán và cách trả lời chuẩn văn phong Hàn Quốc.');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- KẾT THÚC BẢN MIGRATION — HOÀN TẤT THIẾT LẬP CỔNG HỌC VIÊN
-- =====================================================================
