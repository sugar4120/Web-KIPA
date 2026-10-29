# DỰ ÁN WORDPRESS - KIPA VIỆT HÀN

Dự án website WordPress cho **KIPA Việt Hàn** (Du học & Xuất khẩu lao động uy tín) được thiết lập hoàn chỉnh trên nền tảng Docker Compose với kiến trúc chuyên nghiệp:

- **WordPress 6.x** chạy trên nền PHP 8.2 Apache
- **MySQL 8.0** chuẩn hoá UTF-8 Multibyte
- **phpMyAdmin** để quản lý và trực quan hóa cơ sở dữ liệu
- Quản lý mã nguồn tùy biến qua thư mục `wp-content/themes/kipa-theme`

---

## 🚀 1. Khởi động dự án

Bạn có thể dùng script tiện ích hoặc lệnh Docker trực tiếp:

### Cách 1: Dùng script tiện ích (Khuyên dùng)
```bash
./manage.sh start
```

### Cách 2: Dùng lệnh Docker Compose
```bash
docker-compose up -d
```

---

## 🌐 2. Địa chỉ truy cập

| Dịch vụ | URL | Thông tin đăng nhập |
| :--- | :--- | :--- |
| **Website WordPress** | [http://localhost:8080](http://localhost:8080) | Cài đặt tài khoản admin trong lần đầu mở trang |
| **phpMyAdmin** | [http://localhost:8081](http://localhost:8081) | User: `kipa_user` hoặc `root`<br>Pass: trong file `.env` |
| **Cơ sở dữ liệu (MySQL)** | `localhost:3306` | Tên DB: `kipa_db` |

---

## ⚙️ 3. Thông tin Database (`.env`)

Các thông số mặc định đã được cấu hình sẵn trong file `.env`:

- **Host DB**: `db:3306` (kết nối nội bộ giữa container WordPress và MySQL)
- **Database Name**: `kipa_db`
- **User**: `kipa_user`
- **Password**: `kipa_secret_password`
- **Root Password**: `kipa_root_password`

---

## 🎨 4. Cấu trúc thư mục mã nguồn

```
Kipa/
├── .env                    # Biến môi trường & mật khẩu
├── .env.example            # Bản mẫu cấu hình môi trường
├── docker-compose.yml      # Cấu hình container Docker
├── manage.sh               # Script thao tác nhanh (start/stop/logs)
├── config/
│   └── uploads.ini         # Cấu hình PHP (tăng limit upload 128MB, memory 512MB)
└── wp-content/
    ├── themes/
    │   └── kipa-theme/     # 🎯 Theme giao diện tùy biến của KIPA
    │       ├── assets/
    │       │   ├── css/main.css
    │       │   └── js/main.js
    │       ├── functions.php
    │       ├── header.php
    │       ├── footer.php
    │       ├── index.php
    │       └── style.css
    ├── plugins/            # Nơi cài đặt hoặc code thêm Plugin
    └── uploads/            # Thư mục lưu media khi upload trên web
```

---

## 🛠️ 5. Các lệnh quản lý tiện ích

```bash
# Xem log hệ thống
./manage.sh logs

# Kiểm tra trạng thái các container
./manage.sh status

# Khởi động lại
./manage.sh restart

# Tắt dự án
./manage.sh stop
```

---

## 📌 6. Kích hoạt Giao diện KIPA

1. Mở trình duyệt vào [http://localhost:8080](http://localhost:8080).
2. Hoàn thành các bước cài đặt ban đầu của WordPress (Chọn Tiếng Việt, đặt tên trang, tạo tài khoản Admin).
3. Đăng nhập vào trang quản trị: `http://localhost:8080/wp-admin`.
4. Vào menu **Giao diện (Appearance)** -> **Giao diện (Themes)**.
5. Chọn và kích hoạt **KIPA Việt Hàn**.
