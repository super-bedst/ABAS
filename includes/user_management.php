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
    require_once __DIR__ . '/installation_groups.php';

    return abas_user_accessible_installation_ids($conn, $userId);
}

function abas_users_share_installation(mysqli $conn, int $userA, int $userB): bool
{
    require_once __DIR__ . '/installation_groups.php';

    $idsA = abas_user_accessible_installation_ids($conn, $userA);
    if ($idsA === []) {
        return false;
    }
    $idsB = array_flip(abas_user_accessible_installation_ids($conn, $userB));
    foreach ($idsA as $id) {
        if (isset($idsB[$id])) {
            return true;
        }
    }

    return false;
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

function abas_vc_anlaegsbruger_edit_url(int $userId, array $listQuery = []): string
{
    $params = ['id' => $userId];
    foreach (['q', 'sort', 'dir'] as $key) {
        if (!empty($listQuery[$key])) {
            $params[$key] = $listQuery[$key];
        }
    }

    return abas_url('vc-anlaegsbruger-edit.php?' . http_build_query($params));
}

/**
 * @param array<string, mixed> $actor
 * @param array<string, string|null> $listQuery
 */
function abas_anlaegsbruger_edit_url_for_actor(array $actor, int $targetUserId, array $listQuery = []): string
{
    $role = (string) ($actor['role'] ?? '');
    if ($role === 'admin') {
        $params = [
            'id' => $targetUserId,
            'filter' => 'anlaegsbrugere',
            'return' => 'vc-anlaegsbrugere.php' . ($listQuery !== [] ? '?' . http_build_query(array_filter($listQuery)) : ''),
        ];

        return abas_url('admin/user-edit.php?' . http_build_query($params));
    }
    if ($role === 'vagtcentral') {
        return abas_vc_anlaegsbruger_edit_url($targetUserId, $listQuery);
    }

    return abas_url('anlaegsbruger-edit.php?id=' . $targetUserId);
}

function abas_vc_may_manage_anlaegsbruger(array $actor, array $target): bool
{
    if (($actor['role'] ?? '') !== 'vagtcentral') {
        return false;
    }

    return in_array($target['role'] ?? '', ['anlaegsejer', 'anlaegsafprover'], true);
}

function abas_vc_anlaegsbrugere_badge_visible_limit(): int
{
    return 4;
}

/**
 * @param list<array{installation_id:int, miscno2:string, in_service:bool}> $installations
 * @return list<array{installation_id:int, miscno2:string, in_service:bool}>
 */
function abas_sort_installations_in_service_first(array $installations): array
{
    usort(
        $installations,
        static function (array $a, array $b): int {
            $serviceCmp = ((int) !empty($b['in_service'])) <=> ((int) !empty($a['in_service']));
            if ($serviceCmp !== 0) {
                return $serviceCmp;
            }

            return strcasecmp((string) $a['miscno2'], (string) $b['miscno2']);
        }
    );

    return $installations;
}

/**
 * @param array<int, list<array{installation_id:int, miscno2:string, in_service:bool}>> $directByUser
 * @param list<int> $userIds
 * @return array<int, array{
 *   groups: list<array{id:int, name:string, public_id:string, installations:list<array{installation_id:int, miscno2:string, in_service:bool}>}>,
 *   direct: list<array{installation_id:int, miscno2:string, in_service:bool}>
 * }>
 */
function abas_anlaegsbrugere_installation_access_for_users(mysqli $conn, array $userIds, array $directByUser = []): array
{
    require_once __DIR__ . '/installation_groups.php';

    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
    if ($userIds === []) {
        return [];
    }

    $access = [];
    foreach ($userIds as $userId) {
        $access[$userId] = [
            'groups' => [],
            'direct' => abas_sort_installations_in_service_first($directByUser[$userId] ?? []),
        ];
    }

    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $types = str_repeat('i', count($userIds));
    $stmt = $conn->prepare(
        "SELECT uig.user_id, g.id AS group_id, g.name, g.public_id,
                i.id AS installation_id, i.miscno2,
                (active_ss.installation_id IS NOT NULL) AS in_service
         FROM user_installation_groups uig
         INNER JOIN installation_groups g ON g.id = uig.group_id
         INNER JOIN installation_group_members igm ON igm.group_id = g.id
         INNER JOIN installations i ON i.id = igm.installation_id
         LEFT JOIN (
             SELECT DISTINCT installation_id
             FROM service_sessions
             WHERE status = 'active'
         ) active_ss ON active_ss.installation_id = i.id
         WHERE uig.user_id IN ($placeholders)
         ORDER BY uig.user_id, g.name, i.miscno2"
    );
    $stmt->bind_param($types, ...$userIds);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $groupsByUser = [];
    foreach ($rows as $row) {
        $userId = (int) $row['user_id'];
        $groupId = (int) $row['group_id'];
        if (!isset($groupsByUser[$userId][$groupId])) {
            $groupsByUser[$userId][$groupId] = [
                'id' => $groupId,
                'name' => (string) $row['name'],
                'public_id' => (string) $row['public_id'],
                'installations' => [],
            ];
        }
        $installationId = (int) $row['installation_id'];
        $groupsByUser[$userId][$groupId]['installations'][$installationId] = [
            'installation_id' => $installationId,
            'miscno2' => (string) $row['miscno2'],
            'in_service' => (bool) $row['in_service'],
        ];
    }

    foreach ($userIds as $userId) {
        $groupList = array_values($groupsByUser[$userId] ?? []);
        foreach ($groupList as &$group) {
            $group['installations'] = abas_sort_installations_in_service_first(array_values($group['installations']));
        }
        unset($group);
        usort(
            $groupList,
            static fn (array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name'])
        );
        $access[$userId]['groups'] = $groupList;
    }

    return $access;
}

/**
 * @param list<array{installation_id:int, miscno2:string, in_service:bool}> $installations
 */
function abas_render_installation_badges_collapsible(array $installations, int $limit = 0): void
{
    if ($installations === []) {
        return;
    }
    if ($limit <= 0) {
        $limit = abas_vc_anlaegsbrugere_badge_visible_limit();
    }

    $sorted = abas_sort_installations_in_service_first($installations);
    $visible = array_slice($sorted, 0, $limit);
    $hidden = array_slice($sorted, $limit);
    ?>
    <div class="abas-installation-badges">
        <?php foreach ($visible as $inst): ?>
            <?php abas_render_installation_status_badge($inst); ?>
        <?php endforeach; ?>
    </div>
    <?php if ($hidden !== []): ?>
    <details class="abas-installation-badges-expand">
        <summary class="abas-installation-badges-expand-summary">+<?= count($hidden) ?> anlæg</summary>
        <div class="abas-installation-badges abas-installation-badges--expanded">
            <?php foreach ($hidden as $inst): ?>
                <?php abas_render_installation_status_badge($inst); ?>
            <?php endforeach; ?>
        </div>
    </details>
    <?php endif;
}

/**
 * @param array{installation_id:int, miscno2:string, in_service:bool} $inst
 */
function abas_render_installation_status_badge(array $inst): void
{
    $inService = !empty($inst['in_service']);
    ?>
    <a
        href="<?= abas_url('installation.php?id=' . (int) $inst['installation_id']) ?>"
        data-abas-loading="Åbner anlæg…"
        class="<?= $inService ? 'abas-badge-in-service' : 'abas-badge-ok' ?> hover:opacity-90"
        title="<?= $inService ? 'I service' : 'Normal drift' ?>"
    ><?= htmlspecialchars((string) $inst['miscno2']) ?></a>
    <?php
}

/**
 * @param array{groups:list<array<string, mixed>>, direct:list<array{installation_id:int, miscno2:string, in_service:bool}>} $access
 */
function abas_render_vc_anlaegsbruger_installation_access(array $access): void
{
    $groups = $access['groups'] ?? [];
    $direct = $access['direct'] ?? [];
    $hasGroups = $groups !== [];
    $hasDirect = $direct !== [];

    if (!$hasGroups && !$hasDirect) {
        echo '<span class="text-gray-400 text-sm">Ingen anlæg</span>';

        return;
    }
    ?>
    <div class="abas-vc-installation-access" data-abas-row-ignore="1">
        <?php foreach ($groups as $group): ?>
            <?php if (($group['installations'] ?? []) === []) {
                continue;
            } ?>
            <div class="abas-vc-access-group">
                <p class="abas-vc-access-group-label" title="<?= htmlspecialchars((string) ($group['public_id'] ?? '')) ?>">
                    <?= htmlspecialchars((string) ($group['name'] ?? 'Gruppe')) ?>
                </p>
                <?php abas_render_installation_badges_collapsible($group['installations']); ?>
            </div>
        <?php endforeach; ?>
        <?php if ($hasDirect): ?>
            <div class="abas-vc-access-direct">
                <?php if ($hasGroups): ?>
                    <p class="abas-vc-access-group-label">Direkte</p>
                <?php endif; ?>
                <?php abas_render_installation_badges_collapsible($direct); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
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
    if ($permissionCheck === 'vagtcentral' && !abas_vc_may_manage_anlaegsbruger($actor, $target)) {
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
