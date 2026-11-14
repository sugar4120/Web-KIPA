#!/usr/bin/env python3
import os
import sys
import mimetypes
from urllib.parse import unquote, urlparse
from http.server import HTTPServer, BaseHTTPRequestHandler

PORT = 3000
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

ROUTE_MAP = {
    '/': 'index.html',
    '/index': 'index.html',
    '/index.html': 'index.html',
    '/gioi-thieu': 'gioi-thieu.html',
    '/gioi-thieu/': 'gioi-thieu.html',
    '/gioi-thieu.html': 'gioi-thieu.html',
    '/doi-ngu': 'doi-ngu.html',
    '/doi-ngu/': 'doi-ngu.html',
    '/doi-ngu.html': 'doi-ngu.html',
    '/du-hoc': 'du-hoc.html',
    '/du-hoc/': 'du-hoc.html',
    '/du-hoc.html': 'du-hoc.html',
    '/du-hoc-han-quoc': 'du-hoc-han-quoc.html',
    '/du-hoc-han-quoc/': 'du-hoc-han-quoc.html',
    '/du-hoc-han-quoc.html': 'du-hoc-han-quoc.html',
    '/du-hoc-uc': 'du-hoc-uc.html',
    '/du-hoc-uc/': 'du-hoc-uc.html',
    '/du-hoc-uc.html': 'du-hoc-uc.html',
    '/du-hoc-nhat-ban': 'du-hoc-nhat-ban.html',
    '/du-hoc-nhat-ban/': 'du-hoc-nhat-ban.html',
    '/du-hoc-nhat-ban.html': 'du-hoc-nhat-ban.html',
    '/du-hoc-nhat.html': 'du-hoc-nhat-ban.html',
    '/du-hoc-chau-au': 'du-hoc-chau-au.html',
    '/du-hoc-chau-au/': 'du-hoc-chau-au.html',
    '/du-hoc-chau-au.html': 'du-hoc-chau-au.html',
    '/xuat-khau-lao-dong': 'xuat-khau-lao-dong.html',
    '/xuat-khau-lao-dong/': 'xuat-khau-lao-dong.html',
    '/xuat-khau-lao-dong.html': 'xuat-khau-lao-dong.html',
    '/xuat-khau-lao-dong-han-quoc': 'xuat-khau-lao-dong-han-quoc.html',
    '/xuat-khau-lao-dong-han-quoc/': 'xuat-khau-lao-dong-han-quoc.html',
    '/xuat-khau-lao-dong-han-quoc.html': 'xuat-khau-lao-dong-han-quoc.html',
    '/xuat-khau-lao-dong-uc': 'xuat-khau-lao-dong-uc.html',
    '/xuat-khau-lao-dong-uc/': 'xuat-khau-lao-dong-uc.html',
    '/xuat-khau-lao-dong-uc.html': 'xuat-khau-lao-dong-uc.html',
    '/xuat-khau-lao-dong-nhat-ban': 'xuat-khau-lao-dong-nhat-ban.html',
    '/xuat-khau-lao-dong-nhat-ban/': 'xuat-khau-lao-dong-nhat-ban.html',
    '/xuat-khau-lao-dong-nhat-ban.html': 'xuat-khau-lao-dong-nhat-ban.html',
    '/xuat-khau-lao-dong-chau-au': 'xuat-khau-lao-dong-chau-au.html',
    '/xuat-khau-lao-dong-chau-au/': 'xuat-khau-lao-dong-chau-au.html',
    '/xuat-khau-lao-dong-chau-au.html': 'xuat-khau-lao-dong-chau-au.html',
    '/hop-tac-quoc-te': 'hop-tac-quoc-te.html',
    '/hop-tac-quoc-te/': 'hop-tac-quoc-te.html',
    '/hop-tac-quoc-te.html': 'hop-tac-quoc-te.html',
    '/dang-ky-tu-van': 'dang-ky-tu-van.html',
    '/dang-ky-tu-van/': 'dang-ky-tu-van.html',
    '/dang-ky-tu-van.html': 'dang-ky-tu-van.html',
    '/faq': 'faq.html',
    '/faq/': 'faq.html',
    '/faq.html': 'faq.html',
    '/chinh-sach-bao-mat': 'chinh-sach-bao-mat.html',
    '/chinh-sach-bao-mat/': 'chinh-sach-bao-mat.html',
    '/chinh-sach-bao-mat.html': 'chinh-sach-bao-mat.html',
    '/tieng-han': 'tieng-han.html',
    '/tieng-han/': 'tieng-han.html',
    '/tieng-han.html': 'tieng-han.html',
    '/co-so': 'co-so.html',
    '/co-so/': 'co-so.html',
    '/co-so.html': 'co-so.html',
    '/lien-he': 'lien-he.html',
    '/lien-he/': 'lien-he.html',
    '/lien-he.html': 'lien-he.html',
    '/tin-tuc': 'tin-tuc.html',
    '/tin-tuc/': 'tin-tuc.html',
    '/tin-tuc.html': 'tin-tuc.html',
    '/cau-chuyen-hoc-vien': 'cau-chuyen-hoc-vien.html',
    '/cau-chuyen-hoc-vien/': 'cau-chuyen-hoc-vien.html',
    '/cau-chuyen-hoc-vien.html': 'cau-chuyen-hoc-vien.html',
    '/hoat-dong-su-kien': 'hoat-dong-su-kien.html',
    '/hoat-dong-su-kien/': 'hoat-dong-su-kien.html',
    '/hoat-dong-su-kien.html': 'hoat-dong-su-kien.html',
    '/dang-ky-tai-khoan': 'dang-ky-tai-khoan.html',
    '/dang-ky-tai-khoan/': 'dang-ky-tai-khoan.html',
    '/dang-ky-tai-khoan.html': 'dang-ky-tai-khoan.html',
    '/dang-nhap': 'dang-nhap.html',
    '/dang-nhap/': 'dang-nhap.html',
    '/dang-nhap.html': 'dang-nhap.html',
    '/cam-on': 'cam-on.html',
    '/cam-on/': 'cam-on.html',
    '/cam-on.html': 'cam-on.html',
    '/404': '404.html',
    '/404/': '404.html',
    '/404.html': '404.html',
}

PAGE_TITLES = {
    'index.html': 'KIPA Việt Hàn | Du Học & Xuất Khẩu Lao Động Uy Tín',
    'gioi-thieu.html': 'Giới Thiệu KIPA Việt Hàn | Hành Trình Mở Rộng Tương Lai',
    'doi-ngu.html': 'Đội Ngũ KIPA Việt Hàn | Ban Lãnh Đạo & Chuyên Gia Tuyển Sinh',
    'du-hoc.html': 'Du Học Quốc Tế KIPA Việt Hàn | Hàn Quốc, Úc, Nhật Bản, Châu Âu',
    'du-hoc-han-quoc.html': 'Du Học Hàn Quốc KIPA Việt Hàn | Lộ Trình Rõ Ràng, Vững Bước Tương Lai',
    'du-hoc-uc.html': 'Du Học Úc Cùng KIPA Việt Hàn | Định Hướng Lộ Trình & Học Bổng Toàn Diện',
    'du-hoc-nhat-ban.html': 'Du Học Nhật Bản Cùng KIPA Việt Hàn | Lộ Trình Rõ Ràng, Vững Bước Tương Lai',
    'du-hoc-chau-au.html': 'Du Học Châu Âu Cùng KIPA Việt Hàn | Lộ Trình Rõ Ràng, Vững Bước Tương Lai',
    'xuat-khau-lao-dong.html': 'Xuất Khẩu Lao Động & Việc Làm Quốc Tế | KIPA Việt Hàn',
    'xuat-khau-lao-dong-han-quoc.html': 'Xuất Khẩu Lao Động Hàn Quốc | KIPA Việt Hàn EPS E-9 & E-7 Chính Ngạch',
    'xuat-khau-lao-dong-uc.html': 'Xuất Khẩu Lao Động Úc | KIPA Việt Hàn Lao Động Nông Nghiệp & Tay Nghề',
    'xuat-khau-lao-dong-nhat-ban.html': 'Xuất Khẩu Lao Động Nhật Bản | KIPA Việt Hàn Tokutei & Kỹ Sư Thu Nhập Cao',
    'xuat-khau-lao-dong-chau-au.html': 'Xuất Khẩu Lao Động Châu Âu | KIPA Việt Hàn Đức, Ba Lan, Hungary, Rumani',
    'hop-tac-quoc-te.html': 'Hợp Tác Quốc Tế & Liên Kết Giáo Dục | KIPA Việt Hàn',
    'dang-ky-tu-van.html': 'Đăng Ký Tư Vấn Du Học & Việc Làm | KIPA Việt Hàn',
    'faq.html': 'Câu Hỏi Thường Gặp (FAQ) | KIPA Việt Hàn',
    'chinh-sach-bao-mat.html': 'Chính Sách Bảo Mật Thông Tin | KIPA Việt Hàn',
    'tieng-han.html': 'Đào Tạo Tiếng Hàn Cấp Tốc TOPIK 1 - 6 | KIPA Việt Hàn',
    'co-so.html': 'Hệ Thống Cơ Sở & Ký Túc Xá | KIPA Việt Hàn',
    'lien-he.html': 'Liên Hệ KIPA Việt Hàn | Điểm Khởi Đầu Cho Tương Lai',
    'tin-tuc.html': 'Tin Tức & Cẩm Nang Du Học, Việc Làm Quốc Tế | KIPA Việt Hàn',
    'cau-chuyen-hoc-vien.html': 'Câu Chuyện Học Viên KIPA Việt Hàn | Hành Trình Vươn Xa Quốc Tế',
    'hoat-dong-su-kien.html': 'Hoạt Động & Sự Kiện KIPA Việt Hàn | Kết nối cơ hội, phát triển tương lai',
    'dang-ky-tai-khoan.html': 'Đăng Ký Tài Khoản Học Viên | KIPA Việt Hàn',
    'dang-nhap.html': 'Đăng Nhập Tài Khoản Học Viên | KIPA Việt Hàn',
    'cam-on.html': 'Cảm Ơn Bạn Đã Đăng Ký | KIPA Việt Hàn',
    '404.html': '404 - Không Tìm Thấy Trang | KIPA Việt Hàn'
}

class KipaDevHandler(BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        sys.stderr.write("[%s] %s\n" % (self.log_date_time_string(), format % args))

    def end_headers(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Cache-Control', 'no-cache, no-store, must-revalidate')
        super().end_headers()

    def do_HEAD(self):
        self.handle_request(head_only=True)

    def do_GET(self):
        self.handle_request(head_only=False)

    def handle_request(self, head_only=False):
        parsed = urlparse(self.path)
        path = unquote(parsed.path)
        if path in {'/api/admin.php', '/api/cms.php'}:
            self.send_response(302)
            self.send_header('Location', 'https://kipaviethan.edu.vn' + path)
            self.send_header('Content-Length', '0')
            self.end_headers()
            return
        clean_path = path.rstrip('/') if path != '/' else '/'

        if '.php' in clean_path.lower().split('/')[-1]:
            self.send_error(403, "PHP cannot be executed by the static development server.")
            return

        # Check in route map
        target_file = ROUTE_MAP.get(clean_path) or ROUTE_MAP.get(path)

        if not target_file:
            # Check if file exists relative to BASE_DIR
            rel_path = path.lstrip('/')
            file_path = os.path.realpath(os.path.join(BASE_DIR, rel_path))
            if os.path.commonpath([os.path.realpath(BASE_DIR), file_path]) != os.path.realpath(BASE_DIR):
                self.send_error(403, "Path is outside the development website.")
                return
            if file_path.lower().endswith('.php'):
                self.send_error(403, "PHP source cannot be served.")
                return
            if os.path.isfile(file_path):
                self.serve_static_file(file_path, head_only=head_only)
                return
            if os.path.isdir(file_path):
                index_cand = os.path.join(file_path, 'index.html')
                if os.path.isfile(index_cand):
                    self.serve_static_file(index_cand, head_only=head_only)
                    return
            html_cand = file_path + '.html'
            if os.path.isfile(html_cand):
                self.serve_static_file(html_cand, head_only=head_only)
                return
            else:
                target_file = '404.html'

        # Render HTML page
        html_file_path = os.path.join(BASE_DIR, target_file)
        if not os.path.isfile(html_file_path):
            self.send_error(404, "File Not Found")
            return

        with open(html_file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # If it's a standalone page already with DOCTYPE, serve directly
        if '<!DOCTYPE' in content:
            data = content.encode('utf-8')
            self.send_response(200 if target_file != '404.html' else 404)
            self.send_header('Content-Type', 'text/html; charset=utf-8')
            self.send_header('Content-Length', str(len(data)))
            self.end_headers()
            if not head_only:
                self.wfile.write(data)
            return

        # Otherwise, dynamically inject header.html and footer.html
        header_path = os.path.join(BASE_DIR, 'header.html')
        footer_path = os.path.join(BASE_DIR, 'footer.html')
        
        header_html = ''
        if os.path.isfile(header_path):
            with open(header_path, 'r', encoding='utf-8') as hf:
                header_html = hf.read()
        
        footer_html = ''
        if os.path.isfile(footer_path):
            with open(footer_path, 'r', encoding='utf-8') as ff:
                footer_html = ff.read()

        # Replace placeholders
        if '<!-- [SHARED COMPONENT]: Header da duoc tach sang file header.html -->' in content:
            content = content.replace('<!-- [SHARED COMPONENT]: Header da duoc tach sang file header.html -->', header_html)
        else:
            content = header_html + '\n' + content

        if '<!-- [SHARED COMPONENT]: Footer da duoc tach sang file footer.html -->' in content:
            content = content.replace('<!-- [SHARED COMPONENT]: Footer da duoc tach sang file footer.html -->', footer_html)
        else:
            content = content + '\n' + footer_html

        title = PAGE_TITLES.get(target_file, 'KIPA Việt Hàn')
        full_html = f'''<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{title}</title>
    <link rel="icon" type="image/jpeg" href="/Logo.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&family=Dancing+Script:wght@600;700&family=Phudu:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        [class*="text-[11px]"] {{ font-size: 13px !important; line-height: 1.4 !important; }}
        [class*="text-[12.5px]"] {{ font-size: 12.5px !important; line-height: 1.45 !important; }}
        [class*="text-[13px]"] {{ font-size: 13px !important; line-height: 1.45 !important; }}
        [class*="text-[14px]"] {{ font-size: 14px !important; line-height: 1.5 !important; }}
        [class*="text-[15px]"] {{ font-size: 15px !important; line-height: 1.55 !important; }}
        [class*="text-[16px]"] {{ font-size: 16px !important; line-height: 1.6 !important; }}
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
{content}
</body>
</html>'''

        data = full_html.encode('utf-8')
        self.send_response(200 if target_file != '404.html' else 404)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.send_header('Content-Length', str(len(data)))
        self.end_headers()
        if not head_only:
            self.wfile.write(data)

    def serve_static_file(self, file_path, head_only=False):
        mime_type, _ = mimetypes.guess_type(file_path)
        if not mime_type:
            mime_type = 'application/octet-stream'

        try:
            with open(file_path, 'rb') as f:
                data = f.read()
            self.send_response(200)
            self.send_header('Content-Type', mime_type)
            self.send_header('Content-Length', str(len(data)))
            self.end_headers()
            if not head_only:
                self.wfile.write(data)
        except Exception as e:
            self.send_error(500, f"Error reading file: {str(e)}")

def run():
    server_address = ('', PORT)
    httpd = HTTPServer(server_address, KipaDevHandler)
    print("=" * 60)
    print(f"🚀 KIPA VIỆT HÀN LOCALHOST ĐÃ KHỞI CHẠY!")
    print(f"👉 Local URL:     http://localhost:{PORT}/")
    print(f"👉 Giới thiệu:    http://localhost:{PORT}/gioi-thieu/")
    print(f"👉 Du học:        http://localhost:{PORT}/du-hoc/")
    print(f"👉 Tiếng Hàn:     http://localhost:{PORT}/tieng-han/")
    print(f"👉 Cơ sở:         http://localhost:{PORT}/co-so/")
    print(f"👉 Liên hệ:       http://localhost:{PORT}/lien-he/")
    print("=" * 60)
    httpd.serve_forever()

if __name__ == '__main__':
    run()
