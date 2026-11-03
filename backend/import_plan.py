#!/usr/bin/env python3
"""Generate the CMS data dictionary from the KIPA database worksheet, never account sheets."""

import argparse
import json
import re
import xml.etree.ElementTree as ET
import zipfile
from pathlib import Path

NS = {'m': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
GROUPS = {
    'A': 'Chương trình và dịch vụ', 'B': 'Quốc gia, trường học và đối tác',
    'C': 'Điều kiện, chi phí và hồ sơ', 'D': 'Khóa học tiếng Hàn',
    'E': 'Cơ sở KIPA', 'F': 'Tin tức và nội dung', 'G': 'Đăng ký và liên hệ',
    'H': 'FAQ và chính sách', 'I': 'Đánh giá và câu chuyện', 'J': 'SEO và hệ thống',
}
EXTRA_STARTS = {
    506: 'school_countries', 622: 'partner_types', 878: 'intake_milestones',
    921: 'document_categories', 3148: 'media_usages',
}
REFERENCES = {
    'study_abroad_id': 'study_abroad_programs', 'study_program_id': 'study_program_details',
    'study_program_detail_id': 'study_program_details', 'work_program_id': 'work_programs',
    'roadmap_id': 'course_roadmaps', 'gallery_id': 'media_galleries',
    'interest_id': 'consultation_interests', 'assigned_to': 'admins',
    'performed_by': 'admins', 'changed_by': 'admins', 'updated_by': 'admins',
    'uploaded_by': 'admins', 'verified_by': 'admins',
}


def read_rows(workbook):
    with zipfile.ZipFile(workbook) as archive:
        sheets = ET.fromstring(archive.read('xl/workbook.xml')).find('m:sheets', NS)
        sheet = next(s for s in sheets if s.get('name') == 'Database - Cơ sở dữ liệu KIPA')
        relations = ET.fromstring(archive.read('xl/_rels/workbook.xml.rels'))
        relation_id = sheet.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
        target = next(r.get('Target') for r in relations if r.get('Id') == relation_id)
        target = target.lstrip('/') if target.startswith('/') else 'xl/' + target
        strings = [
            ''.join(item.itertext())
            for item in ET.fromstring(archive.read('xl/sharedStrings.xml')).findall('m:si', NS)
        ]
        rows = []
        for row in ET.fromstring(archive.read(target)).findall('m:sheetData/m:row', NS):
            cells = {'row': int(row.get('r'))}
            for cell in row:
                value = cell.find('m:v', NS)
                if value is not None:
                    cells[re.sub(r'\d', '', cell.get('r'))] = (
                        strings[int(value.text)] if cell.get('t') == 's' else value.text
                    )
            rows.append(cells)
        return rows


def sql_type(raw):
    raw = raw.upper()
    if raw == 'YEAR':
        return 'SMALLINT'
    if 'UUID' in raw or raw.startswith('FK'):
        return 'BIGINT UNSIGNED'
    match = re.match(r'(VARCHAR\(\d+\)|CHAR\(\d+\)|DECIMAL\(\d+,\s*\d+\)|'
                     r'BIGINT|SMALLINT|TINYINT|INT|BOOLEAN|LONGTEXT|TEXT|JSON|DATETIME|TIMESTAMP|DATE|TIME)', raw)
    if raw.startswith('ENUM'):
        return 'VARCHAR(50)'
    if not match:
        raise ValueError(f'Unsupported worksheet type: {raw}')
    return match[0]


def extract_model(rows):
    tables = {}
    current = None
    group = 'A'
    started = False
    heading = re.compile(
        r'^(?:\d+(?:\.\d+)?\.\s*(?:Bảng\s*)?|Bảng\s+\d+\.\s*)'
        r'([a-z][a-z0-9_]+)\s*[–—-]'
    )
    for row in rows:
        name, raw = row.get('A', '').strip(), row.get('B', '').strip()
        if re.match(r'^[A-J]\. Nhóm', name):
            group = name[0]
            current = None
        match = heading.match(name)
        table_name = EXTRA_STARTS.get(row['row']) or (match[1] if match else None)
        if table_name:
            current = {
                'group': group, 'label': name.split('–')[-1].strip(),
                'source_row': row['row'], 'fields': {},
            }
            tables[table_name] = current
            started = False
            continue
        is_field = re.fullmatch(r'[a-z][a-z0-9_]*', name) and re.match(
            r'^(UUID|BIGINT|VARCHAR|TEXT|LONGTEXT|ENUM|BOOLEAN|DATETIME|DATE|TIME|'
            r'INT|SMALLINT|TINYINT|DECIMAL|JSON|FK|CHAR|TIMESTAMP|YEAR)', raw, re.I)
        if current and is_field:
            if name == 'id' and started:
                current = None
                continue
            started = True
            current['fields'][name] = {
                'type': sql_type(raw),
                'required': row.get('C') == 'Có',
                'label': row.get('D') or row.get('C') or name,
            }
        elif current and started:
            # The video interview section explicitly extends the same shared table.
            if 2752 <= row['row'] <= 2754:
                continue
            current = None

    tables.pop('school_countries')
    language = tables.pop('language_courses')
    courses = tables['courses']['fields']
    for field in ('entry_requirement', 'outcome', 'syllabus', 'class_size'):
        courses[field] = language['fields'][field]
    courses['program_id'] = {'type': 'BIGINT UNSIGNED', 'required': False, 'label': 'Chương trình liên kết'}
    # Keep one source of truth for SEO and source assets.
    for table_name, table in tables.items():
        if table_name == 'seo_metadata':
            continue
        for field in ('seo_title', 'seo_description', 'canonical_url', 'noindex'):
            table['fields'].pop(field, None)
    for table_name, table in tables.items():
        for name, field in table['fields'].items():
            target = REFERENCES.get(name)
            if name == 'parent_id':
                target = table_name
            if name == 'intake_id':
                target = 'course_intakes' if table_name.startswith('course_') else 'program_intakes'
            if name == 'category_id':
                target = 'post_categories' if table_name.startswith('post_') else 'faq_categories'
            if name == 'tag_id':
                target = 'post_tags'
            if name == 'service_id':
                target = 'location_services'
            if not target and name.endswith('_id'):
                base = name[:-3]
                target = next((candidate for candidate in (base + 's', base + 'es') if candidate in tables), None)
            if target:
                field['ref'] = target
                field['type'] = 'BIGINT UNSIGNED'
            if name == 'id':
                field.update(type='BIGINT UNSIGNED', required=True)
            if name in ('status', 'publication_status'):
                field['default'] = 'draft' if table['group'] not in ('G',) else 'new'
            if name == 'status' and 'active / inactive' in field['label']:
                field['default'] = 'active'
            if field['type'] == 'BOOLEAN':
                field['default'] = 1 if name in ('robots_index', 'robots_follow') else 0
            if name in ('display_order', 'level_order', 'hit_count'):
                field['default'] = 0
            options = [item.strip() for item in field['label'].split('/')]
            if len(options) > 1 and all(re.fullmatch(r'[A-Za-z0-9_ -]+', item) for item in options):
                field['choices'] = options
                if name in ('status', 'publication_status') and field.get('default') not in options:
                    field['default'] = options[0]
    # Keep the existing lead store during transition, with cascade-safe, idempotent backfill.
    for table_name in ('consultation_requests', 'contact_messages', 'course_registrations', 'form_submissions'):
        tables[table_name]['fields']['legacy_lead_id'] = {
            'type': 'BIGINT UNSIGNED', 'required': False,
            'label': 'Đăng ký gốc (tương thích hệ thống hiện tại)', 'ref': 'leads',
        }
    for table_name in ('consultation_requests', 'contact_messages', 'course_registrations', 'event_registrations'):
        fields = tables[table_name]['fields']
        fields['phone']['type'] = 'VARCHAR(32)'
        fields['email']['required'] = False
        fields['privacy_consent'] = {'type': 'BOOLEAN', 'required': True, 'default': 0, 'label': 'Đồng ý chính sách bảo mật'}
        fields['consent_at'] = {'type': 'DATETIME', 'required': False, 'label': 'Thời điểm đồng ý'}
        fields['source_page'] = {'type': 'VARCHAR(1000)', 'required': False, 'label': 'Trang gửi biểu mẫu'}
    # Existing course enquiries do not yet choose a database-backed course.
    tables['course_registrations']['fields']['course_id']['required'] = False
    tables['redirects']['fields']['source_url']['type'] = 'VARCHAR(500)'
    for table_name in ('program_requirements', 'program_fees', 'program_documents', 'program_process_steps', 'program_intakes'):
        tables[table_name]['one_of'] = ['study_program_id', 'work_program_id', 'course_id']
    tables['form_submissions']['fields']['payload_json'] = {
        'type': 'JSON', 'required': False, 'label': 'Nội dung gửi gốc (chỉ quản trị viên)',
    }
    for table_name in ('testimonials', 'success_stories'):
        tables[table_name]['fields']['consent_confirmed'] = {
            'type': 'BOOLEAN', 'required': True, 'default': 0, 'label': 'Đã được đồng ý công khai',
        }
    return {'version': 1, 'groups': GROUPS, 'tables': tables}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('workbook', type=Path)
    parser.add_argument('--output', type=Path, default=Path(__file__).parents[1] / 'api/content-model.json')
    args = parser.parse_args()
    model = extract_model(read_rows(args.workbook))
    for name, table in model['tables'].items():
        if 'id' not in table['fields']:
            raise ValueError(f'Missing primary key: {name}')
    args.output.write_text(json.dumps(model, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f"Generated {len(model['tables'])} tables across groups A–J; no account sheets read.")


if __name__ == '__main__':
    main()
