<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cron_auth.php';
require_once __DIR__ . '/trekant_client.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/service_notifications.php';
require_once __DIR__ . '/sms.php';

function abas_reconcile_verify_request(): bool
{
    return abas_cron_verify_request(['SERVICE_RECONCILE_CRON_SECRET', 'SYNC_CRON_SECRET']);
}

function abas_reconcile_request_auth_error(): string
{
    return abas_cron_auth_error(['SERVICE_RECONCILE_CRON_SECRET', 'SYNC_CRON_SECRET'], 'reconcile');
}

function abas_parse_trekant_queue_datetime(?string $date, ?string $time): ?string
{
    $date = trim((string) $date);
    $time = trim((string) $time);
    if ($date === '' || $time === '') {
        return null;
    }
    $parts = explode('/', $date);
    if (count($parts) === 3) {
        $date = sprintf('%02d-%02d-%02d', (int) $parts[2], (int) $parts[1], (int) $parts[0]);
    }
    $ts = strtotime($date . ' ' . $time);

    return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
}

/**
 * @return list<array<string, mixed>>
 */
function abas_active_abas_service_sessions(mysqli $conn): array
{
    $result = $conn->query(
        'SELECT ss.*, i.s_ins, i.deal_id, i.miscno2, i.name AS installation_name,
                u.phone AS user_phone, u.username AS user_username
         FROM service_sessions ss
         INNER JOIN installations i ON i.id = ss.installation_id
         INNER JOIN users u ON u.id = ss.user_id
         WHERE ss.status = "active"
         ORDER BY ss.id ASC'
    );
    if (!$result) {
        return [];
    }
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();

    return $rows;
}

function abas_installation_in_testqueue(mysqli $conn, int $sIns, string $dealId): bool
{
    $client = abas_trekant();
    $resp = $client->getTestQueueStatus($sIns, $dealId, 1);

    return abas_trekant_return_code($resp) === 0 && abas_trekant_rows($resp) !== [];
}

/**
 * @return array<string, mixed>|null
 */
function abas_external_testqueue_for_installation(mysqli $conn, int $installationId): ?array
{
    $stmt = $conn->prepare(
        'SELECT et.*, i.miscno2, i.name, i.s_ins, i.deal_id, i.mon_stat
         FROM installation_external_testqueue et
         INNER JOIN installations i ON i.id = et.installation_id
         WHERE et.installation_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $installationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return list<array<string, mixed>>
 */
function abas_external_testqueue_installations(mysqli $conn): array
{
    $result = $conn->query(
        'SELECT et.*, i.miscno2, i.name, i.city, i.s_ins, i.deal_id, i.mon_stat
         FROM installation_external_testqueue et
         INNER JOIN installations i ON i.id = et.installation_id
         ORDER BY et.updated_at DESC'
    );
    if (!$result) {
        return [];
    }
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();

    return $rows;
}

function abas_clear_external_testqueue(mysqli $conn, int $installationId): void
{
    $stmt = $conn->prepare('DELETE FROM installation_external_testqueue WHERE installation_id = ?');
    $stmt->bind_param('i', $installationId);
    $stmt->execute();
    $stmt->close();
}

function abas_upsert_external_testqueue(mysqli $conn, int $installationId, array $queueRow): void
{
    $sInc = (int) ($queueRow['s_inc'] ?? 0);
    $userId = trim((string) ($queueRow['user_id'] ?? ''));
    $comment = trim((string) ($queueRow['comment'] ?? ''));
    if ($comment === '') {
        $comment = trim((string) ($queueRow['comm_gen'] ?? ''));
    }
    $endAt = abas_parse_trekant_queue_datetime(
        (string) ($queueRow['end_date'] ?? ''),
        (string) ($queueRow['end_time'] ?? '')
    );

    $stmt = $conn->prepare(
        'INSERT INTO installation_external_testqueue (installation_id, s_inc, trekant_user_id, queue_comment, end_at)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE s_inc=VALUES(s_inc), trekant_user_id=VALUES(trekant_user_id),
         queue_comment=VALUES(queue_comment), end_at=VALUES(end_at), updated_at=NOW()'
    );
    $stmt->bind_param('iisss', $installationId, $sInc, $userId, $comment, $endAt);
    $stmt->execute();
    $stmt->close();
}

function abas_close_session_ended_externally(mysqli $conn, array $sessionRow): bool
{
    $sessionId = (int) ($sessionRow['id'] ?? 0);
    if ($sessionId <= 0) {
        return false;
    }

    $stmt = $conn->prepare(
        'UPDATE service_sessions SET status="ended", ended_at=NOW() WHERE id=? AND status="active"'
    );
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $closed = $stmt->affected_rows > 0;
    $stmt->close();
    if (!$closed) {
        return false;
    }

    $userId = (int) $sessionRow['user_id'];
    $onBehalf = isset($sessionRow['on_behalf_of_user_id']) ? (int) $sessionRow['on_behalf_of_user_id'] : null;
    if ($onBehalf !== null && $onBehalf <= 0) {
        $onBehalf = null;
    }

    $userStmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    $installation = [
        'id' => (int) $sessionRow['installation_id'],
        's_ins' => (int) $sessionRow['s_ins'],
        'deal_id' => (string) $sessionRow['deal_id'],
        'miscno2' => (string) ($sessionRow['miscno2'] ?? ''),
        'name' => (string) ($sessionRow['installation_name'] ?? ''),
    ];

    if ($user) {
        abas_log_service_action(
            $conn,
            $userId,
            $onBehalf,
            $sessionId,
            (int) $installation['s_ins'],
            (string) $installation['deal_id'],
            'stop_service',
            null,
            'Afsluttet uden for ABA Service (VC/Trekant)',
            'cron',
            0
        );
        abas_notify_service_ended_externally($conn, $user, $installation, $onBehalf, $sessionId);
    }

    return true;
}

function abas_notify_service_ended_externally(
    mysqli $conn,
    array $actor,
    array $installation,
    ?int $onBehalfUserId,
    ?int $sessionId
): void {
    $phone = abas_service_notification_phone($conn, $actor, $onBehalfUserId);
    if ($phone === '') {
        return;
    }

    $misc = abas_service_notification_misc($installation);
    $name = trim((string) ($installation['name'] ?? ''));
    $body = 'ABA: Anlæg ' . $misc;
    if ($name !== '') {
        $body .= ' (' . $name . ')';
    }
    $body .= ' er sat i drift igen uden for ABA Service (fx af VC). Din ABAS-service er afsluttet.';

    abas_sms_queue($conn, $phone, $body, 'service_external_end', $sessionId);
}

/**
 * @return array{ok:bool, message?:string, code?:int}
 */
function abas_stop_external_testqueue(
    mysqli $conn,
    array $user,
    array $installation,
    string $comment = ''
): array {
    if (!abas_installation_allows_service((string) ($installation['mon_stat'] ?? ''))) {
        $label = abas_mon_stat_label((string) ($installation['mon_stat'] ?? ''));

        return ['ok' => false, 'message' => 'Anlægget er ' . strtolower($label) . ' og kan ikke sættes i drift.'];
    }

    $ext = abas_external_testqueue_for_installation($conn, (int) $installation['id']);
    $sInc = $ext ? (int) ($ext['s_inc'] ?? 0) : 0;

    if ($sInc <= 0) {
        $client = abas_trekant();
        $resp = $client->getTestQueueStatus((int) $installation['s_ins'], (string) $installation['deal_id'], 1);
        $rows = abas_trekant_rows($resp);
        if ($rows === []) {
            abas_clear_external_testqueue($conn, (int) $installation['id']);

            return ['ok' => false, 'message' => 'Anlægget er ikke i testkø længere.'];
        }
        $sInc = (int) ($rows[0]['s_inc'] ?? 0);
    }

    $client = abas_trekant();
    $comm = abas_trekant_trim_comment($comment !== '' ? $comment : 'ABA Service stop (ekstern test)');
    $comm = abas_enrich_service_start_comment($conn, $user, $comm);
    $resp = $client->stopService((int) $installation['s_ins'], (string) $installation['deal_id'], $sInc > 0 ? $sInc : null, $comm);
    $code = abas_trekant_return_code($resp);
    abas_log_service_action(
        $conn,
        (int) $user['id'],
        null,
        null,
        (int) $installation['s_ins'],
        (string) $installation['deal_id'],
        'stop_service',
        null,
        $comm,
        'web',
        $code
    );

    if ($code !== 0 && $code !== 15974) {
        return ['ok' => false, 'code' => $code, 'message' => $resp['message']['message'] ?? 'Kunne ikke sætte anlæg i drift.'];
    }

    if ($comm !== '' && $sInc > 0) {
        $addResp = $client->addLogComment((int) $installation['s_ins'], (string) $installation['deal_id'], $sInc, $comm);
        if (abas_trekant_return_code($addResp) !== 0) {
            error_log('ABA addLogComment after external stop failed s_inc=' . $sInc);
        }
    }

    abas_clear_external_testqueue($conn, (int) $installation['id']);

    return ['ok' => true, 'code' => $code];
}

/**
 * @return array{s_ins:int, deal_id:string}|null
 */
function abas_queue_row_identity(array $row): ?array
{
    $sIns = (int) ($row['s_ins'] ?? $row['insid'] ?? 0);
    $dealId = trim((string) ($row['deal_id'] ?? $row['deal'] ?? ''));
    if ($sIns <= 0 || $dealId === '') {
        return null;
    }

    return ['s_ins' => $sIns, 'deal_id' => $dealId];
}

function abas_find_installation_by_s_ins(mysqli $conn, int $sIns, string $dealId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM installations WHERE s_ins = ? AND deal_id = ? LIMIT 1');
    $stmt->bind_param('is', $sIns, $dealId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return list<array{id:int, s_ins:int, deal_id:string}>
 */
function abas_installations_for_reconcile_scan(mysqli $conn, int $limit, int $afterId = 0): array
{
    if ($limit <= 0) {
        return [];
    }

    if ($afterId > 0) {
        $stmt = $conn->prepare(
            'SELECT id, s_ins, deal_id FROM installations WHERE s_ins > 0 AND id > ? ORDER BY id ASC LIMIT ?'
        );
        $stmt->bind_param('ii', $afterId, $limit);
    } else {
        $stmt = $conn->prepare(
            'SELECT id, s_ins, deal_id FROM installations WHERE s_ins > 0 ORDER BY id ASC LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * @return list<array{id:int, s_ins:int, deal_id:string}>
 */
function abas_reconcile_scan_targets(mysqli $conn, int $batchLimit, int $cursorId): array
{
    $targets = [];
    $seen = [];

    foreach (abas_external_testqueue_installations($conn) as $ext) {
        $id = (int) $ext['installation_id'];
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $targets[] = [
            'id' => $id,
            's_ins' => (int) $ext['s_ins'],
            'deal_id' => (string) $ext['deal_id'],
        ];
    }

    $batch = abas_installations_for_reconcile_scan($conn, $batchLimit, $cursorId);
    if ($batch === [] && $cursorId > 0) {
        $batch = abas_installations_for_reconcile_scan($conn, $batchLimit, 0);
    }

    foreach ($batch as $installation) {
        $id = (int) $installation['id'];
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $targets[] = [
            'id' => $id,
            's_ins' => (int) $installation['s_ins'],
            'deal_id' => (string) $installation['deal_id'],
        ];
    }

    return ['targets' => $targets, 'batch' => $batch];
}

/**
 * @param list<string> $errors
 * @return array{
 *   rows: list<array<string, mixed>>,
 *   scanned: int,
 *   checked_installation_ids: array<int, true>,
 *   scan_cursor: int
 * }
 */
function abas_reconcile_scan_installations_testqueue(mysqli $conn, TrekantClient $client, array &$errors): array
{
    if (abas_env('SERVICE_RECONCILE_SCAN', '1') === '0') {
        return ['rows' => [], 'scanned' => 0, 'checked_installation_ids' => [], 'scan_cursor' => 0];
    }

    $batchLimit = max(1, (int) abas_env('SERVICE_RECONCILE_SCAN_LIMIT', '50'));
    $budgetSec = max(10, (int) abas_env('SERVICE_RECONCILE_SCAN_BUDGET_SEC', '85'));
    $deadline = microtime(true) + $budgetSec;
    $cursorId = max(0, (int) abas_setting($conn, 'reconcile_scan_cursor', '0'));

    $plan = abas_reconcile_scan_targets($conn, $batchLimit, $cursorId);
    $targets = $plan['targets'];
    $batch = $plan['batch'];

    $rows = [];
    $scanned = 0;
    $checkedInstallationIds = [];
    $nextCursor = $cursorId;

    foreach ($targets as $installation) {
        if (microtime(true) >= $deadline) {
            break;
        }

        $installationId = (int) $installation['id'];
        $scanned++;
        $checkedInstallationIds[$installationId] = true;

        if (abas_active_session_for_installation($conn, $installationId) !== null) {
            continue;
        }

        $sIns = (int) $installation['s_ins'];
        $dealId = (string) $installation['deal_id'];
        try {
            $resp = $client->getTestQueueStatus($sIns, $dealId, 1);
            if (abas_trekant_return_code($resp) !== 0) {
                continue;
            }
            $testRows = abas_trekant_rows($resp);
            if ($testRows === []) {
                continue;
            }
            $row = $testRows[0];
            if (!is_array($row)) {
                continue;
            }
            $row['s_ins'] = (int) ($row['s_ins'] ?? $sIns);
            $row['deal_id'] = (string) ($row['deal_id'] ?? $dealId);
            $rows[] = $row;
        } catch (Throwable $e) {
            $errors[] = 'scan ' . $sIns . ':' . $dealId . ': ' . $e->getMessage();
        }
    }

    if ($batch !== []) {
        $last = $batch[count($batch) - 1];
        $nextCursor = (int) $last['id'];
        abas_set_setting($conn, 'reconcile_scan_cursor', (string) $nextCursor);
    }

    return [
        'rows' => $rows,
        'scanned' => $scanned,
        'checked_installation_ids' => $checkedInstallationIds,
        'scan_cursor' => $nextCursor,
    ];
}

/**
 * @return array{ok:bool, closed_abas:int, external_found:int, external_cleared:int, queue_rows:int, scanned_installations:int, scan_mode:bool, scan_cursor:int, summary_warning:?string, errors:list<string>, duration_ms:int}
 */
function abas_reconcile_service_testqueue(mysqli $conn): array
{
    $startedAt = microtime(true);
    $client = abas_trekant();
    $userid = strtoupper((string) abas_config()['trekant']['user']);
    $dealId = strtoupper((string) abas_env('TREKANT_DEAL_ID', 'TB'));

    $summaryRows = [];
    $summaryWarning = null;
    $scanMode = false;
    $scanned = 0;
    $scanCursor = max(0, (int) abas_setting($conn, 'reconcile_scan_cursor', '0'));
    $checkedInstallationIds = [];
    try {
        $summaryResp = $client->getTestQueueSummary($userid, $dealId);
        $code = abas_trekant_return_code($summaryResp);
        if (abas_trekant_summary_return_ok($code)) {
            $summaryRows = abas_trekant_rows($summaryResp);
            if ($summaryRows === [] && $code === 15342) {
                $summaryWarning = 'g_ma_testqueue_summary s_ins=0 gav ingen raekker (RC 15342) - scanner anlaeg med g_ma_testqueue';
            }
        } else {
            $summaryWarning = 'g_ma_testqueue_summary returncode ' . $code . ': ' . abas_trekant_response_hint($summaryResp);
        }
    } catch (Throwable $e) {
        $summaryWarning = $e->getMessage();
    }

    $errors = [];
    if ($summaryRows === []) {
        $scan = abas_reconcile_scan_installations_testqueue($conn, $client, $errors);
        $summaryRows = $scan['rows'];
        $scanned = $scan['scanned'];
        $checkedInstallationIds = $scan['checked_installation_ids'];
        $scanCursor = $scan['scan_cursor'];
        $scanMode = $scanned > 0 || abas_env('SERVICE_RECONCILE_SCAN', '1') !== '0';
    }

    $queueBySIns = [];
    foreach ($summaryRows as $row) {
        $identity = abas_queue_row_identity($row);
        if ($identity === null) {
            continue;
        }
        $queueBySIns[$identity['s_ins'] . ':' . $identity['deal_id']] = $row;
    }

    $closedAbas = 0;

    foreach (abas_active_abas_service_sessions($conn) as $session) {
        $sIns = (int) $session['s_ins'];
        $dealId = (string) $session['deal_id'];
        $key = $sIns . ':' . $dealId;
        $inQueue = isset($queueBySIns[$key]);

        if (!$inQueue) {
            try {
                $inQueue = abas_installation_in_testqueue($conn, $sIns, $dealId);
            } catch (Throwable $e) {
                $errors[] = 'testqueue ' . $sIns . ': ' . $e->getMessage();
                continue;
            }
        }

        if (!$inQueue) {
            try {
                if (abas_close_session_ended_externally($conn, $session)) {
                    $closedAbas++;
                }
            } catch (Throwable $e) {
                $errors[] = 'close session ' . ($session['id'] ?? '?') . ': ' . $e->getMessage();
            }
        }
    }

    $externalFound = 0;
    $externalCleared = 0;
    $seenInstallationIds = [];

    foreach ($summaryRows as $row) {
        $identity = abas_queue_row_identity($row);
        if ($identity === null) {
            continue;
        }
        $installation = abas_find_installation_by_s_ins($conn, $identity['s_ins'], $identity['deal_id']);
        if ($installation === null) {
            continue;
        }
        $installationId = (int) $installation['id'];
        $seenInstallationIds[$installationId] = true;

        if (abas_active_session_for_installation($conn, $installationId) !== null) {
            abas_clear_external_testqueue($conn, $installationId);
            continue;
        }

        try {
            abas_upsert_external_testqueue($conn, $installationId, $row);
            $externalFound++;
        } catch (Throwable $e) {
            $errors[] = 'external upsert ' . $installationId . ': ' . $e->getMessage();
        }
    }

    if ($scanMode) {
        foreach (abas_external_testqueue_installations($conn) as $ext) {
            $installationId = (int) $ext['installation_id'];
            if (isset($checkedInstallationIds[$installationId]) && !isset($seenInstallationIds[$installationId])) {
                abas_clear_external_testqueue($conn, $installationId);
                $externalCleared++;
            }
        }
    } elseif ($summaryRows !== []) {
        $existing = abas_external_testqueue_installations($conn);
        foreach ($existing as $ext) {
            $installationId = (int) $ext['installation_id'];
            if (!isset($seenInstallationIds[$installationId])) {
                abas_clear_external_testqueue($conn, $installationId);
                $externalCleared++;
            }
        }
    }

    return [
        'ok' => $errors === [],
        'closed_abas' => $closedAbas,
        'external_found' => $externalFound,
        'external_cleared' => $externalCleared,
        'queue_rows' => count($summaryRows),
        'scanned_installations' => $scanned,
        'scan_mode' => $scanMode,
        'scan_cursor' => $scanCursor,
        'summary_warning' => $summaryWarning,
        'errors' => $errors,
        'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
    ];
}

function abas_handle_reconcile_service_webhook(mysqli $conn): never
{
    require_once __DIR__ . '/api_auth.php';

    if (!abas_reconcile_verify_request()) {
        abas_api_json(403, ['ok' => false, 'error' => abas_reconcile_request_auth_error()]);
    }

    @set_time_limit(150);

    try {
        $result = abas_reconcile_service_testqueue($conn);
        $status = $result['ok'] ? 200 : 500;
        if (!$result['ok'] && $result['summary_warning'] && $result['errors'] === []) {
            $status = 200;
        }
        abas_api_json($status, $result);
    } catch (Throwable $e) {
        abas_api_json(500, ['ok' => false, 'error' => $e->getMessage()]);
    }
}
