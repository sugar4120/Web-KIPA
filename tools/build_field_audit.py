# -*- coding: utf-8 -*-
"""
Script to generate DATABASE_FIELD_AUDIT.xlsx
Senior Database Architect Audit & Gap Analysis for KIPA Website + CRM + Student Portal + LMS
Version: 2.2.0 (Full Consultation, RBAC, SLA, Approvals, Protected Documents)
"""

import json
import os
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

def build_excel_audit():
    wb = openpyxl.Workbook()
    
    # Define styles
    header_font = Font(name="Segoe UI", size=11, bold=True, color="FFFFFF")
    title_font = Font(name="Segoe UI", size=14, bold=True, color="003875")
    sub_font = Font(name="Segoe UI", size=10, italic=True, color="555555")
    bold_font = Font(name="Segoe UI", size=10, bold=True)
    regular_font = Font(name="Segoe UI", size=10)
    code_font = Font(name="Consolas", size=9.5)
    
    header_fill = PatternFill(start_color="003875", end_color="003875", fill_type="solid")
    sec_header_fill = PatternFill(start_color="0274BD", end_color="0274BD", fill_type="solid")
    
    # Status fills
    fill_existing_complete = PatternFill(start_color="D1E7DD", end_color="D1E7DD", fill_type="solid") # light green
    font_existing_complete = Font(name="Segoe UI", size=10, bold=True, color="0F5132")
    
    fill_existing_ext = PatternFill(start_color="FFF3CD", end_color="FFF3CD", fill_type="solid") # light yellow
    font_existing_ext = Font(name="Segoe UI", size=10, bold=True, color="664D03")
    
    fill_missing_req = PatternFill(start_color="F8D7DA", end_color="F8D7DA", fill_type="solid") # light red
    font_missing_req = Font(name="Segoe UI", size=10, bold=True, color="842029")
    
    fill_missing_opt = PatternFill(start_color="E2E3E5", end_color="E2E3E5", fill_type="solid") # light gray
    font_missing_opt = Font(name="Segoe UI", size=10, bold=True, color="41464B")

    thin_border = Border(
        left=Side(style="thin", color="E0E0E0"),
        right=Side(style="thin", color="E0E0E0"),
        top=Side(style="thin", color="E0E0E0"),
        bottom=Side(style="thin", color="E0E0E0")
    )
    
    # -------------------------------------------------------------
    # SHEET 1: DASHBOARD & SUMMARY
    # -------------------------------------------------------------
    ws_dash = wb.active
    ws_dash.title = "TongHop_Dashboard"
    ws_dash.views.sheetView[0].showGridLines = True
    
    ws_dash.merge_cells("A1:G1")
    ws_dash["A1"] = "KIPA VIỆT HÀN — BÁO CÁO ĐỐI CHIẾU & KIỂM TOÁN HỆ THỐNG CƠ SỞ DỮ LIỆU"
    ws_dash["A1"].font = title_font
    
    ws_dash.merge_cells("A2:G2")
    ws_dash["A2"] = "Phân hệ: Website KIPA + CMS Nội Dung + KIPA CRM + KIPA Student Portal + LMS (Phiên bản 2.2.0)"
    ws_dash["A2"].font = sub_font
    
    headers_summary = ["Nhóm Phân Hệ / Chức Năng", "Tổng Số Bảng", "Đang Hoạt Động (Prod)", "Đã Thiết Kế CMS (Staged)", "Cần Tạo Mới CRM", "Cần Tạo Mới Portal", "Giai Đoạn Sau (LMS)"]
    ws_dash.append([])
    ws_dash.append(headers_summary)
    for col_idx, col_name in enumerate(headers_summary, 1):
        cell = ws_dash.cell(row=4, column=col_idx)
        cell.font = header_font
        cell.fill = header_fill
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)
    
    summary_data = [
        ["1. Website Nền Tảng (Core Leads, Auth & Users)", 5, 5, 0, 0, 0, 0],
        ["2. Website CMS (10 Nhóm A - J theo SEO Plan)", 85, 0, 85, 0, 0, 0],
        ["3. CMS Migration State Tracker", 1, 0, 1, 0, 0, 0],
        ["4. KIPA CRM (Tổ chức, Tư vấn, RBAC, Hồ sơ, SLA, Phê duyệt, Kho tài liệu)", 35, 0, 0, 35, 0, 0],
        ["5. KIPA Student Portal (Hồ sơ học viên, Lộ trình, Lịch, Support + 1 View)", 7, 0, 0, 0, 7, 0],
        ["6. KIPA LMS (Đào Tạo & Lớp Học - Giai Đoạn Sau)", 6, 0, 0, 0, 0, 6],
        ["TỔNG CỘNG BẢNG VẬT LÝ TOÀN HỆ THỐNG (Kèm 1 SQL View)", 139, 5, 86, 35, 7, 6]
    ]
    
    for row_idx, row_val in enumerate(summary_data, 5):
        ws_dash.append(row_val)
        for col_idx in range(1, len(row_val) + 1):
            cell = ws_dash.cell(row=row_idx, column=col_idx)
            cell.font = bold_font if row_idx == 11 else regular_font
            cell.border = thin_border
            if col_idx == 1:
                cell.alignment = Alignment(horizontal="left", vertical="center")
            else:
                cell.alignment = Alignment(horizontal="center", vertical="center")
            if row_idx == 11:
                cell.fill = PatternFill(start_color="EBF3FB", end_color="EBF3FB", fill_type="solid")

    # Status classification overview
    ws_dash.cell(row=13, column=1, value="BẢNG PHÂN LOẠI TRẠNG THÁI KIỂM TOÁN (5 NHÓM THEO YÊU CẦU)").font = bold_font
    cat_headers = ["Phân Loại", "Ý Nghĩa Nghiệp Vụ", "Số Lượng", "Tỷ Lệ %", "Định Hướng Hành Động"]
    for col_idx, h in enumerate(cat_headers, 1):
        cell = ws_dash.cell(row=14, column=col_idx, value=h)
        cell.font = header_font
        cell.fill = sec_header_fill
        cell.alignment = Alignment(horizontal="center", vertical="center")
    
    cat_data = [
        ["1. ĐÃ CÓ VÀ ĐỦ", "Bảng hiện tại trên cPanel đã đủ phục vụ Website (Rate limit, OTP, Admin auth)", 3, "2.1%", "Bảo toàn tuyệt đối, không thay đổi cấu trúc, không can thiệp."],
        ["2. ĐÃ CÓ NHƯNG THIẾU TRƯỜNG", "Đã có nhưng cần thêm trường FK liên kết CRM/Portal (kipa_leads, kipa_users)", 2, "1.4%", "Thêm cột nullable (converted_to_customer_id, is_active, last_login_at); bảo vệ chống cascade delete."],
        ["3. CHƯA CÓ, CẦN TẠO MỚI", "42 bảng vật lý + 1 SQL View để CRM và Student Portal vận hành đồng bộ", "42 bảng + 1 view", "30.9%", "Thực thi bằng crm_portal_schema.sql (InnoDB, utf8mb4, BIGINT UNSIGNED, ON DELETE SET NULL an toàn)."],
        ["4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI", "85 bảng CMS nội dung + 1 tracker + 6 bảng LMS", 92, "65.7%", "Giữ nguyên thiết kế staging trong content-schema.sql & LMS, chỉ migrate khi đưa CMS/LMS vào khai thác."],
        ["5. CẦN QUYẾT ĐỊNH NGHIỆP VỤ TRƯỚC KHI TẠO", "Chính sách hoa hồng CTV theo đợt, chính sách lưu trữ hồ sơ quá hạn > 5 năm", "Quy chế", "-", "Tạm hoãn bảng phụ, sử dụng kipa_dossier_assignments (COLLABORATOR) và kipa_dossier_status_history."]
    ]
    for row_idx, r in enumerate(cat_data, 15):
        ws_dash.append(r)
        for col_idx in range(1, len(r) + 1):
            cell = ws_dash.cell(row=row_idx, column=col_idx)
            cell.font = regular_font
            cell.border = thin_border
            if col_idx == 1:
                if "1." in r[0]:
                    cell.fill, cell.font = fill_existing_complete, font_existing_complete
                elif "2." in r[0]:
                    cell.fill, cell.font = fill_existing_ext, font_existing_ext
                elif "3." in r[0]:
                    cell.fill, cell.font = fill_missing_req, font_missing_req
                else:
                    cell.fill, cell.font = fill_missing_opt, font_missing_opt
                cell.alignment = Alignment(horizontal="left", vertical="center")
            elif col_idx in [3, 4]:
                cell.alignment = Alignment(horizontal="center", vertical="center")
            else:
                cell.alignment = Alignment(horizontal="left", vertical="center")

    # -------------------------------------------------------------
    # SHEET 2: BANG DOI CHIEU CHI TIET
    # -------------------------------------------------------------
    ws_detail = wb.create_sheet(title="BangDoiChieu_ChiTiet")
    ws_detail.views.sheetView[0].showGridLines = True
    
    audit_cols = [
        "STT", "Tên bảng / View", "Phân hệ", "Đã tồn tại?", "Số bản ghi", 
        "Trường đã có", "Trường còn thiếu", "Quan hệ FK", "Phân loại", "Đề xuất kỹ thuật & kiến trúc"
    ]
    ws_detail.append(audit_cols)
    for col_idx, col_name in enumerate(audit_cols, 1):
        cell = ws_detail.cell(row=1, column=col_idx)
        cell.font = header_font
        cell.fill = header_fill
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)

    # Master table list for audit
    tables_to_audit = [
        # 1. Baseline
        {
            "name": "kipa_leads", "module": "Website Core", "exists": "ĐÃ TỒN TẠI", "records": "3 bản ghi test",
            "existing_fields": "id, form_type, contact_name, phone, email, program, source_page, details_json, consented_at, created_at",
            "missing_fields": "converted_to_customer_id, lead_status",
            "fk": "Không có FK ra ngoài. Bị kipa_form_submissions tham chiếu.",
            "type": "2. ĐÃ CÓ NHƯNG THIẾU TRƯỜNG",
            "proposal": "BẢO TOÀN DỮ LIỆU. Bổ sung cột nullable converted_to_customer_id để liên kết sang CRM. Sửa logic submit.php & cleanup.php không xóa lead đã thành khách hàng."
        },
        {
            "name": "kipa_rate_limits", "module": "Website Security", "exists": "ĐÃ TỒN TẠI", "records": "Biến động theo IP",
            "existing_fields": "id, scope, remote_key, occurred_at",
            "missing_fields": "Không thiếu",
            "fk": "Không có",
            "type": "1. ĐÃ CÓ VÀ ĐỦ",
            "proposal": "Giữ nguyên. Cleanup tự động xóa bản ghi cũ hơn 24h qua cronjob."
        },
        {
            "name": "kipa_otp_codes", "module": "Website Auth", "exists": "ĐÃ TỒN TẠI", "records": "Biến động",
            "existing_fields": "id, email, code_hash, attempts, expires_at, verified_at, created_at",
            "missing_fields": "Không thiếu",
            "fk": "Không có. Liên kết logic qua email với kipa_users.",
            "type": "1. ĐÃ CÓ VÀ ĐỦ",
            "proposal": "Giữ nguyên. Dùng chung cho quy trình xác thực OTP đăng ký và quên mật khẩu của Student Portal."
        },
        {
            "name": "kipa_users", "module": "Website / Portal Auth", "exists": "ĐÃ TỒN TẠI", "records": "1 học viên test",
            "existing_fields": "id, email, password_hash, full_name, phone, email_verified_at, created_at",
            "missing_fields": "avatar_url, is_active, last_login_at",
            "fk": "Được kipa_students, kipa_customers tham chiếu qua user_id hoặc email.",
            "type": "2. ĐÃ CÓ NHƯNG THIẾU TRƯỜNG",
            "proposal": "Đóng vai trò tài khoản đăng nhập (Auth). Bổ sung is_active, last_login_at. Liên kết 1-1 với kipa_customers.user_id."
        },
        {
            "name": "kipa_admins", "module": "Website / CMS Auth", "exists": "ĐÃ TỒN TẠI", "records": "1 tài khoản admin",
            "existing_fields": "id, username, password_hash, must_change_password, created_at",
            "missing_fields": "full_name, email, role_id",
            "fk": "Được kipa_staff tham chiếu qua admin_id.",
            "type": "1. ĐÃ CÓ VÀ ĐỦ",
            "proposal": "Giữ nguyên tài khoản quản trị kỹ thuật. Liên kết với kipa_staff để phân quyền nghiệp vụ CRM."
        },

        # 2. CRM Organization & RBAC (8 BẢNG)
        {"name": "kipa_branches", "module": "CRM Organization", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, branch_code, branch_name, phone, email, address, province, country, manager_name, is_active, created_at", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Quản lý 7 chi nhánh KIPA."},
        {"name": "kipa_departments", "module": "CRM Organization", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, branch_id, dept_code, dept_name, description, is_active, created_at", "fk": "branch_id -> kipa_branches(id) ON DELETE SET NULL", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Phân chia phòng ban chức năng."},
        {"name": "kipa_teams", "module": "CRM Organization", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, dept_id, team_code, team_name, leader_id, description, is_active, created_at", "fk": "dept_id -> kipa_departments(id) ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Quản lý nhóm chuyên môn trực thuộc phòng ban."},
        {"name": "kipa_staff", "module": "CRM Organization", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, staff_code, admin_id, direct_manager_id, full_name, email, phone, branch_id, dept_id, team_id, position, avatar_url, is_active, created_at, updated_at", "fk": "admin_id -> kipa_admins, direct_manager_id -> kipa_staff, branch_id -> kipa_branches, dept_id -> kipa_departments, team_id -> kipa_teams", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Hồ sơ nhân sự có liên kết quản lý trực tiếp chính."},
        {"name": "kipa_roles", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, role_code, role_name, description, is_system, created_at", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Vai trò hệ thống."},
        {"name": "kipa_permissions", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, permission_code, permission_name, module_name, description, is_sensitive", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Quyền hạn chi tiết có cờ nhạy cảm."},
        {"name": "kipa_role_permissions", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "role_id, permission_id, created_at", "fk": "role_id -> kipa_roles, permission_id -> kipa_permissions", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Bảng quan hệ vai trò - quyền hạn cụ thể."},
        {"name": "kipa_staff_roles", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "staff_id, role_id, scope, granted_by, assigned_at, expires_at", "fk": "staff_id -> kipa_staff, role_id -> kipa_roles, granted_by -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Gán quyền kèm phạm vi và thời hạn."},
        {"name": "kipa_staff_role_history", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, staff_id, role_id, action, scope, reason, performed_by, occurred_at", "fk": "staff_id -> kipa_staff, role_id -> kipa_roles, performed_by -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Lịch sử cấp, sửa, thu hồi quyền khi chuyển phòng ban."},
        {"name": "kipa_role_delegations", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, delegator_id, delegatee_id, role_id, delegated_scope, start_date, end_date, reason, approved_by, status, created_at", "fk": "delegator_id -> kipa_staff, delegatee_id -> kipa_staff, role_id -> kipa_roles, approved_by -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Ủy quyền tạm thời có phê duyệt và phạm vi."},

        # 3. CRM Customers (4 BẢNG)
        {"name": "kipa_customers", "module": "CRM Core", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, customer_code, full_name, phone, email, birthday, gender, id_card_number, id_card_date, id_card_place, user_id, legacy_lead_id, lead_source_id, assigned_staff_id, branch_id, customer_status, notes, created_by, created_at, updated_at", "fk": "user_id -> kipa_users, legacy_lead_id -> kipa_leads (SET NULL), assigned_staff_id -> kipa_staff, branch_id -> kipa_branches", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Thực thể trung tâm CRM."},
        {"name": "kipa_customer_addresses", "module": "CRM Core", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, customer_id, address_type, street_address, ward, district, province, is_default, created_at", "fk": "customer_id -> kipa_customers ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Sổ địa chỉ thường trú, tạm trú."},
        {"name": "kipa_customer_relations", "module": "CRM Core", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, customer_id, relation_type, full_name, phone, email, occupation, birth_year, address, is_emergency, created_at", "fk": "customer_id -> kipa_customers ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Quan hệ thân nhân chứng minh tài chính visa."},
        {"name": "kipa_customer_education", "module": "CRM Core", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, customer_id, education_level, school_name, major, graduation_year, gpa, language_certificate, language_score, certificate_date, created_at", "fk": "customer_id -> kipa_customers ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Quản lý học bạ và chứng chỉ tiếng."},

        # 4. Lead Intake & Consultation (5 BẢNG)
        {"name": "kipa_lead_sources", "module": "CRM Consultation", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, source_code, source_name, channel_type, is_active, created_at", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Danh mục nguồn tiếp nhận Lead."},
        {"name": "kipa_campaigns", "module": "CRM Consultation", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, campaign_code, campaign_name, start_date, end_date, budget, channel, status, created_at", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Chiến dịch tiếp thị & tuyển sinh."},
        {"name": "kipa_consultation_dossiers", "module": "CRM Consultation", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, consultation_code, customer_id, legacy_lead_id, lead_source_id, campaign_id, assigned_staff_id, stage, primary_interest, target_intake, estimated_budget, last_contacted_at, next_appointment_at, lost_reason, lost_notes, handover_status, handover_notes, handed_over_by, handed_over_at, accepted_by, accepted_at, created_service_dossier_id, created_at, updated_at", "fk": "customer_id -> kipa_customers, legacy_lead_id -> kipa_leads, lead_source_id -> kipa_lead_sources, campaign_id -> kipa_campaigns, assigned_staff_id -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Hồ sơ tư vấn riêng biệt, tách rời hồ sơ dịch vụ."},
        {"name": "kipa_consultation_interests", "module": "CRM Consultation", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, consultation_id, service_type, target_country, target_major, preferred_school, notes, created_at", "fk": "consultation_id -> kipa_consultation_dossiers ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Nhu cầu dịch vụ chi tiết của đợt tư vấn."},
        {"name": "kipa_consultation_activities", "module": "CRM Consultation", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, consultation_id, staff_id, activity_type, activity_time, outcome, content, scheduled_next_at, created_at", "fk": "consultation_id -> kipa_consultation_dossiers, staff_id -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Lịch sử cuộc gọi, tương tác Zalo, lịch hẹn."},

        # 5. Service Dossiers & Lifecycle (3 BẢNG)
        {"name": "kipa_service_dossiers", "module": "CRM Dossier", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, dossier_code, customer_id, consultation_dossier_id, service_type, program_id, target_country, target_intake, target_school_id, target_major, stage, progress_percent, consultant_id, case_officer_id, collaborator_id, contract_number, contract_signed_at, status, notes, created_at, updated_at", "fk": "customer_id -> kipa_customers ON DELETE RESTRICT, consultant_id -> kipa_staff, case_officer_id -> kipa_staff, collaborator_id -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Hồ sơ dịch vụ hợp đồng chính thức."},
        {"name": "kipa_dossier_assignments", "module": "CRM Dossier", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, dossier_id, staff_id, assignment_role, assigned_at, unassigned_at, is_current, note", "fk": "dossier_id -> kipa_service_dossiers, staff_id -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Lịch sử phân công & bàn giao hồ sơ."},
        {"name": "kipa_dossier_status_history", "module": "CRM Dossier", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, dossier_id, from_status, to_status, change_type, reason, changed_by, changed_at", "fk": "dossier_id -> kipa_service_dossiers, changed_by -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Vòng đời hồ sơ: Tạm dừng, Mở lại, Đóng, Lưu trữ."},

        # 6. Tasks, SLA & Working Calendars (5 BẢNG)
        {"name": "kipa_sla_policies", "module": "CRM SLA", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, policy_code, policy_name, service_type, branch_id, priority, response_time_hours, resolution_time_hours, is_active, created_at", "fk": "branch_id -> kipa_branches ON DELETE SET NULL", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Chính sách SLA theo dịch vụ và chi nhánh."},
        {"name": "kipa_working_calendars", "module": "CRM SLA", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, branch_id, calendar_date, is_working_day, start_time, end_time, lunch_start, lunch_end, holiday_name, created_at", "fk": "branch_id -> kipa_branches ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Lịch làm việc và ngày nghỉ lễ tính SLA theo giờ hành chính."},
        {"name": "kipa_tasks", "module": "CRM Task", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, parent_id, dossier_id, customer_id, title, description, assigned_to, created_by, priority, sla_policy_id, sla_hours, sla_status, sla_paused_at, sla_pause_reason, sla_total_paused_minutes, due_date, reminder_at, escalated_to, escalation_level, is_recurring, recurrence_pattern, recurrence_end_date, status, completed_at, completion_confirmed_by, completion_confirmed_at, completion_rating, created_at, updated_at", "fk": "parent_id -> kipa_tasks, dossier_id -> kipa_service_dossiers, customer_id -> kipa_customers, assigned_to -> kipa_staff, sla_policy_id -> kipa_sla_policies", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Nhiệm vụ con, SLA tạm dừng/tiếp tục, xác nhận hoàn thành."},
        {"name": "kipa_task_collaborators", "module": "CRM Task", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "task_id, staff_id, role_description, assigned_at", "fk": "task_id -> kipa_tasks, staff_id -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Người phối hợp trong nhiệm vụ."},
        {"name": "kipa_task_history", "module": "CRM Task", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, task_id, changed_by, field_name, old_value, new_value, reason, changed_at", "fk": "task_id -> kipa_tasks, changed_by -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Lịch sử thay đổi nhiệm vụ và gia hạn deadline."},

        # 7. Approvals & Workflows (4 BẢNG)
        {"name": "kipa_approval_workflows", "module": "CRM Approval", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, workflow_code, workflow_name, approval_type, version, requires_dual_approval, description, is_active, created_at", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Định nghĩa quy trình duyệt nhiều bước, phiên bản, duyệt kép."},
        {"name": "kipa_approval_workflow_steps", "module": "CRM Approval", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, workflow_id, step_order, step_name, required_role_id, specific_approver_id, sla_hours, is_final_step, created_at", "fk": "workflow_id -> kipa_approval_workflows, required_role_id -> kipa_roles, specific_approver_id -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Các bước trong quy trình phê duyệt."},
        {"name": "kipa_approvals", "module": "CRM Approval", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, dossier_id, workflow_id, step_id, approval_type, approval_level, snapshot_payload, requested_by, approver_id, next_approver_id, second_approver_id, status, comments, second_comments, reviewed_at, second_approved_at, created_at", "fk": "dossier_id -> kipa_service_dossiers, workflow_id -> kipa_approval_workflows, requested_by -> kipa_staff, approver_id -> kipa_staff, second_approver_id -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Snapshot dữ liệu, phê duyệt kép, chống tự duyệt."},
        {"name": "kipa_audit_logs", "module": "CRM System Log", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, actor_type, actor_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Nhật ký kiểm toán thao tác hệ thống."},

        # 8. Document Vault & Protected Access (4 BẢNG)
        {"name": "kipa_dossier_documents", "module": "CRM Docs", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, dossier_id, customer_id, doc_group, doc_code, doc_name, is_required, status, access_level, is_archived, current_version_id, notes, created_at, updated_at", "fk": "dossier_id -> kipa_service_dossiers ON DELETE RESTRICT, customer_id -> kipa_customers ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI (Master Vault). Bảo tồn tài liệu khi đóng hồ sơ (ON DELETE RESTRICT). Phân loại access_level."},
        {"name": "kipa_document_versions", "module": "CRM Docs", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, document_id, version_number, file_url, file_name, file_size, mime_type, uploaded_by_type, uploaded_by_id, uploaded_at, note", "fk": "document_id -> kipa_dossier_documents ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Quản lý file vật lý đa phiên bản trong thư mục bảo vệ kipa-private/storage/."},
        {"name": "kipa_document_reviews", "module": "CRM Docs", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, version_id, reviewer_id, review_status, review_note, rejection_reason, reviewed_at", "fk": "version_id -> kipa_document_versions, reviewer_id -> kipa_staff", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Lịch sử thẩm định và yêu cầu bổ sung."},
        {"name": "kipa_document_access_logs", "module": "CRM Docs", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, version_id, actor_type, actor_id, access_type, ip_address, user_agent, accessed_at", "fk": "version_id -> kipa_document_versions ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Nhật ký kiểm tra quyền xem, tải xuống và chia sẻ tài liệu nhạy cảm."},

        # 9. Student Portal (7 BẢNG + 1 VIEW)
        {"name": "kipa_students", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, email, customer_id, full_name, avatar_url, birthday, gender, phone, address, education_level, school_name, emergency_contact, student_status, current_program, program_code, country, registration_date, target_departure, dossier_status, progress_percent, advisor_name, advisor_role, advisor_phone, advisor_email, advisor_avatar, created_at, updated_at", "fk": "customer_id -> kipa_customers ON DELETE SET NULL", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Profile cache render giao diện portal tức thì."},
        {"name": "kipa_student_journey", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, dossier_id, student_email, step_number, title, status, progress_percent, description, deadline, in_charge, tasks_json, updated_at", "fk": "dossier_id -> kipa_service_dossiers ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. 5 mốc tiến độ hành trình du học."},
        {"name": "kipa_student_schedules", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, student_email, title, schedule_type, day_label, time_range, location, mentor, status", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Lịch học và lịch hẹn cá nhân."},
        {"name": "kipa_student_notifications", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, student_email, title, content, category, time_label, is_read, is_urgent, created_at", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Trung tâm thông báo đẩy có cờ khẩn cấp."},
        {"name": "kipa_student_materials", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, title, category, format, file_url, file_size, description", "fk": "Không có", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Thư viện giáo trình PDF tải về."},
        {"name": "kipa_student_support", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, student_email, ticket_code, subject, message, category, status, advisor_reply, assigned_staff_id, created_at, updated_at", "fk": "assigned_staff_id -> kipa_staff ON DELETE SET NULL", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Kênh ticket hỗ trợ 2 chiều."},
        {"name": "kipa_notification_settings", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, customer_id, email_notify, sms_notify, zalo_notify, portal_notify, updated_at", "fk": "customer_id -> kipa_customers ON DELETE CASCADE", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO MỚI. Cài đặt nhận thông báo của học viên."},
        {"name": "kipa_student_documents (VIEW)", "module": "Portal Document View", "exists": "CHƯA TỒN TẠI (VIEW)", "records": "View động", "existing_fields": "id, dossier_id, customer_id, user_id, student_email, doc_group, doc_code, doc_name, status, file_url, file_name, file_size, uploaded_at, note, updated_at", "missing_fields": "Không thiếu", "fk": "Ánh xạ từ kipa_dossier_documents JOIN kipa_customers LEFT JOIN kipa_document_versions", "type": "3. CHƯA CÓ, CẦN TẠO MỚI", "proposal": "TẠO VIEW HỢP NHẤT. Bổ sung user_id & customer_id, API lọc strictly theo session user_id chống lộ chéo tài liệu."},

        # 10. LMS Foundation (6 BẢNG)
        {"name": "kipa_lms_classes", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, class_code, class_name, course_id, teacher_id, start_date, end_date, schedule_info, room, status, created_at", "fk": "course_id -> kipa_courses, teacher_id -> kipa_staff", "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI", "proposal": "CHUẨN BỊ QUAN HỆ. Quản lý lớp tiếng Hàn giai đoạn 2."},
        {"name": "kipa_lms_lessons", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, class_id, lesson_number, lesson_title, lesson_date, start_time, end_time, room, teacher_id, notes", "fk": "class_id -> kipa_lms_classes ON DELETE CASCADE", "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI", "proposal": "CHUẨN BỊ QUAN HỆ. Lịch học chi tiết từng buổi."},
        {"name": "kipa_lms_enrollments", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, class_id, customer_id, student_id, enrolled_at, status, tuition_status, notes", "fk": "class_id -> kipa_lms_classes, customer_id -> kipa_customers", "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI", "proposal": "CHUẨN BỊ QUAN HỆ. Ghi danh học viên vào lớp."},
        {"name": "kipa_lms_attendance", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, lesson_id, enrollment_id, attendance_status, note, checked_by, checked_at", "fk": "lesson_id -> kipa_lms_lessons, enrollment_id -> kipa_lms_enrollments", "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI", "proposal": "CHUẨN BỊ QUAN HỆ. Điểm danh chuyên cần."},
        {"name": "kipa_lms_exams", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, exam_code, exam_name, class_id, exam_date, max_score, passing_score, exam_type", "fk": "class_id -> kipa_lms_classes", "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI", "proposal": "CHUẨN BỊ QUAN HỆ. Kỳ thi đánh giá năng lực định kỳ."},
        {"name": "kipa_lms_grades", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0", "existing_fields": "Chưa có", "missing_fields": "id, exam_id, enrollment_id, score, evaluation, examiner_id, graded_at", "fk": "exam_id -> kipa_lms_exams, enrollment_id -> kipa_lms_enrollments", "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI", "proposal": "CHUẨN BỊ QUAN HỆ. Bảng điểm thi của học viên."}
    ]

    # Add CMS 85 tables summary into sheet
    content_model_path = os.path.join(os.path.dirname(__file__), '..', 'api', 'content-model.json')
    if os.path.exists(content_model_path):
        with open(content_model_path, 'r', encoding='utf-8') as f:
            model = json.load(f)
            tables = model.get('tables', {})
            for t_name, t_meta in tables.items():
                fields = ", ".join(t_meta.get('fields', {}).keys())
                tables_to_audit.append({
                    "name": t_name,
                    "module": f"Website CMS ({t_meta.get('category', 'Nội dung')})",
                    "exists": "ĐÃ CÓ SCHEMA (Chưa tạo Prod)",
                    "records": "0",
                    "existing_fields": fields,
                    "missing_fields": "Chưa migrate lên DB",
                    "fk": "Xem DDL backend/content-schema.sql",
                    "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI",
                    "proposal": "Giữ nguyên thiết kế DDL trong content-schema.sql. Triển khai theo kế hoạch nội dung sau."
                })

    # Render rows in BangDoiChieu_ChiTiet
    for idx, row in enumerate(tables_to_audit, 1):
        r_type = row["type"]
        cells = [
            idx,
            row["name"],
            row["module"],
            row["exists"],
            row["records"],
            row["existing_fields"],
            row["missing_fields"],
            row["fk"],
            r_type,
            row["proposal"]
        ]
        ws_detail.append(cells)
        r_idx = idx + 1
        
        for c_idx in range(1, len(cells) + 1):
            cell = ws_detail.cell(row=r_idx, column=c_idx)
            cell.font = regular_font
            cell.border = thin_border
            
            if c_idx in [1, 4, 5]:
                cell.alignment = Alignment(horizontal="center", vertical="center")
            elif c_idx == 2:
                cell.font = code_font
                cell.alignment = Alignment(horizontal="left", vertical="center")
            elif c_idx == 9:
                cell.alignment = Alignment(horizontal="left", vertical="center")
                if "1." in r_type:
                    cell.fill, cell.font = fill_existing_complete, font_existing_complete
                elif "2." in r_type:
                    cell.fill, cell.font = fill_existing_ext, font_existing_ext
                elif "3." in r_type:
                    cell.fill, cell.font = fill_missing_req, font_missing_req
                else:
                    cell.fill, cell.font = fill_missing_opt, font_missing_opt
            else:
                cell.alignment = Alignment(horizontal="left", vertical="center", wrap_text=True)

    # Auto fit column widths
    ws_dash.column_dimensions["A"].width = 46
    ws_dash.column_dimensions["B"].width = 24
    ws_dash.column_dimensions["C"].width = 22
    ws_dash.column_dimensions["D"].width = 24
    ws_dash.column_dimensions["E"].width = 22
    ws_dash.column_dimensions["F"].width = 22
    ws_dash.column_dimensions["G"].width = 22

    ws_detail.column_dimensions["A"].width = 6
    ws_detail.column_dimensions["B"].width = 34
    ws_detail.column_dimensions["C"].width = 26
    ws_detail.column_dimensions["D"].width = 26
    ws_detail.column_dimensions["E"].width = 16
    ws_detail.column_dimensions["F"].width = 40
    ws_detail.column_dimensions["G"].width = 36
    ws_detail.column_dimensions["H"].width = 38
    ws_detail.column_dimensions["I"].width = 32
    ws_detail.column_dimensions["J"].width = 48

    out_path = "DATABASE_FIELD_AUDIT.xlsx"
    wb.save(out_path)
    print(f"XUẤT THÀNH CÔNG: {out_path} (Tổng cộng: {len(tables_to_audit)} bảng và view)")

if __name__ == "__main__":
    build_excel_audit()
