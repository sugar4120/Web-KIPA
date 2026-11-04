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

CREATE TABLE IF NOT EXISTS kipa_admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kipa_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
