# KIPA VIỆT HÀN — BÁO CÁO PHÂN TÍCH KHOẢNG CÁCH DỮ LIỆU (DATABASE GAP ANALYSIS)

> **Tài liệu chiến lược dữ liệu:** Đối chiếu toàn diện giữa Hiện trạng Database và Mục tiêu CRM + Student Portal  
> **Phiên bản:** 2.3.0 (Chuẩn hóa Pipeline KIPA, Lịch T2-T7, 7 Nhóm Duyệt Kép, 3 Chính Sách Lưu Trữ & Khử Trùng CMS)  
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
| **2. Đã có nhưng thiếu trường** | Đang chạy nhưng thiếu khóa ngoại liên kết hoặc cờ trạng thái CRM/Portal. | **2 bảng** | `kipa_leads` (thiếu `converted_to_customer_id`, `lead_status`), `kipa_users` (thiếu `avatar_url`, `is_active`, `last_login_at`) | **Mở rộng an toàn.** Bổ sung cột nullable; bảo vệ chống xóa dây chuyền từ `cleanup.php` và `submit.php` bằng `ON DELETE SET NULL`. |
| **3. Chưa có, cần tạo mới** | Bắt buộc phải tạo để CRM và Cổng học viên hoạt động đồng bộ. | **42 bảng + 1 SQL View** | - **Tổ chức & RBAC (10):** `kipa_branches`, `kipa_departments`, `kipa_teams`, `kipa_staff`, `kipa_roles`, `kipa_permissions`, `kipa_role_permissions`, `kipa_staff_roles`, `kipa_staff_role_history`, `kipa_role_delegations`<br>- **Khách hàng (4):** `kipa_customers`, `kipa_customer_addresses`, `kipa_customer_relations`, `kipa_customer_education`<br>- **Tiếp nhận & Tư vấn (5):** `kipa_lead_sources`, `kipa_campaigns`, `kipa_consultation_dossiers`, `kipa_consultation_interests`, `kipa_consultation_activities`<br>- **Hồ sơ dịch vụ (3):** `kipa_service_dossiers`, `kipa_dossier_assignments`, `kipa_dossier_status_history`<br>- **Nhiệm vụ & SLA (5):** `kipa_sla_policies`, `kipa_working_calendars`, `kipa_tasks`, `kipa_task_collaborators`, `kipa_task_history`<br>- **Phê duyệt & Kiểm toán (4):** `kipa_approval_workflows`, `kipa_approval_workflow_steps`, `kipa_approvals`, `kipa_audit_logs`<br>- **Kho tài liệu Master (4):** `kipa_dossier_documents`, `kipa_document_versions`, `kipa_document_reviews`, `kipa_document_access_logs`<br>- **Student Portal (7):** `kipa_students`, `kipa_student_journey`, `kipa_student_schedules`, `kipa_student_notifications`, `kipa_student_materials`, `kipa_student_support`, `kipa_notification_settings`<br>- **View hợp nhất (1):** `VIEW kipa_student_documents` | **Tạo mới bằng DDL chuẩn.** Toàn bộ khóa ngoại dùng `BIGINT UNSIGNED`, `InnoDB`, `utf8mb4_unicode_ci`, thiết lập `ON DELETE SET NULL` cho leads và `ON DELETE RESTRICT` cho kho tài liệu để bảo vệ dữ liệu lịch sử. |
| **4. Đã có trong thiết kế nhưng chưa cần triển khai** | Mô hình dữ liệu mở rộng chuẩn bị sẵn cho tương lai. | **92 bảng** | - 85 bảng CMS nội dung (Nhóm A -> J trong `content-schema.sql`)<br>- 1 bảng tracker CMS `kipa_content_state`<br>- 6 bảng LMS đào tạo (`kipa_lms_classes`, `kipa_lms_lessons`, `kipa_lms_enrollments`, `kipa_lms_attendance`, `kipa_lms_exams`, `kipa_lms_grades`) | **Giữ nguyên thiết kế staging.** Triển khai độc lập theo từng giai đoạn ra mắt CMS và LMS. |
| **5. Cần quyết định nghiệp vụ trước khi tạo** | Các quy chế nội bộ cần sự chuẩn thuận từ Ban Giám Đốc KIPA. | **2 chính sách nghiệp vụ** | - Đề xuất ngưỡng hoàn phí > 20 triệu VNĐ cần duyệt kép (`REFUND_OVER_20M [Chờ duyệt]`)<br>- Thời hạn quy định xóa vật lý file tài liệu theo từng diện visa (3 năm hay 5 năm) | **Tạm thời xử lý qua bảng hiện có:** Dùng cờ duyệt kép `requires_dual_approval` trong `kipa_approval_workflows` và trạng thái `ARCHIVED` trong `kipa_dossier_status_history`. |

---

## III. ĐỐI CHIẾU CHI TIẾT 7 YÊU CẦU NGHIỆP VỤ NÂNG CAO

### 1. Nghiệp vụ Tiếp nhận, Tư vấn & Vòng đời Khách hàng (Lead Intake & Lifecycle)
- **Chuẩn hóa Pipeline tư vấn KIPA đã thống nhất:**
  - Giữ nguyên bộ trạng thái chuẩn: `NEW` (Mới tiếp nhận), `CONTACTED` (Đã liên hệ), `CONSULTING` (Đang tư vấn), `POTENTIAL` (Tiềm năng cao), `HANDOVER_PENDING` (Chờ bàn giao), `WON` (Chốt thành công), `LOST` (Thất bại / Dừng).
  - Tuyệt đối không thay thế bằng các trạng thái ngoại lai chưa duyệt (như `TRIAL_CLASS`, `PROPOSAL_SENT`). Bảng `kipa_consultation_dossiers` cho phép cấu hình workflow riêng theo loại dịch vụ nhưng phải bảo toàn pipeline cốt lõi này.
- **Tách bạch Hồ sơ tư vấn và Hồ sơ dịch vụ:**
  - Một khách hàng quay lại KIPA **giữ nguyên mã khách hàng `customer_code` vĩnh viễn** (Single Source of Customer Identity).
  - Một khách hàng có thể có **nhiều hồ sơ tư vấn** (`kipa_consultation_dossiers`) theo các giai đoạn hoặc các dịch vụ khác nhau mà không làm mất lịch sử.
  - Một khách hàng có thể sở hữu **nhiều hồ sơ dịch vụ độc lập** (`kipa_service_dossiers`) hoạt động song song (VD: 1 hồ sơ du học D2-2 + 1 hồ sơ học tiếng Hàn Topik 4).
  - **Quy trình bàn giao chặt chẽ:** Hồ sơ dịch vụ chỉ được khởi tạo khi bộ phận xử lý hồ sơ (Case Officer) **xác nhận tiếp nhận bàn giao** (`handover_status = 'ACCEPTED'`). Nếu thông tin tư vấn chưa đầy đủ, Case Officer có quyền từ chối (`handover_status = 'REJECTED'`) kèm ghi chú lý do (`handover_notes`) để chuyên viên tư vấn bổ sung.
- **Theo dõi hoạt động tư vấn và Nhu cầu dịch vụ:**
  - `kipa_consultation_activities`: Ghi lại chi tiết cuộc gọi, gặp mặt, tương tác Zalo, kết quả và lịch hẹn tiếp theo (`scheduled_next_at`).
  - `kipa_consultation_interests`: Quản lý danh mục ngành nghề, trường học, quốc gia quan tâm của đợt tư vấn.
  - `lost_reason` và `lost_notes`: Phân tích lý do không tiếp tục (chi phí, trượt phỏng vấn thử, chọn trung tâm khác...) để tối ưu tuyển sinh.
- **Khử trùng dữ liệu với Website CMS (`content-schema.sql`):**
  - `kipa_lead_sources`: Master catalog danh mục nguồn tiếp nhận (Facebook Ads, Zalo, Website, Hotline, Giới thiệu), dùng chung cho cả Website Form, CMS và CRM.
  - `kipa_consultation_interests`: Master catalog phân loại nhu cầu quan tâm, tương thích với Group G của CMS.
  - `kipa_consultation_activities`: Cấu trúc mở hỗ trợ liên kết cả `consultation_dossier_id` (CRM) và `consultation_request_id` (CMS).

### 2. Hoàn thiện Cơ chế Phân quyền (Advanced RBAC & Governance)
- **Ma trận vai trò và quyền hạn chi tiết:**
  - Bảng `kipa_roles`, `kipa_permissions` và bảng quan hệ nhiều-nhiều `kipa_role_permissions`.
  - Phân tách tường minh quyền hạn chi tiết (VD: `view_leads`, `edit_dossier`, `review_document`, `approve_visa`, `view_sensitive_financials`).
- **Phân cấp Quản lý và Giới hạn phạm vi (Scope Limitation):**
  - Trường `direct_manager_id` trong `kipa_staff`: Xác định chính xác một cấp trên trực tiếp quản lý cán bộ nhân sự.
  - **Quyền quản lý không mặc nhiên được xem toàn bộ hồ sơ:**
    + Trưởng chi nhánh chỉ được xem hồ sơ thuộc phạm vi chi nhánh của mình (`branch_id`).
    + Trưởng phòng ban chỉ được xem hồ sơ thuộc phòng ban chuyên môn (`dept_id`).
    + Các hồ sơ có tài liệu tài chính / pháp lý đặc thù (`is_sensitive = 1`) chỉ nhân sự có quyền riêng biệt `view_sensitive_financials` mới được xem, không áp dụng quyền thừa kế tự động.
- **Chuyển phòng ban / chi nhánh & Lịch sử phân quyền:**
  - Cấp quyền có thời hạn thông qua trường `expires_at` trong `kipa_staff_roles`.
  - Bảng `kipa_staff_role_history`: Ghi lại toàn bộ lịch sử cấp quyền (`GRANT`), sửa quyền (`MODIFY`), thu hồi quyền (`REVOKE`) khi nhân sự luân chuyển chi nhánh hoặc điều chuyển công tác.
- **Quyền tạm thời và Ủy quyền:**
  - Bảng `kipa_role_delegations`: Cho phép ủy quyền trong kỳ nghỉ phép, có ngày bắt đầu (`start_date`), ngày kết thúc (`end_date`), lý do và phê duyệt của Ban Giám Đốc.

### 3. Hoàn thiện Nhiệm vụ và SLA (Tasks & Service Level Agreements)
- **SLA theo dịch vụ và chi nhánh:**
  - Bảng `kipa_sla_policies`: Định nghĩa thời hạn phản hồi (`response_time_hours`) và xử lý (`resolution_time_hours`) riêng cho từng loại dịch vụ (Du học D2, D4, E-7, E-8) và chi nhánh.
- **Lịch làm việc mặc định chuẩn KIPA đã chốt:**
  - **Thứ 2 đến Thứ 6:** 08:00 – 17:00 (Thời gian nghỉ trưa: 12:00 – 13:30).
  - **Thứ 7:** 08:00 – 12:00 (Buổi chiều nghỉ).
  - **Chủ nhật:** Nghỉ toàn bộ.
  - Bảng `kipa_working_calendars`: Cho phép cấu hình riêng lịch nghỉ lễ, ngày làm bù theo từng chi nhánh. Đồng hồ SLA chỉ đếm giờ trong khung giờ làm việc thực tế, không tính giờ nghỉ và ngày nghỉ.
- **Cơ chế Tạm dừng và Tiếp tục SLA:**
  - Trường `sla_paused_at`, `sla_pause_reason`, `sla_total_paused_minutes` trong `kipa_tasks`. Khi chờ học viên bổ sung giấy tờ từ địa phương hoặc chờ kết quả từ Cục Xuất nhập cảnh Hàn Quốc, chuyên viên được bấm tạm dừng SLA để không bị tính trễ hạn oan.
- **Phân công và Cộng tác nhiệm vụ:**
  - Hỗ trợ nhiệm vụ con nhiều cấp qua trường `parent_id` (Self-referencing).
  - Tách bạch Người giao việc (`created_by`), Người thực hiện chính (`assigned_to`), và Người phối hợp (`kipa_task_collaborators`).
  - Quy trình xác nhận hoàn thành: Phải có chữ ký duyệt hoàn thành từ quản lý (`completion_confirmed_by`, `completion_confirmed_at`).
  - Bảng `kipa_task_history`: Lưu vết toàn bộ thay đổi hạn xử lý (gia hạn deadline), trạng thái và lý do thay đổi.
  - Quy tắc leo thang tự động: `escalated_to`, `escalation_level` tự động báo động lên Trưởng phòng khi quá hạn xử lý.

### 4. Quy trình Phê duyệt nhiều cấp & Khôi phục Đầy đủ 7 Nhóm Phê duyệt Kép
- **Quy trình nhiều bước linh hoạt theo phiên bản:**
  - Bảng `kipa_approval_workflows` & `kipa_approval_workflow_steps`: Định nghĩa các bước duyệt theo chuỗi (VD: Bước 1 Chuyên viên -> Bước 2 Trưởng phòng -> Bước 3 Giám đốc chi nhánh -> Bước 4 Kế toán trưởng).
  - Quản lý phiên bản quy trình qua trường `version`.
- **Khôi phục đầy đủ 7 nhóm phê duyệt kép KIPA đã thống nhất:**
  1. `TERMINATE_CONTRACT`: Chấm dứt hợp đồng dịch vụ / rút hồ sơ.
  2. `SALARY_CHANGE`: Thay đổi chế độ lương thưởng, định mức nhân sự.
  3. `ACCESS_EXCEPTION`: Cấp ngoại lệ quyền truy cập vượt cấp hoặc xem tài liệu tuyệt mật.
  4. `DELETE_SENSITIVE_DOCS`: Xóa tài liệu nhạy cảm hoặc scan gốc khỏi Master Vault.
  5. `BRANCH_TRANSFER`: Điều chuyển chi nhánh đối với nhân sự hoặc chuyển giao học viên giữa các cơ sở.
  6. `APPOINT_DISMISS_MANAGER`: Bổ nhiệm hoặc miễn nhiệm vị trí quản lý chi nhánh/phòng ban.
  7. `CHANGE_DIRECT_MANAGER`: Thay đổi quản lý trực tiếp của nhân viên.
  - *Đề xuất bổ sung:* Nhóm duyệt kép hoàn phí rút hồ sơ trên 20 triệu VNĐ (`REFUND_OVER_20M [Chờ duyệt]`) được ghi nhận là phương án đề xuất mở rộng, đang chờ Ban Giám Đốc ký ban hành quy chế tài chính.
- **Lưu cấu hình tại thời điểm gửi duyệt (Snapshot Pattern):**
  - Trường `snapshot_payload` (JSON) trong `kipa_approvals`: Đóng băng toàn bộ nội dung yêu cầu, số liệu tại thời điểm gửi. Cấp trên đối chiếu trực tiếp trên snapshot để chống việc sửa đổi lén dữ liệu ngầm sau khi trình duyệt.
- **Nguyên tắc chống tự duyệt (Separation of Duties):**
  - Ràng buộc toàn vẹn cơ sở dữ liệu: `CONSTRAINT chk_kipa_app_no_self CHECK (requested_by != approver_id)`. Người tạo yêu cầu tuyệt đối không được tự duyệt cho chính mình.
- **Trạng thái phê duyệt toàn diện:**
  - Hỗ trợ: `PENDING`, `APPROVED`, `REJECTED`, `RETURNED_FOR_ADDITIONAL` (Trả về yêu cầu bổ sung thông tin), `CANCELLED` (Người gửi tự hủy), `RESUBMITTED` (Đã trình lại).

### 5. Chuẩn hóa 3 Chính sách Lưu trữ & Kho Tài liệu Master Vault
- **Tách bạch 3 chính sách lưu trữ độc lập theo chuẩn quản trị:**
  1. **Chính sách Lưu trữ Hồ sơ (Dossier Retention):** Hồ sơ dịch vụ và hồ sơ tư vấn khi đóng hoặc thanh lý được chuyển sang trạng thái `ARCHIVED`. Khóa ngoại `dossier_id` trong `kipa_dossier_documents` thiết lập **`ON DELETE RESTRICT`** để bảo toàn 100% hồ sơ học viên, không cho phép xóa vật lý cascade.
  2. **Chính sách Lưu trữ File Vật lý (Physical File Retention):** File scan/PDF lưu trữ tại thư mục bảo vệ ngoài webroot `kipa-private/storage/`. Lưu trữ theo thời hạn quy định (VD: 3-5 năm sau tốt nghiệp/xuất cảnh). Tuyệt đối không xóa file tự động; việc xóa file nhạy cảm chỉ được thực hiện khi có phê duyệt kép (`DELETE_SENSITIVE_DOCS`).
  3. **Chính sách Lưu trữ Nhật ký Kiểm toán (Audit Log Retention):** Toàn bộ nhật ký tại `kipa_audit_logs` và `kipa_document_access_logs` được lưu vết tối thiểu 3 đến 5 năm để phục vụ điều tra tuân thủ. Tuyệt đối không thiết lập cron tự động xóa khi chưa có chính sách bằng văn bản được phê duyệt.
- **Tách biệt quyền Xem và quyền Tải tài liệu:**
  - Bổ sung trường `can_download` (BOOLEAN NOT NULL DEFAULT 1) vào `kipa_dossier_documents` và `VIEW kipa_student_documents`.
  - Giúp quản lý cấu hình: Học viên hoặc nhân viên cấp dưới có thể chỉ được "Xem trực tuyến qua PDF Viewer an toàn" (`can_download = 0`) nhưng không được tải file scan gốc về máy tính cá nhân.
- **Nhật ký truy cập tài liệu:**
  - Bảng `kipa_document_access_logs`: Lưu vết ai đã xem, tải xuống (`DOWNLOAD`) hoặc in ấn tài liệu nhạy cảm, từ địa chỉ IP nào và vào thời gian nào.

### 6. Bảo mật Cổng Học Viên & Zero-Trust Architecture
- **Nguyên tắc không tin tưởng dữ liệu Client (Zero-Trust Client Input):**
  - Client gửi lên `customer_id`, `student_email` hay `dossier_id` **TUYỆT ĐỐI KHÔNG ĐƯỢC DÙNG LÀM CĂN CỨ TRUY VẤN DUY NHẤT**.
  - Server PHP luôn bắt buộc đọc danh tính từ Session máy chủ:
    `$userId = (int)($_SESSION['user_id'] ?? 0);`
  - Sau đó xác định quyền sở hữu qua quan hệ: `kipa_customers.user_id = :userId`. Nếu yêu cầu xem hồ sơ không thuộc quyền sở hữu của user đang đăng nhập -> Chặn đứng bằng `403 Forbidden`.
- **Rà soát an toàn của `VIEW kipa_student_documents`:**
  - Định nghĩa View tích hợp đầy đủ `user_id`, `customer_id`, `dossier_id`, `can_download`:
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
        d.can_download AS can_download,
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
