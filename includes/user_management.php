<?php

declare(strict_types=1);

require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/password_policy.php';
require_once __DIR__ . '/table_list.php';

/** @return list<string> */
function abas_anlaegsejer_users_sort_columns(): array
{
    return ['name', 'email', 'phone', 'role'];
}

function abas_anlaegsejer_users_order_sql(string $sort, string $dir): string
{
    /** @var array<string, string> */
    $columns = [
        'name' => 'COALESCE(NULLIF(u.registration_display_name, ""), u.username)',
        'email' => 'u.email',
        'phone' => 'u.phone',
        'role' => 'u.role',
    ];
    $dir = abas_table_normalize_sort_dir($dir) === 'desc' ? 'DESC' : 'ASC';
    if (!isset($columns[$sort])) {
        return 'u.role ASC, u.username ASC';
    }

    return $columns[$sort] . ' ' . $dir . ', u.username ASC';
}

/** @return list<string> */
function abas_virksomhed_users_sort_columns(): array
{
    return ['name', 'email', 'phone', 'role', 'active'];
}

function abas_virksomhed_users_order_sql(string $sort, string $dir): string
{
    /** @var array<string, string> */
    $columns = [
        'name' => 'COALESCE(NULLIF(registration_display_name, ""), username)',
        'email' => 'email',
        'phone' => 'phone',
        'role' => 'role',
        'active' => 'active',
    ];
    $dir = abas_table_normalize_sort_dir($dir) === 'desc' ? 'DESC' : 'ASC';
    if (!isset($columns[$sort])) {
        return 'role ASC, username ASC';
    }

    return $columns[$sort] . ' ' . $dir . ', username ASC';
}

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
function abas_list_anlaegsejer_managed_users(
    mysqli $conn,
    int $actorId,
    string $sort = '',
    string $sortDir = 'asc',
    string $search = ''
): array {
    $sort = abas_table_resolve_sort($sort, abas_anlaegsejer_users_sort_columns(), 'name');
    $orderSql = abas_anlaegsejer_users_order_sql($sort, $sortDir);
    $search = trim($search);
    $searchSql = '';
    $searchParams = [];
    $searchTypes = '';

    if ($search !== '') {
        $like = '%' . $search . '%';
        $roleMatches = abas_admin_users_matching_roles($search);
        $roleInSql = '';
        if ($roleMatches !== []) {
            $rolePlaceholders = implode(',', array_fill(0, count($roleMatches), '?'));
            $roleInSql = ' OR u.role IN (' . $rolePlaceholders . ')';
            $searchTypes .= str_repeat('s', count($roleMatches));
            $searchParams = array_merge($searchParams, $roleMatches);
        }

        $searchSql = ' AND (
            u.username LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
            OR u.registration_display_name LIKE ?
            OR u.role LIKE ?
            OR EXISTS (
                SELECT 1 FROM user_installations ui2
                INNER JOIN installations i ON i.id = ui2.installation_id
                WHERE ui2.user_id = u.id AND (i.miscno2 LIKE ? OR i.name LIKE ?)
            )' . $roleInSql . '
        )';
        $searchTypes = str_repeat('s', 6) . $searchTypes;
        $searchParams = array_merge(array_fill(0, 6, $like), $searchParams);
    }

    $sql = 'SELECT DISTINCT u.id, u.email, u.username, u.phone, u.role, u.active,
                u.registration_display_name, u.sms_service_allowed, u.sms_secret_hash
         FROM users u
         INNER JOIN user_installations ui ON ui.user_id = u.id
         WHERE u.role IN ("anlaegsejer", "anlaegsafprover")
           AND u.id <> ?
           AND ui.installation_id IN (
               SELECT installation_id FROM user_installations WHERE user_id = ?
           )' . $searchSql . '
         ORDER BY ' . $orderSql;

    $types = 'ii' . $searchTypes;
    $params = array_merge([$actorId, $actorId], $searchParams);
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * @return list<array<string, mixed>>
 */
function abas_list_virksomhed_installer_users(
    mysqli $conn,
    int $installerId,
    string $sort = '',
    string $sortDir = 'asc',
    string $search = ''
): array {
    $sort = abas_table_resolve_sort($sort, abas_virksomhed_users_sort_columns(), 'name');
    $orderSql = abas_virksomhed_users_order_sql($sort, $sortDir);
    $search = trim($search);
    $searchSql = '';
    $searchParams = [];
    $searchTypes = '';

    if ($search !== '') {
        $like = '%' . $search . '%';
        $roleMatches = abas_admin_users_matching_roles($search);
        $roleInSql = '';
        if ($roleMatches !== []) {
            $rolePlaceholders = implode(',', array_fill(0, count($roleMatches), '?'));
            $roleInSql = ' OR role IN (' . $rolePlaceholders . ')';
            $searchTypes .= str_repeat('s', count($roleMatches));
            $searchParams = array_merge($searchParams, $roleMatches);
        }

        $searchSql = ' AND (
            username LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
            OR registration_display_name LIKE ?
            OR role LIKE ?
            OR (active = 1 AND LOCATE(?, "aktiv") > 0)
            OR (active = 0 AND LOCATE(?, "inaktiv") > 0)
        ' . $roleInSql . ')';
        $searchTypes = str_repeat('s', 8) . $searchTypes;
        $searchParams = array_merge(array_fill(0, 5, $like), [$search, $search], $searchParams);
    }

    $sql = 'SELECT id, email, username, phone, role, active, registration_display_name, sms_service_allowed
         FROM users
         WHERE installer_id = ? AND role NOT IN ("admin","vagtcentral")' . $searchSql . '
         ORDER BY ' . $orderSql;

    $types = 'i' . $searchTypes;
    $params = array_merge([$installerId], $searchParams);
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_anlaegsbrugere_page_url(array $query = []): string
{
    return abas_table_page_url('anlaegsbrugere.php', $query);
}

function abas_virksomhed_users_page_url(array $query = []): string
{
    return abas_table_page_url('virksomhed/users.php', $query);
}

function abas_vc_anlaegsbrugere_page_url(array $query = []): string
{
    return abas_table_page_url('vc-anlaegsbrugere.php', $query);
}

function abas_update_user_phone(mysqli $conn, int $userId, string $phone): ?string
{
    $phone = abas_normalize_phone(trim($phone));
    if (!abas_validate_phone($phone)) {
        return 'Angiv et gyldigt telefonnummer (min. 8 cifre).';
    }

    $stmt = $conn->prepare('SELECT phone FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $previous = trim((string) ($row['phone'] ?? ''));

    $stmt = $conn->prepare('UPDATE users SET phone = ? WHERE id = ?');
    $stmt->bind_param('si', $phone, $userId);
    $stmt->execute();
    $stmt->close();

    if ($previous !== $phone) {
        require_once __DIR__ . '/activity_log.php';
        abas_log_user_target_event(
            $conn,
            'user',
            'profile_updated',
            $userId,
            $userId,
            null,
            'Telefon opdateret'
        );
    }

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

    require_once __DIR__ . '/activity_log.php';
    abas_log_user_target_event($conn, 'auth', 'password_changed', $userId, $userId);

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

    require_once __DIR__ . '/activity_log.php';
    abas_log_user_target_event(
        $conn,
        'user',
        'updated',
        $targetId,
        (int) ($actor['id'] ?? 0) ?: null,
        $displayName !== '' ? $displayName : $username,
        'Kontaktdata opdateret'
    );

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

/**
 * @param list<int> $installationIds
 * @return array{ok:bool, message:string, user_id?:int}
 */
function abas_create_anlaegsejer_managed_user(
    mysqli $conn,
    array $actor,
    string $email,
    string $username,
    string $phone,
    string $displayName,
    string $role,
    array $installationIds,
    bool $smsServiceAllowed,
    string $smsCode
): array {
    if (($actor['role'] ?? '') !== 'anlaegsejer') {
        return ['ok' => false, 'message' => 'Ingen adgang.'];
    }
    if (!in_array($role, ['anlaegsejer', 'anlaegsafprover'], true)) {
        return ['ok' => false, 'message' => 'Ugyldig rolle.'];
    }

    $email = strtolower(trim($email));
    $username = trim($username);
    $phone = abas_normalize_phone(trim($phone));
    $displayName = trim($displayName);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Angiv en gyldig e-mail.'];
    }
    if ($username === '') {
        return ['ok' => false, 'message' => 'Brugernavn er påkrævet.'];
    }
    if (!abas_validate_phone($phone)) {
        return ['ok' => false, 'message' => 'Angiv et gyldigt telefonnummer (min. 8 cifre).'];
    }

    $smsAllowed = $smsServiceAllowed ? 1 : 0;
    if (abas_user_sms_service_code_required_on_create($role, $smsAllowed === 1, $smsCode)) {
        return ['ok' => false, 'message' => 'SMS-kode skal være mindst 6 tegn når SMS-betjening er aktiveret.'];
    }

    $actorInstIds = abas_user_installation_ids($conn, (int) $actor['id']);
    $installationIds = array_values(array_unique(array_filter(
        array_map(static fn ($id): int => (int) $id, $installationIds),
        static fn (int $id): bool => $id > 0
    )));
    if ($installationIds === []) {
        return ['ok' => false, 'message' => 'Vælg mindst ét anlæg.'];
    }
    foreach ($installationIds as $installationId) {
        if (!in_array($installationId, $actorInstIds, true)) {
            return ['ok' => false, 'message' => 'Du kan kun tilknytte dine egne anlæg.'];
        }
    }

    $chk = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
    $chk->bind_param('ss', $email, $username);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        $chk->close();

        return ['ok' => false, 'message' => 'En bruger med samme e-mail eller brugernavn findes allerede.'];
    }
    $chk->close();

    $actorId = (int) $actor['id'];
    $displayNameDb = $displayName !== '' ? $displayName : null;
    $stmt = $conn->prepare(
        'INSERT INTO users (email, username, role, phone, active, sms_service_allowed, registration_status, registration_display_name, created_by_user_id)
         VALUES (?, ?, ?, ?, 1, ?, "approved", ?, ?)'
    );
    $stmt->bind_param('ssssisi', $email, $username, $role, $phone, $smsAllowed, $displayNameDb, $actorId);
    $stmt->execute();
    $newId = (int) $stmt->insert_id;
    $stmt->close();

    if ($smsAllowed === 1 && $smsCode !== '' && abas_user_role_uses_sms_code($role)) {
        abas_set_user_sms_code($conn, $newId, $smsCode);
    }

    foreach ($installationIds as $installationId) {
        abas_link_user_installation($conn, $newId, $installationId);
    }

    return ['ok' => true, 'message' => 'Bruger oprettet og tilknyttet.', 'user_id' => $newId];
}
