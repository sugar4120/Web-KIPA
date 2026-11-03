<?php
declare(strict_types=1);

$directory = sys_get_temp_dir() . '/kipa-config-test-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
mkdir($directory . '/public_html');
mkdir($directory . '/public_html/api');
mkdir($directory . '/kipa-private', 0700);

try {
    copy(__DIR__ . '/../api/bootstrap.php', $directory . '/public_html/api/bootstrap.php');
    $expected = [
        'db_dsn' => 'mysql:host=localhost;dbname=test;charset=utf8mb4',
        'db_user' => 'test',
        'db_password' => 'test-only-password',
        'app_key' => str_repeat('a', 32),
        'setup_token' => str_repeat('b', 32),
    ];
    file_put_contents($directory . '/kipa-private/config.php', '<?php return ' . var_export($expected, true) . ';');
    define('KIPA_API_BOOTSTRAP', true);
    require $directory . '/public_html/api/bootstrap.php';

    foreach ([null, '', $directory . '/public_html'] as $documentRoot) {
        if ($documentRoot === null) {
            unset($_SERVER['DOCUMENT_ROOT']);
        } else {
            $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        }
        if (kipa_config() !== $expected) {
            throw new RuntimeException('Configuration did not load for the expected web/CLI document root.');
        }
    }
    echo "Web and CLI configuration regression test passed.\n";
} finally {
    unlink($directory . '/kipa-private/config.php');
    unlink($directory . '/public_html/api/bootstrap.php');
    rmdir($directory . '/public_html/api');
    rmdir($directory . '/public_html');
    rmdir($directory . '/kipa-private');
    rmdir($directory);
}
