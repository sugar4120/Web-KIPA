<?php
declare(strict_types=1);

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/../api/bootstrap.php';
require __DIR__ . '/../api/content.php';
require __DIR__ . '/../api/publish.php';

function content_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function content_reject(callable $action, string $message): void
{
    try {
        $action();
    } catch (InvalidArgumentException $expected) {
        return;
    }
    throw new RuntimeException('Expected validation failure: ' . $message);
}

function content_command(array $command): string
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not launch test command.');
    }
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    content_assert($status === 0 && $errors === '', "Test command failed: $output $errors");
    return $output;
}

$dsn = getenv('KIPA_TEST_DSN');
if (!$dsn || !preg_match('/dbname=kipa_content_test(?:;|$)/', $dsn)) {
    fwrite(STDERR, "Set KIPA_TEST_DSN to an isolated MySQL database named kipa_content_test. Never use production.\n");
    exit(2);
}
$db = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
content_assert((int)$db->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchColumn() === 0, 'Test database must be empty.');
$root = sys_get_temp_dir() . '/kipa-content-' . bin2hex(random_bytes(6));
$public = $root . '/public_html';
$private = $root . '/kipa-private';
mkdir($root, 0700);
mkdir($public, 0755);
mkdir($private, 0700);
mkdir($public . '/api', 0755);
copy(__DIR__ . '/../.htaccess', $public . '/.htaccess');
try {
    foreach (glob(__DIR__ . '/../api/*') as $file) {
        if (is_file($file)) {
            copy($file, $public . '/api/' . basename($file));
        }
    }
    file_put_contents($private . '/config.php', '<?php return ' . var_export([
        'db_dsn' => $dsn, 'db_user' => 'root', 'db_password' => 'test-only',
        'app_key' => str_repeat('T', 64), 'setup_token' => str_repeat('S', 64),
    ], true) . ';');
    // The test service has an empty root password; config validation still requires a nonempty string.
    // A separate test user makes the fixture exercise the real configuration loader.
    $db->exec("CREATE USER IF NOT EXISTS 'kipa_test'@'%' IDENTIFIED BY 'kipa-test-only'");
    $db->exec("GRANT ALL ON kipa_content_test.* TO 'kipa_test'@'%'");
    $config = [
        'db_dsn' => $dsn, 'db_user' => 'kipa_test', 'db_password' => 'kipa-test-only',
        'app_key' => str_repeat('T', 64), 'setup_token' => str_repeat('S', 64),
    ];
    file_put_contents($private . '/config.php', '<?php return ' . var_export($config, true) . ';');
    foreach (explode(';', file_get_contents(__DIR__ . '/../backend/schema.sql')) as $sql) {
        if (trim($sql) !== '') {
            $db->exec($sql);
        }
    }
    content_assert(!kipa_content_ready($db), 'Legacy backend must remain usable before migration.');
    $hash = password_hash('KIPA-test-password-2026', PASSWORD_DEFAULT);
    $q = $db->prepare('INSERT INTO kipa_admins (username,password_hash,must_change_password,created_at) VALUES (?,?,1,UTC_TIMESTAMP())');
    $q->execute(['test-admin', $hash]);
    $insert = $db->prepare('INSERT INTO kipa_leads (form_type,contact_name,phone,email,program,source_page,details_json,consented_at,created_at) VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
    foreach (['home_consultation', 'consultation', 'contact', 'korean_course', 'labor', 'partnership'] as $type) {
        $insert->execute([$type, 'TEST ' . $type, '0900000000', null, 'Nhu cầu kiểm thử', '/', '{"notes":"PRIVATE-LEAD-CANARY"}']);
    }
    $original = $db->query('SELECT * FROM kipa_leads ORDER BY id')->fetchAll();
    $first = json_decode(trim(content_command([PHP_BINARY, $public . '/api/migrate.php', '--apply'])), true, 512, JSON_THROW_ON_ERROR);
    content_assert($first['tables'] === 85 && $first['backfilled_leads'] === 6, 'Full model and all six form types must migrate.');
    $second = json_decode(trim(content_command([PHP_BINARY, $public . '/api/migrate.php', '--apply'])), true, 512, JSON_THROW_ON_ERROR);
    content_assert($second['backfilled_leads'] === 0, 'Migration must be idempotent.');
    content_assert($db->query('SELECT * FROM kipa_leads ORDER BY id')->fetchAll() === $original, 'Legacy records must be unchanged.');
    content_assert($db->query('SELECT password_hash FROM kipa_admins')->fetchColumn() === $hash, 'Existing admin password must not change.');
    $backups = glob($private . '/database-before-content-*.sql');
    content_assert(count($backups) === 1 && (fileperms($backups[0]) & 0777) === 0600, 'Initial migration must create exactly one private backup.');
    content_assert(kipa_content_ready($db), 'Migrated content model should be enabled.');
    $writer = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->beginTransaction();
    $installedHash = $db->query('SELECT model_hash FROM kipa_content_state WHERE id = 1')->fetchColumn();
    $writer->exec("UPDATE kipa_content_state SET model_hash = REPEAT('0',64) WHERE id = 1");
    $fresh = false;
    try {
        kipa_content_ready($db);
    } catch (RuntimeException $expected) {
        $fresh = str_contains($expected->getMessage(), 'does not match');
    }
    $db->rollBack();
    $writer->prepare('UPDATE kipa_content_state SET model_hash = ? WHERE id = 1')->execute([$installedHash]);
    content_assert($fresh, 'Readiness must use the latest committed state even inside an older transaction snapshot.');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_consultation_requests')->fetchColumn() === 4, 'Consultation routing mismatch.');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_contact_messages')->fetchColumn() === 1, 'Contact routing mismatch.');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_course_registrations')->fetchColumn() === 1, 'Course routing mismatch.');
    $post = ['title' => 'Bài viết kiểm thử', 'slug' => 'bai-viet-kiem-thu', 'content' => 'Nội dung <script>alert(1)</script>', 'post_type' => 'article', 'status' => 'published'];
    $postId = kipa_content_save($db, 'posts', $post, null);
    content_reject(fn() => kipa_content_validate('posts', [...$post, 'slug' => '../private']), 'traversal slug');
    content_reject(fn() => kipa_content_validate('posts', [...$post, 'unknown' => 'x']), 'unknown field');
    content_reject(fn() => kipa_content_validate('posts', [...$post, 'featured_image' => 'javascript:alert(1)']), 'script URL');
    content_reject(fn() => kipa_content_validate('posts', [...$post, 'published_at' => '2026-02-31 00:00:00']), 'impossible date');
    content_reject(fn() => kipa_content_validate('posts', [...$post, 'reading_time' => '99999999999999999999']), 'integer overflow');
    content_reject(fn() => kipa_content_save($db, 'posts', [...$post, 'slug' => 'missing-author', 'author_id' => '999999'], null), 'missing foreign key');
    content_reject(fn() => kipa_content_validate('site_settings', ['setting_group' => 'x', 'setting_key' => 'api_token', 'setting_value' => 'x', 'value_type' => 'text', 'label' => 'x']), 'secret setting');
    content_reject(fn() => kipa_content_validate('redirects', ['source_url' => '/x', 'target_url' => '/x', 'redirect_type' => '301']), 'redirect loop');
    content_reject(fn() => kipa_content_validate('redirects', ['source_url' => '/x', 'target_url' => 'https://kipaviethan.edu.vn/x?again=1', 'redirect_type' => '301']), 'same-site absolute redirect loop');
    kipa_content_save($db, 'redirects', ['source_url' => '/old.html', 'target_url' => '/intermediate', 'redirect_type' => '301', 'is_active' => '1'], null);
    kipa_content_save($db, 'redirects', ['source_url' => '/intermediate', 'target_url' => '/tin-tuc/', 'redirect_type' => '301', 'is_active' => '1'], null);
    content_reject(fn() => kipa_content_save($db, 'redirects', ['source_url' => '/tin-tuc/', 'target_url' => '/old.html', 'redirect_type' => '301', 'is_active' => '1'], null), 'multi-hop redirect loop');
    content_reject(fn() => kipa_content_validate('seo_metadata', ['entity_type' => 'post', 'entity_id' => (string)$postId, 'schema_json' => '{bad']), 'invalid JSON');
    content_reject(fn() => kipa_content_validate('seo_metadata', ['entity_type' => 'post', 'entity_id' => (string)$postId, 'schema_json' => 'true']), 'scalar JSON-LD');
    content_reject(fn() => kipa_content_validate('program_documents', ['document_name' => 'Test']), 'missing programme owner');
    content_reject(fn() => kipa_content_validate('program_documents', ['document_name' => 'Test', 'study_program_id' => '1', 'work_program_id' => '1']), 'multiple programme owners');
    kipa_content_save($db, 'posts', [...$post, 'slug' => 'private-draft', 'title' => 'DRAFT-CANARY', 'status' => 'draft'], null);
    kipa_content_save($db, 'seo_metadata', ['entity_type' => 'post', 'entity_id' => (string)$postId, 'meta_title' => 'Tiêu đề SEO',
        'robots_index' => '1', 'robots_follow' => '0', 'schema_json' => '{"@type":"Article","name":"</script><script>unsafe</script>"}'], null);
    $review = ['student_name' => 'NO-CONSENT-CANARY', 'review_content' => 'Private', 'review_type' => 'student', 'status' => 'published'];
    kipa_content_save($db, 'testimonials', $review, null);
    $pages = kipa_publish($db, $public);
    content_assert(str_contains(file_get_contents($public . '/.htaccess'), 'RewriteRule ^old\\.html$ /tin-tuc/ [R=301,L,NE]'), 'Redirect chains must resolve to their final destination.');
    content_assert($pages > 16, 'Publication should include public section indexes.');
    $html = file_get_contents($public . '/tin-tuc/bai-viet-kiem-thu/index.html');
    content_assert(str_contains($html, 'Tiêu đề SEO') && str_contains($html, 'index,nofollow'), 'SEO and robots must be applied.');
    content_assert(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($html, '</script><script>unsafe'), 'Content and JSON-LD must be safely escaped.');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($public . '/noi-dung', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $text = file_get_contents($file->getPathname());
            content_assert(!str_contains($text, 'PRIVATE-LEAD-CANARY') && !str_contains($text, 'DRAFT-CANARY') && !str_contains($text, 'NO-CONSENT-CANARY'), 'Private data must never be published.');
        }
    }
    kipa_content_save($db, 'posts', [...$post, 'status' => 'archived'], $postId);
    kipa_publish($db, $public);
    content_assert(!file_exists($public . '/tin-tuc/bai-viet-kiem-thu/index.html'), 'Hidden pages must be removed after publishing.');
    content_assert(str_contains(file_get_contents($public . '/tin-tuc/index.html'), 'noindex,follow'), 'Empty sections must not be indexed.');
    content_assert(!str_contains(file_get_contents($public . '/noi-dung/sitemap.xml'), '<url>'), 'Empty sections must not enter the sitemap.');
    $before = file_get_contents($public . '/noi-dung/index.html');
    file_put_contents($public . '/tin-tuc/.kipa-owned', 'unmanaged');
    try {
        kipa_publish($db, $public);
        throw new LogicException('Expected unmanaged target protection.');
    } catch (RuntimeException $expected) {
        content_assert(str_contains($expected->getMessage(), 'unmanaged'), 'Unexpected publish failure.');
    }
    content_assert(file_get_contents($public . '/noi-dung/index.html') === $before, 'Failed publication must retain live files.');
    file_put_contents($public . '/tin-tuc/.kipa-owned', "KIPA-CMS-1\n");
    $db->exec('DELETE FROM kipa_leads WHERE id = 1');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_form_submissions WHERE legacy_lead_id = 1')->fetchColumn() === 0, 'Deleting a legacy lead must cascade to its submission log.');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_consultation_requests WHERE legacy_lead_id = 1')->fetchColumn() === 0, 'Deleting a legacy lead must cascade to its typed record.');
    echo content_command(['node', __DIR__ . '/test_cms_http.js', $public]);
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_leads')->fetchColumn() === 11, 'Six new HTTP requests must preserve legacy persistence.');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_form_submissions')->fetchColumn() === 11, 'All new HTTP forms must also persist their structured records and submission logs.');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_consultation_requests')->fetchColumn() === 7, 'New consultations must route to the structured table.');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_contact_messages')->fetchColumn() === 2, 'New contacts must route to the structured table.');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_course_registrations')->fetchColumn() === 2, 'New course enquiries must route to the structured table.');
    $db->exec("UPDATE kipa_leads SET created_at = UTC_TIMESTAMP() - INTERVAL 13 MONTH WHERE id = 2");
    $beforeCount = (int)$db->query('SELECT COUNT(*) FROM kipa_form_submissions')->fetchColumn();
    $db->exec('DELETE FROM kipa_leads WHERE created_at < UTC_TIMESTAMP() - INTERVAL 12 MONTH');
    content_assert((int)$db->query('SELECT COUNT(*) FROM kipa_form_submissions')->fetchColumn() === $beforeCount - 1, 'Legacy retention must cascade to structured data.');
    $db->exec("INSERT INTO kipa_course_registrations (registration_code, full_name, phone, privacy_consent, status, submitted_at, created_at, updated_at)
        VALUES ('MANUAL-OLD', 'TEST', '0900000000', 1, 'new', UTC_TIMESTAMP() - INTERVAL 13 MONTH, UTC_TIMESTAMP() - INTERVAL 13 MONTH, UTC_TIMESTAMP())");
    content_assert(kipa_content_cleanup($db) === 1, 'Retention must also cover registrations without a legacy lead.');
    content_assert((int)$db->query("SELECT COUNT(*) FROM kipa_course_registrations WHERE registration_code = 'MANUAL-OLD'")->fetchColumn() === 0, 'Expired structured record must be deleted.');
    echo "85-table migration, idempotency, legacy backfill, validation, privacy-safe publishing and removal tests passed.\n";
} finally {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($root);
}
