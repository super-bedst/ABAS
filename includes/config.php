<?php

declare(strict_types=1);

function abas_load_env(string $root): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    foreach (['.env', '.env.local'] as $file) {
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
            'name' => abas_env('DB_NAME', 'aba_service'),
            'user' => abas_env('DB_USER', 'root'),
            'pass' => abas_env('DB_PASS', ''),
        ],
        'trekant' => [
            'url' => rtrim((string) abas_env('TREKANT_API_URL', 'https://api.trekantbrand.dk'), '/'),
            'user' => abas_env('TREKANT_API_USER', ''),
            'pass' => abas_env('TREKANT_API_PASS', ''),
            'term' => abas_env('TREKANT_TERM', 'ABAS'),
        ],
        'mail' => [
            'from' => abas_env('MAIL_FROM', 'noreply@trekantbrand.dk'),
            'from_name' => abas_env('MAIL_FROM_NAME', 'ABA Service'),
        ],
    ];
}

function abas_setting(mysqli $conn, string $key, ?string $default = null): ?string
{
    $stmt = $conn->prepare('SELECT `value` FROM system_settings WHERE `key` = ? LIMIT 1');
    if (!$stmt) {
        return $default;
    }
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return $row['value'] ?? $default;
}

function abas_set_setting(mysqli $conn, string $key, string $value): void
{
    $stmt = $conn->prepare('INSERT INTO system_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    if ($stmt) {
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
        $stmt->close();
    }
}
