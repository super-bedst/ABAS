<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * @param list<string> $envKeys Første ikke-tomme env-værdi bruges som hemmelighed.
 */
function abas_cron_resolve_secret(array $envKeys): string
{
    foreach ($envKeys as $envKey) {
        $secret = trim((string) abas_env($envKey, ''));
        if ($secret !== '') {
            return $secret;
        }
    }

    return '';
}

function abas_cron_request_credential(): string
{
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
        return $m[1];
    }

    return trim((string) ($_GET['key'] ?? $_POST['key'] ?? ''));
}

/**
 * @param list<string> $envKeys
 */
function abas_cron_verify_request(array $envKeys): bool
{
    $secret = abas_cron_resolve_secret($envKeys);
    if ($secret === '') {
        return false;
    }

    $credential = abas_cron_request_credential();
    if ($credential === '') {
        return false;
    }

    return hash_equals($secret, $credential);
}

/**
 * @param list<string> $envKeys
 */
function abas_cron_auth_error(array $envKeys, string $label): string
{
    if (abas_cron_resolve_secret($envKeys) === '') {
        return $label . '-nøgle er ikke sat på serveren (' . implode(' eller ', $envKeys) . ' i env.local)';
    }
    if (abas_cron_request_credential() === '') {
        return 'Manglende ' . $label . '-nøgle — tilføj ?key=<secret> eller Authorization: Bearer <secret>';
    }

    return 'Ugyldig ' . $label . '-nøgle';
}
