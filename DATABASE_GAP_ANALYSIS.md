# KIPA VIỆT HÀN — BÁO CÁO PHÂN TÍCH KHOẢNG CÁCH DỮ LIỆU (DATABASE GAP ANALYSIS)

> **Tài liệu chiến lược dữ liệu:** Đối chiếu toàn diện giữa Hiện trạng Database và Mục tiêu CRM + Student Portal  
> **Phiên bản:** 2.1.0 (Vòng kiểm tra & Đối chiếu kỹ thuật cuối cùng)  
> **Tác giả:** Senior Database Architect & MySQL Engineer  
> **Dự án:** Hệ sinh thái số KIPA Việt Hàn (Website - CRM - Student Portal - LMS)  

---

## I. MỤC TIÊU VÀ PHƯƠNG PHÁP ĐỐI CHIẾU

Báo cáo này đối chiếu chi tiết giữa hiện trạng cơ sở dữ liệu trên Shared Hosting cPanel và yêu cầu nghiệp vụ của hệ thống **KIPA CRM** và **KIPA Student Portal**.

Toàn bộ hệ thống dữ liệu được phân loại chuẩn xác vào **5 nhóm trạng thái**:
1. **Đã có và đủ:** Đáp ứng 100% nghiệp vụ hiện tại của Website -> **Bảo toàn nguyên trạng, không can thiệp**.
2. **Đã có nhưng thiếu trường:** Đã hoạt động nhưng thiếu trường liên kết sang CRM/Portal -> **Mở rộng an toàn bằng cột NULLABLE**.
3. **Chưa có, cần tạo mới:** Bắt buộc phải có để CRM và Cổng học viên vận hành thống nhất -> **Tạo mới bằng DDL chuẩn trong `crm_portal_schema.sql` (28 bảng vật lý + 1 View)**.
4. **Đã có trong thiết kế nhưng chưa cần triển khai:** 85 bảng CMS nội dung + 1 tracker + 6 bảng LMS -> **Tạm hoãn, kích hoạt khi đưa CMS/LMS vào khai thác**.
5. **Cần quyết định nghiệp vụ trước khi tạo:** Các tính năng cần chính sách quản trị của Ban Giám Đốc trước khi đóng khung bảng dữ liệu.

---

## II. BẢNG TỔNG HỢP PHÂN LOẠI 5 NHÓM ĐỐI CHIẾU

| Nhóm Phân Loại | Ý Nghĩa Nghiệp Vụ | Số Lượng Bảng / View | Danh Mục Bảng Cụ Thể | Định Hướng Xử Lý Kỹ Thuật |
|---|---|:---:|---|---|
| **1. Đã có và đủ** | Đang chạy ổn định trên Production, phục vụ an toàn cho Website KIPA. | **3 bảng** | `kipa_rate_limits`, `kipa_otp_codes`, `kipa_admins` | **Bảo toàn tuyệt đối.** Không đổi tên, không sửa kiểu dữ liệu, không xóa bảng. |
| **2. Đã có nhưng thiếu trường** | Đang chạy nhưng thiếu khóa ngoại liên kết hoặc cờ trạng thái CRM/Portal. | **2 bảng** | `kipa_leads` (thiếu `converted_to_customer_id`, `lead_status`), `kipa_users` (thiếu `avatar_url`, `is_active`, `last_login_at`) | **Mở rộng an toàn.** Bổ sung cột nullable; bảo vệ chống xóa dây chuyền từ `cleanup.php`. |
| **3. Chưa có, cần tạo mới** | Bắt buộc phải tạo để CRM và Cổng học viên hoạt động đồng bộ. | **28 bảng + 1 SQL View** | - Tổ chức & RBAC: `kipa_branches`, `kipa_departments`, `kipa_teams`, `kipa_staff`, `kipa_roles`, `kipa_permissions`, `kipa_staff_roles`, `kipa_role_delegations` (8 bảng)<br>- Khách hàng: `kipa_customers`, `kipa_customer_addresses`, `kipa_customer_relations`, `kipa_customer_education` (4 bảng)<br>- Hồ sơ & Quy trình: `kipa_service_dossiers`, `kipa_dossier_assignments`, `kipa_dossier_status_history`, `kipa_tasks`, `kipa_approvals`, `kipa_audit_logs` (6 bảng)<br>- Kho tài liệu gốc: `kipa_dossier_documents`, `kipa_document_versions`, `kipa_document_reviews` (3 bảng)<br>- Student Portal: `kipa_students`, `kipa_student_journey`, `kipa_student_schedules`, `kipa_student_notifications`, `kipa_student_materials`, `kipa_student_support`, `kipa_notification_settings` (7 bảng)<br>- View hợp nhất tài liệu: `VIEW kipa_student_documents` (1 View) | **Tạo mới bằng DDL chuẩn.** Toàn bộ khóa ngoại dùng `BIGINT UNSIGNED`, `InnoDB`, `utf8mb4_unicode_ci`, thiết lập `ON DELETE SET NULL` hoặc `CASCADE` hợp lý. |
| **4. Đã có trong thiết kế nhưng chưa cần triển khai** | Mô hình dữ liệu mở rộng chuẩn bị sẵn cho tương lai. | **92 bảng** | - 85 bảng CMS nội dung (Nhóm A -> J trong `content-schema.sql`)<br>- 1 bảng tracker CMS `kipa_content_state`<br>- 6 bảng LMS đào tạo (`kipa_lms_classes`, `kipa_lms_lessons`, `kipa_lms_enrollments`, `kipa_lms_attendance`, `kipa_lms_exams`, `kipa_lms_grades`) | **Giữ nguyên thiết kế staging.** Triển khai độc lập theo từng giai đoạn ra mắt CMS và LMS. |
| **5. Cần quyết định nghiệp vụ trước khi tạo** | Các quy chế nội bộ cần sự chuẩn thuận từ Ban Giám Đốc KIPA. | **2 chính sách nghiệp vụ** | - Tính hoa hồng CTV theo đợt (`kipa_commissions`)<br>- Chính sách lưu trữ hồ sơ quá hạn > 5 năm (`kipa_dossier_archives`) | **Tạm thời xử lý qua bảng hiện có:** Dùng `collaborator_id` trong `kipa_service_dossiers` và bảng `kipa_dossier_status_history` (trạng thái `ARCHIVED`). |

---

## III. ĐỐI CHIẾU CHI TIẾT NGHIỆP VỤ CRM ĐÃ THỐNG NHẤT

| Nghiệp Vụ CRM Yêu Cầu | Tình Trạng Thiết Kế | Bảng Dữ Liệu Đáp Ứng | Cơ Chế Kỹ Thuật Đã Tích Hợp |
|---|:---:|---|---|
| **1. Khách hàng, quan hệ gia đình & đa hồ sơ** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_customers`, `kipa_customer_addresses`, `kipa_customer_relations`, `kipa_customer_education`, `kipa_service_dossiers` | 1 khách hàng có thể sở hữu nhiều hồ sơ dịch vụ (du học tiếng Hàn D4-1, sau đó lên chuyên ngành D2-2, hoặc người thân đi XKLĐ). Thông tin phụ huynh (bố mẹ) lưu ở `kipa_customer_relations` phục vụ chứng minh tài chính visa. |
| **2. Tư vấn, bàn giao & xử lý hồ sơ** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_service_dossiers`, `kipa_dossier_assignments` | Phân công rõ 3 vai trò: Chuyên viên tư vấn (`consultant_id`), Chuyên viên hồ sơ (`case_officer_id`), và Cộng tác viên (`collaborator_id`). Lịch sử bàn giao ghi nhận tại `kipa_dossier_assignments`. |
| **3. Chi nhánh, phòng ban, nhân sự, nhóm, chức danh** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_branches`, `kipa_departments`, `kipa_teams`, `kipa_staff` | Mô hình 4 cấp hoàn chỉnh: Chi nhánh (7 văn phòng) -> Phòng ban (Tuyển sinh, Hồ sơ...) -> Nhóm (Team Hàn 1, Team Úc...) -> Nhân viên. Trường `position` lưu chức danh cụ thể. |
| **4. Phân quyền theo vai trò, phạm vi & hồ sơ được giao** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_roles`, `kipa_permissions`, `kipa_staff_roles` | Hỗ trợ trường `scope` (`GLOBAL`, `BRANCH`, `DEPARTMENT`, `ASSIGNED_ONLY`). Chuyên viên cấp cơ sở chỉ xem được hồ sơ được phân công phụ trách. |
| **5. Quyền tạm thời, ủy quyền & lịch sử phân quyền** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_staff_roles`, `kipa_role_delegations` | `kipa_staff_roles` có trường `granted_by` và `expires_at` (hết hạn quyền tự động). `kipa_role_delegations` hỗ trợ ủy quyền tạm thời khi nhân sự đi công tác/nghỉ phép. |
| **6. Công việc, nhiệm vụ, SLA & nhắc hạn** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_tasks` | Hỗ trợ trường `sla_hours` (cam kết phản hồi), `due_date` (hạn chót), `reminder_at` (thời điểm gửi thông báo nhắc việc) và `escalated_to` (báo cáo vượt cấp khi quá hạn SLA). |
| **7. Tài liệu, phiên bản, thẩm định & yêu cầu bổ sung** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_dossier_documents`, `kipa_document_versions`, `kipa_document_reviews` | Không ghi đè file. Mỗi lần nộp lại tăng `version_number`. Chuyên viên ghi nhận kết quả thẩm định (`review_status`: APPROVED / REJECTED / NEEDS_ADDITIONAL) và ghi rõ `rejection_reason`. |
| **8. Phê duyệt nhiều cấp & nhật ký kiểm toán** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_approvals`, `kipa_audit_logs` | `kipa_approvals` có trường `approval_level` (Cấp 1 Trưởng nhóm, Cấp 2 Giám đốc chi nhánh, Cấp 3 Ban Giám Đốc) và `next_approver_id`. `kipa_audit_logs` ghi nhận IP, user agent, giá trị cũ/mới của mọi thay đổi. |
| **9. Tạm dừng, mở lại, đóng & lưu trữ hồ sơ** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_dossier_status_history`, `kipa_service_dossiers` | `kipa_dossier_status_history` ghi nhận chi tiết: Trạng thái trước/sau, phân loại thay đổi (`PAUSE`, `REOPEN`, `CLOSE`, `ARCHIVE`), lý do thay đổi và nhân sự thực hiện. |
| **10. Lịch sử thay đổi người phụ trách & cộng tác viên** | **ĐÃ ĐÁP ỨNG ĐẦY ĐỦ** | `kipa_dossier_assignments` | Lưu vết thời điểm gán (`assigned_at`), kết thúc (`unassigned_at`), cờ hiện tại (`is_current`), và vai trò gán (`CONSULTANT`, `CASE_OFFICER`, `COLLABORATOR`). |

---

## IV. ĐỐI CHIẾU CHI TIẾT STUDENT PORTAL (CỔNG HỌC VIÊN)

| Yêu Cầu Cổng Học Viên | Tình Trạng Thiết Kế | Bảng Dữ Liệu Đáp Ứng | Cơ Chế Kỹ Thuật Đã Tích Hợp |
|---|:---:|---|---|
| **1. Liên kết tài khoản `kipa_users` với học viên và khách hàng** | **ĐÃ ĐÁP ỨNG** | `kipa_users`, `kipa_customers`, `kipa_students` | `kipa_users` là danh tính Auth duy nhất. `kipa_customers.user_id` liên kết 1-1. `kipa_students` cache profile giúp hiển thị trang cá nhân không cần JOIN nặng. |
| **2. Một học viên có nhiều hồ sơ đăng ký** | **ĐÃ ĐÁP ỨNG** | `kipa_service_dossiers` | Khóa ngoại `customer_id` quan hệ 1-Nhiều với `kipa_service_dossiers`. Học viên có thể chuyển đổi linh hoạt giữa các chương trình trên Dashboard. |
| **3. Một hồ sơ có nhiều tài liệu và nhiều phiên bản** | **ĐÃ ĐÁP ỨNG** | `kipa_dossier_documents`, `kipa_document_versions` | 1 hồ sơ có nhiều giấy tờ theo checklist. 1 giấy tờ có nhiều phiên bản file scan/PDF (`version_number: 1, 2, 3...`). |
| **4. Trạng thái tài liệu 5 cấp độ** | **ĐÃ ĐÁP ỨNG** | `kipa_dossier_documents.status` | Hỗ trợ chính xác 5 trạng thái hiển thị trên Cổng: `Chưa tải lên`, `Đã tải lên`, `Đang kiểm tra`, `Cần bổ sung`, `Đã xác nhận`. |
| **5. Lộ trình, thông báo, tài liệu học tập, hỗ trợ** | **ĐÃ ĐÁP ỨNG** | `kipa_student_journey`, `kipa_student_notifications`, `kipa_student_materials`, `kipa_student_support` | 4 phân hệ tương ứng 4 bảng chuyên biệt: 5 mốc lộ trình, trung tâm thông báo có cờ khẩn cấp, thư viện giáo trình PDF và ticket hỗ trợ trực tiếp. |
| **6. Quyền riêng tư: Học viên chỉ xem hồ sơ của mình** | **ĐÃ ĐÁP ỨNG** | Truy vấn theo `student_email` / `customer_id` | Mọi API Cổng học viên đều lọc bắt buộc theo `session['user_id']` hoặc `session['student_email']`, ngăn chặn tuyệt đối lộ dữ liệu chéo giữa các học viên. |
| **7. Nhân viên chỉ truy cập theo quyền được cấp** | **ĐÃ ĐÁP ỨNG** | `kipa_staff_roles.scope` | Kết hợp phân quyền CRM: Chuyên viên chỉ thấy danh sách học viên do mình phụ trách (`ASSIGNED_ONLY`) hoặc thuộc chi nhánh mình quản lý (`BRANCH`). |

---

## V. ĐỐI CHIẾU THIẾT KẾ KHO TÀI LIỆU (GIẢI PHÁP HỢP NHẤT SINGLE SOURCE OF TRUTH)

### Vấn đề xung đột nếu duy trì 2 bảng độc lập:
Nếu duy trì cả `kipa_dossier_documents` (phía CRM) và `kipa_student_documents` (phía Portal) dưới dạng 2 bảng vật lý riêng biệt:
- Khi học viên tải giấy tờ mới trên Portal, hệ thống phải ghi vào cả 2 bảng.
- Khi chuyên viên KIPA thẩm định giấy tờ và yêu cầu bổ sung trên CRM, nếu cập nhật thiếu một bảng sẽ dẫn đến tình trạng **lệch dữ liệu**: CRM báo "Cần bổ sung" nhưng Portal học viên vẫn hiện "Đang kiểm tra".
- Tốn gấp đôi dung lượng lưu trữ trên Shared Hosting.

### Giải pháp hợp nhất chuẩn mực:
1. **Kho vật lý gốc duy nhất (Master Storage):**
   - `kipa_dossier_documents`: Quản lý danh mục giấy tờ.
   - `kipa_document_versions`: Quản lý file vật lý và phiên bản.
   - `kipa_document_reviews`: Quản lý thẩm định và phản hồi.
2. **Khung nhìn hợp nhất cho Cổng học viên (Compatibility View):**
   - Tạo **`VIEW kipa_student_documents`** trong MySQL:
   ```sql
   CREATE OR REPLACE VIEW `kipa_student_documents` AS
   SELECT 
       d.id AS id,
       c.email AS student_email,
       d.doc_group AS doc_group,
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
3. **Lợi ích kiến trúc:**
   - Bảo toàn 100% mã nguồn hiện tại của Cổng học viên (`ho-so.html` và `api/account.php`).
   - Dữ liệu luôn đồng bộ thời gian thực giữa CRM và Portal.
   - Không tốn thêm một byte dung lượng nào cho việc lưu bản sao.

---

## VI. BẢO VỆ CHỐNG XÓA DÂY CHUYỀN LEADS (`api/cleanup.php`)

### Phân tích hiểm họa:
- Trong `api/cleanup.php` có câu lệnh dọn dẹp lead cũ theo quy định bảo mật:
  `DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH`
- Nếu trường `legacy_lead_id` trong `kipa_customers` thiết lập `ON DELETE CASCADE`, khi một khách hàng ký hợp đồng du học sau 1 năm, lead ban đầu bị xóa sẽ kéo theo **xóa sạch toàn bộ hồ sơ khách hàng, hồ sơ dịch vụ, visa và tài liệu trong CRM!**

### Giải pháp kỹ thuật bảo vệ 2 tầng:
1. **Tầng Database (Khóa ngoại an toàn):**
   Thiết lập bắt buộc trong DDL:
   ```sql
   CONSTRAINT `fk_kipa_customers_lead` FOREIGN KEY (`legacy_lead_id`) 
     REFERENCES `kipa_leads` (`id`) ON DELETE SET NULL
   ```
   Khi lead cũ bị xóa, trường `legacy_lead_id` trong `kipa_customers` tự động chuyển thành `NULL`, hồ sơ khách hàng vẫn được bảo toàn nguyên vẹn 100%.
2. **Tầng Ứng Dụng (`api/cleanup.php`):**
   Bổ sung điều kiện loại trừ:
   ```php
   $leads = $db->exec('DELETE FROM kipa_leads WHERE (converted_to_customer_id IS NULL OR converted_to_customer_id = 0) AND created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
   ```
   Chỉ xóa các form rác hoặc lead chưa chuyển đổi; không bao giờ xóa lead đã trở thành khách hàng.
