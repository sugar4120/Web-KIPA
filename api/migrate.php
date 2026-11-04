<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/content.php';

function kipa_content_ddl(): array
{
    $statements = [];
    foreach (kipa_content_model()['tables'] as $name => $table) {
        $columns = [];
        foreach ($table['fields'] as $fieldName => $field) {
            $sql = '`' . $fieldName . '` ' . $field['type'];
            if ($fieldName === 'id') {
                $sql .= ' NOT NULL AUTO_INCREMENT PRIMARY KEY';
            } else {
                $sql .= $field['required'] ? ' NOT NULL' : ' NULL';
                if (isset($field['default'])) {
                    $sql .= " DEFAULT '" . str_replace("'", "''", (string)$field['default']) . "'";
                }
                if (in_array($fieldName, ['created_at', 'updated_at'], true)) {
                    $sql .= ' DEFAULT CURRENT_TIMESTAMP';
                }
            }
            $columns[] = $sql;
        }
        foreach ($table['fields'] as $fieldName => $field) {
            if ($fieldName === 'slug' || $fieldName === 'legacy_lead_id'
                || in_array($fieldName, ['request_code', 'message_code', 'registration_code', 'submission_code', 'setting_key', 'menu_code'], true)
                || ($name === 'countries' && $fieldName === 'country_code')
                || ($name === 'redirects' && $fieldName === 'source_url')) {
                $columns[] = 'UNIQUE KEY `uq_' . $fieldName . '` (`' . $fieldName . '`)';
            }
            if (isset($field['ref'])) {
                $columns[] = 'KEY `idx_' . $fieldName . '` (`' . $fieldName . '`)';
            }
        }
        $pairs = [
            'course_teachers' => ['course_id', 'teacher_id'],
            'post_category_assignments' => ['post_id', 'category_id'],
            'post_tag_assignments' => ['post_id', 'tag_id'],
            'consultation_request_interests' => ['consultation_request_id', 'interest_id'],
            'program_faqs' => ['faq_id', 'program_id'],
            'partner_schools' => ['partner_id', 'school_id'],
            'location_service_assignments' => ['location_id', 'service_id'],
            'seo_metadata' => ['entity_type', 'entity_id'],
        ];
        if (isset($pairs[$name])) {
            $columns[] = 'UNIQUE KEY `uq_relation` (`' . implode('`,`', $pairs[$name]) . '`)';
        }
        if (isset($table['one_of'])) {
            $columns[] = 'CONSTRAINT `ck_owner_' . $name . '` CHECK ((' . implode(' + ', array_map(
                fn($field) => '(`' . $field . '` IS NOT NULL)', $table['one_of']
            )) . ') = 1)';
        }
        $statements[] = 'CREATE TABLE IF NOT EXISTS ' . kipa_content_table($name) . " (\n  " .
            implode(",\n  ", $columns) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }
    return $statements;
}

function kipa_content_constraints(): array
{
    $constraints = [];
    foreach (kipa_content_model()['tables'] as $name => $table) {
        foreach ($table['fields'] as $fieldName => $field) {
            if (!isset($field['ref'])) {
                continue;
            }
            $constraint = 'fk_' . substr(hash('sha256', $name . ':' . $fieldName), 0, 24);
            $cascade = $fieldName === 'legacy_lead_id'
                || in_array($name, ['consultation_activities', 'consultation_request_interests', 'post_revisions', 'page_revisions'], true);
            $constraints[] = [
                'table' => 'kipa_' . $name,
                'name' => $constraint,
                'sql' => 'ALTER TABLE ' . kipa_content_table($name) . ' ADD CONSTRAINT `' . $constraint .
                    '` FOREIGN KEY (`' . $fieldName . '`) REFERENCES ' . kipa_content_table($field['ref']) .
                    ' (`id`) ON DELETE ' . ($cascade ? 'CASCADE' : 'RESTRICT'),
            ];
        }
    }
    return $constraints;
}

$mode = $argv[1] ?? '';
if (!in_array($mode, ['--print', '--apply'], true)) {
    fwrite(STDERR, "Usage: php api/migrate.php --print|--apply\nBack up the database before --apply. Existing lead/admin tables are retained.\n");
    exit(2);
}
if ($mode === '--print') {
    echo "-- Generated from content-model.json; existing leads and admins are not replaced.\n";
    echo implode(";\n\n", [...kipa_content_ddl(), ...array_column(kipa_content_constraints(), 'sql')]), ";\n";
    exit;
}

$db = null;
try {
    $db = kipa_db(kipa_config());
    if ((int)$db->query("SELECT GET_LOCK('kipa-content-migration', 10)")->fetchColumn() !== 1) {
        throw new RuntimeException('Another content migration is running.');
    }
    $db->exec('CREATE TABLE IF NOT EXISTS kipa_content_state (
        id TINYINT UNSIGNED PRIMARY KEY, schema_version INT NOT NULL,
        model_hash CHAR(64) NOT NULL, migrated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $hash = hash_file('sha256', __DIR__ . '/content-model.json');
    $previous = $db->query('SELECT * FROM kipa_content_state WHERE id = 1')->fetch();
    if ($previous && $previous['model_hash'] !== $hash) {
        throw new RuntimeException('Model differs from installed schema. An explicit upgrade migration is required.');
    }
    if (!$previous) {
        $private = dirname(dirname(__DIR__)) . '/kipa-private';
        $backupPath = $private . '/database-before-content-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.sql';
        $backup = fopen($backupPath, 'x');
        if (!$backup || !chmod($backupPath, 0600)) {
            throw new RuntimeException('Cannot create a private database backup.');
        }
        try {
            foreach (['kipa_admins', 'kipa_leads', 'kipa_rate_limits'] as $legacy) {
                $definition = $db->query("SHOW CREATE TABLE `$legacy`")->fetch(PDO::FETCH_NUM)[1] . ";\n";
                if (fwrite($backup, $definition) !== strlen($definition)) {
                    throw new RuntimeException('Cannot write database backup schema.');
                }
                foreach ($db->query("SELECT * FROM `$legacy`")->fetchAll() as $row) {
                    $sql = "INSERT INTO `$legacy` (`" . implode('`,`', array_keys($row)) . '`) VALUES (' .
                        implode(',', array_map(fn($value) => $value === null ? 'NULL' : $db->quote((string)$value), array_values($row))) . ");\n";
                    if (fwrite($backup, $sql) !== strlen($sql)) {
                        throw new RuntimeException('Cannot write database backup rows.');
                    }
                }
            }
        } finally {
            fclose($backup);
        }
    }
    foreach (kipa_content_ddl() as $sql) {
        $db->exec($sql);
    }
    foreach (kipa_content_model()['tables'] as $name => $table) {
        $columnQuery = $db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $columnQuery->execute(['kipa_' . $name]);
        $columns = $columnQuery->fetchAll(PDO::FETCH_COLUMN);
        if (array_diff(array_keys($table['fields']), $columns)) {
            throw new RuntimeException('Incomplete table: ' . $name);
        }
    }
    foreach (kipa_content_constraints() as $constraint) {
        $query = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?');
        $query->execute([$constraint['table'], $constraint['name']]);
        if ((int)$query->fetchColumn() === 0) {
            $db->exec($constraint['sql']);
        }
    }
    $db->beginTransaction();
    $leads = $db->query('SELECT * FROM kipa_leads ORDER BY id FOR UPDATE')->fetchAll();
    $existing = $db->prepare('SELECT id FROM kipa_form_submissions WHERE legacy_lead_id = ?');
    $count = 0;
    foreach ($leads as $lead) {
        $existing->execute([$lead['id']]);
        if ($existing->fetchColumn()) {
            continue;
        }
        kipa_content_capture($db, $lead);
        $count++;
    }
    $query = $db->prepare('INSERT INTO kipa_content_state (id, schema_version, model_hash, migrated_at)
        VALUES (1, 1, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE migrated_at = UTC_TIMESTAMP()');
    $query->execute([$hash]);
    $db->commit();
    echo json_encode(['ok' => true, 'tables' => count(kipa_content_model()['tables']), 'backfilled_leads' => $count]), PHP_EOL;
} catch (Throwable $error) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'Content migration failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if ($db) {
        $db->query("SELECT RELEASE_LOCK('kipa-content-migration')");
    }
}
