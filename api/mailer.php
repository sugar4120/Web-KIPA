<?php
declare(strict_types=1);

if (!defined('KIPA_API_BOOTSTRAP')) {
    http_response_code(404);
    exit;
}

const KIPA_MAIL_DOMAIN = 'kipaviethan.edu.vn';
const KIPA_OTP_LENGTH = 6;
const KIPA_OTP_TTL_MINUTES = 10;
const KIPA_OTP_MAX_ATTEMPTS = 5;

/**
 * Validates the optional smtp_* keys of the private hosting configuration.
 * Credentials (e.g. a Gmail App Password) live only in kipa-private/config.php.
 */
function kipa_smtp_config(array $config): array
{
    foreach (['smtp_host', 'smtp_username', 'smtp_password', 'smtp_from_email'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') {
            throw new RuntimeException('SMTP configuration is incomplete.');
        }
    }
    $host = $config['smtp_host'];
    if (strlen($host) > 253 || !preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
        throw new RuntimeException('SMTP host is invalid.');
    }
    $port = $config['smtp_port'] ?? 587;
    if (!is_int($port) || $port < 1 || $port > 65535) {
        throw new RuntimeException('SMTP port is invalid.');
    }
    $fromEmail = $config['smtp_from_email'];
    if (strlen($fromEmail) > 254 || filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
        throw new RuntimeException('SMTP sender address is invalid.');
    }
    $fromName = $config['smtp_from_name'] ?? 'KIPA Việt Hàn';
    if (!is_string($fromName) || $fromName === '' || strlen($fromName) > 120 || preg_match('/[\r\n\0]/', $fromName)) {
        throw new RuntimeException('SMTP sender name is invalid.');
    }
    if (preg_match('/[\r\n\0]/', $config['smtp_username'] . $config['smtp_password'])) {
        throw new RuntimeException('SMTP credentials are invalid.');
    }
    return [
        'host' => $host,
        'port' => $port,
        'username' => $config['smtp_username'],
        'password' => $config['smtp_password'],
        'from_email' => $fromEmail,
        'from_name' => $fromName,
    ];
}

/** @param resource $stream */
function kipa_smtp_expect($stream, array $codes): void
{
    $response = '';
    do {
        $line = fgets($stream, 2048);
        if ($line === false) {
            throw new RuntimeException('SMTP server closed the connection.');
        }
        $response .= $line;
    } while (isset($line[3]) && $line[3] === '-');
    if (!in_array((int)substr($response, 0, 3), $codes, true)) {
        throw new RuntimeException('Unexpected SMTP response code ' . substr($response, 0, 3) . '.');
    }
}

/** @param resource $stream */
function kipa_smtp_command($stream, string $command, array $codes): void
{
    if (fwrite($stream, $command . "\r\n") === false) {
        throw new RuntimeException('Cannot write to the SMTP server.');
    }
    kipa_smtp_expect($stream, $codes);
}

/**
 * Sends one plain-text UTF-8 email over SMTP with STARTTLS and AUTH LOGIN
 * (compatible with smtp.gmail.com:587 using an App Password).
 */
function kipa_send_mail(array $smtp, string $to, string $subject, string $body): void
{
    if (strlen($to) > 254 || preg_match('/[\r\n\0]/', $to) || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Mail recipient is invalid.');
    }
    if ($subject === '' || strlen($subject) > 255 || preg_match('/[\r\n\0]/', $subject)) {
        throw new InvalidArgumentException('Mail subject is invalid.');
    }
    $context = stream_context_create(['ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'peer_name' => $smtp['host'],
        'SNI_enabled' => true,
    ]]);
    $stream = @stream_socket_client(
        'tcp://' . $smtp['host'] . ':' . $smtp['port'],
        $errorCode,
        $errorMessage,
        15,
        STREAM_CLIENT_CONNECT,
        $context
    );
    if ($stream === false) {
        throw new RuntimeException('Cannot connect to the SMTP server.');
    }
    stream_set_timeout($stream, 15);
    try {
        kipa_smtp_expect($stream, [220]);
        kipa_smtp_command($stream, 'EHLO ' . KIPA_MAIL_DOMAIN, [250]);
        kipa_smtp_command($stream, 'STARTTLS', [220]);
        if (stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
            throw new RuntimeException('Cannot establish TLS with the SMTP server.');
        }
        kipa_smtp_command($stream, 'EHLO ' . KIPA_MAIL_DOMAIN, [250]);
        kipa_smtp_command($stream, 'AUTH LOGIN', [334]);
        kipa_smtp_command($stream, base64_encode($smtp['username']), [334]);
        kipa_smtp_command($stream, base64_encode($smtp['password']), [235]);
        kipa_smtp_command($stream, 'MAIL FROM:<' . $smtp['from_email'] . '>', [250]);
        kipa_smtp_command($stream, 'RCPT TO:<' . $to . '>', [250, 251]);
        kipa_smtp_command($stream, 'DATA', [354]);
        $headers = [
            'From: =?UTF-8?B?' . base64_encode($smtp['from_name']) . '?= <' . $smtp['from_email'] . '>',
            'To: <' . $to . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . KIPA_MAIL_DOMAIN . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n"
            . chunk_split(base64_encode($body), 76, "\r\n");
        if (fwrite($stream, $message . ".\r\n") === false) {
            throw new RuntimeException('Cannot write the mail body to the SMTP server.');
        }
        kipa_smtp_expect($stream, [250]);
        kipa_smtp_command($stream, 'QUIT', [221]);
    } finally {
        fclose($stream);
    }
}

function kipa_otp_normalize_email(string $email): string
{
    $email = strtolower(trim($email));
    if ($email === '' || strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Email address is invalid.');
    }
    return $email;
}

function kipa_otp_generate_code(): string
{
    return str_pad((string)random_int(0, 10 ** KIPA_OTP_LENGTH - 1), KIPA_OTP_LENGTH, '0', STR_PAD_LEFT);
}

function kipa_otp_hash_code(string $email, string $code, string $appKey): string
{
    return hash_hmac('sha256', $email . ':' . $code, $appKey);
}
