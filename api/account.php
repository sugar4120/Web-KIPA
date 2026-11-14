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
if (!is_array($request) || array_diff(array_keys($request), ['action', 'full_name', 'phone', 'current_password', 'new_password', 'website'])) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (isset($request['website']) && (!is_string($request['website']) || $request['website'] !== '')) {
    kipa_json_response(202, ['ok' => true]);
}
$action = $request['action'] ?? null;
if (!in_array($action, ['get', 'update', 'change_password'], true)) {
    kipa_json_response(400, ['error' => 'Yêu cầu không hợp lệ.']);
}

kipa_user_session();
if (!isset($_SESSION['user_id'])) {
    kipa_json_response(401, ['error' => 'Bạn cần đăng nhập để xem hồ sơ.']);
}

function kipa_account_view(array $user): array
{
    return [
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'created_at' => $user['created_at'],
    ];
}

try {
    $config = kipa_config();
    $db = kipa_db($config);

    $query = $db->prepare('SELECT id, email, password_hash, full_name, phone, created_at FROM kipa_users WHERE id = :id');
    $query->execute(['id' => (int)$_SESSION['user_id']]);
    $user = $query->fetch();
    if (!$user) {
        $_SESSION = [];
        session_destroy();
        kipa_json_response(401, ['error' => 'Phiên đăng nhập không còn hiệu lực. Vui lòng đăng nhập lại.']);
    }

    if ($action === 'get') {
        kipa_json_response(200, ['ok' => true, 'user' => kipa_account_view($user)]);
    }

    if ($action === 'update') {
        $fullName = is_string($request['full_name'] ?? null) ? trim($request['full_name']) : '';
        if ($fullName === '' || kipa_text_length($fullName) < 2 || kipa_text_length($fullName) > 160
            || preg_match('/[\x00-\x1F\x7F]/', $fullName)) {
            kipa_json_response(422, ['error' => 'Vui lòng nhập họ và tên hợp lệ.']);
        }
        $phone = is_string($request['phone'] ?? null) ? trim($request['phone']) : '';
        if ($phone !== '') {
            $digits = preg_replace('/\D+/', '', $phone);
            if (strlen($phone) > 32 || $digits === null || strlen($digits) < 7 || strlen($digits) > 15 || !preg_match('/^\+?[\d\s().-]+$/', $phone)) {
                kipa_json_response(422, ['error' => 'Vui lòng nhập số điện thoại hợp lệ.']);
            }
        }
        $db->prepare('UPDATE kipa_users SET full_name = :full_name, phone = :phone WHERE id = :id')
            ->execute(['full_name' => $fullName, 'phone' => $phone === '' ? null : $phone, 'id' => (int)$user['id']]);
        $user['full_name'] = $fullName;
        $user['phone'] = $phone === '' ? null : $phone;
        kipa_json_response(200, ['ok' => true, 'user' => kipa_account_view($user)]);
    }

    // action === 'change_password'
    $current = $request['current_password'] ?? null;
    $new = $request['new_password'] ?? null;
    if (!is_string($current) || $current === '' || strlen($current) > 72) {
        kipa_json_response(422, ['error' => 'Vui lòng nhập mật khẩu hiện tại.']);
    }
    if (!is_string($new) || strlen($new) < 8 || strlen($new) > 72 || str_contains($new, "\0")) {
        kipa_json_response(422, ['error' => 'Mật khẩu mới cần dài 8–72 ký tự.']);
    }
    $remoteKey = kipa_remote_key($config);
    $db->beginTransaction();
    if (kipa_rate_limited($db, 'account', $remoteKey, 10, 15)) {
        $db->rollBack();
        kipa_json_response(429, ['error' => 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau.']);
    }
    kipa_record_rate_event($db, 'account', $remoteKey);
    $db->commit();
    if (!password_verify($current, $user['password_hash'])) {
        kipa_json_response(401, ['error' => 'Mật khẩu hiện tại không đúng.']);
    }
    $db->prepare('UPDATE kipa_users SET password_hash = :hash WHERE id = :id')
        ->execute(['hash' => password_hash($new, PASSWORD_DEFAULT), 'id' => (int)$user['id']]);
    session_regenerate_id(true);
    kipa_json_response(200, ['ok' => true]);
} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-account] Database operation failed: ' . $error->getCode());
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
} catch (RuntimeException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-account] Backend configuration failed.');
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
}
