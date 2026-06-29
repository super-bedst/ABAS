<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/activity_log.php';
require_once __DIR__ . '/bas_sso_auth.php';

function abas_scim_enabled(): bool
{
    $token = abas_scim_bearer_token();

    return $token !== '';
}

function abas_scim_bearer_token(): string
{
    return trim((string) (abas_env('SCIM_BEARER_TOKEN') ?? abas_env('BAS_SCIM_BEARER_TOKEN') ?? ''));
}

function abas_scim_require_auth(): void
{
    if (!abas_scim_enabled()) {
        abas_scim_error('SCIM er ikke konfigureret.', 503);
    }
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/i', (string) $hdr, $m)) {
        abas_scim_error('Unauthorized', 401);
    }
    if (!hash_equals(abas_scim_bearer_token(), $m[1])) {
        abas_scim_error('Unauthorized', 401);
    }
}

function abas_scim_error(string $detail, int $code = 400): never
{
    http_response_code($code);
    header('Content-Type: application/scim+json; charset=utf-8');
    echo json_encode([
        'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
        'detail' => $detail,
        'status' => (string) $code,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** @param array<string, mixed> $payload */
function abas_scim_json(array $payload, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/scim+json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** @return array<string, mixed>|null */
function abas_scim_read_json_body(): ?array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : null;
}

function abas_scim_new_id(): string
{
    return bin2hex(random_bytes(16));
}

/** @param array<string, mixed> $payload */
function abas_scim_parse_email(array $payload): string
{
    $emails = $payload['emails'] ?? [];
    if (is_array($emails)) {
        foreach ($emails as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $value = trim((string) ($entry['value'] ?? ''));
            if ($value !== '' && str_contains($value, '@')) {
                return mb_strtolower($value, 'UTF-8');
            }
        }
    }

    return '';
}

/** @param array<string, mixed> $payload */
function abas_scim_map_role(array $payload): string
{
    $roles = $payload['roles'] ?? [];
    if (is_array($roles)) {
        foreach ($roles as $role) {
            $role = strtolower(trim((string) $role));
            if ($role === 'admin') {
                return 'admin';
            }
        }
    }

    return 'vagtcentral';
}

/** @param array<string, mixed> $payload */
function abas_scim_fallback_email(string $userName, string $externalId): string
{
    $base = $externalId !== ''
        ? preg_replace('/[^a-z0-9]+/i', '', $externalId)
        : preg_replace('/[^a-z0-9._-]+/i', '', $userName);
    $base = strtolower(trim((string) ($base ?: bin2hex(random_bytes(8)))));

    return substr($base, 0, 48) . '@bas-provisioned.invalid';
}

function abas_scim_unique_username(mysqli $conn, string $preferred, string $email): string
{
    $preferred = trim($preferred);
    if ($preferred !== '' && !abas_scim_username_taken($conn, $preferred)) {
        return $preferred;
    }
    if ($email !== '' && str_contains($email, '@')) {
        $local = preg_replace('/[^a-z0-9._-]+/i', '', (string) explode('@', $email, 2)[0]) ?? '';
        if ($local !== '' && !abas_scim_username_taken($conn, $local)) {
            return $local;
        }
    }
    do {
        $candidate = 'bas-' . substr(bin2hex(random_bytes(4)), 0, 8);
    } while (abas_scim_username_taken($conn, $candidate));

    return $candidate;
}

function abas_scim_username_taken(mysqli $conn, string $username, ?int $exceptUserId = null): bool
{
    $usernameKey = mb_strtolower($username, 'UTF-8');
    if ($exceptUserId !== null && $exceptUserId > 0) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE LOWER(username) = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $usernameKey, $exceptUserId);
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE LOWER(username) = ? LIMIT 1');
        $stmt->bind_param('s', $usernameKey);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (bool) $row;
}

function abas_scim_link_by_scim_id(mysqli $conn, string $scimId): ?array
{
    $stmt = $conn->prepare(
        'SELECT b.*, u.email, u.username AS aba_username, u.role, u.active AS user_active,
                u.registration_status, u.registration_display_name
         FROM bas_user_links b
         INNER JOIN users u ON u.id = b.aba_user_id
         WHERE b.scim_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $scimId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function abas_scim_link_by_oidc_sub(mysqli $conn, string $sub): ?array
{
    if ($sub === '') {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT b.*, u.email, u.username AS aba_username, u.role, u.active AS user_active,
                u.registration_status, u.registration_display_name
         FROM bas_user_links b
         INNER JOIN users u ON u.id = b.aba_user_id
         WHERE b.bas_oidc_sub = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $sub);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function abas_scim_link_by_bas_username(mysqli $conn, string $basUsername): ?array
{
    $key = mb_strtolower(trim($basUsername), 'UTF-8');
    if ($key === '') {
        return null;
    }
    $stmt = $conn->prepare(
        'SELECT b.*, u.email, u.username AS aba_username, u.role, u.active AS user_active,
                u.registration_status, u.registration_display_name
         FROM bas_user_links b
         INNER JOIN users u ON u.id = b.aba_user_id
         WHERE LOWER(b.bas_username) = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/** @param array<string, mixed> $linkRow */
function abas_scim_user_resource(array $linkRow): array
{
    $displayName = trim((string) ($linkRow['registration_display_name'] ?? ''));
    if ($displayName === '') {
        $displayName = (string) ($linkRow['aba_username'] ?? $linkRow['bas_username'] ?? '');
    }
    $active = (int) ($linkRow['user_active'] ?? 0) === 1
        && ($linkRow['registration_status'] ?? 'approved') === 'approved';

    return [
        'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
        'id' => (string) ($linkRow['scim_id'] ?? ''),
        'externalId' => (string) ($linkRow['bas_oidc_sub'] ?? ''),
        'userName' => (string) ($linkRow['bas_username'] ?? ''),
        'displayName' => $displayName,
        'active' => $active,
        'emails' => [['value' => (string) ($linkRow['email'] ?? ''), 'primary' => true]],
        'roles' => [(string) ($linkRow['role'] ?? 'vagtcentral')],
        'meta' => [
            'resourceType' => 'User',
        ],
    ];
}

/** @param array<string, mixed> $payload */
function abas_scim_handle_create_user(mysqli $conn, array $payload): never
{
    $externalId = trim((string) ($payload['externalId'] ?? ''));
    $userName = trim((string) ($payload['userName'] ?? ''));
    $email = abas_scim_parse_email($payload);
    $active = !array_key_exists('active', $payload) || (bool) $payload['active'];
    $displayName = trim((string) ($payload['displayName'] ?? ''));

    if ($userName === '') {
        abas_scim_error('userName er påkrævet.');
    }

    $existingLink = abas_scim_link_by_oidc_sub($conn, $externalId)
        ?? abas_scim_link_by_bas_username($conn, $userName);

    if ($existingLink !== null) {
        $existingScimId = trim((string) ($existingLink['scim_id'] ?? ''));
        if ($existingScimId === '') {
            $existingScimId = abas_scim_new_id();
            abas_bas_user_link_upsert(
                $conn,
                (int) $existingLink['aba_user_id'],
                $userName,
                $externalId !== '' ? $externalId : null,
                $existingScimId
            );
            $existingLink['scim_id'] = $existingScimId;
        }
        abas_scim_handle_update_user($conn, $existingScimId, $payload, $existingLink);
    }

    if ($email === '') {
        $email = abas_scim_fallback_email($userName, $externalId);
    }

    $stmt = $conn->prepare('SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $existingUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $role = abas_scim_map_role($payload);
    if (!in_array($role, ['vagtcentral', 'admin'], true)) {
        $role = 'vagtcentral';
    }

    if ($existingUser) {
        $userId = (int) $existingUser['id'];
        $activeInt = $active ? 1 : 0;
        $upd = $conn->prepare(
            'UPDATE users SET role = ?, active = ?, registration_status = "approved",
             registration_display_name = CASE WHEN ? <> "" THEN ? ELSE registration_display_name END
             WHERE id = ?'
        );
        $upd->bind_param('sissi', $role, $activeInt, $displayName, $displayName, $userId);
        $upd->execute();
        $upd->close();
    } else {
        $username = abas_scim_unique_username($conn, $userName, $email);
        $activeInt = $active ? 1 : 0;
        $ins = $conn->prepare(
            'INSERT INTO users (email, username, password_hash, role, active, registration_status, registration_display_name)
             VALUES (?, ?, NULL, ?, ?, "approved", ?)'
        );
        $ins->bind_param('sssis', $email, $username, $role, $activeInt, $displayName);
        $ins->execute();
        $userId = (int) $conn->insert_id;
        $ins->close();
    }

    $scimId = abas_scim_new_id();
    abas_bas_user_link_upsert($conn, $userId, $userName, $externalId !== '' ? $externalId : null, $scimId);

    abas_log_activity(
        $conn,
        'user',
        'created',
        null,
        'BAS SCIM',
        'user',
        (string) $userId,
        $displayName !== '' ? $displayName : $userName,
        json_encode(['bas_username' => $userName, 'externalId' => $externalId], JSON_UNESCAPED_UNICODE),
        null,
        null,
        'bas_sso',
        abas_activity_client_ip()
    );

    $link = abas_scim_link_by_scim_id($conn, $scimId);
    abas_scim_json(abas_scim_user_resource($link ?? ['scim_id' => $scimId, 'bas_username' => $userName]), 201);
}

/** @param array<string, mixed> $payload */
/** @param array<string, mixed>|null $knownLink */
function abas_scim_handle_update_user(mysqli $conn, string $scimId, array $payload, ?array $knownLink = null): never
{
    $link = $knownLink ?? abas_scim_link_by_scim_id($conn, $scimId);
    if ($link === null) {
        abas_scim_error('Bruger ikke fundet.', 404);
    }

    $userId = (int) $link['aba_user_id'];
    $userName = trim((string) ($payload['userName'] ?? $link['bas_username'] ?? ''));
    $externalId = trim((string) ($payload['externalId'] ?? $link['bas_oidc_sub'] ?? ''));
    $email = abas_scim_parse_email($payload);
    $displayName = trim((string) ($payload['displayName'] ?? ''));
    $active = array_key_exists('active', $payload) ? (bool) $payload['active'] : ((int) ($link['user_active'] ?? 0) === 1);
    $role = array_key_exists('roles', $payload)
        ? abas_scim_map_role($payload)
        : (string) ($link['role'] ?? 'vagtcentral');

    if ($email !== '') {
        $dup = $conn->prepare('SELECT id FROM users WHERE LOWER(email) = ? AND id <> ? LIMIT 1');
        $dup->bind_param('si', $email, $userId);
        $dup->execute();
        $conflict = $dup->get_result()->fetch_assoc();
        $dup->close();
        if ($conflict) {
            abas_scim_error('E-mail er allerede i brug af en anden bruger.', 409);
        }
    }

    $activeInt = $active ? 1 : 0;
    if ($email !== '') {
        $stmt = $conn->prepare(
            'UPDATE users SET email = ?, role = ?, active = ?, registration_status = "approved",
             registration_display_name = CASE WHEN ? <> "" THEN ? ELSE registration_display_name END
             WHERE id = ?'
        );
        $stmt->bind_param('ssissi', $email, $role, $activeInt, $displayName, $displayName, $userId);
    } else {
        $stmt = $conn->prepare(
            'UPDATE users SET role = ?, active = ?, registration_status = "approved",
             registration_display_name = CASE WHEN ? <> "" THEN ? ELSE registration_display_name END
             WHERE id = ?'
        );
        $stmt->bind_param('sissi', $role, $activeInt, $displayName, $displayName, $userId);
    }
    $stmt->execute();
    $stmt->close();

    $effectiveScimId = trim((string) ($link['scim_id'] ?? $scimId));
    if ($effectiveScimId === '') {
        $effectiveScimId = abas_scim_new_id();
    }
    abas_bas_user_link_upsert(
        $conn,
        $userId,
        $userName !== '' ? $userName : (string) $link['bas_username'],
        $externalId !== '' ? $externalId : null,
        $effectiveScimId
    );

    abas_log_activity(
        $conn,
        'user',
        'updated',
        null,
        'BAS SCIM',
        'user',
        (string) $userId,
        $displayName !== '' ? $displayName : $userName,
        null,
        null,
        null,
        'bas_sso',
        abas_activity_client_ip()
    );

    $updated = abas_scim_link_by_scim_id($conn, $effectiveScimId);
    abas_scim_json(abas_scim_user_resource($updated ?? $link));
}

function abas_scim_handle_delete_user(mysqli $conn, string $scimId): never
{
    $link = abas_scim_link_by_scim_id($conn, $scimId);
    if ($link === null) {
        abas_scim_error('Bruger ikke fundet.', 404);
    }
    $userId = (int) $link['aba_user_id'];
    $stmt = $conn->prepare('UPDATE users SET active = 0 WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    abas_log_activity(
        $conn,
        'user',
        'deactivated',
        null,
        'BAS SCIM',
        'user',
        (string) $userId,
        (string) ($link['bas_username'] ?? ''),
        null,
        null,
        null,
        'bas_sso',
        abas_activity_client_ip()
    );

    http_response_code(204);
    exit;
}

function abas_scim_handle_request(mysqli $conn, string $method, string $path): never
{
    abas_scim_require_auth();
    $path = trim($path, '/');

    if ($path === 'ServiceProviderConfig' && $method === 'GET') {
        abas_scim_json([
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig'],
            'patch' => ['supported' => true],
            'bulk' => ['supported' => false],
        ]);
    }

    if ($path === 'Users' && $method === 'POST') {
        $body = abas_scim_read_json_body();
        if ($body === null) {
            abas_scim_error('Ugyldig JSON.');
        }
        abas_scim_handle_create_user($conn, $body);
    }

    if (preg_match('#^Users/([^/]+)$#', $path, $m)) {
        $scimId = rawurldecode($m[1]);
        if ($method === 'GET') {
            $link = abas_scim_link_by_scim_id($conn, $scimId);
            if ($link === null) {
                abas_scim_error('Bruger ikke fundet.', 404);
            }
            abas_scim_json(abas_scim_user_resource($link));
        }
        if (in_array($method, ['PATCH', 'PUT'], true)) {
            $body = abas_scim_read_json_body();
            if ($body === null) {
                abas_scim_error('Ugyldig JSON.');
            }
            abas_scim_handle_update_user($conn, $scimId, $body);
        }
        if ($method === 'DELETE') {
            abas_scim_handle_delete_user($conn, $scimId);
        }
    }

    abas_scim_error('Not found', 404);
}
