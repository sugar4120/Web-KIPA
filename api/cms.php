<?php
declare(strict_types=1);

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/content.php';
require __DIR__ . '/publish.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
kipa_admin_session();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /api/admin.php', true, 303);
    exit;
}

function kipa_cms_url(string $table, array $params = []): string
{
    return '/api/cms.php?' . http_build_query(['table' => $table, ...$params]);
}

function kipa_cms_scalar(array $source, string $key, string $default = ''): string
{
    $value = $source[$key] ?? $default;
    if (!is_string($value)) {
        throw new InvalidArgumentException('Tham số không hợp lệ: ' . $key);
    }
    return $value;
}

function kipa_cms_form(string $action, string $table, ?int $id = null): string
{
    return '<form method="post" action="/api/cms.php"><input type="hidden" name="csrf" value="' .
        kipa_h($_SESSION['csrf']) . '"><input type="hidden" name="action" value="' . kipa_h($action) .
        '"><input type="hidden" name="table" value="' . kipa_h($table) .
        '"><input type="hidden" name="id" value="' . ($id ?? '') . '">';
}

function kipa_cms_shell(string $title, string $body, string $active = ''): never
{
    $model = kipa_content_model();
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . kipa_h($title) . ' | KIPA CMS</title><style>
    *{box-sizing:border-box}body{margin:0;background:#f3f6fb;color:#14243a;font:15px/1.6 system-ui,sans-serif}
    a{color:#0274bd;text-decoration:none}a:hover{text-decoration:underline}header{background:#081d36;color:white;padding:18px 24px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
    header a{color:white}header strong{letter-spacing:.04em}.layout{display:grid;grid-template-columns:270px minmax(0,1fr);max-width:1600px;margin:auto}
    aside{padding:18px;background:white;border-right:1px solid #dfe7ef}aside details{margin-bottom:12px}aside summary{font-weight:700;cursor:pointer}aside a{display:block;font-size:13px;padding:5px 10px;border-radius:6px;overflow-wrap:anywhere}
    aside a.active{background:#e5f3fd;font-weight:700}main{padding:28px;min-width:0}h1{font-size:26px;margin:0 0 8px}h2{font-size:18px}p{margin:8px 0 16px}
    .card{background:white;border:1px solid #dfe7ef;border-radius:14px;padding:22px;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px}
    .field{display:block;margin-bottom:16px;font-weight:600;min-width:0}.field small{display:block;font-weight:400;color:#64748b;overflow-wrap:anywhere}input,textarea,select{width:100%;padding:10px;border:1px solid #bdcddb;border-radius:7px;font:inherit;background:white}
    textarea{min-height:130px}button,.button{display:inline-block;background:#0274bd;color:white;border:0;border-radius:8px;padding:10px 16px;font:inherit;font-weight:650;cursor:pointer}
    .danger{background:#b91c1c}.muted{color:#64748b;font-size:13px}.notice{padding:14px;border:1px solid #b5d5eb;border-radius:8px;background:#eef8ff;margin-bottom:18px}.error{background:#fff1f0;border-color:#efb3af;color:#9b1c16}
    .actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:14px 0}.scroll{overflow:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px;border-bottom:1px solid #e8eef5;vertical-align:top}td{overflow-wrap:anywhere}td:first-child{width:70px}
    @media(max-width:850px){.layout{grid-template-columns:1fr}aside{max-height:230px;overflow:auto;border-bottom:1px solid #dfe7ef}main{padding:18px}.card{padding:16px}}
    </style></head><body><header><strong>KIPA · QUẢN TRỊ DỮ LIỆU</strong><nav><a href="/api/admin.php">Đăng ký gốc / tài khoản</a> · <a href="/">Website</a></nav></header><div class="layout"><aside><a href="/api/cms.php">Tổng quan &amp; xuất bản</a>';
    foreach ($model['groups'] as $group => $label) {
        $open = $active && ($model['tables'][$active]['group'] ?? '') === $group;
        echo '<details' . ($open ? ' open' : '') . '><summary>' . $group . '. ' . kipa_h($label) . '</summary>';
        foreach ($model['tables'] as $table => $definition) {
            if ($definition['group'] === $group) {
                echo '<a class="' . ($table === $active ? 'active' : '') . '" href="' . kipa_h(kipa_cms_url($table)) . '">' . kipa_h($table) . '</a>';
            }
        }
        echo '</details>';
    }
    echo '</aside><main><h1>' . kipa_h($title) . '</h1>' . $body . '</main></div></body></html>';
    exit;
}

$table = '';
try {
    $db = kipa_db(kipa_config());
    $admin = kipa_current_admin($db);
    if (!$admin || (bool)$admin['must_change_password']) {
        header('Location: /api/admin.php', true, 303);
        exit;
    }
    if (!kipa_content_ready($db)) {
        http_response_code(503);
        kipa_cms_shell('Chưa khởi tạo dữ liệu nội dung', '<p class="notice">Cần sao lưu database và chạy migration bằng PHP CLI trước khi sử dụng CMS. Form đăng ký cũ vẫn hoạt động.</p>');
    }
    $table = isset($_POST['table']) ? kipa_cms_scalar($_POST, 'table') : kipa_cms_scalar($_GET, 'table');
    $model = kipa_content_model();
    if ($table !== '') {
        kipa_content_table($table);
        if (!isset($model['tables'][$table])) {
            throw new InvalidArgumentException('Dùng trang quản trị tài khoản hoặc đăng ký gốc cho bảng này.');
        }
    }
    $idValue = $_POST['id'] ?? $_GET['id'] ?? null;
    $id = $idValue !== null && $idValue !== '' ? filter_var($idValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
    if ($id === false) {
        throw new InvalidArgumentException('ID không hợp lệ.');
    }
    $action = kipa_cms_scalar($_POST, 'action');
    $readOnly = in_array($table, ['form_submissions', 'post_revisions', 'page_revisions'], true);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        kipa_require_origin();
        kipa_check_csrf();
        if ($action === 'publish') {
            $published = kipa_publish($db, dirname(__DIR__));
            kipa_cms_shell('Đã xuất bản HTML tĩnh', '<div class="notice">Đã xuất bản ' . $published .
                ' trang. Không có dữ liệu khách hàng trong nội dung công khai.</div><p><a class="button" href="/noi-dung/">Xem nội dung website</a></p>');
        }
        if ($table === '' || $readOnly) {
            throw new InvalidArgumentException('Bảng chỉ đọc hoặc chưa được chọn.');
        }
        if ($action === 'save') {
            $input = $_POST['fields'] ?? null;
            if (!is_array($input)) {
                throw new InvalidArgumentException('Thiếu nội dung biểu mẫu.');
            }
            $db->beginTransaction();
            if ($id !== null) {
                $query = $db->prepare('SELECT * FROM ' . kipa_content_table($table) . ' WHERE id = ? FOR UPDATE');
                $query->execute([$id]);
                $old = $query->fetch();
                if (!$old || !hash_equals(hash('sha256', json_encode($old, JSON_THROW_ON_ERROR)), kipa_cms_scalar($_POST, 'revision'))) {
                    throw new InvalidArgumentException('Bản ghi đã thay đổi hoặc không còn tồn tại. Hãy tải lại trước khi sửa.');
                }
            }
            kipa_content_save($db, $table, $input, $id);
            if ($id !== null && in_array($table, ['posts', 'pages'], true)) {
                $revisionTable = $table === 'posts' ? 'post_revisions' : 'page_revisions';
                $foreignKey = $table === 'posts' ? 'post_id' : 'page_id';
                $titleKey = $table === 'posts' ? 'title' : 'page_title';
                $number = $db->prepare('SELECT COALESCE(MAX(revision_number), 0) + 1 FROM ' . kipa_content_table($revisionTable) . " WHERE `$foreignKey` = ?");
                $number->execute([$id]);
                $revision = $db->prepare('INSERT INTO ' . kipa_content_table($revisionTable) . " (`$foreignKey`, revision_number, `$titleKey`, content, changed_by, change_note, created_at) VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())");
                $revision->execute([$id, (int)$number->fetchColumn(), $old[$titleKey], $old['content'], $_SESSION['admin_id'], 'Trước khi cập nhật từ CMS']);
            }
            if ($id !== null && $table === 'consultation_requests' && $old['status'] !== ($input['status'] ?? 'new')) {
                $activity = $db->prepare('INSERT INTO kipa_consultation_activities (consultation_request_id, activity_type, activity_content, old_status, new_status, performed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())');
                $activity->execute([$id, 'status_change', 'Cập nhật trạng thái từ CMS', $old['status'], $input['status'] ?? 'new', $_SESSION['admin_id']]);
            }
            $db->commit();
            header('Location: ' . kipa_cms_url($table, ['saved' => '1']), true, 303);
            exit;
        }
        if ($action === 'delete' && $id !== null) {
            kipa_cms_shell('Xác nhận xóa', '<section class="card"><p>Xóa bản ghi #' . $id .
                '? Dữ liệu đang được bảng khác sử dụng sẽ không được xóa.</p>' . kipa_cms_form('confirm_delete', $table, $id) .
                '<button class="danger">Xác nhận xóa</button></form><p><a href="' . kipa_h(kipa_cms_url($table)) . '">Hủy</a></p></section>', $table);
        }
        if ($action === 'confirm_delete' && $id !== null) {
            $legacyId = null;
            if (isset($model['tables'][$table]['fields']['legacy_lead_id'])) {
                $query = $db->prepare('SELECT legacy_lead_id FROM ' . kipa_content_table($table) . ' WHERE id = ?');
                $query->execute([$id]);
                $legacyId = $query->fetchColumn();
            }
            $query = $db->prepare('DELETE FROM ' . ($legacyId ? 'kipa_leads' : kipa_content_table($table)) . ' WHERE id = ?');
            $query->execute([$legacyId ?: $id]);
            if ($query->rowCount() !== 1) {
                throw new InvalidArgumentException('Không tìm thấy bản ghi cần xóa.');
            }
            header('Location: ' . kipa_cms_url($table, ['deleted' => '1']), true, 303);
            exit;
        }
        throw new InvalidArgumentException('Thao tác không hợp lệ.');
    }
    if ($table === '') {
        $body = '<p class="muted">Dữ liệu tập trung theo 10 nhóm A–J · MySQL / HTML tĩnh · Không dùng WordPress</p>';
        $body .= '<div class="notice"><strong>Lưu nháp ≠ xuất bản.</strong> Chỉ bản ghi có trạng thái published mới được xuất bản. Nội dung học viên còn cần xác nhận đồng ý công khai. Chuyển về trạng thái khác hoặc xóa cần xuất bản lại để gỡ khỏi website.</div>';
        $body .= '<section class="card"><h2>Xuất bản website</h2><p>Nội dung được tạo tại <a href="/noi-dung/">/noi-dung/</a>; bài viết tại /tin-tuc/. Các trang thiết kế sẵn vẫn giữ nguyên. Menu, cấu hình và banner áp dụng cho các trang CMS, chưa thay thế giao diện các trang cũ.</p>' .
            kipa_cms_form('publish', '') . '<button>Xuất bản HTML tĩnh</button></form></section><div class="grid">';
        foreach ($model['groups'] as $group => $label) {
            $names = array_filter($model['tables'], fn($definition) => $definition['group'] === $group);
            $body .= '<section class="card"><h2>' . $group . '. ' . kipa_h($label) . '</h2><p class="muted">' . count($names) . ' bảng dữ liệu</p>';
            foreach ($names as $name => $definition) {
                $body .= '<a href="' . kipa_h(kipa_cms_url($name)) . '">' . kipa_h($name) . '</a><br>';
            }
            $body .= '</section>';
        }
        kipa_cms_shell('Trung tâm quản trị KIPA', $body . '</div>');
    }
    $definition = $model['tables'][$table];
    if (isset($_GET['new']) || $id !== null) {
        if ($readOnly) {
            throw new InvalidArgumentException('Bảng này chỉ đọc.');
        }
        $row = [];
        if ($id !== null) {
            $query = $db->prepare('SELECT * FROM ' . kipa_content_table($table) . ' WHERE id = ?');
            $query->execute([$id]);
            $row = $query->fetch();
            if (!$row) {
                throw new InvalidArgumentException('Không tìm thấy bản ghi.');
            }
        }
        $body = '<p><a href="' . kipa_h(kipa_cms_url($table)) . '">← Danh sách</a></p><p class="muted">Trường ngày giờ dùng UTC (giờ Việt Nam = UTC + 7). Nội dung dạng văn bản thuần; không chèn mã HTML.</p><section class="card">' . kipa_cms_form('save', $table, $id);
        if (isset($definition['one_of'])) {
            $body .= '<p class="notice">Chọn đúng một trong các liên kết: study_program_id, work_program_id hoặc course_id. Không chọn nhiều loại đối tượng cho cùng bản ghi.</p>';
        }
        $body .= '<input type="hidden" name="revision" value="' . hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)) . '"><div class="grid">';
        foreach ($definition['fields'] as $name => $field) {
            if (in_array($name, ['id', 'created_at', 'updated_at', 'legacy_lead_id'], true)) {
                continue;
            }
            $value = (string)($row[$name] ?? $field['default'] ?? '');
            $required = $field['required'] ? ' required' : '';
            $attr = ' name="fields[' . $name . ']"' . $required;
            $body .= '<label class="field">' . kipa_h($name) . ($required ? ' *' : '') . '<small>' . kipa_h($field['label']) . '</small>';
            if (isset($field['choices'])) {
                $body .= '<select' . $attr . '>';
                if (!$required) {
                    $body .= '<option value="">Chưa chọn</option>';
                }
                foreach ($field['choices'] as $choice) {
                    $body .= '<option value="' . kipa_h($choice) . '"' . ($choice === $value ? ' selected' : '') . '>' . kipa_h($choice) . '</option>';
                }
                $body .= '</select>';
            } elseif ($field['type'] === 'BOOLEAN') {
                $body .= '<select' . $attr . '><option value="0"' . ($value !== '1' ? ' selected' : '') . '>Không</option><option value="1"' . ($value === '1' ? ' selected' : '') . '>Có</option></select>';
            } elseif (isset($field['ref'])) {
                $body .= '<input type="number" min="1" step="1" list="ref-' . $name . '"' . $attr . ' value="' . kipa_h($value) . '"><datalist id="ref-' . $name . '">';
                $options = $db->query('SELECT * FROM ' . kipa_content_table($field['ref']) . ' ORDER BY id DESC LIMIT 300')->fetchAll();
                foreach ($options as $option) {
                    $body .= '<option value="' . (int)$option['id'] . '">' . kipa_h(kipa_content_title($option)) . '</option>';
                }
                $body .= '</datalist><small>Nhập ID thuộc ' . kipa_h($field['ref']) . '; gợi ý 300 bản ghi gần nhất.</small>';
            } elseif (in_array($field['type'], ['TEXT', 'LONGTEXT', 'JSON'], true)) {
                $body .= '<textarea' . $attr . '>' . kipa_h($value) . '</textarea><small>' .
                    ($field['type'] === 'JSON' ? 'Nhập JSON hợp lệ.' : 'Văn bản thuần; mã HTML không được thực thi.') . '</small>';
            } else {
                $type = in_array($field['type'], ['DATE', 'TIME'], true) ? strtolower($field['type']) :
                    (in_array($field['type'], ['DATETIME', 'TIMESTAMP'], true) ? 'datetime-local' : 'text');
                $maxlength = preg_match('/^(?:VAR)?CHAR\((\d+)\)$/', $field['type'], $match) ? ' maxlength="' . $match[1] . '"' : '';
                $body .= '<input type="' . $type . '"' . $attr . $maxlength . ' value="' . kipa_h($value) . '">';
            }
            $body .= '</label>';
        }
        $body .= '</div><button>Lưu dữ liệu</button><p class="muted">Các thay đổi chưa xuất hiện công khai cho tới khi xuất bản.</p></form></section>';
        kipa_cms_shell(($id ? 'Sửa #' . $id : 'Thêm mới') . ' · ' . $table, $body, $table);
    }
    $page = filter_var(kipa_cms_scalar($_GET, 'page', '1'), FILTER_VALIDATE_INT);
    $page = $page === false ? 1 : max(1, $page);
    $search = trim(kipa_cms_scalar($_GET, 'q'));
    if (kipa_text_length($search) > 200) {
        throw new InvalidArgumentException('Từ khóa tìm kiếm quá dài.');
    }
    $searchFields = array_keys(array_filter($definition['fields'], fn($field) => str_starts_with($field['type'], 'VARCHAR') || $field['type'] === 'TEXT'));
    $where = $search !== '' && $searchFields ? ' WHERE ' . implode(' OR ', array_map(fn($name) => '`' . $name . '` LIKE ?', $searchFields)) : '';
    $params = $where !== '' ? array_fill(0, count($searchFields), '%' . $search . '%') : [];
    $count = $db->prepare('SELECT COUNT(*) FROM ' . kipa_content_table($table) . $where);
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $pages = max(1, (int)ceil($total / 50));
    $page = min($page, $pages);
    $query = $db->prepare('SELECT * FROM ' . kipa_content_table($table) . $where . ' ORDER BY id DESC LIMIT 50 OFFSET ' . (($page - 1) * 50));
    $query->execute($params);
    $body = '<p class="muted">' . kipa_h($definition['label']) . ' · ' . $total . ' bản ghi</p>';
    if (isset($_GET['saved']) || isset($_GET['deleted'])) {
        $body .= '<p class="notice">Đã cập nhật dữ liệu. Xuất bản lại nếu thay đổi liên quan tới nội dung công khai.</p>';
    }
    $body .= '<div class="actions">' . (!$readOnly ? '<a class="button" href="' . kipa_h(kipa_cms_url($table, ['new' => 1])) . '">Thêm mới</a>' : '') .
        '<form method="get" action="/api/cms.php"><input type="hidden" name="table" value="' . kipa_h($table) . '"><input name="q" value="' . kipa_h($search) .
        '" placeholder="Tìm trong dữ liệu"><button>Tìm kiếm</button></form></div><section class="card scroll"><table><thead><tr><th>ID</th><th>Nội dung</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>';
    foreach ($query->fetchAll() as $row) {
        $body .= '<tr><td>#' . (int)$row['id'] . '</td><td>' . kipa_h(kipa_content_title($row)) . '</td><td>' .
            kipa_h((string)($row['status'] ?? $row['publication_status'] ?? '—')) . '</td><td>';
        if (!$readOnly) {
            $body .= '<a href="' . kipa_h(kipa_cms_url($table, ['id' => $row['id']])) . '">Sửa</a>' .
                kipa_cms_form('delete', $table, (int)$row['id']) . '<button class="danger">Xóa</button></form>';
        } else {
            $body .= '<details><summary>Xem dữ liệu</summary><pre>' . kipa_h(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) . '</pre></details>';
        }
        $body .= '</td></tr>';
    }
    $body .= '</tbody></table><nav class="actions">';
    if ($page > 1) {
        $body .= '<a href="' . kipa_h(kipa_cms_url($table, ['page' => $page - 1, 'q' => $search])) . '">Trang trước</a>';
    }
    $body .= 'Trang ' . $page . ' / ' . $pages;
    if ($page < $pages) {
        $body .= '<a href="' . kipa_h(kipa_cms_url($table, ['page' => $page + 1, 'q' => $search])) . '">Trang sau</a>';
    }
    kipa_cms_shell($table, $body . '</nav></section>', $table);
} catch (InvalidArgumentException $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(422);
    kipa_cms_shell('Không thể lưu dữ liệu', '<p class="notice error">' . kipa_h($error->getMessage()) .
        '</p><p><a href="' . kipa_h(kipa_cms_url($table)) . '">Quay lại danh sách</a></p>', $table);
} catch (PDOException $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-cms] Database failure: ' . $error->getCode());
    http_response_code(503);
    kipa_cms_shell('Chưa thể cập nhật', '<p class="notice error">' .
        ($error->getCode() === '23000' ? 'Dữ liệu trùng hoặc đang có liên kết. Kiểm tra slug, mã và các bản ghi liên quan.' : 'Database tạm thời không xử lý được yêu cầu. Thử lại sau.') . '</p>', $table);
} catch (RuntimeException | JsonException $error) {
    error_log('[kipa-cms] Operation failed: ' . $error->getMessage());
    http_response_code(503);
    kipa_cms_shell('Thao tác chưa hoàn thành', '<p class="notice error">Không thể hoàn thành thao tác. Dữ liệu chưa được xác nhận xuất bản. Vui lòng kiểm tra nhật ký hosting.</p>', $table);
}
