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

function abas_generate_username_from_email(mysqli $conn, string $email, ?int $excludeUserId = null): string
{
    $email = strtolower(trim($email));
    if ($email === '') {
        $email = 'user@invalid';
    }

    $candidate = $email;
    $n = 1;
    while (true) {
        if ($excludeUserId !== null && $excludeUserId > 0) {
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
            $stmt->bind_param('si', $candidate, $excludeUserId);
        } else {
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $candidate);
        }
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();
        if (!$exists) {
            return $candidate;
        }

        $suffix = (string) $n;
        $maxLen = 255;
        if (strlen($email) + strlen($suffix) <= $maxLen) {
            $candidate = $email . $suffix;
        } else {
            $candidate = substr($email, 0, $maxLen - strlen($suffix)) . $suffix;
        }
        $n++;
    }
}

function abas_resolve_username_for_email(mysqli $conn, string $email, string $username): string
{
    $username = trim($username);

    return $username !== '' ? $username : abas_generate_username_from_email($conn, $email);
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

function abas_user_sms_service_code_required_on_create(string $role, bool $smsServiceAllowed, string $smsCode): bool
{
    if (!$smsServiceAllowed || !abas_user_role_uses_sms_code($role)) {
        return false;
    }

    return !abas_validate_sms_code($smsCode);
}

function abas_user_sms_service_code_required_on_edit(string $role, bool $smsServiceAllowed, array $user, string $newSmsCode): bool
{
    if (!$smsServiceAllowed || !abas_user_role_uses_sms_code($role)) {
        return false;
    }
    if ($newSmsCode !== '') {
        return false;
    }

    return !abas_user_has_sms_code($user);
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

        require_once __DIR__ . '/activity_log.php';
        abas_log_activity(
            $conn,
            'user',
            'deactivated',
            $actorId,
            null,
            'user',
            (string) $userId,
            null,
            'Har servicehistorik',
            null,
            null,
            'web',
            abas_activity_client_ip()
        );

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

    require_once __DIR__ . '/activity_log.php';
    abas_log_activity(
        $conn,
        'user',
        'deleted',
        $actorId,
        null,
        'user',
        (string) $userId,
        null,
        null,
        null,
        null,
        'web',
        abas_activity_client_ip()
    );

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
    $company = trim((string) ($user['company_name'] ?? ''));
    if ($company !== '') {
        return $company;
    }

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

function abas_user_display_name(array $user): string
{
    $name = trim((string) ($user['registration_display_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }

    return trim((string) ($user['username'] ?? ''));
}

/**
 * @return list<string>
 */
function abas_service_log_actor_parts(mysqli $conn, array $user): array
{
    $parts = [];

    $name = abas_user_display_name($user);
    if ($name !== '') {
        $parts[] = $name;
    }

    $company = abas_user_company_name($conn, $user);
    if ($company !== '') {
        $parts[] = $company;
    }

    $phone = trim((string) ($user['phone'] ?? ''));
    if ($phone !== '') {
        $parts[] = $phone;
    }

    $role = abas_role_label((string) ($user['role'] ?? ''));
    if ($role !== '') {
        $parts[] = $role;
    }

    return $parts;
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

function abas_service_event_label(string $action): string
{
    return match ($action) {
        'start' => 'Service start',
        'extend' => 'Service forlængelse',
        'stop' => 'Service stop',
        default => 'Service',
    };
}

/**
 * Trekant KOMMENTAR: Hændelse, evt. brugerkommentar, navn, firma, telefon, rolle.
 */
function abas_build_service_log_comment(mysqli $conn, array $user, string $action, string $userComment = ''): string
{
    $parts = [abas_service_event_label($action)];

    $userComment = trim($userComment);
    if ($userComment !== '') {
        $parts[] = $userComment;
    }

    $parts = array_merge($parts, abas_service_log_actor_parts($conn, $user));

    require_once __DIR__ . '/trekant_client.php';

    return abas_trekant_trim_log_parts($parts, 80, 4);
}

function abas_enrich_service_start_comment(mysqli $conn, array $user, string $comment): string
{
    return abas_build_service_log_comment($conn, $user, 'start', $comment);
}

function abas_enrich_service_stop_comment(mysqli $conn, array $user, string $comment): string
{
    return abas_build_service_log_comment($conn, $user, 'stop', $comment);
}

/** @deprecated use abas_build_service_log_comment */
function abas_enrich_service_actor_comment(mysqli $conn, array $user, string $comment): string
{
    return abas_build_service_log_comment($conn, $user, 'start', $comment);
}

/**
 * @deprecated use abas_enrich_service_actor_comment
 */
function abas_enrich_service_user_comment(mysqli $conn, array $user, string $comment): string
{
    return abas_enrich_service_actor_comment($conn, $user, $comment);
}

function abas_record_user_login(mysqli $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    $stmt = $conn->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    require_once __DIR__ . '/activity_log.php';
    abas_log_activity(
        $conn,
        'auth',
        'login',
        $userId,
        null,
        'user',
        (string) $userId,
        null,
        null,
        null,
        null,
        'web',
        abas_activity_client_ip()
    );
}

/** @return list<string> */
function abas_admin_users_sort_columns(): array
{
    return ['username', 'role', 'phone', 'company', 'sms', 'active', 'last_login'];
}

function abas_admin_users_order_sql(string $sort, string $dir): string
{
    /** @var array<string, string> */
    $columns = [
        'username' => 'u.username',
        'role' => 'u.role',
        'phone' => 'u.phone',
        'company' => 'COALESCE(ai.company_name, "")',
        'sms' => 'u.sms_service_allowed',
        'active' => 'u.active',
        'last_login' => 'u.last_login_at',
    ];

    $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
    if (!isset($columns[$sort])) {
        return 'u.role ASC, u.username ASC';
    }

    $col = $columns[$sort];
    if ($sort === 'last_login') {
        return "$col IS NULL, $col $dir, u.username ASC";
    }

    return "$col $dir, u.username ASC";
}

/** @return list<string> */
function abas_admin_users_matching_roles(string $search): array
{
    $search = mb_strtolower(trim($search));
    if ($search === '') {
        return [];
    }

    $matched = [];
    foreach (abas_roles() as $role) {
        $label = mb_strtolower(abas_role_label($role));
        if (str_contains($label, $search) || str_contains($role, $search)) {
            $matched[] = $role;
        }
    }

    return $matched;
}

/**
 * @param list<string> $rolesInFilter
 * @return array{where:string, types:string, params:list<mixed>}
 */
function abas_admin_users_list_query_parts(array $rolesInFilter, string $search): array
{
    if ($rolesInFilter === []) {
        return ['where' => '0=1', 'types' => '', 'params' => []];
    }

    $placeholders = implode(',', array_fill(0, count($rolesInFilter), '?'));
    $where = "u.role IN ($placeholders)";
    $types = str_repeat('s', count($rolesInFilter));
    $params = $rolesInFilter;

    $search = trim($search);
    if ($search !== '') {
        $like = '%' . $search . '%';
        $roleMatches = abas_admin_users_matching_roles($search);
        $roleInSql = '';
        if ($roleMatches !== []) {
            $rolePlaceholders = implode(',', array_fill(0, count($roleMatches), '?'));
            $roleInSql = ' OR u.role IN (' . $rolePlaceholders . ')';
            $types .= str_repeat('s', count($roleMatches));
            $params = array_merge($params, $roleMatches);
        }

        $where .= ' AND (
            u.username LIKE ?
            OR u.email LIKE ?
            OR u.phone LIKE ?
            OR u.registration_display_name LIKE ?
            OR u.trekant_userid LIKE ?
            OR ai.company_name LIKE ?
            OR u.role LIKE ?
            OR u.registration_status LIKE ?
            OR EXISTS (
                SELECT 1 FROM user_installations ui
                INNER JOIN installations i ON i.id = ui.installation_id
                WHERE ui.user_id = u.id AND (i.miscno2 LIKE ? OR i.name LIKE ?)
            )' . $roleInSql . '
        )';
        $types .= str_repeat('s', 10);
        $params = array_merge($params, array_fill(0, 10, $like));
    }

    return ['where' => $where, 'types' => $types, 'params' => $params];
}

/**
 * @param list<string> $rolesInFilter
 */
function abas_admin_users_list_count(mysqli $conn, array $rolesInFilter, string $search = ''): int
{
    $parts = abas_admin_users_list_query_parts($rolesInFilter, $search);
    if ($parts['where'] === '0=1') {
        return 0;
    }

    $sql = 'SELECT COUNT(*) AS c FROM users u
         LEFT JOIN approved_installers ai ON ai.id = u.installer_id
         WHERE ' . $parts['where'];
    $stmt = $conn->prepare($sql);
    if ($parts['types'] !== '') {
        $stmt->bind_param($parts['types'], ...$parts['params']);
    }
    $stmt->execute();
    $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();

    return $count;
}

/**
 * @param list<string> $rolesInFilter
 * @return array{rows:list<array<string, mixed>>, total:int}
 */
function abas_admin_users_list_page(
    mysqli $conn,
    array $rolesInFilter,
    string $sort,
    string $sortDir,
    string $search,
    int $page,
    int $perPage = 50
): array {
    require_once __DIR__ . '/table_list.php';

    $total = abas_admin_users_list_count($conn, $rolesInFilter, $search);
    $pagination = abas_table_pagination_state($total, $page, $perPage);
    $rows = abas_admin_users_list_rows(
        $conn,
        $rolesInFilter,
        $sort,
        $sortDir,
        $search,
        $pagination['perPage'],
        $pagination['offset']
    );

    return ['rows' => $rows, 'total' => $total, 'page' => $pagination['page'], 'totalPages' => $pagination['totalPages']];
}

/**
 * @param list<string> $rolesInFilter
 * @return list<array<string, mixed>>
 */
function abas_admin_users_list_rows(
    mysqli $conn,
    array $rolesInFilter,
    string $sort,
    string $sortDir,
    string $search = '',
    int $limit = 0,
    int $offset = 0
): array {
    $parts = abas_admin_users_list_query_parts($rolesInFilter, $search);
    if ($parts['where'] === '0=1') {
        return [];
    }

    $orderSql = abas_admin_users_order_sql($sort, $sortDir);
    $sql = "SELECT u.id, u.email, u.username, u.role, u.active, u.phone, u.sms_secret_hash, u.sms_service_allowed,
            u.registration_status, u.registration_display_name, u.last_login_at, ai.company_name
     FROM users u
     LEFT JOIN approved_installers ai ON ai.id = u.installer_id
     WHERE {$parts['where']}
     ORDER BY $orderSql";

    if ($limit > 0) {
        $sql .= ' LIMIT ? OFFSET ?';
    }

    $types = $parts['types'];
    $params = $parts['params'];
    if ($limit > 0) {
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_admin_users_list_url(string $filter = 'alle', ?string $sort = null, ?string $dir = null, ?string $search = null, ?int $page = null): string
{
    $params = [];
    if ($filter !== 'alle') {
        $params['filter'] = $filter;
    }
    if ($sort !== null && $sort !== '' && in_array($sort, abas_admin_users_sort_columns(), true)) {
        $params['sort'] = $sort;
        $params['dir'] = strtolower((string) $dir) === 'desc' ? 'desc' : 'asc';
    }
    if ($search !== null && trim($search) !== '') {
        $params['q'] = trim($search);
    }
    if ($page !== null && $page > 1) {
        $params['page'] = $page;
    }

    $path = 'admin/users.php';

    return $params === [] ? abas_url($path) : abas_url($path . '?' . http_build_query($params));
}

function abas_admin_user_edit_url(int $userId, string $filter = 'alle', ?string $sort = null, ?string $dir = null, ?string $search = null): string
{
    $params = ['id' => $userId];
    if ($filter !== 'alle') {
        $params['filter'] = $filter;
    }
    if ($sort !== null && $sort !== '' && in_array($sort, abas_admin_users_sort_columns(), true)) {
        $params['sort'] = $sort;
        $params['dir'] = strtolower((string) $dir) === 'desc' ? 'desc' : 'asc';
    }
    if ($search !== null && trim($search) !== '') {
        $params['q'] = trim($search);
    }

    return abas_url('admin/user-edit.php?' . http_build_query($params));
}

/**
 * @return array{href: string, active: bool, indicator: string}
 */
function abas_admin_users_sort_link(string $column, string $currentSort, string $currentDir, string $filter, string $search = ''): array
{
    if (!in_array($column, abas_admin_users_sort_columns(), true)) {
        throw new InvalidArgumentException('Unknown sort column: ' . $column);
    }

    $nextDir = $currentSort === $column && $currentDir === 'asc' ? 'desc' : 'asc';

    return [
        'href' => abas_admin_users_list_url($filter, $column, $nextDir, $search !== '' ? $search : null),
        'active' => $currentSort === $column,
        'indicator' => $currentSort === $column ? ($currentDir === 'asc' ? '↑' : '↓') : '',
    ];
}

/** @return list<string> */
function abas_vc_anlaegsbrugere_sort_columns(): array
{
    return ['name', 'email', 'phone', 'role', 'installations'];
}

function abas_vc_anlaegsbrugere_order_sql(string $sort, string $dir): string
{
    require_once __DIR__ . '/table_list.php';

    /** @var array<string, string> */
    $columns = [
        'name' => 'COALESCE(NULLIF(u.registration_display_name, ""), u.username)',
        'email' => 'u.email',
        'phone' => 'u.phone',
        'role' => 'u.role',
        'installations' => 'COALESCE((
            SELECT MIN(i.miscno2) FROM user_installations ui
            INNER JOIN installations i ON i.id = ui.installation_id
            WHERE ui.user_id = u.id
        ), "")',
    ];
    $dir = abas_table_normalize_sort_dir($dir) === 'desc' ? 'DESC' : 'ASC';
    if (!isset($columns[$sort])) {
        return 'u.username ASC';
    }

    return $columns[$sort] . ' ' . $dir . ', u.username ASC';
}

/**
 * @return list<array<string, mixed>>
 */
function abas_list_vc_anlaegsbrugere(
    mysqli $conn,
    string $sort = '',
    string $sortDir = 'asc',
    string $search = ''
): array {
    require_once __DIR__ . '/table_list.php';

    $sort = abas_table_resolve_sort($sort, abas_vc_anlaegsbrugere_sort_columns(), 'name');
    $orderSql = abas_vc_anlaegsbrugere_order_sql($sort, $sortDir);
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
            OR (u.active = 1 AND LOCATE(?, "aktiv") > 0)
            OR (u.active = 0 AND LOCATE(?, "inaktiv") > 0)
            OR EXISTS (
                SELECT 1 FROM user_installations ui
                INNER JOIN installations i ON i.id = ui.installation_id
                WHERE ui.user_id = u.id AND (i.miscno2 LIKE ? OR i.name LIKE ?)
            )' . $roleInSql . '
        )';
        $searchTypes = str_repeat('s', 9) . $searchTypes;
        $searchParams = array_merge([$like, $like, $like, $like, $like, $search, $search, $like, $like], $searchParams);
    }

    $sql = 'SELECT u.id, u.email, u.username, u.phone, u.role, u.active, u.registration_display_name
         FROM users u
         WHERE u.role IN ("anlaegsejer", "anlaegsafprover")' . $searchSql . '
         ORDER BY ' . $orderSql;

    $types = $searchTypes;
    if ($types === '') {
        $result = $conn->query($sql);

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$searchParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}
