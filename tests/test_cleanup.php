<?php
declare(strict_types=1);

$directory = sys_get_temp_dir() . '/kipa-cleanup-test-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);

try {
    copy(__DIR__ . '/../api/cleanup.php', $directory . '/cleanup.php');
    file_put_contents($directory . '/content.php', '<?php function kipa_content_cleanup(PDO $db): int { return 0; }');
    file_put_contents($directory . '/bootstrap.php', <<<'PHP'
<?php
class CleanupTestPDO extends PDO
{
    public array $queries = [];
    public bool $committed = false;
    public function __construct() {}
    public function beginTransaction(): bool { return true; }
    public function exec(string $statement): int|false
    {
        $this->queries[] = $statement;
        return count($this->queries) === 1 ? 2 : 3;
    }
    public function commit(): bool
    {
        $this->committed = true;
        return true;
    }
}
function kipa_config(): array { return []; }
function kipa_db(array $config): PDO
{
    return $GLOBALS['cleanupTestDb'] = new CleanupTestPDO();
}
PHP);
    ob_start();
    require $directory . '/cleanup.php';
    $output = ob_get_clean();
    $db = $GLOBALS['cleanupTestDb'];
    if ($output !== "Deleted 2 expired leads and 3 expired rate-limit records.\nDeleted 0 additional expired content registration/log records.\n") {
        throw new RuntimeException('Cleanup reported incorrect affected-row counts.');
    }
    if (!$db->committed || $db->queries !== [
        'DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH',
        'DELETE FROM kipa_rate_limits WHERE occurred_at < UTC_TIMESTAMP() - INTERVAL 1 DAY',
    ]) {
        throw new RuntimeException('Cleanup did not commit the expected retention queries.');
    }
    echo "Cleanup regression test passed.\n";
} finally {
    unlink($directory . '/cleanup.php');
    unlink($directory . '/bootstrap.php');
    unlink($directory . '/content.php');
    rmdir($directory);
}
