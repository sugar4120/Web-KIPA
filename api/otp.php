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
if (!is_array($request) || array_diff(array_keys($request), ['action', 'email', 'code', 'purpose', 'website'])) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (isset($request['website']) && (!is_string($request['website']) || $request['website'] !== '')) {
    kipa_json_response(202, ['ok' => true]);
}

$action = $request['action'] ?? null;
if (!in_array($action, ['send', 'verify'], true)) {
    kipa_json_response(400, ['error' => 'Yêu cầu không hợp lệ.']);
}
$purpose = $request['purpose'] ?? 'register';
if (!in_array($purpose, ['register', 'reset'], true)) {
    kipa_json_response(400, ['error' => 'Yêu cầu không hợp lệ.']);
}
try {
    $email = kipa_otp_normalize_email(is_string($request['email'] ?? null) ? $request['email'] : '');
} catch (InvalidArgumentException) {
    kipa_json_response(422, ['error' => 'Vui lòng nhập địa chỉ email hợp lệ.']);
}
$code = $request['code'] ?? null;
if ($action === 'verify' && (!is_string($code) || !preg_match('/^\d{' . KIPA_OTP_LENGTH . '}$/', $code))) {
    kipa_json_response(422, ['error' => 'Mã OTP phải gồm ' . KIPA_OTP_LENGTH . ' chữ số.']);
}

try {
    $config = kipa_config();
    $smtp = $action === 'send' ? kipa_smtp_config($config) : null;
    $db = kipa_db($config);
    $remoteKey = kipa_remote_key($config);

    if ($action === 'send') {
        $db->beginTransaction();
        $registered = $db->prepare('SELECT id FROM kipa_users WHERE email = :email');
        $registered->execute(['email' => $email]);
        $hasAccount = (bool)$registered->fetchColumn();
        if ($purpose === 'register' && $hasAccount) {
            $db->rollBack();
            kipa_json_response(409, ['error' => 'Email này đã được đăng ký tài khoản. Vui lòng dùng email khác.']);
        }
        if ($purpose === 'reset' && !$hasAccount) {
            // Generic response so account existence cannot be probed; no code is sent.
            $db->rollBack();
            kipa_json_response(200, ['ok' => true]);
        }
        if (kipa_rate_limited($db, 'otp_send', $remoteKey, 5, 15)) {
            $db->rollBack();
            kipa_json_response(429, ['error' => 'Bạn đã yêu cầu quá nhiều mã OTP. Vui lòng thử lại sau.']);
        }
        kipa_record_rate_event($db, 'otp_send', $remoteKey);
        $recent = $db->prepare(
            'SELECT COUNT(*) FROM kipa_otp_codes
             WHERE email = :email AND created_at >= UTC_TIMESTAMP() - INTERVAL 1 HOUR'
        );
        $recent->execute(['email' => $email]);
        if ((int)$recent->fetchColumn() >= 5) {
            $db->rollBack();
            kipa_json_response(429, ['error' => 'Email này đã nhận quá nhiều mã OTP. Vui lòng thử lại sau một giờ.']);
        }
        $otp = kipa_otp_generate_code();
        // Older unverified codes stay for the per-email rate count; verify only reads the newest row.
        $db->prepare(
            'INSERT INTO kipa_otp_codes (email, code_hash, attempts, expires_at, created_at)
             VALUES (:email, :code_hash, 0, UTC_TIMESTAMP() + INTERVAL ' . KIPA_OTP_TTL_MINUTES . ' MINUTE, UTC_TIMESTAMP())'
        )->execute(['email' => $email, 'code_hash' => kipa_otp_hash_code($email, $otp, $config['app_key'])]);
        $id = (int)$db->lastInsertId();
        $db->commit();
        try {
            $isReset = $purpose === 'reset';
            kipa_send_mail(
                $smtp,
                $email,
                $isReset
                    ? 'Mã OTP đặt lại mật khẩu tài khoản KIPA Việt Hàn'
                    : 'Mã xác thực OTP đăng ký tài khoản KIPA Việt Hàn',
                "Xin chào,\n\n"
                . "Mã xác thực (OTP) của bạn là: {$otp}\n\n"
                . 'Mã có hiệu lực trong ' . KIPA_OTP_TTL_MINUTES . ' phút và chỉ dùng để '
                . ($isReset ? 'đặt lại mật khẩu' : 'đăng ký tài khoản') . " tại kipaviethan.edu.vn.\n"
                . "Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email.\n\n"
                . "KIPA Việt Hàn\n"
                . "Hotline: 036 851 3865 | 0889 2222 55\n"
            );
        } catch (Throwable $error) {
            $db->prepare('DELETE FROM kipa_otp_codes WHERE id = ?')->execute([$id]);
            error_log('[kipa-otp] Sending the OTP email failed.');
            kipa_json_response(503, ['error' => 'Hiện chưa thể gửi mã OTP. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
        }
        kipa_json_response(200, ['ok' => true]);
    }

    $db->beginTransaction();
    if (kipa_rate_limited($db, 'otp_verify', $remoteKey, 15, 15)) {
        $db->rollBack();
        kipa_json_response(429, ['error' => 'Bạn đã thử xác thực quá nhiều lần. Vui lòng thử lại sau.']);
    }
    kipa_record_rate_event($db, 'otp_verify', $remoteKey);
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
    if (!hash_equals($row['code_hash'], kipa_otp_hash_code($email, (string)$code, $config['app_key']))) {
        $db->commit();
        kipa_json_response(422, ['error' => 'Mã OTP không đúng hoặc đã hết hạn. Vui lòng yêu cầu mã mới.']);
    }
    $db->prepare('UPDATE kipa_otp_codes SET verified_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$row['id']]);
    $db->commit();
    kipa_json_response(200, ['ok' => true]);
} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-otp] Database operation failed: ' . $error->getCode());
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
} catch (RuntimeException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-otp] Backend configuration failed.');
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
}
