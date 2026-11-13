<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/content.php';

try {
    $db = kipa_db(kipa_config());
    $db->beginTransaction();
    $leads = $db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
    $rates = $db->exec('DELETE FROM kipa_rate_limits WHERE occurred_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    try {
        $otps = $db->exec('DELETE FROM kipa_otp_codes WHERE expires_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    } catch (PDOException $error) {
        // Tolerate hosts where backend/otp_table.php has not been run yet.
        if (($error->errorInfo[0] ?? '') !== '42S02') {
            throw $error;
        }
        $otps = 0;
    }
    $content = kipa_content_cleanup($db);
    $db->commit();
    echo "Deleted {$leads} expired leads, {$rates} expired rate-limit records and {$otps} expired OTP codes.\n";
    echo "Deleted {$content} additional expired content registration/log records.\n";
} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-cleanup] Database operation failed: ' . $error->getCode());
    fwrite(STDERR, "Cleanup failed: database operation failed.\n");
    exit(1);
} catch (RuntimeException | JsonException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Cleanup failed: backend configuration is missing or invalid.\n");
    exit(1);
}
