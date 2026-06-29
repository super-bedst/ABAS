<?php

declare(strict_types=1);

function abas_bas_sso_enabled(): bool
{
    $flag = abas_env('BAS_SSO_ENABLED', '1');
    if ($flag === '0' || strtolower($flag) === 'false') {
        return false;
    }

    return abas_bas_sso_issuer() !== '' && abas_bas_sso_client_id() !== '';
}

function abas_bas_sso_issuer(): string
{
    $issuer = trim((string) (abas_env('BAS_SSO_ISSUER') ?? ''));
    if ($issuer !== '') {
        return rtrim($issuer, '/');
    }

    return '';
}

function abas_bas_sso_client_id(): string
{
    return trim((string) (abas_env('BAS_SSO_CLIENT_ID', 'abas-web') ?? 'abas-web'));
}

function abas_bas_sso_client_secret(): string
{
    return (string) (abas_env('BAS_SSO_CLIENT_SECRET') ?? '');
}

function abas_bas_sso_endpoint(string $path): string
{
    return abas_bas_sso_issuer() . '/' . ltrim($path, '/');
}

function abas_bas_sso_embed_page_url(): string
{
    $override = trim((string) (abas_env('BAS_SSO_EMBED_URL') ?? ''));
    if ($override !== '') {
        return $override;
    }

    return abas_full_url('embed.php');
}

/** Matcher BAS vc_abas.php — redirect_uri til token exchange ved embed-grant. */
function abas_bas_sso_embed_redirect_uri(): string
{
    $base = rtrim(abas_bas_sso_embed_page_url(), '/');
    if (!str_contains($base, '?')) {
        return $base . '/callback';
    }

    return $base;
}

function abas_bas_sso_login_redirect_uri(): string
{
    $override = trim((string) (abas_env('BAS_SSO_REDIRECT_URI') ?? ''));
    if ($override !== '') {
        return $override;
    }

    return abas_full_url('sso/callback.php');
}

function abas_bas_sso_base64url(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function abas_bas_sso_base64url_decode(string $data): string
{
    $pad = 4 - (strlen($data) % 4);
    if ($pad < 4) {
        $data .= str_repeat('=', $pad);
    }

    return (string) base64_decode(strtr($data, '-_', '+/'), true);
}

function abas_bas_sso_random_token(int $bytes = 32): string
{
    return abas_bas_sso_base64url(random_bytes($bytes));
}

/** @return array<string, mixed>|null */
function abas_bas_sso_fetch_discovery(): ?array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }
    if (!function_exists('curl_init')) {
        return null;
    }
    $url = abas_bas_sso_endpoint('.well-known/openid-configuration');
    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($body) || $code < 200 || $code >= 300) {
        return null;
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        return null;
    }
    $cached = $decoded;

    return $cached;
}

/** @return list<array<string, mixed>> */
function abas_bas_sso_fetch_jwks(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }
    $discovery = abas_bas_sso_fetch_discovery();
    $jwksUri = is_array($discovery) ? (string) ($discovery['jwks_uri'] ?? '') : '';
    if ($jwksUri === '') {
        $jwksUri = abas_bas_sso_endpoint('oidc/jwks');
    }
    if (!function_exists('curl_init')) {
        return [];
    }
    $ch = curl_init($jwksUri);
    if ($ch === false) {
        return [];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($body) || $code < 200 || $code >= 300) {
        return [];
    }
    $decoded = json_decode($body, true);
    $keys = is_array($decoded) && is_array($decoded['keys'] ?? null) ? $decoded['keys'] : [];
    $cached = $keys;

    return $keys;
}

function abas_bas_sso_jwk_to_pem(array $jwk): ?string
{
    if (($jwk['kty'] ?? '') !== 'RSA' || !isset($jwk['n'], $jwk['e'])) {
        return null;
    }
    $n = abas_bas_sso_base64url_decode((string) $jwk['n']);
    $e = abas_bas_sso_base64url_decode((string) $jwk['e']);
    if ($n === '' || $e === '') {
        return null;
    }
    $modulus = "\x00" . $n;
    $exponent = $e;
    $modulusEnc = abas_bas_sso_asn1_integer($modulus);
    $exponentEnc = abas_bas_sso_asn1_integer($exponent);
    $rsaPub = abas_bas_sso_asn1_sequence($modulusEnc . $exponentEnc);
    $bitString = "\x00" . $rsaPub;
    $bitStringEnc = "\x03" . abas_bas_sso_asn1_length(strlen($bitString)) . $bitString;
    $oid = abas_bas_sso_asn1_sequence("\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00");
    $pubKey = abas_bas_sso_asn1_sequence($oid . $bitStringEnc);
    $pem = "-----BEGIN PUBLIC KEY-----\n"
        . chunk_split(base64_encode($pubKey), 64, "\n")
        . "-----END PUBLIC KEY-----\n";

    return $pem;
}

function abas_bas_sso_asn1_length(int $length): string
{
    if ($length < 128) {
        return chr($length);
    }
    $bytes = '';
    while ($length > 0) {
        $bytes = chr($length & 0xff) . $bytes;
        $length >>= 8;
    }

    return chr(0x80 | strlen($bytes)) . $bytes;
}

function abas_bas_sso_asn1_integer(string $value): string
{
    if ($value[0] !== "\x00" && (ord($value[0]) & 0x80) !== 0) {
        $value = "\x00" . $value;
    }

    return "\x02" . abas_bas_sso_asn1_length(strlen($value)) . $value;
}

function abas_bas_sso_asn1_sequence(string $value): string
{
    return "\x30" . abas_bas_sso_asn1_length(strlen($value)) . $value;
}

/** @return array<string, mixed>|null */
function abas_bas_sso_jwt_verify(string $jwt): ?array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    $header = json_decode(abas_bas_sso_base64url_decode($parts[0]), true);
    $payload = json_decode(abas_bas_sso_base64url_decode($parts[1]), true);
    if (!is_array($header) || !is_array($payload)) {
        return null;
    }
    $kid = (string) ($header['kid'] ?? '');
    $pem = null;
    foreach (abas_bas_sso_fetch_jwks() as $jwk) {
        if (!is_array($jwk)) {
            continue;
        }
        if ($kid !== '' && ($jwk['kid'] ?? '') !== $kid) {
            continue;
        }
        $pem = abas_bas_sso_jwk_to_pem($jwk);
        if ($pem !== null) {
            break;
        }
    }
    if ($pem === null) {
        return null;
    }
    $input = $parts[0] . '.' . $parts[1];
    $sig = abas_bas_sso_base64url_decode($parts[2]);
    $ok = openssl_verify($input, $sig, $pem, OPENSSL_ALGO_SHA256);
    if ($ok !== 1) {
        return null;
    }
    $exp = (int) ($payload['exp'] ?? 0);
    if ($exp > 0 && $exp < time()) {
        return null;
    }
    $iss = (string) ($payload['iss'] ?? '');
    if ($iss !== '' && $iss !== abas_bas_sso_issuer()) {
        return null;
    }
    $aud = $payload['aud'] ?? null;
    $clientId = abas_bas_sso_client_id();
    if ($aud !== null) {
        $audList = is_array($aud) ? $aud : [$aud];
        if (!in_array($clientId, array_map('strval', $audList), true)) {
            return null;
        }
    }

    return $payload;
}

/** @return array<string, mixed>|null */
function abas_bas_sso_exchange_authorization_code(
    string $code,
    string $redirectUri,
    string $codeVerifier = ''
): ?array {
    if (!function_exists('curl_init')) {
        return null;
    }
    $discovery = abas_bas_sso_fetch_discovery();
    $tokenUrl = is_array($discovery) ? (string) ($discovery['token_endpoint'] ?? '') : '';
    if ($tokenUrl === '') {
        $tokenUrl = abas_bas_sso_endpoint('oidc/token');
    }
    $fields = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'client_id' => abas_bas_sso_client_id(),
    ];
    if ($codeVerifier !== '') {
        $fields['code_verifier'] = $codeVerifier;
    }
    $secret = abas_bas_sso_client_secret();
    if ($secret !== '') {
        $fields['client_secret'] = $secret;
    }
    $ch = curl_init($tokenUrl);
    if ($ch === false) {
        return null;
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
    ]);
    $body = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!is_string($body) || $httpCode < 200 || $httpCode >= 300) {
        if (function_exists('abas_log_error')) {
            $decoded = is_string($body) ? json_decode($body, true) : null;
            $idpError = is_array($decoded) ? (string) ($decoded['error_description'] ?? $decoded['error'] ?? '') : '';
            abas_log_error('bas_sso', 'Token exchange failed', [
                'http' => $httpCode,
                'redirect_uri' => $redirectUri,
                'idp_error' => $idpError !== '' ? $idpError : null,
                'body' => is_string($body) ? substr($body, 0, 300) : null,
            ]);
        }

        return null;
    }
    $decoded = json_decode($body, true);

    return is_array($decoded) ? $decoded : null;
}

function abas_bas_sso_build_authorize_url(string $redirectUri, string $scope = 'openid profile email'): string
{
    $verifier = abas_bas_sso_random_token(48);
    $challenge = abas_bas_sso_base64url(hash('sha256', $verifier, true));
    $_SESSION['abas_bas_sso_pkce_verifier'] = $verifier;
    $state = abas_bas_sso_random_token(16);
    $_SESSION['abas_bas_sso_oauth_state'] = $state;

    $discovery = abas_bas_sso_fetch_discovery();
    $authorizeUrl = is_array($discovery) ? (string) ($discovery['authorization_endpoint'] ?? '') : '';
    if ($authorizeUrl === '') {
        $authorizeUrl = abas_bas_sso_endpoint('oidc/authorize');
    }

    return $authorizeUrl . '?' . http_build_query([
        'client_id' => abas_bas_sso_client_id(),
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => $scope,
        'state' => $state,
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ]);
}
