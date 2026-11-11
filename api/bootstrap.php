<?php
declare(strict_types=1);

if (!defined('KIPA_API_BOOTSTRAP')) {
    http_response_code(404);
    exit;
}

const KIPA_ALLOWED_ORIGINS = [
    'https://kipaviethan.edu.vn',
    'https://www.kipaviethan.edu.vn',
];

function kipa_config(): array
{
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($documentRoot === '') {
        $documentRoot = dirname(__DIR__);
    }
    $configPath = dirname($documentRoot) . '/kipa-private/config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Backend configuration is missing.');
    }

    $config = require $configPath;
    $required = ['db_dsn', 'db_user', 'db_password', 'app_key', 'setup_token'];
    if (!is_array($config)) {
        throw new RuntimeException('Backend configuration is invalid.');
    }
    foreach ($required as $key) {
        if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') {
            throw new RuntimeException('Backend configuration is incomplete.');
        }
    }
    if (strlen($config['app_key']) < 32 || strlen($config['setup_token']) < 32) {
        throw new RuntimeException('Backend security keys are invalid.');
    }
    return $config;
}

function kipa_db(array $config): PDO
{
    return new PDO($config['db_dsn'], $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function kipa_json_response(int $status, array $body): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function kipa_require_origin(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!in_array($origin, KIPA_ALLOWED_ORIGINS, true)) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ.');
    }
}

function kipa_remote_key(array $config): string
{
    $address = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($address === '' || filter_var($address, FILTER_VALIDATE_IP) === false) {
        $address = 'unknown';
    }
    return hash_hmac('sha256', $address, $config['app_key']);
}

function kipa_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kipa_text_length(string $value): int
{
    $matched = preg_match_all('/./us', $value, $unused);
    return $matched === false ? strlen($value) : $matched;
}

function kipa_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('kipa_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/api/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function kipa_check_csrf(): void
{
    $submitted = $_POST['csrf'] ?? '';
    if (!is_string($submitted) || !hash_equals($_SESSION['csrf'] ?? '', $submitted)) {
        http_response_code(403);
        exit('Phiên làm việc không hợp lệ. Hãy tải lại trang.');
    }
}

function kipa_current_admin(PDO $db): ?array
{
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    $query = $db->prepare('SELECT id, username, must_change_password FROM kipa_admins WHERE id = :id');
    $query->execute(['id' => (int)$_SESSION['admin_id']]);
    $admin = $query->fetch();
    if (!$admin || (!empty($_SESSION['must_change_password']) && !(bool)$admin['must_change_password'])) {
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['must_change_password']);
        return null;
    }
    return $admin;
}

function kipa_rate_limited(PDO $db, string $scope, string $remoteKey, int $limit, int $minutes): bool
{
    $cutoff = gmdate('Y-m-d H:i:s', time() - ($minutes * 60));
    $statement = $db->prepare(
        'SELECT COUNT(*) FROM kipa_rate_limits
         WHERE scope = :scope AND remote_key = :remote_key
           AND occurred_at >= :cutoff'
    );
    $statement->bindValue(':scope', $scope);
    $statement->bindValue(':remote_key', $remoteKey);
    $statement->bindValue(':cutoff', $cutoff);
    $statement->execute();
    return (int)$statement->fetchColumn() >= $limit;
}

function kipa_record_rate_event(PDO $db, string $scope, string $remoteKey): void
{
    $statement = $db->prepare(
        'INSERT INTO kipa_rate_limits (scope, remote_key, occurred_at)
         VALUES (:scope, :remote_key, UTC_TIMESTAMP())'
    );
    $statement->execute(['scope' => $scope, 'remote_key' => $remoteKey]);
}
