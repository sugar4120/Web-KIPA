<?php
declare(strict_types=1);

define('KIPA_API_BOOTSTRAP', true);
require __DIR__ . '/../api/mailer.php';

function kipa_otp_test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$validConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'sender@example.com',
    'smtp_password' => 'app-password',
    'smtp_from_email' => 'sender@example.com',
];
$smtp = kipa_smtp_config($validConfig);
kipa_otp_test_assert($smtp === [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'sender@example.com',
    'password' => 'app-password',
    'from_email' => 'sender@example.com',
    'from_name' => 'KIPA Việt Hàn',
], 'A valid SMTP configuration did not normalize as expected.');
kipa_otp_test_assert(
    kipa_smtp_config([...$validConfig, 'smtp_from_name' => 'KIPA'])['from_name'] === 'KIPA',
    'A custom sender name was not honored.'
);

$invalidConfigs = [
    'missing password' => array_diff_key($validConfig, ['smtp_password' => true]),
    'empty host' => [...$validConfig, 'smtp_host' => ''],
    'host with CRLF' => [...$validConfig, 'smtp_host' => "smtp.gmail.com\r\nX"],
    'string port' => [...$validConfig, 'smtp_port' => '587'],
    'port out of range' => [...$validConfig, 'smtp_port' => 70000],
    'invalid sender' => [...$validConfig, 'smtp_from_email' => 'not-an-email'],
    'sender name with CRLF' => [...$validConfig, 'smtp_from_name' => "KIPA\r\nBcc: x"],
    'credentials with CRLF' => [...$validConfig, 'smtp_password' => "a\r\nb"],
];
foreach ($invalidConfigs as $label => $config) {
    try {
        kipa_smtp_config($config);
        throw new LogicException("SMTP configuration with {$label} was accepted.");
    } catch (RuntimeException) {
    }
}

kipa_otp_test_assert(
    kipa_otp_normalize_email('  Hoc.Vien@Example.COM ') === 'hoc.vien@example.com',
    'Email normalization did not trim and lowercase.'
);
foreach (['', 'not-an-email', "a@b\r\nc.com", 'a@' . str_repeat('b', 260) . '.com'] as $invalid) {
    try {
        kipa_otp_normalize_email($invalid);
        throw new LogicException('An invalid email address was accepted: ' . $invalid);
    } catch (InvalidArgumentException) {
    }
}

for ($i = 0; $i < 50; $i++) {
    $otp = kipa_otp_generate_code();
    kipa_otp_test_assert(preg_match('/^\d{6}$/', $otp) === 1, 'Generated OTP is not six digits: ' . $otp);
}

$hash = kipa_otp_hash_code('a@example.com', '123456', str_repeat('k', 32));
kipa_otp_test_assert(preg_match('/^[0-9a-f]{64}$/', $hash) === 1, 'OTP hash is not a SHA-256 hex digest.');
kipa_otp_test_assert(
    $hash === kipa_otp_hash_code('a@example.com', '123456', str_repeat('k', 32)),
    'OTP hashing is not deterministic.'
);
kipa_otp_test_assert(
    $hash !== kipa_otp_hash_code('b@example.com', '123456', str_repeat('k', 32))
        && $hash !== kipa_otp_hash_code('a@example.com', '654321', str_repeat('k', 32)),
    'OTP hashes must be bound to both the email and the code.'
);

// Header-injection attempts must be rejected before any network connection.
foreach ([["user@example.com\r\nBcc: x@y.z", 'Subject'], ['user@example.com', "Subject\r\nBcc: x@y.z"]] as [$to, $subject]) {
    try {
        kipa_send_mail($smtp, $to, $subject, 'body');
        throw new LogicException('A mail header injection attempt was accepted.');
    } catch (InvalidArgumentException) {
    }
}

echo "OTP mailer regression test passed.\n";
