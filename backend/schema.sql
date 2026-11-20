CREATE TABLE IF NOT EXISTS kipa_leads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    form_type VARCHAR(32) NOT NULL,
    contact_name VARCHAR(254) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(254) NULL,
    program VARCHAR(254) NULL,
    source_page VARCHAR(64) NOT NULL,
    details_json JSON NOT NULL,
    consented_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_kipa_leads_created_at (created_at),
    KEY idx_kipa_leads_form_type (form_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scope VARCHAR(16) NOT NULL,
    remote_key CHAR(64) NOT NULL,
    occurred_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_kipa_rate_scope_remote_time (scope, remote_key, occurred_at),
    KEY idx_kipa_rate_time (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_otp_codes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    code_hash CHAR(64) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_kipa_otp_email_created (email, created_at),
    KEY idx_kipa_otp_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(160) NOT NULL,
    phone VARCHAR(32) NULL,
    email_verified_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kipa_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    full_name VARCHAR(160) NOT NULL DEFAULT '',
    email VARCHAR(191) NULL DEFAULT NULL,
    phone VARCHAR(32) NULL DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    department VARCHAR(64) NOT NULL DEFAULT 'Ban Giám Đốc',
    role VARCHAR(64) NOT NULL DEFAULT 'super_admin',
    scope VARCHAR(255) NOT NULL DEFAULT 'Toàn hệ thống',
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    must_change_password BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kipa_admin_username (username),
    UNIQUE KEY uq_kipa_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- KIPA STUDENT PORTAL TABLES
-- ========================================================
CREATE TABLE IF NOT EXISTS kipa_students (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    full_name VARCHAR(160) NOT NULL,
    avatar_url VARCHAR(255) DEFAULT '/assets/home/avatar-hoc-sinh.jpg',
    birthday DATE NULL,
    gender VARCHAR(16) DEFAULT 'Nữ',
    phone VARCHAR(32) NULL,
    address VARCHAR(255) NULL,
    education_level VARCHAR(128) DEFAULT 'Tốt nghiệp THPT',
    school_name VARCHAR(160) DEFAULT 'THPT Lê Quý Đôn',
    emergency_contact VARCHAR(255) NULL,
    student_status VARCHAR(64) DEFAULT 'Đang học',
    current_program VARCHAR(160) DEFAULT 'Du học Hàn Quốc (D2-2)',
    program_code VARCHAR(64) DEFAULT 'KR-D2-2026',
    country VARCHAR(64) DEFAULT 'Hàn Quốc',
    registration_date VARCHAR(32) DEFAULT '15/09/2026',
    target_departure VARCHAR(64) DEFAULT 'Tháng 09/2027',
    dossier_status VARCHAR(64) DEFAULT 'Đang chuẩn bị',
    progress_percent INT UNSIGNED DEFAULT 72,
    advisor_name VARCHAR(160) DEFAULT 'Cô Trần Thu Hà',
    advisor_role VARCHAR(128) DEFAULT 'Cố vấn học tập KIPA',
    advisor_phone VARCHAR(32) DEFAULT '036 851 3865',
    advisor_email VARCHAR(254) DEFAULT 'duhocquoctekipaviethan@gmail.com',
    advisor_avatar VARCHAR(255) DEFAULT '/assets/doi-ngu/ban-quan-tri-thuy-anh.jpg',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kipa_students_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_student_journey (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(254) NOT NULL,
    step_number INT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    status VARCHAR(64) NOT NULL,
    progress_percent INT UNSIGNED DEFAULT 0,
    description TEXT NULL,
    deadline VARCHAR(64) NULL,
    in_charge VARCHAR(160) NULL,
    tasks_json JSON NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kipa_student_journey_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_student_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(254) NOT NULL,
    doc_group VARCHAR(64) NOT NULL,
    doc_name VARCHAR(160) NOT NULL,
    status VARCHAR(64) NOT NULL,
    file_url VARCHAR(255) DEFAULT '',
    file_name VARCHAR(160) DEFAULT '',
    file_size VARCHAR(32) DEFAULT '',
    uploaded_at VARCHAR(32) DEFAULT '',
    note TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kipa_student_documents_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_student_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(254) NOT NULL,
    title VARCHAR(160) NOT NULL,
    schedule_type VARCHAR(64) NOT NULL,
    day_label VARCHAR(64) NOT NULL,
    time_range VARCHAR(64) NOT NULL,
    location VARCHAR(255) NOT NULL,
    mentor VARCHAR(160) NOT NULL,
    status VARCHAR(64) DEFAULT 'Sắp diễn ra',
    PRIMARY KEY (id),
    KEY idx_kipa_student_schedules_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_student_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(254) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(64) NOT NULL,
    time_label VARCHAR(64) NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT 0,
    is_urgent BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kipa_student_notifications_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_student_materials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(64) NOT NULL,
    format VARCHAR(16) DEFAULT 'PDF',
    file_url VARCHAR(255) NOT NULL,
    file_size VARCHAR(32) DEFAULT '',
    description TEXT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kipa_student_support (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_email VARCHAR(254) NOT NULL,
    ticket_code VARCHAR(32) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    category VARCHAR(64) NOT NULL,
    status VARCHAR(64) DEFAULT 'Đang xử lý',
    advisor_reply TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kipa_student_support_email (student_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
