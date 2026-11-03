<?php
declare(strict_types=1);

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    kipa_json_response(405, ['error' => 'Phương thức gửi không được hỗ trợ.']);
}
kipa_require_origin();

$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($contentType !== 'application/json') {
    kipa_json_response(415, ['error' => 'Định dạng yêu cầu không được hỗ trợ.']);
}
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 16384) {
    kipa_json_response(413, ['error' => 'Thông tin gửi lên quá dài.']);
}
$raw = file_get_contents('php://input', false, null, 0, 16385);
if ($raw === false || strlen($raw) > 16384) {
    kipa_json_response(413, ['error' => 'Thông tin gửi lên quá dài.']);
}

try {
    $request = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (!is_array($request) || array_diff(array_keys($request), ['form_type', 'source_page', 'fields', 'website'])) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (isset($request['website']) && (!is_string($request['website']) || $request['website'] !== '')) {
    kipa_json_response(202, ['ok' => true]);
}

$formTypes = [
    'consultation' => [
        'path' => '/dang-ky-tu-van/',
        'keys' => ['name', 'phone', 'area', 'contact_time', 'program', 'notes', 'consent'],
        'required' => ['name', 'phone', 'area', 'contact_time', 'program', 'consent'],
        'contact' => 'name',
    ],
    'contact' => [
        'path' => '/lien-he/',
        'keys' => ['fullname', 'phone', 'email', 'program', 'topics', 'branch', 'message', 'consent'],
        'required' => ['fullname', 'phone', 'program', 'topics', 'branch', 'message', 'consent'],
        'contact' => 'fullname',
    ],
    'korean_course' => [
        'path' => '/tieng-han/',
        'keys' => ['name', 'phone', 'level', 'format', 'course', 'timing', 'consent'],
        'required' => ['name', 'phone', 'consent'],
        'contact' => 'name',
    ],
    'labor' => [
        'path' => '/xuat-khau-lao-dong/',
        'keys' => ['fullname', 'phone', 'birth_year', 'market', 'industry', 'notes', 'consent'],
        'required' => ['fullname', 'phone', 'birth_year', 'consent'],
        'contact' => 'fullname',
    ],
    'partnership' => [
        'path' => '/hop-tac-quoc-te/',
        'keys' => ['organization', 'org_type', 'contact_name', 'position', 'phone', 'email', 'cooperation_type', 'proposal', 'consent'],
        'required' => ['organization', 'org_type', 'contact_name', 'phone', 'email', 'cooperation_type', 'consent'],
        'contact' => 'contact_name',
    ],
];

$type = $request['form_type'] ?? null;
$page = $request['source_page'] ?? null;
$fields = $request['fields'] ?? null;
if (!is_string($type) || !isset($formTypes[$type]) || $page !== $formTypes[$type]['path'] || !is_array($fields)) {
    kipa_json_response(400, ['error' => 'Biểu mẫu không hợp lệ. Hãy tải lại trang và thử lại.']);
}
$form = $formTypes[$type];
if (array_diff(array_keys($fields), $form['keys'])) {
    kipa_json_response(400, ['error' => 'Biểu mẫu chứa trường không được hỗ trợ.']);
}

$cleanFields = [];
foreach ($fields as $key => $value) {
    if ($key === 'consent') {
        if ($value !== 'yes') {
            kipa_json_response(422, ['error' => 'Bạn cần đồng ý với chính sách bảo mật trước khi gửi.']);
        }
        $cleanFields[$key] = $value;
        continue;
    }
    if ($key === 'topics') {
        if (!is_array($value) || count($value) > 8) {
            kipa_json_response(422, ['error' => 'Vui lòng chọn nội dung tư vấn hợp lệ.']);
        }
        $items = [];
        foreach ($value as $item) {
            if (!is_string($item) || trim($item) === '' || strlen($item) > 120) {
                kipa_json_response(422, ['error' => 'Nội dung tư vấn không hợp lệ.']);
            }
            $items[] = trim($item);
        }
        if ($items === []) {
            kipa_json_response(422, ['error' => 'Vui lòng chọn ít nhất một nội dung cần tư vấn.']);
        }
        $cleanFields[$key] = $items;
        continue;
    }
    if (!is_string($value)) {
        kipa_json_response(422, ['error' => 'Một hoặc nhiều trường có định dạng không hợp lệ.']);
    }
    $value = trim($value);
    if (kipa_text_length($value) > ($key === 'message' || $key === 'proposal' || $key === 'notes' ? 2000 : 254)) {
        kipa_json_response(422, ['error' => 'Một hoặc nhiều trường vượt quá độ dài cho phép.']);
    }
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
        kipa_json_response(422, ['error' => 'Một hoặc nhiều trường chứa ký tự không hợp lệ.']);
    }
    $cleanFields[$key] = $value;
}
foreach ($form['required'] as $required) {
    if (!isset($cleanFields[$required]) || $cleanFields[$required] === '') {
        kipa_json_response(422, ['error' => 'Vui lòng điền đầy đủ các trường bắt buộc.']);
    }
}
$name = $cleanFields[$form['contact']];
$phone = $cleanFields['phone'];
$digits = preg_replace('/\D+/', '', $phone);
if ($digits === null || strlen($digits) < 7 || strlen($digits) > 15 || !preg_match('/^\+?[\d\s().-]+$/', $phone)) {
    kipa_json_response(422, ['error' => 'Vui lòng nhập số điện thoại hợp lệ.']);
}
$email = $cleanFields['email'] ?? null;
if ($email !== null && $email !== '' && (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
    kipa_json_response(422, ['error' => 'Vui lòng nhập địa chỉ email hợp lệ.']);
}
if ($type === 'partnership' && $email === '') {
    kipa_json_response(422, ['error' => 'Vui lòng nhập email liên hệ.']);
}
if (isset($cleanFields['birth_year']) && $cleanFields['birth_year'] !== 'older') {
    $year = filter_var($cleanFields['birth_year'], FILTER_VALIDATE_INT);
    if ($year === false || $year < 1900 || $year > (int)gmdate('Y')) {
        kipa_json_response(422, ['error' => 'Năm sinh không hợp lệ.']);
    }
}

unset($cleanFields['consent']);
$program = $cleanFields['program']
    ?? $cleanFields['course']
    ?? $cleanFields['market']
    ?? $cleanFields['cooperation_type']
    ?? null;
$email = $email === '' ? null : $email;

try {
    $config = kipa_config();
    $db = kipa_db($config);
    $remoteKey = kipa_remote_key($config);
    $db->beginTransaction();
    $db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
    if (kipa_rate_limited($db, 'submit', $remoteKey, 10, 60)) {
        $db->rollBack();
        kipa_json_response(429, ['error' => 'Bạn đã gửi nhiều yêu cầu trong thời gian ngắn. Vui lòng thử lại sau.']);
    }
    kipa_record_rate_event($db, 'submit', $remoteKey);
    $db->exec('DELETE FROM kipa_rate_limits WHERE occurred_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    $statement = $db->prepare(
        'INSERT INTO kipa_leads
            (form_type, contact_name, phone, email, program, source_page, details_json, consented_at, created_at)
         VALUES
            (:form_type, :contact_name, :phone, :email, :program, :source_page, :details_json, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
    );
    $statement->execute([
        'form_type' => $type,
        'contact_name' => $name,
        'phone' => $phone,
        'email' => $email,
        'program' => $program,
        'source_page' => $page,
        'details_json' => json_encode($cleanFields, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
    ]);
    $id = (int)$db->lastInsertId();
    $db->commit();
    kipa_json_response(201, ['ok' => true, 'id' => $id]);
} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-form] Database operation failed: ' . $error->getCode());
    kipa_json_response(503, ['error' => 'Hiện chưa thể gửi thông tin. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
} catch (RuntimeException $error) {
    error_log('[kipa-form] Backend configuration failed.');
    kipa_json_response(503, ['error' => 'Hiện chưa thể gửi thông tin. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
}
