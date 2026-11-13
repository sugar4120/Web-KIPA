<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if ($argc !== 2 || !is_dir($argv[1] . '/api')) {
    fwrite(STDERR, "Usage: php backend/account_tables.php /absolute/path/to/public_html\nCreates the kipa_otp_codes and kipa_users tables used by api/otp.php and api/register.php. Existing data is never changed.\n");
    exit(2);
}

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/../api/bootstrap.php';
$_SERVER['DOCUMENT_ROOT'] = realpath($argv[1]);

try {
    $db = kipa_db(kipa_config());
    $db->exec('CREATE TABLE IF NOT EXISTS kipa_otp_codes (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $db->exec('CREATE TABLE IF NOT EXISTS kipa_users (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(254) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(160) NOT NULL,
        phone VARCHAR(32) NULL,
        email_verified_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_kipa_user_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    fwrite(STDOUT, "Account table migration completed; kipa_otp_codes and kipa_users are ready.\n");
} catch (PDOException $error) {
    fwrite(STDERR, 'Account table migration failed (database error ' . $error->getCode() . ").\n");
    exit(1);
} catch (RuntimeException $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
