<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function abas_activity_client_ip(): ?string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (empty($_SERVER[$header])) {
            continue;
        }
        $value = (string) $_SERVER[$header];
        if ($header === 'HTTP_X_FORWARDED_FOR') {
            $value = trim(explode(',', $value)[0]);
        }
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }
    }

    return null;
}

/**
 * @param array<string, scalar|null> $extra
 */
function abas_log_activity(
    mysqli $conn,
    string $category,
    string $action,
    ?int $userId = null,
    ?string $actorUsername = null,
    ?string $objectType = null,
    ?string $objectId = null,
    ?string $objectLabel = null,
    ?string $details = null,
    ?int $relatedSIns = null,
    ?string $relatedDealId = null,
    ?string $source = null,
    ?string $ipAddress = null,
    array $extra = []
): void {
    static $tableChecked = false;
    if (!$tableChecked) {
        $tableChecked = true;
        $chk = $conn->query("SHOW TABLES LIKE 'activity_events'");
        if (!$chk || $chk->num_rows === 0) {
            return;
        }
        $chk->close();
    }

    if ($details === null && $extra !== []) {
        $details = json_encode($extra, JSON_UNESCAPED_UNICODE);
    }

    if ($userId !== null && $userId > 0 && ($actorUsername === null || $actorUsername === '')) {
        $uStmt = $conn->prepare('SELECT username FROM users WHERE id = ? LIMIT 1');
        $uStmt->bind_param('i', $userId);
        $uStmt->execute();
        $uRow = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();
        $actorUsername = $uRow['username'] ?? null;
    }

    $stmt = $conn->prepare(
        'INSERT INTO activity_events (
            user_id, actor_username, category, action, object_type, object_id, object_label,
            details, ip_address, related_s_ins, related_deal_id, source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issssssssiss',
        $userId,
        $actorUsername,
        $category,
        $action,
        $objectType,
        $objectId,
        $objectLabel,
        $details,
        $ipAddress,
        $relatedSIns,
        $relatedDealId,
        $source
    );
    $stmt->execute();
    $stmt->close();
}

function abas_activity_installation_object(?string $miscno2, ?string $installationName, int $sIns, string $dealId): array
{
    $misc = strtolower(trim((string) $miscno2));
    $name = trim((string) $installationName);
    if ($misc !== '') {
        return [
            'id' => $misc,
            'label' => strtoupper($misc) . ($name !== '' ? ' · ' . $name : ''),
        ];
    }

    return [
        'id' => (string) $sIns,
        'label' => 'Anlæg ' . $sIns . ' · ' . $dealId,
    ];
}

function abas_log_user_target_event(
    mysqli $conn,
    string $category,
    string $action,
    int $targetUserId,
    ?int $actorUserId = null,
    ?string $targetLabel = null,
    ?string $details = null,
    ?string $source = 'web'
): void {
    abas_log_activity(
        $conn,
        $category,
        $action,
        $actorUserId,
        null,
        'user',
        (string) $targetUserId,
        $targetLabel,
        $details,
        null,
        null,
        $source,
        abas_activity_client_ip()
    );
}

function abas_activity_action_label(string $category, string $action): string
{
    $key = $category . '/' . $action;

    return match ($key) {
        'service/start_service' => 'Service startet',
        'service/stop_service' => 'Service stoppet',
        'service/extend_service' => 'Service forlænget',
        'service/add_comment' => 'Servicekommentar',
        'auth/login' => 'Login',
        'auth/logout' => 'Logout',
        'auth/mfa_enrolled' => '2FA oprettet',
        'auth/mfa_reset' => '2FA nulstillet',
        'auth/mfa_method_set' => '2FA-metode ændret',
        'auth/password_reset_sent' => 'Nulstillingslink sendt',
        'auth/welcome_sent' => 'Velkomstlink sendt',
        'auth/password_set' => 'Adgangskode sat',
        'auth/password_changed' => 'Adgangskode ændret',
        'user/created' => 'Bruger oprettet',
        'user/updated' => 'Bruger opdateret',
        'user/profile_updated' => 'Profil opdateret',
        'user/deactivated' => 'Bruger deaktiveret',
        'user/deleted' => 'Bruger slettet',
        'registration/submitted' => 'Ansøgning modtaget',
        'registration/approved' => 'Ansøgning godkendt',
        'registration/rejected' => 'Ansøgning afvist',
        'installer/created' => 'Installatør oprettet',
        'installer/updated' => 'Installatør opdateret',
        'api/health' => 'API health',
        'api/search' => 'API søgning',
        'api/service' => 'API service',
        'api/fetch_log' => 'API alarmlog',
        'api/error' => 'API fejl',
        'api/request' => 'API kald',
        default => str_replace('_', ' ', $action),
    };
}

/** @return list<string> */
function abas_activity_categories(): array
{
    return ['service', 'auth', 'user', 'registration', 'installer', 'sms', 'system', 'api'];
}

/** @return list<string> */
function abas_activity_category_actions(mysqli $conn, ?string $category = null): array
{
    static $tableChecked = false;
    if (!$tableChecked) {
        $tableChecked = true;
        $chk = $conn->query("SHOW TABLES LIKE 'activity_events'");
        if (!$chk || $chk->num_rows === 0) {
            return [];
        }
        $chk->close();
    }

    if ($category !== null && $category !== '') {
        $stmt = $conn->prepare(
            'SELECT DISTINCT action FROM activity_events WHERE category = ? ORDER BY action'
        );
        $stmt->bind_param('s', $category);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return array_column($rows, 'action');
    }

    $rows = $conn->query(
        'SELECT DISTINCT category, action FROM activity_events ORDER BY category, action'
    )->fetch_all(MYSQLI_ASSOC);

    return array_map(
        static fn (array $row): string => $row['category'] . '/' . $row['action'],
        $rows
    );
}

/**
 * @param array<string, scalar|null> $filters
 * @return array{rows:list<array<string,mixed>>, total:int}
 */
function abas_activity_search(mysqli $conn, array $filters, int $limit = 50, int $offset = 0): array
{
    static $tableChecked = false;
    if (!$tableChecked) {
        $tableChecked = true;
        $chk = $conn->query("SHOW TABLES LIKE 'activity_events'");
        if (!$chk || $chk->num_rows === 0) {
            return ['rows' => [], 'total' => 0];
        }
        $chk->close();
    }

    $where = ['1=1'];
    $types = '';
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(ae.actor_username LIKE ? OR ae.object_label LIKE ? OR ae.details LIKE ? OR ae.object_id LIKE ?)';
        $types .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }

    $category = trim((string) ($filters['category'] ?? ''));
    if ($category !== '' && in_array($category, abas_activity_categories(), true)) {
        $where[] = 'ae.category = ?';
        $types .= 's';
        $params[] = $category;
    }

    $action = trim((string) ($filters['action'] ?? ''));
    if ($action !== '') {
        $where[] = 'ae.action = ?';
        $types .= 's';
        $params[] = $action;
    }

    $userId = (int) ($filters['user_id'] ?? 0);
    if ($userId > 0) {
        $where[] = 'ae.user_id = ?';
        $types .= 'i';
        $params[] = $userId;
    }

    $sInsFilter = trim((string) ($filters['s_ins'] ?? ''));
    if ($sInsFilter !== '' && $sInsFilter !== '0') {
        if (ctype_digit($sInsFilter)) {
            $where[] = 'ae.related_s_ins = ?';
            $types .= 'i';
            $params[] = (int) $sInsFilter;
        } else {
            $misc = strtolower($sInsFilter);
            $where[] = '(LOWER(ae.object_id) = ? OR ae.object_label LIKE ?)';
            $types .= 'ss';
            $params[] = $misc;
            $params[] = '%' . strtoupper($misc) . '%';
        }
    }

    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    if ($dateFrom !== '') {
        $where[] = 'ae.created_at >= ?';
        $types .= 's';
        $params[] = $dateFrom . ' 00:00:00';
    }

    $dateTo = trim((string) ($filters['date_to'] ?? ''));
    if ($dateTo !== '') {
        $where[] = 'ae.created_at <= ?';
        $types .= 's';
        $params[] = $dateTo . ' 23:59:59';
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) AS c FROM activity_events ae WHERE $whereSql";
    $countStmt = $conn->prepare($countSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int) ($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
    $countStmt->close();

    $sql = "SELECT ae.*, u.email AS actor_email, u.role AS actor_role
            FROM activity_events ae
            LEFT JOIN users u ON u.id = ae.user_id
            WHERE $whereSql
            ORDER BY ae.created_at DESC, ae.id DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $bindTypes = $types . 'ii';
    $bindParams = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return ['rows' => $rows, 'total' => $total];
}

/**
 * @return array<string, int>
 */
function abas_activity_service_stats(mysqli $conn): array
{
    $stats = [
        'start_service' => 0,
        'stop_service' => 0,
        'extend_service' => 0,
        'add_comment' => 0,
    ];

    static $tableChecked = false;
    if (!$tableChecked) {
        $tableChecked = true;
        $chk = $conn->query("SHOW TABLES LIKE 'activity_events'");
        if (!$chk || $chk->num_rows === 0) {
            $res = $conn->query(
                "SELECT action, COUNT(*) AS c FROM service_actions
                 WHERE action IN ('start_service','stop_service','extend_service','add_comment')
                 GROUP BY action"
            );
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $stats[(string) $row['action']] = (int) $row['c'];
                }
            }

            return $stats;
        }
        $chk->close();
    }

    $res = $conn->query(
        "SELECT action, COUNT(*) AS c FROM activity_events
         WHERE category = 'service'
           AND action IN ('start_service','stop_service','extend_service','add_comment')
         GROUP BY action"
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $stats[(string) $row['action']] = (int) $row['c'];
        }
    }

    return $stats;
}

/**
 * @return array<string, int>
 */
function abas_admin_dashboard_stats(mysqli $conn): array
{
    $stats = [
        'users_active' => 0,
        'users_pending' => 0,
        'installers_active' => 0,
        'service_active' => 0,
        'montors' => 0,
        'virksomhedsadmins' => 0,
    ];

    $row = $conn->query(
        "SELECT
            SUM(role NOT IN ('admin') AND active = 1 AND registration_status = 'approved') AS users_active,
            SUM(registration_status = 'pending') AS users_pending,
            SUM(role = 'montor' AND active = 1) AS montors,
            SUM(role = 'virksomhedsadmin' AND active = 1) AS virksomhedsadmins
         FROM users"
    )->fetch_assoc();
    if ($row) {
        $stats['users_active'] = (int) ($row['users_active'] ?? 0);
        $stats['users_pending'] = (int) ($row['users_pending'] ?? 0);
        $stats['montors'] = (int) ($row['montors'] ?? 0);
        $stats['virksomhedsadmins'] = (int) ($row['virksomhedsadmins'] ?? 0);
    }

    $stats['installers_active'] = (int) ($conn->query(
        'SELECT COUNT(*) AS c FROM approved_installers WHERE active = 1'
    )->fetch_assoc()['c'] ?? 0);

    $stats['service_active'] = (int) ($conn->query(
        "SELECT COUNT(*) AS c FROM service_sessions WHERE status = 'active'"
    )->fetch_assoc()['c'] ?? 0);

    return $stats;
}

/** @return list<array<string,mixed>> */
function abas_activity_recent(mysqli $conn, int $limit = 15): array
{
    $result = abas_activity_search($conn, [], $limit, 0);

    return $result['rows'];
}

function abas_activity_log_retention_days(): ?int
{
    require_once __DIR__ . '/config.php';
    $raw = abas_env('ACTIVITY_LOG_RETENTION_DAYS');
    if ($raw === null || $raw === '' || $raw === '0') {
        return null;
    }
    $days = (int) $raw;

    return $days > 0 ? $days : null;
}

/**
 * @return array{purged: int, retention_days: ?int, skipped?: string}
 */
function abas_activity_purge_expired(mysqli $conn, bool $force = false): array
{
    $days = abas_activity_log_retention_days();
    if ($days === null) {
        return ['purged' => 0, 'retention_days' => null];
    }

    static $tableChecked = false;
    if (!$tableChecked) {
        $tableChecked = true;
        $chk = $conn->query("SHOW TABLES LIKE 'activity_events'");
        if (!$chk || $chk->num_rows === 0) {
            return ['purged' => 0, 'retention_days' => $days, 'skipped' => 'no_table'];
        }
        $chk->close();
    }

    if (!$force) {
        require_once __DIR__ . '/config.php';
        $last = abas_setting($conn, 'activity_log_last_purge', '');
        if ($last === date('Y-m-d')) {
            return ['purged' => 0, 'retention_days' => $days, 'skipped' => 'already_today'];
        }
    }

    $stmt = $conn->prepare('DELETE FROM activity_events WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)');
    if (!$stmt) {
        return ['purged' => 0, 'retention_days' => $days, 'skipped' => 'prepare_failed'];
    }
    $stmt->bind_param('i', $days);
    $stmt->execute();
    $purged = $stmt->affected_rows;
    $stmt->close();

    abas_set_setting($conn, 'activity_log_last_purge', date('Y-m-d'));

    return ['purged' => max(0, $purged), 'retention_days' => $days];
}
