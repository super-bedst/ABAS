<?php

declare(strict_types=1);

/** Tom streng = app i DocumentRoot (fx public/ på teknikweb2). */
function abas_normalize_public_base(string $base): string
{
    $base = rtrim(str_replace('\\', '/', $base), '/');
    if ($base === '' || $base === '/' || $base === '.') {
        return '';
    }

    return $base;
}

function abas_public_base_from_script(string $script): string
{
    $script = str_replace('\\', '/', $script);
    if ($script === '' || $script === '/') {
        return '';
    }

    if (preg_match('#^(.*)/public(?:/|$)#', $script, $m)) {
        return abas_normalize_public_base($m[1] . '/public');
    }

    $subpathPatterns = [
        '#^(.*)/admin/[^/]+\.php$#',
        '#^(.*)/admin/?$#',
        '#^(.*)/virksomhed/[^/]+\.php$#',
        '#^(.*)/virksomhed/?$#',
        '#^(.*)/sso/[^/]+\.php$#',
        '#^(.*)/scim/v2/index\.php$#',
        '#^(.*)/scim/v2/.+#',
        '#^(.*)/api/v1/index\.php$#',
        '#^(.*)/api/v1/.+#',
        '#^(.*)/api/.+#',
    ];
    foreach ($subpathPatterns as $pattern) {
        if (preg_match($pattern, $script, $m)) {
            return abas_normalize_public_base($m[1]);
        }
    }

    $dir = dirname($script);

    return abas_normalize_public_base($dir);
}

function abas_public_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $override = abas_env('APP_BASE_PATH');
    if ($override !== null && $override !== '') {
        $base = abas_normalize_public_base('/' . trim(str_replace('\\', '/', $override), '/'));

        return $base;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = abas_public_base_from_script($script);

    return $base;
}

function abas_url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = abas_public_base();

    // Undgå /admin/admin/ når APP_BASE_PATH=/admin og path starter med admin/
    if ($path !== '' && str_starts_with($path, 'admin/') && $base !== '' && str_ends_with($base, '/admin')) {
        $path = substr($path, strlen('admin/'));
    }

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
    if (preg_match('#^https?://#i', $path)) {
        $location = $path;
    } elseif (str_starts_with($path, '/')) {
        $location = $path;
    } else {
        $location = abas_url($path);
    }

    header('Location: ' . $location, true, $code);
    exit;
}

function abas_full_url(string $path): string
{
    return rtrim(abas_app_url(), '/') . '/' . ltrim($path, '/');
}

/** URL til filer uden for public/ (fx cron/*.php). */
function abas_app_root_url(string $path = ''): string
{
    $app = rtrim(abas_app_url(), '/');
    $root = (string) preg_replace('#/public$#', '', $app);
    $path = ltrim(str_replace('\\', '/', $path), '/');

    return $path === '' ? $root : $root . '/' . $path;
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
