<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/bootstrap.php';

try {
    $db = kipa_db(kipa_config());
    $db->beginTransaction();
    $db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
    $leads = $db->rowCount();
    $db->exec('DELETE FROM kipa_rate_limits WHERE occurred_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    $rates = $db->rowCount();
    $db->commit();
    echo "Deleted {$leads} expired leads and {$rates} expired rate-limit records.\n";
} catch (PDOException $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[kipa-cleanup] Database operation failed: ' . $error->getCode());
    fwrite(STDERR, "Cleanup failed: database operation failed.\n");
    exit(1);
} catch (RuntimeException $error) {
    fwrite(STDERR, "Cleanup failed: backend configuration is missing or invalid.\n");
    exit(1);
}
