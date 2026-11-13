# Email OTP đăng ký tài khoản

Website dùng một địa chỉ Gmail của KIPA để **gửi** mã OTP; học viên **nhận** mã
trong hộp thư và nhập lại trên website để xác thực email khi đăng ký tài khoản.

```text
Trình duyệt ──POST {action: send, email}──> /api/otp.php ──SMTP Gmail──> hộp thư học viên
Trình duyệt ──POST {action: verify, email, code}──> /api/otp.php ──> kipa_otp_codes
```

## 1. Chuẩn bị Gmail (một lần)

1. Bật **xác minh 2 bước** cho tài khoản Gmail của KIPA.
2. Tạo **App Password** (Mật khẩu ứng dụng) tại
   <https://myaccount.google.com/apppasswords> — Gmail không cho đăng nhập SMTP
   bằng mật khẩu thường.
3. **Không** đưa App Password vào Git, source code hay GitHub Actions. Nó chỉ
   nằm trong file cấu hình riêng trên hosting.

## 2. Cấu hình trên hosting

Thêm các khóa `smtp_*` vào `/home/<cpanel-user>/kipa-private/config.php`
(cùng file cấu hình database hiện có, ngoài web root):

```php
return [
    // ...các khóa db_dsn, db_user, db_password, app_key, setup_token hiện có...
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,                                   // STARTTLS
    'smtp_username' => 'duhocquoctekipaviethan@gmail.com',
    'smtp_password' => '<app-password-16-ky-tu>',
    'smtp_from_email' => 'duhocquoctekipaviethan@gmail.com',
    'smtp_from_name' => 'KIPA Việt Hàn',                  // tùy chọn
];
```

Sau đó tạo bảng lưu mã OTP (chạy một lần, không đổi dữ liệu hiện có):

```bash
/opt/alt/php83/usr/bin/php backend/otp_table.php /home/<cpanel-user>/public_html
```

## 3. API

`POST /api/otp.php` với `Content-Type: application/json`, cùng Origin của
website. Trường honeypot `website` phải rỗng nếu gửi kèm.

### Gửi mã

```json
{ "action": "send", "email": "hocvien@example.com" }
```

- Mã 6 chữ số, hiệu lực **10 phút**; yêu cầu mã mới sẽ hủy mã cũ chưa dùng.
- Giới hạn: 5 lần gửi / 15 phút mỗi IP và 5 mã / giờ mỗi email.

### Xác thực mã

```json
{ "action": "verify", "email": "hocvien@example.com", "code": "123456" }
```

- Tối đa **5 lần nhập sai** mỗi mã; 15 lần xác thực / 15 phút mỗi IP.
- Thành công trả `{"ok": true}` và ghi `verified_at`; backend đăng ký tài khoản
  sau này kiểm tra bản ghi đã xác thực trong `kipa_otp_codes`.

## 4. Bảo mật

- Database chỉ lưu **HMAC-SHA256** của mã (khóa `app_key`), không lưu mã thô.
- SMTP dùng STARTTLS với xác minh chứng chỉ; chặn header injection ở người nhận
  và tiêu đề thư.
- Cron `api/cleanup.php` hiện có tự xóa mã hết hạn sau 1 ngày; chạy được cả khi
  bảng chưa tạo.
- Backup database trên hosting cần bao gồm `kipa_otp_codes` như các bảng khác.
