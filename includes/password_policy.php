<?php

declare(strict_types=1);

/**
 * Password policy (2026): min 12, max 128 UTF-8 code units, composition rules,
 * reject if found in Have I Been Pwned (same as BAS).
 */

require_once __DIR__ . '/config.php';

function abas_password_utf8_length(string $s): int
{
    if (function_exists('mb_strlen')) {
        return (int) mb_strlen($s, 'UTF-8');
    }

    return strlen($s);
}

/**
 * @return bool|null true = found in leak DB, false = not found, null = API/network failure
 */
function abas_password_hibp_is_pwned(string $pwd): ?bool
{
    $hash = strtoupper(hash('sha1', $pwd));
    if (strlen($hash) !== 40) {
        return null;
    }
    $prefix = substr($hash, 0, 5);
    $suffix = substr($hash, 5);

    $url = 'https://api.pwnedpasswords.com/range/' . $prefix;
    $streamHeaders = "User-Agent: ABA-Service-PasswordCheck\r\nAccept: text/plain\r\nAdd-Padding: true\r\n";

    $tryCurl = static function (string $requestUrl) use ($streamHeaders): ?string {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($requestUrl);
        if ($ch === false) {
            return null;
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: ABA-Service-PasswordCheck',
                'Accept: text/plain',
                'Add-Padding: true',
            ],
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
        ];
        $caFile = abas_env('CURL_CAINFO');
        if ($caFile !== null && $caFile !== '' && is_readable($caFile)) {
            $opts[CURLOPT_CAINFO] = $caFile;
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code === 200 && is_string($raw)) {
            return $raw;
        }

        return null;
    };

    $body = null;
    for ($attempt = 0; $attempt < 2 && $body === null; $attempt++) {
        if ($attempt > 0) {
            usleep(200000);
        }
        $body = $tryCurl($url);
    }
    if ($body === null) {
        $ssl = ['verify_peer' => true, 'verify_peer_name' => true];
        $caFile = abas_env('CURL_CAINFO');
        if ($caFile !== null && $caFile !== '' && is_readable($caFile)) {
            $ssl['cafile'] = $caFile;
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $streamHeaders,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
            'ssl' => $ssl,
        ]);
        $fallback = @file_get_contents($url, false, $ctx);
        if (is_string($fallback)) {
            $body = $fallback;
        }
    }
    if ($body === null || !is_string($body)) {
        return null;
    }

    foreach (explode("\n", $body) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode(':', $line, 2);
        if (count($parts) < 2) {
            continue;
        }
        if (strcasecmp($parts[0], $suffix) === 0) {
            return true;
        }
    }

    return false;
}

/** @return string|null Fejlbesked på dansk, eller null hvis OK */
function abas_password_validate(string $pwd): ?string
{
    $len = abas_password_utf8_length($pwd);
    if ($len < 12) {
        return 'Adgangskode skal være mindst 12 tegn.';
    }
    if ($len > 128) {
        return 'Adgangskode må højst være 128 tegn.';
    }

    if (!preg_match('/\p{Ll}/u', $pwd)) {
        return 'Adgangskode skal indeholde mindst ét lille bogstav.';
    }
    if (!preg_match('/\p{Lu}/u', $pwd)) {
        return 'Adgangskode skal indeholde mindst ét stort bogstav.';
    }
    if (!preg_match('/\p{N}/u', $pwd)) {
        return 'Adgangskode skal indeholde mindst ét tal.';
    }
    if (!preg_match('/[^\p{L}\p{N}\s]/u', $pwd)) {
        return 'Adgangskode skal indeholde mindst ét specialtegn (fx tegnsætning eller symbol).';
    }

    $pwned = abas_password_hibp_is_pwned($pwd);
    if ($pwned === null) {
        return 'Kunne ikke tjekke adgangskode mod kendte datalæk. Prøv igen om lidt.';
    }
    if ($pwned) {
        return 'Denne adgangskode findes i kendte datalæk. Vælg et andet.';
    }

    return null;
}

/** @return list<string> */
function abas_password_policy_rule_labels(): array
{
    return [
        '12–128 tegn',
        'Adgangskoderne matcher',
        'Mindst ét lille bogstav',
        'Mindst ét stort bogstav',
        'Mindst ét tal',
        'Mindst ét specialtegn',
        'Ikke fundet i kendte datalæk',
    ];
}
