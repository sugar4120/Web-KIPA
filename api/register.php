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
$raw = file_get_contents('php://input', false, null, 0, 4097);
if ($raw === false || strlen($raw) > 4096) {
    kipa_json_response(413, ['error' => 'Thông tin gửi lên quá dài.']);
}

try {
    $request = json_decode($raw, true, 4, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (!is_array($request) || array_diff(array_keys($request), ['full_name', 'email', 'phone', 'password', 'code', 'website'])) {
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
$password = $request['password'] ?? null;
if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
    kipa_json_response(422, ['error' => 'Mật khẩu cần dài 8–72 ký tự.']);
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
    if (kipa_rate_limited($db, 'register', $remoteKey, 10, 60)) {
        $db->rollBack();
        kipa_json_response(429, ['error' => 'Bạn đã gửi nhiều yêu cầu trong thời gian ngắn. Vui lòng thử lại sau.']);
    }
    kipa_record_rate_event($db, 'register', $remoteKey);

    $existing = $db->prepare('SELECT id FROM kipa_users WHERE email = :email');
    $existing->execute(['email' => $email]);
    if ($existing->fetchColumn()) {
        $db->commit();
        kipa_json_response(409, ['error' => 'Email này đã được đăng ký tài khoản. Vui lòng dùng email khác.']);
    }

    // The registration itself consumes the OTP so the check and the insert stay atomic.
    $statement = $db->prepare(
        'SELECT id, code_hash, attempts FROM kipa_otp_codes
         WHERE email = :email AND verified_at IS NULL AND expires_at >= UTC_TIMESTAMP()
         ORDER BY id DESC LIMIT 1 FOR UPDATE'
    );
    $statement->execute(['email' => $email]);
    $row = $statement->fetch();
    if (!$row) {
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
    $db->prepare(
        'INSERT INTO kipa_users (email, password_hash, full_name, phone, email_verified_at, created_at)
         VALUES (:email, :password_hash, :full_name, :phone, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
    )->execute([
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'full_name' => $fullName,
        'phone' => $phone === '' ? null : $phone,
    ]);
    $db->commit();
    kipa_json_response(201, ['ok' => true]);
} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    if ($error->getCode() === '23000') {
        kipa_json_response(409, ['error' => 'Email này đã được đăng ký tài khoản. Vui lòng dùng email khác.']);
    }
    error_log('[kipa-register] Database operation failed: ' . $error->getCode());
    kipa_json_response(503, ['error' => 'Hiện chưa thể tạo tài khoản. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
} catch (RuntimeException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-register] Backend configuration failed.');
    kipa_json_response(503, ['error' => 'Hiện chưa thể tạo tài khoản. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
}
