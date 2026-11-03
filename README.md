# KIPA Việt Hàn — Website Chính Thức

Hệ thống website chính thức của **KIPA Việt Hàn** — Đơn vị hàng đầu trong lĩnh vực Tư vấn Du học Hàn Quốc, Du học Úc, Xuất khẩu lao động & Đào tạo tiếng Hàn TOPIK cấp tốc.

---

## 🌟 Tính Năng & Điểm Nổi Bật

- **Website HTML tĩnh**: Không phụ thuộc WordPress khi hiển thị trang. Năm form đăng ký gửi qua API PHP tới database riêng, chỉ chuyển tới trang cảm ơn sau khi lưu thành công.
- **Thiết kế chuẩn Figma 100%**: Đầy đủ 16 trang chuyên nghiệp, tương thích 100% Mobile & Desktop.
- **Hệ thống Component dùng chung**: Header và Footer được quản lý độc lập tại `header.html` và `footer.html`, tự động build đồng bộ vào tất cả các trang qua script `build.py`.
- **Hosting chính**: LiteSpeed/cPanel. GitHub Actions build và kiểm thử tự động; bước deploy FTPS chỉ chạy khi được bật và cấu hình đầy đủ.
- **SEO On-Page**: OpenGraph, Twitter Cards, canonical, sitemap và JSON-LD được sinh qua `build.py`.

---

## 📁 Cấu Trúc Thư Mục

```
Kipa/
├── index.html                   # Trang chủ (Homepage)
├── gioi-thieu.html              # Giới thiệu KIPA Việt Hàn
├── doi-ngu.html                 # Đội ngũ ban lãnh đạo & chuyên gia
├── du-hoc.html                  # Du học quốc tế (Tổng quan các nước)
├── du-hoc-han-quoc.html         # Du học Hàn Quốc (Trọng điểm)
├── du-hoc-uc.html               # Du học Úc (Chuyên sâu)
├── xuat-khau-lao-dong.html      # Xuất khẩu lao động & Việc làm quốc tế
├── tieng-han.html               # Đào tạo tiếng Hàn TOPIK
├── co-so.html                   # Hệ thống cơ sở đào tạo & bản đồ
├── hop-tac-quoc-te.html         # Hợp tác giáo dục quốc tế
├── lien-he.html                 # Liên hệ & Bản đồ
├── dang-ky-tu-van.html          # Form đăng ký tư vấn 1:1
├── faq.html                     # Hỏi đáp thường gặp
├── chinh-sach-bao-mat.html      # Chính sách bảo mật thông tin
├── cam-on.html                  # Trang xác nhận sau khi gửi form
├── 404.html                     # Trang báo lỗi 404
│
├── header.html                  # Component Header dùng chung
├── footer.html                  # Component Footer dùng chung
├── build.py                     # Script tự động compile 16 trang
├── dev_server.py                # Server chạy thử nghiệm local (Port 3000)
├── deploy_ftps.py               # Upload bản build công khai qua FTPS
├── tests/                       # Kiểm thử build, schema và đóng gói deploy
├── .github/workflows/deploy.yml # CI/CD (deploy mặc định chưa bật)
│
├── assets/                      # Toàn bộ hình ảnh, banner, icon
├── Logo.jpg                     # Logo thương hiệu
├── sitemap.xml                  # Sơ đồ website SEO
├── robots.txt                   # Quy tắc điều hướng bot tìm kiếm
├── vercel.json                  # Cấu hình Clean URLs cho Vercel
├── _redirects                   # Cấu hình điều hướng cho Netlify
└── README.md                    # Tài liệu dự án
```

---

## 💻 Hướng Dẫn Phát Triển Local

### 1. Khởi chạy máy chủ nội bộ:
```bash
python3 dev_server.py
```
Truy cập trình duyệt: `http://localhost:3000/`

### 2. Cập nhật Menu hoặc Footer:
Khi bạn muốn đổi số hotline, địa chỉ, hoặc thêm menu mới:
1. Mở file `header.html` hoặc `footer.html` và chỉnh sửa.
2. Chạy lệnh:
```bash
python3 build.py
```
Toàn bộ 16 trang web sẽ tự động được cập nhật đồng bộ trong 1 giây!

### 3. Kiểm thử và tạo gói triển khai:
```bash
python3 -m unittest discover -s tests -v
php tests/test_cleanup.php
php tests/test_config.php
node tests/test_forms.js
python3 build.py --output-dir dist
```

`dist/` phải chưa tồn tại hoặc còn trống. Gói này chỉ chứa trang HTML đã build,
thư mục Clean URL, tài nguyên công khai trong `assets/`, logo, sitemap,
robots và `.htaccess`. Không upload trực tiếp cả repository: mã nguồn build,
file Excel kế hoạch, `.env`, hướng dẫn nội bộ và thông tin đăng nhập không thuộc website công khai.

Build mặc định vẫn cập nhật các HTML trong repository. Build lặp lại không thêm
header/footer hoặc bố cục lồng nhau.

---

## 🚀 Hướng Dẫn Đẩy Lên Git & Triển Khai (Deploy)

### 1. Đẩy code lên GitHub:
```bash
git add <cac-tep-da-kiem-tra>
git commit -m "feat: cập nhật website KIPA Việt Hàn"
git push origin master
```

### 2. Chuẩn bị GitHub Actions cho cPanel

Workflow chạy kiểm thử và build khi push/PR vào `master`; có thể chạy tay qua
**Actions → Build and deploy KIPA → Run workflow**. Artifact `kipa-public-site`
là bản website đã build, không chứa toàn bộ repository.

Đã kiểm tra kết nối **explicit FTPS** và xác minh chứng chỉ TLS cho
`wordpress-hosting10.nhanhoa.com` trên cổng 21. Chưa xác nhận đăng nhập FTP,
quyền ghi và document root vì hosting hiện chưa có tài khoản FTP riêng.
Git Version Control không có trong giao diện cPanel này; GitHub Actions là
luồng triển khai dự kiến. Deploy production vẫn bị khóa đến khi kiểm tra được
tài khoản FTPS. FTPS khác SFTP/SSH; script không hỗ trợ SFTP, không dùng FTP
không mã hóa và không bỏ kiểm tra chứng chỉ.

Trong **GitHub → Settings → Secrets and variables → Actions**, cấu hình:

| Loại | Tên | Nội dung |
|---|---|---|
| Secret | `FTP_HOST` | Hostname FTPS khớp chứng chỉ TLS |
| Secret | `FTP_USER` | Tài khoản FTP giới hạn vào website nếu có thể |
| Secret | `FTP_PASSWORD` | Mật khẩu mới, không dùng mật khẩu đã lộ |
| Variable | `FTP_REMOTE_DIR` | Đường dẫn tuyệt đối theo góc nhìn tài khoản FTP. Nếu tài khoản bị giới hạn trực tiếp vào `public_html`, đặt `/` |
| Variable | `FTP_PORT` | Cổng explicit FTPS; mặc định `21` |
| Variable | `DEPLOY_ENABLED` | Chỉ đặt `true` sau khi xác nhận kết nối, đường dẫn và có bản sao lưu |

`DEPLOY_ENABLED` phải là **repository variable** vì được kiểm tra trước khi job
deploy khởi chạy. Các secret và biến `FTP_REMOTE_DIR`, `FTP_PORT` có thể đặt
trong environment `production`; nên cấu hình người duyệt bắt buộc cho
environment này trước lần deploy đầu.

Với host đã kiểm tra, `FTP_HOST=wordpress-hosting10.nhanhoa.com` và
`FTP_PORT=21`. Tạo một tài khoản FTPS riêng trong cPanel, giới hạn thư mục ở
`public_html`; khi đó `FTP_USER` là tên FTP đầy đủ (kèm `@kipaviethan.edu.vn`)
và `FTP_REMOTE_DIR=/`. Không dùng tài khoản chính cPanel làm FTP deployment.

Khi bật, mỗi push lên `master` sẽ build, kiểm thử và upload artifact. Script
kiểm tra danh sách file được phép, bảo vệ kênh điều khiển và dữ liệu bằng TLS,
dừng khi upload lỗi và **không xóa file trên máy chủ**. Vì không xóa, việc gỡ
trang cũ cần được xử lý riêng. Đây là upload từng file, không phải chuyển bản
phát hành nguyên tử; lỗi giữa chừng có thể để lại bản cập nhật chưa đầy đủ.
Khôi phục bằng cách triển khai lại bản tốt đã biết.

`.htaccess` của website tĩnh chặn chạy trực tiếp PHP ngoài `/api/`, để tạm dừng
WordPress trong khi giữ nguyên các tệp và database làm bản dự phòng. Việc
chuyển lại WordPress cần khôi phục thông tin kết nối database và xem xét lại
các quy tắc truy cập. Bản deploy không xóa tệp WordPress.

Trước khi dùng Actions làm luồng deploy chính, tắt hoặc thống nhất luồng deploy
cPanel cũ: `.cpanel.yml` hiện copy cả thư mục làm việc, không dùng gói public
được kiểm tra ở trên. Không chạy song song hai luồng.

`deploy_ftp.py` là tiện ích local cũ đang bị Git bỏ qua; workflow mới không dùng
tiện ích này. Không đưa thông tin đăng nhập của tiện ích cũ vào Git.

### 3. Schema SEO và giới hạn dữ liệu

- Một thực thể mang cả hai kiểu `Organization` và `EducationalOrganization`,
  dùng chung `@id` ổn định trên toàn website.
- `BreadcrumbList` cho các trang nội dung con.
- Năm `Course` chỉ nằm trên trang tiếng Hàn và khớp các khóa học đang hiển thị.
  Không khai báo KIPA là đơn vị trực tiếp tổ chức chương trình đại học nước ngoài.
- Sitemap bao phủ 14 trang có thể index. Trang cảm ơn và 404 có `noindex, follow`
  và không nằm trong sitemap.
- Tham chiếu các tab `1. ENTITY`, `SP` và kế hoạch nội dung website trong
  `KIPA - SEO Plan.xlsx`; ưu tiên thông tin liên hệ hiện hành đã được xác nhận.
  File kế hoạch ghi loại hình công ty khác phần bàn giao, nên chưa thêm
  `legalName`, mã số thuế hoặc ngày thành lập vào schema.
- Không tự thêm đánh giá, học phí, lịch khai giảng hoặc chứng nhận chưa được xác minh.
  JSON-LD hợp lệ không đảm bảo Google hiển thị rich results; cần kiểm tra thêm
  bằng Schema Markup Validator/Search Console sau khi deploy.

### 4. Backend form và trang quản trị

Năm form tại `dang-ky-tu-van.html`, `lien-he.html`, `tieng-han.html`,
`xuat-khau-lao-dong.html` và `hop-tac-quoc-te.html` gửi JSON tới
`api/submit.php`. Dữ liệu chỉ được chuyển tới `/cam-on/` sau khi database xác
nhận lưu thành công; lỗi cấu hình/kết nối hiển thị thông báo để người gửi thử
lại. Backend không gửi lead ra email, Telegram hoặc dịch vụ bên ngoài.

Backend cần PHP 8.1+ với PDO MySQL và một database/tài khoản MySQL **riêng**,
không dùng database hay user WordPress. Tạo ba bảng trong `backend/schema.sql`.
Trên hosting Nhân Hòa hiện tại, `api/.htaccess` chọn CloudLinux PHP 8.3 riêng
cho `/api/` bằng handler `application/x-httpd-alt-php83___lsphp`; không thay đổi
PHP của WordPress cũ. Khi chuyển hosting, cần thay handler này theo cấu hình
máy chủ mới và xác minh PHP/PDO trước khi deploy.
Đặt cấu hình PHP bên ngoài document root tại
`/home/<cpanel-user>/kipa-private/config.php` (thư mục quyền `700`, file `600`):

```php
<?php
return [
    'db_dsn' => 'mysql:host=localhost;dbname=<cpanel-prefix>_kipa_forms;charset=utf8mb4',
    'db_user' => '<full-cpanel-database-user-name>',
    'db_password' => '<database-user-password>',
    'app_key' => '<random-string-at-least-32-characters>',
    'setup_token' => '<different-random-string-at-least-32-characters>',
];
```

`db_user` is the full cPanel-prefixed name of the dedicated MySQL user; it can
have a different suffix from the database name.

Không commit cấu hình thật hoặc gửi mật khẩu qua chat. Tài khoản MySQL chỉ nên
có quyền cần thiết trên ba bảng backend; không cấp quyền tới database WordPress.
Sau khi import schema và đặt config, mở `https://kipaviethan.edu.vn/api/admin.php`,
dùng `setup_token` để tạo tài khoản quản trị đầu tiên (mật khẩu tối thiểu 12 ký
tự). Dashboard yêu cầu đăng nhập, có CSRF protection, giới hạn thử đăng nhập,
cho xem và xác nhận xóa lead, đổi mật khẩu; không có chức năng xuất hàng loạt.

Lead biểu mẫu được tự động xóa sau 12 tháng. Trong cPanel **Cron Jobs**, cấu hình
chạy hằng ngày:

```sh
/opt/alt/php83/usr/bin/php /home/<cpanel-user>/public_html/api/cleanup.php >> /home/<cpanel-user>/kipa-private/cleanup.log 2>&1
```

Lệnh cleanup chỉ chạy qua CLI; đường dẫn trên đã được kiểm chứng với PHP 8.3
của hosting hiện tại. Lịch chạy là `0 0 * * *` (00:00 theo múi giờ máy chủ),
kết quả và lỗi được ghi vào log riêng bên ngoài webroot. Khi chuyển hosting,
cần kiểm tra lại đường dẫn PHP. Rate-limit chỉ lưu HMAC của IP (không lưu IP gốc) tối đa
24 giờ. Backup database phải được cấu hình riêng và bảo vệ tương đương dữ liệu
cá nhân. Chính sách bảo mật hiện nêu thời hạn lưu lead 12 tháng.

Việc deploy production cần giữ `DEPLOY_ENABLED=false` cho tới khi hoàn tất
database, config, tài khoản quản trị và kiểm tra thành công trên hosting.

### 5. Blog có trang quản trị — mới tư vấn, chưa triển khai

Hai hướng không cần quay lại WordPress:

- **CMS dựa trên Git (ví dụ Decap CMS):** biên tập viên soạn bài, upload ảnh,
  lưu nháp/xuất bản qua giao diện. Nội dung được commit vào repository, build
  ra `/tin-tuc/` và `/tin-tuc/<slug>/`; cần cấu hình đăng nhập/OAuth và quyền
  repository. Lưu ý repository public không phù hợp lưu bản nháp bí mật.
- **CMS PHP + MySQL trên hosting:** tài khoản quản trị riêng, không yêu cầu
  biên tập viên dùng GitHub, nhưng cần thiết kế đăng nhập, phân quyền,
  bảo vệ upload, xử lý nội dung an toàn và sao lưu database.

Đề xuất hướng Git nếu ưu tiên website tĩnh và ít vận hành backend.
Chưa cài CMS, tạo tài khoản, template bài viết hoặc bài viết mẫu. Cần chốt
đăng nhập, người biên tập, yêu cầu nháp riêng tư và dịch vụ xác thực trước khi làm.

### 6. Thông tin đăng nhập

Không ghi mật khẩu/token vào chat, code, Excel public hoặc tài liệu nội bộ được
theo dõi bằng Git. Thông tin database đã bị xóa khỏi bản hướng dẫn hiện tại,
**không phải khỏi lịch sử Git**. Cần đổi mật khẩu đã lộ trên cPanel ngay,
cập nhật những dịch vụ còn dùng nó và rà soát lịch sử/file public liên quan.
Không tự viết lại lịch sử Git khi chưa thống nhất với các cộng tác viên.

---

## 📞 Thông Tin Liên Hệ KIPA Việt Hàn

- **Hotline**: `036 851 3865` | `0889 2222 55`
- **Email**: `duhocquoctekipaviethan@gmail.com`
- **Trụ sở chính**: 135 Bạch Năng Thi, Phường Tân Hưng, TP. Hải Phòng
- **Văn phòng đại diện**: Seoul (Hàn Quốc) & Sydney (Úc)
