#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
KIPA VIET HAN - STATIC SITE BUILDER
Tu dong bien dich va dong goi toan bo 16 trang web KIPA thanh trang HTML tinh hoan chinh,
chuan SEO, tich hop Header, Footer dung chung, san sang deploy len Git/Vercel/Netlify/Hosting.
"""

import argparse
import json
import os
import re
import shutil
from pathlib import Path
from xml.sax.saxutils import escape

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
SITE_URL = 'https://kipaviethan.edu.vn'
NOINDEX_SLUGS = {'cam-on', '404', 'ho-so'}
PUBLIC_ASSET_SUFFIXES = {
    '.avif', '.css', '.gif', '.ico', '.jpeg', '.jpg', '.js', '.mp4',
    '.pdf', '.png', '.svg', '.ttf', '.webm', '.webp', '.woff', '.woff2',
}
API_FILES = {
    'api/.htaccess', 'api/admin.php', 'api/bootstrap.php', 'api/cleanup.php',
    'api/submit.php', 'api/content.php', 'api/content-model.json',
    'api/cms.php', 'api/migrate.php', 'api/publish.php',
    'api/mailer.php', 'api/otp.php', 'api/register.php', 'api/login.php',
    'api/account.php', 'api/reset-password.php',
}
KOREAN_COURSES = [
    ('nhap-mon', 'Tiếng Hàn nhập môn',
     'Làm quen Hangul, phát âm, từ vựng và câu cơ bản; giao tiếp tình huống đơn giản.'),
    ('so-cap', 'Tiếng Hàn sơ cấp',
     'Ngữ pháp và từ vựng nền tảng, giao tiếp cơ bản và phát triển kỹ năng nghe, nói, đọc, viết.'),
    ('trung-cap', 'Tiếng Hàn trung cấp',
     'Phát triển bốn kỹ năng nâng cao, giao tiếp theo chủ đề, chuẩn bị cho học tập và việc làm.'),
    ('du-hoc', 'Tiếng Hàn du học',
     'Ngôn ngữ phỏng vấn visa, kỹ năng học thuật và chuẩn bị đời sống tại Hàn Quốc.'),
    ('topik', 'Luyện thi TOPIK',
     'Ôn tập theo cấu trúc đề, chiến lược làm bài thi, thi thử và đánh giá định kỳ.'),
]

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
        'file': 'du-hoc-nhat-ban.html',
        'slug': 'du-hoc-nhat-ban',
        'title': 'Du Học Nhật Bản Cùng KIPA Việt Hàn | Lộ Trình Rõ Ràng, Vững Bước Tương Lai',
        'desc': 'Du học Nhật Bản cùng KIPA Việt Hàn: Mở rộng tri thức - Trải nghiệm văn hóa - Kiến tạo tương lai. Hỗ trợ trọn gói từ tư vấn chọn trường, đào tạo tiếng Nhật đến visa.',
        'keywords': 'du học nhật bản, du học nhật bản kipa, học bổng nhật bản, kipa việt hàn'
    },
    {
        'file': 'du-hoc-chau-au.html',
        'slug': 'du-hoc-chau-au',
        'title': 'Du Học Châu Âu Cùng KIPA Việt Hàn | Lộ Trình Rõ Ràng, Vững Bước Tương Lai',
        'desc': 'Du học Châu Âu cùng KIPA Việt Hàn: Đức, Pháp, Ý, Hà Lan, Ba Lan, Thụy Điển... Đồng hành toàn diện từ tư vấn chọn trường, săn học bổng đến visa trọn gói.',
        'keywords': 'du học châu âu, du học đức, du học pháp, du học ý, du học hà lan, du học ba lan'
    },
    {
        'file': 'xuat-khau-lao-dong.html',
        'slug': 'xuat-khau-lao-dong',
        'title': 'Xuất Khẩu Lao Động & Việc Làm Quốc Tế | KIPA Việt Hàn Chính Ngạch',
        'desc': 'Chương trình làm việc tại Hàn Quốc (E7/E9), Nhật Bản (Tokutei), Úc và Châu Âu. Thu nhập cao, quy trình chuẩn, hỗ trợ đào tạo ngoại ngữ và tay nghề.',
        'keywords': 'xuất khẩu lao động, xkld hàn quốc, xkld nhật bản, việc làm quốc tế kipa'
    },
    {
        'file': 'xuat-khau-lao-dong-han-quoc.html',
        'slug': 'xuat-khau-lao-dong-han-quoc',
        'title': 'Xuất Khẩu Lao Động Hàn Quốc | KIPA Việt Hàn EPS E-9 & E-7 Chính Ngạch',
        'desc': 'Chương trình xuất khẩu lao động Hàn Quốc chính ngạch cùng KIPA Việt Hàn: Visa EPS E-9 sản xuất, nông nghiệp; Visa kỹ sư E-7; Lao động thời vụ E-8.',
        'keywords': 'xuất khẩu lao động hàn quốc, xkld hàn quốc, visa e9 hàn quốc, visa e7 hàn quốc'
    },
    {
        'file': 'xuat-khau-lao-dong-uc.html',
        'slug': 'xuat-khau-lao-dong-uc',
        'title': 'Xuất Khẩu Lao Động Úc | KIPA Việt Hàn Lao Động Nông Nghiệp & Tay Nghề',
        'desc': 'Chương trình làm việc và định cư Úc chính ngạch cùng KIPA Việt Hàn: Nông nghiệp, kỹ năng tay nghề cao, visa TSS 482 và đào tạo ngoại ngữ.',
        'keywords': 'xuất khẩu lao động úc, xkld úc, visa 482 úc, lao động nông nghiệp úc'
    },
    {
        'file': 'xuat-khau-lao-dong-nhat-ban.html',
        'slug': 'xuat-khau-lao-dong-nhat-ban',
        'title': 'Xuất Khẩu Lao Động Nhật Bản | KIPA Việt Hàn Tokutei & Kỹ Sư Thu Nhập Cao',
        'desc': 'Chương trình làm việc Nhật Bản chính ngạch: Kỹ năng đặc định Tokutei Gino, thực tập sinh kỹ năng và kỹ sư công nghệ cao thu nhập hấp dẫn.',
        'keywords': 'xuất khẩu lao động nhật bản, xkld nhật bản, tokutei gino, thực tập sinh nhật bản'
    },
    {
        'file': 'xuat-khau-lao-dong-chau-au.html',
        'slug': 'xuat-khau-lao-dong-chau-au',
        'title': 'Xuất Khẩu Lao Động Châu Âu | KIPA Việt Hàn Đức, Ba Lan, Hungary, Rumani',
        'desc': 'Chương trình việc làm Châu Âu chính ngạch tại Đức, Ba Lan, Hungary, Rumani với thu nhập hấp dẫn, quy trình minh bạch và hợp đồng lâu dài.',
        'keywords': 'xuất khẩu lao động châu âu, việc làm đức, việc làm ba lan, xkld hungary, xkld rumani'
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
        'file': 'tin-tuc.html',
        'slug': 'tin-tuc',
        'title': 'Tin Tức & Cẩm Nang Du Học, Việc Làm Quốc Tế | KIPA Việt Hàn',
        'desc': 'Tổng hợp tin tức, cẩm nang du học Hàn Quốc, học tiếng Hàn TOPIK, kinh nghiệm chuẩn bị hồ sơ và việc làm quốc tế mới nhất từ KIPA Việt Hàn.',
        'keywords': 'tin tức du học hàn quốc, cẩm nang du học kipa, kinh nghiệm du học hàn quốc, học tiếng hàn topik'
    },
    {
        'file': 'cau-chuyen-hoc-vien.html',
        'slug': 'cau-chuyen-hoc-vien',
        'title': 'Câu Chuyện Học Viên KIPA Việt Hàn | Hành Trình Vươn Xa Quốc Tế',
        'desc': 'Những câu chuyện thật, trải nghiệm thật của học viên KIPA Việt Hàn trên hành trình du học và làm việc tại Hàn Quốc, Úc, Nhật Bản và Châu Âu.',
        'keywords': 'câu chuyện học viên, trải nghiệm du học kipa, du học hàn quốc kipa, du học úc, việc làm quốc tế kipa'
    },
    {
        'file': 'hoat-dong-su-kien.html',
        'slug': 'hoat-dong-su-kien',
        'title': 'Hoạt Động & Sự Kiện KIPA Việt Hàn | Kết nối cơ hội, phát triển tương lai',
        'desc': 'Khám phá hoạt động và sự kiện KIPA Việt Hàn: hội thảo du học, đào tạo tiếng Hàn, hợp tác giáo dục, tuyển sinh và các chương trình cộng đồng.',
        'keywords': 'Hoạt động KIPA Việt Hàn, sự kiện KIPA, hội thảo du học, tuyển sinh du học, đào tạo tiếng Hàn'
    },
    {
        'file': 'dang-ky-tu-van.html',
        'slug': 'dang-ky-tu-van',
        'title': 'Đăng Ký Tư Vấn Du Học & Việc Làm Quốc Tế | KIPA Việt Hàn',
        'desc': 'Để lại thông tin nhận tư vấn 1:1 miễn phí lộ trình du học Hàn Quốc, Úc, học tiếng Hàn và việc làm quốc tế phù hợp nhất với nguyện vọng của bạn.',
        'keywords': 'đăng ký tư vấn du học, tư vấn du học miễn phí kipa'
    },
    {
        'file': 'dang-ky-tai-khoan.html',
        'slug': 'dang-ky-tai-khoan',
        'title': 'Đăng Ký Tài Khoản Học Viên | KIPA Việt Hàn',
        'desc': 'Tạo tài khoản học viên KIPA Việt Hàn với xác thực email bằng mã OTP để theo dõi lộ trình du học, khóa học tiếng Hàn và nhận thông tin ưu tiên.',
        'keywords': 'đăng ký tài khoản kipa, tài khoản học viên kipa việt hàn'
    },
    {
        'file': 'dang-nhap.html',
        'slug': 'dang-nhap',
        'title': 'Đăng Nhập Tài Khoản Học Viên | KIPA Việt Hàn',
        'desc': 'Đăng nhập tài khoản học viên KIPA Việt Hàn để theo dõi lộ trình du học, khóa học tiếng Hàn và nhận hỗ trợ ưu tiên từ đội ngũ tư vấn.',
        'keywords': 'đăng nhập kipa, tài khoản học viên kipa việt hàn'
    },
    {
        'file': 'quen-mat-khau.html',
        'slug': 'quen-mat-khau',
        'title': 'Quên Mật Khẩu Tài Khoản Học Viên | KIPA Việt Hàn',
        'desc': 'Đặt lại mật khẩu tài khoản học viên KIPA Việt Hàn an toàn bằng mã OTP gửi qua email đã xác thực.',
        'keywords': 'quên mật khẩu kipa, đặt lại mật khẩu tài khoản học viên'
    },
    {
        'file': 'ho-so.html',
        'slug': 'ho-so',
        'title': 'Hồ Sơ Học Viên | KIPA Việt Hàn',
        'desc': 'Xem và cập nhật thông tin tài khoản học viên KIPA Việt Hàn: họ tên, số điện thoại và mật khẩu đăng nhập.',
        'keywords': 'hồ sơ học viên kipa, tài khoản kipa việt hàn'
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
    raise FileNotFoundError(f'Missing shared component: {path}')

def extract_body_content(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Unwrap older builds too: previous builds could nest the generated layout.
    layout_pattern = (
        r'<!-- MAIN PAGE CONTENT -->\s*<(?P<layout_tag>main|div)\b[^>]*>(?P<content>.*)'
        r'</(?P=layout_tag)>\s*<!-- SITE FOOTER -->'
    )
    match = re.search(layout_pattern, content, re.DOTALL | re.IGNORECASE)
    if match:
        content = match.group('content').strip()
        while True:
            match = re.search(layout_pattern, content, re.DOTALL | re.IGNORECASE)
            if not match:
                return content
            content = match.group('content').strip()

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

def page_url(cfg):
    return f"{SITE_URL}/{cfg['slug'] + '/' if cfg['slug'] else ''}"


def structured_data(cfg):
    organization_id = f'{SITE_URL}/#organization'
    graph = [{
        '@type': ['Organization', 'EducationalOrganization'],
        '@id': organization_id,
        'name': 'KIPA Việt Hàn',
        'url': f'{SITE_URL}/',
        'logo': f'{SITE_URL}/Logo.jpg',
        'email': 'duhocquoctekipaviethan@gmail.com',
        'telephone': '+84368513865',
        'address': {
            '@type': 'PostalAddress',
            'streetAddress': '135 Bạch Năng Thi, Phường Tân Hưng',
            'addressLocality': 'Hải Phòng',
            'addressCountry': 'VN',
        },
        'contactPoint': [{
            '@type': 'ContactPoint',
            'contactType': 'Tư vấn tuyển sinh',
            'telephone': phone,
            'availableLanguage': ['vi'],
        } for phone in ['+84368513865', '+84889222255']],
    }]
    if cfg['slug'] and cfg['slug'] not in NOINDEX_SLUGS:
        graph.append({
            '@type': 'BreadcrumbList',
            '@id': page_url(cfg) + '#breadcrumb',
            'itemListElement': [
                {'@type': 'ListItem', 'position': 1, 'name': 'Trang chủ',
                 'item': f'{SITE_URL}/'},
                {'@type': 'ListItem', 'position': 2,
                 'name': cfg['title'].split('|')[0].strip(),
                 'item': page_url(cfg)},
            ],
        })
    if cfg['slug'] == 'tieng-han':
        graph.extend({
            '@type': 'Course',
            '@id': f'{page_url(cfg)}#course-{slug}',
            'url': page_url(cfg) + '#khoa-hoc',
            'name': name,
            'description': description,
            'inLanguage': 'vi',
            'provider': {'@id': organization_id},
        } for slug, name, description in KOREAN_COURSES)
    # Keep JSON safe inside an HTML script element, including future content edits.
    return json.dumps(
        {'@context': 'https://schema.org', '@graph': graph},
        ensure_ascii=False, indent=4,
    ).replace('<', '\\u003c').replace('>', '\\u003e').replace('&', '\\u0026')


def render_full_html(cfg, body_content, header_html, footer_html):
    title = escape(cfg['title'], {'"': '&quot;'})
    desc = escape(cfg['desc'], {'"': '&quot;'})
    keywords = escape(cfg.get('keywords', 'kipa việt hàn, du học hàn quốc, du học úc'),
                      {'"': '&quot;'})
    canonical = page_url(cfg)
    robots = 'noindex, follow' if cfg['slug'] in NOINDEX_SLUGS else 'index, follow'
    layout_tag = 'div' if re.search(r'<main\b', body_content, re.IGNORECASE) else 'main'
    
    return f"""<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{title}</title>
    <meta name="description" content="{desc}">
    <meta name="keywords" content="{keywords}">
    <meta name="robots" content="{robots}">
    <link rel="canonical" href="{canonical}">
    <script type="application/ld+json">
{structured_data(cfg)}
    </script>

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

    <link rel="stylesheet" href="/assets/css/kipa.css?v=20261112-1">
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
    <{layout_tag} class="flex-grow">
        {body_content}
    </{layout_tag}>

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

def build_site(output_dir=None):
    output = Path(output_dir).resolve() if output_dir is not None else Path(BASE_DIR)
    staging = output != Path(BASE_DIR)
    if staging:
        if output.exists() and any(output.iterdir()):
            raise ValueError('Output directory must be empty to avoid publishing stale or private files.')
        output.mkdir(parents=True, exist_ok=True)

    required = [
        'header.html', 'footer.html', '.htaccess', 'robots.txt', 'Logo.jpg',
        'assets/css/kipa.css',
    ]
    required.extend(sorted(API_FILES))
    required.extend(cfg['file'] for cfg in PAGES_CONFIG)
    for name in required:
        if not (Path(BASE_DIR) / name).is_file():
            raise FileNotFoundError(f'Missing required site file: {name}')

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
        # Extract pure body content
        body = extract_body_content(fpath)
        
        # Render full standalone HTML
        full_html = render_full_html(cfg, body, header_html, footer_html)

        # 1. Update the root HTML file directly (e.g. gioi-thieu.html)
        with open(output / fname, 'w', encoding='utf-8') as f:
            f.write(full_html)

        # 2. Also create clean directory with index.html for pretty URLs if slug exists
        # e.g. gioi-thieu/index.html
        slug = cfg['slug']
        if slug and slug != '404':
            slug_dir = os.path.join(output, slug)
            os.makedirs(slug_dir, exist_ok=True)
            slug_index = os.path.join(slug_dir, 'index.html')
            with open(slug_index, 'w', encoding='utf-8') as f:
                f.write(full_html)

        count += 1
        print(f"✓ [{count:02d}/{len(PAGES_CONFIG):02d}] Đã build thành công: {fname} (và /{slug}/ nếu có)")

    urls = '\n'.join(
        f'    <url><loc>{escape(page_url(cfg))}</loc></url>'
        for cfg in PAGES_CONFIG if cfg['slug'] not in NOINDEX_SLUGS
    )
    (output / 'sitemap.xml').write_text(
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
        f'{urls}\n</urlset>\n', encoding='utf-8',
    )
    if staging:
        for name in ['.htaccess', 'robots.txt', 'Logo.jpg']:
            shutil.copyfile(Path(BASE_DIR) / name, output / name)
        assets = Path(BASE_DIR) / 'assets'
        for source in assets.rglob('*'):
            if source.is_symlink():
                raise ValueError(f'Symbolic links cannot be published: {source}')
            if source.is_file() and source.suffix.lower() in PUBLIC_ASSET_SUFFIXES:
                target = output / source.relative_to(BASE_DIR)
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copyfile(source, target)
        shutil.copytree(Path(BASE_DIR) / 'api', output / 'api')

    print("-" * 60)
    print(f"🎉 HOÀN THÀNH! Đã build {count} trang web độc lập, sẵn sàng deploy lên Git!")
    print("=" * 60)


def main():
    parser = argparse.ArgumentParser(description='Build the public KIPA website.')
    parser.add_argument('--output-dir', help='Build into an empty directory instead of updating source HTML.')
    args = parser.parse_args()
    build_site(args.output_dir)


if __name__ == '__main__':
    main()
