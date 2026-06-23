<?php

declare(strict_types=1);

function abas_public_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $override = abas_env('APP_BASE_PATH');
    if ($override !== null && $override !== '') {
        $base = '/' . trim(str_replace('\\', '/', $override), '/');

        return $base;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(.*)/public(?:/|$)#', $script, $m)) {
        $base = $m[1] . '/public';

        return $base;
    }

    $dir = rtrim(dirname($script), '/');
    if ($dir === '' || $dir === '/' || $dir === '.') {
        $base = '';
    } else {
        $base = $dir;
    }

    return $base;
}

function abas_url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = abas_public_base();

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function abas_app_url(): string
{
    $configured = abas_env('APP_URL');
    if ($configured) {
        $host = parse_url($configured, PHP_URL_HOST);
        if ($host && !in_array($host, ['localhost', '127.0.0.1'], true)) {
            return rtrim($configured, '/');
        }
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = abas_public_base();

    return $scheme . '://' . $host . ($base === '' ? '' : $base);
}

function abas_redirect(string $path, int $code = 302): never
{
    header('Location: ' . abas_url($path), true, $code);
    exit;
}

function abas_full_url(string $path): string
{
    return rtrim(abas_app_url(), '/') . '/' . ltrim($path, '/');
}

/** Officiel BAS/Inmobile webhook-URL (kræver mod_rewrite i public/.htaccess). */
function abas_sms_inbound_webhook_url(): string
{
    return abas_full_url('api/v1/sms/inbound');
}

function abas_asset_url(string $path): string
{
    $rel = ltrim(str_replace('\\', '/', $path), '/');
    $file = dirname(__DIR__) . '/public/' . $rel;
    $version = is_file($file) ? (string) filemtime($file) : (string) time();

    return abas_url($rel) . '?v=' . $version;
}
