<?php
declare(strict_types=1);

/**
 * KIPA VIỆT HÀN — SCRIPT CHẠY MIGRATION CỔNG HỌC VIÊN TRÊN CPANEL
 * File: backend/migrate_student_portal.php
 * 
 * Cách sử dụng trên cPanel Terminal hoặc SSH:
 *   php backend/migrate_student_portal.php /home/username/public_html
 * 
 * Hoặc nhập file SQL trực tiếp trong giao diện phpMyAdmin:
 *   backend/student_portal_cpanel_import.sql
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if ($argc < 2 || !is_dir($argv[1] . '/api')) {
    fwrite(STDERR, "Cách dùng: php backend/migrate_student_portal.php /duong/dan/tuyet/doi/toi/public_html\n");
    exit(2);
}

define('KIPA_API_BOOTSTRAP', true);
$_SERVER['DOCUMENT_ROOT'] = realpath($argv[1]);
require __DIR__ . '/../api/bootstrap.php';

try {
    fwrite(STDOUT, "=== BẮT ĐẦU MIGRATION CỔNG THÔNG TIN HỌC VIÊN KIPA ===\n");
    $config = kipa_config();
    $db = kipa_db($config);

    $sqlPath = __DIR__ . '/student_portal_cpanel_import.sql';
    if (!is_file($sqlPath)) {
        throw new RuntimeException("Không tìm thấy tệp SQL: {$sqlPath}");
    }

    $sqlContent = file_get_contents($sqlPath);
    if ($sqlContent === false) {
        throw new RuntimeException("Không thể đọc tệp SQL: {$sqlPath}");
    }

    fwrite(STDOUT, "- Đang thực thi tệp student_portal_cpanel_import.sql vào Database...\n");
    $db->exec($sqlContent);

    fwrite(STDOUT, "[THÀNH CÔNG] Toàn bộ các bảng và dữ liệu Cổng học viên đã được tạo/cập nhật an toàn!\n");
    fwrite(STDOUT, "  - kipa_branches\n");
    fwrite(STDOUT, "  - kipa_staff\n");
    fwrite(STDOUT, "  - kipa_customers\n");
    fwrite(STDOUT, "  - kipa_service_dossiers\n");
    fwrite(STDOUT, "  - kipa_dossier_documents\n");
    fwrite(STDOUT, "  - kipa_students\n");
    fwrite(STDOUT, "  - kipa_student_journey\n");
    fwrite(STDOUT, "  - kipa_student_schedules\n");
    fwrite(STDOUT, "  - kipa_student_notifications\n");
    fwrite(STDOUT, "  - kipa_student_support\n");
    fwrite(STDOUT, "  - kipa_student_materials\n");
    fwrite(STDOUT, "Trang hồ sơ học viên (ho-so.html) đã sẵn sàng hoạt động với dữ liệu thực tế.\n");
    exit(0);
} catch (PDOException $e) {
    fwrite(STDERR, "[LỖI DATABASE] Mã lỗi " . $e->getCode() . ": " . $e->getMessage() . "\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, "[LỖI HỆ THỐNG] " . $e->getMessage() . "\n");
    exit(1);
}
