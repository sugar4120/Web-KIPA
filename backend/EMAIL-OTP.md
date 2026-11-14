# Email OTP đăng ký tài khoản

Website dùng địa chỉ Gmail **kipaviethan@gmail.com** của KIPA để **gửi** mã OTP;
học viên **nhận** mã trong hộp thư và nhập lại trên website để xác thực email
khi đăng ký tài khoản.

```text
/dang-ky-tai-khoan/ ──POST {action: send, email}──> /api/otp.php ──SMTP Gmail──> hộp thư học viên
/dang-ky-tai-khoan/ ──POST {full_name, email, password, code}──> /api/register.php ──> kipa_users
```

## 1. Chuẩn bị Gmail (một lần)

1. Bật **xác minh 2 bước** cho tài khoản Gmail `kipaviethan@gmail.com`.
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
    'smtp_username' => 'kipaviethan@gmail.com',
    'smtp_password' => '<app-password-16-ky-tu>',
    'smtp_from_email' => 'kipaviethan@gmail.com',
    'smtp_from_name' => 'KIPA Việt Hàn',                  // tùy chọn
];
```

Sau đó tạo bảng lưu mã OTP và bảng tài khoản (chạy một lần, không đổi dữ liệu hiện có):

```bash
/opt/alt/php83/usr/bin/php backend/account_tables.php /home/<cpanel-user>/public_html
```

## 3. API

Trang `/dang-ky-tai-khoan/` dùng hai endpoint JSON (`Content-Type: application/json`,
cùng Origin của website). Trường honeypot `website` phải rỗng nếu gửi kèm.

### Gửi mã — `POST /api/otp.php`

```json
{ "action": "send", "email": "hocvien@example.com" }
```

- Mã 6 chữ số, hiệu lực **10 phút**; chỉ mã mới nhất có thể dùng.
- Từ chối email đã có tài khoản (409).
- Giới hạn: 5 lần gửi / 15 phút mỗi IP và 5 mã / giờ mỗi email.

### Xác thực mã (tùy chọn, dùng cho luồng khác) — `POST /api/otp.php`

```json
{ "action": "verify", "email": "hocvien@example.com", "code": "123456" }
```

- Tối đa **5 lần nhập sai** mỗi mã; 15 lần xác thực / 15 phút mỗi IP.
- Thành công trả `{"ok": true}` và ghi `verified_at`.

### Hoàn tất đăng ký — `POST /api/register.php`

```json
{
  "full_name": "Nguyễn Văn A",
  "email": "hocvien@example.com",
  "phone": "0368513865",
  "password": "mat-khau-8-ky-tu",
  "code": "123456"
}
```

- Kiểm tra OTP và tạo tài khoản **trong cùng một transaction**; mã OTP được
  tiêu thụ ngay khi đăng ký thành công (không gọi `verify` trước đó).
- Mật khẩu 8–72 ký tự, lưu bằng `password_hash()`; email trùng trả 409.
- Giới hạn 10 lần đăng ký / giờ mỗi IP. Thành công trả `201 {"ok": true}`.

### Đăng nhập học viên — `POST /api/login.php`

Trang `/dang-nhap/` dùng endpoint này với các `action`:

- `login` (`email`, `password`): xác thực bằng `password_verify`, tạo phiên
  cookie `kipa_user` (HttpOnly, Secure, SameSite=Lax); sai thông tin trả 401
  với thông báo chung, giới hạn 10 lần / 15 phút mỗi IP.
- `me`: trả `{"ok": true, "user": {...}}` hoặc `user: null` nếu chưa đăng nhập.
- `logout`: hủy phiên.

### Hồ sơ học viên — `POST /api/account.php` (yêu cầu phiên đăng nhập)

Trang `/ho-so/` dùng endpoint này với các `action`:

- `get`: trả hồ sơ hiện tại (họ tên, email, SĐT, ngày tạo).
- `update` (`full_name`, `phone`): cập nhật thông tin cá nhân; email không đổi.
- `change_password` (`current_password`, `new_password`): yêu cầu đúng mật khẩu
  hiện tại, giới hạn 10 lần / 15 phút mỗi IP, làm mới ID phiên sau khi đổi.

### Quên mật khẩu — trang `/quen-mat-khau/`

1. Gửi mã: `POST /api/otp.php` với `purpose: "reset"` — chỉ gửi khi email đã
   có tài khoản; email lạ vẫn nhận phản hồi chung để không dò được tài khoản.
   (`purpose: "register"` — mặc định — ngược lại: từ chối email đã đăng ký.)
2. Đặt lại: `POST /api/reset-password.php` (`email`, `code`, `password`) —
   kiểm tra OTP và cập nhật mật khẩu trong cùng transaction; giới hạn
   10 lần / giờ mỗi IP.

## 4. Bảo mật

- Database chỉ lưu **HMAC-SHA256** của mã (khóa `app_key`), không lưu mã thô.
- SMTP dùng STARTTLS với xác minh chứng chỉ; chặn header injection ở người nhận
  và tiêu đề thư.
- Cron `api/cleanup.php` hiện có tự xóa mã hết hạn sau 1 ngày; chạy được cả khi
  bảng chưa tạo.
- Backup database trên hosting cần bao gồm `kipa_otp_codes` như các bảng khác.
