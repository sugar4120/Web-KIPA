# -*- coding: utf-8 -*-
"""
Script to generate DATABASE_FIELD_AUDIT.xlsx
Senior Database Architect Audit & Gap Analysis for KIPA Website + CRM + Student Portal + LMS
Version: 2.1.0 (Final Verification)
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
    ws_dash["A2"] = "Phân hệ: Website KIPA + CMS Nội Dung + KIPA CRM + KIPA Student Portal + LMS Tương Lai (v2.1.0)"
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
        ["4. KIPA CRM (Tổ chức, RBAC, Khách hàng, Hồ sơ, SLA, Phê duyệt)", 21, 0, 0, 21, 0, 0],
        ["5. KIPA Student Portal (Hồ sơ học viên, Lộ trình, Lịch, Support + 1 View)", 7, 0, 0, 0, 7, 0],
        ["6. KIPA LMS (Đào Tạo & Lớp Học - Giai Đoạn Sau)", 6, 0, 0, 0, 0, 6],
        ["TỔNG CỘNG BẢNG VẬT LÝ TOÀN HỆ THỐNG (Kèm 1 SQL View)", 125, 5, 86, 21, 7, 6]
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
        ["1. ĐÃ CÓ VÀ ĐỦ", "Bảng hiện tại trên cPanel đã đủ phục vụ Website (Rate limit, OTP, Admin auth)", 3, "2.4%", "Bảo toàn tuyệt đối, không thay đổi cấu trúc, không can thiệp."],
        ["2. ĐÃ CÓ NHƯNG THIẾU TRƯỜNG", "Đã có nhưng cần thêm trường FK liên kết CRM/Portal (kipa_leads, kipa_users)", 2, "1.6%", "Thêm cột nullable (converted_to_customer_id, is_active, last_login_at); bảo vệ chống cascade delete."],
        ["3. CHƯA CÓ, CẦN TẠO MỚI", "28 bảng vật lý + 1 SQL View để CRM và Student Portal vận hành đồng bộ", "28 bảng + 1 view", "23.0%", "Thực thi bằng crm_portal_schema.sql (InnoDB, utf8mb4, BIGINT UNSIGNED, ON DELETE SET NULL an toàn)."],
        ["4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI", "85 bảng CMS nội dung + 1 tracker + 6 bảng LMS", 92, "73.0%", "Giữ nguyên thiết kế staging trong content-schema.sql & LMS, chỉ migrate khi đưa CMS/LMS vào khai thác."],
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
            "missing_fields": "converted_to_customer_id, customer_status",
            "fk": "Không có FK ra ngoài. Bị kipa_form_submissions tham chiếu.",
            "type": "2. ĐÃ CÓ NHƯNG THIẾU TRƯỜNG",
            "proposal": "BẢO TOÀN DỮ LIỆU. Bổ sung cột nullable converted_to_customer_id để liên kết sang CRM. Sửa logic cleanup.php không xóa lead đã thành khách hàng."
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

        # 2. CRM Core Tables (MỚI)
        {
            "name": "kipa_branches", "module": "CRM Organization", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, branch_code, branch_name, phone, email, address, province, country, manager_name, is_active, created_at",
            "fk": "Không có",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quản lý 7 chi nhánh KIPA (Trụ sở Hải Phòng, Hà Nội, TP.HCM, VPĐD Seoul, Sydney, v.v.)."
        },
        {
            "name": "kipa_departments", "module": "CRM Organization", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, branch_id, dept_code, dept_name, description, is_active, created_at",
            "fk": "branch_id -> kipa_branches(id) ON DELETE SET NULL",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Phân chia phòng ban: Tuyển sinh, Xử lý hồ sơ, Đào tạo tiếng Hàn, Đối ngoại."
        },
        {
            "name": "kipa_teams", "module": "CRM Organization", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, dept_id, team_code, team_name, leader_id, description, is_active, created_at",
            "fk": "dept_id -> kipa_departments(id) ON DELETE CASCADE",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quản lý nhóm chuyên môn (Team Hàn 1, Team Du học nghề Úc, Team Visa)."
        },
        {
            "name": "kipa_staff", "module": "CRM Organization", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, staff_code, admin_id, full_name, email, phone, branch_id, dept_id, team_id, position, avatar_url, is_active, created_at, updated_at",
            "fk": "admin_id -> kipa_admins(id) ON DELETE SET NULL, branch_id -> kipa_branches(id), dept_id -> kipa_departments(id), team_id -> kipa_teams(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Lưu thông tin cán bộ cố vấn đồng hành cùng học viên (Cô Trần Thu Hà, Chuyên viên xử lý hồ sơ...)."
        },
        {
            "name": "kipa_roles", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, role_code, role_name, description, is_system, created_at",
            "fk": "Không có",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Định nghĩa vai trò: ADMIN, DIRECTOR, CONSULTANT, CASE_OFFICER, TEACHER, ACCOUNTANT."
        },
        {
            "name": "kipa_permissions", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, permission_code, permission_name, module_name, description",
            "fk": "Không có",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quyền hạn chi tiết trên CRM (xem lead, gán hồ sơ, duyệt giấy tờ, xuất hợp đồng)."
        },
        {
            "name": "kipa_staff_roles", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "staff_id, role_id, scope, granted_by, assigned_at, expires_at",
            "fk": "staff_id -> kipa_staff(id) ON DELETE CASCADE, role_id -> kipa_roles(id) ON DELETE CASCADE, granted_by -> kipa_staff(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Bảng gán vai trò có hỗ trợ quyền tạm thời (expires_at) và người cấp (granted_by)."
        },
        {
            "name": "kipa_role_delegations", "module": "CRM RBAC", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, delegator_id, delegatee_id, role_id, start_date, end_date, reason, is_active, created_at",
            "fk": "delegator_id -> kipa_staff(id), delegatee_id -> kipa_staff(id), role_id -> kipa_roles(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Ủy quyền quyền hạn khi nhân sự nghỉ phép/công tác mà không cần đổi tài khoản."
        },
        {
            "name": "kipa_customers", "module": "CRM Core", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, customer_code, full_name, phone, email, birthday, gender, id_card_number, id_card_date, id_card_place, user_id, legacy_lead_id, lead_source_id, assigned_staff_id, branch_id, customer_status, notes, created_by, created_at, updated_at",
            "fk": "user_id -> kipa_users(id) ON DELETE SET NULL, legacy_lead_id -> kipa_leads(id) ON DELETE SET NULL, assigned_staff_id -> kipa_staff(id), branch_id -> kipa_branches(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Thực thể trung tâm CRM. Khắc phục triệt để cascade xóa lead (ON DELETE SET NULL). Liên kết 1-1 với user."
        },
        {
            "name": "kipa_customer_addresses", "module": "CRM Core", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, customer_id, address_type, street_address, ward, district, province, is_default, created_at",
            "fk": "customer_id -> kipa_customers(id) ON DELETE CASCADE",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quản lý hộ khẩu thường trú, tạm trú của học viên phục vụ thủ tục dịch thuật công chứng và hồ sơ visa."
        },
        {
            "name": "kipa_customer_relations", "module": "CRM Core", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, customer_id, relation_type, full_name, phone, email, occupation, birth_year, address, is_emergency, created_at",
            "fk": "customer_id -> kipa_customers(id) ON DELETE CASCADE",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Lưu thông tin bố mẹ/vợ chồng phục vụ chứng minh tài chính, bảo lãnh visa và người liên hệ khẩn cấp."
        },
        {
            "name": "kipa_customer_education", "module": "CRM Core", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, customer_id, education_level, school_name, major, graduation_year, gpa, language_certificate, language_score, certificate_date, created_at",
            "fk": "customer_id -> kipa_customers(id) ON DELETE CASCADE",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quản lý điểm GPA học bạ cấp 3, đại học và chứng chỉ tiếng (TOPIK/IELTS/KLAT) để đối chiếu điều kiện nhập học."
        },
        {
            "name": "kipa_service_dossiers", "module": "CRM Dossier", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, dossier_code, customer_id, service_type, program_id, target_country, target_intake, target_school_id, target_major, stage, progress_percent, consultant_id, case_officer_id, collaborator_id, contract_number, contract_signed_at, status, notes, created_at, updated_at",
            "fk": "customer_id -> kipa_customers(id) ON DELETE RESTRICT, consultant_id -> kipa_staff(id), case_officer_id -> kipa_staff(id), collaborator_id -> kipa_staff(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Trọng tâm của hệ thống hồ sơ dịch vụ. Phản ánh trực tiếp lên thanh tiến độ và Dashboard của Student Portal."
        },
        {
            "name": "kipa_dossier_assignments", "module": "CRM Dossier", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, dossier_id, staff_id, assignment_role, assigned_at, unassigned_at, is_current, note",
            "fk": "dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE, staff_id -> kipa_staff(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Ghi nhận lịch sử phân công và chuyển giao phụ trách hồ sơ (Tư vấn, Xử lý hồ sơ, Cộng tác viên CTV)."
        },
        {
            "name": "kipa_dossier_status_history", "module": "CRM Dossier", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, dossier_id, from_status, to_status, change_type, reason, changed_by, changed_at",
            "fk": "dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE, changed_by -> kipa_staff(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quản lý vòng đời hồ sơ: Tạm dừng, Mở lại, Đóng và Lưu trữ (Archived) kèm lý do chi tiết."
        },
        {
            "name": "kipa_tasks", "module": "CRM Task", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, dossier_id, customer_id, title, description, assigned_to, created_by, priority, sla_hours, due_date, reminder_at, escalated_to, status, completed_at, created_at, updated_at",
            "fk": "dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE, customer_id -> kipa_customers(id), assigned_to -> kipa_staff(id), escalated_to -> kipa_staff(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quản lý việc cần làm, cam kết SLA phản hồi, cơ chế nhắc hạn (reminder_at) và báo cáo vượt cấp (escalated_to)."
        },
        {
            "name": "kipa_approvals", "module": "CRM Approval", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, dossier_id, approval_type, approval_level, requested_by, approver_id, next_approver_id, status, comments, created_at, reviewed_at",
            "fk": "dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE, requested_by -> kipa_staff(id), approver_id -> kipa_staff(id), next_approver_id -> kipa_staff(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quy trình duyệt hồ sơ nhiều cấp (Trưởng nhóm -> Giám đốc chi nhánh -> Ban giám đốc) trước khi nộp trường/visa."
        },
        {
            "name": "kipa_audit_logs", "module": "CRM System Log", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, actor_type, actor_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at",
            "fk": "Không có",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Ghi nhật ký thao tác sửa thông tin, đổi trạng thái, tải tài liệu để đảm bảo an toàn dữ liệu trên shared hosting."
        },

        # Master Document Vault
        {
            "name": "kipa_dossier_documents", "module": "CRM / Portal Docs", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, dossier_id, customer_id, doc_group, doc_code, doc_name, is_required, status, current_version_id, updated_at",
            "fk": "dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE, customer_id -> kipa_customers(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI (Kho tài liệu duy nhất). Checklist giấy tờ chuẩn hoá cho cả CRM và Portal. Trạng thái: Chưa tải, Đã tải, Đang kiểm tra, Cần bổ sung, Đã xác nhận."
        },
        {
            "name": "kipa_document_versions", "module": "CRM / Portal Docs", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, document_id, version_number, file_url, file_name, file_size, mime_type, uploaded_by_type, uploaded_by_id, uploaded_at, note",
            "fk": "document_id -> kipa_dossier_documents(id) ON DELETE CASCADE",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quản lý lịch sử tải file đa phiên bản (v1, v2, v3...) khi học viên nộp lại giấy tờ bị mờ hoặc sai quy cách."
        },
        {
            "name": "kipa_document_reviews", "module": "CRM / Portal Docs", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, version_id, reviewer_id, review_status, review_note, rejection_reason, reviewed_at",
            "fk": "version_id -> kipa_document_versions(id) ON DELETE CASCADE, reviewer_id -> kipa_staff(id)",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Lưu lại lý do yêu cầu bổ sung giấy tờ và lịch sử thẩm định của chuyên viên KIPA."
        },

        # Student Portal Tables
        {
            "name": "kipa_students", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, email, customer_id, full_name, avatar_url, birthday, gender, phone, address, education_level, school_name, emergency_contact, student_status, current_program, program_code, country, registration_date, target_departure, dossier_status, progress_percent, advisor_name, advisor_role, advisor_phone, advisor_email, advisor_avatar, created_at, updated_at",
            "fk": "customer_id -> kipa_customers(id) ON DELETE SET NULL",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Bảng cache hồ sơ học viên độc lập giúp Student Portal render cực nhanh mà không cần JOIN phức tạp."
        },
        {
            "name": "kipa_student_journey", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, dossier_id, student_email, step_number, title, status, progress_percent, description, deadline, in_charge, tasks_json, updated_at",
            "fk": "dossier_id -> kipa_service_dossiers(id) ON DELETE CASCADE",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. 5 mốc tiến độ hành trình du học hiển thị trực tiếp trên giao diện portal của học viên."
        },
        {
            "name": "kipa_student_schedules", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, student_email, title, schedule_type, day_label, time_range, location, mentor, status",
            "fk": "Không có",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Lịch học tiếng Hàn, lịch hẹn cố vấn, lịch phỏng vấn visa."
        },
        {
            "name": "kipa_student_notifications", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, student_email, title, content, category, time_label, is_read, is_urgent, created_at",
            "fk": "Không có",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Trung tâm thông báo đẩy cho học viên (nhắc nộp hồ sơ, đổi phòng học...)."
        },
        {
            "name": "kipa_student_materials", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, title, category, format, file_url, file_size, description",
            "fk": "Không có",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Kho tài liệu học tập PDF (sách tiếng Hàn, cẩm nang xuất cảnh)."
        },
        {
            "name": "kipa_student_support", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, student_email, ticket_code, subject, message, category, status, advisor_reply, assigned_staff_id, created_at, updated_at",
            "fk": "assigned_staff_id -> kipa_staff(id) ON DELETE SET NULL",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Kênh ticket hỗ trợ giải đáp thắc mắc giữa học viên và nhân sự phụ trách."
        },
        {
            "name": "kipa_notification_settings", "module": "Student Portal", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, customer_id, email_notify, sms_notify, zalo_notify, portal_notify, updated_at",
            "fk": "customer_id -> kipa_customers(id) ON DELETE CASCADE",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO MỚI. Quản lý cấu hình nhận thông báo qua Email, SMS, Zalo của từng khách hàng/học viên."
        },
        {
            "name": "kipa_student_documents (VIEW)", "module": "Portal Document View", "exists": "CHƯA TỒN TẠI (VIEW)", "records": "View động",
            "existing_fields": "id, student_email, doc_group, doc_name, status, file_url, file_name, file_size, uploaded_at, note, updated_at",
            "missing_fields": "Không thiếu",
            "fk": "Ánh xạ từ kipa_dossier_documents JOIN kipa_document_versions JOIN kipa_customers",
            "type": "3. CHƯA CÓ, CẦN TẠO MỚI",
            "proposal": "TẠO VIEW ĐỒNG BỘ. Thay vì tạo bảng vật lý trùng lặp gây xung đột dữ liệu, dùng View để giữ 100% tương thích với API cổng học viên."
        },

        # LMS Foundation
        {
            "name": "kipa_lms_classes", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, class_code, class_name, course_id, teacher_id, start_date, end_date, schedule_info, room, status, created_at",
            "fk": "course_id -> kipa_courses(id), teacher_id -> kipa_staff(id)",
            "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI",
            "proposal": "CHUẨN BỊ QUAN HỆ. Quản lý lớp học tiếng Hàn (TOPIK 1, 2, EPS-TOPIK). Triển khai ở giai đoạn 2."
        },
        {
            "name": "kipa_lms_lessons", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, class_id, lesson_number, lesson_title, lesson_date, start_time, end_time, room, teacher_id, notes",
            "fk": "class_id -> kipa_lms_classes(id) ON DELETE CASCADE",
            "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI",
            "proposal": "CHUẨN BỊ QUAN HỆ. Lịch học chi tiết từng buổi."
        },
        {
            "name": "kipa_lms_enrollments", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, class_id, customer_id, student_id, enrolled_at, status, tuition_status, notes",
            "fk": "class_id -> kipa_lms_classes(id), customer_id -> kipa_customers(id)",
            "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI",
            "proposal": "CHUẨN BỊ QUAN HỆ. Ghi danh học viên vào lớp tiếng Hàn."
        },
        {
            "name": "kipa_lms_attendance", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, lesson_id, enrollment_id, attendance_status, note, checked_by, checked_at",
            "fk": "lesson_id -> kipa_lms_lessons(id), enrollment_id -> kipa_lms_enrollments(id)",
            "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI",
            "proposal": "CHUẨN BỊ QUAN HỆ. Điểm danh chuyên cần (Có mặt, Đi muộn, Có phép, Vắng)."
        },
        {
            "name": "kipa_lms_exams", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, exam_code, exam_name, class_id, exam_date, max_score, passing_score, exam_type",
            "fk": "class_id -> kipa_lms_classes(id)",
            "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI",
            "proposal": "CHUẨN BỊ QUAN HỆ. Kỳ thi đánh giá năng lực định kỳ và thi thử EPS-TOPIK."
        },
        {
            "name": "kipa_lms_grades", "module": "LMS (Giai đoạn sau)", "exists": "CHƯA TỒN TẠI", "records": "0",
            "existing_fields": "Chưa có",
            "missing_fields": "id, exam_id, enrollment_id, score, evaluation, examiner_id, graded_at",
            "fk": "exam_id -> kipa_lms_exams(id), enrollment_id -> kipa_lms_enrollments(id)",
            "type": "4. ĐÃ CÓ TRONG THIẾT KẾ NHƯNG CHƯA CẦN TRIỂN KHAI",
            "proposal": "CHUẨN BỊ QUAN HỆ. Kết quả điểm thi và tiến độ học tập của học viên."
        }
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
    ws_detail.column_dimensions["B"].width = 32
    ws_detail.column_dimensions["C"].width = 26
    ws_detail.column_dimensions["D"].width = 26
    ws_detail.column_dimensions["E"].width = 16
    ws_detail.column_dimensions["F"].width = 38
    ws_detail.column_dimensions["G"].width = 34
    ws_detail.column_dimensions["H"].width = 36
    ws_detail.column_dimensions["I"].width = 30
    ws_detail.column_dimensions["J"].width = 46

    out_path = "DATABASE_FIELD_AUDIT.xlsx"
    wb.save(out_path)
    print(f"XUẤT THÀNH CÔNG: {out_path} (Tổng cộng: {len(tables_to_audit)} bảng và view)")

if __name__ == "__main__":
    build_excel_audit()
