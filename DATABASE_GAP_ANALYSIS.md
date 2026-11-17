# KIPA VIỆT HÀN — BÁO CÁO PHÂN TÍCH KHOẢNG CÁCH DỮ LIỆU (DATABASE GAP ANALYSIS)

> **Tài liệu chiến lược dữ liệu:** Đối chiếu toàn diện giữa Hiện trạng Database và Mục tiêu CRM + Student Portal  
> **Tác giả:** Senior Database Architect & MySQL Engineer  
> **Dự án:** Hệ sinh thái số KIPA Việt Hàn (Website - CRM - Student Portal - LMS)  

---

## I. MỤC TIÊU VÀ PHƯƠNG PHÁP ĐỐI CHIẾU

Báo cáo này phân tích chi tiết sự chênh lệch giữa:
1. **Dữ liệu thực tế đang hoạt động** trên Shared Hosting cPanel (5 bảng nền tảng).
2. **Thiết kế CMS nội dung 85 bảng** trong `backend/content-schema.sql` (theo SEO Plan).
3. **Mục tiêu quản trị KIPA CRM:** Quản lý vòng đời khách hàng, nhân sự, chi nhánh, hồ sơ dịch vụ, nhiệm vụ, tài liệu và phê duyệt.
4. **Mục tiêu KIPA Student Portal:** Đồng hành cùng học viên, theo dõi tiến độ, ví hồ sơ số, lịch trình và hỗ trợ.
5. **Nền tảng KIPA LMS tương lai:** Lớp học, điểm danh, bài thi tiếng Hàn.

Mỗi bảng trong hệ thống được phân loại chính xác vào 1 trong **4 nhóm hành động**:
- **`EXISTING_COMPLETE`**: Đã tồn tại, chạy ổn định, đáp ứng nghiệp vụ hiện tại -> **Bảo toàn 100%, không sửa đổi**.
- **`EXISTING_NEEDS_EXTENSION`**: Đã tồn tại nhưng thiếu trường định danh liên kết -> **Bổ sung cột an toàn (NULLABLE), không phá vỡ logic cũ**.
- **`MISSING_REQUIRED`**: Chưa có trên production, bắt buộc phải tạo để CRM và Portal hoạt động -> **Tạo mới bằng DDL chuẩn**.
- **`MISSING_OPTIONAL`**: Chưa có trên production, thuộc giai đoạn CMS mở rộng hoặc LMS -> **Tạm hoãn, sẵn sàng kích hoạt sau**.

---

## II. MA TRẬN PHÂN LOẠI TOÀN BỘ 122 BẢNG HỆ THỐNG

| Phân hệ | Tổng số bảng | EXISTING_COMPLETE | EXISTING_NEEDS_EXTENSION | MISSING_REQUIRED | MISSING_OPTIONAL |
|---|---:|---:|---:|---:|---:|
| **Website Core (Nền tảng)** | 5 | 3 | 2 | 0 | 0 |
| **KIPA CRM (Quản trị)** | 18 | 0 | 0 | 18 | 0 |
| **KIPA Student Portal (Học viên)** | 7 | 0 | 0 | 7 | 0 |
| **Website CMS (10 Nhóm A-J + State)** | 86 | 0 | 0 | 0 | 86 |
| **KIPA LMS (Đào tạo tiếng Hàn)** | 6 | 0 | 0 | 0 | 6 |
| **TỔNG CỘNG HỆ THỐNG** | **122** | **3 (2.5%)** | **2 (1.6%)** | **25 (20.5%)** | **92 (75.4%)** |

> **KẾT QUẢ CỐT LÕI:** Để đưa **KIPA CRM** và **Student Portal** vào hoạt động ngay mà không ảnh hưởng Website, số lượng bảng thực tế cần tạo mới là **25 bảng**, và chỉ cần mở rộng **2 bảng cũ** bằng các cột khóa ngoại an toàn.

---

## III. CHI TIẾT ĐỐI CHIẾU THEO 4 NHÓM HÀNH ĐỘNG

### 1. Nhóm `EXISTING_COMPLETE` (3 bảng) — Bảo toàn nguyên trạng
1. **`kipa_rate_limits`**:
   - Hiện trạng: Lưu mã băm IP, scope và thời gian để chặn brute-force.
   - Đánh giá: Đáp ứng 100% bảo mật. Cronjob tự dọn sau 24h.
   - Hành động: **Không can thiệp.**
2. **`kipa_otp_codes`**:
   - Hiện trạng: Lưu mã OTP 6 số qua email phục vụ đăng ký và lấy lại mật khẩu.
   - Đánh giá: Đầy đủ các trường `attempts`, `expires_at`, `verified_at`.
   - Hành động: **Không can thiệp, dùng chung cho cả Website và Portal.**
3. **`kipa_admins`**:
   - Hiện trạng: Lưu tài khoản quản trị hệ thống kỹ thuật (`admin`).
   - Đánh giá: Đã có cờ `must_change_password`, mã hóa mật khẩu Bcrypt.
   - Hành động: **Không can thiệp. Sẽ liên kết sang `kipa_staff` qua khóa ngoại `admin_id`.**

---

### 2. Nhóm `EXISTING_NEEDS_EXTENSION` (2 bảng) — Mở rộng an toàn
1. **`kipa_leads`**:
   - **Hiện trạng:** Đang lưu lead từ 6 biểu mẫu web (tên, số điện thoại, email, chương trình, JSON chi tiết).
   - **Thiếu sót:** Chưa có trường ghi nhận Lead này đã được nhân viên tiếp nhận hay chưa, và đã được chuyển thành Khách hàng trong CRM hay chưa.
   - **Đề xuất mở rộng an toàn:**
     ```sql
     ALTER TABLE kipa_leads 
       ADD COLUMN converted_to_customer_id BIGINT UNSIGNED NULL AFTER details_json,
       ADD COLUMN lead_status VARCHAR(32) NOT NULL DEFAULT 'NEW' AFTER converted_to_customer_id;
     ```
   - **Đảm bảo tương thích:** Tất cả các cột bổ sung đều có giá trị mặc định hoặc `NULL`, không làm ảnh hưởng đến mã nguồn nộp form của `api/submit.php`.

2. **`kipa_users`**:
   - **Hiện trạng:** Đang lưu tài khoản học viên (email, mật khẩu, họ tên, điện thoại).
   - **Thiếu sót:** Chưa có cờ khóa tài khoản `is_active`, thời điểm đăng nhập cuối `last_login_at`, và ảnh đại diện `avatar_url`.
   - **Đề xuất mở rộng an toàn:**
     ```sql
     ALTER TABLE kipa_users 
       ADD COLUMN avatar_url VARCHAR(255) NULL AFTER phone,
       ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT 1 AFTER avatar_url,
       ADD COLUMN last_login_at DATETIME NULL AFTER email_verified_at;
     ```

---

### 3. Nhóm `MISSING_REQUIRED` (25 bảng) — Bắt buộc tạo mới cho CRM & Student Portal

#### A. Phân hệ Tổ chức & Nhân sự KIPA (5 bảng)
1. `kipa_branches`: 7 chi nhánh/văn phòng KIPA (Hải Phòng, Hà Nội, TP.HCM, Seoul, Sydney...).
2. `kipa_departments`: Các phòng ban (Tư vấn tuyển sinh, Hồ sơ & Visa, Đào tạo tiếng Hàn, Đối ngoại, Kế toán).
3. `kipa_staff`: Hồ sơ nhân sự KIPA (mã nhân viên, họ tên, chức vụ, chi nhánh, phòng ban, email, liên kết `admin_id`).
4. `kipa_roles`: Bảng vai trò hệ thống (Admin, Giám đốc, Trưởng phòng, Tư vấn viên, Chuyên viên hồ sơ, Giáo viên).
5. `kipa_permissions` & `kipa_staff_roles`: Phân quyền chi tiết thao tác trên CRM.

#### B. Phân hệ Khách hàng CRM (4 bảng)
6. `kipa_customers`: **Thực thể trung tâm CRM.** Lưu trữ mã khách hàng, CCCD, thông tin liên lạc, nguồn tiếp nhận, trạng thái. Liên kết 1-1 với `kipa_users` khi tạo tài khoản học viên.
7. `kipa_customer_addresses`: Sổ địa chỉ (Hộ khẩu thường trú, Tạm trú) phục vụ dịch thuật công chứng và hồ sơ visa.
8. `kipa_customer_relations`: Quan hệ nhân thân (Bố, Mẹ, Vợ/Chồng) phục vụ chứng minh tài chính và người liên hệ khẩn cấp.
9. `kipa_customer_education`: Hồ sơ học vấn (Trường cũ, GPA học bạ cấp 3/Đại học, chứng chỉ ngoại ngữ TOPIK/IELTS/KLAT).

#### C. Phân hệ Hồ sơ Dịch vụ, Nhiệm vụ & Quy trình (5 bảng)
10. `kipa_service_dossiers`: **Thực thể trung tâm xử lý hồ sơ.** Mỗi khách hàng đăng ký 1 chương trình du học Hàn/Úc hoặc XKLĐ sẽ có 1 hồ sơ dịch vụ riêng biệt. Quản lý giai đoạn, tiến độ %, chuyên viên tư vấn và chuyên viên xử lý hồ sơ.
11. `kipa_dossier_assignments`: Lịch sử phân công nhân viên phụ trách hồ sơ (bảo đảm minh bạch khi bàn giao nhân sự).
12. `kipa_tasks`: Quản lý việc cần làm (thu thập giấy tờ, nhắc hạn dịch thuật, khám sức khỏe, nộp tiền đặt cọc).
13. `kipa_approvals`: Quy trình phê duyệt nội bộ (Duyệt hợp đồng, duyệt hồ sơ nộp trường, duyệt nộp visa).
14. `kipa_audit_logs`: Nhật ký kiểm toán mọi thao tác trên CRM (bảo vệ an toàn dữ liệu trên shared hosting).

#### D. Phân hệ Tài liệu & Ví hồ sơ số (Dùng chung CRM & Portal) (4 bảng)
15. `kipa_dossier_documents`: Danh mục giấy tờ theo checklist cá nhân hóa từng chương trình.
16. `kipa_document_versions`: Lưu các phiên bản file scan/PDF nộp bổ sung.
17. `kipa_document_reviews`: Lịch sử thẩm định giấy tờ, lý do yêu cầu bổ sung của chuyên viên.
18. `kipa_student_materials`: Thư viện tài liệu học tập, giáo trình TOPIK, cẩm nang xuất cảnh.

#### E. Phân hệ Student Portal Nghiệp vụ (7 bảng)
19. `kipa_students`: Bảng hồ sơ tổng hợp học viên (đã cấu hình trong `schema.sql`), cache dữ liệu hiển thị nhanh trên Portal.
20. `kipa_student_journey`: 5 mốc hành trình cá nhân (Tư vấn, Học tiếng, Chuẩn bị hồ sơ, Xét tuyển, Visa & Xuất cảnh).
21. `kipa_student_documents`: Đồng bộ danh mục tài liệu trực tiếp từ `kipa_dossier_documents`.
22. `kipa_student_schedules`: Lịch học, lịch phỏng vấn và tư vấn cá nhân.
23. `kipa_student_notifications`: Trung tâm thông báo có phân loại và cờ khẩn cấp.
24. `kipa_student_support`: Kênh gửi ticket hỗ trợ và phản hồi 2 chiều với cố vấn.
25. `kipa_notification_settings`: Cài đặt nhận thông báo của học viên (Email, Zalo, Web Push).

---

### 4. Nhóm `MISSING_OPTIONAL` (92 bảng) — Triển khai ở các giai đoạn tiếp theo
1. **85 Bảng CMS Nội dung (Nhóm A - J):**
   - Đã được lập trình DDL hoàn chỉnh trong `backend/content-schema.sql`.
   - Vì 16 trang web KIPA hiện tại đang chạy HTML tĩnh đạt điểm số SEO/Pagespeed tối đa, chưa cần thiết phải nạp 85 bảng này lên production ngay lập tức.
   - Sẽ kích hoạt bằng lệnh `php api/migrate.php --apply` khi KIPA đưa công cụ xuất bản tự động vào vận hành.
2. **1 Bảng `kipa_content_state`:** Bảng ghi nhận phiên bản migration của CMS.
3. **6 Bảng KIPA LMS:**
   - `kipa_lms_classes`, `kipa_lms_lessons`, `kipa_lms_enrollments`, `kipa_lms_attendance`, `kipa_lms_exams`, `kipa_lms_grades`.
   - Đã được chuẩn bị sẵn cấu trúc quan hệ khóa ngoại gắn liền với `kipa_customers`, sẵn sàng khởi tạo khi đưa hệ thống đào tạo ngoại ngữ vào hoạt động.

---

## IV. PHÂN TÍCH VÀ XỬ LÝ 4 RỦI RO NGHIỆP VỤ & KIẾN TRÚC NGHIÊM TRỌNG

### 1. RỦI RO XÓA DÂY CHUYỀN (CASCADE DELETION) TỪ `api/cleanup.php`
- **Nguyên nhân gốc rễ:**
  - File `api/cleanup.php` có đoạn mã chạy cron hằng đêm:
    ```php
    $leads = $db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
    ```
  - File `api/migrate.php` cấu hình khóa ngoại `legacy_lead_id` với điều kiện `ON DELETE CASCADE`.
- **Hậu quả nếu không xử lý:**
  - Khi một khách hàng đã ký hợp đồng du học sau 12 tháng, cronjob chạy sẽ xóa lead gốc trong `kipa_leads`.
  - Cơ chế `CASCADE` sẽ tự động xóa sạch dữ liệu khách hàng, hồ sơ dịch vụ, visa và tài liệu trong CRM!
- **Giải pháp dứt điểm:**
  1. Trong bảng `kipa_customers`, trường `legacy_lead_id` phải cấu hình `ON DELETE SET NULL`:
     ```sql
     FOREIGN KEY (legacy_lead_id) REFERENCES kipa_leads(id) ON DELETE SET NULL
     ```
  2. Bổ sung trường `converted_to_customer_id` vào `kipa_leads`.
  3. Cập nhật câu lệnh trong `api/cleanup.php` chỉ xóa các lead rác / spam chưa được chuyển thành khách hàng:
     ```php
     $leads = $db->exec('DELETE FROM kipa_leads WHERE converted_to_customer_id IS NULL AND created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
     ```

### 2. BẢO TOÀN DANH TÍNH DUY NHẤT (SINGLE SOURCE OF TRUTH)
- **Vấn đề trùng lặp:** 1 người có thể là:
  - Khách hàng điền form tư vấn (`kipa_leads`).
  - Khách hàng trong CRM (`kipa_customers`).
  - Học viên đăng nhập Cổng thông tin (`kipa_users` + `kipa_students`).
- **Giải pháp thống nhất:**
  - `kipa_users`: Giữ vai trò duy nhất là **Tài khoản xác thực (Authentication Identity)** chứa mật khẩu băm, email, số điện thoại.
  - `kipa_customers`: Giữ vai trò **Hồ sơ kinh doanh & pháp lý (Business Profile)** chứa CCCD, học bạ, hồ sơ gia đình. Liên kết `user_id` với `kipa_users.id`.
  - `kipa_students`: Là **Góc nhìn học viên (Student View)** của khách hàng đó, hiển thị dữ liệu tiến độ và hồ sơ học tập.

### 3. QUẢN LÝ ĐA CHÍ NHÁNH & BẢO MẬT DỮ LIỆU
- KIPA có 7 cơ sở tại Việt Nam, Hàn Quốc và Úc.
- Nhân viên tư vấn ở chi nhánh Hải Phòng không được tự ý xem hoặc sửa hợp đồng tài chính của chi nhánh TP.HCM nếu không có quyền Giám đốc.
- Mô hình RBAC (`kipa_staff_roles`, `kipa_permissions`) bảo vệ phân quyền ở cấp độ hàng (Row-level permissions) dựa trên `branch_id` và `dept_id`.

### 4. VÍ HỒ SƠ SỐ & LỊCH SỬ THẨM ĐỊNH (DOCUMENT AUDIT TRAIL)
- Trong quy trình du học và XKLĐ, giấy tờ học bạ và chứng minh tài chính thường xuyên phải sửa đổi, dịch thuật lại hoặc bổ sung dấu tím Sở Ngoại Vụ.
- Không được ghi đè trực tiếp lên file cũ. Cần lưu theo phiên bản (`kipa_document_versions`) và lưu vết từng lần chuyên viên KIPA từ chối hoặc yêu cầu bổ sung (`kipa_document_reviews`).
