# KIPA VIỆT HÀN — SƠ ĐỒ QUAN HỆ THỰC THỂ DỮ LIỆU (DATABASE RELATIONSHIP MAP)

> **Tài liệu kiến trúc cơ sở dữ liệu:** Sơ đồ quan hệ thực thể (ERD) kết nối Website, CRM, Student Portal và LMS  
> **Phiên bản:** 2.1.0 (Vòng kiểm tra & Đối chiếu kỹ thuật cuối cùng)  
> **Chuẩn mô hình hóa:** Mermaid ER Diagrams & Khóa ngoại MySQL InnoDB  

---

## I. SƠ ĐỒ QUAN HỆ TỔNG THỂ HỆ THỐNG (MASTER ARCHITECTURE ERD)

```mermaid
erDiagram
    %% WEBSITE CORE & AUTHENTICATION
    kipa_leads ||--o| kipa_customers : "chuyển đổi thành (ON DELETE SET NULL)"
    kipa_users ||--o| kipa_customers : "tài khoản đăng nhập 1-1 (ON DELETE SET NULL)"
    kipa_admins ||--o| kipa_staff : "tài khoản quản trị nhân sự (ON DELETE SET NULL)"
    kipa_users ||--o| kipa_students : "xác thực cổng học viên"

    %% CRM ORGANIZATION & ADVANCED RBAC
    kipa_branches ||--o{ kipa_departments : "có các phòng ban"
    kipa_branches ||--o{ kipa_staff : "trực thuộc chi nhánh"
    kipa_branches ||--o{ kipa_customers : "tiếp nhận khách hàng"
    kipa_departments ||--o{ kipa_teams : "chia thành các nhóm chuyên môn"
    kipa_departments ||--o{ kipa_staff : "thuộc phòng ban"
    kipa_teams ||--o{ kipa_staff : "thuộc nhóm chuyên môn"
    kipa_staff ||--o{ kipa_staff_roles : "được gán vai trò"
    kipa_roles ||--o{ kipa_staff_roles : "vai trò hệ thống"
    kipa_roles ||--o{ kipa_permissions : "gồm các quyền hạn"
    kipa_staff ||--o{ kipa_role_delegations : "người ủy quyền"
    kipa_staff ||--o{ kipa_role_delegations : "người được ủy quyền"
    kipa_roles ||--o{ kipa_role_delegations : "vai trò ủy quyền"

    %% CRM CUSTOMER MASTER
    kipa_customers ||--o{ kipa_customer_addresses : "sổ địa chỉ"
    kipa_customers ||--o{ kipa_customer_relations : "quan hệ nhân thân & bảo lãnh"
    kipa_customers ||--o{ kipa_customer_education : "học vấn, GPA & chứng chỉ"
    kipa_customers ||--o{ kipa_service_dossiers : "đăng ký các hồ sơ dịch vụ"
    kipa_customers ||--o| kipa_students : "cache profile cổng học viên"
    kipa_customers ||--o| kipa_notification_settings : "cấu hình nhận tin"

    %% CRM SERVICE DOSSIERS & WORKFLOW
    kipa_staff ||--o{ kipa_service_dossiers : "cố vấn tư vấn phụ trách"
    kipa_staff ||--o{ kipa_service_dossiers : "chuyên viên hồ sơ xử lý"
    kipa_staff ||--o{ kipa_service_dossiers : "cộng tác viên giới thiệu (CTV)"
    kipa_service_dossiers ||--o{ kipa_dossier_assignments : "lịch sử phân công & bàn giao"
    kipa_service_dossiers ||--o{ kipa_dossier_status_history : "lịch sử tạm dừng, mở lại, đóng, lưu trữ"
    kipa_service_dossiers ||--o{ kipa_tasks : "nhiệm vụ & cam kết SLA"
    kipa_service_dossiers ||--o{ kipa_approvals : "quy trình phê duyệt nhiều cấp"
    kipa_service_dossiers ||--o{ kipa_dossier_documents : "checklist hồ sơ giấy tờ"
    kipa_service_dossiers ||--o{ kipa_student_journey : "5 mốc tiến độ hành trình"

    %% MASTER DOCUMENT VAULT
    kipa_dossier_documents ||--o{ kipa_document_versions : "các phiên bản file scan/PDF"
    kipa_document_versions ||--o{ kipa_document_reviews : "lịch sử thẩm định & phản hồi"
    kipa_dossier_documents ||--o{ kipa_student_documents : "VIEW ánh xạ sang cổng học viên"

    %% STUDENT PORTAL EXPERIENCES
    kipa_customers ||--o{ kipa_student_schedules : "lịch học & lịch hẹn"
    kipa_customers ||--o{ kipa_student_notifications : "thông báo đẩy"
    kipa_customers ||--o{ kipa_student_support : "ticket yêu cầu hỗ trợ"
    kipa_staff ||--o{ kipa_student_support : "chuyên viên tiếp nhận giải đáp"

    %% LMS FOUNDATION (STAGE 2)
    kipa_staff ||--o{ kipa_lms_classes : "giáo viên phụ trách"
    kipa_lms_classes ||--o{ kipa_lms_lessons : "chi tiết buổi học"
    kipa_lms_classes ||--o{ kipa_lms_enrollments : "danh sách học viên ghi danh"
    kipa_customers ||--o{ kipa_lms_enrollments : "khách hàng tham gia khóa học"
    kipa_lms_lessons ||--o{ kipa_lms_attendance : "điểm danh từng buổi"
    kipa_lms_enrollments ||--o{ kipa_lms_attendance : "chuyên cần của học viên"
    kipa_lms_classes ||--o{ kipa_lms_exams : "kỳ thi đánh giá"
    kipa_lms_exams ||--o{ kipa_lms_grades : "bảng điểm thi"
    kipa_lms_enrollments ||--o{ kipa_lms_grades : "điểm của từng học viên"
```

---

## II. SƠ ĐỒ CHI TIẾT CƠ CẤU TỔ CHỨC & PHÂN QUYỀN RBAC NÂNG CAO

```mermaid
erDiagram
    kipa_branches {
        bigint id PK
        varchar branch_code UK
        varchar branch_name
        varchar phone
        varchar address
        varchar province
        varchar country
        boolean is_active
    }

    kipa_departments {
        bigint id PK
        bigint branch_id FK
        varchar dept_code UK
        varchar dept_name
        boolean is_active
    }

    kipa_teams {
        bigint id PK
        bigint dept_id FK
        varchar team_code UK
        varchar team_name
        bigint leader_id FK
        boolean is_active
    }

    kipa_staff {
        bigint id PK
        varchar staff_code UK
        bigint admin_id FK
        varchar full_name
        varchar email
        varchar phone
        bigint branch_id FK
        bigint dept_id FK
        bigint team_id FK
        varchar position
        boolean is_active
    }

    kipa_roles {
        bigint id PK
        varchar role_code UK
        varchar role_name
        boolean is_system
    }

    kipa_permissions {
        bigint id PK
        varchar permission_code UK
        varchar permission_name
        varchar module_name
    }

    kipa_staff_roles {
        bigint staff_id PK,FK
        bigint role_id PK,FK
        varchar scope
        bigint granted_by FK
        datetime assigned_at
        datetime expires_at
    }

    kipa_role_delegations {
        bigint id PK
        bigint delegator_id FK
        bigint delegatee_id FK
        bigint role_id FK
        date start_date
        date end_date
        varchar reason
        boolean is_active
    }

    kipa_branches ||--o{ kipa_departments : "có"
    kipa_departments ||--o{ kipa_teams : "chia thành"
    kipa_departments ||--o{ kipa_staff : "thuộc"
    kipa_teams ||--o{ kipa_staff : "thuộc"
    kipa_staff ||--o{ kipa_staff_roles : "gán"
    kipa_roles ||--o{ kipa_staff_roles : "chứa"
    kipa_staff ||--o{ kipa_role_delegations : "ủy quyền"
    kipa_roles ||--o{ kipa_role_delegations : "ủy quyền vai trò"
```

---

## III. SƠ ĐỒ CHI TIẾT KHÁCH HÀNG, HỒ SƠ DỊCH VỤ & QUY TRÌNH XỬ LÝ

```mermaid
erDiagram
    kipa_customers {
        bigint id PK
        varchar customer_code UK
        varchar full_name
        varchar phone
        varchar email
        date birthday
        varchar gender
        varchar id_card_number
        bigint user_id FK
        bigint legacy_lead_id FK
        bigint assigned_staff_id FK
        bigint branch_id FK
        varchar customer_status
        datetime created_at
    }

    kipa_service_dossiers {
        bigint id PK
        varchar dossier_code UK
        bigint customer_id FK
        varchar service_type
        varchar target_country
        varchar target_intake
        varchar stage
        int progress_percent
        bigint consultant_id FK
        bigint case_officer_id FK
        bigint collaborator_id FK
        varchar status
        datetime created_at
    }

    kipa_dossier_assignments {
        bigint id PK
        bigint dossier_id FK
        bigint staff_id FK
        varchar assignment_role
        datetime assigned_at
        datetime unassigned_at
        boolean is_current
        varchar note
    }

    kipa_dossier_status_history {
        bigint id PK
        bigint dossier_id FK
        varchar from_status
        varchar to_status
        varchar change_type
        text reason
        bigint changed_by FK
        datetime changed_at
    }

    kipa_tasks {
        bigint id PK
        bigint dossier_id FK
        bigint customer_id FK
        varchar title
        bigint assigned_to FK
        varchar priority
        int sla_hours
        datetime due_date
        datetime reminder_at
        bigint escalated_to FK
        varchar status
    }

    kipa_approvals {
        bigint id PK
        bigint dossier_id FK
        varchar approval_type
        int approval_level
        bigint requested_by FK
        bigint approver_id FK
        bigint next_approver_id FK
        varchar status
        text comments
    }

    kipa_customers ||--o{ kipa_service_dossiers : "đăng ký"
    kipa_service_dossiers ||--o{ kipa_dossier_assignments : "lịch sử bàn giao"
    kipa_service_dossiers ||--o{ kipa_dossier_status_history : "vòng đời hồ sơ"
    kipa_service_dossiers ||--o{ kipa_tasks : "giao việc & SLA"
    kipa_service_dossiers ||--o{ kipa_approvals : "phê duyệt nhiều cấp"
```

---

## IV. SƠ ĐỒ CHI TIẾT KHO TÀI LIỆU DUY NHẤT & VIEW CỔNG HỌC VIÊN

```mermaid
erDiagram
    kipa_service_dossiers ||--o{ kipa_dossier_documents : "yêu cầu danh mục giấy tờ"
    kipa_customers ||--o{ kipa_dossier_documents : "sở hữu giấy tờ"

    kipa_dossier_documents {
        bigint id PK
        bigint dossier_id FK
        bigint customer_id FK
        varchar doc_group
        varchar doc_code
        varchar doc_name
        boolean is_required
        varchar status
        bigint current_version_id
    }

    kipa_document_versions {
        bigint id PK
        bigint document_id FK
        int version_number
        varchar file_url
        varchar file_name
        varchar file_size
        varchar mime_type
        varchar uploaded_by_type
        bigint uploaded_by_id
        datetime uploaded_at
        text note
    }

    kipa_document_reviews {
        bigint id PK
        bigint version_id FK
        bigint reviewer_id FK
        varchar review_status
        text review_note
        text rejection_reason
        datetime reviewed_at
    }

    kipa_student_documents {
        bigint id
        varchar student_email
        varchar doc_group
        varchar doc_name
        varchar status
        varchar file_url
        varchar file_name
        varchar file_size
        varchar uploaded_at
        text note
        datetime updated_at
    }

    kipa_dossier_documents ||--o{ kipa_document_versions : "quản lý phiên bản"
    kipa_document_versions ||--o{ kipa_document_reviews : "thẩm định"
    kipa_dossier_documents ||--o{ kipa_student_documents : "VIEW KHÔNG GHI ĐÈ / TỒN TẠI ĐỘC LẬP"
```

---

## V. NGUYÊN TẮC RÀNG BUỘC TOÀN VẸN (INTEGRITY CONSTRAINTS)

1. **Bảo tồn khách hàng khi xóa Lead rác (`legacy_lead_id`):**  
   `ON DELETE SET NULL` — Tuyệt đối không dùng `CASCADE` để bảo vệ khách hàng khỏi cronjob dọn dẹp sau 12 tháng của `api/cleanup.php`.
2. **Xóa theo tầng đối với thông tin bổ trợ khách hàng:**  
   Khi khách hàng bị xóa có chủ đích (GDPR/quyết định Ban Giám Đốc), các bảng `kipa_customer_addresses`, `kipa_customer_relations`, `kipa_customer_education` sẽ tự động xóa dọn dẹp theo (`ON DELETE CASCADE`).
3. **Bảo vệ hồ sơ dịch vụ (`customer_id` trong `kipa_service_dossiers`):**  
   `ON DELETE RESTRICT` — Không cho phép xóa khách hàng nếu khách hàng đó đang có hồ sơ dịch vụ hoặc hợp đồng đang xử lý.
4. **Nhân sự rời khỏi công ty (`assigned_to`, `case_officer_id`):**  
   `ON DELETE SET NULL` hoặc `ON DELETE RESTRICT` — Ngăn ngừa xóa mất dấu nhân viên đã ký duyệt hoặc phụ trách hồ sơ.
