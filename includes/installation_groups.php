<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function abas_installation_group_public_id(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * @return list<string>
 */
function abas_installation_groups_sort_columns(): array
{
    return ['name', 'members', 'users', 'updated'];
}

function abas_admin_installation_groups_list_url(?string $sort = null, ?string $dir = null, ?string $q = null, ?int $page = null): string
{
    $params = array_filter([
        'sort' => $sort,
        'dir' => $dir,
        'q' => $q,
        'page' => $page !== null && $page > 1 ? (string) $page : null,
    ], static fn ($v) => $v !== null && $v !== '');

    $query = $params === [] ? '' : '?' . http_build_query($params);

    return abas_url('admin/installation-groups.php' . $query);
}

function abas_admin_installation_group_edit_url(int $groupId): string
{
    return abas_url('admin/installation-group-edit.php?id=' . $groupId);
}

/**
 * @return array{rows:list<array<string,mixed>>, total:int, page:int, totalPages:int}
 */
function abas_list_installation_groups_page(
    mysqli $conn,
    string $search,
    string $sort,
    string $sortDir,
    int $page
): array {
    require_once __DIR__ . '/table_list.php';

    $page = max(1, $page);
    $perPage = 25;
    $search = trim($search);
    $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';
    $allowed = abas_installation_groups_sort_columns();
    if (!in_array($sort, $allowed, true)) {
        $sort = 'name';
    }

    $orderSql = match ($sort) {
        'members' => 'member_count',
        'users' => 'user_count',
        'updated' => 'g.updated_at',
        default => 'g.name',
    };

    $where = '';
    $types = '';
    $params = [];
    if ($search !== '') {
        $like = '%' . $search . '%';
        $where = ' WHERE (g.name LIKE ? OR g.public_id LIKE ? OR g.description LIKE ?)';
        $types = 'sss';
        $params = [$like, $like, $like];
    }

    $countSql = 'SELECT COUNT(*) FROM installation_groups g' . $where;
    $countStmt = $conn->prepare($countSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int) ($countStmt->get_result()->fetch_row()[0] ?? 0);
    $countStmt->close();

    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $sql = 'SELECT g.*,
            (SELECT COUNT(*) FROM installation_group_members igm WHERE igm.group_id = g.id) AS member_count,
            (SELECT COUNT(*) FROM user_installation_groups uig WHERE uig.group_id = g.id) AS user_count
            FROM installation_groups g'
        . $where
        . ' ORDER BY ' . $orderSql . ' ' . strtoupper($sortDir)
        . ', g.id ASC LIMIT ? OFFSET ?';

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
    } else {
        $stmt->bind_param('ii', $perPage, $offset);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'totalPages' => $totalPages,
    ];
}

function abas_installation_group_get(mysqli $conn, int $groupId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM installation_groups WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $groupId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return array{ok:bool, message?:string, id?:int}
 */
function abas_installation_group_create(mysqli $conn, string $name, string $description, int $actorId): array
{
    $name = trim($name);
    if ($name === '') {
        return ['ok' => false, 'message' => 'Angiv et navn til gruppen.'];
    }

    $publicId = abas_installation_group_public_id();
    $descriptionDb = trim($description) !== '' ? trim($description) : null;
    $stmt = $conn->prepare(
        'INSERT INTO installation_groups (public_id, name, description, created_by_user_id) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('sssi', $publicId, $name, $descriptionDb, $actorId);
    $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();

    return ['ok' => true, 'id' => $id];
}

/**
 * @return array{ok:bool, message?:string}
 */
function abas_installation_group_update(mysqli $conn, int $groupId, string $name, string $description): array
{
    $name = trim($name);
    if ($name === '') {
        return ['ok' => false, 'message' => 'Angiv et navn til gruppen.'];
    }

    $descriptionDb = trim($description) !== '' ? trim($description) : null;
    $stmt = $conn->prepare('UPDATE installation_groups SET name = ?, description = ? WHERE id = ?');
    $stmt->bind_param('ssi', $name, $descriptionDb, $groupId);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();

    return $ok ? ['ok' => true] : ['ok' => false, 'message' => 'Gruppen findes ikke.'];
}

/**
 * @return array{ok:bool, message:string}
 */
function abas_installation_group_delete(mysqli $conn, int $groupId): array
{
    $group = abas_installation_group_get($conn, $groupId);
    if (!$group) {
        return ['ok' => false, 'message' => 'Gruppen findes ikke.'];
    }

    $stmt = $conn->prepare('DELETE FROM installation_groups WHERE id = ?');
    $stmt->bind_param('i', $groupId);
    $stmt->execute();
    $stmt->close();

    return ['ok' => true, 'message' => 'Gruppen «' . ($group['name'] ?? '') . '» er slettet.'];
}

/**
 * @return list<array<string, mixed>>
 */
function abas_installation_group_members(mysqli $conn, int $groupId): array
{
    $stmt = $conn->prepare(
        'SELECT i.id, i.miscno2, i.name, i.city, i.ins_no
         FROM installation_group_members igm
         JOIN installations i ON i.id = igm.installation_id
         WHERE igm.group_id = ?
         ORDER BY i.miscno2'
    );
    $stmt->bind_param('i', $groupId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * @param list<int> $installationIds
 */
function abas_installation_group_set_members(mysqli $conn, int $groupId, array $installationIds): void
{
    $wanted = [];
    foreach ($installationIds as $installationId) {
        $installationId = (int) $installationId;
        if ($installationId > 0) {
            $wanted[$installationId] = true;
        }
    }

    $current = [];
    $stmt = $conn->prepare('SELECT installation_id FROM installation_group_members WHERE group_id = ?');
    $stmt->bind_param('i', $groupId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $current[(int) $row['installation_id']] = true;
    }
    $stmt->close();

    $insert = $conn->prepare(
        'INSERT IGNORE INTO installation_group_members (group_id, installation_id) VALUES (?, ?)'
    );
    foreach (array_keys($wanted) as $installationId) {
        if (!isset($current[$installationId])) {
            $insert->bind_param('ii', $groupId, $installationId);
            $insert->execute();
        }
    }
    $insert->close();

    $delete = $conn->prepare(
        'DELETE FROM installation_group_members WHERE group_id = ? AND installation_id = ?'
    );
    foreach (array_keys($current) as $installationId) {
        if (!isset($wanted[$installationId])) {
            $delete->bind_param('ii', $groupId, $installationId);
            $delete->execute();
        }
    }
    $delete->close();
}

/**
 * @return list<array{id:int, miscno2:string, name:string, city:string, ins_no:string}>
 */
function abas_search_installations_for_group_editor(
    mysqli $conn,
    string $q,
    bool $useApi,
    ?array $apiUser
): array {
    $q = trim($q);
    if ($q === '') {
        return [];
    }

    require_once __DIR__ . '/installation_sync.php';

    $rows = abas_search_installations_local($conn, $q, true, 0);
    if ($rows === [] && $useApi && $apiUser !== null && abas_is_miscno2_query($q)) {
        try {
            $rows = abas_search_installations_from_api($conn, $apiUser, $q);
        } catch (Throwable) {
            return [];
        }
    }

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) ($row['id'] ?? 0),
            'miscno2' => (string) ($row['miscno2'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'city' => (string) ($row['city'] ?? ''),
            'ins_no' => (string) ($row['ins_no'] ?? ''),
        ];
    }

    return $out;
}

/**
 * @return list<array{id:int, public_id:string, name:string, description:string, member_count:int, user_count:int}>
 */
function abas_list_all_installation_groups(mysqli $conn): array
{
    $res = $conn->query(
        'SELECT g.id, g.public_id, g.name, g.description,
            (SELECT COUNT(*) FROM installation_group_members igm WHERE igm.group_id = g.id) AS member_count,
            (SELECT COUNT(*) FROM user_installation_groups uig WHERE uig.group_id = g.id) AS user_count
         FROM installation_groups g
         ORDER BY g.name, g.id'
    );
    if (!$res) {
        return [];
    }
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();

    return $rows;
}

function abas_installation_groups_user_picker_threshold(): int
{
    return 10;
}

function abas_count_installation_groups(mysqli $conn): int
{
    $res = $conn->query('SELECT COUNT(*) FROM installation_groups');
    if (!$res) {
        return 0;
    }
    $count = (int) ($res->fetch_row()[0] ?? 0);
    $res->free();

    return $count;
}

/**
 * Grupper til bruger-vælger (ekskl. allerede tilknyttede).
 *
 * @param list<int> $excludeGroupIds
 * @return list<array{id:int, public_id:string, name:string, description:string, member_count:int}>
 */
function abas_search_installation_groups_for_user_picker(
    mysqli $conn,
    string $search,
    array $excludeGroupIds,
    int $limit = 30
): array {
    $search = trim($search);
    $limit = max(1, min($limit, 100));
    $excludeGroupIds = array_values(array_filter(array_map(static fn ($id): int => (int) $id, $excludeGroupIds), static fn (int $id): bool => $id > 0));

    $where = '';
    $types = '';
    $params = [];

    if ($excludeGroupIds !== []) {
        $placeholders = implode(',', array_fill(0, count($excludeGroupIds), '?'));
        $where = ' WHERE g.id NOT IN (' . $placeholders . ')';
        $types = str_repeat('i', count($excludeGroupIds));
        $params = $excludeGroupIds;
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $where .= $where === '' ? ' WHERE ' : ' AND ';
        $where .= '(g.name LIKE ? OR g.public_id LIKE ? OR g.description LIKE ?)';
        $types .= 'sss';
        array_push($params, $like, $like, $like);
    }

    $sql = 'SELECT g.id, g.public_id, g.name, g.description,
            (SELECT COUNT(*) FROM installation_group_members igm WHERE igm.group_id = g.id) AS member_count
            FROM installation_groups g'
        . $where
        . ' ORDER BY g.name, g.id LIMIT ?';

    $types .= 'i';
    $params[] = $limit;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_admin_user_edit_url_with_group_search(
    int $userId,
    string $groupSearch,
    ?string $listFilter = null,
    ?string $listSort = null,
    ?string $listDir = null,
    ?string $listSearch = null
): string {
    $params = array_filter([
        'id' => (string) $userId,
        'group_q' => $groupSearch !== '' ? $groupSearch : null,
        'filter' => $listFilter !== null && $listFilter !== '' && $listFilter !== 'alle' ? $listFilter : null,
        'sort' => $listSort,
        'dir' => $listDir !== null && $listDir !== 'asc' ? $listDir : null,
        'q' => $listSearch,
    ], static fn ($v) => $v !== null && $v !== '');

    return abas_url('admin/user-edit.php?' . http_build_query($params));
}

/**
 * @return list<array{id:int, public_id:string, name:string, member_count:int}>
 */
function abas_user_installation_group_links(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare(
        'SELECT g.id, g.public_id, g.name,
            (SELECT COUNT(*) FROM installation_group_members igm WHERE igm.group_id = g.id) AS member_count
         FROM user_installation_groups uig
         JOIN installation_groups g ON g.id = uig.group_id
         WHERE uig.user_id = ?
         ORDER BY g.name, g.id'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * @param list<int> $groupIds
 */
function abas_user_set_installation_groups(mysqli $conn, int $userId, array $groupIds): void
{
    $wanted = [];
    foreach ($groupIds as $groupId) {
        $groupId = (int) $groupId;
        if ($groupId > 0) {
            $wanted[$groupId] = true;
        }
    }

    $current = [];
    $stmt = $conn->prepare('SELECT group_id FROM user_installation_groups WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $current[(int) $row['group_id']] = true;
    }
    $stmt->close();

    $insert = $conn->prepare('INSERT IGNORE INTO user_installation_groups (user_id, group_id) VALUES (?, ?)');
    foreach (array_keys($wanted) as $groupId) {
        if (!isset($current[$groupId])) {
            $insert->bind_param('ii', $userId, $groupId);
            $insert->execute();
        }
    }
    $insert->close();

    $delete = $conn->prepare('DELETE FROM user_installation_groups WHERE user_id = ? AND group_id = ?');
    foreach (array_keys($current) as $groupId) {
        if (!isset($wanted[$groupId])) {
            $delete->bind_param('ii', $userId, $groupId);
            $delete->execute();
        }
    }
    $delete->close();
}

function abas_user_role_uses_installation_groups(string $role): bool
{
    return in_array($role, ['montor', 'anlaegsejer', 'anlaegsafprover'], true);
}

function abas_user_role_always_scoped_to_installations(string $role): bool
{
    return in_array($role, ['anlaegsejer', 'anlaegsafprover'], true);
}

function abas_user_uses_scoped_installation_access(array $user): bool
{
    $role = (string) ($user['role'] ?? '');
    if (abas_user_role_always_scoped_to_installations($role)) {
        return true;
    }
    if ($role === 'montor') {
        return !empty($user['montor_scoped_access']);
    }

    return false;
}

/**
 * @return list<int>
 */
function abas_user_accessible_installation_ids(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare(
        'SELECT installation_id AS id FROM user_installations WHERE user_id = ?
         UNION
         SELECT igm.installation_id AS id
         FROM user_installation_groups uig
         JOIN installation_group_members igm ON igm.group_id = uig.group_id
         WHERE uig.user_id = ?'
    );
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $ids = [];
    while ($row = $res->fetch_assoc()) {
        $ids[] = (int) ($row['id'] ?? 0);
    }
    $stmt->close();

    return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
}

/**
 * @return list<array<string, mixed>>
 */
function abas_user_accessible_installations(mysqli $conn, int $userId): array
{
    $ids = abas_user_accessible_installation_ids($conn, $userId);
    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare(
        'SELECT * FROM installations WHERE id IN (' . $placeholders . ') ORDER BY miscno2'
    );
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_user_has_installation_access(mysqli $conn, int $userId, int $installationId): bool
{
    if ($installationId <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT 1 FROM user_installations WHERE user_id = ? AND installation_id = ?
         UNION
         SELECT 1
         FROM user_installation_groups uig
         JOIN installation_group_members igm ON igm.group_id = uig.group_id
         WHERE uig.user_id = ? AND igm.installation_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('iiii', $userId, $installationId, $userId, $installationId);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

/**
 * @return list<array<string, mixed>>
 */
function abas_search_user_accessible_installations_local(mysqli $conn, int $userId, string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return abas_user_accessible_installations($conn, $userId);
    }

    $like = '%' . $q . '%';
    $stmt = $conn->prepare(
        'SELECT DISTINCT i.*
         FROM installations i
         WHERE i.id IN (
             SELECT ui.installation_id FROM user_installations ui WHERE ui.user_id = ?
             UNION
             SELECT igm.installation_id
             FROM user_installation_groups uig
             JOIN installation_group_members igm ON igm.group_id = uig.group_id
             WHERE uig.user_id = ?
         )
         AND (i.miscno2 LIKE ? OR i.name LIKE ? OR i.ins_no LIKE ? OR i.city LIKE ?)
         ORDER BY i.miscno2
         LIMIT 50'
    );
    $stmt->bind_param('iissss', $userId, $userId, $like, $like, $like, $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_set_user_montor_scoped_access(mysqli $conn, int $userId, bool $scoped): void
{
    $flag = $scoped ? 1 : 0;
    $stmt = $conn->prepare('UPDATE users SET montor_scoped_access = ? WHERE id = ?');
    $stmt->bind_param('ii', $flag, $userId);
    $stmt->execute();
    $stmt->close();
}
