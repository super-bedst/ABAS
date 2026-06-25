<?php

declare(strict_types=1);

function abas_load_env(string $root): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    foreach (['.env', '.env.local', 'env.local'] as $file) {
        $path = $root . DIRECTORY_SEPARATOR . $file;
        if (!is_readable($path)) {
            continue;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
    $loaded = true;
    require_once __DIR__ . '/datetime.php';
    abas_datetime_bootstrap();
}

function abas_env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        return $default;
    }

    return $v;
}

function abas_root(): string
{
    return dirname(__DIR__);
}

function abas_config(): array
{
    $root = abas_root();
    abas_load_env($root);

    return [
        'app_name' => abas_env('APP_NAME', 'ABA Service'),
        'app_url' => rtrim((string) abas_env('APP_URL', 'http://localhost'), '/'),
        'app_env' => abas_env('APP_ENV', 'local'),
        'db' => [
            'host' => abas_env('DB_HOST', '127.0.0.1'),
            'port' => (int) abas_env('DB_PORT', '3306'),
            'name' => abas_env('DB_NAME', 'abas'),
            'user' => abas_env('DB_USER', 'abas_app'),
            'pass' => abas_env('DB_PASS', 'B*qs89j1Sg*V#5y*G$LHMCC3Ia%f'),        ],
        'trekant' => [
            'url' => rtrim((string) abas_env('TREKANT_API_URL', 'https://api.trekantbrand.dk'), '/'),
            'user' => abas_env('TREKANT_API_USER', 'NKI'),
            'pass' => abas_env('TREKANT_API_PASS', 'Test1234'),
            'term' => abas_env('TREKANT_TERM', 'ABAS'),
        ],
        'mail' => [
            'from' => abas_env('MAIL_FROM', 'noreply@trekantbrand.dk'),
            'from_name' => abas_env('MAIL_FROM_NAME', 'ABA Service'),
            'smtp_host' => abas_env('SMTP_HOST'),
            'smtp_port' => (int) abas_env('SMTP_PORT', '587'),
            'smtp_user' => abas_env('SMTP_USER'),
            'smtp_pass' => abas_env('SMTP_PASS'),
            'smtp_secure' => strtolower((string) abas_env('SMTP_SECURE', '')),
        ],
        'sms' => [
            'enabled' => abas_env('SMS_ENABLED', '1') !== '0',
            'gateway' => abas_env('SMS_GATEWAY', 'bas'),
            'bas_url' => rtrim((string) abas_env('BAS_SMS_API_URL', ''), '/'),
            'bas_token' => (string) abas_env('BAS_SMS_API_TOKEN', ''),
            'bas_system' => (string) abas_env('BAS_SMS_SYSTEM', 'PMS'),
            'sender' => (string) abas_env('BAS_SMS_SENDER', '+4541140602'),
            'dedupe_seconds' => (int) abas_env('BAS_SMS_DEDUPE_SECONDS', '120'),
            'send_replies' => abas_env('SMS_SEND_REPLIES', '1') !== '0',
            'inbound_secret' => (string) abas_env('SMS_INBOUND_SECRET', ''),
        ],
    ];
}
require_once __DIR__ . '/paths.php';

function abas_setting(mysqli $conn, string $key, ?string $default = null): ?string
{
    if (!isset($GLOBALS['_abas_settings_cache'])) {
        $GLOBALS['_abas_settings_cache'] = [];
    }
    if (!array_key_exists($key, $GLOBALS['_abas_settings_cache'])) {
        $stmt = $conn->prepare('SELECT `value` FROM system_settings WHERE `key` = ? LIMIT 1');
        if (!$stmt) {
            return $default;
        }
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $GLOBALS['_abas_settings_cache'][$key] = $row['value'] ?? $default;
    }

    return $GLOBALS['_abas_settings_cache'][$key];
}

function abas_set_setting(mysqli $conn, string $key, string $value): void
{
    $stmt = $conn->prepare('INSERT INTO system_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    if ($stmt) {
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
        $stmt->close();
    }
    if (isset($GLOBALS['_abas_settings_cache'])) {
        unset($GLOBALS['_abas_settings_cache'][$key]);
    }
}
