<?php

declare(strict_types=1);

function abas_normalize_email_domain(string $domain): string
{
    return strtolower(trim($domain));
}

function abas_installer_approved_for_domain(mysqli $conn, string $domain): ?array
{
    $domain = abas_normalize_email_domain($domain);
    if ($domain === '') {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT ai.*, aid.email_domain
         FROM approved_installer_domains aid
         INNER JOIN approved_installers ai ON ai.id = aid.installer_id
         WHERE aid.email_domain = ? AND ai.active = 1
         LIMIT 1'
    );
    $stmt->bind_param('s', $domain);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return list<string>
 */
function abas_installers_sort_columns(): array
{
    return ['company', 'montor_count'];
}

function abas_admin_installers_list_url(?string $sort = null, ?string $dir = null, ?string $search = null, ?int $page = null): string
{
    require_once __DIR__ . '/table_list.php';

    return abas_table_page_url('admin/installers.php', [
        'sort' => $sort,
        'dir' => $dir,
        'q' => $search,
        'page' => ($page !== null && $page > 1) ? $page : null,
    ]);
}

/**
 * @return array{searchSql:string, types:string, params:list<mixed>}
 */
function abas_installers_search_parts(string $search): array
{
    $search = trim($search);
    if ($search === '') {
        return ['searchSql' => '', 'types' => '', 'params' => []];
    }

    $like = '%' . $search . '%';

    return [
        'searchSql' => ' AND (
            ai.company_name LIKE ?
            OR EXISTS (
                SELECT 1 FROM approved_installer_domains aid
                WHERE aid.installer_id = ai.id AND aid.email_domain LIKE ?
            )
        )',
        'types' => 'ss',
        'params' => [$like, $like],
    ];
}

function abas_installers_order_sql(string $sort, string $sortDir): string
{
    $dir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

    return $sort === 'montor_count'
        ? "montor_count $dir, ai.company_name ASC"
        : "ai.company_name $dir";
}

/**
 * @return array{rows:list<array<string, mixed>>, total:int, page:int, totalPages:int}
 */
function abas_list_installers_page(
    mysqli $conn,
    string $search = '',
    string $sort = 'company',
    string $sortDir = 'asc',
    int $page = 1,
    int $perPage = 50
): array {
    require_once __DIR__ . '/table_list.php';

    $searchParts = abas_installers_search_parts($search);
    $countSql = 'SELECT COUNT(*) AS c FROM approved_installers ai WHERE 1=1' . $searchParts['searchSql'];
    $countStmt = $conn->prepare($countSql);
    if ($searchParts['types'] !== '') {
        $countStmt->bind_param($searchParts['types'], ...$searchParts['params']);
    }
    $countStmt->execute();
    $total = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
    $countStmt->close();

    $pagination = abas_table_pagination_state($total, $page, $perPage);
    $orderSql = abas_installers_order_sql($sort, $sortDir);
    $listSql = 'SELECT ai.*, COUNT(u.id) AS montor_count
         FROM approved_installers ai
         LEFT JOIN users u ON u.installer_id = ai.id AND u.role IN ("montor", "virksomhedsadmin")
         WHERE 1=1' . $searchParts['searchSql'] . '
         GROUP BY ai.id
         ORDER BY ' . $orderSql . '
         LIMIT ? OFFSET ?';

    $types = $searchParts['types'] . 'ii';
    $params = array_merge($searchParts['params'], [$pagination['perPage'], $pagination['offset']]);
    $listStmt = $conn->prepare($listSql);
    $listStmt->bind_param($types, ...$params);
    $listStmt->execute();
    $installers = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $listStmt->close();

    if ($installers === []) {
        return [
            'rows' => [],
            'total' => $total,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
        ];
    }

    $ids = array_map(static fn (array $row): int => (int) $row['id'], $installers);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $domainTypes = str_repeat('i', count($ids));
    $domainStmt = $conn->prepare(
        "SELECT installer_id, email_domain FROM approved_installer_domains WHERE installer_id IN ($placeholders) ORDER BY email_domain"
    );
    $domainStmt->bind_param($domainTypes, ...$ids);
    $domainStmt->execute();
    $domainRows = $domainStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $domainStmt->close();

    $domainsByInstaller = [];
    foreach ($domainRows as $row) {
        $domainsByInstaller[(int) $row['installer_id']][] = (string) $row['email_domain'];
    }

    foreach ($installers as &$installer) {
        $installer['domains'] = $domainsByInstaller[(int) $installer['id']] ?? [];
    }
    unset($installer);

    return [
        'rows' => $installers,
        'total' => $total,
        'page' => $pagination['page'],
        'totalPages' => $pagination['totalPages'],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function abas_list_installers(mysqli $conn, string $search = '', string $sort = 'company', string $sortDir = 'asc'): array
{
    $result = abas_list_installers_page($conn, $search, $sort, $sortDir, 1, 10000);

    return $result['rows'];
}

/**
 * @return list<array<string, mixed>>
 */
function abas_installers_with_domains(mysqli $conn): array
{
    $installers = $conn->query(
        'SELECT ai.*, COUNT(u.id) AS montor_count
         FROM approved_installers ai
         LEFT JOIN users u ON u.installer_id = ai.id AND u.role IN ("montor", "virksomhedsadmin")
         GROUP BY ai.id
         ORDER BY ai.company_name'
    )->fetch_all(MYSQLI_ASSOC);

    if ($installers === []) {
        return [];
    }

    $ids = array_map(static fn (array $row): int => (int) $row['id'], $installers);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare(
        "SELECT installer_id, email_domain FROM approved_installer_domains WHERE installer_id IN ($placeholders) ORDER BY email_domain"
    );
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $domainRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $domainsByInstaller = [];
    foreach ($domainRows as $row) {
        $domainsByInstaller[(int) $row['installer_id']][] = (string) $row['email_domain'];
    }

    foreach ($installers as &$installer) {
        $installer['domains'] = $domainsByInstaller[(int) $installer['id']] ?? [];
    }
    unset($installer);

    return $installers;
}

function abas_installer_montor_count(mysqli $conn, int $installerId): int
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS c FROM users WHERE installer_id = ? AND role IN ("montor", "virksomhedsadmin")'
    );
    $stmt->bind_param('i', $installerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['c'] ?? 0);
}

/**
 * @return array{ok:bool, message?:string, id?:int}
 */
function abas_installer_create(mysqli $conn, string $companyName, string $emailDomain, int $approvedByUserId): array
{
    $companyName = trim($companyName);
    $emailDomain = abas_normalize_email_domain($emailDomain);
    if ($companyName === '') {
        return ['ok' => false, 'message' => 'Angiv firmanavn.'];
    }
    if ($emailDomain === '' || !preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $emailDomain)) {
        return ['ok' => false, 'message' => 'Angiv et gyldigt e-mail-domæne.'];
    }

    $existing = abas_installer_approved_for_domain($conn, $emailDomain);
    if ($existing) {
        return ['ok' => false, 'message' => 'Domænet er allerede tilknyttet et firma.'];
    }

    $stmt = $conn->prepare(
        'INSERT INTO approved_installers (company_name, active, approved_at, approved_by_user_id) VALUES (?, 1, NOW(), ?)'
    );
    $stmt->bind_param('si', $companyName, $approvedByUserId);
    $stmt->execute();
    $installerId = (int) $stmt->insert_id;
    $stmt->close();

    $domainResult = abas_installer_add_domain($conn, $installerId, $emailDomain);
    if (!$domainResult['ok']) {
        $conn->query('DELETE FROM approved_installers WHERE id = ' . $installerId);

        return $domainResult;
    }

    return ['ok' => true, 'id' => $installerId];
}

/**
 * @return array{ok:bool, message?:string}
 */
function abas_installer_add_domain(mysqli $conn, int $installerId, string $emailDomain): array
{
    $emailDomain = abas_normalize_email_domain($emailDomain);
    if ($emailDomain === '' || !preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $emailDomain)) {
        return ['ok' => false, 'message' => 'Angiv et gyldigt e-mail-domæne.'];
    }

    $chk = $conn->prepare('SELECT id FROM approved_installers WHERE id = ? LIMIT 1');
    $chk->bind_param('i', $installerId);
    $chk->execute();
    $exists = (bool) $chk->get_result()->fetch_row();
    $chk->close();
    if (!$exists) {
        return ['ok' => false, 'message' => 'Firma ikke fundet.'];
    }

    $existing = abas_installer_approved_for_domain($conn, $emailDomain);
    if ($existing && (int) $existing['id'] !== $installerId) {
        return ['ok' => false, 'message' => 'Domænet er allerede tilknyttet et andet firma.'];
    }

    $stmt = $conn->prepare('INSERT INTO approved_installer_domains (installer_id, email_domain) VALUES (?, ?)');
    $stmt->bind_param('is', $installerId, $emailDomain);
    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        $stmt->close();
        if ($e->getCode() === 1062) {
            return ['ok' => false, 'message' => 'Domænet findes allerede for dette firma.'];
        }
        throw $e;
    }
    $stmt->close();

    return ['ok' => true];
}

/**
 * @return array{ok:bool, message?:string}
 */
function abas_installer_update_company(mysqli $conn, int $installerId, string $companyName): array
{
    $companyName = trim($companyName);
    if ($companyName === '') {
        return ['ok' => false, 'message' => 'Angiv firmanavn.'];
    }

    $stmt = $conn->prepare('UPDATE approved_installers SET company_name = ? WHERE id = ? LIMIT 1');
    $stmt->bind_param('si', $companyName, $installerId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$ok) {
        $chk = $conn->prepare('SELECT id FROM approved_installers WHERE id = ? LIMIT 1');
        $chk->bind_param('i', $installerId);
        $chk->execute();
        $exists = (bool) $chk->get_result()->fetch_row();
        $chk->close();
        if (!$exists) {
            return ['ok' => false, 'message' => 'Firma ikke fundet.'];
        }
    }

    return ['ok' => true];
}

/**
 * @return array{ok:bool, message?:string}
 */
function abas_installer_remove_domain(mysqli $conn, int $installerId, string $emailDomain): array
{
    $emailDomain = abas_normalize_email_domain($emailDomain);
    if ($emailDomain === '') {
        return ['ok' => false, 'message' => 'Angiv et domæne.'];
    }

    $countStmt = $conn->prepare('SELECT COUNT(*) AS c FROM approved_installer_domains WHERE installer_id = ?');
    $countStmt->bind_param('i', $installerId);
    $countStmt->execute();
    $domainCount = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
    $countStmt->close();
    if ($domainCount <= 1) {
        return ['ok' => false, 'message' => 'Firmaet skal have mindst ét domæne — tilføj et nyt før du sletter det sidste.'];
    }

    $stmt = $conn->prepare('DELETE FROM approved_installer_domains WHERE installer_id = ? AND email_domain = ? LIMIT 1');
    $stmt->bind_param('is', $installerId, $emailDomain);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok
        ? ['ok' => true]
        : ['ok' => false, 'message' => 'Domænet blev ikke fundet for firmaet.'];
}

/**
 * @return array{ok:bool, message:string, removed_users?:int}
 */
function abas_installer_delete(mysqli $conn, int $installerId): array
{
    $stmt = $conn->prepare('SELECT company_name FROM approved_installers WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $installerId);
    $stmt->execute();
    $installer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$installer) {
        return ['ok' => false, 'message' => 'Firma ikke fundet.'];
    }

    $stmt = $conn->prepare(
        'SELECT id FROM users WHERE installer_id = ? AND role IN ("montor", "virksomhedsadmin")'
    );
    $stmt->bind_param('i', $installerId);
    $stmt->execute();
    $res = $stmt->get_result();
    $userIds = [];
    while ($row = $res->fetch_assoc()) {
        $userIds[] = (int) $row['id'];
    }
    $stmt->close();

    $removedUsers = 0;
    foreach ($userIds as $uid) {
        $delSa = $conn->prepare('DELETE FROM service_actions WHERE user_id = ? OR on_behalf_of_user_id = ?');
        $delSa->bind_param('ii', $uid, $uid);
        $delSa->execute();
        $delSa->close();

        $delSs = $conn->prepare('DELETE FROM service_sessions WHERE user_id = ? OR on_behalf_of_user_id = ?');
        $delSs->bind_param('ii', $uid, $uid);
        $delSs->execute();
        $delSs->close();

        $delUser = $conn->prepare('DELETE FROM users WHERE id = ? AND installer_id = ?');
        $delUser->bind_param('ii', $uid, $installerId);
        $delUser->execute();
        $removedUsers += $delUser->affected_rows;
        $delUser->close();
    }

    $clear = $conn->prepare('UPDATE users SET installer_id = NULL WHERE installer_id = ?');
    $clear->bind_param('i', $installerId);
    $clear->execute();
    $clear->close();

    $delInstaller = $conn->prepare('DELETE FROM approved_installers WHERE id = ?');
    $delInstaller->bind_param('i', $installerId);
    $delInstaller->execute();
    $ok = $delInstaller->affected_rows > 0;
    $delInstaller->close();

    if (!$ok) {
        return ['ok' => false, 'message' => 'Kunne ikke slette firma.'];
    }

    $name = (string) $installer['company_name'];

    return [
        'ok' => true,
        'message' => 'Firmaet "' . $name . '" er slettet.'
            . ($removedUsers > 0 ? ' ' . $removedUsers . ' montør(er)/virksomhedsadmin(s) er fjernet.' : ''),
        'removed_users' => $removedUsers,
    ];
}
