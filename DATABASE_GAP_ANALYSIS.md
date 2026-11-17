# KIPA VIỆT HÀN — BÁO CÁO PHÂN TÍCH KHOẢNG CÁCH DỮ LIỆU (DATABASE GAP ANALYSIS)

> **Tài liệu chiến lược dữ liệu:** Đối chiếu toàn diện giữa Hiện trạng Database và Mục tiêu CRM + Student Portal  
> **Phiên bản:** 2.2.0 (Vòng kiểm tra & Đối chiếu kỹ thuật toàn diện)  
> **Tác giả:** Senior Database Architect & MySQL Engineer  
> **Dự án:** Hệ sinh thái số KIPA Việt Hàn (Website - CRM - Student Portal - LMS)  
> **Cam kết:** **CHƯA THỰC THI SQL TRÊN PRODUCTION.** Toàn bộ nội dung là kiểm toán kỹ thuật và chuẩn bị phương án trình duyệt.

---

## I. MỤC TIÊU VÀ PHƯƠNG PHÁP ĐỐI CHIẾU

Báo cáo này đối chiếu chi tiết giữa hiện trạng cơ sở dữ liệu trên Shared Hosting cPanel và yêu cầu nghiệp vụ chuyên sâu của hệ thống **KIPA CRM** và **KIPA Student Portal**.

Toàn bộ hệ thống dữ liệu được phân loại chuẩn xác vào **5 nhóm trạng thái**:
1. **Đã có và đủ:** Đáp ứng 100% nghiệp vụ hiện tại của Website -> **Bảo toàn nguyên trạng, không can thiệp**.
2. **Đã có nhưng thiếu trường:** Đã hoạt động nhưng thiếu trường liên kết sang CRM/Portal -> **Mở rộng an toàn bằng cột NULLABLE**.
3. **Chưa có, cần tạo mới:** Bắt buộc phải có để CRM và Cổng học viên vận hành thống nhất -> **Tạo mới bằng DDL chuẩn trong `crm_portal_schema.sql` (42 bảng vật lý + 1 View)**.
4. **Đã có trong thiết kế nhưng chưa cần triển khai:** 85 bảng CMS nội dung + 1 tracker + 6 bảng LMS -> **Tạm hoãn, kích hoạt khi đưa CMS/LMS vào khai thác**.
5. **Cần quyết định nghiệp vụ trước khi tạo:** Các chính sách quản trị của Ban Giám Đốc cần thống nhất quy chế trước khi đóng khung bảng dữ liệu.

---

## II. BẢNG TỔNG HỢP PHÂN LOẠI 5 NHÓM ĐỐI CHIẾU

| Nhóm Phân Loại | Ý Nghĩa Nghiệp Vụ | Số Lượng Bảng / View | Danh Mục Bảng Cụ Thể | Định Hướng Xử Lý Kỹ Thuật |
|---|---|:---:|---|---|
| **1. Đã có và đủ** | Đang chạy ổn định trên Production, phục vụ an toàn cho Website KIPA. | **3 bảng** | `kipa_rate_limits`, `kipa_otp_codes`, `kipa_admins` | **Bảo toàn tuyệt đối.** Không đổi tên, không sửa kiểu dữ liệu, không xóa bảng. |
| **2. Đã có nhưng thiếu trường** | Đang chạy nhưng thiếu khóa ngoại liên kết hoặc cờ trạng thái CRM/Portal. | **2 bảng** | `kipa_leads` (thiếu `converted_to_customer_id`, `lead_status`), `kipa_users` (thiếu `avatar_url`, `is_active`, `last_login_at`) | **Mở rộng an toàn.** Bổ sung cột nullable; bảo vệ chống xóa dây chuyền từ `cleanup.php` và `submit.php`. |
| **3. Chưa có, cần tạo mới** | Bắt buộc phải tạo để CRM và Cổng học viên hoạt động đồng bộ. | **42 bảng + 1 SQL View** | - **Tổ chức & RBAC (10):** `kipa_branches`, `kipa_departments`, `kipa_teams`, `kipa_staff`, `kipa_roles`, `kipa_permissions`, `kipa_role_permissions`, `kipa_staff_roles`, `kipa_staff_role_history`, `kipa_role_delegations`<br>- **Khách hàng (4):** `kipa_customers`, `kipa_customer_addresses`, `kipa_customer_relations`, `kipa_customer_education`<br>- **Tiếp nhận & Tư vấn (5):** `kipa_lead_sources`, `kipa_campaigns`, `kipa_consultation_dossiers`, `kipa_consultation_interests`, `kipa_consultation_activities`<br>- **Hồ sơ dịch vụ (3):** `kipa_service_dossiers`, `kipa_dossier_assignments`, `kipa_dossier_status_history`<br>- **Nhiệm vụ & SLA (5):** `kipa_sla_policies`, `kipa_working_calendars`, `kipa_tasks`, `kipa_task_collaborators`, `kipa_task_history`<br>- **Phê duyệt & Kiểm toán (4):** `kipa_approval_workflows`, `kipa_approval_workflow_steps`, `kipa_approvals`, `kipa_audit_logs`<br>- **Kho tài liệu Master (4):** `kipa_dossier_documents`, `kipa_document_versions`, `kipa_document_reviews`, `kipa_document_access_logs`<br>- **Student Portal (7):** `kipa_students`, `kipa_student_journey`, `kipa_student_schedules`, `kipa_student_notifications`, `kipa_student_materials`, `kipa_student_support`, `kipa_notification_settings`<br>- **View hợp nhất (1):** `VIEW kipa_student_documents` | **Tạo mới bằng DDL chuẩn.** Toàn bộ khóa ngoại dùng `BIGINT UNSIGNED`, `InnoDB`, `utf8mb4_unicode_ci`, thiết lập `ON DELETE SET NULL` cho leads và `ON DELETE RESTRICT` cho kho tài liệu để bảo vệ dữ liệu lịch sử. |
| **4. Đã có trong thiết kế nhưng chưa cần triển khai** | Mô hình dữ liệu mở rộng chuẩn bị sẵn cho tương lai. | **92 bảng** | - 85 bảng CMS nội dung (Nhóm A -> J trong `content-schema.sql`)<br>- 1 bảng tracker CMS `kipa_content_state`<br>- 6 bảng LMS đào tạo (`kipa_lms_classes`, `kipa_lms_lessons`, `kipa_lms_enrollments`, `kipa_lms_attendance`, `kipa_lms_exams`, `kipa_lms_grades`) | **Giữ nguyên thiết kế staging.** Triển khai độc lập theo từng giai đoạn ra mắt CMS và LMS. |
| **5. Cần quyết định nghiệp vụ trước khi tạo** | Các quy chế nội bộ cần sự chuẩn thuận từ Ban Giám Đốc KIPA. | **2 chính sách nghiệp vụ** | - Tính hoa hồng CTV theo đợt (`kipa_commissions`)<br>- Quy chế xóa vĩnh viễn dữ liệu quá hạn > 5 năm (`kipa_data_purging_policy`) | **Tạm thời xử lý qua bảng hiện có:** Dùng `collaborator_id` trong `kipa_service_dossiers` và trạng thái `ARCHIVED` trong `kipa_dossier_status_history`. |

---

## III. ĐỐI CHIẾU CHI TIẾT 7 YÊU CẦU NGHIỆP VỤ NÂNG CAO

### 1. Nghiệp vụ Tiếp nhận & Tư vấn CRM (Lead Intake & Consultation)
- **Tách bạch Hồ sơ tư vấn và Hồ sơ dịch vụ:**
  - Không gộp chung làm một vì một khách hàng có thể có nhiều đợt tư vấn (ví dụ: đợt 1 năm 2024 tư vấn du học Úc bị trượt hoặc dừng; đợt 2 năm 2026 tư vấn du học Hàn Quốc và chốt hợp đồng). Nếu gộp chung sẽ làm mất toàn bộ lịch sử tư vấn thất bại trước đó.
  - Bảng `kipa_consultation_dossiers`: Lưu trữ từng đợt tư vấn với mã `consultation_code`, khách hàng, nguồn tiếp nhận, chiến dịch tuyển sinh, chuyên viên phụ trách.
- **Theo dõi hoạt động tư vấn:**
  - Bảng `kipa_consultation_activities`: Ghi lại chi tiết từng cuộc gọi, cuộc gặp tại văn phòng KIPA, tương tác qua Zalo/Email, kết quả cuộc gọi và hẹn lịch liên hệ tiếp theo (`scheduled_next_at`).
- **Nhu cầu dịch vụ:**
  - Bảng `kipa_consultation_interests`: Một đợt tư vấn có thể quan tâm nhiều quốc gia, nhiều ngành hoặc nhiều trường khác nhau trước khi chốt phương án.
- **Lý do khách hàng không tiếp tục (Lost Analysis):**
  - Trường `lost_reason` và `lost_notes` trong `kipa_consultation_dossiers` phân loại rõ lý do: `Chi phí cao`, `Gia đình phản đối`, `Học bạ GPA thấp`, `Chọn trung tâm khác`, `Không liên lạc được`... phục vụ báo cáo tỷ lệ chuyển đổi cho Ban Giám Đốc.
- **Quy trình bàn giao từ Tư vấn sang Xử lý hồ sơ:**
  - Trường `handover_status` (`NONE` $\rightarrow$ `PENDING_HANDOVER` $\rightarrow$ `HANDED_OVER`), `handed_over_by`, `handed_over_at`, `accepted_by`, `accepted_at` và `created_service_dossier_id`. Khi tư vấn chốt hợp đồng và thu cọc, hồ sơ dịch vụ chính thức `kipa_service_dossiers` mới được khởi tạo và bàn giao cho Chuyên viên hồ sơ thụ lý.

### 2. Hoàn thiện Cơ chế Phân quyền (Advanced RBAC & Governance)
- **Ma trận Vai trò - Quyền hạn cụ thể:**
  - Bảng `kipa_role_permissions`: Xác định rõ ràng từng vai trò có những quyền thao tác nào, loại bỏ việc hardcode quyền trong code.
  - Cờ `is_sensitive` trong `kipa_permissions`: Đánh dấu các quyền truy cập dữ liệu nhạy cảm (xem số dư tài khoản phụ huynh, xem CCCD, xuất dữ liệu ra Excel).
- **Phân quyền theo phạm vi (Scope-based Access Control):**
  - Trường `scope` trong `kipa_staff_roles`: Hỗ trợ `GLOBAL` (Toàn quốc), `BRANCH` (Chỉ xem dữ liệu chi nhánh mình), `DEPARTMENT` (Phòng ban), `TEAM` (Nhóm), `ASSIGNED_ONLY` (Chỉ xem hồ sơ được giao trực tiếp cho mình).
- **Quản lý một Quản lý trực tiếp chính:**
  - Bổ sung trường `direct_manager_id` vào `kipa_staff` (Foreign Key đệ quy trỏ đến `kipa_staff.id`). Đảm bảo mỗi nhân sự luôn có đúng một cấp trên trực tiếp ký duyệt, giao việc và đánh giá SLA.
- **Thu hồi quyền khi chuyển phòng ban:**
  - Bảng `kipa_staff_role_history`: Ghi lại mọi hành động cấp, sửa, thu hồi quyền kèm lý do và người thực hiện. Khi nhân viên luân chuyển phòng ban, hệ thống tự động thu hồi vai trò cũ và ghi log kiểm toán.
- **Ủy quyền vai trò tạm thời:**
  - Bảng `kipa_role_delegations`: Hỗ trợ ủy quyền khi nhân sự nghỉ phép/công tác từ ngày đến ngày, có trường `delegated_scope` và bắt buộc phải có cấp trên phê duyệt (`approved_by`) mới có hiệu lực.
- **Quy trình Phê duyệt kép (Dual Authorization / Four-Eyes Principle):**
  - Bắt buộc 2 nhân sự quản lý độc lập ký duyệt đối với các quyết định nhạy cảm: Miễn giảm học phí > 20 triệu, Hủy hợp đồng hoàn cọc, Cấp quyền Quản trị viên (`ADMIN`).

### 3. Hoàn thiện Nhiệm vụ và SLA (Tasks & Service Level Agreements)
- **SLA theo Dịch vụ và Chi nhánh:**
  - Bảng `kipa_sla_policies`: Định nghĩa thời gian phản hồi (`response_time_hours`) và xử lý (`resolution_time_hours`) theo từng mức ưu tiên (`URGENT`, `HIGH`, `MEDIUM`, `LOW`) và chi nhánh.
- **Lịch làm việc và ngày nghỉ lễ:**
  - Bảng `kipa_working_calendars`: Khung giờ hành chính (8h - 17h30, trừ giờ ăn trưa 12h - 13h30), ngày nghỉ cuối tuần và ngày lễ quốc gia (Việt Nam, Hàn Quốc, Úc). Đồng hồ đếm SLA chỉ chạy trong giờ làm việc thực tế.
- **Tạm dừng và tiếp tục SLA:**
  - Các trường `sla_status` (`RUNNING`, `PAUSED`, `BREACHED`, `COMPLETED_ON_TIME`), `sla_paused_at`, `sla_pause_reason`, `sla_total_paused_minutes`. Khi chờ học viên nộp bổ sung giấy tờ, chuyên viên có thể tạm dừng đồng hồ SLA.
- **Đa vai trò trong nhiệm vụ:**
  - Người giao việc: `created_by`. Người chịu trách nhiệm chính: `assigned_to`. Người phối hợp: Bảng `kipa_task_collaborators`.
- **Nhiệm vụ con và nhiệm vụ lặp lại:**
  - Hỗ trợ `parent_id` (nhiệm vụ con) và `is_recurring`, `recurrence_pattern` (nhiệm vụ lặp lại hàng ngày/tuần/tháng).
- **Xác nhận hoàn thành:**
  - `completion_confirmed_by`, `completion_confirmed_at`, `completion_rating` (Người giao việc kiểm tra và nghiệm thu kết quả).
- **Lịch sử thay đổi nhiệm vụ:**
  - Bảng `kipa_task_history`: Ghi nhận mọi thay đổi người làm, gia hạn deadline kèm lý do.
- **Nhắc việc và chuyển cấp:**
  - `reminder_at`, `escalated_to`, `escalation_level` (Tự động leo thang cảnh báo lên Trưởng nhóm/Giám đốc khi quá hạn).

### 4. Hoàn thiện Quy trình Phê duyệt (Approval Workflows)
- **Quy trình nhiều bước linh hoạt theo loại yêu cầu:**
  - Bảng `kipa_approval_workflows` & `kipa_approval_workflow_steps`: Định nghĩa các bước duyệt (Bước 1 Trưởng nhóm $\rightarrow$ Bước 2 Giám đốc chi nhánh $\rightarrow$ Bước 3 Kế toán trưởng).
  - Quản lý phiên bản quy trình qua trường `version` và cờ `requires_dual_approval`.
- **Lưu cấu hình tại thời điểm gửi duyệt (Snapshot Pattern):**
  - Trường `snapshot_payload` (JSON) trong `kipa_approvals`: Đóng băng toàn bộ dữ liệu hợp đồng, số tiền, điều khoản tại giây phút gửi duyệt. Cấp trên đối chiếu trực tiếp với snapshot, chống sửa lén dữ liệu ngầm.
- **Nguyên tắc chống tự duyệt (Separation of Duties):**
  - Ràng buộc: `CONSTRAINT chk_kipa_app_no_self CHECK (requested_by != approver_id)`. Người tạo yêu cầu tuyệt đối không được tự duyệt cho chính mình.
- **Xử lý trạng thái duyệt đầy đủ:**
  - Hỗ trợ các trạng thái: `PENDING`, `APPROVED`, `REJECTED`, `RETURNED_FOR_ADDITIONAL` (Trả lại yêu cầu bổ sung), `CANCELLED` (Người gửi tự hủy), `RESUBMITTED` (Đã gửi lại).

### 5. Bảo vệ Kho Tài liệu & An toàn Lưu trữ (Document Vault Security)
- **Bảo vệ tài liệu lịch sử khi hồ sơ Đóng hoặc Lưu trữ:**
  - Trong `kipa_dossier_documents`, khóa ngoại `dossier_id` được đổi từ `ON DELETE CASCADE` thành **`ON DELETE RESTRICT`**.
  - Ngăn chặn hoàn toàn việc xóa hồ sơ làm mất dấu chứng chỉ, bằng cấp và visa đã cấp. Khi hồ sơ chuyển trạng thái `status = 'CLOSED'` hay `'ARCHIVED'`, toàn bộ phiên bản file (`kipa_document_versions`) và lịch sử thẩm định (`kipa_document_reviews`) vẫn được bảo tồn vĩnh viễn.
- **Phân biệt rõ 3 mức độ vòng đời dữ liệu:**
  1. *Lưu trữ (`ARCHIVED`):* Giữ nguyên file, ẩn khỏi màn hình hoạt động, chỉ quyền Audit/Director mới mở lại được.
  2. *Hủy hồ sơ (`CANCELLED`):* Giữ biên bản thanh lý và tài liệu thẩm định trong thời hạn pháp lý (retention period 12-36 tháng).
  3. *Xóa vĩnh viễn (`PURGED`):* Chỉ thực hiện khi có yêu cầu tuân thủ quyền được quên lãng theo Nghị định 13/2023/NĐ-CP và phải có phê duyệt kép của Ban Giám Đốc.
- **Bảo mật đường dẫn vật lý (Protected Storage):**
  - **TUYỆT ĐỐI KHÔNG DÙNG ĐƯỜNG DẪN CÔNG KHAI `public_html/assets/...` CHO TÀI LIỆU NHẠY CẢM.**
  - File CCCD, học bạ, sổ tiết kiệm lưu tại thư mục bảo vệ nằm ngoài webroot: `kipa-private/storage/documents/{dossier_code}/{hash}.dat`.
  - Chỉ được tải qua endpoint `GET /api/document.php?id={version_id}` có xác thực phiên đăng nhập và kiểm tra quyền sở hữu.
- **Nhật ký truy cập tài liệu:**
  - Bảng `kipa_document_access_logs`: Lưu vết ai đã xem, tải xuống (`DOWNLOAD`) hoặc chia sẻ tài liệu nhạy cảm, từ địa chỉ IP nào và vào thời gian nào.

### 6. Bảo mật Cổng Học Viên & Kiểm tra View Hợp Nhất
- **Nguyên tắc không tin tưởng dữ liệu Client (Zero-Trust Client Input):**
  - Client gửi lên `customer_id`, `student_email` hay `dossier_id` **TUYỆT ĐỐI KHÔNG ĐƯỢC DÙNG LÀM CĂN CỨ TRUY VẤN DUY NHẤT**.
  - Server PHP luôn bắt buộc đọc danh tính từ Session máy chủ:
    `$userId = (int)($_SESSION['user_id'] ?? 0);`
  - Sau đó xác định quyền sở hữu qua quan hệ: `kipa_customers.user_id = :userId`. Nếu yêu cầu xem hồ sơ không thuộc quyền sở hữu của user đang đăng nhập $\rightarrow$ Chặn đứng bằng `403 Forbidden`.
- **Rà soát an toàn của `VIEW kipa_student_documents`:**
  - Nâng cấp định nghĩa View để đưa thêm `user_id`, `customer_id`, `dossier_id` vào View:
    ```sql
    CREATE OR REPLACE VIEW `kipa_student_documents` AS
    SELECT 
        d.id AS id,
        d.dossier_id AS dossier_id,
        d.customer_id AS customer_id,
        c.user_id AS user_id,
        c.email AS student_email,
        d.doc_group AS doc_group,
        d.doc_code AS doc_code,
        d.doc_name AS doc_name,
        d.status AS status,
        COALESCE(v.file_url, '') AS file_url,
        COALESCE(v.file_name, '') AS file_name,
        COALESCE(v.file_size, '') AS file_size,
        COALESCE(DATE_FORMAT(v.uploaded_at, '%Y-%m-%d %H:%i'), '') AS uploaded_at,
        d.notes AS note,
        d.updated_at AS updated_at
    FROM `kipa_dossier_documents` d
    JOIN `kipa_customers` c ON d.customer_id = c.id
    LEFT JOIN `kipa_document_versions` v ON d.current_version_id = v.id;
    ```
  - Khi API Cổng học viên truy vấn tài liệu, bắt buộc lọc theo:
    `WHERE user_id = :session_user_id`
  - Loại bỏ hoàn toàn lỗ hổng lộ tài liệu chéo (IDOR vulnerability), không dựa vào email làm cơ chế bảo mật duy nhất.

---

## IV. ĐỐI CHIẾU THỰC TẾ MÃ NGUỒN WEBSITE (CODEBASE AUDIT)

Đã đọc và phân tích trực tiếp 5 file mã nguồn PHP cốt lõi của Website KIPA:

### 1. `api/submit.php` (Tiếp nhận form tư vấn web)
- **Hiện trạng kiểm tra:**
  - Dòng 200: Có câu lệnh dọn dẹp lead cũ: `$db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH')`.
  - Dòng 208-221: Câu lệnh `INSERT INTO kipa_leads (form_type, contact_name, phone, email, program, source_page, details_json, consented_at, created_at) VALUES (...)`.
- **Đánh giá tương thích:**
  - Việc thêm cột nullable `converted_to_customer_id` và `lead_status` (DEFAULT 'NEW') vào `kipa_leads` **hoàn toàn tương thích** với câu lệnh `INSERT` cũ vì các cột mới tự động nhận giá trị mặc định.
- **Phát hiện quan trọng:**
  - Lệnh xóa dòng 200 trong `api/submit.php` chạy trên mỗi lần nộp form. Nếu không cấu hình `ON DELETE SET NULL` thì chính lệnh nộp form của khách hàng mới sẽ xóa mất khách hàng cũ trong CRM!
  - **Đề xuất sửa đổi:** Sửa điều kiện dòng 200 tương tự `cleanup.php` để chỉ xóa lead chưa chuyển đổi:
    `WHERE (converted_to_customer_id IS NULL OR converted_to_customer_id = 0) AND created_at < ...`

### 2. `api/account.php` (Cổng thông tin tài khoản học viên)
- **Hiện trạng kiểm tra:**
  - Dòng 39-42: Kiểm tra phiên đăng nhập nghiêm ngặt:
    ```php
    kipa_user_session();
    if (!isset($_SESSION['user_id'])) {
        kipa_json_response(401, ['error' => 'Bạn cần đăng nhập để xem hồ sơ.']);
    }
    ```
  - Dòng 58-60: Truy vấn trực tiếp từ session:
    `SELECT id, email, password_hash, full_name, phone, created_at FROM kipa_users WHERE id = :id`
    với `['id' => (int)$_SESSION['user_id']]`.
- **Đánh giá tương thích:**
  - Cực kỳ an toàn, đã tuân thủ chuẩn kiểm tra quyền trên server. Hoàn toàn tương thích với bảng `kipa_users` và liên kết 1-1 với `kipa_customers.user_id`.

### 3. `api/cleanup.php` (Tự động dọn dẹp hằng đêm qua Cron)
- **TÌNH TRẠNG THỰC TẾ: FILE CHƯA ĐƯỢC CHỈNH SỬA TRÊN MÃ NGUỒN.**
  - Dòng 16 của file vẫn đang là:
    `$leads = $db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');`
  - **XÁC NHẬN:** Đây là **ĐỀ XUẤT THAY ĐỔI CẦN THỰC HIỆN** khi triển khai CRM trên production, chưa được sửa đổi ngầm để đảm bảo nguyên tắc bảo toàn mã nguồn.

### 4. `api/bootstrap.php` (Khởi tạo kết nối PDO & cấu hình)
- **Hiện trạng kiểm tra:**
  - Dòng 20: Tải cấu hình an toàn từ `kipa-private/config.php` nằm ngoài thư mục webroot.
  - Dòng 41-48: Hàm `kipa_db()` khởi tạo PDO với `ATTR_EMULATE_PREPARES => false`, `ERRMODE_EXCEPTION`.
- **Đánh giá tương thích:**
  - Cả Website, CRM và Student Portal đều có thể dùng chung hàm `kipa_db(kipa_config())` để tối ưu kết nối và chia sẻ connection pool trên cPanel.

### 5. `api/admin.php` (Giao diện quản trị hệ thống kỹ thuật)
- **Hiện trạng kiểm tra:**
  - Dòng 13, 88-100: Sử dụng session riêng `kipa_admin`, kiểm tra rate limit đăng nhập, lưu tài khoản tại `kipa_admins`.
- **Đánh giá tương thích:**
  - Bảng `kipa_admins` giữ nguyên 100%, liên kết sang cán bộ nhân sự CRM qua trường `kipa_staff.admin_id`.
