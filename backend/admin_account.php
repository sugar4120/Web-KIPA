<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if ($argc !== 3 || !in_array($argv[1], ['--migrate-only', '--create', '--reset'], true) || !is_dir($argv[2] . '/api')) {
    fwrite(STDERR, "Usage: php backend/admin_account.php --migrate-only|--create|--reset /absolute/path/to/public_html\nFor --create or --reset, provide the password on standard input.\n");
    exit(2);
}

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/../api/bootstrap.php';
$_SERVER['DOCUMENT_ROOT'] = realpath($argv[2]);

try {
    $db = kipa_db(kipa_config());
    $column = $db->query("SHOW COLUMNS FROM kipa_admins LIKE 'must_change_password'")->fetch();
    if (!$column) {
        $db->exec('ALTER TABLE kipa_admins ADD COLUMN must_change_password BOOLEAN NOT NULL DEFAULT 0');
    }
    $emailColumn = $db->query("SHOW COLUMNS FROM kipa_admins LIKE 'email'")->fetch();
    if (!$emailColumn) {
        $db->exec('ALTER TABLE kipa_admins ADD COLUMN email VARCHAR(191) NULL DEFAULT NULL AFTER username');
    }
    if ($argv[1] === '--migrate-only') {
        fwrite(STDOUT, "Administrator password-change migration completed; existing passwords retained.\n");
        exit;
    }
    if ($argv[1] === '--reset') {
        $input = fgets(STDIN, 75);
        $password = $input === false ? '' : rtrim($input, "\r\n");
        if ($password === '' || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new RuntimeException('New password must contain 1–72 bytes without null bytes.');
        }
        $existing = $db->prepare('SELECT id FROM kipa_admins WHERE username = ?');
        $existing->execute(['admin']);
        $adminId = $existing->fetchColumn();
        if ($adminId) {
            $query = $db->prepare('UPDATE kipa_admins SET password_hash = ?, must_change_password = 0 WHERE id = ?');
            $query->execute([password_hash($password, PASSWORD_DEFAULT), (int)$adminId]);
            fwrite(STDOUT, "Admin account password reset successfully.\n");
        } else {
            $query = $db->prepare('INSERT INTO kipa_admins (username, password_hash, must_change_password, created_at) VALUES (?, ?, 0, UTC_TIMESTAMP())');
            $query->execute(['admin', password_hash($password, PASSWORD_DEFAULT)]);
            fwrite(STDOUT, "Admin account created with specified password.\n");
        }
        unset($password, $input);
        exit;
    }
    $existing = $db->prepare('SELECT id FROM kipa_admins WHERE username = ?');
    $existing->execute(['admin']);
    if ($existing->fetchColumn()) {
        throw new RuntimeException('The admin account already exists; no password was changed.');
    }
    $input = fgets(STDIN, 75);
    $password = $input === false ? '' : rtrim($input, "\r\n");
    if ($password === '' || strlen($password) > 72 || str_contains($password, "\0")) {
        throw new RuntimeException('Initial password must contain 1–72 bytes without null bytes.');
    }
    $query = $db->prepare('INSERT INTO kipa_admins (username, password_hash, must_change_password, created_at) VALUES (?, ?, 1, UTC_TIMESTAMP())');
    $query->execute(['admin', password_hash($password, PASSWORD_DEFAULT)]);
    unset($password, $input);
    fwrite(STDOUT, "Admin account created. Password change is required before accessing administration.\n");
} catch (PDOException $error) {
    fwrite(STDERR, "Administrator provisioning failed (database error " . $error->getCode() . ").\n");
    exit(1);
} catch (RuntimeException $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
