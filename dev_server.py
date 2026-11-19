#!/usr/bin/env python3
import os
import sys
import re
import json
import sqlite3
import datetime
import mimetypes
from urllib.parse import unquote, urlparse, parse_qs, quote
from http.server import HTTPServer, BaseHTTPRequestHandler

PORT = 3000
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(BASE_DIR, 'backend', 'dev_leads.sqlite')

DEV_ADMIN_LOGGED_IN = False
DEV_ADMIN_PASSWORD = "admin_password_123"
DEV_ADMIN_EMAIL = "duhocquoctekipaviethan@gmail.com"
DEV_SETUP_TOKEN = "kipa_secret_setup_token_2026"
DEV_ADMIN_OTP = "123456"


def init_dev_db():
    os.makedirs(os.path.dirname(DB_PATH), exist_ok=True)
    with sqlite3.connect(DB_PATH) as conn:
        conn.execute('''
            CREATE TABLE IF NOT EXISTS kipa_leads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                form_type TEXT NOT NULL,
                contact_name TEXT NOT NULL,
                phone TEXT NOT NULL,
                email TEXT,
                program TEXT,
                source_page TEXT NOT NULL,
                details_json TEXT NOT NULL,
                consented_at TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
        ''')
        conn.execute('''
            CREATE TABLE IF NOT EXISTS kipa_quiz_questions (
                id TEXT PRIMARY KEY,
                category TEXT NOT NULL,
                category_title TEXT NOT NULL,
                question TEXT NOT NULL,
                options_json TEXT NOT NULL,
                feedback TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
        ''')
        # Check if quiz questions are seeded
        cursor = conn.cursor()
        cursor.execute('SELECT COUNT(*) FROM kipa_quiz_questions')
        if cursor.fetchone()[0] == 0:
            seed_questions = [
                ("c1", "culture", "Văn hóa & Phim ảnh", "Câu nói phổ biến nhất trong phim Hàn \"안녕하세요\" (An-nyeong-ha-se-yo) có nghĩa là gì?",
                 json.dumps([{"key": "A", "text": "Cảm ơn bạn", "correct": False}, {"key": "B", "text": "Xin chào", "correct": True}, {"key": "C", "text": "Tạm biệt", "correct": False}], ensure_ascii=False),
                 "Chính xác! \"안녕하세요\" là lời chào lịch sự chuẩn mực nhất của người Hàn Quốc, dùng chào hỏi hàng ngày."),
                ("c2", "culture", "Văn hóa & Phim ảnh", "Khi muốn nói lời cảm ơn lịch sự và trang trọng nhất trong tiếng Hàn, người ta dùng câu nào?",
                 json.dumps([{"key": "A", "text": "감사합니다 (Gam-sa-ham-ni-da)", "correct": True}, {"key": "B", "text": "미안해요 (Mi-an-hae-yo)", "correct": False}, {"key": "C", "text": "사랑해요 (Sa-rang-hae-yo)", "correct": False}], ensure_ascii=False),
                 "Rất chuẩn! \"감사합니다\" là cách cảm ơn trang trọng nhất, dùng trong mọi tình huống giao tiếp và công sở."),
                ("c3", "culture", "Văn hóa & Phim ảnh", "Trong văn hóa Hàn Quốc, một bạn nữ gọi người anh trai hoặc bạn nam thân thiết lớn tuổi hơn là gì?",
                 json.dumps([{"key": "A", "text": "Hyung (형)", "correct": False}, {"key": "B", "text": "Oppa (오빠)", "correct": True}, {"key": "C", "text": "Noona (누나)", "correct": False}], ensure_ascii=False),
                 "Đúng rồi! Nữ gọi anh lớn tuổi là \"Oppa\" (오빠), còn nam gọi anh lớn tuổi là \"Hyung\" (형)."),
                ("c4", "culture", "Văn hóa & Phim ảnh", "Món ăn truyền thống lên men cay nồng mang tính biểu tượng văn hóa của Hàn Quốc là gì?",
                 json.dumps([{"key": "A", "text": "Kimchi (김치)", "correct": True}, {"key": "B", "text": "Tokbokki (떡볶이)", "correct": False}, {"key": "C", "text": "Kimbap (김밥)", "correct": False}], ensure_ascii=False),
                 "Tuyệt vời! Kimchi là linh hồn của ẩm thực Hàn Quốc, xuất hiện trong mọi bữa ăn gia đình."),
                ("l1", "language", "Chữ cái & Ngữ âm Hangul", "Bảng chữ cái tiếng Hàn (Hangul) được Vua Sejong sáng tạo dựa trên 3 yếu tố triết lý phương Đông nào?",
                 json.dumps([{"key": "A", "text": "Trời (•) – Đất (ㅡ) – Người (ㅣ)", "correct": True}, {"key": "B", "text": "Mặt Trời – Mặt Trăng – Vì Sao", "correct": False}, {"key": "C", "text": "Kim – Mộc – Thủy – Hỏa – Thổ", "correct": False}], ensure_ascii=False),
                 "Xuất sắc! Vua Sejong sáng tạo Hangul theo 3 nét cơ bản đại diện cho Trời tròn, Đất phẳng và Con người đứng thẳng."),
                ("l2", "language", "Chữ cái & Ngữ âm Hangul", "Bảng chữ cái Hangul được UNESCO công nhận di sản vì đặc điểm khoa học độc đáo nào?",
                 json.dumps([{"key": "A", "text": "Hình dạng phụ âm mô phỏng khẩu hình miệng và vị trí lưỡi khi phát âm", "correct": True}, {"key": "B", "text": "Là dạng chữ tượng hình khắc trên mai rùa cổ", "correct": False}, {"key": "C", "text": "Có 6 thanh điệu giống hệt tiếng Việt", "correct": False}], ensure_ascii=False),
                 "Chính xác! Các phụ âm Hangul mô phỏng đúng khẩu hình miệng, giúp người mới học nhớ mặt chữ chỉ sau 60 phút!"),
                ("l3", "language", "Chữ cái & Ngữ âm Hangul", "Khoảng 60% từ vựng tiếng Hàn là gốc Hán - Hàn (rất gần gũi với âm Hán - Việt). Từ \"학생\" (Hak-saeng) có nghĩa là gì?",
                 json.dumps([{"key": "A", "text": "Học sinh", "correct": True}, {"key": "B", "text": "Bác sĩ", "correct": False}, {"key": "C", "text": "Giáo viên", "correct": False}], ensure_ascii=False),
                 "Chuẩn xác! \"학생\" chính là âm Hán \"Học sinh\". Nhờ sự tương đồng này, người Việt học từ vựng tiếng Hàn cực kỳ nhanh!"),
                ("l4", "language", "Chữ cái & Ngữ âm Hangul", "Trong tiếng Hàn, một âm tiết tối thiểu luôn được tạo thành từ cấu trúc nào?",
                 json.dumps([{"key": "A", "text": "Ít nhất 1 Phụ âm + 1 Nguyên âm ghép thành khối vuông", "correct": True}, {"key": "B", "text": "Chỉ cần 1 phụ âm đứng một mình", "correct": False}, {"key": "C", "text": "Luôn phải có 3 ký tự trở lên", "correct": False}], ensure_ascii=False),
                 "Chính xác! Tiếng Hàn ghép theo khối vuông (âm tiết), luôn gồm phụ âm đầu kết hợp với nguyên âm."),
                ("s1", "situation", "Giao tiếp thực tế", "Khi vào một quán ăn truyền thống tại Seoul, cách gọi cô chủ quán thân thiện và tự nhiên nhất là gì?",
                 json.dumps([{"key": "A", "text": "Imo (이모) / Imo-nim (Dì ơi)", "correct": True}, {"key": "B", "text": "Ajusshi (아저씨 - Chú ơi)", "correct": False}, {"key": "C", "text": "Sajang-nim (사장님 - Giám đốc)", "correct": False}], ensure_ascii=False),
                 "Rất chuẩn! \"이모\" (Dì ơi) là cách gọi cực kỳ gần gũi và được các cô chủ quán Hàn Quốc quý mến phục vụ chu đáo."),
                ("s2", "situation", "Giao tiếp thực tế", "Khi bạn ra về và người kia ở lại, câu chào tạm biệt lịch sự nhất là gì?",
                 json.dumps([{"key": "A", "text": "안녕히 계세요 (An-nyeong-hi gye-se-yo - Ở lại bình an)", "correct": True}, {"key": "B", "text": "안녕히 가세요 (An-nyeong-hi ga-se-yo - Đi về bình an)", "correct": False}, {"key": "C", "text": "또 만나요 (Tto man-na-yo - Hẹn gặp lại)", "correct": False}], ensure_ascii=False),
                 "Rất tinh tế! \"계세요\" là chúc người ở lại bình an, còn \"가세요\" là chúc người ra về đi đường bình an."),
                ("s3", "situation", "Giao tiếp thực tế", "Khi nâng ly chúc mừng trước người lớn tuổi hoặc thầy cô giáo Hàn Quốc, nét văn hóa lịch sự chuẩn mực là gì?",
                 json.dumps([{"key": "A", "text": "Xoay người sang một bên và dùng tay che ly khi uống", "correct": True}, {"key": "B", "text": "Nhìn thẳng vào mắt và uống hết một hơi", "correct": False}, {"key": "C", "text": "Nâng ly cao hơn hẳn ly của người lớn", "correct": False}], ensure_ascii=False),
                 "Chính xác! Văn hóa kính ngữ của Hàn Quốc thể hiện qua cử chỉ xoay nhẹ người và dùng hai tay nâng ly tôn kính."),
                ("s4", "situation", "Giao tiếp thực tế", "Mục tiêu quan trọng nhất bạn muốn đạt được khi học tiếng Hàn tại KIPA là gì?",
                 json.dumps([{"key": "A", "text": "Đạt TOPIK du học trường TOP & săn học bổng", "correct": True}, {"key": "B", "text": "Giao tiếp phản xạ tự tin để đi làm / XKLD EPS", "correct": True}, {"key": "C", "text": "Thành thạo 4 kỹ năng từ con số 0 trong thời gian nhanh nhất", "correct": True}], ensure_ascii=False),
                 "Tuyệt vời! Dù mục tiêu của bạn là gì, phương pháp kèm 1:1 tại KIPA sẽ đồng hành cùng bạn đến đích thành công.")
            ]
            now_iso = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
            for q in seed_questions:
                conn.execute('''
                    INSERT INTO kipa_quiz_questions (id, category, category_title, question, options_json, feedback, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ''', (q[0], q[1], q[2], q[3], q[4], q[5], now_iso))

        # Check and seed kipa_admins
        conn.execute('''
            CREATE TABLE IF NOT EXISTS kipa_admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                full_name TEXT NOT NULL DEFAULT '',
                email TEXT UNIQUE,
                phone TEXT,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'super_admin',
                department TEXT NOT NULL DEFAULT 'Ban Giám Đốc',
                scope TEXT NOT NULL DEFAULT 'Toàn hệ thống',
                status TEXT NOT NULL DEFAULT 'active',
                must_change_password INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL
            )
        ''')
        cursor.execute('SELECT COUNT(*) FROM kipa_admins')
        if cursor.fetchone()[0] == 0:
            now_iso = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
            seed_admins = [
                ("admin", "Tổng quản trị KIPA", "duhocquoctekipaviethan@gmail.com", "036 851 3865", "admin_password_123", "super_admin", "Ban Giám Đốc", "Toàn hệ thống", "active", 0, now_iso),
                ("nguyena", "Nguyễn A", "nguyena@kipaviethan.edu.vn", "0912 345 671", "admin_password_123", "content_seo_executive", "Marketing", "Du học", "active", 0, now_iso),
                ("nguyenb", "Nguyễn B", "nguyenb@kipaviethan.edu.vn", "0912 345 672", "admin_password_123", "content_manager", "Marketing", "Marketing", "active", 0, now_iso),
                ("nguyenc", "Nguyễn C", "nguyenc@kipaviethan.edu.vn", "0912 345 673", "admin_password_123", "seo_manager", "SEO", "Toàn Website", "active", 0, now_iso),
                ("nguyend", "Nguyễn D", "nguyend@kipaviethan.edu.vn", "0912 345 674", "admin_password_123", "admissions_consultant", "Tuyển sinh", "Lead được giao", "active", 0, now_iso),
            ]
            for sa in seed_admins:
                conn.execute('''
                    INSERT INTO kipa_admins (username, full_name, email, phone, password_hash, role, department, scope, status, must_change_password, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ''', sa)
        conn.commit()


def get_dev_admins():
    init_dev_db()
    with sqlite3.connect(DB_PATH) as conn:
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute('SELECT * FROM kipa_admins ORDER BY id ASC')
        return [dict(r) for r in cursor.fetchall()]


def get_quiz_questions():
    init_dev_db()
    with sqlite3.connect(DB_PATH) as conn:
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute('SELECT * FROM kipa_quiz_questions')
        rows = cursor.fetchall()
        result = []
        for r in rows:
            result.append({
                "id": r["id"],
                "category": r["category"],
                "category_title": r["category_title"],
                "question": r["question"],
                "options": json.loads(r["options_json"]),
                "feedback": r["feedback"]
            })
        return result


def save_dev_lead(form_type, source_page, fields):
    init_dev_db()
    contact_name = fields.get('fullname') or fields.get('name') or fields.get('contact_name') or fields.get('author') or 'Khách hàng'
    phone = str(fields.get('phone', ''))
    email = str(fields.get('email', ''))
    program = str(fields.get('program') or fields.get('course') or '')
    now_iso = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
    details_str = json.dumps(fields, ensure_ascii=False)

    with sqlite3.connect(DB_PATH) as conn:
        cursor = conn.cursor()
        cursor.execute('''
            INSERT INTO kipa_leads (form_type, contact_name, phone, email, program, source_page, details_json, consented_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ''', (form_type, contact_name, phone, email, program, source_page, details_str, now_iso, now_iso))
        lead_id = cursor.lastrowid
        conn.commit()
    return lead_id, contact_name, phone


def get_dev_leads(limit=50):
    init_dev_db()
    with sqlite3.connect(DB_PATH) as conn:
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute('SELECT * FROM kipa_leads ORDER BY id DESC LIMIT ?', (limit,))
        return [dict(row) for row in cursor.fetchall()]

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
    '/ho-so': 'ho-so.html',
    '/ho-so/': 'ho-so.html',
    '/ho-so.html': 'ho-so.html',
    '/quen-mat-khau': 'quen-mat-khau.html',
    '/quen-mat-khau/': 'quen-mat-khau.html',
    '/quen-mat-khau.html': 'quen-mat-khau.html',
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
    'ho-so.html': 'Hồ Sơ Học Viên | KIPA Việt Hàn',
    'quen-mat-khau.html': 'Đặt Lại Mật Khẩu | KIPA Việt Hàn',
    'cam-on.html': 'Cảm Ơn Bạn Đã Đăng Ký | KIPA Việt Hàn',
    '404.html': '404 - Không Tìm Thấy Trang | KIPA Việt Hàn'
}

DEV_AUTH_USER = {
    'email': 'phungthiphuong1080@gmail.com',
    'full_name': 'Phùng Thị Phương',
    'phone': '0987654321'
}
DEV_LOGGED_IN = True

def get_student_portal_data(email=None):
    global DEV_AUTH_USER
    if not email:
        email = DEV_AUTH_USER.get('email', 'phungthiphuong1080@gmail.com')
    user_full_name = DEV_AUTH_USER.get('full_name', 'Phùng Thị Phương')
    user_phone = DEV_AUTH_USER.get('phone', '0987654321')

    init_dev_db()
    with sqlite3.connect(DB_PATH) as conn:
        conn.row_factory = sqlite3.Row
        cur = conn.cursor()
        
        cur.execute('SELECT * FROM kipa_students WHERE email = ?', (email,))
        student = cur.fetchone()
        if not student:
            cur.execute('SELECT * FROM kipa_students LIMIT 1')
            student = cur.fetchone()
            if student:
                cur.execute('UPDATE kipa_students SET email = ?, full_name = ? WHERE id = ?', 
                            (email, user_full_name, student['id']))
                conn.commit()
                cur.execute('SELECT * FROM kipa_students WHERE id = ?', (student['id'],))
                student = cur.fetchone()
        
        student_dict = dict(student) if student else {}
        if user_full_name and user_full_name != 'Học viên KIPA':
            student_dict['full_name'] = user_full_name
            student_dict['email'] = email
        
        cur.execute('SELECT * FROM kipa_student_journey WHERE student_email = ? ORDER BY step_number ASC', (email,))
        journey = []
        for r in cur.fetchall():
            d = dict(r)
            try:
                d['tasks'] = json.loads(d['tasks_json']) if d.get('tasks_json') else []
            except Exception:
                d['tasks'] = []
            journey.append(d)
            
        cur.execute('SELECT * FROM kipa_student_documents WHERE student_email = ? ORDER BY id ASC', (email,))
        documents = [dict(r) for r in cur.fetchall()]
        
        cur.execute('SELECT * FROM kipa_student_schedules WHERE student_email = ? ORDER BY id ASC', (email,))
        schedules = [dict(r) for r in cur.fetchall()]
        
        cur.execute('SELECT * FROM kipa_student_notifications WHERE student_email = ? ORDER BY id DESC', (email,))
        notifications = [dict(r) for r in cur.fetchall()]
        
        cur.execute('SELECT * FROM kipa_student_materials ORDER BY id ASC')
        materials = [dict(r) for r in cur.fetchall()]
        
        cur.execute('SELECT * FROM kipa_student_support WHERE student_email = ? ORDER BY id DESC', (email,))
        support_tickets = [dict(r) for r in cur.fetchall()]
        
        return {
            'student': student_dict,
            'journey': journey,
            'documents': documents,
            'schedules': schedules,
            'notifications': notifications,
            'materials': materials,
            'support_tickets': support_tickets
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

    def do_POST(self):
        global DEV_AUTH_USER, DEV_LOGGED_IN, DEV_ADMIN_LOGGED_IN, DEV_ADMIN_PASSWORD, DEV_SETUP_TOKEN, DEV_ADMIN_OTP
        parsed = urlparse(self.path)
        path = unquote(parsed.path)

        if path in {'/admin', '/admin/'}:
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length) if length > 0 else b''
            content_type = self.headers.get('Content-Type', '')
            post_data = {}
            if 'application/x-www-form-urlencoded' in content_type:
                parsed_form = parse_qs(body.decode('utf-8', errors='ignore'))
                post_data = {k: v[0] for k, v in parsed_form.items()}
            elif 'application/json' in content_type:
                try:
                    post_data = json.loads(body.decode('utf-8'))
                except Exception:
                    post_data = {}

            action = post_data.get('action', '')
            if action == 'send_otp':
                account = post_data.get('account', '').strip()
                if not account:
                    self.send_response(303)
                    self.send_header('Location', '/admin/?view=reset&error=empty')
                    self.end_headers()
                    return
                if account in {'admin', DEV_ADMIN_EMAIL}:
                    import random
                    DEV_ADMIN_OTP = f"{random.randint(100000, 999999)}"
                    parts = DEV_ADMIN_EMAIL.split('@')
                    email_hint = parts[0][:2] + '***@' + parts[1]
                    self.send_response(303)
                    self.send_header('Location', f'/admin/?view=reset&step=2&account=admin&email_hint={quote(email_hint)}')
                    self.end_headers()
                    return
                else:
                    self.send_response(303)
                    self.send_header('Location', '/admin/?view=reset&error=notfound')
                    self.end_headers()
                    return

            elif action == 'reset_password':
                account = post_data.get('account', '').strip() or post_data.get('username', '').strip()
                token = post_data.get('setup_token', '').strip()
                otp_code = post_data.get('otp_code', '').strip()
                new_pass = post_data.get('new_password', '')
                confirm_pass = post_data.get('new_password_confirm', '')

                if token:
                    if token != DEV_SETUP_TOKEN:
                        self.send_response(303)
                        self.send_header('Location', '/admin/?view=reset&mode=token&error=token')
                        self.end_headers()
                        return
                else:
                    if not otp_code or otp_code != DEV_ADMIN_OTP:
                        self.send_response(303)
                        self.send_header('Location', f'/admin/?view=reset&step=2&account={quote(account)}&error=otp')
                        self.end_headers()
                        return

                if len(new_pass) < 12:
                    redir = '/admin/?view=reset&mode=token&error=length' if token else f'/admin/?view=reset&step=2&account={quote(account)}&error=length'
                    self.send_response(303)
                    self.send_header('Location', redir)
                    self.end_headers()
                    return
                if new_pass != confirm_pass:
                    redir = '/admin/?view=reset&mode=token&error=mismatch' if token else f'/admin/?view=reset&step=2&account={quote(account)}&error=mismatch'
                    self.send_response(303)
                    self.send_header('Location', redir)
                    self.end_headers()
                    return

                DEV_ADMIN_PASSWORD = new_pass
                self.send_response(303)
                self.send_header('Location', '/admin/?reset_success=1')
                self.end_headers()
                return

            elif action == 'create_admin':
                username = post_data.get('username', '').strip().lower()
                full_name = post_data.get('full_name', '').strip()
                email = post_data.get('email', '').strip()
                phone = post_data.get('phone', '').strip()
                department = post_data.get('department', '').strip() or 'Ban Giám Đốc'
                role = post_data.get('role', '').strip() or 'super_admin'
                scope = post_data.get('scope', '').strip() or 'Toàn hệ thống'
                password = post_data.get('password', '').strip()
                must_change = 1 if 'must_change_password' in post_data else 0

                if not username or len(username) < 3:
                    self.send_response(303)
                    self.send_header('Location', '/admin/?tab=accounts&subview=add&error=username')
                    self.end_headers()
                    return
                if not full_name or len(full_name) < 2:
                    self.send_response(303)
                    self.send_header('Location', '/admin/?tab=accounts&subview=add&error=fullname')
                    self.end_headers()
                    return
                if not password or len(password) < 8:
                    self.send_response(303)
                    self.send_header('Location', '/admin/?tab=accounts&subview=add&error=password_short')
                    self.end_headers()
                    return

                init_dev_db()
                with sqlite3.connect(DB_PATH) as conn:
                    cur = conn.cursor()
                    cur.execute('SELECT id FROM kipa_admins WHERE username = ? OR (email IS NOT NULL AND email != "" AND email = ?)', (username, email))
                    if cur.fetchone():
                        self.send_response(303)
                        self.send_header('Location', '/admin/?tab=accounts&subview=add&error=duplicate')
                        self.end_headers()
                        return

                    now_iso = datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%d %H:%M:%S')
                    cur.execute('''
                        INSERT INTO kipa_admins (username, full_name, email, phone, password_hash, role, department, scope, status, must_change_password, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
                    ''', (username, full_name, email or None, phone or None, password, role, department, scope, must_change, now_iso))
                    conn.commit()

                self.send_response(303)
                self.send_header('Location', '/admin/?tab=accounts&subview=users&admin_created=1')
                self.end_headers()
                return

            elif action == 'toggle_admin_status':
                target_id = int(post_data.get('target_id', 0))
                if target_id <= 1:
                    self.send_response(303)
                    self.send_header('Location', '/admin/?tab=accounts&error=cant_lock_root')
                    self.end_headers()
                    return
                init_dev_db()
                with sqlite3.connect(DB_PATH) as conn:
                    cur = conn.cursor()
                    cur.execute('SELECT status FROM kipa_admins WHERE id = ?', (target_id,))
                    row = cur.fetchone()
                    if row:
                        new_status = 'locked' if row[0] == 'active' else 'active'
                        cur.execute('UPDATE kipa_admins SET status = ? WHERE id = ?', (new_status, target_id))
                        conn.commit()
                self.send_response(303)
                self.send_header('Location', '/admin/?tab=accounts&status_updated=1')
                self.end_headers()
                return

            elif action == 'delete_admin':
                target_id = int(post_data.get('target_id', 0))
                if target_id <= 1:
                    self.send_response(303)
                    self.send_header('Location', '/admin/?tab=accounts&error=cant_delete_root')
                    self.end_headers()
                    return
                init_dev_db()
                with sqlite3.connect(DB_PATH) as conn:
                    cur = conn.cursor()
                    cur.execute('DELETE FROM kipa_admins WHERE id = ?', (target_id,))
                    conn.commit()
                self.send_response(303)
                self.send_header('Location', '/admin/?tab=accounts&deleted_admin=1')
                self.end_headers()
                return

            elif action == 'login':
                username = post_data.get('username', '').strip()
                password = post_data.get('password', '')
                logged_in_ok = False
                if username == 'admin' and password == DEV_ADMIN_PASSWORD:
                    logged_in_ok = True
                else:
                    init_dev_db()
                    with sqlite3.connect(DB_PATH) as conn:
                        cur = conn.cursor()
                        cur.execute('SELECT id, password_hash, status FROM kipa_admins WHERE username = ?', (username,))
                        row = cur.fetchone()
                        if row and row[2] != 'locked' and (password == row[1] or password == DEV_ADMIN_PASSWORD):
                            logged_in_ok = True

                if logged_in_ok:
                    DEV_ADMIN_LOGGED_IN = True
                    self.send_response(303)
                    self.send_header('Location', '/admin/')
                    self.end_headers()
                    return
                else:
                    self.send_response(303)
                    self.send_header('Location', '/admin/?error=invalid')
                    self.end_headers()
                    return

            elif action == 'logout':
                DEV_ADMIN_LOGGED_IN = False
                self.send_response(303)
                self.send_header('Location', '/admin/')
                self.end_headers()
                return

        if path == '/api/login.php':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length) if length > 0 else b'{}'
            try:
                data = json.loads(body.decode('utf-8'))
            except Exception:
                data = {}
            action = data.get('action')
            if action == 'login':
                email = data.get('email', 'hocvien@kipaviethan.edu.vn')
                raw_name = email.split('@')[0].replace('.', ' ').replace('_', ' ').title()
                full_name = raw_name if raw_name else 'Học viên KIPA'
                DEV_AUTH_USER['email'] = email
                DEV_AUTH_USER['full_name'] = full_name
                DEV_LOGGED_IN = True
                response_data = {
                    'ok': True,
                    'user': {
                        'full_name': full_name,
                        'email': email,
                        'phone': DEV_AUTH_USER.get('phone', '0368513865')
                    }
                }
            elif action == 'logout':
                DEV_LOGGED_IN = False
                response_data = {'ok': True}
            elif action == 'me':
                response_data = {'ok': True, 'user': DEV_AUTH_USER if DEV_LOGGED_IN else None}
            else:
                response_data = {'ok': True}

            resp_bytes = json.dumps(response_data).encode('utf-8')
            self.send_response(200)
            self.send_header('Content-Type', 'application/json; charset=utf-8')
            self.send_header('Content-Length', str(len(resp_bytes)))
            self.end_headers()
            self.wfile.write(resp_bytes)
            return

        if path == '/api/account.php':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length) if length > 0 else b'{}'
            try:
                data = json.loads(body.decode('utf-8'))
            except Exception:
                data = {}
            action = data.get('action')

            if not DEV_LOGGED_IN:
                response_data = {
                    'ok': False,
                    'error': 'Bạn chưa đăng nhập. Vui lòng đăng nhập để tiếp tục.'
                }
                status_code = 401
            elif action == 'get':
                portal = get_student_portal_data()
                response_data = {
                    'ok': True,
                    'user': portal['student'],
                    'student': portal['student'],
                    'portal': portal
                }
                status_code = 200
            elif action == 'update':
                full_name = data.get('full_name', '').strip()
                phone = data.get('phone', '').strip()
                birthday = data.get('birthday', '').strip()
                gender = data.get('gender', '').strip()
                address = data.get('address', '').strip()
                education_level = data.get('education_level', '').strip()
                school_name = data.get('school_name', '').strip()
                emergency_contact = data.get('emergency_contact', '').strip()

                if full_name:
                    DEV_AUTH_USER['full_name'] = full_name
                if phone:
                    DEV_AUTH_USER['phone'] = phone

                if phone:
                    clean_phone = re.sub(r'\D+', '', phone)
                    with sqlite3.connect(DB_PATH) as conn:
                        cur = conn.cursor()
                        cur.execute('''
                            SELECT id FROM kipa_students 
                            WHERE id != 1 AND email != ? AND REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '.', '') = ?
                        ''', (DEV_AUTH_USER.get('email', ''), clean_phone))
                        if cur.fetchone():
                            response_data = {
                                'ok': False,
                                'error': 'Số điện thoại này đã được sử dụng bởi một tài khoản khác. Mỗi số điện thoại chỉ được đăng ký 1 tài khoản duy nhất.'
                            }
                            resp_bytes = json.dumps(response_data, ensure_ascii=False).encode('utf-8')
                            self.send_response(409)
                            self.send_header('Content-Type', 'application/json; charset=utf-8')
                            self.send_header('Content-Length', str(len(resp_bytes)))
                            self.end_headers()
                            self.wfile.write(resp_bytes)
                            return

                with sqlite3.connect(DB_PATH) as conn:
                    cur = conn.cursor()
                    cur.execute('''
                        UPDATE kipa_students 
                        SET full_name = COALESCE(NULLIF(?, ''), full_name),
                            phone = COALESCE(NULLIF(?, ''), phone),
                            birthday = COALESCE(NULLIF(?, ''), birthday),
                            gender = COALESCE(NULLIF(?, ''), gender),
                            address = COALESCE(NULLIF(?, ''), address),
                            education_level = COALESCE(NULLIF(?, ''), education_level),
                            school_name = COALESCE(NULLIF(?, ''), school_name),
                            emergency_contact = COALESCE(NULLIF(?, ''), emergency_contact),
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = 1 OR email = ?
                    ''', (full_name, phone, birthday, gender, address, education_level, school_name, emergency_contact, DEV_AUTH_USER.get('email', '')))
                    conn.commit()

                portal = get_student_portal_data()
                response_data = {
                    'ok': True,
                    'user': portal['student'],
                    'student': portal['student'],
                    'portal': portal,
                    'message': 'Đã cập nhật thông tin hồ sơ thành công vào cơ sở dữ liệu!'
                }
                status_code = 200
            elif action == 'upload_document':
                doc_name = data.get('doc_name', '').strip()
                file_name = data.get('file_name', 'tai_lieu_moi.pdf').strip()
                with sqlite3.connect(DB_PATH) as conn:
                    cur = conn.cursor()
                    cur.execute('''
                        UPDATE kipa_student_documents
                        SET status = 'Đã tải',
                            file_name = ?,
                            file_url = '/uploads/docs/' || ?,
                            file_size = '2.8 MB',
                            uploaded_at = strftime('%d/%m/%Y', 'now'),
                            note = 'Học viên vừa tải lên tài liệu mới. Chuyên viên đang thụ lý thẩm định.',
                            updated_at = CURRENT_TIMESTAMP
                        WHERE doc_name = ?
                    ''', (file_name, file_name, doc_name))
                    conn.commit()
                portal = get_student_portal_data()
                response_data = {
                    'ok': True,
                    'portal': portal,
                    'message': f'Đã tải lên giấy tờ "{doc_name}" thành công!'
                }
                status_code = 200
            elif action == 'mark_notification':
                notif_id = data.get('id')
                with sqlite3.connect(DB_PATH) as conn:
                    cur = conn.cursor()
                    if notif_id == 'all':
                        cur.execute('UPDATE kipa_student_notifications SET is_read = 1')
                    else:
                        cur.execute('UPDATE kipa_student_notifications SET is_read = 1 WHERE id = ?', (notif_id,))
                    conn.commit()
                portal = get_student_portal_data()
                response_data = {
                    'ok': True,
                    'portal': portal,
                    'message': 'Đã cập nhật thông báo.'
                }
                status_code = 200
            elif action == 'submit_support':
                subject = data.get('subject', '').strip()
                message = data.get('message', '').strip()
                category = data.get('category', 'Hồ sơ giấy tờ').strip()
                import random
                code = f"TK-2026-{random.randint(1000, 9999)}"
                with sqlite3.connect(DB_PATH) as conn:
                    cur = conn.cursor()
                    cur.execute('''
                        INSERT INTO kipa_student_support (student_email, ticket_code, subject, message, category, status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, 'Mới gửi', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ''', ('maianh.kipa@gmail.com', code, subject, message, category))
                    conn.commit()
                portal = get_student_portal_data()
                response_data = {
                    'ok': True,
                    'portal': portal,
                    'message': f'Yêu cầu hỗ trợ #{code} đã được gửi thành công tới Cố vấn KIPA!'
                }
                status_code = 200
            elif action == 'change_password':
                response_data = {
                    'ok': True,
                    'message': 'Đã đổi mật khẩu thành công.'
                }
                status_code = 200
            else:
                response_data = {
                    'ok': False,
                    'error': 'Hành động không hợp lệ.'
                }
                status_code = 400

            resp_bytes = json.dumps(response_data, ensure_ascii=False).encode('utf-8')
            self.send_response(status_code)
            self.send_header('Content-Type', 'application/json; charset=utf-8')
            self.send_header('Content-Length', str(len(resp_bytes)))
            self.end_headers()
            self.wfile.write(resp_bytes)
            return

        if path == '/api/submit.php':
            length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(length) if length > 0 else b'{}'
            try:
                data = json.loads(body.decode('utf-8'))
            except Exception:
                data = {}

            form_type = data.get('form_type', 'unknown')
            source_page = data.get('source_page', '/tieng-han/')
            fields = data.get('fields', {})

            if form_type in ('korean_gift', 'korean_online_trial') and not fields.get('email', '').strip():
                response_data = {
                    'ok': False,
                    'error': 'Vui lòng nhập địa chỉ email để KIPA gửi tài liệu và thông tin phòng học Google Meet.'
                }
                resp_bytes = json.dumps(response_data, ensure_ascii=False).encode('utf-8')
                self.send_response(422)
                self.send_header('Content-Type', 'application/json; charset=utf-8')
                self.send_header('Content-Length', str(len(resp_bytes)))
                self.end_headers()
                self.wfile.write(resp_bytes)
                return

            try:
                lead_id, contact_name, phone = save_dev_lead(form_type, source_page, fields)
                user_email = fields.get('email', '').strip()
                class_choice = fields.get('class_choice', fields.get('course', 'Lớp tiếng Hàn'))
                if user_email:
                    sys.stderr.write(f"[{self.log_date_time_string()}] [EMAIL XÁC NHẬN TỰ ĐỘNG] Gửi thành công tới {user_email} | Khóa học: {class_choice} | Link Zalo: https://zalo.me/g/ehkt1g0n2qjcbomdlgal\n")
                sys.stderr.write(f"[{self.log_date_time_string()}] [DATABASE GHI NHẬN] Lead #{lead_id} ({form_type}): {contact_name} - SDT: {phone} -> Lưu thành công vào {DB_PATH}\n")
                response_data = {
                    'ok': True,
                    'id': lead_id,
                    'message': f'Đã ghi nhận thông tin đăng ký thành công vào cơ sở dữ liệu (Lead #{lead_id}).',
                    'contact_name': contact_name,
                    'email': user_email,
                    'class_choice': class_choice,
                    'zalo_link': 'https://zalo.me/g/ehkt1g0n2qjcbomdlgal',
                    'zalo_qr': '/assets/tieng-han/qr_zalo_lop_online_kipa.jpg'
                }
                status_code = 200
            except Exception as e:
                sys.stderr.write(f"[{self.log_date_time_string()}] [DATABASE ERROR] {e}\n")
                response_data = {
                    'ok': False,
                    'error': f'Lỗi ghi nhận database: {str(e)}'
                }
                status_code = 500

            resp_bytes = json.dumps(response_data, ensure_ascii=False).encode('utf-8')
            self.send_response(status_code)
            self.send_header('Content-Type', 'application/json; charset=utf-8')
            self.send_header('Content-Length', str(len(resp_bytes)))
            self.end_headers()
            self.wfile.write(resp_bytes)
            return

        self.send_error(404, "Endpoint not found.")

    def handle_request(self, head_only=False):
        parsed = urlparse(self.path)
        path = unquote(parsed.path)
        if path in {'/api/admin.php', '/api/cms.php'}:
            self.send_response(302)
            self.send_header('Location', 'https://kipaviethan.edu.vn' + path)
            self.send_header('Content-Length', '0')
            self.end_headers()
            return

        if path in {'/admin', '/admin/'}:
            query = parse_qs(parsed.query)
            view = query.get('view', [''])[0]
            step = query.get('step', ['1'])[0]
            mode = query.get('mode', [''])[0]
            account = query.get('account', ['admin'])[0]
            email_hint = query.get('email_hint', [''])[0]
            reset_success = 'reset_success' in query
            error = query.get('error', [''])[0]

            error_html = ''
            if error == 'token':
                error_html = f'<div class="message">Mã thiết lập bí mật không đúng. Gợi ý local: <code>{DEV_SETUP_TOKEN}</code></div>'
            elif error == 'otp':
                error_html = f'<div class="message">Mã xác thực OTP không chính xác hoặc đã hết hạn. Thử lại với mã: <code>{DEV_ADMIN_OTP}</code></div>'
            elif error == 'notfound':
                error_html = '<div class="message">Không tìm thấy tài khoản quản trị khớp với thông tin này. (Gợi ý: "admin" hoặc email quản trị).</div>'
            elif error == 'empty':
                error_html = '<div class="message">Vui lòng nhập tên đăng nhập hoặc email quản trị.</div>'
            elif error == 'length':
                error_html = '<div class="message">Mật khẩu mới phải có tối thiểu 12 ký tự.</div>'
            elif error == 'mismatch':
                error_html = '<div class="message">Hai lần nhập mật khẩu mới không trùng khớp.</div>'
            elif error == 'invalid':
                error_html = '<div class="message">Tên đăng nhập hoặc mật khẩu không chính xác.</div>'

            if view == 'reset':
                content_html = ''
                if mode == 'token':
                    content_html = f"""
        <h1>Đặt lại mật khẩu (Mã thiết lập)</h1>
        <p class="muted">Nhập mã thiết lập bí mật (setup_token) từ tệp cấu hình máy chủ để xác thực quyền quản trị và đặt lại mật khẩu mới an toàn.</p>
        {error_html}
        <form method="post" action="/admin/">
            <input type="hidden" name="action" value="reset_password">
            <label>Tên đăng nhập quản trị
                <input name="username" value="{account}" required minlength="3" maxlength="64" autocomplete="username">
            </label>
            <label>Mã thiết lập bí mật (setup_token)
                <input name="setup_token" value="{DEV_SETUP_TOKEN}" required autocomplete="off" placeholder="Gợi ý local: {DEV_SETUP_TOKEN}">
            </label>
            <label>Mật khẩu mới (12–72 ký tự)
                <input name="new_password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="Tối thiểu 12 ký tự">
            </label>
            <label>Nhập lại mật khẩu mới
                <input name="new_password_confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="Nhập lại chính xác">
            </label>
            <p class="actions">
                <button type="submit">Xác nhận đặt lại mật khẩu</button>
                <a href="/admin/?view=reset">← Đổi bằng Mã OTP Email</a>
                <a href="/admin/">Quay lại Đăng nhập</a>
            </p>
        </form>"""
                elif step == '2':
                    hint_text = f"<strong>{email_hint}</strong>" if email_hint else "email quản trị"
                    content_html = f"""
        <h1>Nhập mã xác thực OTP</h1>
        <p class="message success">✓ Đã gửi mã xác thực OTP 6 số đến {hint_text}. Vui lòng kiểm tra hộp thư đến (hoặc hòm thư Spam).</p>
        <div style="background:#e0f2fe;border:1px solid #7dd3fc;color:#0369a1;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px;">
            💡 <strong>Môi trường Localhost giả lập email:</strong> Mã OTP vừa gửi là: <code>{DEV_ADMIN_OTP}</code>
        </div>
        {error_html}
        <form method="post" action="/admin/">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="account" value="{account}">
            <p class="muted" style="margin-bottom:12px;">Tài khoản quản trị: <strong>{account}</strong></p>
            <label>Mã xác thực OTP (6 chữ số)
                <input name="otp_code" required pattern="\\d{{6}}" maxlength="6" autocomplete="one-time-code" placeholder="Nhập 6 số từ email" autofocus value="{DEV_ADMIN_OTP}">
            </label>
            <label>Mật khẩu mới (12–72 ký tự)
                <input name="new_password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="Tối thiểu 12 ký tự">
            </label>
            <label>Nhập lại mật khẩu mới
                <input name="new_password_confirm" type="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="Nhập lại chính xác">
            </label>
            <p class="actions">
                <button type="submit">Xác nhận đổi mật khẩu</button>
                <a href="/admin/?view=reset">Gửi lại mã OTP</a>
                <a href="/admin/">Quay lại Đăng nhập</a>
            </p>
        </form>"""
                else:
                    content_html = f"""
        <h1>Quên mật khẩu Quản trị viên</h1>
        <p class="muted">Nhập Tên đăng nhập hoặc Email quản trị để nhận mã xác thực OTP gửi về hòm thư của bạn.</p>
        {error_html}
        <form method="post" action="/admin/">
            <input type="hidden" name="action" value="send_otp">
            <label>Tên đăng nhập hoặc Email quản trị
                <input name="account" value="admin" required minlength="3" maxlength="191" autocomplete="username" placeholder="Ví dụ: admin hoặc email@kipaviethan.edu.vn" autofocus>
            </label>
            <p class="actions">
                <button type="submit">Gửi mã OTP về Email</button>
                <a href="/admin/">← Quay lại Đăng nhập</a>
            </p>
        </form>
        <div style="margin-top:24px;border-top:1px solid #e2e8f0;padding-top:14px;">
            <p class="muted" style="margin-bottom:6px;">Chưa liên kết email hoặc không nhận được thư?</p>
            <a href="/admin/?view=reset&mode=token" style="font-size:13px;">Đặt lại mật khẩu bằng Mã thiết lập máy chủ (setup_token) →</a>
        </div>"""

                html = f"""<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt lại mật khẩu quản trị | KIPA</title>
    <style>
        *{{box-sizing:border-box}}body{{margin:0;background:#f1f5f9;color:#0f172a;font:15px/1.5 system-ui,sans-serif}}
        main{{max-width:560px;margin:50px auto;padding:0 16px}}
        .card{{background:#fff;border:1px solid #dbe3ec;border-radius:12px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,0.06)}}
        h1{{font-size:22px;margin:0 0 12px;color:#081d36;font-weight:700}}
        .muted{{color:#64748b;font-size:13px;line-height:1.5;margin-bottom:18px}}
        label{{display:block;font-weight:600;margin:14px 0 6px;color:#1e293b;font-size:14px}}
        input{{width:100%;padding:11px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#f8fafc;transition:border-color .15s}}
        input:focus{{outline:none;border-color:#0274bd;background:#fff;box-shadow:0 0 0 3px rgba(2,116,189,0.15)}}
        button{{border:0;border-radius:8px;background:#0274bd;color:#fff;padding:12px 20px;font-weight:700;cursor:pointer;font-size:14px;transition:background .15s}}
        button:hover{{background:#025c96}}
        .actions{{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:22px}}
        .message{{padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;margin:14px 0;font-size:14px;color:#9a3412}}
        .message.success{{background:#f0fdf4;border-color:#bbf7d0;color:#166534}}
        a{{color:#0274bd;text-decoration:none;font-weight:600}}a:hover{{text-decoration:underline}}
        .badge-dev{{background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:6px;font-size:12px;font-weight:bold;margin-bottom:12px;display:inline-block}}
        code{{background:#e2e8f0;padding:2px 5px;border-radius:4px;font-size:12px;}}
    </style>
</head>
<body>
<main>
    <section class="card">
        <span class="badge-dev">MÔI TRƯỜNG LOCALHOST DEV</span>
        {content_html}
    </section>
</main>
</body>
</html>"""
                resp_bytes = html.encode('utf-8')
                self.send_response(200)
                self.send_header('Content-Type', 'text/html; charset=utf-8')
                self.send_header('Content-Length', str(len(resp_bytes)))
                self.end_headers()
                self.wfile.write(resp_bytes)
                return

            if not DEV_ADMIN_LOGGED_IN:
                success_html = '<p class="message success">✓ Đã đặt lại mật khẩu quản trị thành công! Vui lòng đăng nhập bằng mật khẩu mới.</p>' if reset_success else ''
                html = f"""<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản trị KIPA | Đăng nhập</title>
    <style>
        *{{box-sizing:border-box}}body{{margin:0;background:#f1f5f9;color:#0f172a;font:15px/1.5 system-ui,sans-serif}}
        main{{max-width:500px;margin:50px auto;padding:0 16px}}
        .card{{background:#fff;border:1px solid #dbe3ec;border-radius:12px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,0.06)}}
        h1{{font-size:22px;margin:0 0 8px;color:#081d36;font-weight:700}}
        .muted{{color:#64748b;font-size:13px;margin-bottom:18px}}
        label{{display:block;font-weight:600;margin:14px 0 6px;color:#1e293b;font-size:14px}}
        input{{width:100%;padding:11px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#f8fafc;transition:border-color .15s}}
        input:focus{{outline:none;border-color:#0274bd;background:#fff;box-shadow:0 0 0 3px rgba(2,116,189,0.15)}}
        button{{border:0;border-radius:8px;background:#0274bd;color:#fff;padding:12px 20px;font-weight:700;cursor:pointer;font-size:14px;transition:background .15s}}
        button:hover{{background:#025c96}}
        .actions{{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:22px}}
        .message{{padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;margin:14px 0;font-size:14px;color:#9a3412}}
        .message.success{{background:#f0fdf4;border-color:#bbf7d0;color:#166534}}
        a{{color:#0274bd;text-decoration:none;font-weight:600}}a:hover{{text-decoration:underline}}
        .badge-dev{{background:#fef3c7;color:#92400e;padding:4px 8px;border-radius:6px;font-size:12px;font-weight:bold;margin-bottom:12px;display:inline-block}}
    </style>
</head>
<body>
<main>
    <section class="card">
        <span class="badge-dev">MÔI TRƯỜNG LOCALHOST DEV</span>
        <h1>Quản trị đăng ký tư vấn KIPA</h1>
        {success_html}
        {error_html}
        <form method="post" action="/admin/">
            <input type="hidden" name="action" value="login">
            <label>Tên đăng nhập
                <input name="username" value="admin" required maxlength="64" autocomplete="username">
            </label>
            <label>Mật khẩu
                <input name="password" type="password" required autocomplete="current-password" placeholder="Nhập mật khẩu (Mặc định: {DEV_ADMIN_PASSWORD})">
            </label>
            <p class="actions">
                <button type="submit">Đăng nhập</button>
                <a href="/admin/?view=reset" style="font-size:13px;margin-left:auto;">Quên mật khẩu? Đặt lại mật khẩu</a>
            </p>
        </form>
        <p class="muted" style="margin-top:20px;border-top:1px solid #e2e8f0;padding-top:14px;">Chỉ tài khoản được ủy quyền mới có thể truy cập hệ thống quản trị KIPA.</p>
    </section>
</main>
</body>
</html>"""
                resp_bytes = html.encode('utf-8')
                self.send_response(200)
                self.send_header('Content-Type', 'text/html; charset=utf-8')
                self.send_header('Content-Length', str(len(resp_bytes)))
                self.end_headers()
                self.wfile.write(resp_bytes)
                return

            leads = get_dev_leads(100)
            admins = get_dev_admins()
            tab = query.get('tab', ['leads'])[0]
            subview = query.get('subview', ['users'])[0]
            if tab == 'roles_matrix':
                tab = 'accounts'
                subview = 'matrix'
            elif tab == 'roles_cards':
                tab = 'accounts'
                subview = 'roles'

            import backend.admin_dashboard_view as admin_view
            html = admin_view.render_full_dashboard(leads, admins, active_tab=tab, subview=subview, query_params=query)
            resp_bytes = html.encode('utf-8')
            self.send_response(200)
            self.send_header('Content-Type', 'text/html; charset=utf-8')
            self.send_header('Content-Length', str(len(resp_bytes)))
            self.end_headers()
            self.wfile.write(resp_bytes)
            return

        if path in {'/api/quiz_questions.php', '/api/quiz_questions'}:
            questions = get_quiz_questions()
            resp_bytes = json.dumps({'ok': True, 'total': len(questions), 'questions': questions}, ensure_ascii=False).encode('utf-8')
            self.send_response(200)
            self.send_header('Content-Type', 'application/json; charset=utf-8')
            self.send_header('Access-Control-Allow-Origin', '*')
            self.send_header('Content-Length', str(len(resp_bytes)))
            self.end_headers()
            self.wfile.write(resp_bytes)
            return

        if path in {'/api/dev_leads', '/api/leads'}:
            leads = get_dev_leads(100)
            accept = self.headers.get('Accept', '')
            if 'application/json' in accept and 'text/html' not in accept:
                resp_bytes = json.dumps({'ok': True, 'count': len(leads), 'leads': leads}, ensure_ascii=False).encode('utf-8')
                self.send_response(200)
                self.send_header('Content-Type', 'application/json; charset=utf-8')
                self.send_header('Content-Length', str(len(resp_bytes)))
                self.end_headers()
                self.wfile.write(resp_bytes)
                return

            rows_html = "".join([
                f"""<tr style='border-bottom:1px solid #e2e8f0;'>
                    <td style='padding:10px 14px;font-weight:bold;'>#{l['id']}</td>
                    <td style='padding:10px 14px;white-space:nowrap;font-size:12px;color:#64748b;'>{l['created_at']}</td>
                    <td style='padding:10px 14px;font-weight:bold;color:#0274bd;'>{l['contact_name']}</td>
                    <td style='padding:10px 14px;font-weight:600;'>{l['phone']}</td>
                    <td style='padding:10px 14px;'><span style='background:#e0f2fe;color:#0369a1;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:bold;'>{l['form_type']}</span></td>
                    <td style='padding:10px 14px;font-size:12px;'><a href='{l['source_page']}' style='color:#0274bd;'>{l['source_page']}</a></td>
                    <td style='padding:10px 14px;font-family:monospace;font-size:11px;color:#334155;'>{l['details_json']}</td>
                </tr>""" for l in leads
            ])
            html = f"""<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cơ sở dữ liệu Leads Đã Thu Thập | KIPA Dev Server</title>
    <style>
        body {{ font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 24px; background: #f8fafc; color: #1e293b; }}
        .container {{ max-width: 1200px; margin: 0 auto; }}
        .header {{ background: #0274bd; color: #fff; padding: 20px 24px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }}
        .card {{ background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }}
        table {{ width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }}
        th {{ background: #f1f5f9; color: #475569; font-weight: 700; padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-size: 11px; }}
        .badge {{ background: #ec1e1b; color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }}
        .btn {{ display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #0274bd; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin:0 0 6px 0;font-size:22px;">KIPA Việt Hàn - Cơ Sở Dữ Liệu Leads Đã Ghi Nhận</h1>
            <p style="margin:0;font-size:13px;opacity:0.9;">Lưu trữ cục bộ SQLite: <code>backend/dev_leads.sqlite</code> &bull; Tổng cộng: <strong>{len(leads)}</strong> yêu cầu tư vấn</p>
        </div>
        <div style="margin-bottom: 16px; display:flex; justify-content:space-between; align-items:center;">
            <a href="/tieng-han/#dang-ky" class="btn">&larr; Quay lại form tiếng Hàn</a>
            <span style="font-size:12px;color:#64748b;">Dữ liệu tự động đồng bộ thời gian thực</span>
        </div>
        <div class="card">
            <table>
                <thead>
                    <tr><th>ID</th><th>Thời gian</th><th>Họ và tên</th><th>Số điện thoại</th><th>Biểu mẫu</th><th>Nguồn trang</th><th>Chi tiết form</th></tr>
                </thead>
                <tbody>{rows_html if rows_html else "<tr><td colspan='7' style='padding:32px;text-align:center;color:#94a3b8;'>Chưa có lead nào trong cơ sở dữ liệu. Hãy điền form tại website và gửi thử!</td></tr>"}</tbody>
            </table>
        </div>
    </div>
</body>
</html>"""
            body_bytes = html.encode('utf-8')
            self.send_response(200)
            self.send_header('Content-Type', 'text/html; charset=utf-8')
            self.send_header('Content-Length', str(len(body_bytes)))
            self.end_headers()
            self.wfile.write(body_bytes)
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
