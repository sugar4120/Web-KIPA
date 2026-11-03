#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
KIPA VIET HAN - STATIC SITE BUILDER
Tu dong bien dich va dong goi toan bo 16 trang web KIPA thanh trang HTML tinh hoan chinh,
chuan SEO, tich hop Header, Footer dung chung, san sang deploy len Git/Vercel/Netlify/Hosting.
"""

import os
import re
import shutil

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

PAGES_CONFIG = [
    {
        'file': 'index.html',
        'slug': '',
        'title': 'KIPA Việt Hàn | Hệ Thống Du Học Hàn Quốc, Úc & Đào Tạo Tiếng Hàn Uy Tín',
        'desc': 'KIPA Việt Hàn - Đơn vị hàng đầu trong lĩnh vực tư vấn du học Hàn Quốc, du học Úc, xuất khẩu lao động và đào tạo tiếng Hàn TOPIK cấp tốc. Cam kết minh bạch, đồng hành trọn gói.',
        'keywords': 'du học hàn quốc, du học úc, kipa việt hàn, học tiếng hàn, xuất khẩu lao động'
    },
    {
        'file': 'gioi-thieu.html',
        'slug': 'gioi-thieu',
        'title': 'Giới Thiệu KIPA Việt Hàn | Hành Trình Kiến Tạo Ước Mơ & Tương Lai',
        'desc': 'Khám phá hành trình phát triển, sứ mệnh, tầm nhìn, giá trị cốt lõi và hệ thống cơ sở vật chất hiện đại của KIPA Việt Hàn trên toàn quốc.',
        'keywords': 'giới thiệu kipa, kipa việt hàn là ai, sứ mệnh kipa, tầm nhìn kipa'
    },
    {
        'file': 'doi-ngu.html',
        'slug': 'doi-ngu',
        'title': 'Đội Ngũ KIPA Việt Hàn | Ban Lãnh Đạo & Chuyên Gia Tuyển Sinh Tận Tâm',
        'desc': 'Đội ngũ chuyên gia tư vấn du học, giảng viên tiếng Hàn bản xứ và ban cố vấn giàu kinh nghiệm, tận tâm đồng hành cùng từng học viên KIPA.',
        'keywords': 'đội ngũ kipa, giáo viên tiếng hàn kipa, chuyên gia du học hàn quốc'
    },
    {
        'file': 'du-hoc.html',
        'slug': 'du-hoc',
        'title': 'Du Học Quốc Tế KIPA Việt Hàn | Hàn Quốc, Úc, Nhật Bản & Châu Âu',
        'desc': 'Tổng quan các chương trình du học quốc tế chính ngạch tại Hàn Quốc, Úc, Nhật Bản và các nước Châu Âu cùng KIPA Việt Hàn. Học bổng lên đến 100%.',
        'keywords': 'du học quốc tế, du học các nước, học bổng du học, kipa việt hàn'
    },
    {
        'file': 'du-hoc-han-quoc.html',
        'slug': 'du-hoc-han-quoc',
        'title': 'Du Học Hàn Quốc KIPA Việt Hàn | Lộ Trình Rõ Ràng, Vững Bước Tương Lai',
        'desc': 'Chương trình du học Hàn Quốc trọn gói: D4-1 học tiếng, D2 đại học, thạc sĩ, D4-3 THPT. Chi phí minh bạch, hỗ trợ xin visa, việc làm thêm và ký túc xá.',
        'keywords': 'du học hàn quốc, visa d4-1, visa d2, học bổng du học hàn quốc kipa'
    },
    {
        'file': 'du-hoc-uc.html',
        'slug': 'du-hoc-uc',
        'title': 'Du Học Úc Cùng KIPA Việt Hàn | Định Hướng Lộ Trình & Học Bổng Toàn Diện',
        'desc': 'Tư vấn du học Úc các hệ THPT, VET/TAFE học nghề, cử nhân và thạc sĩ. Học bổng hấp dẫn, cơ hội làm thêm hợp pháp và định cư sau tốt nghiệp.',
        'keywords': 'du học úc, visa 500 úc, học bổng du học úc, kipa việt hàn'
    },
    {
        'file': 'xuat-khau-lao-dong.html',
        'slug': 'xuat-khau-lao-dong',
        'title': 'Xuất Khẩu Lao Động & Việc Làm Quốc Tế | KIPA Việt Hàn Chính Ngạch',
        'desc': 'Chương trình làm việc tại Hàn Quốc (E7/E9), Nhật Bản (Tokutei), Úc và Châu Âu. Thu nhập cao, quy trình chuẩn, hỗ trợ đào tạo ngoại ngữ và tay nghề.',
        'keywords': 'xuất khẩu lao động, xkld hàn quốc, xkld nhật bản, việc làm quốc tế kipa'
    },
    {
        'file': 'tieng-han.html',
        'slug': 'tieng-han',
        'title': 'Đào Tạo Tiếng Hàn Cấp Tốc TOPIK 1 - 6 | KIPA Việt Hàn',
        'desc': 'Khóa học tiếng Hàn từ con số 0, tiếng Hàn du học, giao tiếp phản xạ và luyện thi TOPIK cấp tốc cùng giảng viên bản xứ Hàn Quốc tại KIPA.',
        'keywords': 'học tiếng hàn, luyện thi topik, tiếng hàn sơ cấp, tiếng hàn du học kipa'
    },
    {
        'file': 'co-so.html',
        'slug': 'co-so',
        'title': 'Hệ Thống Cơ Sở & Ký Túc Xá KIPA Việt Hàn Trên Toàn Quốc',
        'desc': 'Hệ thống cơ sở đào tạo, văn phòng tư vấn và ký túc xá tiện nghi của KIPA tại Hải Phòng, Nam Sách, Quảng Ninh, TP.HCM, Seoul (Hàn Quốc) và Sydney (Úc).',
        'keywords': 'cơ sở kipa, địa chỉ kipa hải phòng, văn phòng kipa seoul'
    },
    {
        'file': 'hop-tac-quoc-te.html',
        'slug': 'hop-tac-quoc-te',
        'title': 'Hợp Tác Quốc Tế & Liên Kết Giáo Dục | KIPA Việt Hàn',
        'desc': 'Mạng lưới đối tác chiến lược giữa KIPA Việt Hàn và hàng chục trường đại học, viện ngôn ngữ, tập đoàn hàng đầu tại Hàn Quốc, Úc và quốc tế.',
        'keywords': 'hợp tác quốc tế, liên kết đại học hàn quốc, đối tác kipa'
    },
    {
        'file': 'lien-he.html',
        'slug': 'lien-he',
        'title': 'Liên Hệ KIPA Việt Hàn | Kết Nối Đội Ngũ Tư Vấn 24/7',
        'desc': 'Thông tin liên hệ chính thức KIPA Việt Hàn. Hotline: 036 851 3865 - 0889 2222 55. Trụ sở tại Hải Phòng và văn phòng hỗ trợ tại Hàn Quốc, Úc.',
        'keywords': 'liên hệ kipa, hotline kipa, địa chỉ kipa việt hàn'
    },
    {
        'file': 'dang-ky-tu-van.html',
        'slug': 'dang-ky-tu-van',
        'title': 'Đăng Ký Tư Vấn Du Học & Việc Làm Quốc Tế | KIPA Việt Hàn',
        'desc': 'Để lại thông tin nhận tư vấn 1:1 miễn phí lộ trình du học Hàn Quốc, Úc, học tiếng Hàn và việc làm quốc tế phù hợp nhất với nguyện vọng của bạn.',
        'keywords': 'đăng ký tư vấn du học, tư vấn du học miễn phí kipa'
    },
    {
        'file': 'faq.html',
        'slug': 'faq',
        'title': 'Câu Hỏi Thường Gặp (FAQ) Về Du Học & Đào Tạo | KIPA Việt Hàn',
        'desc': 'Tổng hợp và giải đáp chi tiết tất cả các thắc mắc về chi phí, điều kiện, hồ sơ, visa, học bổng và cuộc sống du học sinh tại Hàn Quốc và Úc.',
        'keywords': 'faq du học hàn quốc, thắc mắc du học úc, câu hỏi thường gặp kipa'
    },
    {
        'file': 'chinh-sach-bao-mat.html',
        'slug': 'chinh-sach-bao-mat',
        'title': 'Chính Sách Bảo Mật Thông Tin Khách Hàng | KIPA Việt Hàn',
        'desc': 'Cam kết bảo mật tuyệt đối thông tin cá nhân và hồ sơ du học của học viên và phụ huynh tại KIPA Việt Hàn.',
        'keywords': 'chính sách bảo mật kipa, bảo vệ dữ liệu học viên'
    },
    {
        'file': 'cam-on.html',
        'slug': 'cam-on',
        'title': 'Cảm Ơn Bạn Đã Đăng Ký Tư Vấn | KIPA Việt Hàn',
        'desc': 'KIPA Việt Hàn đã nhận được thông tin của bạn. Đội ngũ chuyên gia sẽ liên hệ tư vấn trong thời gian sớm nhất.',
        'keywords': 'cảm ơn kipa việt hàn'
    },
    {
        'file': '404.html',
        'slug': '404',
        'title': '404 - Không Tìm Thấy Trang | KIPA Việt Hàn',
        'desc': 'Trang bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển. Vui lòng quay về trang chủ KIPA Việt Hàn.',
        'keywords': '404 not found kipa'
    }
]

def load_component(fname):
    path = os.path.join(BASE_DIR, fname)
    if os.path.isfile(path):
        with open(path, 'r', encoding='utf-8') as f:
            return f.read().strip()
    return ''

def extract_body_content(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # If it's already a full HTML page, extract inside <body ...> ... </body>
    body_match = re.search(r'<body[^>]*>(.*?)</body>', content, re.DOTALL | re.IGNORECASE)
    if body_match:
        inner = body_match.group(1).strip()
        # Remove any existing header or footer elements if duplicated
        inner = re.sub(r'<header[^>]*>.*?</header>', '', inner, flags=re.DOTALL | re.IGNORECASE)
        inner = re.sub(r'<footer[^>]*>.*?</footer>', '', inner, flags=re.DOTALL | re.IGNORECASE)
        return inner.strip()

    # If it's an inner snippet, strip existing doctype or html tags if any
    content = re.sub(r'<!DOCTYPE[^>]*>', '', content, flags=re.IGNORECASE)
    content = re.sub(r'<html[^>]*>', '', content, flags=re.IGNORECASE)
    content = re.sub(r'</html>', '', content, flags=re.IGNORECASE)
    content = re.sub(r'<head>.*?</head>', '', content, flags=re.DOTALL | re.IGNORECASE)
    content = re.sub(r'<header[^>]*>.*?</header>', '', content, flags=re.DOTALL | re.IGNORECASE)
    content = re.sub(r'<footer[^>]*>.*?</footer>', '', content, flags=re.DOTALL | re.IGNORECASE)
    
    return content.strip()

def render_full_html(cfg, body_content, header_html, footer_html):
    title = cfg['title']
    desc = cfg['desc']
    keywords = cfg.get('keywords', 'kipa việt hàn, du học hàn quốc, du học úc')
    canonical = f"https://kipaviethan.edu.vn/{cfg['slug'] + '/' if cfg['slug'] else ''}"
    
    return f"""<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{title}</title>
    <meta name="description" content="{desc}">
    <meta name="keywords" content="{keywords}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{canonical}">

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/Logo.jpg">
    <link rel="apple-touch-icon" href="/Logo.jpg">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{canonical}">
    <meta property="og:title" content="{title}">
    <meta property="og:description" content="{desc}">
    <meta property="og:image" content="https://kipaviethan.edu.vn/Logo.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{canonical}">
    <meta property="twitter:title" content="{title}">
    <meta property="twitter:description" content="{desc}">
    <meta property="twitter:image" content="https://kipaviethan.edu.vn/Logo.jpg">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Dancing+Script:wght@600;700&family=Phudu:wght@600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Base Styles -->
    <style>
        body {{
            font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            background-color: #fafbfc;
            margin: 0;
            padding: 0;
        }}
        .font-phudu {{ font-family: 'Phudu', sans-serif !important; }}
        .font-vietnam {{ font-family: 'Be Vietnam Pro', sans-serif !important; }}
        .font-script {{ font-family: 'Dancing Script', cursive !important; }}
        .text-brand-blue {{ color: #0274BD !important; }}
        .bg-brand-blue {{ background-color: #0274BD !important; }}
        .border-brand-blue {{ border-color: #0274BD !important; }}
        .text-brand-red {{ color: #EC1E1B !important; }}
        .bg-brand-red {{ background-color: #EC1E1B !important; }}
        .border-brand-red {{ border-color: #EC1E1B !important; }}
        .text-brand-yellow {{ color: #FFF017 !important; }}
        .bg-brand-yellow {{ background-color: #FFF017 !important; }}
        .bg-brand-navy {{ background-color: #081D36 !important; }}
        .text-brand-navy {{ color: #081D36 !important; }}
        html {{ scroll-behavior: smooth; }}
    </style>
</head>
<body class="antialiased bg-[#fafbfc] text-[#1e293b] flex flex-col min-h-screen">

    <!-- SITE HEADER -->
    {header_html}

    <!-- MAIN PAGE CONTENT -->
    <main class="flex-grow">
        {body_content}
    </main>

    <!-- SITE FOOTER -->
    {footer_html}

    <!-- Global Scripts -->
    <script>
        // Mobile Navigation Drawer Toggle
        document.addEventListener('DOMContentLoaded', function() {{
            const btn = document.getElementById('mobile-menu-toggle');
            const menu = document.getElementById('mobile-header-menu');
            if (btn && menu) {{
                btn.addEventListener('click', function(e) {{
                    e.stopPropagation();
                    menu.classList.toggle('hidden');
                }});
                document.addEventListener('click', function(e) {{
                    if (!menu.contains(e.target) && !btn.contains(e.target)) {{
                        menu.classList.add('hidden');
                    }}
                }});
            }}
        }});
    </script>
</body>
</html>
"""

def main():
    print("=" * 60)
    print("🏗️  KIPA VIỆT HÀN - BẮT ĐẦU BUILD STATIC SITE")
    print("=" * 60)

    header_html = load_component('header.html')
    footer_html = load_component('footer.html')

    print(f"✓ Đã tải Header ({len(header_html)} bytes)")
    print(f"✓ Đã tải Footer ({len(footer_html)} bytes)")
    print("-" * 60)

    count = 0
    for cfg in PAGES_CONFIG:
        fname = cfg['file']
        fpath = os.path.join(BASE_DIR, fname)
        if not os.path.isfile(fpath):
            print(f"⚠️  Bỏ qua {fname}: không tìm thấy file!")
            continue

        # Extract pure body content
        body = extract_body_content(fpath)
        
        # Render full standalone HTML
        full_html = render_full_html(cfg, body, header_html, footer_html)

        # 1. Update the root HTML file directly (e.g. gioi-thieu.html)
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(full_html)

        # 2. Also create clean directory with index.html for pretty URLs if slug exists
        # e.g. gioi-thieu/index.html
        slug = cfg['slug']
        if slug and slug != '404':
            slug_dir = os.path.join(BASE_DIR, slug)
            os.makedirs(slug_dir, exist_ok=True)
            slug_index = os.path.join(slug_dir, 'index.html')
            with open(slug_index, 'w', encoding='utf-8') as f:
                f.write(full_html)

        count += 1
        print(f"✓ [{count:02d}/{len(PAGES_CONFIG):02d}] Đã build thành công: {fname} (và /{slug}/ nếu có)")

    print("-" * 60)
    print(f"🎉 HOÀN THÀNH! Đã build {count} trang web độc lập, sẵn sàng deploy lên Git!")
    print("=" * 60)

if __name__ == '__main__':
    main()
