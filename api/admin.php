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
        'super_admin' => [
            'code' => '01',
            'title' => 'Super Admin',
            'badge' => 'Toàn quyền hệ thống',
            'desc' => 'Quản trị hệ thống toàn quyền, quản trị tài khoản nhân sự, phân quyền kỹ thuật và nhật ký bảo mật.',
            'color' => '#dc2626',
            'bg_color' => '#fef2f2',
            'border_color' => '#fecaca',
            'scope_default' => 'Toàn hệ thống'
        ],
        'website_manager' => [
            'code' => '02',
            'title' => 'Website Manager',
            'badge' => 'Vận hành Website & CMS',
            'desc' => 'Quản lý vận hành toàn bộ Website, duyệt nội dung và quản lý chuyên mục trong phạm vi Super Admin giao.',
            'color' => '#0284c7',
            'bg_color' => '#f0f9ff',
            'border_color' => '#bae6fd',
            'scope_default' => 'Toàn Website'
        ],
        'admissions_manager' => [
            'code' => '03',
            'title' => 'Admissions Manager',
            'badge' => 'Trưởng Tuyển sinh & Lead',
            'desc' => 'Quản lý bộ phận tuyển sinh, phân chia và điều phối Lead khách hàng, giám sát tỷ lệ chuyển đổi.',
            'color' => '#059669',
            'bg_color' => '#ecfdf5',
            'border_color' => '#a7f3d0',
            'scope_default' => 'Toàn bộ Lead'
        ],
        'admissions_consultant' => [
            'code' => '04',
            'title' => 'Admissions Consultant',
            'badge' => 'Tư vấn viên & CSKH',
            'desc' => 'Tư vấn tuyển sinh trực tiếp, chăm sóc khách hàng, cập nhật tình trạng và lịch hẹn Lead được phân công.',
            'color' => '#10b981',
            'bg_color' => '#f0fdf4',
            'border_color' => '#bbf7d0',
            'scope_default' => 'Lead được giao'
        ],
        'content_manager' => [
            'code' => '05',
            'title' => 'Content Manager',
            'badge' => 'Quản lý Nội dung & Biên tập',
            'desc' => 'Quản trị nội dung bài viết, biên tập tin tức, kiểm duyệt bài viết và quản lý lịch xuất bản.',
            'color' => '#d97706',
            'bg_color' => '#fffbeb',
            'border_color' => '#fde68a',
            'scope_default' => 'Marketing & Nội dung'
        ],
        'content_seo_executive' => [
            'code' => '06',
            'title' => 'Content & SEO Executive',
            'badge' => 'Biên soạn & SEO Onpage',
            'desc' => 'Soạn thảo bài viết theo phân công, tối ưu SEO Onpage cơ bản (Title, Heading, Alt ảnh) và gửi duyệt.',
            'color' => '#f59e0b',
            'bg_color' => '#fffbeb',
            'border_color' => '#fef3c7',
            'scope_default' => 'Du học'
        ],
        'seo_manager' => [
            'code' => '07',
            'title' => 'SEO Manager',
            'badge' => 'SEO Chiến lược & Kỹ thuật',
            'desc' => 'Quản trị SEO kỹ thuật nâng cao (Canonical, Robots, Sitemap XML, Schema, Redirect 301) và tối ưu từ khóa.',
            'color' => '#7c3aed',
            'bg_color' => '#f5f3ff',
            'border_color' => '#ddd6fe',
            'scope_default' => 'Toàn Website'
        ],
        'academic_coordinator' => [
            'code' => '08',
            'title' => 'Academic & Student Coordinator',
            'badge' => 'Quản lý Học viên & Học vụ',
            'desc' => 'Quản lý danh sách học viên, hồ sơ du học/XKLĐ, lịch học tiếng Hàn, theo dõi tiến độ Visa và điều phối học vụ.',
            'color' => '#2563eb',
            'bg_color' => '#eff6ff',
            'border_color' => '#bfdbfe',
            'scope_default' => 'Đào tạo & Học vụ'
        ],
    ];
}

function kipa_avatar_meta(array $admin): array
{
    $username = trim((string)($admin['username'] ?? ''));
    $fullName = trim((string)($admin['full_name'] ?? $username));
    $parts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY);
    if ($parts && count($parts) >= 2) {
        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last = mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');
        $initials = mb_strtoupper($first . $last, 'UTF-8');
    } elseif (mb_strlen($fullName, 'UTF-8') >= 2) {
        $initials = mb_strtoupper(mb_substr($fullName, 0, 2, 'UTF-8'), 'UTF-8');
    } else {
        $initials = mb_strtoupper(mb_substr($username, 0, 2, 'UTF-8'), 'UTF-8');
    }

    if ($username === 'admin') {
        return ['SA', 'linear-gradient(135deg, #ef4444, #991b1b)', '#ffffff'];
    } elseif ($username === 'nguyena') {
        return ['NA', 'linear-gradient(135deg, #f59e0b, #d97706)', '#ffffff'];
    } elseif ($username === 'nguyenb') {
        return ['NB', 'linear-gradient(135deg, #ea580c, #c2410c)', '#ffffff'];
    } elseif ($username === 'nguyenc') {
        return ['NC', 'linear-gradient(135deg, #8b5cf6, #6d28d9)', '#ffffff'];
    } elseif ($username === 'nguyend') {
        return ['ND', 'linear-gradient(135deg, #10b981, #047857)', '#ffffff'];
    }

    $colors = [
        ['linear-gradient(135deg, #3b82f6, #1d4ed8)', '#ffffff'],
        ['linear-gradient(135deg, #06b6d4, #0891b2)', '#ffffff'],
        ['linear-gradient(135deg, #ec4899, #be185d)', '#ffffff'],
        ['linear-gradient(135deg, #14b8a6, #0f766e)', '#ffffff'],
    ];
    $idx = abs(crc32($username)) % count($colors);
    return [$initials, $colors[$idx][0], $colors[$idx][1]];
}

function kipa_permission_sections(): array
{
    return [
        [
            'group_code' => 'P01',
            'group_title' => '1. Dashboard & Báo cáo Tổng quan',
            'rows' => [
                ['Xem Dashboard', ['✅', '✅', '🟡', '🟡', '🟡', '🟡', '🟡', '🟡']],
                ['Xem Báo cáo Tuyển sinh / Lead', ['✅', '🟡', '✅', '🟡', '🔒', '🔒', '🔒', '🔒']],
                ['Xuất dữ liệu báo cáo thống kê', ['✅', '🟡', '✅', '🔒', '🔒', '🔒', '🟡', '🔒']],
            ]
        ],
        [
            'group_code' => 'P11_P12',
            'group_title' => '2. Quản trị Hệ thống, Tài khoản & Phân quyền',
            'rows' => [
                ['Xem tài khoản nhân viên', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Tạo tài khoản mới', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Sửa thông tin tài khoản', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Khóa / Mở tài khoản', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Gán vai trò nhân sự', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Tạo vai trò mới', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Chỉnh sửa nhóm quyền', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Phân quyền nâng cao', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Xem nhật ký hệ thống (Audit log)', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Cấu hình hệ thống', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
            ]
        ],
        [
            'group_code' => 'P02_P03_P04',
            'group_title' => '3. Quản lý Nội dung, Duyệt & Xuất bản (CMS)',
            'rows' => [
                ['Xem nội dung Website', ['✅', '✅', '🔒', '🔒', '🟡', '🟡', '🟡', '🔒']],
                ['Tạo bài viết mới', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🔒', '🔒']],
                ['Sửa bài viết', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🔒', '🔒']],
                ['Xóa nội dung (vào Trash)', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🔒', '🔒']],
                ['Xem nội dung theo phòng ban', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🔒', '🔒']],
                ['Quản lý nội dung được phân công', ['✅', '🟡', '🔒', '🔒', '✅', '✅', '🔒', '🔒']],
                ['Quản lý nội dung nhiều phòng ban', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🔒', '🔒']],
                ['Tạo trang nội dung (Pages)', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🔒', '🔒']],
                ['Chỉnh sửa trang hiện có', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🔒', '🔒']],
                ['Quản lý tin tức & sự kiện', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🔒', '🔒']],
                ['Quản lý bài hoạt động học viên', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🔒', '🔒']],
                ['Quản lý bài tuyển sinh & chương trình', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🔒', '🔒']],
                ['Gửi bài duyệt', ['🟡', '🟡', '🔒', '🔒', '🟡', '✅', '🟡', '🔒']],
                ['Duyệt bài viết', ['✅', '🟡', '🔒', '🔒', '✅', '🔒', '🔒', '🔒']],
                ['Từ chối bài viết', ['✅', '🟡', '🔒', '🔒', '✅', '🔒', '🔒', '🔒']],
                ['Trả lại yêu cầu chỉnh sửa', ['✅', '🟡', '🔒', '🔒', '✅', '🔒', '🔒', '🔒']],
                ['Xuất bản trực tiếp (Publish)', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']],
                ['Lên lịch xuất bản bài', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']],
                ['Gỡ bài viết đã xuất bản', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']],
                ['Khôi phục nội dung từ thùng rác', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🔒', '🔒']],
                ['Xóa vĩnh viễn nội dung', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
            ]
        ],
        [
            'group_code' => 'P05',
            'group_title' => '4. SEO Cơ bản (Basic Onpage SEO)',
            'rows' => [
                ['Chỉnh sửa Meta Title', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']],
                ['Chỉnh sửa Meta Description', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']],
                ['Chỉnh sửa Slug / Đường dẫn URL', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '✅', '🔒']],
                ['Tối ưu Heading H1, H2, H3', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']],
                ['Tối ưu Alt Text hình ảnh', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']],
                ['Gán từ khóa chính & từ khóa phụ', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']],
                ['Kiểm tra điểm chất lượng Onpage', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']],
            ]
        ],
        [
            'group_code' => 'P06',
            'group_title' => '5. SEO Nâng cao & Kỹ thuật (Technical SEO)',
            'rows' => [
                ['Cấu hình thẻ Canonical URL', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']],
                ['Cấu hình Robots Meta (noindex, nofollow)', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']],
                ['Quản lý chuyển hướng Redirect 301/302', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']],
                ['Cấu hình Schema Structured Data', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']],
                ['Quản lý Sitemap XML', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']],
                ['Theo dõi thu thập dữ liệu & Search Console', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']],
                ['Audit kỹ thuật toàn diện Website', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']],
            ]
        ],
        [
            'group_code' => 'P07_P08_P09',
            'group_title' => '6. Danh mục, Thư viện Media & Landing Page',
            'rows' => [
                ['Xem danh mục chuyên đề', ['✅', '✅', '🔒', '🔒', '🟡', '🟡', '✅', '🔒']],
                ['Tạo danh mục mới', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']],
                ['Sửa danh mục chuyên đề', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']],
                ['Xóa danh mục chuyên đề', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Tải file ảnh/video lên thư viện', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🟡', '🔒']],
                ['Tối ưu nén dung lượng hình ảnh', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '✅', '🔒']],
                ['Xóa file trong thư viện Media', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🔒', '🔒']],
                ['Tạo Landing Page chiến dịch', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🟡', '🔒']],
                ['Chỉnh sửa Landing Page', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🟡', '🔒']],
                ['Xuất bản Landing Page', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']],
                ['Gắn mã Tracking Pixel / GTM trên Landing Page', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']],
            ]
        ],
        [
            'group_code' => 'P10',
            'group_title' => '7. Marketing, Tuyển sinh & Lead Khách hàng',
            'rows' => [
                ['Xem danh sách Lead & Số ĐT', ['✅', '🟡', '✅', '🟡', '🔒', '🔒', '🔒', '🔒']],
                ['Phân chia Lead cho tư vấn viên', ['🟡', '🔒', '✅', '🔒', '🔒', '🔒', '🔒', '🔒']],
                ['Nhận Lead được phân công', ['🟡', '🔒', '✅', '✅', '🔒', '🔒', '🔒', '🔒']],
                ['Cập nhật trạng thái & ghi chú chăm sóc', ['🟡', '🔒', '✅', '✅', '🔒', '🔒', '🔒', '🔒']],
                ['Xem báo cáo tỷ lệ chuyển đổi Lead', ['✅', '🟡', '✅', '🔒', '🔒', '🔒', '🟡', '🔒']],
                ['Quản lý liên kết chiến dịch UTM', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '✅', '🔒']],
                ['Xóa Lead / Chuyển vào rác', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']],
            ]
        ],
        [
            'group_code' => 'ACADEMIC',
            'group_title' => '8. Học viên, Đào tạo & Điều phối Học vụ',
            'rows' => [
                ['Xem danh sách & hồ sơ học viên', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']],
                ['Quản lý tiến độ Visa du học & XKLĐ', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']],
                ['Quản lý lớp học tiếng Hàn', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']],
                ['Cập nhật điểm danh & điểm thi học viên', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']],
                ['Gửi thông báo học vụ & lịch phỏng vấn', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']],
            ]
        ]
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

        $subview = (string)($_GET['subview'] ?? 'users');
    if ($tab === 'roles_matrix') {
        $tab = 'accounts';
        $subview = 'matrix';
    } elseif ($tab === 'roles_cards') {
        $tab = 'accounts';
        $subview = 'roles';
    }

    $totalRoles = 8;
    $totalAssignedUsers = count($allAdmins);
    $totalPermissionGroups = 12;
    $activeCount = 0;
    $roleUserMap = [];
    foreach (array_keys($rolesMeta) as $rk) {
        $roleUserMap[$rk] = [];
    }
    foreach ($allAdmins as $a) {
        if (($a['status'] ?? 'active') === 'active') {
            $activeCount++;
        }
        $rk = (string)($a['role'] ?? 'super_admin');
        if (isset($roleUserMap[$rk])) {
            $roleUserMap[$rk][] = $a;
        }
    }
    $healthPercent = $totalAssignedUsers > 0 ? (int)round(($activeCount / $totalAssignedUsers) * 100) : 100;

    $bannerHtml = '';
    if (isset($_GET['admin_created'])) {
        $bannerHtml .= '<div class="banner banner-success">✓ Đã tạo tài khoản nhân sự mới thành công và lưu trực tiếp vào CSDL!</div>';
    }
    if (isset($_GET['status_updated'])) {
        $bannerHtml .= '<div class="banner banner-success">✓ Đã cập nhật trạng thái hoạt động của tài khoản trong CSDL!</div>';
    }
    if (isset($_GET['deleted_admin'])) {
        $bannerHtml .= '<div class="banner banner-success">✓ Đã xóa tài khoản nhân sự khỏi CSDL!</div>';
    }
    if (isset($_GET['deleted'])) {
        $bannerHtml .= '<div class="banner banner-success">✓ Đã xóa đăng ký tư vấn khỏi CSDL.</div>';
    }
    if (isset($_GET['password_changed'])) {
        $bannerHtml .= '<div class="banner banner-success">✓ Đã đổi mật khẩu quản trị thành công.</div>';
    }

    // Build View 1: Team Members Table (AdminOS & Sneat)
    $userRows = '';
    foreach ($allAdmins as $a) {
        $rKey = (string)($a['role'] ?? 'super_admin');
        $rInfo = $rolesMeta[$rKey] ?? [
            'code' => '00', 'title' => $rKey, 'badge' => $rKey, 'color' => '#64748b',
            'bg_color' => '#f1f5f9', 'border_color' => '#cbd5e1'
        ];
        [$initials, $avBg, $avFg] = kipa_avatar_meta($a);
        $st = (string)($a['status'] ?? 'active');
        $isActive = ($st === 'active');
        $stDotColor = $isActive ? '#10b981' : '#ef4444';
        $stText = $isActive ? 'Hoạt động' : 'Đã khóa';
        $stBg = $isActive ? '#ecfdf5' : '#fef2f2';
        $stFg = $isActive ? '#065f46' : '#991b1b';

        $actionsHtml = '';
        if ((int)$a['id'] !== (int)$_SESSION['admin_id']) {
            $toggleTxt = $isActive ? 'Khóa' : 'Mở khóa';
            $toggleColor = $isActive ? '#dc2626' : '#059669';
            $actionsHtml .= kipa_admin_form_start('toggle_admin_status', $csrf) .
                '<input type="hidden" name="target_id" value="' . (int)$a['id'] . '">' .
                '<button type="submit" class="btn-sm" style="background:' . $toggleColor . ';color:#fff;">' . $toggleTxt . '</button></form> ';

            if (($_SESSION['admin_role'] ?? 'super_admin') === 'super_admin') {
                $actionsHtml .= kipa_admin_form_start('delete_admin', $csrf) .
                    '<input type="hidden" name="target_id" value="' . (int)$a['id'] . '">' .
                    '<button type="submit" class="btn-sm" style="background:#64748b;color:#fff;" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này khỏi CSDL?');">Xóa</button></form>';
            }
        } else {
            $actionsHtml = '<span class="chip-root">Đang đăng nhập</span>';
        }

        $fullNameDisplay = !empty($a['full_name']) ? $a['full_name'] : $a['username'];
        $userRows .= '<tr class="user-row" data-dept="' . kipa_h((string)($a['department'] ?? '')) . '" data-role="' . kipa_h($rKey) . '" data-search="' . mb_strtolower(kipa_h($fullNameDisplay . ' ' . $a['username'] . ' ' . ($a['email'] ?? '')), 'UTF-8') . '">
            <td style="width:40px;text-align:center;"><input type="checkbox" class="user-cb"></td>
            <td>
                <div class="user-cell">
                    <div class="avatar-circle" style="background:' . $avBg . ';color:' . $avFg . ';">' . kipa_h($initials) . '</div>
                    <div class="user-meta">
                        <div class="user-name">' . kipa_h($fullNameDisplay) . '</div>
                        <div class="user-handle">@' . kipa_h((string)$a['username']) . '</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="contact-email">' . kipa_h((string)($a['email'] ?? '—')) . '</div>
                <div class="contact-phone">' . kipa_h((string)($a['phone'] ?? '—')) . '</div>
            </td>
            <td><span class="badge-dept">' . kipa_h((string)($a['department'] ?? 'Ban Giám Đốc')) . '</span></td>
            <td>
                <span class="badge-role" style="background:' . $rInfo['bg_color'] . ';color:' . $rInfo['color'] . ';border-color:' . $rInfo['border_color'] . ';">
                    ' . kipa_h($rInfo['code'] . '. ' . $rInfo['title']) . '
                </span>
            </td>
            <td><span class="badge-scope">' . kipa_h((string)($a['scope'] ?? 'Toàn hệ thống')) . '</span></td>
            <td>
                <span class="status-pill" style="background:' . $stBg . ';color:' . $stFg . ';">
                    <span class="status-dot" style="background:' . $stDotColor . ';"></span>
                    ' . $stText . '
                </span>
            </td>
            <td style="white-space:nowrap;text-align:right;">' . $actionsHtml . '</td>
        </tr>';
    }

    // Role options for Create Admin form
    $roleSelectOptions = '';
    foreach ($rolesMeta as $k => $v) {
        $roleSelectOptions .= '<option value="' . kipa_h($k) . '">' . kipa_h($v['code'] . '. ' . $v['title'] . ' — ' . $v['badge']) . '</option>';
    }

    // Build View 2: Sneat-style Roles Cards Grid
    $roleCards = '';
    foreach ($rolesMeta as $rk => $rv) {
        $assigned = $roleUserMap[$rk] ?? [];
        $countU = count($assigned);
        $avatarsStack = '';
        if ($countU > 0) {
            $avatarsStack = '<div class="avatar-stack">';
            $slice = array_slice($assigned, 0, 3);
            foreach ($slice as $u) {
                [$uIn, $uBg, $uFg] = kipa_avatar_meta($u);
                $uName = !empty($u['full_name']) ? $u['full_name'] : $u['username'];
                $avatarsStack .= '<div class="avatar-sm" style="background:' . $uBg . ';color:' . $uFg . ';" title="' . kipa_h($uName) . '">' . kipa_h($uIn) . '</div>';
            }
            if ($countU > 3) {
                $avatarsStack .= '<div class="avatar-sm avatar-more">+' . ($countU - 3) . '</div>';
            }
            $avatarsStack .= '</div>';
        } else {
            $avatarsStack = '<span class="text-muted-xs" style="font-size:12px;color:#94a3b8;">Chưa có nhân sự</span>';
        }

        $roleCards .= '<div class="role-card" style="border-top:3px solid ' . $rv['color'] . ';">
            <div class="role-card-top">
                <div class="role-user-count">Tổng: <strong>' . $countU . '</strong> nhân sự</div>
                ' . $avatarsStack . '
            </div>
            <div class="role-card-header">
                <span class="role-code-badge" style="background:' . $rv['bg_color'] . ';color:' . $rv['color'] . ';">' . $rv['code'] . '</span>
                <h3 class="role-card-title">' . kipa_h($rv['title']) . '</h3>
            </div>
            <div class="role-badge-pill" style="background:' . $rv['bg_color'] . ';color:' . $rv['color'] . ';border-color:' . $rv['border_color'] . ';">
                ' . kipa_h($rv['badge']) . '
            </div>
            <p class="role-card-desc">' . kipa_h($rv['desc']) . '</p>
            <div class="role-card-footer">
                <a href="javascript:void(0)" onclick="filterByRole('' . kipa_h($rk) . '')" class="role-link">Lọc nhân sự (' . $countU . ')</a>
                <a href="javascript:void(0)" onclick="switchSubview('matrix')" class="role-link secondary">Xem ma trận quyền →</a>
            </div>
        </div>';
    }

    $roleCards .= '<div class="role-card role-card-add">
        <div class="add-role-illustration" style="width:60px;height:60px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <path d="M12 8v8"></path>
                <path d="M8 12h8"></path>
            </svg>
        </div>
        <h3 class="role-card-title" style="margin-top:8px;text-align:center;">+ Tạo vai trò mới</h3>
        <p class="role-card-desc" style="text-align:center;">Tùy biến nhóm quyền P01-P12 và cấu hình vai trò riêng cho bộ phận.</p>
        <button type="button" class="btn-create-role" style="background:#2563eb;color:#fff;border:0;padding:8px 16px;border-radius:7px;font-weight:700;cursor:pointer;font-size:12px;margin:0 auto;" onclick="alert('Chức năng tạo vai trò tùy biến được quản lý bởi Super Admin.');">+ Tạo vai trò mới</button>
    </div>';

    // Build View 3: Taskora-style Permission Matrix Table
    $matrixSectionsHtml = '';
    $sectionsData = kipa_permission_sections();
    foreach ($sectionsData as $sec) {
        $secRows = '';
        foreach ($sec['rows'] as $r) {
            $rTitle = $r[0];
            $rBadges = $r[1];
            $tds = '';
            foreach ($rBadges as $b) {
                if ($b === '✅') {
                    $tds .= '<td class="mat-cell"><span class="mat-badge mat-allow" title="Được phép">✅</span></td>';
                } elseif ($b === '🟡') {
                    $tds .= '<td class="mat-cell"><span class="mat-badge mat-cond" title="Có điều kiện / theo phạm vi">🟡</span></td>';
                } else {
                    $tds .= '<td class="mat-cell"><span class="mat-badge mat-deny" title="Không được phép">🔒</span></td>';
                }
            }
            $secRows .= '<tr class="mat-row">
                <td class="mat-func">' . kipa_h($rTitle) . '</td>
                ' . $tds . '
            </tr>';
        }

        $matrixSectionsHtml .= '<div class="mat-section">
            <div class="mat-sec-header">
                <span class="mat-sec-code">' . kipa_h($sec['group_code']) . '</span>
                <span class="mat-sec-title">' . kipa_h($sec['group_title']) . '</span>
            </div>
            <div class="table-wrap">
                <table class="mat-table">
                    <thead>
                        <tr>
                            <th class="mat-th-func">Chức năng chi tiết</th>
                            <th class="mat-th">1. Super Admin</th>
                            <th class="mat-th">2. Website Mgr</th>
                            <th class="mat-th">3. Admissions Mgr</th>
                            <th class="mat-th">4. Admissions Cons</th>
                            <th class="mat-th">5. Content Mgr</th>
                            <th class="mat-th">6. Content & SEO</th>
                            <th class="mat-th">7. SEO Mgr</th>
                            <th class="mat-th">8. Academic Coord</th>
                        </tr>
                    </thead>
                    <tbody>' . $secRows . '</tbody>
                </table>
            </div>
        </div>';
    }

    // Leads Tab Table
    $leadsRows = '';
    foreach ($leads as $lead) {
        $details = json_decode($lead['details_json'], true);
        if (!is_array($details)) { $details = []; }
        $program = (string)($lead['program'] ?? '');
        $contact = '<strong>' . kipa_h($lead['contact_name']) . '</strong><br>' . kipa_h($lead['phone']);
        if ($lead['email'] !== null) {
            $contact .= '<br>' . kipa_h($lead['email']);
        }
        $detailsText = [];
        foreach ($details as $k => $val) {
            $formatted = is_array($val) ? implode(', ', array_map('strval', $val)) : (string)$val;
            $detailsText[] = (string)$k . ': ' . $formatted;
        }

        $leadsRows .= '<tr>
            <td>#' . (int)$lead['id'] . '</td>
            <td style="font-size:12px;color:#64748b;white-space:nowrap;">' . kipa_h($lead['created_at']) . '</td>
            <td>' . $contact . '</td>
            <td><span class="badge-dept" style="background:#e0f2fe;color:#0369a1;">' . kipa_h($lead['form_type']) . '</span><br><span style="font-size:12px;color:#64748b;">' . kipa_h((string)$lead['source_page']) . '</span></td>
            <td>' . kipa_h($program) . '</td>
            <td><pre style="max-width:320px;font:11px monospace;white-space:pre-wrap;">' . kipa_h(implode("
", $detailsText)) . '</pre></td>
            <td>' . kipa_admin_form_start('delete', $csrf) . '<input type="hidden" name="lead_id" value="' . (int)$lead['id'] . '"><button class="danger btn-sm" type="submit">Xóa</button></form></td>
        </tr>';
    }

    $paginationHtml = '';
    if ($totalPages > 1) {
        $paginationHtml .= '<nav class="actions" style="margin-top:16px;">';
        if ($page > 1) {
            $paginationHtml .= '<a href="/api/admin.php?tab=leads&page=' . ($page - 1) . '" class="btn-secondary">← Trang trước</a>';
        }
        $paginationHtml .= '<span style="font-size:13px;font-weight:600;padding:6px 12px;">Trang ' . $page . ' / ' . $totalPages . '</span>';
        if ($page < $totalPages) {
            $paginationHtml .= '<a href="/api/admin.php?tab=leads&page=' . ($page + 1) . '" class="btn-secondary">Trang sau →</a>';
        }
        $paginationHtml .= '</nav>';
    }

    // Output Complete AdminOS HTML
    echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>KIPA AdminOS | Hệ thống Phân quyền 8 Vai trò</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<style>
        :root {
            --bg-body: #f8fafc;
            --bg-sidebar: #091E3A;
            --sidebar-border: #132d52;
            --sidebar-text: #94a3b8;
            --sidebar-hover: #102a4e;
            --sidebar-active: #2563eb;
            --primary: #0F5B9C;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --radius-md: 10px;
            --radius-lg: 14px;
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{background:var(--bg-body);color:var(--text-main);font-family:'Plus Jakarta Sans',system-ui,sans-serif;font-size:14px;line-height:1.5;display:flex;min-height:100vh;}
        .sidebar{width:260px;background:var(--bg-sidebar);border-right:1px solid var(--sidebar-border);display:flex;flex-direction:column;flex-shrink:0;color:#fff;position:sticky;top:0;height:100vh;overflow-y:auto;}
        .brand-header{padding:22px 20px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--sidebar-border);}
        .brand-icon{width:38px;height:38px;background:linear-gradient(135deg,#2563eb,#38bdf8);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;box-shadow:0 4px 10px rgba(37,99,235,0.3);}
        .brand-title{font-size:16px;font-weight:800;letter-spacing:-0.3px;color:#fff;}
        .brand-badge{font-size:10px;background:rgba(56,189,248,0.15);color:#38bdf8;padding:2px 6px;border-radius:4px;font-weight:700;margin-top:2px;display:inline-block;}
        .nav-section-title{padding:20px 20px 8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:#64748b;}
        .nav-menu{display:flex;flex-direction:column;gap:4px;padding:0 12px;}
        .nav-link{display:flex;align-items:center;gap:12px;padding:10px 14px;color:var(--sidebar-text);text-decoration:none;font-weight:600;font-size:13px;border-radius:8px;transition:all .15s ease;}
        .nav-link:hover{background:var(--sidebar-hover);color:#fff;text-decoration:none;}
        .nav-link.active{background:var(--sidebar-active);color:#fff;box-shadow:0 2px 8px rgba(37,99,235,0.35);}
        .nav-link .nav-badge{margin-left:auto;background:rgba(255,255,255,0.2);padding:2px 7px;border-radius:10px;font-size:11px;font-weight:700;}
        .sidebar-footer{margin-top:auto;padding:16px;border-top:1px solid var(--sidebar-border);display:flex;align-items:center;gap:12px;background:rgba(0,0,0,0.15);}
        .user-avatar-sm{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#991b1b);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;}
        .user-info{flex:1;min-width:0;}
        .user-info-name{font-weight:700;font-size:13px;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .user-info-role{font-size:11px;color:#94a3b8;}
        .btn-logout{background:transparent;border:1px solid #334155;color:#cbd5e1;padding:6px 10px;border-radius:6px;cursor:pointer;font-size:11px;font-weight:600;}
        .btn-logout:hover{background:#ef4444;border-color:#ef4444;color:#fff;}
        .main-layout{flex:1;min-width:0;display:flex;flex-direction:column;overflow-y:auto;}
        .topbar{background:#fff;border-bottom:1px solid var(--border-color);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:20;}
        .topbar-search{display:flex;align-items:center;gap:10px;background:#f1f5f9;padding:8px 14px;border-radius:8px;width:340px;}
        .topbar-search input{border:0;background:transparent;outline:none;width:100%;font-size:13px;font-family:inherit;}
        .topbar-right{display:flex;align-items:center;gap:16px;}
        .content-container{padding:28px 32px;max-width:1400px;margin:0 auto;width:100%;}
        .page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:16px;}
        .page-title{font-size:24px;font-weight:800;color:#081d36;letter-spacing:-0.4px;}
        .page-subtitle{color:var(--text-muted);font-size:13px;margin-top:4px;}
        .btn-primary{background:#2563eb;color:#fff;border:0;padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-size:13px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 6px rgba(37,99,235,0.25);}
        .btn-primary:hover{background:#1d4ed8;}
        .btn-secondary{background:#fff;color:#334155;border:1px solid #cbd5e1;padding:10px 16px;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px;display:inline-flex;align-items:center;gap:6px;}
        .btn-secondary:hover{background:#f8fafc;border-color:#94a3b8;text-decoration:none;}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:24px;}
        .stat-card{background:#fff;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.03);display:flex;align-items:flex-start;justify-content:space-between;}
        .stat-meta-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;}
        .stat-value{font-size:28px;font-weight:800;color:#081d36;margin:6px 0 2px;letter-spacing:-0.5px;}
        .stat-desc{font-size:12px;color:#94a3b8;}
        .stat-icon-wrap{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;}
        .banner{padding:12px 18px;border-radius:var(--radius-md);margin-bottom:20px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;}
        .banner-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;}
        .banner-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;}
        .subviews-bar{display:flex;gap:8px;background:#fff;padding:6px;border-radius:10px;border:1px solid var(--border-color);margin-bottom:22px;width:fit-content;flex-wrap:wrap;}
        .subview-btn{border:0;background:transparent;padding:8px 16px;border-radius:7px;font-weight:700;font-size:13px;color:#64748b;cursor:pointer;transition:all .15s ease;display:inline-flex;align-items:center;gap:6px;}
        .subview-btn:hover{background:#f1f5f9;color:#1e293b;}
        .subview-btn.active{background:#2563eb;color:#fff;box-shadow:0 1px 3px rgba(37,99,235,0.25);}
        .add-user-card{background:#fff;border:1px solid #cbd5e1;border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;box-shadow:0 4px 12px rgba(0,0,0,0.04);display:none;}
        .add-user-card.open{display:block;}
        .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-top:16px;}
        .form-group label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;}
        .form-group input,.form-group select{width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;background:#f8fafc;outline:none;font-family:inherit;}
        .form-group input:focus,.form-group select:focus{border-color:#2563eb;background:#fff;box-shadow:0 0 0 3px rgba(37,99,235,0.15);}
        .table-card{background:#fff;border:1px solid var(--border-color);border-radius:var(--radius-lg);box-shadow:0 1px 4px rgba(0,0,0,0.03);overflow:hidden;}
        .table-toolbar{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid var(--border-color);flex-wrap:wrap;}
        .table-search{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;width:300px;}
        .table-search input{border:0;background:transparent;outline:none;width:100%;font-size:13px;font-family:inherit;}
        .filter-select{padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;background:#fff;outline:none;font-family:inherit;}
        .table-wrap{overflow-x:auto;}
        table.data-table{width:100%;border-collapse:collapse;font-size:13px;text-align:left;}
        table.data-table thead th{background:#f8fafc;color:#475569;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;padding:12px 16px;border-bottom:1px solid var(--border-color);white-space:nowrap;}
        table.data-table tbody td{padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
        table.data-table tbody tr:hover{background:#f8fafc;}
        .user-cell{display:flex;align-items:center;gap:12px;}
        .avatar-circle{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
        .user-name{font-weight:700;color:#0f172a;}
        .user-handle{font-size:12px;color:#64748b;}
        .contact-email{font-weight:500;color:#334155;}
        .contact-phone{font-size:12px;color:#64748b;}
        .badge-dept{background:#f1f5f9;color:#334155;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;display:inline-block;}
        .badge-role{padding:4px 10px;border-radius:6px;font-size:12px;font-weight:700;border:1px solid transparent;display:inline-block;white-space:nowrap;}
        .badge-scope{background:#f8fafc;color:#475569;border:1px solid #e2e8f0;padding:4px 8px;border-radius:6px;font-size:12px;display:inline-block;}
        .status-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;}
        .status-dot{width:7px;height:7px;border-radius:50%;}
        .chip-root{font-size:11px;font-weight:700;color:#64748b;background:#f1f5f9;padding:4px 8px;border-radius:4px;}
        .btn-sm{border:0;padding:5px 10px;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;transition:opacity .15s ease;}
        .btn-sm:hover{opacity:.85;}
        .btn-sm.danger{background:#dc2626;color:#fff;}
        .roles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;}
        .role-card{background:#fff;border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:22px;box-shadow:0 1px 3px rgba(0,0,0,0.03);display:flex;flex-direction:column;transition:transform .15s ease;}
        .role-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.06);}
        .role-card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
        .role-user-count{font-size:13px;color:#64748b;}
        .avatar-stack{display:flex;align-items:center;}
        .avatar-sm{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-left:-8px;border:2px solid #fff;}
        .avatar-stack .avatar-sm:first-child{margin-left:0;}
        .avatar-more{background:#e2e8f0;color:#475569;}
        .role-card-header{display:flex;align-items:center;gap:8px;margin-bottom:8px;}
        .role-code-badge{font-size:11px;font-weight:800;padding:2px 6px;border-radius:4px;}
        .role-card-title{font-size:16px;font-weight:700;color:#081d36;}
        .role-badge-pill{display:inline-block;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;border:1px solid transparent;margin-bottom:12px;align-self:flex-start;}
        .role-card-desc{font-size:13px;color:#64748b;line-height:1.5;margin-bottom:18px;flex:1;}
        .role-card-footer{display:flex;align-items:center;justify-content:space-between;border-top:1px solid #f1f5f9;padding-top:14px;}
        .role-link{color:#2563eb;text-decoration:none;font-size:12px;font-weight:700;}
        .role-link:hover{text-decoration:underline;}
        .role-link.secondary{color:#64748b;font-weight:600;}
        .role-card-add{border:2px dashed #cbd5e1;background:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;min-height:240px;}
        .matrix-intro{background:#fff;border:1px solid var(--border-color);border-radius:var(--radius-md);padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
        .matrix-legend{display:flex;gap:16px;font-size:13px;}
        .mat-section{background:#fff;border:1px solid var(--border-color);border-radius:var(--radius-lg);margin-bottom:20px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.03);}
        .mat-sec-header{background:#f8fafc;padding:12px 18px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:10px;}
        .mat-sec-code{background:#e2e8f0;color:#334155;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:800;}
        .mat-sec-title{font-weight:800;font-size:14px;color:#081d36;}
        table.mat-table{width:100%;border-collapse:collapse;font-size:13px;}
        table.mat-table thead th{background:#fff;color:#475569;font-size:11px;font-weight:700;padding:10px 12px;border-bottom:1px solid var(--border-color);white-space:nowrap;text-align:center;}
        table.mat-table thead th.mat-th-func{text-align:left;width:280px;padding-left:18px;}
        table.mat-table tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;text-align:center;}
        table.mat-table tbody td.mat-func{text-align:left;font-weight:600;color:#1e293b;padding-left:18px;}
        .mat-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;font-size:14px;}
        .mat-allow{background:#ecfdf5;}
        .mat-cond{background:#fffbeb;}
        .mat-deny{background:#f8fafc;}
        @media(max-width:900px){body{flex-direction:column;}.sidebar{width:100%;height:auto;position:static;}.content-container{padding:18px;}.topbar{padding:12px 18px;}}
    </style></head><body>';

    echo '<aside class="sidebar">
        <div class="brand-header">
            <div class="brand-icon">K</div>
            <div>
                <div class="brand-title">KIPA AdminOS</div>
                <span class="brand-badge">RBAC Enterprise v2.5</span>
            </div>
        </div>
        <div class="nav-section-title">Tổng quan & Vận hành</div>
        <nav class="nav-menu">
            <a href="/api/admin.php?tab=leads" class="nav-link ' . ($tab === 'leads' ? 'active' : '') . '">
                <span>📋</span>
                <span>Đăng ký tư vấn (Leads)</span>
                <span class="nav-badge">' . $total . '</span>
            </a>
            <a href="/api/admin.php?tab=accounts" class="nav-link ' . ($tab === 'accounts' ? 'active' : '') . '">
                <span>👥</span>
                <span>Nhân sự & Phân quyền</span>
                <span class="nav-badge">' . count($allAdmins) . '</span>
            </a>
            <a href="/api/cms.php" target="_blank" class="nav-link">
                <span>🌐</span>
                <span>Quản trị CMS A–J</span>
                <span style="font-size:10px;color:#94a3b8;">↗</span>
            </a>
            <a href="/api/admin.php?tab=accounts&subview=matrix" class="nav-link">
                <span>📊</span>
                <span>Ma trận 8 Vai trò (RBAC)</span>
            </a>
            <a href="javascript:void(0)" class="nav-link" onclick="alert('Nhật ký bảo mật Audit Log đang được ghi nhận trực tiếp vào máy chủ.');">
                <span>🛡️</span>
                <span>Nhật ký bảo mật</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-avatar-sm">SA</div>
            <div class="user-info">
                <div class="user-info-name">' . kipa_h((string)($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'])) . '</div>
                <div class="user-info-role">' . kipa_h((string)($_SESSION['admin_role'] ?? 'super_admin')) . '</div>
            </div>
            ' . kipa_admin_form_start('logout', $csrf) . '<button type="submit" class="btn-logout" title="Đăng xuất">Thoát</button></form>
        </div>
    </aside>';

    echo '<div class="main-layout">
        <header class="topbar">
            <div class="topbar-search">
                <span style="color:#94a3b8;">🔍</span>
                <input type="text" placeholder="Tìm kiếm tài khoản, vai trò, dữ liệu..." id="globalSearchInput" onkeyup="handleSearch(this.value)">
            </div>
            <div class="topbar-right">
                <button type="button" class="btn-secondary" onclick="exportDataCSV()">📥 Xuất CSV</button>
                <div style="font-size:12px;color:#64748b;font-weight:600;">UTC: ' . gmdate('Y-m-d') . '</div>
            </div>
        </header>

        <main class="content-container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">' . ($tab === 'accounts' ? 'Quản lý Nhân sự & Phân quyền (Roles & Permissions)' : 'Quản lý Đăng ký Tư vấn (Leads)') . '</h1>
                    <p class="page-subtitle">' . ($tab === 'accounts' ? 'Hệ thống kiểm soát truy cập theo vai trò (RBAC 8 vai trò chính & 12 nhóm quyền P01-P12) KIPA Việt Hàn.' : 'Danh sách học viên và phụ huynh đăng ký tư vấn du học & xuất khẩu lao động.') . '</p>
                </div>
                ' . ($tab === 'accounts' ? '<div class="header-actions"><button type="button" class="btn-primary" onclick="toggleAddUserForm()">+ Thêm tài khoản nhân sự</button></div>' : '') . '
            </div>

            ' . $bannerHtml . '

            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-meta-title">TỔNG SỐ VAI TRÒ</div>
                        <div class="stat-value">' . $totalRoles . '</div>
                        <div class="stat-desc">8 vai trò chuẩn hóa hệ thống</div>
                    </div>
                    <div class="stat-icon-wrap" style="background:#eff6ff;color:#2563eb;">🛡️</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-meta-title">NHÂN SỰ ĐƯỢC GÁN</div>
                        <div class="stat-value">' . $totalAssignedUsers . '</div>
                        <div class="stat-desc">1 Super Admin + 4 Nhân viên</div>
                    </div>
                    <div class="stat-icon-wrap" style="background:#ecfdf5;color:#059669;">👥</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-meta-title">NHÓM QUYỀN HỆ THỐNG</div>
                        <div class="stat-value">' . $totalPermissionGroups . '</div>
                        <div class="stat-desc">P01 Dashboard → P12 System</div>
                    </div>
                    <div class="stat-icon-wrap" style="background:#f5f3ff;color:#7c3aed;">🧩</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-meta-title">TRẠNG THÁI VẬN HÀNH</div>
                        <div class="stat-value">' . $healthPercent . '%</div>
                        <div class="stat-desc">100% tài khoản hoạt động</div>
                    </div>
                    <div class="stat-icon-wrap" style="background:#ecfdf5;color:#10b981;">⚡</div>
                </div>
            </div>';

    if ($tab === 'accounts') {
        echo '<div class="subviews-bar">
            <button type="button" class="subview-btn ' . ($subview === 'users' ? 'active' : '') . '" onclick="switchSubview('users')" id="btnSubUsers">
                👥 Danh sách Nhân sự (' . count($allAdmins) . ')
            </button>
            <button type="button" class="subview-btn ' . ($subview === 'roles' ? 'active' : '') . '" onclick="switchSubview('roles')" id="btnSubRoles">
                🏷️ 8 Thẻ Vai trò chính (Sneat)
            </button>
            <button type="button" class="subview-btn ' . ($subview === 'matrix' ? 'active' : '') . '" onclick="switchSubview('matrix')" id="btnSubMatrix">
                📊 Ma trận Phân quyền chi tiết (Taskora)
            </button>
        </div>';

        // Add User Form Card
        echo '<div class="add-user-card" id="addUserCard">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:17px;font-weight:800;color:#081d36;">+ Tạo tài khoản Quản trị & Nhân sự mới</h2>
                <button type="button" onclick="toggleAddUserForm()" style="background:none;border:none;font-size:18px;cursor:pointer;color:#64748b;">✕</button>
            </div>
            <p style="font-size:12px;color:#64748b;margin-top:2px;">Thông tin tài khoản mới sẽ được mã hóa an toàn và lưu trực tiếp vào CSDL MySQL máy chủ.</p>
            ' . kipa_admin_form_start('create_admin', $csrf) . '
            <div class="form-grid">
                <div class="form-group">
                    <label>Tên đăng nhập (Username) *</label>
                    <input name="username" required minlength="3" maxlength="64" placeholder="Ví dụ: nguyene">
                </div>
                <div class="form-group">
                    <label>Họ và tên nhân sự *</label>
                    <input name="full_name" required minlength="2" maxlength="160" placeholder="Ví dụ: Nguyễn Văn E">
                </div>
                <div class="form-group">
                    <label>Email nhận OTP & Thông báo *</label>
                    <input name="email" type="email" required placeholder="nhansu@kipaviethan.edu.vn">
                </div>
                <div class="form-group">
                    <label>Số điện thoại liên hệ</label>
                    <input name="phone" placeholder="Ví dụ: 0912 345 679">
                </div>
                <div class="form-group">
                    <label>Phòng ban</label>
                    <select name="department">
                        <option value="Marketing">Marketing</option>
                        <option value="SEO">SEO & Kỹ thuật</option>
                        <option value="Tuyển sinh">Tuyển sinh</option>
                        <option value="Đào tạo & Học vụ">Đào tạo & Học vụ</option>
                        <option value="Ban Giám Đốc">Ban Giám Đốc</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vai trò hệ thống (Role) *</label>
                    <select name="role">' . $roleSelectOptions . '</select>
                </div>
                <div class="form-group">
                    <label>Phạm vi dữ liệu (Scope)</label>
                    <input name="scope" value="Du học" placeholder="Ví dụ: Du học, Toàn Website, Lead được giao...">
                </div>
                <div class="form-group">
                    <label>Mật khẩu khởi tạo *</label>
                    <input name="password" type="text" value="Kipa@2026!pass" required minlength="8" maxlength="72">
                </div>
            </div>
            <div style="margin-top:14px;display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="mcp" name="must_change_password" value="1" checked style="width:auto;">
                <label for="mcp" style="font-size:13px;font-weight:normal;color:#334155;margin:0;">Bắt buộc nhân sự phải đổi mật khẩu ở lần đăng nhập đầu tiên</label>
            </div>
            <div style="margin-top:18px;display:flex;gap:10px;">
                <button type="submit" class="btn-primary">+ Lưu và Tạo tài khoản vào CSDL</button>
                <button type="button" class="btn-secondary" onclick="toggleAddUserForm()">Hủy bỏ</button>
            </div>
            </form>
        </div>';

        // View 1: Team Members Table
        echo '<div id="viewUsers" class="subview-panel" style="display:' . ($subview === 'users' ? 'block' : 'none') . ';">
            <div class="table-card">
                <div class="table-toolbar">
                    <div class="table-search">
                        <span style="color:#94a3b8;">🔍</span>
                        <input type="text" id="userSearchInput" placeholder="Tìm theo tên, username, email..." onkeyup="filterUserTable()">
                    </div>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <select class="filter-select" id="deptFilter" onchange="filterUserTable()">
                            <option value="">Tất cả phòng ban</option>
                            <option value="Ban Giám Đốc">Ban Giám Đốc</option>
                            <option value="Marketing">Marketing</option>
                            <option value="SEO">SEO</option>
                            <option value="Tuyển sinh">Tuyển sinh</option>
                            <option value="Đào tạo & Học vụ">Đào tạo & Học vụ</option>
                        </select>
                        <select class="filter-select" id="roleFilter" onchange="filterUserTable()">
                            <option value="">Tất cả vai trò (8 vai trò)</option>
                            <option value="super_admin">01. Super Admin</option>
                            <option value="website_manager">02. Website Manager</option>
                            <option value="admissions_manager">03. Admissions Manager</option>
                            <option value="admissions_consultant">04. Admissions Consultant</option>
                            <option value="content_manager">05. Content Manager</option>
                            <option value="content_seo_executive">06. Content & SEO Executive</option>
                            <option value="seo_manager">07. SEO Manager</option>
                            <option value="academic_coordinator">08. Academic Coordinator</option>
                        </select>
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="data-table" id="usersTable">
                        <thead>
                            <tr>
                                <th style="width:40px;text-align:center;"><input type="checkbox" id="selectAllUsers" onclick="toggleSelectAll(this)"></th>
                                <th>Nhân sự</th>
                                <th>Email / SĐT</th>
                                <th>Phòng ban</th>
                                <th>Vai trò</th>
                                <th>Phạm vi dữ liệu</th>
                                <th>Trạng thái</th>
                                <th style="text-align:right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>' . $userRows . '</tbody>
                    </table>
                </div>
            </div>
        </div>';

        // View 2: Roles Cards Grid
        echo '<div id="viewRoles" class="subview-panel" style="display:' . ($subview === 'roles' ? 'block' : 'none') . ';">
            <div class="roles-grid">' . $roleCards . '</div>
        </div>';

        // View 3: Taskora Matrix
        echo '<div id="viewMatrix" class="subview-panel" style="display:' . ($subview === 'matrix' ? 'block' : 'none') . ';">
            <div class="matrix-intro">
                <div>
                    <strong style="color:#081d36;font-size:14px;">Quy tắc phân định thẩm quyền:</strong>
                    <span style="color:#64748b;font-size:13px;margin-left:6px;">Áp dụng chuẩn hóa cho 8 vai trò và 12 nhóm quyền chức năng KIPA.</span>
                </div>
                <div class="matrix-legend">
                    <span><span class="mat-badge mat-allow">✅</span> Được phép</span>
                    <span><span class="mat-badge mat-cond">🟡</span> Có điều kiện / theo phạm vi</span>
                    <span><span class="mat-badge mat-deny">🔒</span> Không được phép</span>
                </div>
            </div>
            ' . $matrixSectionsHtml . '
        </div>';

    } else {
        // Leads Table View
        echo '<div class="table-card">
            <div class="table-toolbar">
                <div class="table-search">
                    <span style="color:#94a3b8;">🔍</span>
                    <input type="text" placeholder="Tìm theo tên, SĐT, trang nguồn..." onkeyup="filterLeadsTable(this.value)">
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table" id="leadsTable">
                    <thead>
                        <tr>
                            <th>ID</th><th>Thời gian (UTC)</th><th>Liên hệ</th><th>Biểu mẫu / Nguồn</th><th>Nhu cầu</th><th>Chi tiết</th><th>Tác vụ</th>
                        </tr>
                    </thead>
                    <tbody>' . ($leadsRows !== '' ? $leadsRows : '<tr><td colspan="7" style="text-align:center;padding:24px;color:#94a3b8;">Chưa có đăng ký nào.</td></tr>') . '</tbody>
                </table>
            </div>
        </div>' . $paginationHtml;
    }

    echo '</main></div>';

    // Script
    echo '<script>
        function switchSubview(sub) {
            const views = ['users', 'roles', 'matrix'];
            views.forEach(v => {
                const el = document.getElementById('view' + v.charAt(0).toUpperCase() + v.slice(1));
                const btn = document.getElementById('btnSub' + v.charAt(0).toUpperCase() + v.slice(1));
                if (el) el.style.display = (v === sub) ? 'block' : 'none';
                if (btn) {
                    if (v === sub) btn.classList.add('active');
                    else btn.classList.remove('active');
                }
            });
        }

        function toggleAddUserForm() {
            const card = document.getElementById('addUserCard');
            if (card) {
                card.classList.toggle('open');
                if (card.classList.contains('open')) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }

        function filterUserTable() {
            const search = (document.getElementById('userSearchInput')?.value || '').toLowerCase().trim();
            const dept = document.getElementById('deptFilter')?.value || '';
            const role = document.getElementById('roleFilter')?.value || '';
            const rows = document.querySelectorAll('#usersTable tbody tr.user-row');

            rows.forEach(r => {
                const rSearch = r.getAttribute('data-search') || '';
                const rDept = r.getAttribute('data-dept') || '';
                const rRole = r.getAttribute('data-role') || '';

                const matchSearch = !search || rSearch.includes(search);
                const matchDept = !dept || rDept === dept;
                const matchRole = !role || rRole === role;

                if (matchSearch && matchDept && matchRole) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        function filterByRole(roleKey) {
            switchSubview('users');
            const roleFilter = document.getElementById('roleFilter');
            if (roleFilter) {
                roleFilter.value = roleKey;
                filterUserTable();
            }
        }

        function handleSearch(val) {
            const userSearch = document.getElementById('userSearchInput');
            if (userSearch) {
                userSearch.value = val;
                filterUserTable();
            }
        }

        function toggleSelectAll(master) {
            const cbs = document.querySelectorAll('.user-cb');
            cbs.forEach(cb => cb.checked = master.checked);
        }

        function exportDataCSV() {
            let csv = "ID,HoTen,Username,Email,Phone,PhongBan,VaiTro,PhamVi,TrangThai\n";
            const rows = document.querySelectorAll('#usersTable tbody tr.user-row');
            rows.forEach(r => {
                const cells = r.querySelectorAll('td');
                if (cells.length >= 7) {
                    const name = cells[1].querySelector('.user-name')?.innerText.trim() || '';
                    const handle = cells[1].querySelector('.user-handle')?.innerText.replace('@', '').trim() || '';
                    const email = cells[2].querySelector('.contact-email')?.innerText.trim() || '';
                    const phone = cells[2].querySelector('.contact-phone')?.innerText.trim() || '';
                    const dept = cells[3]?.innerText.trim() || '';
                    const role = cells[4]?.innerText.trim() || '';
                    const scope = cells[5]?.innerText.trim() || '';
                    const status = cells[6]?.innerText.trim() || '';
                    csv += '"' + handle + '","' + name + '","' + handle + '","' + email + '","' + phone + '","' + dept + '","' + role + '","' + scope + '","' + status + '"\n';
                }
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.setAttribute("download", "kipa_staff_accounts.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script></body></html>';
    exit;
} catch (PDOException $error) {
    error_log('[kipa-admin] Database operation failed: ' . $error->getCode());
    http_response_code(503);
    kipa_admin_page('Tạm thời gián đoạn', '<section class="card"><h1>Trang quản trị đang tạm thời gián đoạn</h1><p>Không thể tải dữ liệu lúc này. Vui lòng thử lại sau.</p></section>');
} catch (RuntimeException $error) {
    error_log('[kipa-admin] Configuration failed.');
    http_response_code(503);
    kipa_admin_page('Chưa cấu hình', '<section class="card"><h1>Backend chưa được cấu hình</h1><p>Hãy hoàn tất thiết lập riêng trên hosting trước khi sử dụng.</p></section>');
}
