# KIPA VIỆT HÀN — NHẬT KÝ THAY ĐỔI CƠ SỞ DỮ LIỆU (DATABASE CHANGELOG)

> **Hệ thống theo dõi phiên bản Database:** Ghi nhận toàn bộ các mốc thay đổi cấu trúc, di chuyển dữ liệu (Migration) và quyết định kiến trúc  
> **Quản lý bởi:** Senior Database Architect & MySQL Engineer  

---

## [Phiên bản 2.0.0] — Tháng 11/2026 (HIỆN TẠI)
### Bổ sung phân hệ KIPA CRM và KIPA Student Portal Thống Nhất

#### Quyết định kiến trúc được xác nhận:
- **Tiếp tục sử dụng 1 Database MySQL duy nhất** cho toàn bộ Website, CRM và Student Portal. Phân tách ranh giới nghiệp vụ bằng tiền tố bảng và quyền truy cập.
- **Bảo toàn dữ liệu cũ 100%:** Không đổi tên, không xóa bảng/cột, không drop dữ liệu hiện có.
- **Khắc phục triệt để nguy cơ xóa dây chuyền (Cascade Deletion):** Cấu hình khóa ngoại từ `kipa_customers` sang `kipa_leads` là `ON DELETE SET NULL`. Cập nhật `api/cleanup.php` chỉ dọn lead rác chưa chuyển đổi thành khách hàng.

#### Thay đổi cấu trúc (DDL):
- **Mở rộng an toàn 2 bảng hiện có:**
  - `kipa_leads`: Bổ sung `converted_to_customer_id` (BIGINT UNSIGNED NULL) và `lead_status` (VARCHAR 32 DEFAULT 'NEW').
  - `kipa_users`: Bổ sung `avatar_url` (VARCHAR 255 NULL), `is_active` (BOOLEAN DEFAULT 1), `last_login_at` (DATETIME NULL).
- **Tạo mới 18 bảng KIPA CRM:**
  - Cơ cấu tổ chức & Nhân sự: `kipa_branches`, `kipa_departments`, `kipa_staff`, `kipa_roles`, `kipa_permissions`, `kipa_staff_roles`.
  - Khách hàng trung tâm: `kipa_customers`, `kipa_customer_addresses`, `kipa_customer_relations`, `kipa_customer_education`.
  - Hồ sơ dịch vụ & Nhiệm vụ: `kipa_service_dossiers`, `kipa_dossier_assignments`, `kipa_tasks`.
  - Giấy tờ & Thẩm định: `kipa_dossier_documents`, `kipa_document_versions`, `kipa_document_reviews`.
  - Phê duyệt & Giám sát: `kipa_approvals`, `kipa_audit_logs`, `kipa_notification_settings`.
- **Hoàn thiện 7 bảng KIPA Student Portal:**
  - `kipa_students`, `kipa_student_journey`, `kipa_student_documents`, `kipa_student_schedules`, `kipa_student_notifications`, `kipa_student_materials`, `kipa_student_support`.
- **Chuẩn bị DDL cho 6 bảng LMS tương lai:**
  - `kipa_lms_classes`, `kipa_lms_lessons`, `kipa_lms_enrollments`, `kipa_lms_attendance`, `kipa_lms_exams`, `kipa_lms_grades`.

#### Kiểm thử & Kết quả:
- 100% câu lệnh DDL sử dụng `CREATE TABLE IF NOT EXISTS`, `ENGINE=InnoDB`, `utf8mb4`.
- Tương thích tuyệt đối với các script nộp form cũ (`api/submit.php`, `api/register.php`, `api/login.php`).
- Sinh thành công báo cáo đối chiếu chi tiết [DATABASE_FIELD_AUDIT.xlsx](file:///d:/JOB/KIPA%20Vi%E1%BB%87t%20H%C3%A0n/Website/dev/Web-KIPA-master/Web-KIPA-master/DATABASE_FIELD_AUDIT.xlsx).

---

## [Phiên bản 1.2.0] — Đầu Tháng 11/2026
### Thiết kế Giao diện Cổng Học Viên 10 Tab (Student Portal Prototype)
- Bổ sung cấu trúc 7 bảng lưu trữ thông tin học viên trong `backend/schema.sql`.
- Tạo cơ sở dữ liệu giả lập SQLite `backend/dev_leads.sqlite` phục vụ phát triển local qua `dev_server.py`.
- Tích hợp các API `POST /api/account.php` hỗ trợ cập nhật hồ sơ, đổi mật khẩu, nộp giấy tờ, gửi ticket hỗ trợ và theo dõi lộ trình 5 bước.

---

## [Phiên bản 1.1.0] — Tháng 10/2026
### Thiết kế Mô hình CMS Nội Dung 85 Bảng (Theo SEO Plan)
- Chuẩn hóa mô hình dữ liệu CMS theo tài liệu Excel `KIPA - SEO Plan.xlsx`.
- Tạo file đặc tả dữ liệu `api/content-model.json` và script sinh DDL `backend/content-schema.sql`.
- Thiết kế 10 nhóm bảng A -> J (Chương trình, Trường học, Học bổng, Địa điểm, Tin tức, Đăng ký, Cài đặt).
- Bổ sung bảng `kipa_content_state` theo dõi fingerprint và phiên bản migration.

---

## [Phiên bản 1.0.0] — Khởi tạo ban đầu
### Nền tảng Website KIPA Core
- Tạo 5 bảng nền tảng:
  - `kipa_leads`: Lưu dữ liệu form đăng ký tư vấn.
  - `kipa_rate_limits`: Chống spam và brute-force.
  - `kipa_otp_codes`: Mã xác thực email 6 số.
  - `kipa_users`: Tài khoản đăng nhập học viên.
  - `kipa_admins`: Tài khoản quản trị kỹ thuật.
- Thiết lập tệp cấu hình bảo mật `kipa-private/config.php` nằm ngoài webroot.
- Thiết lập cơ chế xóa lead tự động sau 12 tháng qua cronjob `api/cleanup.php`.
