<?php
declare(strict_types=1);

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/mailer.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
kipa_admin_session();

function kipa_admin_page(string $title, string $content): never
{
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . kipa_h($title) . ' | KIPA</title>';
    echo '<style>
        *{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#0f172a;font:15px/1.5 system-ui,sans-serif}
        main{max-width:1160px;margin:32px auto;padding:0 16px}.card{background:#fff;border:1px solid #dbe3ec;border-radius:12px;padding:22px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
        h1{font-size:24px;margin:0 0 16px;color:#081d36}h2{font-size:18px;margin:0 0 12px;color:#081d36}.muted{color:#64748b;font-size:13px}
        label{display:block;font-weight:600;margin:10px 0 4px;color:#1e293b}input,select{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:7px;font-size:14px;background:#fff}
        input:focus,select:focus{outline:none;border-color:#0274bd;box-shadow:0 0 0 2px rgba(2,116,189,0.2)}
        button{border:0;border-radius:7px;background:#0274bd;color:#fff;padding:10px 16px;font-weight:700;cursor:pointer;font-size:14px;transition:background .15s}
        button:hover{background:#025c96}
        button.danger{background:#b91c1c}button.danger:hover{background:#991b1b}
        button.secondary{background:#334155}button.secondary:hover{background:#1e293b}
        .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .message{padding:12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:7px;margin:12px 0;font-size:14px}
        .message.success{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
        a{color:#0274bd;text-decoration:none;font-weight:600}a:hover{text-decoration:underline}
        .table-wrap{overflow:auto;border:1px solid #e2e8f0;border-radius:8px;}table{width:100%;border-collapse:collapse;min-width:800px}th,td{text-align:left;vertical-align:middle;padding:11px 14px;border-bottom:1px solid #e2e8f0}
        thead th{background:#f8fafc;font-size:13px;color:#475569;}
        pre{white-space:pre-wrap;overflow-wrap:anywhere;max-width:420px;font:12px/1.5 ui-monospace,monospace;margin:0}
        form.inline{display:inline}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .nav-tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:2px solid #e2e8f0;padding-bottom:12px;flex-wrap:wrap}
        .nav-tab{padding:9px 16px;border-radius:8px;font-weight:600;text-decoration:none;background:#f1f5f9;color:#475569;display:inline-flex;align-items:center;gap:6px}
        .nav-tab:hover{background:#e2e8f0;text-decoration:none}
        .nav-tab.active{background:#0274bd;color:#fff}
        .badge{padding:3px 8px;border-radius:12px;font-size:11px;font-weight:bold;display:inline-block;}
        @media(max-width:600px){main{margin:16px auto}.card{padding:14px}h1{font-size:21px}}
    </style></head><body><main>' . $content . '</main></body></html>';
    exit;
}

function kipa_roles_list(): array
{
    return [
        'super_admin' => ['title' => '01. Super Admin', 'desc' => 'Quản trị hệ thống toàn quyền', 'color' => '#dc2626'],
        'website_manager' => ['title' => '02. Website Manager', 'desc' => 'Quản lý vận hành Website & CMS', 'color' => '#0284c7'],
        'admissions_manager' => ['title' => '03. Admissions Manager', 'desc' => 'Quản lý bộ phận tuyển sinh & Lead', 'color' => '#059669'],
        'admissions_consultant' => ['title' => '04. Admissions Consultant', 'desc' => 'Tư vấn tuyển sinh & CSKH', 'color' => '#10b981'],
        'content_manager' => ['title' => '05. Content Manager', 'desc' => 'Quản lý nội dung & biên tập', 'color' => '#d97706'],
        'content_seo_executive' => ['title' => '06. Content & SEO Executive', 'desc' => 'Viết nội dung & SEO cơ bản', 'color' => '#f59e0b'],
        'seo_manager' => ['title' => '07. SEO Manager', 'desc' => 'Quản trị SEO chiến lược & kỹ thuật', 'color' => '#7c3aed'],
        'academic_coordinator' => ['title' => '08. Academic & Student Coordinator', 'desc' => 'Quản lý học viên & điều phối học vụ', 'color' => '#2563eb'],
    ];
}

function kipa_ensure_admin_columns(PDO $db): void
{
    static $ensured = false;
    if ($ensured) return;
    try {
        $cols = $db->query("SHOW COLUMNS FROM kipa_admins")->fetchAll(PDO::FETCH_COLUMN);
        if ($cols && is_array($cols)) {
            if (!in_array('full_name', $cols, true)) {
                $db->exec("ALTER TABLE kipa_admins ADD COLUMN full_name VARCHAR(160) NOT NULL DEFAULT '' AFTER username");
            }
            if (!in_array('email', $cols, true)) {
                $db->exec("ALTER TABLE kipa_admins ADD COLUMN email VARCHAR(191) NULL DEFAULT NULL AFTER full_name");
            }
            if (!in_array('phone', $cols, true)) {
                $db->exec("ALTER TABLE kipa_admins ADD COLUMN phone VARCHAR(32) NULL DEFAULT NULL AFTER email");
            }
            if (!in_array('department', $cols, true)) {
                $db->exec("ALTER TABLE kipa_admins ADD COLUMN department VARCHAR(64) NOT NULL DEFAULT 'Ban Giám Đốc' AFTER phone");
            }
            if (!in_array('role', $cols, true)) {
                $db->exec("ALTER TABLE kipa_admins ADD COLUMN role VARCHAR(64) NOT NULL DEFAULT 'super_admin' AFTER department");
            }
            if (!in_array('scope', $cols, true)) {
                $db->exec("ALTER TABLE kipa_admins ADD COLUMN scope VARCHAR(255) NOT NULL DEFAULT 'Toàn hệ thống' AFTER role");
            }
            if (!in_array('status', $cols, true)) {
                $db->exec("ALTER TABLE kipa_admins ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'active' AFTER scope");
            }
        }
    } catch (Throwable) {
    }
    $ensured = true;
}

function kipa_admin_error(string $message, int $status = 400): never
{
    http_response_code($status);
    kipa_admin_page('Lỗi', '<section class="card"><h1>Không thể xử lý yêu cầu</h1><p class="message">' . kipa_h($message) . '</p><p><a href="/api/admin.php">Tải lại trang quản trị</a></p></section>');
}

function kipa_admin_form_start(string $action, string $csrf): string
{
    return '<form method="post" action="/api/admin.php"><input type="hidden" name="action" value="' .
        kipa_h($action) . '"><input type="hidden" name="csrf" value="' . kipa_h($csrf) . '">';
}

try {
    $config = kipa_config();
    $db = kipa_db($config);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $remoteKey = kipa_remote_key($config);
    $currentAdmin = kipa_current_admin($db);

    if ($method === 'POST') {
        kipa_require_origin();
        kipa_check_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'setup') {
            $count = (int)$db->query('SELECT COUNT(*) FROM kipa_admins')->fetchColumn();
            $token = $_POST['setup_token'] ?? '';
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['password_confirm'] ?? '');
            if ($count !== 0 || !is_string($token) || !hash_equals($config['setup_token'], $token)) {
                kipa_admin_error('Thông tin thiết lập không hợp lệ hoặc tài khoản quản trị đã được tạo.');
            }
            if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $username)) {
                kipa_admin_error('Tên đăng nhập phải có từ 3 đến 64 ký tự gồm chữ cái, số, dấu chấm, gạch ngang hoặc gạch dưới.');
            }
            if (strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0") || !hash_equals($password, $confirm)) {
                kipa_admin_error('Mật khẩu phải có từ 12 đến 72 byte và hai lần nhập phải giống nhau.');
            }
            $statement = $db->prepare(
                'INSERT INTO kipa_admins (username, password_hash, created_at)
                 VALUES (:username, :password_hash, UTC_TIMESTAMP())'
            );
            $statement->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            $_SESSION['admin_id'] = (int)$db->lastInsertId();
            $_SESSION['admin_username'] = $username;
            header('Location: /api/admin.php', true, 303);
            exit;
        }

        if ($action === 'login') {
            if (kipa_rate_limited($db, 'login', $remoteKey, 8, 15)) {
                kipa_admin_error('Đăng nhập quá nhiều lần. Vui lòng đợi 15 phút rồi thử lại.', 429);
            }
            kipa_record_rate_event($db, 'login', $remoteKey);
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            kipa_ensure_admin_columns($db);
            try {
                $statement = $db->prepare('SELECT id, username, full_name, email, password_hash, role, department, scope, status, must_change_password FROM kipa_admins WHERE username = :username LIMIT 1');
                $statement->execute(['username' => $username]);
                $admin = $statement->fetch();
            } catch (Throwable) {
                $fallbackStmt = $db->prepare('SELECT id, username, password_hash, must_change_password FROM kipa_admins WHERE username = :username LIMIT 1');
                $fallbackStmt->execute(['username' => $username]);
                $admin = $fallbackStmt->fetch();
            }
            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                kipa_admin_error('Tên đăng nhập hoặc mật khẩu không đúng.');
            }
            if (isset($admin['status']) && $admin['status'] === 'locked') {
                kipa_admin_error('Tài khoản này hiện đang bị tạm khóa. Vui lòng liên hệ Super Admin.', 403);
            }
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_full_name'] = !empty($admin['full_name']) ? $admin['full_name'] : $admin['username'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'super_admin';
            $_SESSION['admin_department'] = $admin['department'] ?? 'Ban Giám Đốc';
            $_SESSION['admin_scope'] = $admin['scope'] ?? 'Toàn hệ thống';
            $_SESSION['must_change_password'] = (bool)$admin['must_change_password'];
            header('Location: /api/admin.php', true, 303);
            exit;
        }

        if ($action === 'send_otp') {
            if (kipa_rate_limited($db, 'admin_otp_send', $remoteKey, 5, 15)) {
                kipa_admin_error('Yêu cầu gửi mã OTP quá nhiều lần. Vui lòng đợi 15 phút rồi thử lại.', 429);
            }
            kipa_record_rate_event($db, 'admin_otp_send', $remoteKey);
            $account = trim((string)($_POST['account'] ?? ''));
            if ($account === '') {
                kipa_admin_error('Vui lòng nhập tên đăng nhập hoặc email quản trị.');
            }

            // Check if column email exists in kipa_admins
            $hasEmailCol = (bool)$db->query("SHOW COLUMNS FROM kipa_admins LIKE 'email'")->fetch();
            $queryStr = $hasEmailCol
                ? 'SELECT id, username, email FROM kipa_admins WHERE username = :acc OR email = :acc LIMIT 1'
                : 'SELECT id, username, NULL as email FROM kipa_admins WHERE username = :acc LIMIT 1';

            $statement = $db->prepare($queryStr);
            $statement->execute(['acc' => $account]);
            $admin = $statement->fetch();
            if (!$admin) {
                kipa_admin_error('Không tìm thấy tài khoản quản trị khớp với thông tin này.');
            }
            $adminEmail = trim((string)($admin['email'] ?? ''));
            if ($adminEmail === '' || filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false) {
                kipa_admin_error('Tài khoản quản trị chưa được gán email để nhận OTP. Vui lòng dùng Mã thiết lập (setup_token) để đặt lại mật khẩu.');
            }

            $otp = kipa_otp_generate_code();
            $db->prepare(
                'INSERT INTO kipa_otp_codes (email, code_hash, attempts, expires_at, created_at)
                 VALUES (:email, :code_hash, 0, UTC_TIMESTAMP() + INTERVAL 10 MINUTE, UTC_TIMESTAMP())'
            )->execute(['email' => $adminEmail, 'code_hash' => kipa_otp_hash_code($adminEmail, $otp, $config['app_key'])]);

            try {
                $smtp = kipa_smtp_config($config);
                kipa_send_mail(
                    $smtp,
                    $adminEmail,
                    'Mã OTP đặt lại mật khẩu Quản trị viên KIPA Việt Hàn',
                    "Xin chào Quản trị viên KIPA Việt Hàn,\n\n"
                    . "Hệ thống ghi nhận yêu cầu đặt lại mật khẩu cho tài khoản quản trị: {$admin['username']}\n\n"
                    . "Mã xác thực OTP của bạn là: {$otp}\n\n"
                    . "Mã có hiệu lực trong 10 phút. Tuyệt đối không chia sẻ mã này cho bất kỳ ai.\n"
                    . "Nếu bạn không thực hiện yêu cầu này, vui lòng kiểm tra an ninh máy chủ ngay lập tức.\n\n"
                    . "KIPA Việt Hàn - Ban Quản Trị Hệ Thống\n"
                    . "Hotline: 036 851 3865 | 0889 2222 55\n"
                );
            } catch (Throwable $e) {
                error_log('[kipa-admin-otp] Failed to send admin OTP: ' . $e->getMessage());
                kipa_admin_error('Chưa thể gửi email OTP lúc này. Vui lòng kiểm tra SMTP hoặc chuyển sang dùng Mã thiết lập (setup_token) để đặt lại mật khẩu.', 503);
            }

            $parts = explode('@', $adminEmail);
            $obfuscated = substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '');
            header('Location: /api/admin.php?view=reset&step=2&account=' . urlencode($admin['username']) . '&email_hint=' . urlencode($obfuscated), true, 303);
            exit;
        }

        if ($action === 'reset_password') {
            if (kipa_rate_limited($db, 'admin_reset', $remoteKey, 5, 15)) {
                kipa_admin_error('Yêu cầu đặt lại mật khẩu quá nhiều lần. Vui lòng đợi 15 phút rồi thử lại.', 429);
            }
            kipa_record_rate_event($db, 'admin_reset', $remoteKey);
            $account = trim((string)($_POST['account'] ?? ''));
            $otpCode = trim((string)($_POST['otp_code'] ?? ''));
            $token = (string)($_POST['setup_token'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['new_password_confirm'] ?? '');

            if ($account === '') {
                kipa_admin_error('Vui lòng cung cấp tên đăng nhập hoặc email quản trị.');
            }
            if (strlen($newPassword) < 12 || strlen($newPassword) > 72 || str_contains($newPassword, "\0") || !hash_equals($newPassword, $confirmPassword)) {
                kipa_admin_error('Mật khẩu mới phải có từ 12 đến 72 byte và hai lần nhập phải giống nhau.');
            }

            $hasEmailCol = (bool)$db->query("SHOW COLUMNS FROM kipa_admins LIKE 'email'")->fetch();
            $queryStr = $hasEmailCol
                ? 'SELECT id, username, email, password_hash FROM kipa_admins WHERE username = :acc OR email = :acc LIMIT 1'
                : 'SELECT id, username, NULL as email, password_hash FROM kipa_admins WHERE username = :acc LIMIT 1';

            $statement = $db->prepare($queryStr);
            $statement->execute(['acc' => $account]);
            $admin = $statement->fetch();
            if (!$admin) {
                kipa_admin_error('Tài khoản quản trị không tồn tại.');
            }

            $verified = false;
            // 1. Xác thực bằng OTP Email
            if ($otpCode !== '') {
                $adminEmail = trim((string)($admin['email'] ?? ''));
                if ($adminEmail === '') {
                    kipa_admin_error('Tài khoản này chưa có email để xác thực OTP. Vui lòng sử dụng Mã thiết lập (setup_token).');
                }
                $st = $db->prepare(
                    'SELECT id, code_hash, attempts FROM kipa_otp_codes
                     WHERE email = :email AND verified_at IS NULL AND expires_at >= UTC_TIMESTAMP()
                     ORDER BY id DESC LIMIT 1 FOR UPDATE'
                );
                $st->execute(['email' => $adminEmail]);
                $row = $st->fetch();
                if (!$row) {
                    kipa_admin_error('Mã OTP không đúng hoặc đã hết hạn. Vui lòng gửi lại mã mới.');
                }
                if ((int)$row['attempts'] >= KIPA_OTP_MAX_ATTEMPTS) {
                    kipa_admin_error('Mã OTP đã bị khóa do nhập sai nhiều lần. Vui lòng yêu cầu mã mới.');
                }
                $db->prepare('UPDATE kipa_otp_codes SET attempts = attempts + 1 WHERE id = ?')->execute([$row['id']]);
                if (!hash_equals($row['code_hash'], kipa_otp_hash_code($adminEmail, $otpCode, $config['app_key']))) {
                    kipa_admin_error('Mã OTP không chính xác. Vui lòng kiểm tra lại email.');
                }
                $db->prepare('UPDATE kipa_otp_codes SET verified_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$row['id']]);
                $verified = true;
            }
            // 2. Xác thực bằng Master Setup Token dự phòng
            elseif ($token !== '') {
                if (!hash_equals($config['setup_token'], $token)) {
                    kipa_admin_error('Mã thiết lập bí mật không đúng. Vui lòng kiểm tra lại trong cấu hình máy chủ.');
                }
                $verified = true;
            } else {
                kipa_admin_error('Vui lòng nhập mã OTP gửi về email hoặc mã thiết lập máy chủ.');
            }

            if ($verified) {
                $update = $db->prepare('UPDATE kipa_admins SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id');
                $update->execute([
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'id' => (int)$admin['id'],
                ]);

                // Gửi thông báo an ninh nếu có email
                if (!empty($admin['email'])) {
                    try {
                        $smtp = kipa_smtp_config($config);
                        kipa_send_mail(
                            $smtp,
                            $admin['email'],
                            'Cảnh báo: Mật khẩu quản trị KIPA vừa được đặt lại',
                            "Xin chào Quản trị viên KIPA,\n\n"
                            . "Mật khẩu cho tài khoản '{$admin['username']}' vừa được đặt lại thành công vào lúc " . gmdate('Y-m-d H:i:s') . " UTC.\n"
                            . "Nếu không phải bạn thực hiện, vui lòng liên hệ ngay với bộ phận kỹ thuật để bảo vệ hệ thống.\n\n"
                            . "KIPA Việt Hàn\n"
                        );
                    } catch (Throwable $e) {
                        // ignore notify failure
                    }
                }

                session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                header('Location: /api/admin.php?reset_success=1', true, 303);
                exit;
            }
        }

        if (!isset($_SESSION['admin_id'])) {
            kipa_admin_error('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
        }
        if ($action === 'logout') {
            $_SESSION = [];
            session_destroy();
            header('Location: /api/admin.php', true, 303);
            exit;
        }
        if ($currentAdmin && (bool)$currentAdmin['must_change_password'] && $action !== 'change_password') {
            kipa_admin_error('Bạn phải đổi mật khẩu trước khi sử dụng chức năng quản trị.', 403);
        }
        if ($action === 'delete') {
            $id = filter_var($_POST['lead_id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                kipa_admin_error('Mã đăng ký không hợp lệ.');
            }
            $statement = $db->prepare('SELECT id FROM kipa_leads WHERE id = :id');
            $statement->execute(['id' => $id]);
            if (!$statement->fetch()) {
                kipa_admin_error('Đăng ký không tồn tại hoặc đã được xóa.');
            }
            $csrf = kipa_h($_SESSION['csrf']);
            $content = '<section class="card"><h1>Xác nhận xóa đăng ký</h1><p>Bạn có chắc chắn muốn xóa đăng ký #' .
                (int)$id . '? Thao tác này không thể hoàn tác.</p>' . kipa_admin_form_start('confirm_delete', $csrf);
            $content .= '<input type="hidden" name="lead_id" value="' . (int)$id .
                '"><button class="danger" type="submit">Xóa vĩnh viễn</button></form> ';
            $content .= '<a href="/api/admin.php">Hủy</a></section>';
            kipa_admin_page('Xác nhận xóa', $content);
        }
        if ($action === 'confirm_delete') {
            $id = filter_var($_POST['lead_id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                kipa_admin_error('Mã đăng ký không hợp lệ.');
            }
            $statement = $db->prepare('DELETE FROM kipa_leads WHERE id = :id');
            $statement->execute(['id' => $id]);
            header('Location: /api/admin.php?deleted=1', true, 303);
            exit;
        }
        if ($action === 'change_password') {
            $oldPassword = (string)($_POST['old_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['new_password_confirm'] ?? '');
            $statement = $db->prepare('SELECT password_hash FROM kipa_admins WHERE id = :id');
            $statement->execute(['id' => (int)$_SESSION['admin_id']]);
            $admin = $statement->fetch();
            if (!$admin || !password_verify($oldPassword, $admin['password_hash'])) {
                kipa_admin_error('Mật khẩu hiện tại không đúng.');
            }
            if (strlen($newPassword) < 12 || strlen($newPassword) > 72 || str_contains($newPassword, "\0") || !hash_equals($newPassword, $confirmPassword)) {
                kipa_admin_error('Mật khẩu mới phải có từ 12 đến 72 byte và hai lần nhập phải giống nhau.');
            }
            if (password_verify($newPassword, $admin['password_hash'])) {
                kipa_admin_error('Mật khẩu mới phải khác mật khẩu hiện tại.');
            }
            $statement = $db->prepare('UPDATE kipa_admins SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id');
            $statement->execute([
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id' => (int)$_SESSION['admin_id'],
            ]);
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            unset($_SESSION['must_change_password']);
            header('Location: /api/admin.php?password_changed=1', true, 303);
            exit;
        }

        if ($action === 'create_admin') {
            $currentRole = $_SESSION['admin_role'] ?? 'super_admin';
            if ($currentRole !== 'super_admin' && $currentRole !== 'website_manager') {
                kipa_admin_error('Bạn không có quyền tạo tài khoản nhân sự.', 403);
            }
            kipa_ensure_admin_columns($db);
            $newUsername = trim((string)($_POST['username'] ?? ''));
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $department = trim((string)($_POST['department'] ?? 'Ban Giám Đốc'));
            $role = trim((string)($_POST['role'] ?? 'super_admin'));
            $scope = trim((string)($_POST['scope'] ?? 'Toàn hệ thống'));
            $password = (string)($_POST['password'] ?? '');
            $mustChange = isset($_POST['must_change_password']) ? 1 : 0;

            if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $newUsername)) {
                kipa_admin_error('Tên đăng nhập phải từ 3 đến 64 ký tự (chữ cái, số, ., -, _).');
            }
            if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 160) {
                kipa_admin_error('Họ và tên nhân sự không được để trống (từ 2 đến 160 ký tự).');
            }
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                kipa_admin_error('Địa chỉ email không hợp lệ.');
            }
            if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
                kipa_admin_error('Mật khẩu khởi tạo phải từ 8 đến 72 byte.');
            }

            $validRoles = array_keys(kipa_roles_list());
            if (!in_array($role, $validRoles, true)) {
                kipa_admin_error('Vai trò được chọn không hợp lệ.');
            }

            $dup = $db->prepare('SELECT id FROM kipa_admins WHERE username = :u OR (email IS NOT NULL AND email != "" AND email = :e) LIMIT 1');
            $dup->execute(['u' => $newUsername, 'e' => $email]);
            if ($dup->fetch()) {
                kipa_admin_error('Tên đăng nhập hoặc Email này đã tồn tại trong hệ thống.');
            }

            $stmt = $db->prepare(
                'INSERT INTO kipa_admins (username, full_name, email, phone, password_hash, department, role, scope, status, must_change_password, created_at)
                 VALUES (:username, :full_name, :email, :phone, :password_hash, :department, :role, :scope, "active", :must_change, UTC_TIMESTAMP())'
            );
            $stmt->execute([
                'username' => $newUsername,
                'full_name' => $fullName,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'department' => $department,
                'role' => $role,
                'scope' => $scope,
                'must_change' => $mustChange,
            ]);

            header('Location: /api/admin.php?tab=accounts&admin_created=1', true, 303);
            exit;
        }

        if ($action === 'toggle_admin_status') {
            $currentRole = $_SESSION['admin_role'] ?? 'super_admin';
            if ($currentRole !== 'super_admin' && $currentRole !== 'website_manager') {
                kipa_admin_error('Bạn không có quyền thay đổi trạng thái tài khoản.', 403);
            }
            $targetId = filter_var($_POST['target_id'] ?? null, FILTER_VALIDATE_INT);
            if ($targetId === false || $targetId < 1) {
                kipa_admin_error('Mã tài khoản không hợp lệ.');
            }
            if ($targetId === (int)$_SESSION['admin_id']) {
                kipa_admin_error('Không thể tự khóa tài khoản đang đăng nhập của chính bạn.');
            }
            $target = $db->prepare('SELECT id, status FROM kipa_admins WHERE id = :id');
            $target->execute(['id' => $targetId]);
            $adminRow = $target->fetch();
            if (!$adminRow) {
                kipa_admin_error('Tài khoản không tồn tại.');
            }
            $newStatus = ($adminRow['status'] ?? 'active') === 'active' ? 'locked' : 'active';
            $update = $db->prepare('UPDATE kipa_admins SET status = :st WHERE id = :id');
            $update->execute(['st' => $newStatus, 'id' => $targetId]);
            header('Location: /api/admin.php?tab=accounts&status_updated=1', true, 303);
            exit;
        }

        if ($action === 'delete_admin') {
            if (($_SESSION['admin_role'] ?? 'super_admin') !== 'super_admin') {
                kipa_admin_error('Chỉ Super Admin mới có quyền xóa tài khoản.', 403);
            }
            $targetId = filter_var($_POST['target_id'] ?? null, FILTER_VALIDATE_INT);
            if ($targetId === false || $targetId < 1) {
                kipa_admin_error('Mã tài khoản không hợp lệ.');
            }
            if ($targetId === (int)$_SESSION['admin_id']) {
                kipa_admin_error('Không thể xóa tài khoản của chính bạn.');
            }
            $del = $db->prepare('DELETE FROM kipa_admins WHERE id = :id');
            $del->execute(['id' => $targetId]);
            header('Location: /api/admin.php?tab=accounts&deleted_admin=1', true, 303);
            exit;
        }
        kipa_admin_error('Tác vụ không hợp lệ.');
    }

    if ($method !== 'GET') {
        header('Allow: GET, POST');
        kipa_admin_error('Phương thức gửi không được hỗ trợ.');
    }

    if ($currentAdmin && (bool)$currentAdmin['must_change_password']) {
        $content = '<section class="card"><h1>Bắt buộc đổi mật khẩu</h1>';
        $content .= '<p class="message">Đổi mật khẩu ban đầu trước khi truy cập dữ liệu hoặc sử dụng chức năng quản trị.</p>';
        $content .= kipa_admin_form_start('change_password', $_SESSION['csrf']);
        $content .= '<label>Mật khẩu hiện tại<input name="old_password" type="password" required autocomplete="current-password"></label>';
        $content .= '<label>Mật khẩu mới (12–72 byte)<input name="new_password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>';
        $content .= '<label>Nhập lại mật khẩu mới<input name="new_password_confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>';
        $content .= '<p><button type="submit">Đổi mật khẩu</button></p></form>';
        $content .= kipa_admin_form_start('logout', $_SESSION['csrf']) . '<button class="secondary">Đăng xuất</button></form></section>';
        kipa_admin_page('Bắt buộc đổi mật khẩu', $content);
    }

    $db->exec('DELETE FROM kipa_rate_limits WHERE occurred_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    $adminCount = (int)$db->query('SELECT COUNT(*) FROM kipa_admins')->fetchColumn();
    if ($adminCount === 0) {
        $csrf = kipa_h($_SESSION['csrf']);
        $content = '<section class="card"><h1>Thiết lập quản trị KIPA</h1>';
        $content .= '<p class="muted">Tạo tài khoản quản trị đầu tiên. Hãy lưu thông tin đăng nhập ở nơi an toàn.</p>';
        $content .= '<p class="message">Chỉ người có mã thiết lập trong tệp cấu hình riêng mới có thể tạo tài khoản này.</p>';
        $content .= kipa_admin_form_start('setup', $csrf);
        $content .= '<label>Mã thiết lập<input name="setup_token" type="password" required autocomplete="off"></label>';
        $content .= '<label>Tên đăng nhập<input name="username" required minlength="3" maxlength="64" autocomplete="username"></label>';
        $content .= '<label>Mật khẩu (12–72 byte)<input name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>';
        $content .= '<label>Nhập lại mật khẩu<input name="password_confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>';
        $content .= '<p><button type="submit">Tạo tài khoản quản trị</button></p></form></section>';
        kipa_admin_page('Thiết lập quản trị', $content);
    }

    if (!isset($_SESSION['admin_id'])) {
        $csrf = kipa_h($_SESSION['csrf']);
        $view = $_GET['view'] ?? '';
        $mode = $_GET['mode'] ?? 'email';
        $step = $_GET['step'] ?? '1';
        $account = trim((string)($_GET['account'] ?? ''));
        $emailHint = trim((string)($_GET['email_hint'] ?? ''));

        if ($view === 'reset') {
            $content = '<section class="card"><h1>Đặt lại mật khẩu quản trị KIPA</h1>';

            if ($mode === 'token') {
                $content .= '<p class="muted">Xác thực bằng Mã thiết lập bí mật (setup_token) từ tệp cấu hình máy chủ để đặt lại mật khẩu khẩn cấp.</p>';
                $content .= kipa_admin_form_start('reset_password', $csrf);
                $content .= '<label>Tên đăng nhập hoặc Email quản trị<input name="account" required minlength="3" maxlength="64" autocomplete="username" placeholder="Ví dụ: admin"></label>';
                $content .= '<label>Mã thiết lập bí mật (setup_token)<input name="setup_token" type="password" required autocomplete="off" placeholder="Khóa bảo mật trong file cấu hình"></label>';
                $content .= '<label>Mật khẩu mới (12–72 byte)<input name="new_password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="Tối thiểu 12 ký tự"></label>';
                $content .= '<label>Nhập lại mật khẩu mới<input name="new_password_confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="Nhập lại chính xác"></label>';
                $content .= '<p class="actions" style="margin-top:18px"><button type="submit">Xác nhận đặt lại mật khẩu</button> <a href="/api/admin.php?view=reset" style="margin-left:12px;font-size:14px">← Đổi bằng Mã OTP Email</a> <a href="/api/admin.php" style="margin-left:12px;font-size:14px">Quay lại Đăng nhập</a></p></form>';
            } elseif ($step === '2') {
                $content .= '<p class="message success">✓ Đã gửi mã xác thực OTP 6 số đến email ' . ($emailHint ? '<strong>' . kipa_h($emailHint) . '</strong>' : 'quản trị') . '. Vui lòng kiểm tra hộp thư đến (hoặc hòm thư Spam).</p>';
                $content .= kipa_admin_form_start('reset_password', $csrf);
                $content .= '<input type="hidden" name="account" value="' . kipa_h($account) . '">';
                $content .= '<p class="muted" style="margin-bottom:10px;">Tài khoản quản trị: <strong>' . kipa_h($account) . '</strong></p>';
                $content .= '<label>Mã xác thực OTP (6 chữ số)<input name="otp_code" required pattern="\\d{6}" maxlength="6" autocomplete="one-time-code" placeholder="Nhập 6 số từ email" autofocus></label>';
                $content .= '<label>Mật khẩu mới (12–72 byte)<input name="new_password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="Tối thiểu 12 ký tự"></label>';
                $content .= '<label>Nhập lại mật khẩu mới<input name="new_password_confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="Nhập lại chính xác"></label>';
                $content .= '<p class="actions" style="margin-top:18px"><button type="submit">Xác nhận đổi mật khẩu</button> <a href="/api/admin.php?view=reset" style="margin-left:12px;font-size:14px">Gửi lại mã OTP</a> <a href="/api/admin.php" style="margin-left:12px;font-size:14px">Quay lại Đăng nhập</a></p></form>';
            } else {
                $content .= '<p class="muted">Nhập Tên đăng nhập hoặc Email quản trị để nhận mã xác thực OTP gửi về hòm thư của bạn.</p>';
                if (kipa_rate_limited($db, 'admin_otp_send', $remoteKey, 5, 15)) {
                    $content .= '<p class="message">Yêu cầu gửi OTP quá nhiều lần. Vui lòng thử lại sau 15 phút.</p>';
                } else {
                    $content .= kipa_admin_form_start('send_otp', $csrf);
                    $content .= '<label>Tên đăng nhập hoặc Email quản trị<input name="account" required minlength="3" maxlength="191" autocomplete="username" placeholder="Ví dụ: admin hoặc email@kipaviethan.edu.vn" autofocus></label>';
                    $content .= '<p class="actions" style="margin-top:18px"><button type="submit">Gửi mã OTP về Email</button> <a href="/api/admin.php" style="margin-left:12px;font-size:14px">← Quay lại Đăng nhập</a></p></form>';
                    $content .= '<div style="margin-top:20px;border-top:1px solid #e2e8f0;padding-top:14px;"><p class="muted" style="margin-bottom:6px;">Chưa liên kết email hoặc không nhận được thư?</p><a href="/api/admin.php?view=reset&mode=token" style="font-size:13px;">Đặt lại mật khẩu bằng Mã thiết lập máy chủ (setup_token) →</a></div>';
                }
            }
            $content .= '</section>';
            kipa_admin_page('Đặt lại mật khẩu quản trị', $content);
        }

        $content = '<section class="card"><h1>Quản trị đăng ký tư vấn KIPA</h1>';
        if (isset($_GET['reset_success'])) {
            $content .= '<p class="message success">✓ Đã đặt lại mật khẩu quản trị thành công! Vui lòng đăng nhập bằng mật khẩu mới.</p>';
        }
        if (kipa_rate_limited($db, 'login', $remoteKey, 8, 15)) {
            $content .= '<p class="message">Đăng nhập quá nhiều lần. Vui lòng thử lại sau.</p>';
        } else {
            $content .= kipa_admin_form_start('login', $csrf);
            $content .= '<label>Tên đăng nhập<input name="username" required maxlength="64" autocomplete="username"></label>';
            $content .= '<label>Mật khẩu<input name="password" type="password" required autocomplete="current-password"></label>';
            $content .= '<p class="actions" style="margin-top:18px"><button type="submit">Đăng nhập</button> <a href="/api/admin.php?view=reset" style="margin-left:12px;font-size:13px">Quên mật khẩu? Đặt lại mật khẩu</a></p></form>';
        }
        $content .= '<p class="muted" style="margin-top:16px">Chỉ tài khoản được ủy quyền mới có thể xem thông tin đăng ký.</p></section>';
        kipa_admin_page('Đăng nhập', $content);
    }

    $db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
    $page = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT);
    $page = $page === false ? 1 : max(1, min(100000, $page));
    $perPage = 50;
    $total = (int)$db->query('SELECT COUNT(*) FROM kipa_leads')->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $statement = $db->prepare(
        'SELECT id, form_type, contact_name, phone, email, program, source_page, details_json, created_at
         FROM kipa_leads ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset'
    );
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
    $statement->execute();
    $leads = $statement->fetchAll();

    kipa_ensure_admin_columns($db);
    $adminListStmt = $db->query('SELECT id, username, full_name, email, phone, department, role, scope, status, created_at FROM kipa_admins ORDER BY id ASC');
    $allAdmins = $adminListStmt ? $adminListStmt->fetchAll() : [];

    $csrf = kipa_h($_SESSION['csrf']);
    $tab = $_GET['tab'] ?? 'leads';
    $rolesMeta = kipa_roles_list();

    $tabLeadsClass = $tab === 'leads' ? 'nav-tab active' : 'nav-tab';
    $tabAccountsClass = $tab === 'accounts' ? 'nav-tab active' : 'nav-tab';
    $tabMatrixClass = $tab === 'roles_matrix' ? 'nav-tab active' : 'nav-tab';

    $tabNavigation = '<nav class="nav-tabs">
        <a class="' . $tabLeadsClass . '" href="/api/admin.php?tab=leads">📋 Đăng ký tư vấn (Leads) <span class="badge" style="background:' . ($tab === 'leads' ? 'rgba(255,255,255,0.3)' : '#e2e8f0') . ';color:' . ($tab === 'leads' ? '#fff' : '#334155') . '">' . $total . '</span></a>
        <a class="' . $tabAccountsClass . '" href="/api/admin.php?tab=accounts">👥 Quản lý Nhân sự & Phân quyền <span class="badge" style="background:' . ($tab === 'accounts' ? 'rgba(255,255,255,0.3)' : '#e2e8f0') . ';color:' . ($tab === 'accounts' ? '#fff' : '#334155') . '">' . count($allAdmins) . '</span></a>
        <a class="' . $tabMatrixClass . '" href="/api/admin.php?tab=roles_matrix">📊 Ma trận 8 Vai trò (RBAC)</a>
        <a class="nav-tab" href="/api/cms.php" target="_blank" style="margin-left:auto;color:#0274bd;">🌐 Quản trị CMS A–J ↗</a>
    </nav>';

    $headerTop = '<div class="top" style="margin-bottom:16px;">
        <div>
            <h1>Quản trị hệ thống KIPA</h1>
            <p class="muted" style="margin:0;">Đang đăng nhập: <strong>' . kipa_h((string)$_SESSION['admin_username']) . ' (' . kipa_h((string)($_SESSION['admin_role'] ?? 'super_admin')) . ')</strong> · ' . kipa_h((string)($_SESSION['admin_department'] ?? 'Ban Giám Đốc')) . '</p>
        </div>
        ' . kipa_admin_form_start('logout', $csrf) . '<button class="secondary" type="submit">Đăng xuất</button></form>
    </div>';

    $bannerHtml = '';
    if (isset($_GET['admin_created'])) {
        $bannerHtml .= '<p class="message success">✓ Đã tạo tài khoản nhân sự mới thành công và lưu trực tiếp vào CSDL!</p>';
    }
    if (isset($_GET['status_updated'])) {
        $bannerHtml .= '<p class="message success">✓ Đã cập nhật trạng thái hoạt động của tài khoản trong CSDL!</p>';
    }
    if (isset($_GET['deleted_admin'])) {
        $bannerHtml .= '<p class="message success">✓ Đã xóa tài khoản nhân sự khỏi CSDL!</p>';
    }
    if (isset($_GET['deleted'])) {
        $bannerHtml .= '<p class="message">Đã xóa đăng ký tư vấn.</p>';
    }
    if (isset($_GET['password_changed'])) {
        $bannerHtml .= '<p class="message success">✓ Đã đổi mật khẩu quản trị thành công.</p>';
    }

    $bodyHtml = '';
    if ($tab === 'accounts') {
        $adminRows = '';
        foreach ($allAdmins as $a) {
            $rKey = (string)($a['role'] ?? 'super_admin');
            $rInfo = $rolesMeta[$rKey] ?? ['title' => $rKey, 'desc' => '', 'color' => '#64748b'];
            $st = (string)($a['status'] ?? 'active');
            $stBadge = $st === 'active'
                ? '<span style="background:#dcfce7;color:#15803d;padding:4px 8px;border-radius:12px;font-size:11px;font-weight:700;">Hoạt động</span>'
                : '<span style="background:#fee2e2;color:#b91c1c;padding:4px 8px;border-radius:12px;font-size:11px;font-weight:700;">Đã khóa</span>';

            $actions = '';
            if ((int)$a['id'] !== (int)$_SESSION['admin_id']) {
                $toggleTxt = $st === 'active' ? 'Khóa' : 'Mở khóa';
                $toggleColor = $st === 'active' ? '#b91c1c' : '#15803d';
                $actions .= kipa_admin_form_start('toggle_admin_status', $csrf) .
                    '<input type="hidden" name="target_id" value="' . (int)$a['id'] . '">' .
                    '<button type="submit" style="background:' . $toggleColor . ';padding:4px 10px;font-size:12px;border-radius:5px;margin-right:6px;">' . $toggleTxt . '</button></form>';

                if (($_SESSION['admin_role'] ?? 'super_admin') === 'super_admin') {
                    $actions .= kipa_admin_form_start('delete_admin', $csrf) .
                        '<input type="hidden" name="target_id" value="' . (int)$a['id'] . '">' .
                        '<button type="submit" class="danger" style="padding:4px 10px;font-size:12px;border-radius:5px;" onclick="return confirm(\'Bạn có chắc chắn muốn xóa tài khoản này khỏi CSDL?\');">Xóa</button></form>';
                }
            } else {
                $actions = '<span style="color:#94a3b8;font-size:12px;">Đang đăng nhập</span>';
            }

            $adminRows .= '<tr>
                <td><strong>' . kipa_h((string)($a['full_name'] ?: $a['username'])) . '</strong><br><span class="muted">@' . kipa_h((string)$a['username']) . '</span></td>
                <td>' . kipa_h((string)($a['email'] ?? '—')) . '<br><span class="muted">' . kipa_h((string)($a['phone'] ?? '—')) . '</span></td>
                <td><strong>' . kipa_h((string)($a['department'] ?? 'Ban Giám Đốc')) . '</strong></td>
                <td><span style="background:' . $rInfo['color'] . '15;color:' . $rInfo['color'] . ';border:1px solid ' . $rInfo['color'] . '40;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:700;">' . kipa_h($rInfo['title']) . '</span></td>
                <td style="font-size:13px;color:#475569;">' . kipa_h((string)($a['scope'] ?? 'Toàn hệ thống')) . '</td>
                <td>' . $stBadge . '</td>
                <td style="white-space:nowrap;">' . $actions . '</td>
            </tr>';
        }

        $roleOpts = '';
        foreach ($rolesMeta as $k => $v) {
            $roleOpts .= '<option value="' . kipa_h($k) . '">' . kipa_h($v['title']) . ' — ' . kipa_h($v['desc']) . '</option>';
        }

        $bodyHtml .= '<div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:10px;padding:22px;margin-bottom:24px;">
            <h2 style="font-size:17px;margin:0 0 14px;color:#081d36;font-weight:700;">+ Tạo tài khoản Quản trị & Nhân sự mới</h2>
            ' . kipa_admin_form_start('create_admin', $csrf) . '
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;">
                <div>
                    <label>Tên đăng nhập (Username) *</label>
                    <input name="username" required minlength="3" maxlength="64" placeholder="Ví dụ: nguyenvana">
                </div>
                <div>
                    <label>Họ và tên nhân sự *</label>
                    <input name="full_name" required minlength="2" maxlength="160" placeholder="Ví dụ: Nguyễn Văn A">
                </div>
                <div>
                    <label>Email nhận OTP & Thông báo *</label>
                    <input name="email" type="email" required placeholder="nhansu@kipaviethan.edu.vn">
                </div>
                <div>
                    <label>Số điện thoại liên hệ</label>
                    <input name="phone" placeholder="Ví dụ: 0912 345 678">
                </div>
                <div>
                    <label>Phòng ban</label>
                    <select name="department">
                        <option value="Ban Giám Đốc">Ban Giám Đốc</option>
                        <option value="Tuyển sinh">Tuyển sinh</option>
                        <option value="Marketing">Marketing</option>
                        <option value="SEO">SEO & Kỹ thuật</option>
                        <option value="Đào tạo & Học vụ">Đào tạo & Học vụ</option>
                    </select>
                </div>
                <div>
                    <label>Vai trò hệ thống (Role) *</label>
                    <select name="role">' . $roleOpts . '</select>
                </div>
                <div>
                    <label>Phạm vi dữ liệu (Scope)</label>
                    <input name="scope" value="Toàn hệ thống" placeholder="Ví dụ: Du học, XKLĐ, Lead được giao...">
                </div>
                <div>
                    <label>Mật khẩu khởi tạo *</label>
                    <input name="password" type="text" value="Kipa@2026!pass" required minlength="8" maxlength="72">
                </div>
            </div>
            <div style="margin-top:14px;display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="mcp" name="must_change_password" value="1" checked style="width:auto;">
                <label for="mcp" style="font-size:13px;margin:0;font-weight:normal;color:#334155;">Bắt buộc nhân sự phải đổi mật khẩu ở lần đăng nhập đầu tiên</label>
            </div>
            <div style="margin-top:16px;">
                <button type="submit">+ Lưu và Tạo tài khoản vào CSDL</button>
            </div>
            </form>
        </div>

        <div>
            <h2 style="font-size:18px;margin:0 0 12px;color:#081d36;font-weight:700;">Danh sách Nhân sự & Phân quyền (' . count($allAdmins) . ' tài khoản)</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nhân sự</th>
                            <th>Email / SĐT</th>
                            <th>Phòng ban</th>
                            <th>Vai trò</th>
                            <th>Phạm vi dữ liệu</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>' . $adminRows . '</tbody>
                </table>
            </div>
        </div>';

    } elseif ($tab === 'roles_matrix') {
        $bodyHtml .= '<div>
            <h2 style="font-size:18px;margin:0 0 8px;color:#081d36;font-weight:700;">Ma trận Phân quyền 8 Vai trò chính (KIPA RBAC)</h2>
            <p style="color:#64748b;font-size:13px;margin-bottom:14px;">Quy tắc: <span style="background:#dcfce7;color:#15803d;padding:2px 6px;border-radius:4px;font-weight:bold;">✅ Được phép</span> | <span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:4px;font-weight:bold;">🟡 Có điều kiện / theo phạm vi</span> | <span style="background:#fee2e2;color:#b91c1c;padding:2px 6px;border-radius:4px;font-weight:bold;">🔒 Không được phép</span></p>
            <div class="table-wrap">
                <table style="font-size:13px;">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">Nhóm quyền / Chức năng</th>
                            <th>1. Super Admin</th>
                            <th>2. Website Mgr</th>
                            <th>3. Admissions Mgr</th>
                            <th>4. Admissions Cons</th>
                            <th>5. Content Mgr</th>
                            <th>6. Content & SEO</th>
                            <th>7. SEO Mgr</th>
                            <th>8. Academic Coord</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background:#f1f5f9;font-weight:bold;"><td colspan="9">P01. Dashboard & Báo cáo</td></tr>
                        <tr><td>Xem Dashboard</td><td>✅</td><td>✅</td><td>🟡</td><td>🟡</td><td>🟡</td><td>🟡</td><td>🟡</td><td>🟡</td></tr>
                        <tr><td>Xem Báo cáo Tuyển sinh/Lead</td><td>✅</td><td>🟡</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>

                        <tr style="background:#f1f5f9;font-weight:bold;"><td colspan="9">P11 & P12. Quản trị Tài khoản & Hệ thống</td></tr>
                        <tr><td>Xem tài khoản nhân viên</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>
                        <tr><td>Tạo tài khoản mới</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>
                        <tr><td>Khóa / Mở khóa tài khoản</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>
                        <tr><td>Cấu hình hệ thống</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>

                        <tr style="background:#f1f5f9;font-weight:bold;"><td colspan="9">P02, P03 & P04. Nội dung, Duyệt & Xuất bản</td></tr>
                        <tr><td>Tạo & Sửa bài viết</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td></tr>
                        <tr><td>Gửi bài duyệt</td><td>🟡</td><td>🟡</td><td>🔒</td><td>🔒</td><td>✅</td><td>✅</td><td>🟡</td><td>🔒</td></tr>
                        <tr><td>Duyệt / Từ chối bài</td><td>🟡</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>
                        <tr><td>Xuất bản bài (Publish)</td><td>🟡</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🟡</td><td>🔒</td><td>🟡</td><td>🔒</td></tr>
                        <tr><td>Xóa bài viết (vào Trash)</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>

                        <tr style="background:#f1f5f9;font-weight:bold;"><td colspan="9">P05 & P06. SEO Cơ bản & Kỹ thuật</td></tr>
                        <tr><td>Meta Title / Description</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🟡</td><td>✅</td><td>✅</td><td>🔒</td></tr>
                        <tr><td>Heading, Alt ảnh, Từ khóa</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🟡</td><td>✅</td><td>✅</td><td>🔒</td></tr>
                        <tr><td>Canonical, Schema, Robots</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>✅</td><td>🔒</td></tr>
                        <tr><td>Sitemap, Redirect 301/302</td><td>✅</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>✅</td><td>🔒</td></tr>

                        <tr style="background:#f1f5f9;font-weight:bold;"><td colspan="9">Tuyển sinh & Quản lý Khách hàng (Leads)</td></tr>
                        <tr><td>Xem Lead & Số ĐT</td><td>✅</td><td>🟡</td><td>🟡</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>
                        <tr><td>Phân chia / Chuyển Lead</td><td>🟡</td><td>🔒</td><td>✅</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>
                        <tr><td>Ghi chú & Lịch chăm sóc</td><td>🟡</td><td>🔒</td><td>🟡</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>
                        <tr><td>Xóa Lead</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td></tr>

                        <tr style="background:#f1f5f9;font-weight:bold;"><td colspan="9">Học viên & Điều phối Học vụ</td></tr>
                        <tr><td>Danh sách & Hồ sơ học viên</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🟡</td></tr>
                        <tr><td>Tiến độ Visa & Lớp tiếng Hàn</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🟡</td></tr>
                        <tr><td>Gửi thông báo cho học viên</td><td>🟡</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🔒</td><td>🟡</td></tr>
                    </tbody>
                </table>
            </div>
        </div>';

    } else { # Leads tab
        if ($leads === []) {
            $bodyHtml .= '<p>Chưa có đăng ký nào.</p>';
        } else {
            $bodyHtml .= '<div class="table-wrap"><table><thead><tr><th>Ngày gửi (UTC)</th><th>Biểu mẫu</th><th>Liên hệ</th><th>Nhu cầu</th><th>Chi tiết</th><th>Tác vụ</th></tr></thead><tbody>';
            foreach ($leads as $lead) {
                $details = json_decode($lead['details_json'], true);
                if (!is_array($details)) {
                    $details = [];
                }
                $program = (string)($lead['program'] ?? '');
                $contact = '<strong>' . kipa_h($lead['contact_name']) . '</strong><br>' . kipa_h($lead['phone']);
                if ($lead['email'] !== null) {
                    $contact .= '<br>' . kipa_h($lead['email']);
                }
                $detailsText = [];
                foreach ($details as $key => $value) {
                    $formatted = is_array($value) ? implode(', ', array_map('strval', $value)) : (string)$value;
                    $detailsText[] = (string)$key . ': ' . $formatted;
                }
                $bodyHtml .= '<tr><td>' . kipa_h($lead['created_at']) . '</td><td>' . kipa_h($lead['form_type']) .
                    '<br><span class="muted">' . kipa_h($lead['source_page']) . '</span></td><td>' . $contact .
                    '</td><td>' . kipa_h($program) . '</td><td><pre>' . kipa_h(implode("\n", $detailsText)) .
                    '</pre></td><td>' . kipa_admin_form_start('delete', $csrf) .
                    '<input type="hidden" name="lead_id" value="' . (int)$lead['id'] .
                    '"><button class="danger" type="submit">Xóa</button></form></td></tr>';
            }
            $bodyHtml .= '</tbody></table></div>';
        }
        if ($totalPages > 1) {
            $bodyHtml .= '<nav class="actions" aria-label="Phân trang đăng ký" style="margin-top:14px;">';
            if ($page > 1) {
                $bodyHtml .= '<a href="/api/admin.php?tab=leads&page=' . ($page - 1) . '">Trang trước</a>';
            }
            $bodyHtml .= '<span>Trang ' . $page . ' / ' . $totalPages . '</span>';
            if ($page < $totalPages) {
                $bodyHtml .= '<a href="/api/admin.php?tab=leads&page=' . ($page + 1) . '">Trang sau</a>';
            }
            $bodyHtml .= '</nav>';
        }
        $bodyHtml .= '<p class="muted" style="margin-top:14px;">Dữ liệu quá 12 tháng sẽ tự xóa khi trang quản trị hoặc biểu mẫu được sử dụng. Múi giờ hiển thị: UTC.</p>';
    }

    $content = '<section class="card">' . $headerTop . $tabNavigation . $bannerHtml . $bodyHtml . '</section>';

    if ($tab === 'leads') {
        $content .= '<section class="card"><h2>Đổi mật khẩu tài khoản</h2>' . kipa_admin_form_start('change_password', $csrf);
        $content .= '<label>Mật khẩu hiện tại<input name="old_password" type="password" required autocomplete="current-password"></label>';
        $content .= '<label>Mật khẩu mới (12–72 byte)<input name="new_password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>';
        $content .= '<label>Nhập lại mật khẩu mới<input name="new_password_confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>';
        $content .= '<p><button type="submit">Đổi mật khẩu</button></p></form></section>';
    }
    kipa_admin_page('Quản trị hệ thống KIPA', $content);
} catch (PDOException $error) {
    error_log('[kipa-admin] Database operation failed: ' . $error->getCode());
    http_response_code(503);
    kipa_admin_page('Tạm thời gián đoạn', '<section class="card"><h1>Trang quản trị đang tạm thời gián đoạn</h1><p>Không thể tải dữ liệu lúc này. Vui lòng thử lại sau.</p></section>');
} catch (RuntimeException $error) {
    error_log('[kipa-admin] Configuration failed.');
    http_response_code(503);
    kipa_admin_page('Chưa cấu hình', '<section class="card"><h1>Backend chưa được cấu hình</h1><p>Hãy hoàn tất thiết lập riêng trên hosting trước khi sử dụng.</p></section>');
}
