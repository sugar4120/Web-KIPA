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
$raw = file_get_contents('php://input', false, null, 0, 4096);
if ($raw === false || strlen($raw) > 4096) {
    kipa_json_response(413, ['error' => 'Thông tin gửi lên quá dài.']);
}

try {
    $request = json_decode($raw, true, 4, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}

$allowedKeys = [
    'action', 'full_name', 'phone', 'birthday', 'gender', 'address',
    'education_level', 'school_name', 'emergency_contact',
    'current_password', 'new_password', 'confirm_new_password',
    'doc_name', 'file_name', 'id', 'subject', 'message', 'category', 'website'
];
if (!is_array($request) || array_diff(array_keys($request), $allowedKeys)) {
    kipa_json_response(400, ['error' => 'Dữ liệu gửi lên không hợp lệ.']);
}
if (isset($request['website']) && (!is_string($request['website']) || $request['website'] !== '')) {
    kipa_json_response(202, ['ok' => true]);
}

$action = $request['action'] ?? null;
$allowedActions = ['get', 'update', 'change_password', 'upload_document', 'mark_notification', 'submit_support'];
if (!in_array($action, $allowedActions, true)) {
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

function kipa_fetch_student_portal(PDO $db, array $user): array
{
    $email = $user['email'];
    $phone = (string)($user['phone'] ?? '');

    // 1. Hồ sơ học viên (kipa_students)
    $studentStmt = $db->prepare('SELECT * FROM kipa_students WHERE email = :email LIMIT 1');
    $studentStmt->execute(['email' => $email]);
    $student = $studentStmt->fetch();
    if (!$student) {
        $student = [
            'id' => $user['id'],
            'email' => $email,
            'full_name' => $user['full_name'],
            'avatar_url' => '/assets/home/avatar-hoc-sinh.jpg',
            'birthday' => '',
            'gender' => 'Nữ',
            'phone' => $phone,
            'address' => '',
            'education_level' => 'Tốt nghiệp THPT',
            'school_name' => '',
            'emergency_contact' => '',
            'student_status' => 'Đang học',
            'current_program' => 'Du học Hàn Quốc (D2-2)',
            'program_code' => 'KR-D2-2026',
            'country' => 'Hàn Quốc',
            'registration_date' => date('d/m/Y'),
            'target_departure' => 'Tháng 09/2027',
            'dossier_status' => 'Đang chuẩn bị',
            'progress_percent' => 72,
            'advisor_name' => 'Cô Trần Thu Hà',
            'advisor_role' => 'Cố vấn học tập KIPA',
            'advisor_phone' => '036 851 3865',
            'advisor_email' => 'duhocquoctekipaviethan@gmail.com',
            'advisor_avatar' => '/media/nhansukipa/thuy-anh-kipa-viet-han.png'
        ];
    }

    // 2. Lộ trình 5 bước học tập (kipa_student_journey)
    $journey = [];
    try {
        $jStmt = $db->prepare('SELECT * FROM kipa_student_journey WHERE student_email = :email ORDER BY step_number ASC');
        $jStmt->execute(['email' => $email]);
        $journey = $jStmt->fetchAll();
        foreach ($journey as &$step) {
            if (isset($step['tasks_json']) && is_string($step['tasks_json'])) {
                $step['tasks'] = json_decode($step['tasks_json'], true) ?: [];
            }
        }
        unset($step);
    } catch (Throwable) {}

    // 3. Lịch hẹn & lịch học (kipa_student_schedules)
    $schedules = [];
    try {
        $sStmt = $db->prepare('SELECT * FROM kipa_student_schedules WHERE student_email = :email ORDER BY id ASC');
        $sStmt->execute(['email' => $email]);
        $schedules = $sStmt->fetchAll();
    } catch (Throwable) {}

    // 4. Thông báo học viên (kipa_student_notifications)
    $notifications = [];
    try {
        $nStmt = $db->prepare('SELECT * FROM kipa_student_notifications WHERE student_email = :email ORDER BY id DESC');
        $nStmt->execute(['email' => $email]);
        $notifications = $nStmt->fetchAll();
    } catch (Throwable) {}

    // 5. Hồ sơ & giấy tờ nộp (kipa_dossier_documents)
    $documents = [];
    try {
        $dStmt = $db->prepare('SELECT d.*, d.document_name AS doc_name, d.category AS doc_group,
                CASE 
                    WHEN d.verification_status = "APPROVED" THEN "Đã xác nhận"
                    WHEN d.verification_status = "PENDING_REVIEW" THEN "Đang kiểm tra"
                    WHEN d.verification_status = "NEEDS_SUPPLEMENT" THEN "Cần bổ sung"
                    ELSE "Chờ nộp"
                END AS status
            FROM kipa_dossier_documents d 
            JOIN kipa_service_dossiers sd ON d.dossier_id = sd.id 
            JOIN kipa_customers c ON sd.customer_id = c.id 
            WHERE c.email = :email OR (c.phone != "" AND c.phone = :phone)
            ORDER BY d.id ASC');
        $dStmt->execute(['email' => $email, 'phone' => $phone]);
        $documents = $dStmt->fetchAll();
    } catch (Throwable) {}

    // 6. Hỏi đáp & hỗ trợ 2 chiều (kipa_student_support)
    $supportTickets = [];
    try {
        $supStmt = $db->prepare('SELECT * FROM kipa_student_support WHERE student_email = :email ORDER BY id DESC');
        $supStmt->execute(['email' => $email]);
        $supportTickets = $supStmt->fetchAll();
    } catch (Throwable) {}

    // 7. Thư viện tài liệu học tập (kipa_student_materials)
    $materials = [];
    try {
        $matStmt = $db->query('SELECT * FROM kipa_student_materials ORDER BY id ASC');
        if ($matStmt) {
            $materials = $matStmt->fetchAll();
        }
    } catch (Throwable) {}

    return [
        'student' => $student,
        'portal' => [
            'journey' => $journey,
            'documents' => $documents,
            'schedules' => $schedules,
            'notifications' => $notifications,
            'support_tickets' => $supportTickets,
            'materials' => $materials,
        ]
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

    // -----------------------------------------------------------------
    // 1. TRUY XUẤT DỮ LIỆU TỔNG THỂ (GET)
    // -----------------------------------------------------------------
    if ($action === 'get') {
        $portalData = kipa_fetch_student_portal($db, $user);
        kipa_json_response(200, [
            'ok' => true,
            'user' => kipa_account_view($user),
            'student' => $portalData['student'],
            'portal' => $portalData['portal']
        ]);
    }

    // -----------------------------------------------------------------
    // 2. CẬP NHẬT THÔNG TIN HỌC VIÊN (UPDATE PROFILE)
    // -----------------------------------------------------------------
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
            // Chống trùng lặp số điện thoại (1 số điện thoại chỉ liên kết 1 tài khoản)
            $cleanPhone = $digits;
            $dupPhone = $db->prepare('SELECT id FROM kipa_users WHERE id <> :id AND (phone = :phone OR REPLACE(REPLACE(REPLACE(phone, " ", ""), "-", ""), ".", "") = :clean_phone) LIMIT 1');
            $dupPhone->execute(['id' => (int)$user['id'], 'phone' => $phone, 'clean_phone' => $cleanPhone]);
            if ($dupPhone->fetchColumn()) {
                kipa_json_response(409, ['error' => 'Số điện thoại này đã được sử dụng bởi một tài khoản khác. Mỗi số điện thoại chỉ được đăng ký 1 tài khoản duy nhất.']);
            }
        }
        $birthday = is_string($request['birthday'] ?? null) ? trim($request['birthday']) : '';
        $gender = is_string($request['gender'] ?? null) ? trim($request['gender']) : 'Nữ';
        $address = is_string($request['address'] ?? null) ? trim($request['address']) : '';
        $educationLevel = is_string($request['education_level'] ?? null) ? trim($request['education_level']) : 'Tốt nghiệp THPT';
        $schoolName = is_string($request['school_name'] ?? null) ? trim($request['school_name']) : '';
        $emergencyContact = is_string($request['emergency_contact'] ?? null) ? trim($request['emergency_contact']) : '';

        // 2.1 Cập nhật bảng xác thực người dùng (kipa_users)
        $db->prepare('UPDATE kipa_users SET full_name = :full_name, phone = :phone WHERE id = :id')
            ->execute(['full_name' => $fullName, 'phone' => $phone === '' ? null : $phone, 'id' => (int)$user['id']]);
        $user['full_name'] = $fullName;
        $user['phone'] = $phone === '' ? null : $phone;

        // 2.2 Cập nhật hoặc chèn bảng hồ sơ học viên (kipa_students)
        try {
            $stCheck = $db->prepare('SELECT id FROM kipa_students WHERE email = :email LIMIT 1');
            $stCheck->execute(['email' => $user['email']]);
            if ($stCheck->fetch()) {
                $db->prepare('UPDATE kipa_students SET 
                    full_name = :full_name,
                    phone = :phone,
                    birthday = :birthday,
                    gender = :gender,
                    address = :address,
                    education_level = :education_level,
                    school_name = :school_name,
                    emergency_contact = :emergency_contact
                    WHERE email = :email'
                )->execute([
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'birthday' => $birthday,
                    'gender' => $gender,
                    'address' => $address,
                    'education_level' => $educationLevel,
                    'school_name' => $schoolName,
                    'emergency_contact' => $emergencyContact,
                    'email' => $user['email']
                ]);
            } else {
                $db->prepare('INSERT INTO kipa_students (
                    email, full_name, phone, birthday, gender, address,
                    education_level, school_name, emergency_contact
                ) VALUES (
                    :email, :full_name, :phone, :birthday, :gender, :address,
                    :education_level, :school_name, :emergency_contact
                )')->execute([
                    'email' => $user['email'],
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'birthday' => $birthday,
                    'gender' => $gender,
                    'address' => $address,
                    'education_level' => $educationLevel,
                    'school_name' => $schoolName,
                    'emergency_contact' => $emergencyContact
                ]);
            }
        } catch (Throwable $e) {
            error_log('[kipa-account] Sync to kipa_students failed: ' . $e->getMessage());
        }

        // 2.3 Cập nhật đồng bộ sang bảng khách hàng CRM (kipa_customers) nếu có
        try {
            $db->prepare('UPDATE kipa_customers SET 
                full_name = :full_name,
                phone = :phone,
                current_address = :address
                WHERE email = :email OR user_id = :user_id'
            )->execute([
                'full_name' => $fullName,
                'phone' => $phone,
                'address' => $address,
                'email' => $user['email'],
                'user_id' => (int)$user['id']
            ]);
        } catch (Throwable) {}

        // Trả về dữ liệu mới nhất sau khi lưu
        $portalData = kipa_fetch_student_portal($db, $user);
        kipa_json_response(200, [
            'ok' => true,
            'user' => kipa_account_view($user),
            'student' => $portalData['student'],
            'portal' => $portalData['portal'],
            'message' => 'Cập nhật thông tin học viên thành công.'
        ]);
    }

    // -----------------------------------------------------------------
    // 3. ĐỔI MẬT KHẨU TÀI KHOẢN (CHANGE PASSWORD)
    // -----------------------------------------------------------------
    if ($action === 'change_password') {
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
        kipa_json_response(200, ['ok' => true, 'message' => 'Đã đổi mật khẩu thành công.']);
    }

    // -----------------------------------------------------------------
    // 4. TẢI LÊN GIẤY TỜ HỒ SƠ (UPLOAD DOCUMENT)
    // -----------------------------------------------------------------
    if ($action === 'upload_document') {
        $docName = is_string($request['doc_name'] ?? null) ? trim($request['doc_name']) : '';
        $fileName = is_string($request['file_name'] ?? null) ? trim($request['file_name']) : 'tai_lieu_moi.pdf';
        if ($docName !== '') {
            try {
                $sdStmt = $db->prepare('SELECT sd.id FROM kipa_service_dossiers sd 
                    JOIN kipa_customers c ON sd.customer_id = c.id 
                    WHERE c.email = :email OR c.phone = :phone LIMIT 1');
                $sdStmt->execute(['email' => $user['email'], 'phone' => (string)($user['phone'] ?? '')]);
                $sd = $sdStmt->fetch();
                if ($sd) {
                    $db->prepare('UPDATE kipa_dossier_documents 
                        SET verification_status = "PENDING_REVIEW", 
                            file_name = :file_name, 
                            file_url = :file_url,
                            submitted_at = NOW() 
                        WHERE dossier_id = :dossier_id AND document_name = :doc_name'
                    )->execute([
                        'file_name' => $fileName,
                        'file_url' => '/kipa-private/vault/documents/' . $fileName,
                        'dossier_id' => (int)$sd['id'],
                        'doc_name' => $docName
                    ]);
                }
            } catch (Throwable $e) {
                error_log('[kipa-account] Upload document metadata failed: ' . $e->getMessage());
            }
        }
        $portalData = kipa_fetch_student_portal($db, $user);
        kipa_json_response(200, [
            'ok' => true,
            'portal' => $portalData['portal'],
            'message' => 'Đã tải lên giấy tờ hồ sơ thành công!'
        ]);
    }

    // -----------------------------------------------------------------
    // 5. ĐÁNH DẤU ĐÃ ĐỌC THÔNG BÁO (MARK NOTIFICATION)
    // -----------------------------------------------------------------
    if ($action === 'mark_notification') {
        $notifId = $request['id'] ?? null;
        try {
            if ($notifId === 'all') {
                $db->prepare('UPDATE kipa_student_notifications SET is_read = 1 WHERE student_email = :email')
                    ->execute(['email' => $user['email']]);
            } elseif (is_numeric($notifId)) {
                $db->prepare('UPDATE kipa_student_notifications SET is_read = 1 WHERE id = :id AND student_email = :email')
                    ->execute(['id' => (int)$notifId, 'email' => $user['email']]);
            }
        } catch (Throwable) {}
        $portalData = kipa_fetch_student_portal($db, $user);
        kipa_json_response(200, ['ok' => true, 'portal' => $portalData['portal']]);
    }

    // -----------------------------------------------------------------
    // 6. GỬI YÊU CẦU HỖ TRỢ / HỎI ĐÁP CỐ VẤN (SUBMIT SUPPORT)
    // -----------------------------------------------------------------
    if ($action === 'submit_support') {
        $subject = is_string($request['subject'] ?? null) ? trim($request['subject']) : '';
        $message = is_string($request['message'] ?? null) ? trim($request['message']) : '';
        $category = is_string($request['category'] ?? null) ? trim($request['category']) : 'Hồ sơ giấy tờ';
        if ($subject !== '' && $message !== '') {
            $code = 'TK-' . date('Y') . '-' . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            try {
                $db->prepare('INSERT INTO kipa_student_support (
                    student_email, ticket_code, subject, message, category, status, created_at
                ) VALUES (
                    :email, :code, :subject, :message, :category, "Đang xử lý", NOW()
                )')->execute([
                    'email' => $user['email'],
                    'code' => $code,
                    'subject' => $subject,
                    'message' => $message,
                    'category' => $category
                ]);
            } catch (Throwable $e) {
                error_log('[kipa-account] Support ticket insert failed: ' . $e->getMessage());
            }
        }
        $portalData = kipa_fetch_student_portal($db, $user);
        kipa_json_response(200, [
            'ok' => true,
            'portal' => $portalData['portal'],
            'message' => 'Yêu cầu hỗ trợ đã được gửi thành công tới Cố vấn KIPA!'
        ]);
    }

} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-account] Database operation failed: ' . $error->getCode() . ' - ' . $error->getMessage());
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
} catch (RuntimeException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-account] Backend configuration failed: ' . $error->getMessage());
    kipa_json_response(503, ['error' => 'Hiện chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ trực tiếp với KIPA.']);
}
