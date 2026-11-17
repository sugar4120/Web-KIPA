# -*- coding: utf-8 -*-
"""
KIPA High-Volume Performance Benchmark Suite (Local Sandbox)
Simulates:
- 100,000 customers
- 200,000 consultation dossiers
- 100,000 service dossiers
- 500,000 consultation activities
- 500,000 task histories
- 100,000 document records

Measures query response times, index performance, pagination, and dashboard aggregations.
"""

import sqlite3
import time
import os
import sys
import platform
import json

def run_performance_benchmark():
    print("=" * 80)
    print("KIPA HIGH-VOLUME PERFORMANCE BENCHMARK (1.5+ MILLION RECORDS)")
    print("=" * 80)

    # Machine Specs
    host_info = {
        "os": platform.platform(),
        "processor": platform.processor(),
        "machine": platform.machine(),
        "python_version": sys.version.split()[0],
        "sqlite_version": sqlite3.sqlite_version
    }
    print(f"Host Specs: {host_info['os']} | {host_info['machine']} | Python {host_info['python_version']}")

    db_path = "staging_benchmark.db"
    if os.path.exists(db_path):
        os.remove(db_path)

    conn = sqlite3.connect(db_path)
    cur = conn.cursor()

    # Fast insertion pragma for benchmark setup
    cur.execute("PRAGMA synchronous = OFF;")
    cur.execute("PRAGMA journal_mode = MEMORY;")

    print("\n1. Creating Indexed Benchmark Schema...")
    cur.executescript("""
        CREATE TABLE kipa_customers (
            id INTEGER PRIMARY KEY,
            customer_code TEXT NOT NULL,
            full_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT NOT NULL,
            branch_id INTEGER NOT NULL,
            created_at TEXT NOT NULL
        );
        CREATE UNIQUE INDEX idx_perf_cust_code ON kipa_customers(customer_code);
        CREATE INDEX idx_perf_cust_phone ON kipa_customers(phone);
        CREATE INDEX idx_perf_cust_branch ON kipa_customers(branch_id);

        CREATE TABLE kipa_consultation_dossiers (
            id INTEGER PRIMARY KEY,
            consultation_code TEXT NOT NULL,
            customer_id INTEGER NOT NULL,
            branch_id INTEGER NOT NULL,
            stage TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        CREATE INDEX idx_perf_cons_cust ON kipa_consultation_dossiers(customer_id);
        CREATE INDEX idx_perf_cons_branch_stage ON kipa_consultation_dossiers(branch_id, stage);
        CREATE INDEX idx_perf_cons_created ON kipa_consultation_dossiers(created_at);

        CREATE TABLE kipa_service_dossiers (
            id INTEGER PRIMARY KEY,
            dossier_code TEXT NOT NULL,
            customer_id INTEGER NOT NULL,
            branch_id INTEGER NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        CREATE UNIQUE INDEX idx_perf_doss_code ON kipa_service_dossiers(dossier_code);
        CREATE INDEX idx_perf_doss_cust ON kipa_service_dossiers(customer_id);
        CREATE INDEX idx_perf_doss_branch ON kipa_service_dossiers(branch_id);

        CREATE TABLE kipa_consultation_activities (
            id INTEGER PRIMARY KEY,
            consultation_id INTEGER NOT NULL,
            staff_id INTEGER NOT NULL,
            activity_type TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        CREATE INDEX idx_perf_act_cons ON kipa_consultation_activities(consultation_id);
        CREATE INDEX idx_perf_act_staff ON kipa_consultation_activities(staff_id);

        CREATE TABLE kipa_task_history (
            id INTEGER PRIMARY KEY,
            task_id INTEGER NOT NULL,
            changed_by INTEGER NOT NULL,
            field_name TEXT NOT NULL,
            occurred_at TEXT NOT NULL
        );
        CREATE INDEX idx_perf_th_task ON kipa_task_history(task_id);

        CREATE TABLE kipa_dossier_documents (
            id INTEGER PRIMARY KEY,
            dossier_id INTEGER NOT NULL,
            customer_id INTEGER NOT NULL,
            doc_name TEXT NOT NULL,
            status TEXT NOT NULL,
            can_download INTEGER NOT NULL
        );
        CREATE INDEX idx_perf_doc_dossier ON kipa_dossier_documents(dossier_id);
        CREATE INDEX idx_perf_doc_cust ON kipa_dossier_documents(customer_id);
    """)
    conn.commit()

    # Step 2: Seed 100k Customers
    print("2. Seeding 100,000 Customers...")
    t0 = time.time()
    batch_size = 20000
    for b in range(5):
        chunk = []
        for i in range(1, batch_size + 1):
            uid = b * batch_size + i
            chunk.append((
                uid,
                f"KH-2026-{uid:06d}",
                f"Học viên KIPA {uid}",
                f"0912{uid:06d}"[-10:],
                f"student_{uid}@kipa.edu.vn",
                (uid % 7) + 1,
                "2026-11-17 08:00:00"
            ))
        cur.executemany("INSERT INTO kipa_customers VALUES (?, ?, ?, ?, ?, ?, ?)", chunk)
        conn.commit()
    print(f"   Done 100,000 Customers in {round(time.time() - t0, 2)}s")

    # Step 3: Seed 200k Consultation Dossiers
    print("3. Seeding 200,000 Consultation Dossiers...")
    t0 = time.time()
    stages = ["NEW", "CONTACTED", "NEED_ANALYSIS", "CONSULTING", "PENDING_DECISION", "WON", "LOST", "UNREACHABLE"]
    for b in range(10):
        chunk = []
        for i in range(1, batch_size + 1):
            cid = b * batch_size + i
            cust_ref = (cid % 100000) + 1
            stg = stages[cid % len(stages)]
            chunk.append((
                cid,
                f"TV-2026-{cid:06d}",
                cust_ref,
                (cid % 7) + 1,
                stg,
                "2026-11-17 09:00:00"
            ))
        cur.executemany("INSERT INTO kipa_consultation_dossiers VALUES (?, ?, ?, ?, ?, ?)", chunk)
        conn.commit()
    print(f"   Done 200,000 Consultations in {round(time.time() - t0, 2)}s")

    # Step 4: Seed 100k Service Dossiers
    print("4. Seeding 100,000 Service Dossiers...")
    t0 = time.time()
    for b in range(5):
        chunk = []
        for i in range(1, batch_size + 1):
            did = b * batch_size + i
            cust_ref = did
            chunk.append((
                did,
                f"HS-2026-{did:06d}",
                cust_ref,
                (did % 7) + 1,
                "ACTIVE",
                "2026-11-17 10:00:00"
            ))
        cur.executemany("INSERT INTO kipa_service_dossiers VALUES (?, ?, ?, ?, ?, ?)", chunk)
        conn.commit()
    print(f"   Done 100,000 Service Dossiers in {round(time.time() - t0, 2)}s")

    # Step 5: Seed 500k Consultation Activities
    print("5. Seeding 500,000 Consultation Activities...")
    t0 = time.time()
    act_types = ["CALL", "MEETING", "ZALO", "EMAIL"]
    for b in range(25):
        chunk = []
        for i in range(1, batch_size + 1):
            aid = b * batch_size + i
            cons_ref = (aid % 200000) + 1
            chunk.append((
                aid,
                cons_ref,
                (aid % 50) + 1,
                act_types[aid % 4],
                "2026-11-17 11:00:00"
            ))
        cur.executemany("INSERT INTO kipa_consultation_activities VALUES (?, ?, ?, ?, ?)", chunk)
        conn.commit()
    print(f"   Done 500,000 Activities in {round(time.time() - t0, 2)}s")

    # Step 6: Seed 500k Task History
    print("6. Seeding 500,000 Task History records...")
    t0 = time.time()
    for b in range(25):
        chunk = []
        for i in range(1, batch_size + 1):
            thid = b * batch_size + i
            chunk.append((
                thid,
                (thid % 100000) + 1,
                (thid % 50) + 1,
                "status",
                "2026-11-17 12:00:00"
            ))
        cur.executemany("INSERT INTO kipa_task_history VALUES (?, ?, ?, ?, ?)", chunk)
        conn.commit()
    print(f"   Done 500,000 Task History in {round(time.time() - t0, 2)}s")

    # Step 7: Seed 100k Documents
    print("7. Seeding 100,000 Documents...")
    t0 = time.time()
    for b in range(5):
        chunk = []
        for i in range(1, batch_size + 1):
            docid = b * batch_size + i
            chunk.append((
                docid,
                docid,
                docid,
                "Học bạ THPT",
                "Đã xác nhận",
                1
            ))
        cur.executemany("INSERT INTO kipa_dossier_documents VALUES (?, ?, ?, ?, ?, ?)", chunk)
        conn.commit()
    print(f"   Done 100,000 Documents in {round(time.time() - t0, 2)}s")

    db_file_size_mb = round(os.path.getsize(db_path) / (1024 * 1024), 2)
    print(f"\n-> TOTAL DATABASE SIZE WITH INDEXES: {db_file_size_mb} MB")

    # Reset pragma to normal mode for authentic query benchmarking
    cur.execute("PRAGMA synchronous = NORMAL;")

    # -------------------------------------------------------------------------
    # BENCHMARK QUERIES
    # -------------------------------------------------------------------------
    print("\n" + "=" * 80)
    print("EXECUTING REAL QUERY BENCHMARKS (AVERAGED OVER 5 RUNS)")
    print("=" * 80)

    benchmark_queries = [
        {
            "id": "PERF-Q1",
            "name": "Tìm kiếm chính xác Khách hàng theo SĐT (Phone Index)",
            "sql": "SELECT * FROM kipa_customers WHERE phone = '0912055555' LIMIT 1;",
            "target": "< 5 ms"
        },
        {
            "id": "PERF-Q2",
            "name": "Tìm kiếm chính xác Khách hàng theo Mã (Customer Code Unique Index)",
            "sql": "SELECT * FROM kipa_customers WHERE customer_code = 'KH-2026-088888';",
            "target": "< 5 ms"
        },
        {
            "id": "PERF-Q3",
            "name": "Tìm kiếm tiền tố số điện thoại (LIKE '091205%' Prefix Search)",
            "sql": "SELECT id, customer_code, full_name, phone FROM kipa_customers WHERE phone LIKE '091205%' LIMIT 20;",
            "target": "< 10 ms"
        },
        {
            "id": "PERF-Q4",
            "name": "Phân trang Hồ sơ tư vấn theo Chi nhánh (ORDER BY created_at OFFSET 100 LIMIT 20)",
            "sql": "SELECT id, consultation_code, customer_id, stage, created_at FROM kipa_consultation_dossiers WHERE branch_id = 1 ORDER BY created_at DESC LIMIT 20 OFFSET 100;",
            "target": "< 20 ms"
        },
        {
            "id": "PERF-Q5",
            "name": "JOIN Khách hàng + Hồ sơ dịch vụ + Đếm tài liệu (Complex Relational Join)",
            "sql": """
                SELECT c.customer_code, c.full_name, sd.dossier_code, COUNT(d.id) AS total_docs
                FROM kipa_service_dossiers sd
                JOIN kipa_customers c ON sd.customer_id = c.id
                LEFT JOIN kipa_dossier_documents d ON sd.id = d.dossier_id
                WHERE sd.branch_id = 2
                GROUP BY sd.id
                LIMIT 20;
            """,
            "target": "< 30 ms"
        },
        {
            "id": "PERF-Q6",
            "name": "Thống kê Dashboard Pipeline (GROUP BY stage trên 200.000 hồ sơ)",
            "sql": "SELECT stage, COUNT(*) AS count FROM kipa_consultation_dossiers WHERE branch_id = 1 GROUP BY stage;",
            "target": "< 50 ms"
        },
        {
            "id": "PERF-Q7",
            "name": "Thống kê Hoạt động Tư vấn theo Chuyên viên (GROUP BY trên 500.000 bản ghi)",
            "sql": "SELECT staff_id, activity_type, COUNT(*) AS total FROM kipa_consultation_activities GROUP BY staff_id, activity_type LIMIT 20;",
            "target": "< 100 ms"
        }
    ]

    results = []
    for q in benchmark_queries:
        runs = []
        for _ in range(5):
            t_start = time.perf_counter()
            cur.execute(q["sql"]).fetchall()
            t_elapsed = (time.perf_counter() - t_start) * 1000 # in ms
            runs.append(t_elapsed)
        avg_ms = round(sum(runs) / len(runs), 3)
        min_ms = round(min(runs), 3)
        max_ms = round(max(runs), 3)
        results.append({
            "id": q["id"],
            "name": q["name"],
            "target": q["target"],
            "avg_ms": avg_ms,
            "min_ms": min_ms,
            "max_ms": max_ms,
            "sql": q["sql"].strip()
        })
        print(f"[{q['id']}] {q['name'][:50]}... -> Avg: {avg_ms} ms (Min: {min_ms} ms, Max: {max_ms} ms) | Target: {q['target']}")

    conn.close()
    if os.path.exists(db_path):
        os.remove(db_path)

    output_data = {
        "host_info": host_info,
        "database_size_mb": db_file_size_mb,
        "records_loaded": {
            "customers": 100000,
            "consultation_dossiers": 200000,
            "service_dossiers": 100000,
            "consultation_activities": 500000,
            "task_history": 500000,
            "documents": 100000,
            "total_records": 1500000
        },
        "query_results": results
    }

    with open("tools/performance_benchmark_results.json", "w", encoding="utf-8") as f:
        json.dump(output_data, f, ensure_ascii=False, indent=2)
    print("\nSaved benchmark results to tools/performance_benchmark_results.json")

if __name__ == '__main__':
    run_performance_benchmark()
