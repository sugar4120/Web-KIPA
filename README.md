# KIPA Việt Hàn — Website Chính Thức

Hệ thống website chính thức của **KIPA Việt Hàn** — Đơn vị hàng đầu trong lĩnh vực Tư vấn Du học Hàn Quốc, Du học Úc, Xuất khẩu lao động & Đào tạo tiếng Hàn TOPIK cấp tốc.

---

## 🌟 Tính Năng & Điểm Nổi Bật

- **Website HTML tĩnh**: Không phụ thuộc WordPress khi hiển thị trang. Form hiện chưa có backend nhận dữ liệu; không coi việc chuyển sang trang cảm ơn là gửi thành công.
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

**Chưa có kết nối hosting được kiểm chứng.** Bước deploy mặc định bị khóa.
Trước khi bật, hỏi Nhân Hòa/cPanel xem tài khoản có hỗ trợ **explicit FTPS**
(thường cổng 21), hostname có chứng chỉ TLS hợp lệ và đường dẫn FTP trỏ tới
document root nào. FTPS khác SFTP/SSH; script hiện không hỗ trợ SFTP,
không dùng FTP không mã hóa và không bỏ kiểm tra chứng chỉ.

Trong **GitHub → Settings → Secrets and variables → Actions**, cấu hình:

| Loại | Tên | Nội dung |
|---|---|---|
| Secret | `FTP_HOST` | Hostname FTPS khớp chứng chỉ TLS |
| Secret | `FTP_USER` | Tài khoản FTP giới hạn vào website nếu có thể |
| Secret | `FTP_PASSWORD` | Mật khẩu mới, không dùng mật khẩu đã lộ |
| Variable | `FTP_REMOTE_DIR` | Đường dẫn tuyệt đối theo góc nhìn tài khoản FTP, ví dụ `/public_html` hoặc `/` nếu đã chroot vào document root |
| Variable | `FTP_PORT` | Cổng explicit FTPS; mặc định `21` |
| Variable | `DEPLOY_ENABLED` | Chỉ đặt `true` sau khi xác nhận kết nối, đường dẫn và có bản sao lưu |

`DEPLOY_ENABLED` phải là **repository variable** vì được kiểm tra trước khi job
deploy khởi chạy. Các secret và biến `FTP_REMOTE_DIR`, `FTP_PORT` có thể đặt
trong environment `production`; nên cấu hình người duyệt bắt buộc cho
environment này trước lần deploy đầu.

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

### 4. Backend form — mới khảo sát, chưa triển khai

Năm trang có form cần nối backend: `dang-ky-tu-van.html`, `lien-he.html`,
`tieng-han.html`, `xuat-khau-lao-dong.html`, `hop-tac-quoc-te.html`.
Một số trường còn thiếu `name`; handler hiện chuyển trang cảm ơn hoặc hiện alert
mà không lưu/gửi dữ liệu.

Phương án phù hợp hosting hiện tại là endpoint PHP dùng PDO, tài khoản MySQL
riêng và bảng lead riêng, không dùng bảng WordPress. Khi được duyệt:

1. Chuẩn hóa trường tên, số điện thoại, chương trình, nội dung, nguồn trang;
   chỉ thu thập email/năm sinh/khu vực khi cần thiết.
2. Kiểm tra dữ liệu, đồng ý chính sách, chống spam/rate limit ở máy chủ;
   lưu bằng prepared statements, cấu hình bí mật ngoài document root.
3. Chỉ chuyển trang cảm ơn sau khi lưu thành công; báo lỗi rõ và giữ dữ liệu form khi thất bại.
4. Nếu thêm Telegram/email, xác định nơi nhận, quyền truy cập, thời hạn lưu dữ liệu
   và cơ chế báo lỗi thông báo; không công khai dữ liệu khách hàng trong Git/log.

Chưa tạo endpoint, bảng MySQL hoặc gửi thử dữ liệu tới dịch vụ ngoài.

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
