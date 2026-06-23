<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/curl_cainfo.php';

function abas_sms_normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D/', '', trim($phone)) ?? '';
    $digits = ltrim($digits, '0');
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) === 8) {
        return '45' . $digits;
    }
    if (strlen($digits) > 8 && str_starts_with($digits, '45')) {
        return $digits;
    }

    return $digits;
}

function abas_sms_phones_match(string $a, string $b): bool
{
    $na = abas_sms_normalize_phone($a);
    $nb = abas_sms_normalize_phone($b);
    if ($na === '' || $nb === '') {
        return false;
    }
    if ($na === $nb) {
        return true;
    }

    return strlen($na) >= 8 && strlen($nb) >= 8 && substr($na, -8) === substr($nb, -8);
}

function abas_sms_gateway_enabled(): bool
{
    $cfg = abas_config()['sms'];

    return $cfg['enabled']
        && $cfg['gateway'] === 'bas'
        && $cfg['bas_url'] !== ''
        && $cfg['bas_token'] !== '';
}

/**
 * Send SMS via BAS Api/V2/Sms/sendSms.php (samme mønster som PMS/ISM).
 *
 * @return array{ok:bool, skipped?:bool, http?:int, response?:mixed, error?:string}
 */
function abas_sms_send_via_bas(string $to, string $body, string $trigger = 'abas'): array
{
    if (!abas_sms_gateway_enabled()) {
        return ['ok' => false, 'skipped' => true];
    }

    $cfg = abas_config()['sms'];
    $phone = abas_sms_normalize_phone($to);
    if ($phone === '' || trim($body) === '') {
        return ['ok' => false, 'error' => 'Tom modtager eller besked'];
    }

    $url = rtrim($cfg['bas_url'], '/') . '/Api/V2/Sms/sendSms.php';
    $payload = [
        'system' => $cfg['bas_system'],
        'sender' => $cfg['sender'],
        'dedupeWindowSeconds' => max(0, (int) $cfg['dedupe_seconds']),
        'messages' => [
            [
                'alarmId' => 'abas_' . preg_replace('/[^a-z0-9_-]/i', '_', $trigger) . '_' . time(),
                'text' => $body,
                'receivers' => [$phone],
            ],
        ],
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return ['ok' => false, 'error' => 'JSON encode fejlede'];
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $cfg['bas_token'],
    ];

    $ch = curl_init($url);
    $curlOpts = [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ] + abas_curl_ssl_options();
    curl_setopt_array($ch, $curlOpts);

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($errno) {
        return ['ok' => false, 'http' => $http, 'error' => 'cURL: ' . $err];
    }

    $decoded = json_decode((string) $raw, true);
    $ok = $http >= 200 && $http < 300 && is_array($decoded) && ($decoded['status'] ?? '') === 'Success';

    return [
        'ok' => $ok,
        'http' => $http,
        'response' => $decoded ?? $raw,
        'error' => $ok ? null : ('BAS SMS fejlede (HTTP ' . $http . ')'),
    ];
}
