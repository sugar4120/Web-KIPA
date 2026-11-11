<?php
declare(strict_types=1);

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/bootstrap.php';

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
        main{max-width:1100px;margin:32px auto;padding:0 16px}.card{background:#fff;border:1px solid #dbe3ec;border-radius:12px;padding:20px;margin-bottom:18px}
        h1{font-size:24px;margin:0 0 16px}h2{font-size:18px;margin:0 0 12px}.muted{color:#64748b;font-size:13px}
        label{display:block;font-weight:600;margin:10px 0 4px}input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px}
        button{border:0;border-radius:7px;background:#0274bd;color:#fff;padding:10px 14px;font-weight:700;cursor:pointer}
        button.danger{background:#b91c1c}button.secondary{background:#334155}.actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .message{padding:10px;background:#fff7ed;border:1px solid #fed7aa;border-radius:7px;margin:12px 0}
        .table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:800px}th,td{text-align:left;vertical-align:top;padding:10px;border-bottom:1px solid #e2e8f0}
        pre{white-space:pre-wrap;overflow-wrap:anywhere;max-width:420px;font:12px/1.5 ui-monospace,monospace;margin:0}
        form.inline{display:inline}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        @media(max-width:600px){main{margin:16px auto} .card{padding:14px}h1{font-size:21px}}
    </style></head><body><main>' . $content . '</main></body></html>';
    exit;
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
            $statement = $db->prepare('SELECT id, username, password_hash, must_change_password FROM kipa_admins WHERE username = :username LIMIT 1');
            $statement->execute(['username' => $username]);
            $admin = $statement->fetch();
            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                kipa_admin_error('Tên đăng nhập hoặc mật khẩu không đúng.');
            }
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['must_change_password'] = (bool)$admin['must_change_password'];
            header('Location: /api/admin.php', true, 303);
            exit;
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
        $content = '<section class="card"><h1>Quản trị đăng ký tư vấn KIPA</h1>';
        if (kipa_rate_limited($db, 'login', $remoteKey, 8, 15)) {
            $content .= '<p class="message">Đăng nhập quá nhiều lần. Vui lòng thử lại sau.</p>';
        } else {
            $content .= kipa_admin_form_start('login', $csrf);
            $content .= '<label>Tên đăng nhập<input name="username" required maxlength="64" autocomplete="username"></label>';
            $content .= '<label>Mật khẩu<input name="password" type="password" required autocomplete="current-password"></label>';
            $content .= '<p><button type="submit">Đăng nhập</button></p></form>';
        }
        $content .= '<p class="muted">Chỉ tài khoản được ủy quyền mới có thể xem thông tin đăng ký.</p></section>';
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

    $csrf = kipa_h($_SESSION['csrf']);
    $content = '<section class="card"><div class="top"><div><h1>Đăng ký tư vấn KIPA</h1><p class="muted">Đang đăng nhập: ' .
        kipa_h((string)$_SESSION['admin_username']) . ' · ' . $total . ' đăng ký</p></div>';
    $content .= '<a href="/api/cms.php">Quản trị dữ liệu A–J</a>' . kipa_admin_form_start('logout', $csrf) . '<button class="secondary" type="submit">Đăng xuất</button></form></div>';
    if (isset($_GET['deleted'])) {
        $content .= '<p class="message">Đã xóa đăng ký.</p>';
    }
    if (isset($_GET['password_changed'])) {
        $content .= '<p class="message">Đã đổi mật khẩu quản trị.</p>';
    }
    if ($leads === []) {
        $content .= '<p>Chưa có đăng ký nào.</p>';
    } else {
        $content .= '<div class="table-wrap"><table><thead><tr><th>Ngày gửi (UTC)</th><th>Biểu mẫu</th><th>Liên hệ</th><th>Nhu cầu</th><th>Chi tiết</th><th>Tác vụ</th></tr></thead><tbody>';
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
            $content .= '<tr><td>' . kipa_h($lead['created_at']) . '</td><td>' . kipa_h($lead['form_type']) .
                '<br><span class="muted">' . kipa_h($lead['source_page']) . '</span></td><td>' . $contact .
                '</td><td>' . kipa_h($program) . '</td><td><pre>' . kipa_h(implode("\n", $detailsText)) .
                '</pre></td><td>' . kipa_admin_form_start('delete', $csrf) .
                '<input type="hidden" name="lead_id" value="' . (int)$lead['id'] .
                '"><button class="danger" type="submit">Xóa</button></form></td></tr>';
        }
        $content .= '</tbody></table></div>';
    }
    if ($totalPages > 1) {
        $content .= '<nav class="actions" aria-label="Phân trang đăng ký">';
        if ($page > 1) {
            $content .= '<a href="/api/admin.php?page=' . ($page - 1) . '">Trang trước</a>';
        }
        $content .= '<span>Trang ' . $page . ' / ' . $totalPages . '</span>';
        if ($page < $totalPages) {
            $content .= '<a href="/api/admin.php?page=' . ($page + 1) . '">Trang sau</a>';
        }
        $content .= '</nav>';
    }
    $content .= '<p class="muted">Dữ liệu quá 12 tháng sẽ tự xóa khi trang quản trị hoặc biểu mẫu được sử dụng. Múi giờ hiển thị: UTC.</p></section>';
    $content .= '<section class="card"><h2>Đổi mật khẩu</h2>' . kipa_admin_form_start('change_password', $csrf);
    $content .= '<label>Mật khẩu hiện tại<input name="old_password" type="password" required autocomplete="current-password"></label>';
    $content .= '<label>Mật khẩu mới (12–72 byte)<input name="new_password" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>';
    $content .= '<label>Nhập lại mật khẩu mới<input name="new_password_confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password"></label>';
    $content .= '<p><button type="submit">Đổi mật khẩu</button></p></form></section>';
    kipa_admin_page('Đăng ký tư vấn', $content);
} catch (PDOException $error) {
    error_log('[kipa-admin] Database operation failed: ' . $error->getCode());
    http_response_code(503);
    kipa_admin_page('Tạm thời gián đoạn', '<section class="card"><h1>Trang quản trị đang tạm thời gián đoạn</h1><p>Không thể tải dữ liệu lúc này. Vui lòng thử lại sau.</p></section>');
} catch (RuntimeException $error) {
    error_log('[kipa-admin] Configuration failed.');
    http_response_code(503);
    kipa_admin_page('Chưa cấu hình', '<section class="card"><h1>Backend chưa được cấu hình</h1><p>Hãy hoàn tất thiết lập riêng trên hosting trước khi sử dụng.</p></section>');
}
