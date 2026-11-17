# KIPA VIỆT HÀN — SƠ ĐỒ QUAN HỆ THỰC THỂ DỮ LIỆU (DATABASE RELATIONSHIP MAP)

> **Tài liệu kiến trúc cơ sở dữ liệu:** Sơ đồ quan hệ thực thể (ERD) kết nối Website, CRM, Student Portal và LMS  
> **Phiên bản:** 2.2.0 (Hoàn thiện nghiệp vụ Tiếp nhận & Tư vấn, Phân quyền, SLA, Phê duyệt kép, Bảo vệ Kho tài liệu & Bảo mật Cổng học viên)  
> **Chuẩn mô hình hóa:** Mermaid ER Diagrams & Khóa ngoại MySQL InnoDB  

---

## I. SƠ ĐỒ QUAN HỆ TỔNG THỂ HỆ THỐNG (MASTER ARCHITECTURE ERD)

```mermaid
erDiagram
    %% WEBSITE CORE & AUTHENTICATION
    kipa_leads ||--o| kipa_customers : "chuyển đổi thành (ON DELETE SET NULL)"
    kipa_leads ||--o{ kipa_consultation_dossiers : "intake vào tư vấn (ON DELETE SET NULL)"
    kipa_users ||--o| kipa_customers : "tài khoản đăng nhập 1-1 (ON DELETE SET NULL)"
    kipa_admins ||--o| kipa_staff : "tài khoản quản trị nhân sự (ON DELETE SET NULL)"
    kipa_users ||--o| kipa_students : "xác thực cổng học viên"

    %% CRM ORGANIZATION & ADVANCED RBAC
    kipa_branches ||--o{ kipa_departments : "có các phòng ban"
    kipa_branches ||--o{ kipa_staff : "trực thuộc chi nhánh"
    kipa_branches ||--o{ kipa_customers : "tiếp nhận khách hàng"
    kipa_branches ||--o{ kipa_working_calendars : "lịch làm việc theo chi nhánh"
    kipa_branches ||--o{ kipa_sla_policies : "áp dụng SLA chi nhánh"
    kipa_departments ||--o{ kipa_teams : "chia thành nhóm chuyên môn"
    kipa_departments ||--o{ kipa_staff : "thuộc phòng ban"
    kipa_teams ||--o{ kipa_staff : "thuộc nhóm chuyên môn"
    kipa_staff ||--o{ kipa_staff : "quản lý trực tiếp chính"
    kipa_roles ||--o{ kipa_role_permissions : "gồm các quyền"
    kipa_permissions ||--o{ kipa_role_permissions : "thuộc các vai trò"
    kipa_staff ||--o{ kipa_staff_roles : "được gán vai trò"
    kipa_roles ||--o{ kipa_staff_roles : "vai trò hệ thống"
    kipa_staff ||--o{ kipa_staff_role_history : "lịch sử luân chuyển quyền"
    kipa_staff ||--o{ kipa_role_delegations : "người ủy quyền"
    kipa_staff ||--o{ kipa_role_delegations : "người được ủy quyền"
    kipa_roles ||--o{ kipa_role_delegations : "vai trò ủy quyền"

    %% CRM CUSTOMER MASTER
    kipa_customers ||--o{ kipa_customer_addresses : "sổ địa chỉ"
    kipa_customers ||--o{ kipa_customer_relations : "quan hệ nhân thân & bảo lãnh"
    kipa_customers ||--o{ kipa_customer_education : "học vấn, GPA & chứng chỉ"
    kipa_customers ||--o{ kipa_consultation_dossiers : "có các đợt tư vấn"
    kipa_customers ||--o{ kipa_service_dossiers : "ký các hợp đồng dịch vụ"
    kipa_customers ||--o| kipa_students : "cache profile cổng học viên"
    kipa_customers ||--o| kipa_notification_settings : "cấu hình nhận tin"

    %% LEAD INTAKE & CONSULTATION DOSSIERS
    kipa_lead_sources ||--o{ kipa_consultation_dossiers : "nguồn tiếp nhận"
    kipa_campaigns ||--o{ kipa_consultation_dossiers : "chiến dịch marketing"
    kipa_staff ||--o{ kipa_consultation_dossiers : "chuyên viên tư vấn phụ trách"
    kipa_consultation_dossiers ||--o{ kipa_consultation_interests : "chi tiết nhu cầu dịch vụ"
    kipa_consultation_dossiers ||--o{ kipa_consultation_activities : "lịch sử cuộc gọi, gặp mặt, lịch hẹn"
    kipa_consultation_dossiers ||--o| kipa_service_dossiers : "bàn giao khi ký hợp đồng"

    %% SERVICE DOSSIERS & LIFECYCLE
    kipa_staff ||--o{ kipa_service_dossiers : "tư vấn phụ trách"
    kipa_staff ||--o{ kipa_service_dossiers : "chuyên viên hồ sơ xử lý"
    kipa_staff ||--o{ kipa_service_dossiers : "cộng tác viên giới thiệu (CTV)"
    kipa_service_dossiers ||--o{ kipa_dossier_assignments : "lịch sử phân công & bàn giao"
    kipa_service_dossiers ||--o{ kipa_dossier_status_history : "lịch sử tạm dừng, mở lại, đóng, lưu trữ"
    kipa_service_dossiers ||--o{ kipa_tasks : "nhiệm vụ gắn với hồ sơ"
    kipa_service_dossiers ||--o{ kipa_approvals : "yêu cầu phê duyệt"
    kipa_service_dossiers ||--o{ kipa_dossier_documents : "checklist hồ sơ giấy tờ (RESTRICT)"
    kipa_service_dossiers ||--o{ kipa_student_journey : "5 mốc tiến độ hành trình"

    %% TASKS & SLA POLICIES
    kipa_sla_policies ||--o{ kipa_tasks : "chính sách cam kết SLA"
    kipa_tasks ||--o{ kipa_tasks : "nhiệm vụ con (parent_id)"
    kipa_tasks ||--o{ kipa_task_collaborators : "người phối hợp"
    kipa_staff ||--o{ kipa_task_collaborators : "tham gia phối hợp"
    kipa_tasks ||--o{ kipa_task_history : "lịch sử thay đổi & gia hạn"

    %% APPROVAL WORKFLOWS & SNAPSHOTS
    kipa_approval_workflows ||--o{ kipa_approval_workflow_steps : "các bước quy trình"
    kipa_approval_workflows ||--o{ kipa_approvals : "quy trình áp dụng"
    kipa_approval_workflow_steps ||--o{ kipa_approvals : "bước hiện tại"
    kipa_staff ||--o{ kipa_approvals : "người gửi duyệt"
    kipa_staff ||--o{ kipa_approvals : "người duyệt 1"
    kipa_staff ||--o{ kipa_approvals : "người duyệt 2 (phê duyệt kép)"

    %% MASTER DOCUMENT VAULT & ACCESS LOGS
    kipa_dossier_documents ||--o{ kipa_document_versions : "quản lý các phiên bản"
    kipa_document_versions ||--o{ kipa_document_reviews : "lịch sử thẩm định & phản hồi"
    kipa_document_versions ||--o{ kipa_document_access_logs : "nhật ký xem & tải file nhạy cảm"
    kipa_dossier_documents ||--o{ kipa_student_documents : "VIEW ánh xạ sang cổng học viên"

    %% STUDENT PORTAL EXPERIENCES
    kipa_customers ||--o{ kipa_student_schedules : "lịch học & lịch hẹn"
    kipa_customers ||--o{ kipa_student_notifications : "thông báo đẩy"
    kipa_customers ||--o{ kipa_student_support : "ticket yêu cầu hỗ trợ"
    kipa_staff ||--o{ kipa_student_support : "chuyên viên tiếp nhận giải đáp"
```

---

## II. SƠ ĐỒ CHI TIẾT TIẾP NHẬN LEAD & HỒ SƠ TƯ VẤN (CONSULTATION INTAKE)

```mermaid
erDiagram
    kipa_leads {
        bigint id PK
        varchar form_type
        varchar contact_name
        varchar phone
        varchar email
        varchar program
        varchar source_page
        json details_json
        bigint converted_to_customer_id
        varchar lead_status
        datetime created_at
    }

    kipa_lead_sources {
        bigint id PK
        varchar source_code UK
        varchar source_name
        varchar channel_type
        boolean is_active
    }

    kipa_campaigns {
        bigint id PK
        varchar campaign_code UK
        varchar campaign_name
        date start_date
        date end_date
        decimal budget
        varchar status
    }

    kipa_consultation_dossiers {
        bigint id PK
        varchar consultation_code UK
        bigint customer_id FK
        bigint legacy_lead_id FK
        bigint lead_source_id FK
        bigint campaign_id FK
        bigint assigned_staff_id FK
        varchar stage
        varchar primary_interest
        varchar target_intake
        decimal estimated_budget
        datetime last_contacted_at
        datetime next_appointment_at
        varchar lost_reason
        text lost_notes
        varchar handover_status
        bigint handed_over_by FK
        datetime handed_over_at
        bigint accepted_by FK
        datetime accepted_at
        bigint created_service_dossier_id FK
    }

    kipa_consultation_interests {
        bigint id PK
        bigint consultation_id FK
        varchar service_type
        varchar target_country
        varchar target_major
        varchar preferred_school
    }

    kipa_consultation_activities {
        bigint id PK
        bigint consultation_id FK
        bigint staff_id FK
        varchar activity_type
        datetime activity_time
        varchar outcome
        text content
        datetime scheduled_next_at
    }

    kipa_leads ||--o{ kipa_consultation_dossiers : "intake"
    kipa_lead_sources ||--o{ kipa_consultation_dossiers : "nguồn"
    kipa_campaigns ||--o{ kipa_consultation_dossiers : "chiến dịch"
    kipa_consultation_dossiers ||--o{ kipa_consultation_interests : "nhu cầu"
    kipa_consultation_dossiers ||--o{ kipa_consultation_activities : "hoạt động"
```

---

## III. SƠ ĐỒ CHI TIẾT CƠ CẤU TỔ CHỨC & PHÂN QUYỀN RBAC NÂNG CAO

```mermaid
erDiagram
    kipa_staff {
        bigint id PK
        varchar staff_code UK
        bigint admin_id FK
        bigint direct_manager_id FK
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
        boolean is_sensitive
    }

    kipa_role_permissions {
        bigint role_id PK,FK
        bigint permission_id PK,FK
    }

    kipa_staff_roles {
        bigint staff_id PK,FK
        bigint role_id PK,FK
        varchar scope
        bigint granted_by FK
        datetime assigned_at
        datetime expires_at
    }

    kipa_staff_role_history {
        bigint id PK
        bigint staff_id FK
        bigint role_id FK
        varchar action
        varchar scope
        varchar reason
        bigint performed_by FK
        datetime occurred_at
    }

    kipa_role_delegations {
        bigint id PK
        bigint delegator_id FK
        bigint delegatee_id FK
        bigint role_id FK
        varchar delegated_scope
        date start_date
        date end_date
        varchar reason
        bigint approved_by FK
        varchar status
    }

    kipa_staff ||--o{ kipa_staff : "báo cáo trực tiếp (direct_manager_id)"
    kipa_roles ||--o{ kipa_role_permissions : "có quyền"
    kipa_permissions ||--o{ kipa_role_permissions : "gán cho vai trò"
    kipa_staff ||--o{ kipa_staff_roles : "được phân quyền"
    kipa_staff ||--o{ kipa_staff_role_history : "lịch sử luân chuyển quyền"
    kipa_staff ||--o{ kipa_role_delegations : "ủy quyền"
```

---

## IV. SƠ ĐỒ CHI TIẾT NHIỆM VỤ, QUẢN LÝ SLA & LỊCH LÀM VIỆC

```mermaid
erDiagram
    kipa_sla_policies {
        bigint id PK
        varchar policy_code UK
        varchar policy_name
        varchar service_type
        bigint branch_id FK
        varchar priority
        int response_time_hours
        int resolution_time_hours
        boolean is_active
    }

    kipa_working_calendars {
        bigint id PK
        bigint branch_id FK
        date calendar_date
        boolean is_working_day
        time start_time
        time end_time
        time lunch_start
        time lunch_end
        varchar holiday_name
    }

    kipa_tasks {
        bigint id PK
        bigint parent_id FK
        bigint dossier_id FK
        bigint customer_id FK
        varchar title
        bigint assigned_to FK
        bigint created_by FK
        varchar priority
        bigint sla_policy_id FK
        varchar sla_status
        datetime sla_paused_at
        int sla_total_paused_minutes
        datetime due_date
        datetime reminder_at
        bigint escalated_to FK
        boolean is_recurring
        varchar status
        bigint completion_confirmed_by FK
        datetime completion_confirmed_at
    }

    kipa_task_collaborators {
        bigint task_id PK,FK
        bigint staff_id PK,FK
        varchar role_description
    }

    kipa_task_history {
        bigint id PK
        bigint task_id FK
        bigint changed_by FK
        varchar field_name
        text old_value
        text new_value
        varchar reason
        datetime changed_at
    }

    kipa_sla_policies ||--o{ kipa_tasks : "áp dụng SLA"
    kipa_tasks ||--o{ kipa_tasks : "nhiệm vụ con (parent_id)"
    kipa_tasks ||--o{ kipa_task_collaborators : "người phối hợp"
    kipa_tasks ||--o{ kipa_task_history : "lịch sử thay đổi & gia hạn"
```

---

## V. SƠ ĐỒ CHI TIẾT QUY TRÌNH PHÊ DUYỆT & PHÊ DUYỆT KÉP

```mermaid
erDiagram
    kipa_approval_workflows {
        bigint id PK
        varchar workflow_code UK
        varchar workflow_name
        varchar approval_type
        int version
        boolean requires_dual_approval
        boolean is_active
    }

    kipa_approval_workflow_steps {
        bigint id PK
        bigint workflow_id FK
        int step_order
        varchar step_name
        bigint required_role_id FK
        bigint specific_approver_id FK
        int sla_hours
        boolean is_final_step
    }

    kipa_approvals {
        bigint id PK
        bigint dossier_id FK
        bigint workflow_id FK
        bigint step_id FK
        varchar approval_type
        int approval_level
        json snapshot_payload
        bigint requested_by FK
        bigint approver_id FK
        bigint next_approver_id FK
        bigint second_approver_id FK
        varchar status
        text comments
        text second_comments
        datetime reviewed_at
        datetime second_approved_at
    }

    kipa_approval_workflows ||--o{ kipa_approval_workflow_steps : "chứa các bước"
    kipa_approval_workflows ||--o{ kipa_approvals : "áp dụng quy trình"
    kipa_approval_workflow_steps ||--o{ kipa_approvals : "bước xử lý"
```

---

## VI. SƠ ĐỒ KHO TÀI LIỆU GỐC, NHẬT KÝ TRUY CẬP & VIEW CỔNG HỌC VIÊN

```mermaid
erDiagram
    kipa_service_dossiers ||--o{ kipa_dossier_documents : "yêu cầu checklist (ON DELETE RESTRICT)"
    kipa_customers ||--o{ kipa_dossier_documents : "chủ sở hữu giấy tờ"

    kipa_dossier_documents {
        bigint id PK
        bigint dossier_id FK
        bigint customer_id FK
        varchar doc_group
        varchar doc_code
        varchar doc_name
        boolean is_required
        varchar status
        varchar access_level
        boolean is_archived
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

    kipa_document_access_logs {
        bigint id PK
        bigint version_id FK
        varchar actor_type
        bigint actor_id
        varchar access_type
        varchar ip_address
        varchar user_agent
        datetime accessed_at
    }

    kipa_student_documents {
        bigint id
        bigint dossier_id
        bigint customer_id
        bigint user_id
        varchar student_email
        varchar doc_group
        varchar doc_code
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
    kipa_document_versions ||--o{ kipa_document_access_logs : "nhật ký xem & tải file"
    kipa_dossier_documents ||--o{ kipa_student_documents : "VIEW HỢP NHẤT (Chống lộ chéo IDOR)"
```
