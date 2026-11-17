# KIPA VIỆT HÀN — NHẬT KÝ THAY ĐỔI CƠ SỞ DỮ LIỆU (DATABASE CHANGELOG)

> **Hệ thống theo dõi phiên bản Database:** Ghi nhận toàn bộ các mốc thay đổi cấu trúc, di chuyển dữ liệu (Migration) và quyết định kiến trúc  
> **Quản lý bởi:** Senior Database Architect & MySQL Engineer  

---

## [Phiên bản 2.3.0] — Ngày 17/11/2026 (CHUẨN HÓA PIPELINE KIPA, LỊCH T2-T7, 7 NHÓM DUYỆT KÉP, 3 CHÍNH SÁCH LƯU TRỮ & KHỬ TRÙNG CMS)
### Hoàn thiện toàn diện thiết kế kiến trúc chuẩn trước khi trình duyệt

#### Quyết định kiến trúc trọng tâm:
1. **Kiểm toán chính xác bằng công cụ tự động:**
   - Script `tools/verify_schema.py` xác thực: **Chính xác 42 bảng vật lý** (`CREATE TABLE`) và **1 SQL View** (`VIEW kipa_student_documents`).
   - Tổng cộng **78 khóa ngoại** (Foreign Keys), 100% sử dụng kiểu dữ liệu `BIGINT UNSIGNED`, `ENGINE=InnoDB`, `utf8mb4_unicode_ci`.
   - Độ đồng nhất tuyệt đối (SHA256 Parity): Cả hai file `crm_portal_schema.sql` và `backend/crm_portal_schema.sql` có hash trùng khớp hoàn toàn (`D2BA640DA5FCB888681DE25C2A7B8488C7139CF18CCA29A88BAB7EC1A8D7DD18`).
2. **Chuẩn hóa Pipeline tư vấn KIPA đã thống nhất:**
   - Bộ trạng thái chuẩn: `NEW`, `CONTACTED`, `CONSULTING`, `POTENTIAL`, `HANDOVER_PENDING`, `WON`, `LOST`.
   - Loại bỏ hoàn toàn các trạng thái ngoại lai tự chế (`TRIAL_CLASS`, `PROPOSAL_SENT`). Bảng `kipa_consultation_dossiers` cho phép cấu hình workflow theo dịch vụ nhưng bảo toàn pipeline chung cốt lõi này.
3. **Sửa lịch làm việc mặc định trong SLA:**
   - Mặc định: Thứ 2 đến Thứ 6: 08:00 – 17:00 (nghỉ trưa 12:00 – 13:30); Thứ 7: 08:00 – 12:00; Chủ nhật: nghỉ.
   - Bảng `kipa_working_calendars` cho phép cấu hình linh hoạt theo từng chi nhánh và ngày lễ để đồng hồ SLA chỉ đếm giờ làm việc thực tế.
4. **Khôi phục đầy đủ 7 nhóm phê duyệt kép KIPA đã chốt:**
   - 1. `TERMINATE_CONTRACT`: Chấm dứt hợp đồng dịch vụ / rút hồ sơ.
   - 2. `SALARY_CHANGE`: Thay đổi chế độ lương thưởng, định mức nhân sự.
   - 3. `ACCESS_EXCEPTION`: Ngoại lệ quyền truy cập vượt cấp hoặc xem tài liệu tuyệt mật.
   - 4. `DELETE_SENSITIVE_DOCS`: Xóa tài liệu nhạy cảm hoặc scan gốc khỏi Master Vault.
   - 5. `BRANCH_TRANSFER`: Điều chuyển chi nhánh nhân sự / chuyển giao học viên giữa cơ sở.
   - 6. `APPOINT_DISMISS_MANAGER`: Bổ nhiệm hoặc miễn nhiệm vị trí quản lý chi nhánh/phòng ban.
   - 7. `CHANGE_DIRECT_MANAGER`: Thay đổi quản lý trực tiếp của nhân sự.
   - *(Đề xuất chờ duyệt: Nhóm duyệt kép hoàn phí rút hồ sơ trên 20 triệu VNĐ `REFUND_OVER_20M [Chờ duyệt]` được đánh dấu rõ ràng là đề xuất mở rộng).*
5. **Chuẩn hóa 3 chính sách lưu trữ tách biệt:**
   - *Chính sách 1: Lưu trữ Hồ sơ (Dossier Retention)* — Lưu vĩnh viễn hồ sơ với trạng thái `ARCHIVED`. Khóa ngoại `kipa_dossier_documents.dossier_id` dùng `ON DELETE RESTRICT` để bảo toàn lịch sử.
   - *Chính sách 2: Lưu trữ File Vật lý (Physical File Retention)* — File scan lưu tại `kipa-private/storage/documents/` ngoài webroot, lưu theo thời hạn quy định, chỉ xóa khi có phê duyệt kép `DELETE_SENSITIVE_DOCS`.
   - *Chính sách 3: Lưu trữ Nhật ký Kiểm toán (Audit Log Retention)* — `kipa_audit_logs` và `kipa_document_access_logs` lưu vết tối thiểu 3-5 năm, không chạy cron xóa tự động khi chưa có chính sách duyệt bằng văn bản.
6. **Làm rõ quan hệ Lead – Customer – Consultation – Service Dossier:**
   - Giữ mã khách hàng `customer_code` vĩnh viễn khi quay lại làm dịch vụ mới.
   - Một khách hàng có nhiều hồ sơ tư vấn theo các đợt/nhu cầu.
   - Một khách hàng có thể sở hữu nhiều hồ sơ dịch vụ độc lập hoạt động song song.
   - Hồ sơ dịch vụ chỉ được tạo khi Case Officer tiếp nhận bàn giao (`handover_status = 'ACCEPTED'`).
   - Lưu đầy đủ lịch sử bàn giao, từ chối (`REJECTED`) và lý do bổ sung (`handover_notes`).
7. **Khử trùng dữ liệu với Website CMS (`content-schema.sql`):**
   - Hợp nhất `kipa_lead_sources` làm canonical master data cho Website, CMS và CRM.
   - `kipa_consultation_interests` làm danh mục nhu cầu dùng chung.
   - `kipa_consultation_activities` hỗ trợ cả mã hồ sơ tư vấn CRM và yêu cầu tư vấn CMS.
8. **Kiểm soát Phân quyền Quản lý & Tách biệt Quyền Xem/Tải:**
   - Quyền quản lý không mặc nhiên xem toàn bộ hồ sơ; phân cấp theo chi nhánh, phòng ban và cờ `is_sensitive`.
   - Bổ sung `can_download` (BOOLEAN NOT NULL DEFAULT 1) vào `kipa_dossier_documents` và `VIEW kipa_student_documents` để phân tách quyền "Xem trực tuyến" và quyền "Tải file gốc".
9. **Cập nhật Báo cáo Kiểm toán Field Audit:**
   - Chạy script `tools/build_field_audit.py` tạo file `DATABASE_FIELD_AUDIT.xlsx` gồm đầy đủ 139 bảng/view (5 Core + 85 CMS Staged + 42 CRM/Portal + 1 View + 6 LMS).

---

## [Phiên bản 2.2.0] — Ngày 17/11/2026 (HOÀN THIỆN NÂNG CAO & BẢO VỆ TOÀN DIỆN)
### Hoàn thiện Nghiệp vụ Tư vấn, Phân quyền, SLA, Phê duyệt kép, Kho tài liệu & Bảo mật Cổng học viên
- Sử dụng script `tools/verify_schema.py` đếm chính xác: 42 bảng vật lý và 1 SQL View.
- Bổ sung Phân hệ Tiếp nhận Lead & Hồ sơ Tư vấn Riêng (5 bảng mới).
- Hoàn thiện Phân quyền RBAC & Quản lý Tổ chức (`direct_manager_id`, `kipa_role_permissions`, `kipa_staff_role_history`, `kipa_role_delegations`).
- Hoàn thiện Nhiệm vụ và Cam kết SLA (`kipa_sla_policies`, `kipa_working_calendars`, `kipa_tasks`, `kipa_task_collaborators`, `kipa_task_history`).
- Hoàn thiện Quy trình Phê duyệt Nhiều bước & Phê duyệt kép (`kipa_approval_workflows`, `kipa_approval_workflow_steps`, `kipa_approvals`).
- Bảo vệ Kho Tài liệu & An toàn Lưu trữ (`kipa_dossier_documents` ON DELETE RESTRICT, `kipa_document_access_logs`).
- Bảo mật Cổng Học Viên & Kiểm tra View Hợp Nhất (`VIEW kipa_student_documents`).
- Kiểm tra Đối chiếu Thực tế Mã Nguồn (phát hiện lệnh xóa lead 12 tháng tại `api/cleanup.php` dòng 16 và `api/submit.php` dòng 200).

---

## [Phiên bản 2.1.0] — Ngày 17/11/2026
### Vòng Kiểm Tra Cuối Cùng & Chuẩn Hóa Toàn Diện DDL
- Xác định 28 bảng vật lý và 1 SQL View.
- Khắc phục nguy cơ cascade xóa lead 12 tháng bằng `ON DELETE SET NULL`.
- Hợp nhất kho tài liệu qua SQL View `kipa_student_documents`.

---

## [Phiên bản 2.0.0] — Tháng 11/2026
### Khởi tạo Kiến trúc CRM KIPA và Student Portal Thống Nhất
