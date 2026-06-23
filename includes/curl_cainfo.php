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

    $resolved = '';

    return null;
}

function abas_curl_ssl_options(): array
{
    $opts = [
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    $cainfo = abas_curl_resolve_ca_bundle_path();
    if ($cainfo !== null) {
        $opts[CURLOPT_CAINFO] = $cainfo;
    }

    return $opts;
}
