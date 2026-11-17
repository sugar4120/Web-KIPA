# KIPA VIỆT HÀN — NHẬT KÝ THAY ĐỔI CƠ SỞ DỮ LIỆU (DATABASE CHANGELOG)

> **Hệ thống theo dõi phiên bản Database:** Ghi nhận toàn bộ các mốc thay đổi cấu trúc, di chuyển dữ liệu (Migration) và quyết định kiến trúc  
> **Quản lý bởi:** Senior Database Architect & MySQL Engineer  

---

## [Phiên bản 2.1.0] — Ngày 17/11/2026 (VÒNG KIỂM TRA CUỐI CÙNG & HOÀN THIỆN ĐẶC TẢ)
### Hoàn thiện Kiến trúc Database KIPA CRM + Student Portal trước khi Deploy cPanel

#### Quyết định kiến trúc trọng tâm:
1. **Kiểm toán chính xác bằng công cụ tự động:**
   - Sử dụng script `tools/verify_schema.py` đếm chính xác: **28 bảng vật lý** (`CREATE TABLE`) và **1 SQL View** (`kipa_student_documents`).
   - Tổng cộng 44 khóa ngoại (Foreign Keys), 100% sử dụng kiểu dữ liệu `BIGINT UNSIGNED`, `ENGINE=InnoDB`, `utf8mb4_unicode_ci`.
2. **Hợp nhất kho tài liệu qua SQL View:**
   - Thay vì duy trì 2 bảng tài liệu độc lập (`kipa_dossier_documents` và `kipa_student_documents`) gây xung đột đồng bộ và tốn tài nguyên lưu trữ, thiết kế hợp nhất sử dụng `kipa_dossier_documents` + `kipa_document_versions` làm kho vật lý duy nhất (Single Source of Truth).
   - Tạo **`VIEW kipa_student_documents`** đảm bảo tương thích 100% với giao diện Cổng học viên và API hiện hữu mà không tạo dữ liệu trùng lặp.
3. **Bổ sung các bảng nghiệp vụ CRM nâng cao đã thống nhất:**
   - `kipa_teams`: Phân cấp nhóm làm việc chuyên môn dưới phòng ban.
   - `kipa_role_delegations`: Ủy quyền vai trò tạm thời khi nhân sự nghỉ phép/công tác.
   - `kipa_dossier_status_history`: Theo dõi vòng đời hồ sơ (Tạm dừng, Mở lại, Đóng, Lưu trữ).
   - Nâng cấp `kipa_tasks`: Bổ sung `sla_hours`, `reminder_at`, `escalated_to` phục vụ quản lý cam kết dịch vụ.
   - Nâng cấp `kipa_approvals`: Bổ sung `approval_level` và `next_approver_id` cho quy trình duyệt nhiều cấp.
   - Nâng cấp `kipa_service_dossiers`: Bổ sung `collaborator_id` và hỗ trợ vai trò `COLLABORATOR` trong `kipa_dossier_assignments`.
4. **Bảo vệ tuyệt đối chống xóa dây chuyền (Cascade Protection):**
   - Rà soát toàn bộ khóa ngoại trỏ tới `kipa_leads` và `kipa_users`: 100% sử dụng `ON DELETE SET NULL`.
   - Cập nhật quy trình an toàn cho `api/cleanup.php` chỉ xóa lead chưa chuyển đổi sau 12 tháng.
5. **Cập nhật đồng bộ các tài liệu bàn giao:**
   - [DATABASE_CURRENT_INVENTORY.md](file:///d:/JOB/KIPA%20Vi%E1%BB%87t%20H%C3%A0n/Website/dev/Web-KIPA-master/Web-KIPA-master/DATABASE_CURRENT_INVENTORY.md)
   - [DATABASE_FIELD_AUDIT.xlsx](file:///d:/JOB/KIPA%20Vi%E1%BB%87t%20H%C3%A0n/Website/dev/Web-KIPA-master/Web-KIPA-master/DATABASE_FIELD_AUDIT.xlsx) (125 bảng và view)
   - [DATABASE_GAP_ANALYSIS.md](file:///d:/JOB/KIPA%20Vi%E1%BB%87t%20H%C3%A0n/Website/dev/Web-KIPA-master/Web-KIPA-master/DATABASE_GAP_ANALYSIS.md) (Bảng tổng hợp 5 nhóm)
   - [DATABASE_RELATIONSHIP_MAP.md](file:///d:/JOB/KIPA%20Vi%E1%BB%87t%20H%C3%A0n/Website/dev/Web-KIPA-master/Web-KIPA-master/DATABASE_RELATIONSHIP_MAP.md) (ERD Mermaid hoàn chỉnh)
   - [DATABASE_NEW_TABLES_SPEC.md](file:///d:/JOB/KIPA%20Vi%E1%BB%87t%20H%C3%A0n/Website/dev/Web-KIPA-master/Web-KIPA-master/DATABASE_NEW_TABLES_SPEC.md) (Đặc tả chi tiết 28 bảng + 1 View)
   - [DATABASE_MIGRATION_PLAN.md](file:///d:/JOB/KIPA%20Vi%E1%BB%87t%20H%C3%A0n/Website/dev/Web-KIPA-master/Web-KIPA-master/DATABASE_MIGRATION_PLAN.md) (Quy trình triển khai SOP 6 bước trên cPanel)
   - [crm_portal_schema.sql](file:///d:/JOB/KIPA%20Vi%E1%BB%87t%20H%C3%A0n/Website/dev/Web-KIPA-master/Web-KIPA-master/crm_portal_schema.sql) (Đồng bộ tại thư mục gốc và `backend/`)

---

## [Phiên bản 2.0.0] — Tháng 11/2026
### Khởi tạo Kiến trúc CRM KIPA và Student Portal Thống Nhất
- Xác lập quyết định dùng chung 1 database MySQL duy nhất trên cPanel Shared Hosting.
- Thiết kế 18 bảng CRM sơ bộ và 7 bảng Cổng học viên.
- Phát hiện và đưa ra giải pháp khắc phục nguy cơ xóa dây chuyền của cronjob dọn dẹp lead 12 tháng.

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
  - `kipa_admins`: Quản trị viên kỹ thuật.
