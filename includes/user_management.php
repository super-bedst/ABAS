<?php

declare(strict_types=1);

require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/password_policy.php';

/**
 * @return list<int>
 */
function abas_user_installation_ids(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare('SELECT installation_id FROM user_installations WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(static fn (array $row): int => (int) $row['installation_id'], $rows);
}

function abas_users_share_installation(mysqli $conn, int $userA, int $userB): bool
{
    $stmt = $conn->prepare(
        'SELECT 1 FROM user_installations a
         INNER JOIN user_installations b ON b.installation_id = a.installation_id
         WHERE a.user_id = ? AND b.user_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $userA, $userB);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

function abas_anlaegsejer_may_manage_user(mysqli $conn, array $actor, array $target): bool
{
    if (($actor['role'] ?? '') !== 'anlaegsejer') {
        return false;
    }
    if (!in_array($target['role'] ?? '', ['anlaegsejer', 'anlaegsafprover'], true)) {
        return false;
    }
    if ((int) $actor['id'] === (int) $target['id']) {
        return false;
    }

    return abas_users_share_installation($conn, (int) $actor['id'], (int) $target['id']);
}

function abas_anlaegsejer_may_unlink_shared_installation(mysqli $conn, array $actor, int $installationId, array $target): bool
{
    $actorInstIds = abas_user_installation_ids($conn, (int) $actor['id']);
    if (!in_array($installationId, $actorInstIds, true)) {
        return false;
    }

    return abas_anlaegsejer_may_manage_user($conn, $actor, $target);
}

/** @deprecated use abas_anlaegsejer_may_unlink_shared_installation */
function abas_actor_may_link_installation_to_user(mysqli $conn, array $actor, int $installationId, array $target): bool
{
    return abas_anlaegsejer_may_unlink_shared_installation($conn, $actor, $installationId, $target);
}

/**
 * @return list<array<string, mixed>>
 */
function abas_list_anlaegsejer_managed_users(mysqli $conn, int $actorId): array
{
    $stmt = $conn->prepare(
        'SELECT DISTINCT u.id, u.email, u.username, u.phone, u.role, u.active,
                u.registration_display_name, u.sms_service_allowed, u.sms_secret_hash
         FROM users u
         INNER JOIN user_installations ui ON ui.user_id = u.id
         WHERE u.role IN ("anlaegsejer", "anlaegsafprover")
           AND u.id <> ?
           AND ui.installation_id IN (
               SELECT installation_id FROM user_installations WHERE user_id = ?
           )
         ORDER BY u.username'
    );
    $stmt->bind_param('ii', $actorId, $actorId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_update_user_phone(mysqli $conn, int $userId, string $phone): ?string
{
    $phone = abas_normalize_phone(trim($phone));
    if (!abas_validate_phone($phone)) {
        return 'Angiv et gyldigt telefonnummer (min. 8 cifre).';
    }

    $stmt = $conn->prepare('UPDATE users SET phone = ? WHERE id = ?');
    $stmt->bind_param('si', $phone, $userId);
    $stmt->execute();
    $stmt->close();

    return null;
}

function abas_update_user_password_with_current(mysqli $conn, array $user, string $currentPassword, string $newPassword, string $confirmPassword): ?string
{
    $hash = (string) ($user['password_hash'] ?? '');
    if ($hash === '' || !password_verify($currentPassword, $hash)) {
        return 'Nuværende adgangskode er forkert.';
    }
    if ($newPassword !== $confirmPassword) {
        return 'De nye adgangskoder matcher ikke.';
    }
    $policyError = abas_password_validate($newPassword);
    if ($policyError !== null) {
        return $policyError;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $userId = (int) $user['id'];
    $stmt = $conn->prepare('UPDATE users SET password_hash = ?, password_set_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $newHash, $userId);
    $stmt->execute();
    $stmt->close();

    return null;
}

/**
 * @param array<string, mixed> $actor
 * @param array<string, mixed> $target
 * @return array{ok:bool, message?:string}
 */
function abas_save_managed_user_contact(
    mysqli $conn,
    array $actor,
    array $target,
    string $phone,
    string $username,
    string $displayName,
    bool $smsServiceAllowed,
    string $smsCode,
    string $permissionCheck
): array {
    if ($permissionCheck === 'anlaegsejer' && !abas_anlaegsejer_may_manage_user($conn, $actor, $target)) {
        return ['ok' => false, 'message' => 'Ingen adgang til brugeren.'];
    }
    if ($permissionCheck === 'virksomhedsadmin' && !abas_virksomhedsadmin_may_manage_user($actor, $target)) {
        return ['ok' => false, 'message' => 'Ingen adgang til brugeren.'];
    }

    $phone = abas_normalize_phone(trim($phone));
    $username = trim($username);
    $displayName = trim($displayName);
    if ($username === '') {
        return ['ok' => false, 'message' => 'Navn/brugernavn er påkrævet.'];
    }
    if (!abas_validate_phone($phone)) {
        return ['ok' => false, 'message' => 'Angiv et gyldigt telefonnummer.'];
    }

    $targetId = (int) $target['id'];
    $dup = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $dup->bind_param('si', $username, $targetId);
    $dup->execute();
    if ($dup->get_result()->fetch_row()) {
        $dup->close();

        return ['ok' => false, 'message' => 'Brugernavn findes allerede.'];
    }
    $dup->close();

    $role = (string) $target['role'];
    $smsAllowed = $smsServiceAllowed ? 1 : 0;
    if ($smsCode !== '' && !abas_validate_sms_code($smsCode)) {
        return ['ok' => false, 'message' => 'SMS-kode skal være mindst 6 tegn.'];
    }
    if (abas_user_sms_service_code_required_on_edit($role, $smsAllowed === 1, $target, $smsCode)) {
        return ['ok' => false, 'message' => 'Angiv SMS-kode når SMS-betjening er aktiveret.'];
    }

    $displayNameDb = $displayName !== '' ? $displayName : null;
    $stmt = $conn->prepare(
        'UPDATE users SET phone = ?, username = ?, registration_display_name = ?, sms_service_allowed = ? WHERE id = ?'
    );
    $stmt->bind_param('sssii', $phone, $username, $displayNameDb, $smsAllowed, $targetId);
    $stmt->execute();
    $stmt->close();

    if ($smsCode !== '') {
        abas_set_user_sms_code($conn, $targetId, $smsCode);
    }

    return ['ok' => true];
}

/**
 * @param array<string, mixed> $actor
 * @param array<string, mixed> $target
 */
function abas_save_virksomhed_managed_user(
    mysqli $conn,
    array $actor,
    array $target,
    string $displayName,
    string $phone,
    bool $active
): array {
    if (!abas_virksomhedsadmin_may_manage_user($actor, $target)) {
        return ['ok' => false, 'message' => 'Ingen adgang til brugeren.'];
    }

    $displayName = trim($displayName);
    $phone = abas_normalize_phone(trim($phone));
    if ($displayName === '' || strlen($displayName) < 2) {
        return ['ok' => false, 'message' => 'Angiv et gyldigt navn.'];
    }
    if (!abas_validate_phone($phone)) {
        return ['ok' => false, 'message' => 'Angiv et gyldigt telefonnummer.'];
    }

    $targetId = (int) $target['id'];
    $activeInt = $active ? 1 : 0;
    if ((int) $actor['id'] === $targetId && $activeInt === 0) {
        return ['ok' => false, 'message' => 'Du kan ikke deaktivere din egen konto.'];
    }

    $displayNameDb = $displayName;
    $stmt = $conn->prepare(
        'UPDATE users SET phone = ?, registration_display_name = ?, active = ? WHERE id = ?'
    );
    $stmt->bind_param('ssii', $phone, $displayNameDb, $activeInt, $targetId);
    $stmt->execute();
    $stmt->close();

    return ['ok' => true];
}
