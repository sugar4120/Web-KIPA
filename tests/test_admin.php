<?php
declare(strict_types=1);

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/../api/bootstrap.php';

function admin_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec('CREATE TABLE kipa_admins (id INTEGER PRIMARY KEY, username TEXT, must_change_password INTEGER)');
$db->exec("INSERT INTO kipa_admins VALUES (1, 'admin', 1)");
$_SESSION = [];
admin_assert(kipa_current_admin($db) === null, 'Anonymous users must remain unauthenticated.');
$_SESSION = ['admin_id' => 1, 'must_change_password' => true];
admin_assert((bool)kipa_current_admin($db)['must_change_password'], 'Initial password must require a change.');
$db->exec('UPDATE kipa_admins SET must_change_password = 0 WHERE id = 1');
admin_assert(kipa_current_admin($db) === null && !isset($_SESSION['admin_id']), 'Other sessions using the initial password must be invalidated after a password change.');
$_SESSION = ['admin_id' => 1];
admin_assert(!(bool)kipa_current_admin($db)['must_change_password'], 'A fresh login after changing the password must have access.');
$db->exec('DELETE FROM kipa_admins');
admin_assert(kipa_current_admin($db) === null && !isset($_SESSION['admin_id']), 'Deleted accounts must lose access.');
echo "Administrator password-change session checks passed.\n";
