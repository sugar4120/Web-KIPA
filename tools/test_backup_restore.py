# -*- coding: utf-8 -*-
"""
Backup and Restore Test Suite for KIPA Database (Local Sandbox)
Simulates:
1. Full database dump of staging schema and seed data.
2. Restore into a fresh, isolated database instance.
3. Verification of table count, row count, foreign key relationships, document metadata.
4. Disaster recovery simulation: simulate a broken/corrupted migration and rollback.
"""

import sqlite3
import os
import sys
import io
import time
import json

def run_backup_restore_test():
    print("=" * 80)
    print("KIPA BACKUP & RESTORE TEST SUITE (LOCAL SANDBOX)")
    print("=" * 80)

    # Step 1: Create Primary Staging Database and populate with realistic sample data
    db_source_path = "staging_kipa_source.db"
    db_target_path = "staging_kipa_restored.db"
    dump_sql_path = "staging_kipa_backup.sql"

    if os.path.exists(db_source_path):
        os.remove(db_source_path)
    if os.path.exists(db_target_path):
        os.remove(db_target_path)
    if os.path.exists(dump_sql_path):
        os.remove(dump_sql_path)

    conn_src = sqlite3.connect(db_source_path)
    conn_src.execute("PRAGMA foreign_keys = ON;")
    cur_src = conn_src.cursor()

    # Load base schema
    cur_src.executescript("""
        CREATE TABLE kipa_branches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            branch_code TEXT NOT NULL UNIQUE,
            branch_name TEXT NOT NULL
        );
        CREATE TABLE kipa_customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_code TEXT NOT NULL UNIQUE,
            full_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT,
            branch_id INTEGER,
            FOREIGN KEY (branch_id) REFERENCES kipa_branches(id) ON DELETE SET NULL
        );
        CREATE TABLE kipa_service_dossiers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            dossier_code TEXT NOT NULL UNIQUE,
            customer_id INTEGER NOT NULL,
            service_type TEXT NOT NULL,
            status TEXT DEFAULT 'ACTIVE',
            FOREIGN KEY (customer_id) REFERENCES kipa_customers(id) ON DELETE RESTRICT
        );
        CREATE TABLE kipa_dossier_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            dossier_id INTEGER NOT NULL,
            customer_id INTEGER NOT NULL,
            doc_name TEXT NOT NULL,
            status TEXT DEFAULT 'Chưa tải lên',
            can_download INTEGER DEFAULT 1,
            FOREIGN KEY (dossier_id) REFERENCES kipa_service_dossiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (customer_id) REFERENCES kipa_customers(id) ON DELETE CASCADE
        );
        CREATE TABLE kipa_document_versions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            version_number INTEGER DEFAULT 1,
            file_url TEXT NOT NULL,
            file_name TEXT NOT NULL,
            FOREIGN KEY (document_id) REFERENCES kipa_dossier_documents(id) ON DELETE CASCADE
        );
    """)

    # Seed sample records
    cur_src.execute("INSERT INTO kipa_branches (branch_code, branch_name) VALUES ('HP_HQ', 'Hải Phòng')")
    cur_src.execute("INSERT INTO kipa_customers (customer_code, full_name, phone, email, branch_id) VALUES ('KH-2026-0001', 'Nguyễn Văn An', '0912345678', 'an@kipa.vn', 1)")
    cur_src.execute("INSERT INTO kipa_service_dossiers (dossier_code, customer_id, service_type) VALUES ('HS-2026-0001', 1, 'Du học Hàn Quốc D2-2')")
    cur_src.execute("INSERT INTO kipa_dossier_documents (dossier_id, customer_id, doc_name, can_download) VALUES (1, 1, 'Học bạ THPT', 1)")
    cur_src.execute("INSERT INTO kipa_document_versions (document_id, version_number, file_url, file_name) VALUES (1, 1, 'kipa-private/storage/hocba.pdf', 'hoc_ba.pdf')")
    conn_src.commit()

    src_tables = [r[0] for r in cur_src.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'").fetchall()]
    src_row_counts = {t: cur_src.execute(f"SELECT COUNT(*) FROM {t}").fetchone()[0] for t in src_tables}
    print(f"1. Source DB created with {len(src_tables)} tables:")
    for t, cnt in src_row_counts.items():
        print(f"   - {t}: {cnt} records")

    # Step 2: Perform Full Backup (Dump to SQL)
    t0_backup = time.time()
    with open(dump_sql_path, 'w', encoding='utf-8') as f:
        for line in conn_src.iterdump():
            f.write(f"{line}\n")
    t1_backup = time.time()
    backup_size = os.path.getsize(dump_sql_path)
    print(f"2. Backup completed in {round((t1_backup - t0_backup)*1000, 2)} ms. File size: {backup_size} bytes.")

    # Step 3: Perform Restore into Fresh Database Target
    t0_restore = time.time()
    conn_tgt = sqlite3.connect(db_target_path)
    # Crucial standard practice: Disable foreign keys during dump execution, then re-enable!
    conn_tgt.execute("PRAGMA foreign_keys = OFF;")
    with open(dump_sql_path, 'r', encoding='utf-8') as f:
        dump_content = f.read()
    conn_tgt.executescript(dump_content)
    conn_tgt.execute("PRAGMA foreign_keys = ON;")
    t1_restore = time.time()
    print(f"3. Restore completed in {round((t1_restore - t0_restore)*1000, 2)} ms (with FKs re-enabled).")

    # Step 4: Verification
    cur_tgt = conn_tgt.cursor()
    tgt_tables = [r[0] for r in cur_tgt.execute("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'").fetchall()]
    tgt_row_counts = {t: cur_tgt.execute(f"SELECT COUNT(*) FROM {t}").fetchone()[0] for t in tgt_tables}
    
    table_match = (set(src_tables) == set(tgt_tables))
    row_match = (src_row_counts == tgt_row_counts)
    
    # Verify FK constraint integrity in restored database
    fk_check = cur_tgt.execute("PRAGMA foreign_key_check").fetchall()
    fk_valid = (len(fk_check) == 0)

    # Verify specific document metadata preserved
    doc_ver = cur_tgt.execute("""
        SELECT d.doc_name, v.file_name, v.file_url 
        FROM kipa_dossier_documents d 
        JOIN kipa_document_versions v ON d.id = v.document_id 
        WHERE d.id = 1
    """).fetchone()
    doc_valid = (doc_ver == ('Học bạ THPT', 'hoc_ba.pdf', 'kipa-private/storage/hocba.pdf'))

    print(f"4. Verification Results:")
    print(f"   - Table match: {table_match} ({len(tgt_tables)} tables)")
    print(f"   - Row counts match: {row_match}")
    print(f"   - Foreign Key integrity: {fk_valid} (0 errors)")
    print(f"   - Document Metadata intact: {doc_valid}")

    # Step 5: Disaster Recovery & Rollback Simulation
    # Simulate a corrupted migration attempt on target DB:
    print("5. Disaster Recovery Simulation:")
    rollback_success = False
    try:
        cur_tgt.execute("BEGIN TRANSACTION;")
        cur_tgt.execute("INSERT INTO kipa_branches (branch_code, branch_name) VALUES ('TEST_BRANCH', 'Chi nhánh test');")
        # Intentionally inject bad SQL: violating foreign key constraint or unique constraint
        cur_tgt.execute("INSERT INTO kipa_branches (branch_code, branch_name) VALUES ('HP_HQ', 'Trùng mã lỗi');")
        conn_tgt.commit()
    except sqlite3.IntegrityError as e:
        conn_tgt.rollback()
        rollback_success = True
        print(f"   - Transaction failed as expected ({e}), successfully rolled back.")

    # Verify target DB is uncorrupted after rollback
    cur_branch = cur_tgt.execute("SELECT COUNT(*) FROM kipa_branches WHERE branch_code='TEST_BRANCH'").fetchone()[0]
    uncorrupted = (cur_branch == 0)
    print(f"   - Target DB uncorrupted after rollback: {uncorrupted}")

    # Clean up files
    conn_src.close()
    conn_tgt.close()
    if os.path.exists(db_source_path): os.remove(db_source_path)
    if os.path.exists(db_target_path): os.remove(db_target_path)
    if os.path.exists(dump_sql_path): os.remove(dump_sql_path)

    all_passed = (table_match and row_match and fk_valid and doc_valid and rollback_success and uncorrupted)
    print(f"\nOVERALL BACKUP/RESTORE TEST: {'PASS' if all_passed else 'FAIL'}")
    return {
        "status": "PASS" if all_passed else "FAIL",
        "table_match": table_match,
        "row_match": row_match,
        "fk_valid": fk_valid,
        "doc_valid": doc_valid,
        "rollback_success": rollback_success,
        "backup_ms": round((t1_backup - t0_backup)*1000, 2),
        "restore_ms": round((t1_restore - t0_restore)*1000, 2)
    }

if __name__ == '__main__':
    run_backup_restore_test()
