<?php

declare(strict_types=1);

require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/auth.php';

function abas_normalize_phone(string $phone): string
{
    return (string) preg_replace('/\s+/', '', trim($phone));
}

function abas_validate_phone(string $phone): bool
{
    $normalized = abas_normalize_phone($phone);

    return (bool) preg_match('/^\+?\d{8,}$/', $normalized);
}

function abas_user_role_uses_sms_code(string $role): bool
{
    return in_array($role, ['montor', 'anlaegsejer', 'anlaegsafprover'], true);
}

function abas_user_sms_service_allowed(array $user): bool
{
    return !empty($user['sms_service_allowed']);
}

function abas_user_has_responsibility_ack(array $user): bool
{
    return !empty($user['responsibility_ack_at']);
}

function abas_set_user_responsibility_ack(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare('UPDATE users SET responsibility_ack_at = NOW() WHERE id = ? AND responsibility_ack_at IS NULL');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

function abas_validate_sms_code(string $code): bool
{
    return strlen(trim($code)) >= 6;
}

function abas_hash_sms_code(string $code): string
{
    return password_hash(trim($code), PASSWORD_DEFAULT);
}

function abas_set_user_sms_code(mysqli $conn, int $userId, string $code): void
{
    $hash = abas_hash_sms_code($code);
    $stmt = $conn->prepare('UPDATE users SET sms_secret_hash = ? WHERE id = ?');
    $stmt->bind_param('si', $hash, $userId);
    $stmt->execute();
    $stmt->close();
}

function abas_user_has_sms_code(array $user): bool
{
    return trim((string) ($user['sms_secret_hash'] ?? '')) !== '';
}

/**
 * @return list<array{id:int, miscno2:string, name:string, city:string}>
 */
function abas_user_installation_links(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare(
        'SELECT i.id, i.miscno2, i.name, i.city FROM user_installations ui
         JOIN installations i ON i.id = ui.installation_id
         WHERE ui.user_id = ? ORDER BY i.miscno2'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_link_user_installation(mysqli $conn, int $userId, int $installationId): bool
{
    $stmt = $conn->prepare('INSERT IGNORE INTO user_installations (user_id, installation_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $userId, $installationId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok;
}

function abas_unlink_user_installation(mysqli $conn, int $userId, int $installationId): bool
{
    $stmt = $conn->prepare('DELETE FROM user_installations WHERE user_id = ? AND installation_id = ?');
    $stmt->bind_param('ii', $userId, $installationId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok;
}

function abas_link_user_installation_by_miscno2(mysqli $conn, int $userId, string $miscno2): ?string
{
    $misc = strtolower(trim($miscno2));
    if ($misc === '') {
        return null;
    }
    $installation = abas_find_installation_by_miscno2($conn, $misc);
    if (!$installation) {
        return 'Anlæg ikke fundet: ' . $misc;
    }
    if (!abas_link_user_installation($conn, $userId, (int) $installation['id'])) {
        return 'Anlæg er allerede tilknyttet.';
    }

    return null;
}

/**
 * @return array{ok:bool, message:string, deactivated?:bool}
 */
function abas_delete_user(mysqli $conn, int $userId, int $actorId): array
{
    if ($userId === $actorId) {
        return ['ok' => false, 'message' => 'Du kan ikke slette din egen konto.'];
    }

    $stmt = $conn->prepare(
        'SELECT 1 FROM service_sessions WHERE status = "active" AND (user_id = ? OR on_behalf_of_user_id = ?) LIMIT 1'
    );
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_row()) {
        $stmt->close();

        return ['ok' => false, 'message' => 'Brugeren har aktiv service — stop service først.'];
    }
    $stmt->close();

    $hist = $conn->prepare(
        'SELECT (
            SELECT COUNT(*) FROM service_sessions WHERE user_id = ? OR on_behalf_of_user_id = ?
         ) + (
            SELECT COUNT(*) FROM service_actions WHERE user_id = ? OR on_behalf_of_user_id = ?
         ) AS c'
    );
    $hist->bind_param('iiii', $userId, $userId, $userId, $userId);
    $hist->execute();
    $count = (int) ($hist->get_result()->fetch_assoc()['c'] ?? 0);
    $hist->close();

    if ($count > 0) {
        $deact = $conn->prepare('UPDATE users SET active = 0 WHERE id = ?');
        $deact->bind_param('i', $userId);
        $deact->execute();
        $deact->close();

        return ['ok' => true, 'deactivated' => true, 'message' => 'Bruger deaktiveret (har servicehistorik og kan ikke slettes helt).'];
    }

    $del = $conn->prepare('DELETE FROM users WHERE id = ?');
    $del->bind_param('i', $userId);
    $del->execute();
    $deleted = $del->affected_rows > 0;
    $del->close();

    if (!$deleted) {
        return ['ok' => false, 'message' => 'Bruger kunne ikke slettes.'];
    }

    return ['ok' => true, 'message' => 'Bruger slettet.'];
}

function abas_installer_id_for_email(mysqli $conn, string $email): ?int
{
    $installer = abas_installer_approved_for_domain($conn, abas_email_domain($email));
    if (!$installer) {
        return null;
    }

    return (int) $installer['id'];
}

function abas_user_company_name(mysqli $conn, array $user): string
{
    $installerId = (int) ($user['installer_id'] ?? 0);
    if ($installerId <= 0) {
        return '';
    }
    $stmt = $conn->prepare('SELECT company_name FROM approved_installers WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $installerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return trim((string) ($row['company_name'] ?? ''));
}

function abas_assign_installer_for_montor(mysqli $conn, string $email): ?int
{
    return abas_installer_id_for_email($conn, $email);
}

/**
 * @return list<array{id:int, username:string, email:string, phone:?string, company_name:?string}>
 */
function abas_search_montors(mysqli $conn, string $q, int $limit = 40): array
{
    $q = trim($q);
    $limit = max(1, min(100, $limit));

    if ($q === '') {
        $stmt = $conn->prepare(
            'SELECT u.id, u.username, u.email, u.phone, ai.company_name
             FROM users u
             LEFT JOIN approved_installers ai ON ai.id = u.installer_id
             WHERE u.role = "montor" AND u.active = 1
             ORDER BY u.username
             LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
    } else {
        $like = '%' . $q . '%';
        $stmt = $conn->prepare(
            'SELECT u.id, u.username, u.email, u.phone, ai.company_name
             FROM users u
             LEFT JOIN approved_installers ai ON ai.id = u.installer_id
             WHERE u.role = "montor" AND u.active = 1
               AND (u.username LIKE ? OR u.phone LIKE ? OR u.email LIKE ? OR ai.company_name LIKE ?)
             ORDER BY u.username
             LIMIT ?'
        );
        $stmt->bind_param('ssssi', $like, $like, $like, $like, $limit);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_vc_append_person_comment(string $comment, string $name, string $phone): string
{
    $name = trim($name);
    $phone = trim($phone);
    if ($name === '' && $phone === '') {
        return $comment;
    }

    $parts = array_values(array_filter([$name, $phone], static fn (string $p): bool => $p !== ''));
    $suffix = implode(', ', $parts);
    if ($comment === '') {
        return $suffix;
    }

    $enriched = $comment . ' — ' . $suffix;

    return function_exists('mb_substr')
        ? (string) mb_substr($enriched, 0, 255)
        : substr($enriched, 0, 255);
}

/** @deprecated use abas_vc_append_person_comment */
function abas_vc_append_manual_montor_comment(string $comment, string $name, string $phone): string
{
    return abas_vc_append_person_comment($comment, $name, $phone);
}

function abas_enrich_service_start_comment(mysqli $conn, array $user, string $comment): string
{
    if ($comment === '' || ($user['role'] ?? '') === 'vagtcentral') {
        return $comment;
    }

    $meta = [
        (string) ($user['username'] ?? ''),
        trim((string) ($user['phone'] ?? '')),
        abas_role_label((string) ($user['role'] ?? '')),
    ];
    if (($user['role'] ?? '') === 'montor') {
        $company = abas_user_company_name($conn, $user);
        if ($company !== '') {
            $meta[] = $company;
        }
    }
    $meta = array_values(array_filter($meta, static fn (string $part): bool => $part !== ''));
    if ($meta === []) {
        return $comment;
    }

    $enriched = $comment . ' — ' . implode(', ', $meta);

    return function_exists('mb_substr')
        ? (string) mb_substr($enriched, 0, 255)
        : substr($enriched, 0, 255);
}
