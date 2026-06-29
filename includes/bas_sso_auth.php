<?php

declare(strict_types=1);

require_once __DIR__ . '/bas_sso_client.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/activity_log.php';

function abas_bas_sso_trust_mfa(): bool
{
    $v = abas_env('BAS_SSO_TRUST_MFA', '1');

    return !($v === '0' || strtolower($v) === 'false');
}

function abas_is_embed_session(): bool
{
    return !empty($_SESSION['abas_embed']);
}

function abas_set_embed_session(bool $embed = true): void
{
    if ($embed) {
        $_SESSION['abas_embed'] = 1;
    } else {
        unset($_SESSION['abas_embed']);
    }
}

function abas_embed_url(string $path): string
{
    $url = abas_url($path);
    if (!abas_is_embed_session()) {
        return $url;
    }
    $sep = str_contains($url, '?') ? '&' : '?';

    return $url . $sep . 'embed=1';
}

function abas_send_embed_headers(): void
{
    $ancestors = trim((string) (abas_env('BAS_SSO_FRAME_ANCESTORS') ?? ''));
    if ($ancestors === '') {
        $ancestors = "'self' https://*.beredskabsalarmering.dk https://*.trekantbrand.dk";
    }
    header('Content-Security-Policy: frame-ancestors ' . $ancestors);
}

/** @param array<string, mixed> $claims */
function abas_bas_sso_find_user(mysqli $conn, array $claims): ?array
{
    $preferred = mb_strtolower(trim((string) ($claims['preferred_username'] ?? '')), 'UTF-8');
    if ($preferred !== '') {
        $stmt = $conn->prepare(
            'SELECT u.* FROM bas_user_links b
             INNER JOIN users u ON u.id = b.aba_user_id
             WHERE LOWER(b.bas_username) = ? AND u.active = 1 AND u.registration_status = "approved"
             LIMIT 1'
        );
        $stmt->bind_param('s', $preferred);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user) {
            return $user;
        }
    }

    $email = mb_strtolower(trim((string) ($claims['email'] ?? '')), 'UTF-8');
    if ($email !== '' && abas_env('BAS_SSO_AUTO_LINK_EMAIL', '1') !== '0') {
        $stmt = $conn->prepare(
            'SELECT * FROM users
             WHERE LOWER(email) = ? AND active = 1 AND registration_status = "approved"
             LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user && $preferred !== '') {
            abas_bas_sso_ensure_link($conn, (int) $user['id'], $preferred);
        }
        if ($user) {
            return $user;
        }
    }

    return null;
}

function abas_bas_sso_ensure_link(mysqli $conn, int $abaUserId, string $basUsername): void
{
    $basUsername = trim($basUsername);
    if ($basUsername === '') {
        return;
    }
    $stmt = $conn->prepare(
        'INSERT INTO bas_user_links (aba_user_id, bas_username)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE bas_username = VALUES(bas_username)'
    );
    $stmt->bind_param('is', $abaUserId, $basUsername);
    $stmt->execute();
    $stmt->close();
}

/** @param array<string, mixed> $claims */
function abas_bas_sso_complete_login(mysqli $conn, array $user, array $claims, bool $embed = false): void
{
    if (!in_array((string) ($user['role'] ?? ''), ['vagtcentral', 'admin', 'montor', 'anlaegsejer'], true)) {
        throw new RuntimeException('Brugeren har ingen gyldig rolle i ABA Service.');
    }
    if ($embed && !in_array((string) $user['role'], ['vagtcentral', 'admin'], true)) {
        throw new RuntimeException('Kun vagtcentral og admin kan bruge VC Service i BAS.');
    }

    abas_login_user($user);
    if (abas_bas_sso_trust_mfa()) {
        require_once __DIR__ . '/mfa.php';
        abas_mfa_complete_verification();
    }
    if ($embed) {
        abas_set_embed_session(true);
    }

    abas_log_activity(
        $conn,
        'auth',
        'login',
        (int) $user['id'],
        (string) ($user['username'] ?? null),
        'user',
        (string) $user['id'],
        null,
        json_encode([
            'bas_username' => $claims['preferred_username'] ?? null,
            'sub' => $claims['sub'] ?? null,
            'embed' => $embed,
        ], JSON_UNESCAPED_UNICODE),
        null,
        null,
        'bas_sso',
        abas_activity_client_ip()
    );
}

/** @return array<string, mixed> */
function abas_bas_sso_claims_from_token_response(array $tokenPayload): array
{
    $idToken = (string) ($tokenPayload['id_token'] ?? '');
    if ($idToken === '') {
        throw new RuntimeException('SSO-svar mangler id_token.');
    }
    $claims = abas_bas_sso_jwt_verify($idToken);
    if (!is_array($claims)) {
        throw new RuntimeException('SSO-token kunne ikke verificeres.');
    }

    return $claims;
}
