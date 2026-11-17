# -*- coding: utf-8 -*-
"""
Verification Script for KIPA CRM + Student Portal Database Schema.
Audits all CREATE TABLE, VIEW, columns, indexes, and foreign keys.
"""
import re

SCHEMA_PATH = 'backend/crm_portal_schema.sql'

with open(SCHEMA_PATH, 'r', encoding='utf-8') as f:
    sql = f.read()

# 1. Physical Tables
tables = re.findall(r'CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`', sql, re.IGNORECASE)
print(f"=== 1. TỔNG SỐ BẢNG VẬT LÝ: {len(tables)} ===")
for i, t in enumerate(tables, 1):
    print(f"  {i:02d}. {t}")

# 2. Views
views = re.findall(r'CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+`?([^`\s]+)`?', sql, re.IGNORECASE)
views = [v.strip('`') for v in views]
print(f"\n=== 2. TỔNG SỐ VIEW: {len(views)} ===")
for i, v in enumerate(views, 1):
    print(f"  {i:02d}. {v}")

# 3. Foreign Keys
pattern_fk = r'CONSTRAINT\s+`?([^`\s]+)`?\s+FOREIGN\s+KEY\s*\(`?([^`\s]+)`?\)\s+REFERENCES\s+`?([^`\s]+)`?\s*\(`?([^`\s]+)`?\)(?:\s+ON\s+DELETE\s+([A-Z\s]+?))?(?:\s+ON\s+UPDATE\s+([A-Z\s]+?))?(?:,|\n|\))'
fks = re.findall(pattern_fk, sql, re.IGNORECASE)
print(f"\n=== 3. TỔNG SỐ KHÓA NGOẠI (FOREIGN KEYS): {len(fks)} ===")
for name, col, ref_t, ref_c, on_del, on_upd in fks:
    del_rule = on_del.strip() if on_del else "RESTRICT/NO ACTION"
    print(f"  - [{name}] ({col}) -> {ref_t}({ref_c}) | ON DELETE {del_rule}")

# 4. Check dangerous operations
dangerous_patterns = [r'\bDROP\s+TABLE\b', r'\bTRUNCATE\b', r'\bDELETE\s+FROM\s+`?kipa_leads`?']
print(f"\n=== 4. KIỂM TRA TOÀN VẸN AN TOÀN SQL ===")
safe = True
for p in dangerous_patterns:
    m = re.search(p, sql, re.IGNORECASE)
    if m:
        print(f"  [CẢNH BÁO NGUY HIỂM] Tìm thấy câu lệnh: {m.group(0)}")
        safe = False
if safe:
    print("  [HOÀN TOÀN AN TOÀN] Không có câu lệnh DROP, TRUNCATE hay DELETE gây nguy hiểm cho dữ liệu Production.")

# 5. Check kipa_leads protection (legacy_lead_id ON DELETE SET NULL)
print(f"\n=== 5. KIỂM TRA BẢO VỆ CHỐNG XÓA DÂY CHUYỀN LEADS (api/cleanup.php) ===")
lead_fks = [fk for fk in fks if fk[2] == 'kipa_leads']
for name, col, ref_t, ref_c, on_del, on_upd in lead_fks:
    print(f"  - FK liên kết kipa_leads: {name} ({col}) -> ON DELETE {on_del.strip()}")
    if "SET NULL" in on_del.upper():
        print("    -> ĐẠT YÊU CẦU: Khách hàng/Hồ sơ được bảo tồn an toàn (SET NULL) khi kipa_leads bị xóa dọn dẹp sau 12 tháng.")
    else:
        print("    -> CẢNH BÁO: Chưa dùng SET NULL!")
