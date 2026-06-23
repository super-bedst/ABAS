<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/trekant_client.php';
require_once __DIR__ . '/users.php';

function abas_log_service_action(
    mysqli $conn,
    int $userId,
    ?int $onBehalfId,
    ?int $sessionId,
    int $sIns,
    string $dealId,
    string $action,
    ?string $testTime,
    ?string $comm,
    string $source,
    ?int $returnCode
): void {
    $stmt = $conn->prepare(
        'INSERT INTO service_actions (user_id, on_behalf_of_user_id, session_id, s_ins, deal_id, action, test_time, comm, source, trekant_return_code)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iiiisssssi', $userId, $onBehalfId, $sessionId, $sIns, $dealId, $action, $testTime, $comm, $source, $returnCode);
    $stmt->execute();
    $stmt->close();
}

function abas_start_service_session(
    mysqli $conn,
    array $user,
    array $installation,
    ?float $hours,
    bool $unlimited,
    ?int $onBehalfUserId,
    string $comment,
    string $source = 'web'
): array {
    $client = abas_trekant();
    $sIns = (int) $installation['s_ins'];
    $dealId = (string) $installation['deal_id'];
    $testTime = $unlimited ? abas_unlimited_test_time() : abas_format_test_time_hours((float) $hours);
    $comm = $comment !== '' ? abas_enrich_service_start_comment($conn, $user, $comment) : 'ABA Service start';
    $resp = $client->startService($sIns, $dealId, $testTime, $comm);
    $code = abas_trekant_return_code($resp);
    $userId = (int) $user['id'];
    abas_log_service_action($conn, $userId, $onBehalfUserId, null, $sIns, $dealId, 'start_service', $testTime, $comm, $source, $code);
    if ($code !== 0 && $code !== 15997) {
        return ['ok' => false, 'code' => $code, 'message' => $resp['message']['message'] ?? 'Start fejlede'];
    }
    $expiresAt = null;
    if (!$unlimited && $hours !== null) {
        $expiresAt = date('Y-m-d H:i:s', time() + (int) round($hours * 3600));
    }
    $instId = (int) $installation['id'];
    $unl = $unlimited ? 1 : 0;
    $dur = $unlimited ? null : $hours;
    $stmt = $conn->prepare(
        'INSERT INTO service_sessions (user_id, on_behalf_of_user_id, installation_id, started_at, expires_at, duration_hours, unlimited, status, source)
         VALUES (?, ?, ?, NOW(), ?, ?, ?, "active", ?)'
    );
    $stmt->bind_param('iiisdis', $userId, $onBehalfUserId, $instId, $expiresAt, $dur, $unl, $source);
    $stmt->execute();
    $sessionId = (int) $stmt->insert_id;
    $stmt->close();

    return ['ok' => true, 'code' => $code, 'session_id' => $sessionId, 'response' => $resp];
}

function abas_stop_service_session(
    mysqli $conn,
    array $user,
    array $installation,
    ?int $sessionId,
    string $comment,
    string $source = 'web'
): array {
    $client = abas_trekant();
    $sIns = (int) $installation['s_ins'];
    $dealId = (string) $installation['deal_id'];
    $sInc = null;
    if ($sessionId) {
        $q = $conn->prepare('SELECT s_inc FROM service_sessions WHERE id = ? LIMIT 1');
        $q->bind_param('i', $sessionId);
        $q->execute();
        $row = $q->get_result()->fetch_assoc();
        $q->close();
        $sInc = isset($row['s_inc']) ? (int) $row['s_inc'] : null;
    }
    $resp = $client->stopService($sIns, $dealId, $sInc, $comment !== '' ? $comment : 'ABA Service stop');
    $code = abas_trekant_return_code($resp);
    $userId = (int) $user['id'];
    abas_log_service_action($conn, $userId, null, $sessionId, $sIns, $dealId, 'stop_service', null, $comment, $source, $code);
    if ($code !== 0 && $code !== 15974) {
        return ['ok' => false, 'code' => $code, 'message' => $resp['message']['message'] ?? 'Stop fejlede'];
    }
    if ($sessionId) {
        $u = $conn->prepare('UPDATE service_sessions SET ended_at=NOW(), status="ended" WHERE id=?');
        $u->bind_param('i', $sessionId);
        $u->execute();
        $u->close();
    } else {
        $instId = (int) $installation['id'];
        $u = $conn->prepare('UPDATE service_sessions SET ended_at=NOW(), status="ended" WHERE installation_id=? AND status="active"');
        $u->bind_param('i', $instId);
        $u->execute();
        $u->close();
    }

    return ['ok' => true, 'code' => $code, 'response' => $resp];
}

function abas_active_session_for_installation(mysqli $conn, int $installationId): ?array
{
    $stmt = $conn->prepare('SELECT * FROM service_sessions WHERE installation_id=? AND status="active" ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $installationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * @return array<int, list<array{installation_id:int, miscno2:string, in_service:bool}>>
 */
function abas_user_installations_with_service_status(mysqli $conn): array
{
    $result = $conn->query(
        'SELECT ui.user_id, i.id AS installation_id, i.miscno2,
                EXISTS(
                    SELECT 1 FROM service_sessions ss
                    WHERE ss.installation_id = i.id AND ss.status = "active"
                    LIMIT 1
                ) AS in_service
         FROM user_installations ui
         JOIN installations i ON i.id = ui.installation_id
         ORDER BY ui.user_id, i.miscno2'
    );
    if (!$result) {
        return [];
    }

    $grouped = [];
    while ($row = $result->fetch_assoc()) {
        $userId = (int) $row['user_id'];
        $grouped[$userId][] = [
            'installation_id' => (int) $row['installation_id'],
            'miscno2' => (string) $row['miscno2'],
            'in_service' => (bool) $row['in_service'],
        ];
    }
    $result->close();

    return $grouped;
}

function abas_fetch_installation_log(array $installation, string $mode, ?array $customRange = null): array
{
    $client = abas_trekant();
    $sIns = (int) $installation['s_ins'];
    $dealId = (string) $installation['deal_id'];
    $range = null;
    $lines = 20;
    if ($mode === 'last20') {
        $lines = 20;
    } elseif ($mode === '24h') {
        $lines = 500;
        $range = [
            'startdate' => date('Y-m-d', strtotime('-24 hours')),
            'starttime' => date('H:i:s', strtotime('-24 hours')),
            'enddate' => date('Y-m-d'),
            'endtime' => date('H:i:s'),
        ];
    } elseif ($mode === 'custom' && $customRange) {
        $lines = 1000;
        $range = $customRange;
    }
    $resp = $client->getAlarmLog($sIns, $dealId, $lines, $range);

    return ['code' => abas_trekant_return_code($resp), 'rows' => abas_trekant_rows($resp), 'raw' => $resp];
}

function abas_format_alarmlog_timestamp(array $row): string
{
    $date = trim((string) ($row['tm_date'] ?? ''));
    $time = trim((string) ($row['tm_time'] ?? ''));
    if ($date !== '' && $time !== '') {
        $ts = strtotime($date . ' ' . $time);

        return $ts !== false ? date('d/m/Y H:i:s', $ts) : $date . ' ' . $time;
    }
    if ($date !== '') {
        $ts = strtotime($date);

        return $ts !== false ? date('d/m/Y', $ts) : $date;
    }
    $fallback = trim((string) ($row['logtime'] ?? $row['datetime'] ?? ''));
    if ($fallback === '') {
        return '';
    }
    $ts = strtotime($fallback);

    return $ts !== false ? date('d/m/Y H:i:s', $ts) : $fallback;
}

function abas_format_alarmlog_text(array $row): string
{
    foreach (['text', 'zone_text', 'event', 'comm_gen', 'comm'] as $key) {
        if (!isset($row[$key])) {
            continue;
        }
        $val = trim((string) $row[$key]);
        if ($val !== '') {
            return $val;
        }
    }

    return '';
}
