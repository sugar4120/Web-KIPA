# -*- coding: utf-8 -*-
"""
KIPA AdminOS - Dashboard View & Enterprise RBAC Interface
Combines Taskora, AdminOS, and Sneat UI Kit design paradigms.
"""

import json
from urllib.parse import quote

ROLES_CONFIG = {
    'super_admin': {
        'code': '01',
        'title': 'Super Admin',
        'badge': 'Toàn quyền hệ thống',
        'desc': 'Quản trị hệ thống toàn quyền, quản trị tài khoản, phân quyền kỹ thuật và nhật ký bảo mật.',
        'color': '#dc2626',
        'bg_color': '#fef2f2',
        'border_color': '#fecaca',
        'scope_default': 'Toàn hệ thống'
    },
    'website_manager': {
        'code': '02',
        'title': 'Website Manager',
        'badge': 'Vận hành Website & CMS',
        'desc': 'Quản lý vận hành toàn bộ Website, duyệt nội dung và quản lý chuyên mục trong phạm vi Super Admin giao.',
        'color': '#0284c7',
        'bg_color': '#f0f9ff',
        'border_color': '#bae6fd',
        'scope_default': 'Toàn Website'
    },
    'admissions_manager': {
        'code': '03',
        'title': 'Admissions Manager',
        'badge': 'Trưởng Tuyển sinh & Lead',
        'desc': 'Quản lý bộ phận tuyển sinh, phân chia và điều phối Lead khách hàng, giám sát tỷ lệ chuyển đổi.',
        'color': '#059669',
        'bg_color': '#ecfdf5',
        'border_color': '#a7f3d0',
        'scope_default': 'Toàn bộ Lead'
    },
    'admissions_consultant': {
        'code': '04',
        'title': 'Admissions Consultant',
        'badge': 'Tư vấn viên & CSKH',
        'desc': 'Tư vấn tuyển sinh trực tiếp, chăm sóc khách hàng, cập nhật tình trạng và lịch hẹn Lead được phân công.',
        'color': '#10b981',
        'bg_color': '#f0fdf4',
        'border_color': '#bbf7d0',
        'scope_default': 'Lead được giao'
    },
    'content_manager': {
        'code': '05',
        'title': 'Content Manager',
        'badge': 'Quản lý Nội dung & Biên tập',
        'desc': 'Quản trị nội dung bài viết, biên tập tin tức, kiểm duyệt bài viết và quản lý lịch xuất bản.',
        'color': '#d97706',
        'bg_color': '#fffbeb',
        'border_color': '#fde68a',
        'scope_default': 'Marketing & Nội dung'
    },
    'content_seo_executive': {
        'code': '06',
        'title': 'Content & SEO Executive',
        'badge': 'Biên soạn & SEO Onpage',
        'desc': 'Soạn thảo bài viết theo phân công, tối ưu SEO Onpage cơ bản (Title, Heading, Alt ảnh) và gửi duyệt.',
        'color': '#f59e0b',
        'bg_color': '#fffbeb',
        'border_color': '#fef3c7',
        'scope_default': 'Du học'
    },
    'seo_manager': {
        'code': '07',
        'title': 'SEO Manager',
        'badge': 'SEO Chiến lược & Kỹ thuật',
        'desc': 'Quản trị SEO kỹ thuật nâng cao (Canonical, Robots, Sitemap XML, Schema, Redirect 301) và tối ưu từ khóa.',
        'color': '#7c3aed',
        'bg_color': '#f5f3ff',
        'border_color': '#ddd6fe',
        'scope_default': 'Toàn Website'
    },
    'academic_coordinator': {
        'code': '08',
        'title': 'Academic & Student Coordinator',
        'badge': 'Quản lý Học viên & Học vụ',
        'desc': 'Quản lý danh sách học viên, hồ sơ du học/XKLĐ, lịch học tiếng Hàn, theo dõi tiến độ Visa và điều phối học vụ.',
        'color': '#2563eb',
        'bg_color': '#eff6ff',
        'border_color': '#bfdbfe',
        'scope_default': 'Đào tạo & Học vụ'
    },
}

PERMISSION_MATRIX_SECTIONS = [
    {
        'group_code': 'P01',
        'group_title': '1. Dashboard & Báo cáo Tổng quan',
        'rows': [
            ('Xem Dashboard', ['✅', '✅', '🟡', '🟡', '🟡', '🟡', '🟡', '🟡']),
            ('Xem Báo cáo Tuyển sinh / Lead', ['✅', '🟡', '✅', '🟡', '🔒', '🔒', '🔒', '🔒']),
            ('Xuất dữ liệu báo cáo thống kê', ['✅', '🟡', '✅', '🔒', '🔒', '🔒', '🟡', '🔒']),
        ]
    },
    {
        'group_code': 'P11_P12',
        'group_title': '2. Quản trị Hệ thống, Tài khoản & Phân quyền',
        'rows': [
            ('Xem tài khoản nhân viên', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Tạo tài khoản mới', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Sửa thông tin tài khoản', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Khóa / Mở tài khoản', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Gán vai trò nhân sự', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Tạo vai trò mới', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Chỉnh sửa nhóm quyền', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Phân quyền nâng cao', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Xem nhật ký hệ thống (Audit log)', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Cấu hình hệ thống', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
        ]
    },
    {
        'group_code': 'P02_P03_P04',
        'group_title': '3. Quản lý Nội dung, Duyệt & Xuất bản (CMS)',
        'rows': [
            ('Xem nội dung Website', ['✅', '✅', '🔒', '🔒', '🟡', '🟡', '🟡', '🔒']),
            ('Tạo bài viết mới', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🔒', '🔒']),
            ('Sửa bài viết', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🔒', '🔒']),
            ('Xóa nội dung (vào Trash)', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🔒', '🔒']),
            ('Xem nội dung theo phòng ban', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🔒', '🔒']),
            ('Quản lý nội dung được phân công', ['✅', '🟡', '🔒', '🔒', '✅', '✅', '🔒', '🔒']),
            ('Quản lý nội dung nhiều phòng ban', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🔒', '🔒']),
            ('Tạo trang nội dung (Pages)', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🔒', '🔒']),
            ('Chỉnh sửa trang hiện có', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🔒', '🔒']),
            ('Quản lý tin tức & sự kiện', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🔒', '🔒']),
            ('Quản lý bài hoạt động học viên', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🔒', '🔒']),
            ('Quản lý bài tuyển sinh & chương trình', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🔒', '🔒']),
            ('Gửi bài duyệt', ['🟡', '🟡', '🔒', '🔒', '🟡', '✅', '🟡', '🔒']),
            ('Duyệt bài viết', ['✅', '🟡', '🔒', '🔒', '✅', '🔒', '🔒', '🔒']),
            ('Từ chối bài viết', ['✅', '🟡', '🔒', '🔒', '✅', '🔒', '🔒', '🔒']),
            ('Trả lại yêu cầu chỉnh sửa', ['✅', '🟡', '🔒', '🔒', '✅', '🔒', '🔒', '🔒']),
            ('Xuất bản trực tiếp (Publish)', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']),
            ('Lên lịch xuất bản bài', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']),
            ('Gỡ bài viết đã xuất bản', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']),
            ('Khôi phục nội dung từ thùng rác', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🔒', '🔒']),
            ('Xóa vĩnh viễn nội dung', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
        ]
    },
    {
        'group_code': 'P05',
        'group_title': '4. SEO Cơ bản (Basic Onpage SEO)',
        'rows': [
            ('Chỉnh sửa Meta Title', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']),
            ('Chỉnh sửa Meta Description', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']),
            ('Chỉnh sửa Slug / Đường dẫn URL', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '✅', '🔒']),
            ('Tối ưu Heading H1, H2, H3', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']),
            ('Tối ưu Alt Text hình ảnh', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']),
            ('Gán từ khóa chính & từ khóa phụ', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']),
            ('Kiểm tra điểm chất lượng Onpage', ['✅', '🟡', '🔒', '🔒', '🟡', '✅', '✅', '🔒']),
        ]
    },
    {
        'group_code': 'P06',
        'group_title': '5. SEO Nâng cao & Kỹ thuật (Technical SEO)',
        'rows': [
            ('Cấu hình thẻ Canonical URL', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']),
            ('Cấu hình Robots Meta (noindex, nofollow)', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']),
            ('Quản lý chuyển hướng Redirect 301/302', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']),
            ('Cấu hình Schema Structured Data', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']),
            ('Quản lý Sitemap XML', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']),
            ('Theo dõi thu thập dữ liệu & Google Search Console', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']),
            ('Audit kỹ thuật toàn diện Website', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']),
        ]
    },
    {
        'group_code': 'P07_P08_P09',
        'group_title': '6. Danh mục, Thư viện Media & Landing Page',
        'rows': [
            ('Xem danh mục chuyên đề', ['✅', '✅', '🔒', '🔒', '🟡', '🟡', '✅', '🔒']),
            ('Tạo danh mục mới', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']),
            ('Sửa danh mục chuyên đề', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']),
            ('Xóa danh mục chuyên đề', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Tải file ảnh/video lên thư viện', ['✅', '🟡', '🔒', '🔒', '✅', '🟡', '🟡', '🔒']),
            ('Tối ưu nén dung lượng hình ảnh', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '✅', '🔒']),
            ('Xóa file trong thư viện Media', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🔒', '🔒']),
            ('Tạo Landing Page chiến dịch', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🟡', '🔒']),
            ('Chỉnh sửa Landing Page', ['✅', '🟡', '🔒', '🔒', '🟡', '🟡', '🟡', '🔒']),
            ('Xuất bản Landing Page', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '🟡', '🔒']),
            ('Gắn mã Tracking Pixel / GTM trên Landing Page', ['✅', '🟡', '🔒', '🔒', '🔒', '🔒', '✅', '🔒']),
        ]
    },
    {
        'group_code': 'P10',
        'group_title': '7. Marketing, Tuyển sinh & Lead Khách hàng',
        'rows': [
            ('Xem danh sách Lead & Số ĐT', ['✅', '🟡', '✅', '🟡', '🔒', '🔒', '🔒', '🔒']),
            ('Phân chia Lead cho tư vấn viên', ['🟡', '🔒', '✅', '🔒', '🔒', '🔒', '🔒', '🔒']),
            ('Nhận Lead được phân công', ['🟡', '🔒', '✅', '✅', '🔒', '🔒', '🔒', '🔒']),
            ('Cập nhật trạng thái & ghi chú chăm sóc', ['🟡', '🔒', '✅', '✅', '🔒', '🔒', '🔒', '🔒']),
            ('Xem báo cáo tỷ lệ chuyển đổi Lead', ['✅', '🟡', '✅', '🔒', '🔒', '🔒', '🟡', '🔒']),
            ('Quản lý liên kết chiến dịch UTM', ['✅', '🟡', '🔒', '🔒', '🟡', '🔒', '✅', '🔒']),
            ('Xóa Lead / Chuyển vào rác', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒']),
        ]
    },
    {
        'group_code': 'ACADEMIC',
        'group_title': '8. Học viên, Đào tạo & Điều phối Học vụ',
        'rows': [
            ('Xem danh sách & hồ sơ học viên', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']),
            ('Quản lý tiến độ Visa du học & XKLĐ', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']),
            ('Quản lý lớp học tiếng Hàn', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']),
            ('Cập nhật điểm danh & điểm thi học viên', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']),
            ('Gửi thông báo học vụ & lịch phỏng vấn', ['🟡', '🔒', '🔒', '🔒', '🔒', '🔒', '🔒', '✅']),
        ]
    }
]


def get_avatar_meta(admin):
    username = str(admin.get('username', '')).strip()
    full_name = str(admin.get('full_name') or username).strip()
    parts = full_name.split()
    if len(parts) >= 2:
        initials = (parts[0][0] + parts[-1][0]).upper()
    elif len(full_name) >= 2:
        initials = full_name[:2].upper()
    else:
        initials = username[:2].upper()

    if username == 'admin':
        return 'SA', 'linear-gradient(135deg, #ef4444, #991b1b)', '#ffffff'
    elif username == 'nguyena':
        return 'NA', 'linear-gradient(135deg, #f59e0b, #d97706)', '#ffffff'
    elif username == 'nguyenb':
        return 'NB', 'linear-gradient(135deg, #ea580c, #c2410c)', '#ffffff'
    elif username == 'nguyenc':
        return 'NC', 'linear-gradient(135deg, #8b5cf6, #6d28d9)', '#ffffff'
    elif username == 'nguyend':
        return 'ND', 'linear-gradient(135deg, #10b981, #047857)', '#ffffff'

    colors = [
        ('linear-gradient(135deg, #3b82f6, #1d4ed8)', '#ffffff'),
        ('linear-gradient(135deg, #06b6d4, #0891b2)', '#ffffff'),
        ('linear-gradient(135deg, #ec4899, #be185d)', '#ffffff'),
        ('linear-gradient(135deg, #14b8a6, #0f766e)', '#ffffff'),
    ]
    idx = abs(hash(username)) % len(colors)
    return initials, colors[idx][0], colors[idx][1]


def render_full_dashboard(leads, admins, active_tab='accounts', subview='users', query_params=None):
    if query_params is None:
        query_params = {}

    # Normalize query params if values are lists (from parse_qs)
    norm_params = {}
    for k, v in query_params.items():
        if isinstance(v, list):
            norm_params[k] = v[0] if v else ''
        else:
            norm_params[k] = v
    query_params = norm_params

    admin_created = 'admin_created' in query_params
    status_updated = 'status_updated' in query_params
    deleted_admin = 'deleted_admin' in query_params
    error = query_params.get('error', '')
    active_subview = query_params.get('subview', subview)
    if isinstance(active_subview, list):
        active_subview = active_subview[0] if active_subview else 'users'

    # Calculate statistics
    total_roles = 8
    total_assigned_users = len(admins)
    total_permission_groups = 12
    active_users = sum(1 for a in admins if a.get('status') == 'active')
    health_percent = int((active_users / max(1, total_assigned_users)) * 100)

    # Role counts and user map
    role_user_map = {k: [] for k in ROLES_CONFIG.keys()}
    for a in admins:
        r = a.get('role', 'super_admin')
        if r in role_user_map:
            role_user_map[r].append(a)

    # Notification Banners
    banner_html = ''
    if admin_created:
        banner_html += '<div class="banner banner-success">✓ Đã tạo tài khoản nhân sự mới thành công và đồng bộ trực tiếp vào CSDL!</div>'
    if status_updated:
        banner_html += '<div class="banner banner-success">✓ Đã cập nhật trạng thái hoạt động của nhân sự trong CSDL!</div>'
    if deleted_admin:
        banner_html += '<div class="banner banner-success">✓ Đã xóa tài khoản nhân sự khỏi CSDL!</div>'
    if error == 'duplicate':
        banner_html += '<div class="banner banner-error">Lỗi: Tên đăng nhập hoặc Email này đã tồn tại trong CSDL.</div>'
    elif error == 'username':
        banner_html += '<div class="banner banner-error">Lỗi: Tên đăng nhập phải có ít nhất 3 ký tự (chữ cái, số, ., -, _).</div>'
    elif error == 'fullname':
        banner_html += '<div class="banner banner-error">Lỗi: Họ và tên nhân sự không được để trống (tối thiểu 2 ký tự).</div>'
    elif error == 'password_short':
        banner_html += '<div class="banner banner-error">Lỗi: Mật khẩu khởi tạo phải có từ 8 ký tự trở lên.</div>'
    elif error in ('cant_lock_root', 'cant_delete_root'):
        banner_html += '<div class="banner banner-error">Lỗi an ninh: Không thể khóa hoặc xóa tài khoản Tổng quản trị hệ thống gốc.</div>'

    # Build View 1: Team Members Table (AdminOS + Sneat Activity Table)
    user_rows = []
    for a in admins:
        r_key = a.get('role', 'super_admin')
        r_conf = ROLES_CONFIG.get(r_key, {
            'code': '00', 'title': r_key, 'badge': r_key, 'color': '#64748b',
            'bg_color': '#f1f5f9', 'border_color': '#cbd5e1'
        })
        initials, av_bg, av_fg = get_avatar_meta(a)
        is_active = a.get('status') == 'active'
        st_dot_color = '#10b981' if is_active else '#ef4444'
        st_text = 'Hoạt động' if is_active else 'Đã khóa'
        st_bg = '#ecfdf5' if is_active else '#fef2f2'
        st_fg = '#065f46' if is_active else '#991b1b'

        actions_html = ''
        if a.get('id', 0) > 1:
            toggle_txt = 'Khóa' if is_active else 'Mở khóa'
            toggle_color = '#dc2626' if is_active else '#059669'
            actions_html = f'''
            <form method="post" action="/admin/" style="display:inline-block;margin-right:6px;">
                <input type="hidden" name="action" value="toggle_admin_status">
                <input type="hidden" name="target_id" value="{a['id']}">
                <button type="submit" class="btn-sm" style="background:{toggle_color};color:#fff;">{toggle_txt}</button>
            </form>
            <form method="post" action="/admin/" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tài khoản @{a['username']} khỏi CSDL?');">
                <input type="hidden" name="action" value="delete_admin">
                <input type="hidden" name="target_id" value="{a['id']}">
                <button type="submit" class="btn-sm" style="background:#64748b;color:#fff;">Xóa</button>
            </form>
            '''
        else:
            actions_html = '<span class="chip-root">Root Admin</span>'

        full_name_display = a.get('full_name') or a.get('username')
        user_rows.append(f'''
        <tr class="user-row" data-dept="{a.get('department', '')}" data-role="{r_key}" data-search="{full_name_display.lower()} {a.get('username','').lower()} {str(a.get('email','')).lower()}">
            <td style="width:40px;text-align:center;">
                <input type="checkbox" class="user-cb">
            </td>
            <td>
                <div class="user-cell">
                    <div class="avatar-circle" style="background:{av_bg};color:{av_fg};">{initials}</div>
                    <div class="user-meta">
                        <div class="user-name">{full_name_display}</div>
                        <div class="user-handle">@{a.get('username')}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="contact-email">{a.get('email') or '—'}</div>
                <div class="contact-phone">{a.get('phone') or '—'}</div>
            </td>
            <td>
                <span class="badge-dept">{a.get('department', 'Ban Giám Đốc')}</span>
            </td>
            <td>
                <span class="badge-role" style="background:{r_conf['bg_color']};color:{r_conf['color']};border-color:{r_conf['border_color']};">
                    {r_conf['code']}. {r_conf['title']}
                </span>
            </td>
            <td>
                <span class="badge-scope">{a.get('scope', 'Toàn hệ thống')}</span>
            </td>
            <td>
                <span class="status-pill" style="background:{st_bg};color:{st_fg};">
                    <span class="status-dot" style="background:{st_dot_color};"></span>
                    {st_text}
                </span>
            </td>
            <td style="white-space:nowrap;text-align:right;">
                {actions_html}
            </td>
        </tr>
        ''')

    role_select_options = ''.join([
        f'<option value="{k}">{v["code"]}. {v["title"]} — {v["badge"]}</option>'
        for k, v in ROLES_CONFIG.items()
    ])

    # Build View 2: Sneat-style Roles Cards Grid
    role_cards = []
    for r_k, r_v in ROLES_CONFIG.items():
        assigned = role_user_map.get(r_k, [])
        count_u = len(assigned)
        avatars_stack_html = ''
        if count_u > 0:
            avatars_stack_html = '<div class="avatar-stack">'
            for u in assigned[:3]:
                u_in, u_bg, u_fg = get_avatar_meta(u)
                avatars_stack_html += f'<div class="avatar-sm" style="background:{u_bg};color:{u_fg};" title="{u.get("full_name") or u.get("username")}">{u_in}</div>'
            if count_u > 3:
                avatars_stack_html += f'<div class="avatar-sm avatar-more">+{count_u - 3}</div>'
            avatars_stack_html += '</div>'
        else:
            avatars_stack_html = '<span class="text-muted-xs">Chưa có nhân sự</span>'

        role_cards.append(f'''
        <div class="role-card" style="border-top:3px solid {r_v['color']};">
            <div class="role-card-top">
                <div class="role-user-count">Tổng: <strong>{count_u}</strong> nhân sự</div>
                {avatars_stack_html}
            </div>
            <div class="role-card-header">
                <span class="role-code-badge" style="background:{r_v['bg_color']};color:{r_v['color']};">{r_v['code']}</span>
                <h3 class="role-card-title">{r_v['title']}</h3>
            </div>
            <div class="role-badge-pill" style="background:{r_v['bg_color']};color:{r_v['color']};border-color:{r_v['border_color']};">
                {r_v['badge']}
            </div>
            <p class="role-card-desc">{r_v['desc']}</p>
            <div class="role-card-footer">
                <a href="javascript:void(0)" onclick="openAddUserWithRole('{r_k}')" class="role-link" style="color:#2563eb;font-weight:700;">+ Thêm vào vai trò này</a>
                <a href="javascript:void(0)" onclick="filterByRole('{r_k}')" class="role-link">Lọc ({count_u})</a>
                <a href="javascript:void(0)" onclick="switchSubview('matrix')" class="role-link secondary">Ma trận quyền →</a>
            </div>
        </div>
        ''')

    # Card 9: Add New Role / Custom RBAC
    role_cards.append('''
    <div class="role-card role-card-add">
        <div class="add-role-illustration">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <path d="M12 8v8"></path>
                <path d="M8 12h8"></path>
            </svg>
        </div>
        <h3 class="role-card-title" style="margin-top:12px;">+ Tạo vai trò mới</h3>
        <p class="role-card-desc">Tùy biến nhóm quyền P01-P12 và cấu hình vai trò riêng cho bộ phận.</p>
        <button type="button" class="btn-create-role" onclick="switchSubview('add')">+ Thêm tài khoản nhân sự</button>
    </div>
    ''')

    # Build View 3: Taskora-style Permission Matrix Table
    matrix_sections_html = []
    for sec in PERMISSION_MATRIX_SECTIONS:
        sec_rows = []
        for r_title, r_badges in sec['rows']:
            tds = []
            for b in r_badges:
                if b == '✅':
                    tds.append('<td class="mat-cell"><span class="mat-badge mat-allow" title="Được phép">✅</span></td>')
                elif b == '🟡':
                    tds.append('<td class="mat-cell"><span class="mat-badge mat-cond" title="Có điều kiện / theo phạm vi">🟡</span></td>')
                else:
                    tds.append('<td class="mat-cell"><span class="mat-badge mat-deny" title="Không được phép">🔒</span></td>')
            sec_rows.append(f'''
            <tr class="mat-row">
                <td class="mat-func">{r_title}</td>
                {''.join(tds)}
            </tr>
            ''')

        matrix_sections_html.append(f'''
        <div class="mat-section">
            <div class="mat-sec-header">
                <span class="mat-sec-code">{sec['group_code']}</span>
                <span class="mat-sec-title">{sec['group_title']}</span>
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
                    <tbody>
                        {''.join(sec_rows)}
                    </tbody>
                </table>
            </div>
        </div>
        ''')

    # Build Leads Tab HTML
    leads_rows = []
    for l in leads:
        leads_rows.append(f'''
        <tr>
            <td style="font-weight:bold;color:#1e293b;">#{l.get('id')}</td>
            <td style="font-size:12px;color:#64748b;white-space:nowrap;">{l.get('created_at')}</td>
            <td style="font-weight:bold;color:#0284c7;">{l.get('contact_name')}</td>
            <td style="font-weight:600;">{l.get('phone')}</td>
            <td><span class="badge-dept" style="background:#e0f2fe;color:#0369a1;">{l.get('form_type')}</span></td>
            <td style="font-size:12px;"><a href="{l.get('source_page')}" target="_blank" style="color:#0284c7;">{l.get('source_page')}</a></td>
        </tr>
        ''')
    leads_table_html = f'''
    <div class="table-wrap" style="background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Thời gian</th><th>Họ và tên</th><th>Số điện thoại</th><th>Biểu mẫu</th><th>Trang nguồn</th>
                </tr>
            </thead>
            <tbody>
                {''.join(leads_rows) if leads else '<tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">Chưa có dữ liệu đăng ký tư vấn.</td></tr>'}
            </tbody>
        </table>
    </div>
    '''

    # Complete HTML Document
    html = f'''<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KIPA AdminOS | Hệ thống Phân quyền 8 Vai trò</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {{
            --bg-body: #f8fafc;
            --bg-sidebar: #091E3A;
            --sidebar-border: #132d52;
            --sidebar-text: #94a3b8;
            --sidebar-hover: #102a4e;
            --sidebar-active: #2563eb;
            --primary: #0F5B9C;
            --primary-hover: #0d4e85;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --radius-md: 10px;
            --radius-lg: 14px;
        }}
        * {{ box-sizing: border-box; margin: 0; padding: 0; }}
        body {{
            background: var(--bg-body);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            min-height: 100vh;
        }}

        /* SIDEBAR (AdminOS Style) */
        .sidebar {{
            width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            color: #fff;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }}
        .brand-header {{
            padding: 22px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--sidebar-border);
        }}
        .brand-icon {{
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 4px 10px rgba(37,99,235,0.3);
        }}
        .brand-title {{
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.3px;
            color: #fff;
        }}
        .brand-badge {{
            font-size: 10px;
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            margin-top: 2px;
            display: inline-block;
        }}

        .nav-section-title {{
            padding: 20px 20px 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
        }}
        .nav-menu {{
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0 12px;
        }}
        .nav-link {{
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            border-radius: 8px;
            transition: all 0.15s ease;
        }}
        .nav-link:hover {{
            background: var(--sidebar-hover);
            color: #fff;
        }}
        .nav-link.active {{
            background: var(--sidebar-active);
            color: #fff;
            box-shadow: 0 2px 8px rgba(37,99,235,0.35);
        }}
        .nav-link .nav-badge {{
            margin-left: auto;
            background: rgba(255,255,255,0.2);
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
        }}

        .sidebar-footer {{
            margin-top: auto;
            padding: 16px;
            border-top: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(0,0,0,0.15);
        }}
        .user-avatar-sm {{
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ef4444, #991b1b);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }}
        .user-info {{
            flex: 1;
            min-width: 0;
        }}
        .user-info-name {{
            font-weight: 700;
            font-size: 13px;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }}
        .user-info-role {{
            font-size: 11px;
            color: #94a3b8;
        }}
        .btn-logout {{
            background: transparent;
            border: 1px solid #334155;
            color: #cbd5e1;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.15s ease;
        }}
        .btn-logout:hover {{
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }}

        /* MAIN CONTENT AREA */
        .main-layout {{
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }}
        .topbar {{
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 20;
        }}
        .topbar-search {{
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f1f5f9;
            padding: 8px 14px;
            border-radius: 8px;
            width: 340px;
        }}
        .topbar-search input {{
            border: 0;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 13px;
            font-family: inherit;
        }}
        .topbar-right {{
            display: flex;
            align-items: center;
            gap: 16px;
        }}
        .dev-badge-top {{
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }}

        .content-container {{
            padding: 28px 32px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }}

        /* PAGE HEADER */
        .page-header {{
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }}
        .page-title {{
            font-size: 24px;
            font-weight: 800;
            color: #081d36;
            letter-spacing: -0.4px;
        }}
        .page-subtitle {{
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 4px;
        }}
        .header-actions {{
            display: flex;
            gap: 10px;
        }}
        .btn-primary {{
            background: #2563eb;
            color: #fff;
            border: 0;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(37,99,235,0.25);
            transition: all 0.15s ease;
        }}
        .btn-primary:hover {{ background: #1d4ed8; }}
        .btn-secondary {{
            background: #fff;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }}
        .btn-secondary:hover {{ background: #f8fafc; border-color: #94a3b8; }}

        /* STAT CARDS (AdminOS Style) */
        .stats-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }}
        .stat-card {{
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }}
        .stat-meta-title {{
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }}
        .stat-value {{
            font-size: 28px;
            font-weight: 800;
            color: #081d36;
            margin: 6px 0 2px;
            letter-spacing: -0.5px;
        }}
        .stat-desc {{
            font-size: 12px;
            color: #94a3b8;
        }}
        .stat-icon-wrap {{
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }}

        /* BANNERS */
        .banner {{
            padding: 12px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }}
        .banner-success {{
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }}
        .banner-error {{
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }}

        /* SUBVIEWS SEGMENTED BAR */
        .subviews-bar {{
            display: flex;
            gap: 8px;
            background: #fff;
            padding: 6px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            margin-bottom: 22px;
            width: fit-content;
            flex-wrap: wrap;
        }}
        .subview-btn {{
            border: 0;
            background: transparent;
            padding: 8px 16px;
            border-radius: 7px;
            font-weight: 700;
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }}
        .subview-btn:hover {{
            background: #f1f5f9;
            color: #1e293b;
        }}
        .subview-btn.active {{
            background: #2563eb;
            color: #fff;
            box-shadow: 0 1px 3px rgba(37,99,235,0.25);
        }}

        /* ADD USER CARD / DRAWER */
        .add-user-card {{
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            display: none;
        }}
        .add-user-card.open {{ display: block; }}
        .form-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }}
        .form-group label {{
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }}
        .form-group input, .form-group select {{
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            background: #f8fafc;
            outline: none;
            transition: all 0.15s ease;
            font-family: inherit;
        }}
        .form-group input:focus, .form-group select:focus {{
            border-color: #2563eb;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }}

        /* TABLE VIEW (AdminOS Activity Table) */
        .table-card {{
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
            overflow: hidden;
        }}
        .table-toolbar {{
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
        }}
        .table-search {{
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            width: 300px;
        }}
        .table-search input {{
            border: 0;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 13px;
            font-family: inherit;
        }}
        .filter-select {{
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            background: #fff;
            outline: none;
            font-family: inherit;
        }}
        .table-wrap {{
            overflow-x: auto;
        }}
        table.data-table {{
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }}
        table.data-table thead th {{
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }}
        table.data-table tbody td {{
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }}
        table.data-table tbody tr:hover {{
            background: #f8fafc;
        }}

        .user-cell {{
            display: flex;
            align-items: center;
            gap: 12px;
        }}
        .avatar-circle {{
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }}
        .user-name {{
            font-weight: 700;
            color: #0f172a;
        }}
        .user-handle {{
            font-size: 12px;
            color: #64748b;
        }}
        .contact-email {{
            font-weight: 500;
            color: #334155;
        }}
        .contact-phone {{
            font-size: 12px;
            color: #64748b;
        }}
        .badge-dept {{
            background: #f1f5f9;
            color: #334155;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }}
        .badge-role {{
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
            display: inline-block;
            white-space: nowrap;
        }}
        .badge-scope {{
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            display: inline-block;
        }}
        .status-pill {{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }}
        .status-dot {{
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }}
        .chip-root {{
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
        }}
        .btn-sm {{
            border: 0;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }}
        .btn-sm:hover {{ opacity: 0.85; }}

        /* SNEAT ROLES CARDS GRID */
        .roles-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }}
        .role-card {{
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 22px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }}
        .role-card:hover {{
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }}
        .role-card-top {{
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }}
        .role-user-count {{
            font-size: 13px;
            color: #64748b;
        }}
        .avatar-stack {{
            display: flex;
            align-items: center;
        }}
        .avatar-sm {{
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            margin-left: -8px;
            border: 2px solid #fff;
        }}
        .avatar-stack .avatar-sm:first-child {{ margin-left: 0; }}
        .avatar-more {{
            background: #e2e8f0;
            color: #475569;
        }}
        .role-card-header {{
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }}
        .role-code-badge {{
            font-size: 11px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
        }}
        .role-card-title {{
            font-size: 16px;
            font-weight: 700;
            color: #081d36;
        }}
        .role-badge-pill {{
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid transparent;
            margin-bottom: 12px;
            align-self: flex-start;
        }}
        .role-card-desc {{
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 18px;
            flex: 1;
        }}
        .role-card-footer {{
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
        }}
        .role-link {{
            color: #2563eb;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }}
        .role-link:hover {{ text-decoration: underline; }}
        .role-link.secondary {{ color: #64748b; font-weight: 600; }}

        .role-card-add {{
            border: 2px dashed #cbd5e1;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 240px;
        }}
        .add-role-illustration {{
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }}
        .btn-create-role {{
            margin-top: 14px;
            background: #2563eb;
            color: #fff;
            border: 0;
            padding: 8px 16px;
            border-radius: 7px;
            font-weight: 700;
            cursor: pointer;
            font-size: 12px;
        }}

        /* TASKORA PERMISSION MATRIX */
        .matrix-intro {{
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }}
        .matrix-legend {{
            display: flex;
            gap: 16px;
            font-size: 13px;
        }}
        .mat-section {{
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }}
        .mat-sec-header {{
            background: #f8fafc;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }}
        .mat-sec-code {{
            background: #e2e8f0;
            color: #334155;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 800;
        }}
        .mat-sec-title {{
            font-weight: 800;
            font-size: 14px;
            color: #081d36;
        }}
        table.mat-table {{
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }}
        table.mat-table thead th {{
            background: #fff;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
            text-align: center;
        }}
        table.mat-table thead th.mat-th-func {{
            text-align: left;
            width: 280px;
            padding-left: 18px;
        }}
        table.mat-table tbody td {{
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            text-align: center;
        }}
        table.mat-table tbody td.mat-func {{
            text-align: left;
            font-weight: 600;
            color: #1e293b;
            padding-left: 18px;
        }}
        .mat-badge {{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            font-size: 14px;
        }}
        .mat-allow {{ background: #ecfdf5; }}
        .mat-cond {{ background: #fffbeb; }}
        .mat-deny {{ background: #f8fafc; }}

        @media (max-width: 900px) {{
            body {{ flex-direction: column; }}
            .sidebar {{ width: 100%; height: auto; position: static; }}
            .content-container {{ padding: 18px; }}
            .topbar {{ padding: 12px 18px; }}
        }}
    </style>
</head>
<body>

    <!-- SIDEBAR (AdminOS style) -->
    <aside class="sidebar">
        <div class="brand-header">
            <div class="brand-icon">K</div>
            <div>
                <div class="brand-title">KIPA AdminOS</div>
                <span class="brand-badge">RBAC Enterprise v2.5</span>
            </div>
        </div>

        <div class="nav-section-title">Tổng quan & Vận hành</div>
        <nav class="nav-menu">
            <a href="/admin/?tab=leads" class="nav-link {'active' if active_tab == 'leads' else ''}">
                <span>📋</span>
                <span>Đăng ký tư vấn (Leads)</span>
                <span class="nav-badge">{len(leads)}</span>
            </a>
            <a href="/admin/?tab=accounts" class="nav-link {'active' if active_tab == 'accounts' else ''}">
                <span>👥</span>
                <span>Nhân sự & Phân quyền</span>
                <span class="nav-badge">{len(admins)}</span>
            </a>
            <a href="https://kipaviethan.edu.vn/api/cms.php" target="_blank" class="nav-link">
                <span>🌐</span>
                <span>Quản trị CMS A–J</span>
                <span style="font-size:10px;color:#94a3b8;">↗</span>
            </a>
            <a href="/admin/?tab=accounts&subview=matrix" class="nav-link">
                <span>📊</span>
                <span>Ma trận 8 Vai trò (RBAC)</span>
            </a>
            <a href="/admin/?tab=audit" class="nav-link" onclick="alert('Nhật ký bảo mật Audit Log đang được ghi nhận trực tiếp vào máy chủ.');return false;">
                <span>🛡️</span>
                <span>Nhật ký bảo mật</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-avatar-sm">SA</div>
            <div class="user-info">
                <div class="user-info-name">Tổng quản trị KIPA</div>
                <div class="user-info-role">Super Admin · Toàn quyền</div>
            </div>
            <form method="post" action="/admin/">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-logout" title="Đăng xuất khỏi hệ thống">Thoát</button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="main-layout">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-search">
                <span style="color:#94a3b8;">🔍</span>
                <input type="text" placeholder="Tìm kiếm tài khoản, vai trò, dữ liệu..." id="globalSearchInput" onkeyup="handleSearch(this.value)">
            </div>
            <div class="topbar-right">
                <span class="dev-badge-top">CSDL SQLite Localhost: dev_leads.sqlite</span>
                <button type="button" class="btn-secondary" onclick="exportDataCSV()">📥 Xuất CSV</button>
                <div style="font-size:12px;color:#64748b;font-weight:600;">UTC: 2026-11-19</div>
            </div>
        </header>

        <main class="content-container">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">
                        {'Quản lý Nhân sự & Phân quyền (Roles & Permissions)' if active_tab == 'accounts' else 'Quản lý Đăng ký Tư vấn (Leads)'}
                    </h1>
                    <p class="page-subtitle">
                        {'Hệ thống kiểm soát truy cập theo vai trò (RBAC 8 vai trò chính & 12 nhóm quyền P01-P12) KIPA Việt Hàn.' if active_tab == 'accounts' else 'Danh sách học viên và phụ huynh đăng ký tư vấn du học & xuất khẩu lao động.'}
                    </p>
                </div>
                {'<div class="header-actions"><button type="button" class="btn-primary" onclick="switchSubview(\'add\')">➕ Thêm tài khoản mới</button></div>' if active_tab == 'accounts' else ''}
            </div>

            <!-- BANNERS -->
            {banner_html}

            <!-- STATS CARDS (AdminOS Style) -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-meta-title">TỔNG SỐ VAI TRÒ</div>
                        <div class="stat-value">{total_roles}</div>
                        <div class="stat-desc">8 vai trò chuẩn hóa hệ thống</div>
                    </div>
                    <div class="stat-icon-wrap" style="background:#eff6ff;color:#2563eb;">🛡️</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-meta-title">NHÂN SỰ ĐƯỢC GÁN</div>
                        <div class="stat-value">{total_assigned_users}</div>
                        <div class="stat-desc">1 Super Admin + 4 Nhân viên</div>
                    </div>
                    <div class="stat-icon-wrap" style="background:#ecfdf5;color:#059669;">👥</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-meta-title">NHÓM QUYỀN HỆ THỐNG</div>
                        <div class="stat-value">{total_permission_groups}</div>
                        <div class="stat-desc">P01 Dashboard → P12 System</div>
                    </div>
                    <div class="stat-icon-wrap" style="background:#f5f3ff;color:#7c3aed;">🧩</div>
                </div>
                <div class="stat-card">
                    <div>
                        <div class="stat-meta-title">TRẠNG THÁI VẬN HÀNH</div>
                        <div class="stat-value">{health_percent}%</div>
                        <div class="stat-desc">100% tài khoản hoạt động</div>
                    </div>
                    <div class="stat-icon-wrap" style="background:#ecfdf5;color:#10b981;">⚡</div>
                </div>
            </div>

            <!-- TAB CONTENT: ACCOUNTS -->
            {f'''
            <!-- SUBVIEW SWITCHER (Sneat & AdminOS tabs) -->
            <div class="subviews-bar">
                <button type="button" class="subview-btn {'active' if active_subview == 'users' else ''}" onclick="switchSubview('users')" id="btnSubUsers">
                    👥 Danh sách Nhân sự ({len(admins)})
                </button>
                <button type="button" class="subview-btn {'active' if active_subview == 'add' else ''}" onclick="switchSubview('add')" id="btnSubAdd" style="background:{'#2563eb' if active_subview == 'add' else '#f1f5f9'};color:{'#fff' if active_subview == 'add' else '#1e293b'};font-weight:700;">
                    ➕ Thêm tài khoản mới
                </button>
                <button type="button" class="subview-btn {'active' if active_subview == 'roles' else ''}" onclick="switchSubview('roles')" id="btnSubRoles">
                    🏷️ 8 Thẻ Vai trò chính (Sneat)
                </button>
                <button type="button" class="subview-btn {'active' if active_subview == 'matrix' else ''}" onclick="switchSubview('matrix')" id="btnSubMatrix">
                    📊 Ma trận Phân quyền chi tiết (Taskora)
                </button>
            </div>

            <!-- VIEW: THÊM TÀI KHOẢN MỚI (Admin & Super Admin) -->
            <div id="viewAdd" class="subview-panel" style="display:{'block' if active_subview == 'add' else 'none'};">
                <div class="add-user-card open" id="addUserCard" style="display:block;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <h2 style="font-size:18px;font-weight:800;color:#081d36;">➕ Tạo tài khoản Quản trị & Nhân sự mới</h2>
                        <button type="button" onclick="switchSubview('users')" style="background:none;border:none;font-size:18px;cursor:pointer;color:#64748b;" title="Đóng">✕</button>
                    </div>
                    <p style="font-size:13px;color:#64748b;margin-top:4px;">
                        Là <strong>Super Admin</strong>, bạn có toàn quyền khởi tạo tài khoản nhân sự, phân bổ 1 trong 8 vai trò và lưu trực tiếp vào CSDL hệ thống.
                    </p>
                    <form method="post" action="/admin/">
                        <input type="hidden" name="action" value="create_admin">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Tên đăng nhập (Username) *</label>
                                <input name="username" id="addUsernameInput" required minlength="3" maxlength="64" placeholder="Ví dụ: nguyene">
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
                                <select name="role" id="addRoleSelect">
                                    {role_select_options}
                                </select>
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
                            <button type="submit" class="btn-primary" style="padding:10px 22px;font-weight:700;">+ Lưu và Tạo tài khoản vào CSDL</button>
                            <button type="button" class="btn-secondary" onclick="switchSubview('users')">Quay lại danh sách</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- VIEW 1: Danh sách Nhân sự (AdminOS Activity Table) -->
            <div id="viewUsers" class="subview-panel" style="display:{'block' if active_subview == 'users' else 'none'};">
                <div class="table-card">
                    <div class="table-toolbar">
                        <div class="table-search">
                            <span style="color:#94a3b8;">🔍</span>
                            <input type="text" id="userSearchInput" placeholder="Tìm theo tên, username, email..." onkeyup="filterUserTable()">
                        </div>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
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
                            <button type="button" class="btn-primary" onclick="switchSubview('add')" style="padding:8px 16px;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;">
                                ➕ Thêm tài khoản mới
                            </button>
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
                            <tbody>
                                {''.join(user_rows)}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VIEW 2: 8 Thẻ Vai trò chính (Sneat Cards) -->
            <div id="viewRoles" class="subview-panel" style="display:{'block' if active_subview == 'roles' else 'none'};">
                <div class="roles-grid">
                    {''.join(role_cards)}
                </div>
            </div>

            <!-- VIEW 3: Ma trận Phân quyền Taskora -->
            <div id="viewMatrix" class="subview-panel" style="display:{'block' if active_subview == 'matrix' else 'none'};">
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
                {''.join(matrix_sections_html)}
            </div>
            ''' if active_tab == 'accounts' else leads_table_html}

        </main>
    </div>

    <!-- CLIENT SCRIPT -->
    <script>
        function switchSubview(sub) {{
            const views = ['users', 'add', 'roles', 'matrix'];
            views.forEach(v => {{
                const el = document.getElementById('view' + v.charAt(0).toUpperCase() + v.slice(1));
                const btn = document.getElementById('btnSub' + v.charAt(0).toUpperCase() + v.slice(1));
                if (el) el.style.display = (v === sub) ? 'block' : 'none';
                if (btn) {{
                    if (v === sub) btn.classList.add('active');
                    else btn.classList.remove('active');
                }}
            }});
            window.scrollTo({{ top: 0, behavior: 'smooth' }});
        }}

        function toggleAddUserForm() {{
            switchSubview('add');
        }}

        function openAddUserWithRole(roleKey) {{
            switchSubview('add');
            const sel = document.getElementById('addRoleSelect');
            if (sel) {{
                sel.value = roleKey;
            }}
            const inputUser = document.getElementById('addUsernameInput');
            if (inputUser) inputUser.focus();
        }}

        function filterUserTable() {{
            const search = (document.getElementById('userSearchInput')?.value || '').toLowerCase().trim();
            const dept = document.getElementById('deptFilter')?.value || '';
            const role = document.getElementById('roleFilter')?.value || '';
            const rows = document.querySelectorAll('#usersTable tbody tr.user-row');

            rows.forEach(r => {{
                const rSearch = r.getAttribute('data-search') || '';
                const rDept = r.getAttribute('data-dept') || '';
                const rRole = r.getAttribute('data-role') || '';

                const matchSearch = !search || rSearch.includes(search);
                const matchDept = !dept || rDept === dept;
                const matchRole = !role || rRole === role;

                if (matchSearch && matchDept && matchRole) {{
                    r.style.display = '';
                }} else {{
                    r.style.display = 'none';
                }}
            }});
        }}

        function filterByRole(roleKey) {{
            switchSubview('users');
            const roleFilter = document.getElementById('roleFilter');
            if (roleFilter) {{
                roleFilter.value = roleKey;
                filterUserTable();
            }}
        }}

        function handleSearch(val) {{
            const userSearch = document.getElementById('userSearchInput');
            if (userSearch) {{
                userSearch.value = val;
                filterUserTable();
            }}
        }}

        function toggleSelectAll(master) {{
            const cbs = document.querySelectorAll('.user-cb');
            cbs.forEach(cb => cb.checked = master.checked);
        }}

        function exportDataCSV() {{
            let csv = "ID,HoTen,Username,Email,Phone,PhongBan,VaiTro,PhamVi,TrangThai\\n";
            const rows = document.querySelectorAll('#usersTable tbody tr.user-row');
            rows.forEach(r => {{
                const cells = r.querySelectorAll('td');
                if (cells.length >= 7) {{
                    const name = cells[1].querySelector('.user-name')?.innerText.trim() || '';
                    const handle = cells[1].querySelector('.user-handle')?.innerText.replace('@', '').trim() || '';
                    const email = cells[2].querySelector('.contact-email')?.innerText.trim() || '';
                    const phone = cells[2].querySelector('.contact-phone')?.innerText.trim() || '';
                    const dept = cells[3]?.innerText.trim() || '';
                    const role = cells[4]?.innerText.trim() || '';
                    const scope = cells[5]?.innerText.trim() || '';
                    const status = cells[6]?.innerText.trim() || '';
                    csv += '"' + handle + '","' + name + '","' + handle + '","' + email + '","' + phone + '","' + dept + '","' + role + '","' + scope + '","' + status + '"\\n';
                }}
            }});
            const blob = new Blob([csv], {{ type: 'text/csv;charset=utf-8;' }});
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.setAttribute("download", "kipa_staff_accounts.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }}
    </script>
</body>
</html>'''
    return html
