<?php

declare(strict_types=1);

require_once __DIR__ . '/curl_cainfo.php';

/** @param array<int, mixed> $extra */
function abas_bas_sso_curl_options(array $extra = []): array
{
    return $extra + [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ] + abas_curl_ssl_options();
}

function abas_bas_sso_enabled(): bool
{
    return abas_bas_sso_disabled_reason() === null;
}

/** @return non-empty-string|null */
function abas_bas_sso_disabled_reason(): ?string
{
    $flag = abas_env('BAS_SSO_ENABLED', '1');
    if ($flag === '0' || strtolower($flag) === 'false') {
        return 'BAS SSO er slået fra (BAS_SSO_ENABLED=0).';
    }
    if (abas_bas_sso_issuer() === '') {
        return 'BAS SSO mangler BAS_SSO_ISSUER i env.local.';
    }
    if (abas_bas_sso_client_id() === '') {
        return 'BAS SSO mangler BAS_SSO_CLIENT_ID i env.local.';
    }

    return null;
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
    return trim((string) (abas_env('BAS_SSO_CLIENT_SECRET') ?? ''));
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
    try {
        return abas_bas_sso_base64url(random_bytes($bytes));
    } catch (Throwable) {
        $fallback = openssl_random_pseudo_bytes($bytes);
        if ($fallback === false) {
            throw new RuntimeException('Kunne ikke generere SSO-sikkerhedstoken.');
        }

        return abas_bas_sso_base64url($fallback);
    }
}

function abas_bas_sso_authorize_url(): string
{
    return abas_bas_sso_endpoint('oidc/authorize');
}

function abas_bas_sso_token_url(): string
{
    return abas_bas_sso_endpoint('oidc/token');
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
    curl_setopt_array($ch, abas_bas_sso_curl_options());
    $body = abas_curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    abas_curl_close($ch);
    if (!is_string($body) || $body === '' || $code < 200 || $code >= 300) {
        return null;
    }
    $body = ltrim($body, "\xEF\xBB\xBF");
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
    if (is_array($cached) && $cached !== []) {
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
    curl_setopt_array($ch, abas_bas_sso_curl_options());
    $body = abas_curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    abas_curl_close($ch);
    if (!is_string($body) || $body === '' || $code < 200 || $code >= 300) {
        return [];
    }
    $body = ltrim($body, "\xEF\xBB\xBF");
    $decoded = json_decode($body, true);
    $keys = is_array($decoded) && is_array($decoded['keys'] ?? null) ? $decoded['keys'] : [];
    if ($keys !== []) {
        $cached = $keys;
    }

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

/** @var string|null */
$GLOBALS['_abas_bas_sso_last_exchange_error'] = null;

function abas_bas_sso_last_exchange_error(): ?string
{
    return isset($GLOBALS['_abas_bas_sso_last_exchange_error'])
        ? $GLOBALS['_abas_bas_sso_last_exchange_error']
        : null;
}

function abas_bas_sso_set_last_exchange_error(?string $message): void
{
    $GLOBALS['_abas_bas_sso_last_exchange_error'] = $message;
}

/** @return array<string, mixed>|null */
function abas_bas_sso_exchange_authorization_code(
    string $code,
    string $redirectUri,
    string $codeVerifier = ''
): ?array {
    abas_bas_sso_set_last_exchange_error(null);

    if (!function_exists('curl_init')) {
        abas_bas_sso_set_last_exchange_error('cURL er ikke tilgængelig på serveren.');

        return null;
    }

    $tokenUrl = abas_bas_sso_token_url();
    $secret = abas_bas_sso_client_secret();
    $fields = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'client_id' => abas_bas_sso_client_id(),
    ];
    if ($codeVerifier !== '') {
        $fields['code_verifier'] = $codeVerifier;
    }
    if ($secret !== '') {
        $fields['client_secret'] = $secret;
    }

    return abas_bas_sso_token_request($tokenUrl, $fields, 'post');
}

function abas_bas_sso_format_token_error(string $body, int $httpCode): string
{
    $decoded = json_decode($body, true);
    if (is_array($decoded)) {
        $desc = trim((string) ($decoded['error_description'] ?? ''));
        $err = trim((string) ($decoded['error'] ?? ''));
        if ($desc !== '') {
            return 'BAS afviste token (HTTP ' . $httpCode . '): ' . $desc;
        }
        if ($err !== '') {
            return 'BAS afviste token (HTTP ' . $httpCode . '): ' . $err;
        }
    }

    if (preg_match('/<\s*(?:html|body|!doctype)/i', $body)) {
        return 'BAS SSO returnerede en serverfejl (HTTP ' . $httpCode
            . '). Tjek error-log på test2.beredskabsalarmering.dk — ABAS-konfigurationen ser OK ud.';
    }

    $plain = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');
    if ($plain !== '') {
        if (strlen($plain) > 120) {
            $plain = substr($plain, 0, 120) . '…';
        }

        return 'BAS afviste token (HTTP ' . $httpCode . '): ' . $plain;
    }

    return 'BAS token-endpoint returnerede HTTP ' . $httpCode . '.';
}

/**
 * @param array<string, string> $fields
 * @return array<string, mixed>|null
 */
function abas_bas_sso_token_request(string $tokenUrl, array $fields, string $authMode = 'post', ?string $basicAuth = null): ?array
{
    $ch = curl_init($tokenUrl);
    if ($ch === false) {
        abas_bas_sso_set_last_exchange_error('Kunne ikke oprette HTTP-klient mod BAS.');

        return null;
    }

    $headers = ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'];

    curl_setopt_array($ch, abas_bas_sso_curl_options([
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $headers,
    ]));

    $body = abas_curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    abas_curl_close($ch);

    if (!is_string($body) || $body === '') {
        $msg = $curlError !== ''
            ? 'Netværksfejl mod BAS: ' . $curlError
            : 'Tomt svar fra BAS token-endpoint (HTTP ' . $httpCode . ').';
        abas_bas_sso_set_last_exchange_error($msg);
        if (function_exists('abas_log_error')) {
            abas_log_error('bas_sso', 'Token exchange failed', [
                'http' => $httpCode,
                'curl_error' => $curlError !== '' ? $curlError : null,
                'ca_bundle' => abas_curl_resolve_ca_bundle_path(),
                'auth_mode' => $authMode,
            ]);
        }

        return null;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $msg = abas_bas_sso_format_token_error($body, $httpCode);
        abas_bas_sso_set_last_exchange_error($msg);
        if (function_exists('abas_log_error')) {
            $decoded = json_decode($body, true);
            abas_log_error('bas_sso', 'Token exchange failed', [
                'http' => $httpCode,
                'oauth_error' => is_array($decoded) ? ($decoded['error'] ?? null) : null,
                'body' => substr($body, 0, 300),
                'redirect_uri' => $fields['redirect_uri'] ?? null,
                'token_url' => $tokenUrl,
                'client_id' => $fields['client_id'] ?? null,
            ]);
        }

        return null;
    }

    $body = ltrim($body, "\xEF\xBB\xBF");
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        abas_bas_sso_set_last_exchange_error(
            'BAS returnerede ugyldigt token-svar (HTTP ' . $httpCode . '). Tjek BAS log.'
        );
        if (function_exists('abas_log_error')) {
            abas_log_error('bas_sso', 'Token exchange invalid JSON', [
                'http' => $httpCode,
                'body' => substr($body, 0, 300),
                'redirect_uri' => $fields['redirect_uri'] ?? null,
            ]);
        }

        return null;
    }
    if (trim((string) ($decoded['id_token'] ?? '')) === '') {
        abas_bas_sso_set_last_exchange_error('BAS token-svar mangler id_token.');
        if (function_exists('abas_log_error')) {
            abas_log_error('bas_sso', 'Token exchange missing id_token', [
                'http' => $httpCode,
                'keys' => array_keys($decoded),
            ]);
        }

        return null;
    }

    return $decoded;
}

/** @return array<string, mixed> */
function abas_bas_sso_oauth_cookie_params(int $ttlSeconds = 600): array
{
    return [
        'expires' => time() + $ttlSeconds,
        'path' => abas_session_cookie_path(),
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function abas_bas_sso_state_signing_key(): string
{
    $secret = abas_bas_sso_client_secret();
    if ($secret !== '') {
        return $secret;
    }

    return hash('sha256', 'abas-oauth-state|' . (abas_env('APP_URL') ?? 'abas'), true);
}

function abas_bas_sso_make_oauth_state(string $redirectUri): string
{
    $payload = [
        'n' => abas_bas_sso_random_token(12),
        'r' => $redirectUri,
        'e' => time() + 600,
    ];
    $json = (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $mac = hash_hmac('sha256', $json, abas_bas_sso_state_signing_key(), true);

    return abas_bas_sso_base64url($json) . '.' . abas_bas_sso_base64url($mac);
}

/** @return array{redirect_uri: string}|null */
function abas_bas_sso_verify_signed_oauth_state(string $state): ?array
{
    $state = trim($state);
    $dot = strrpos($state, '.');
    if ($dot === false || $dot <= 0) {
        return null;
    }

    $json = abas_bas_sso_base64url_decode(substr($state, 0, $dot));
    $mac = abas_bas_sso_base64url_decode(substr($state, $dot + 1));
    if ($json === '' || $mac === '') {
        return null;
    }

    $expected = hash_hmac('sha256', $json, abas_bas_sso_state_signing_key(), true);
    if (!hash_equals($expected, $mac)) {
        return null;
    }

    try {
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    if (!is_array($payload)) {
        return null;
    }
    if ((int) ($payload['e'] ?? 0) < time()) {
        return null;
    }

    $redirectUri = trim((string) ($payload['r'] ?? ''));
    if ($redirectUri === '') {
        return null;
    }

    return ['redirect_uri' => $redirectUri];
}

function abas_bas_sso_store_oauth_context(string $state, string $redirectUri): void
{
    $_SESSION['abas_bas_sso_oauth_state'] = $state;
    $_SESSION['abas_bas_sso_redirect_uri'] = $redirectUri;
    $payload = abas_bas_sso_base64url((string) json_encode([
        's' => $state,
        'r' => $redirectUri,
        'e' => time() + 600,
    ], JSON_THROW_ON_ERROR));
    $cookie = abas_bas_sso_oauth_cookie_params();
    setcookie('abas_oauth_ctx', $payload, $cookie);
    setcookie('abas_oauth_state', $state, $cookie);
    setcookie('abas_oauth_redirect', $redirectUri, $cookie);
}

function abas_bas_sso_clear_oauth_context(): void
{
    unset($_SESSION['abas_bas_sso_oauth_state'], $_SESSION['abas_bas_sso_redirect_uri'], $_SESSION['abas_bas_sso_pkce_verifier']);
    $cookie = abas_bas_sso_oauth_cookie_params();
    $cookie['expires'] = time() - 3600;
    setcookie('abas_oauth_ctx', '', $cookie);
    setcookie('abas_oauth_state', '', $cookie);
    setcookie('abas_oauth_redirect', '', $cookie);
}

/** @return array{redirect_uri: string}|null */
function abas_bas_sso_verify_oauth_callback(string $state): ?array
{
    $signed = abas_bas_sso_verify_signed_oauth_state($state);
    if ($signed !== null) {
        return $signed;
    }

    $state = trim($state);
    if ($state === '') {
        return null;
    }

    $defaultRedirect = abas_bas_sso_login_redirect_uri();
    $sessionState = trim((string) ($_SESSION['abas_bas_sso_oauth_state'] ?? ''));
    $sessionRedirect = trim((string) ($_SESSION['abas_bas_sso_redirect_uri'] ?? ''));
    if ($sessionState !== '' && hash_equals($sessionState, $state)) {
        return [
            'redirect_uri' => $sessionRedirect !== '' ? $sessionRedirect : $defaultRedirect,
        ];
    }

    $ctxRaw = trim((string) ($_COOKIE['abas_oauth_ctx'] ?? ''));
    if ($ctxRaw !== '') {
        try {
            $decoded = json_decode(abas_bas_sso_base64url_decode($ctxRaw), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $ctxState = trim((string) ($decoded['s'] ?? ''));
                $ctxRedirect = trim((string) ($decoded['r'] ?? ''));
                $ctxExpiry = (int) ($decoded['e'] ?? 0);
                if ($ctxState !== '' && $ctxExpiry >= time() && hash_equals($ctxState, $state)) {
                    return [
                        'redirect_uri' => $ctxRedirect !== '' ? $ctxRedirect : $defaultRedirect,
                    ];
                }
            }
        } catch (Throwable) {
            // ignore malformed backup cookie
        }
    }

    $cookieState = trim((string) ($_COOKIE['abas_oauth_state'] ?? ''));
    $cookieRedirect = trim((string) ($_COOKIE['abas_oauth_redirect'] ?? ''));
    if ($cookieState !== '' && hash_equals($cookieState, $state)) {
        return [
            'redirect_uri' => $cookieRedirect !== '' ? $cookieRedirect : $defaultRedirect,
        ];
    }

    return null;
}

function abas_bas_sso_build_authorize_url(string $redirectUri, string $scope = 'openid profile email'): string
{
    abas_bas_sso_clear_oauth_context();
    unset($_SESSION['abas_bas_sso_pkce_verifier']);
    $state = abas_bas_sso_make_oauth_state($redirectUri);

    $authorizeUrl = abas_bas_sso_authorize_url();

    return $authorizeUrl . '?' . http_build_query([
        'client_id' => abas_bas_sso_client_id(),
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => $scope,
        'state' => $state,
    ]);
}
