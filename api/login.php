<?php
declare(strict_types=1);

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/mailer.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    kipa_json_response(405, ['error' => 'Phương thức gửi không được hỗ trợ.']);
}
kipa_require_origin();

$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($contentType !== 'application/json') {
    kipa_json_response(415, ['error' => 'Định dạng yêu cầu không được hỗ trợ.']);
}
$raw = file_get_contents('php://input', false, null, 0, 2049);
if ($raw === false || strlen($raw) > 2048) {
    kipa_json_response(413, ['error' => 'Thông tin gửi lên quá dài.']);
}

try {
    $request = json_decode($raw, true, 4, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (!is_array($request) || array_diff(array_keys($request), ['action', 'email', 'password', 'website'])) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (isset($request['website']) && (!is_string($request['website']) || $request['website'] !== '')) {
    kipa_json_response(202, ['ok' => true]);
}
$action = $request['action'] ?? null;
if (!in_array($action, ['login', 'me', 'logout'], true)) {
    kipa_json_response(400, ['error' => 'Yêu cầu không hợp lệ.']);
}

function kipa_user_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('kipa_user');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

kipa_user_session();

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    setcookie('kipa_user', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
    kipa_json_response(200, ['ok' => true]);
}

try {
    $config = kipa_config();
    $db = kipa_db($config);

    if ($action === 'me') {
        $user = null;
        if (isset($_SESSION['user_id'])) {
            $query = $db->prepare('SELECT full_name, email FROM kipa_users WHERE id = :id');
            $query->execute(['id' => (int)$_SESSION['user_id']]);
            $row = $query->fetch();
            if ($row) {
                $user = ['full_name' => $row['full_name'], 'email' => $row['email']];
            } else {
                $_SESSION = [];
                session_destroy();
            }
        }
        kipa_json_response(200, ['ok' => true, 'user' => $user]);
    }

    // action === 'login'
    try {
        $email = kipa_otp_normalize_email(is_string($request['email'] ?? null) ? $request['email'] : '');
    } catch (InvalidArgumentException) {
        kipa_json_response(422, ['error' => 'Vui lòng nhập địa chỉ email hợp lệ.']);
    }
    $password = $request['password'] ?? null;
    if (!is_string($password) || $password === '' || strlen($password) > 72 || str_contains($password, "\0")) {
        kipa_json_response(422, ['error' => 'Vui lòng nhập mật khẩu.']);
    }

    $remoteKey = kipa_remote_key($config);
    $db->beginTransaction();
    if (kipa_rate_limited($db, 'login', $remoteKey, 10, 15)) {
        $db->rollBack();
        kipa_json_response(429, ['error' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau.']);
    }
    kipa_record_rate_event($db, 'login', $remoteKey);
    $db->commit();

    $query = $db->prepare('SELECT id, password_hash, full_name, email FROM kipa_users WHERE email = :email');
    $query->execute(['email' => $email]);
    $user = $query->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        kipa_json_response(401, ['error' => 'Email hoặc mật khẩu không đúng.']);
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = $db->prepare('UPDATE kipa_users SET password_hash = :hash WHERE id = :id');
        $rehash->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int)$user['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    kipa_json_response(200, ['ok' => true, 'user' => ['full_name' => $user['full_name'], 'email' => $user['email']]]);
} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-login] Database operation failed: ' . $error->getCode());
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
} catch (RuntimeException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-login] Backend configuration failed.');
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
}
