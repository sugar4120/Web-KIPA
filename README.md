# KIPA Việt Hàn — Website Chính Thức

Hệ thống website chính thức của **KIPA Việt Hàn** — Đơn vị hàng đầu trong lĩnh vực Tư vấn Du học Hàn Quốc, Du học Úc, Xuất khẩu lao động & Đào tạo tiếng Hàn TOPIK cấp tốc.

---

## 🌟 Tính Năng & Điểm Nổi Bật

- **Kiến trúc Jamstack thuần khiết**: Tải trang tức thì (< 0.5s), không dùng cơ sở dữ liệu, không WordPress, chống DDoS và bảo mật tuyệt đối.
- **Thiết kế chuẩn Figma 100%**: Đầy đủ 16 trang chuyên nghiệp, tương thích 100% Mobile & Desktop.
- **Hệ thống Component dùng chung**: Header và Footer được quản lý độc lập tại `header.html` và `footer.html`, tự động build đồng bộ vào tất cả các trang qua script `build.py`.
- **Sẵn sàng triển khai 1-Click**: Cấu hình sẵn cho Vercel (`vercel.json`), Netlify (`netlify.toml`, `_redirects`), Cloudflare Pages và GitHub Pages.
- **Chuẩn hóa SEO On-Page**: Tối ưu thẻ OpenGraph, Twitter Cards, Sitemap XML, Robots.txt và Schema Markup.

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

---

## 🚀 Hướng Dẫn Đẩy Lên Git & Triển Khai (Deploy)

### 1. Đẩy code lên GitHub:
```bash
git add .
git commit -m "feat: cập nhật website KIPA Việt Hàn"
git push origin master
```

### 2. Triển khai lên Vercel (Khuyên dùng - Miễn phí):
1. Truy cập [vercel.com](https://vercel.com) và đăng nhập bằng tài khoản GitHub.
2. Bấm **"Add New Project"** ➔ Chọn repository `Web-KIPA`.
3. Bấm **"Deploy"**. Trong 15 giây, website của bạn sẽ hoạt động trực tiếp!
4. Để gắn tên miền `kipaviethan.edu.vn`: Vào mục **Settings** ➔ **Domains** ➔ Thêm tên miền và trỏ DNS theo hướng dẫn của Vercel.

---

## 📞 Thông Tin Liên Hệ KIPA Việt Hàn

- **Hotline**: `036 851 3865` | `0889 2222 55`
- **Email**: `duhocquoctekipaviethan@gmail.com`
- **Trụ sở chính**: 135 Bạch Năng Thi, Phường Tân Hưng, TP. Hải Phòng
- **Văn phòng đại diện**: Seoul (Hàn Quốc) & Sydney (Úc)

