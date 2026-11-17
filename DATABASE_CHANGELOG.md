# KIPA VIỆT HÀN — NHẬT KÝ THAY ĐỔI CƠ SỞ DỮ LIỆU (DATABASE CHANGELOG)

> **Hệ thống theo dõi phiên bản Database:** Ghi nhận toàn bộ các mốc thay đổi cấu trúc, di chuyển dữ liệu (Migration) và quyết định kiến trúc  
> **Quản lý bởi:** Senior Database Architect & MySQL Engineer  

---

## [Phiên bản 2.2.0] — Ngày 17/11/2026 (HOÀN THIỆN NÂNG CAO & BẢO VỆ TOÀN DIỆN)
### Hoàn thiện Nghiệp vụ Tư vấn, Phân quyền, SLA, Phê duyệt kép, Kho tài liệu & Bảo mật Cổng học viên

#### Quyết định kiến trúc trọng tâm:
1. **Kiểm toán chính xác bằng công cụ tự động:**
   - Sử dụng script `tools/verify_schema.py` đếm chính xác: **42 bảng vật lý** (`CREATE TABLE`) và **1 SQL View** (`kipa_student_documents`).
   - Tổng cộng **78 khóa ngoại** (Foreign Keys), 100% sử dụng kiểu dữ liệu `BIGINT UNSIGNED`, `ENGINE=InnoDB`, `utf8mb4_unicode_ci`.
2. **Bổ sung Phân hệ Tiếp nhận Lead & Hồ sơ Tư vấn Riêng (5 bảng mới):**
   - `kipa_lead_sources`: Danh mục nguồn tiếp nhận (Facebook, Zalo, Website, Hotline, Giới thiệu).
   - `kipa_campaigns`: Chiến dịch tuyển sinh theo mùa.
   - `kipa_consultation_dossiers`: Hồ sơ tư vấn riêng biệt, **tách độc lập với Hồ sơ dịch vụ**, lưu lịch hẹn, lý do không tiếp tục (`lost_reason`) và quy trình bàn giao (`handover_status`).
   - `kipa_consultation_interests`: Nhu cầu dịch vụ chi tiết của đợt tư vấn.
   - `kipa_consultation_activities`: Lịch sử cuộc gọi, gặp mặt, tương tác Zalo và lịch hẹn.
3. **Hoàn thiện Phân quyền RBAC & Quản lý Tổ chức:**
   - Bổ sung `direct_manager_id` vào `kipa_staff` để xác định một cấp trên trực tiếp chính.
   - `kipa_role_permissions`: Bảng ma trận gán quyền cụ thể cho từng vai trò.
   - `kipa_permissions.is_sensitive`: Đánh dấu quyền truy cập dữ liệu nhạy cảm.
   - `kipa_staff_role_history`: Lịch sử cấp, sửa, thu hồi quyền khi chuyển phòng ban.
   - `kipa_role_delegations`: Bổ sung trạng thái duyệt `approved_by` và phạm vi ủy quyền `delegated_scope`.
4. **Hoàn thiện Nhiệm vụ và Cam kết SLA:**
   - `kipa_sla_policies`: Chính sách SLA theo loại dịch vụ và chi nhánh.
   - `kipa_working_calendars`: Khung giờ hành chính và ngày nghỉ lễ để tính SLA thời gian thực.
   - `kipa_tasks`: Hỗ trợ nhiệm vụ con (`parent_id`), tạm dừng/tiếp tục đồng hồ SLA (`sla_status`, `sla_paused_at`, `sla_total_paused_minutes`), nhiệm vụ lặp lại (`is_recurring`), xác nhận hoàn thành (`completion_confirmed_by`, `completion_rating`).
   - `kipa_task_collaborators`: Quản lý người phối hợp trong nhiệm vụ.
   - `kipa_task_history`: Lịch sử thay đổi nhiệm vụ và gia hạn deadline.
5. **Hoàn thiện Quy trình Phê duyệt Nhiều bước & Phê duyệt kép:**
   - `kipa_approval_workflows`: Định nghĩa quy trình duyệt nhiều bước, phiên bản và cờ phê duyệt kép (`requires_dual_approval`).
   - `kipa_approval_workflow_steps`: Chi tiết các bước duyệt kèm SLA và vai trò phụ trách.
   - `kipa_approvals`: Bổ sung `snapshot_payload` (JSON) đóng băng cấu hình dữ liệu tại thời điểm gửi duyệt, ràng buộc chống tự duyệt `CHECK (requested_by != approver_id)`, hỗ trợ người duyệt thứ 2 (`second_approver_id`).
6. **Bảo vệ Kho Tài liệu & An toàn Lưu trữ:**
   - Thay đổi ràng buộc `kipa_dossier_documents.dossier_id` thành **`ON DELETE RESTRICT`** để không bị xóa mất dữ liệu lịch sử khi đóng hồ sơ.
   - `kipa_document_access_logs`: Nhật ký kiểm tra quyền xem, tải xuống và chia sẻ tài liệu nhạy cảm.
   - Phân biệt rõ 3 trạng thái: Lưu trữ (`ARCHIVED`), Hủy hợp đồng (`CANCELLED`), Xóa vĩnh viễn (`PURGED`).
   - Đảm bảo đường dẫn file scan lưu tại `kipa-private/storage/documents/` ngoài webroot, tải qua proxy endpoint bảo mật.
7. **Bảo mật Cổng Học Viên & Kiểm tra View Hợp Nhất:**
   - Nâng cấp `VIEW kipa_student_documents` bổ sung `user_id`, `customer_id`, `dossier_id`.
   - API Cổng học viên bắt buộc lọc strictly theo Session `$_SESSION['user_id']` từ server, loại bỏ hoàn toàn lỗ hổng lộ tài liệu chéo (IDOR).
8. **Kiểm tra Đối chiếu Thực tế Mã Nguồn:**
   - Đã đọc trực tiếp 5 file: `api/submit.php`, `api/account.php`, `api/cleanup.php`, `api/bootstrap.php`, `api/admin.php`.
   - Phát hiện lệnh xóa lead 12 tháng xuất hiện ở cả `api/cleanup.php` (dòng 16) và `api/submit.php` (dòng 200). Đã ghi rõ hiện trạng và đề xuất sửa đổi cụ thể khi triển khai.

---

## [Phiên bản 2.1.0] — Ngày 17/11/2026
### Vòng Kiểm Tra Cuối Cùng & Chuẩn Hóa Toàn Diện DDL
- Xác định 28 bảng vật lý và 1 SQL View.
- Khắc phục nguy cơ cascade xóa lead 12 tháng bằng `ON DELETE SET NULL`.
- Hợp nhất kho tài liệu qua SQL View `kipa_student_documents`.

---

## [Phiên bản 2.0.0] — Tháng 11/2026
### Khởi tạo Kiến trúc CRM KIPA và Student Portal Thống Nhất
- Xác lập quyết định dùng chung 1 database MySQL duy nhất trên cPanel Shared Hosting.
- Thiết kế 18 bảng CRM sơ bộ và 7 bảng Cổng học viên.

---

## [Phiên bản 1.2.0] — Đầu Tháng 11/2026
### Thiết kế Giao diện Cổng Học Viên 10 Tab (Student Portal Prototype)
- Bổ sung cấu trúc 7 bảng lưu trữ thông tin học viên trong `backend/schema.sql`.

---

## [Phiên bản 1.1.0] — Tháng 10/2026
### Thiết kế Mô hình CMS Nội Dung 85 Bảng (Theo SEO Plan)
- Chuẩn hóa mô hình dữ liệu CMS theo tài liệu Excel `KIPA - SEO Plan.xlsx`.

---

## [Phiên bản 1.0.0] — Khởi tạo ban đầu
### Nền tảng Website KIPA Core
- Tạo 5 bảng nền tảng: `kipa_leads`, `kipa_rate_limits`, `kipa_otp_codes`, `kipa_users`, `kipa_admins`.
