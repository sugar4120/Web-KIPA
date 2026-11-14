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
if (!is_array($request) || array_diff(array_keys($request), ['email', 'code', 'password', 'website'])) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (isset($request['website']) && (!is_string($request['website']) || $request['website'] !== '')) {
    kipa_json_response(202, ['ok' => true]);
}

try {
    $email = kipa_otp_normalize_email(is_string($request['email'] ?? null) ? $request['email'] : '');
} catch (InvalidArgumentException) {
    kipa_json_response(422, ['error' => 'Vui lòng nhập địa chỉ email hợp lệ.']);
}
$password = $request['password'] ?? null;
if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
    kipa_json_response(422, ['error' => 'Mật khẩu mới cần dài 8–72 ký tự.']);
}
$code = $request['code'] ?? null;
if (!is_string($code) || !preg_match('/^\d{' . KIPA_OTP_LENGTH . '}$/', $code)) {
    kipa_json_response(422, ['error' => 'Mã OTP phải gồm ' . KIPA_OTP_LENGTH . ' chữ số.']);
}

try {
    $config = kipa_config();
    $db = kipa_db($config);
    $remoteKey = kipa_remote_key($config);

    $db->beginTransaction();
    if (kipa_rate_limited($db, 'reset', $remoteKey, 10, 60)) {
        $db->rollBack();
        kipa_json_response(429, ['error' => 'Bạn đã gửi nhiều yêu cầu trong thời gian ngắn. Vui lòng thử lại sau.']);
    }
    kipa_record_rate_event($db, 'reset', $remoteKey);

    $existing = $db->prepare('SELECT id FROM kipa_users WHERE email = :email');
    $existing->execute(['email' => $email]);
    $userId = $existing->fetchColumn();

    // Reset codes are only ever issued for registered emails, so an unknown
    // email simply has no valid code; the message stays generic.
    $statement = $db->prepare(
        'SELECT id, code_hash, attempts FROM kipa_otp_codes
         WHERE email = :email AND verified_at IS NULL AND expires_at >= UTC_TIMESTAMP()
         ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );
    $statement->execute(['email' => $email]);
    $row = $statement->fetch();
    if (!$userId || !$row) {
        $db->commit();
        kipa_json_response(422, ['error' => 'Mã OTP không đúng hoặc đã hết hạn. Vui lòng yêu cầu mã mới.']);
    }
    if ((int)$row['attempts'] >= KIPA_OTP_MAX_ATTEMPTS) {
        $db->commit();
        kipa_json_response(429, ['error' => 'Mã OTP đã bị khóa do nhập sai nhiều lần. Vui lòng yêu cầu mã mới.']);
    }
    $db->prepare('UPDATE kipa_otp_codes SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
    if (!hash_equals($row['code_hash'], kipa_otp_hash_code($email, $code, $config['app_key']))) {
        $db->commit();
        kipa_json_response(422, ['error' => 'Mã OTP không đúng hoặc đã hết hạn. Vui lòng yêu cầu mã mới.']);
    }
    $db->prepare('UPDATE kipa_otp_codes SET verified_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$row['id']]);
    $db->prepare('UPDATE kipa_users SET password_hash = :hash WHERE id = :id')
        ->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int)$userId]);
    $db->commit();
    kipa_json_response(200, ['ok' => true]);
} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-reset] Database operation failed: ' . $error->getCode());
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
} catch (RuntimeException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-reset] Backend configuration failed.');
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
}
