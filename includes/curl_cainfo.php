<?php

declare(strict_types=1);

/**
 * CA-bundle til cURL på Windows/WAMP (samme mønster som BAS bas_curl_cainfo.php).
 * Standardsti: C:\wamp64\bin\Certs\Cacerts\cacert.pem
 */
function abas_curl_resolve_ca_bundle_path(): ?string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved === '' ? null : $resolved;
    }

    $candidates = [];
    $env = abas_env('CURL_CAINFO');
    if ($env !== null && trim($env) !== '') {
        $candidates[] = trim($env);
    }
    $candidates[] = 'C:\\wamp64\\bin\\Certs\\Cacerts\\cacert.pem';
    foreach (['curl.cainfo', 'openssl.cafile'] as $iniKey) {
        $v = ini_get($iniKey);
        if (is_string($v) && trim($v) !== '') {
            $candidates[] = trim($v);
        }
    }
    foreach ($candidates as $path) {
        if (is_file($path) && is_readable($path)) {
            $resolved = $path;

            return $path;
        }
    }

    $phpExtras = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'cacert.pem';
    if (is_file($phpExtras) && is_readable($phpExtras)) {
        $resolved = $phpExtras;

        return $phpExtras;
    }

    $resolved = '';

    return null;
}

/**
 * Kør curl_exec uden at SSL-advarsler bliver til uncaught exceptions (Windows/WAMP).
 */
function abas_curl_exec(\CurlHandle $ch): string|false
{
    set_error_handler(static fn (): bool => true, E_WARNING);
    try {
        return curl_exec($ch);
    } finally {
        restore_error_handler();
    }
}

function abas_curl_ssl_options(): array
{
    $opts = [];
    $cainfo = abas_curl_resolve_ca_bundle_path();
    if ($cainfo !== null && $cainfo !== '') {
        $opts[CURLOPT_CAINFO] = $cainfo;
    }

    return $opts;
}
