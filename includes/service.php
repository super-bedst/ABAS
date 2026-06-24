<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/trekant_client.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/installation_status.php';

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
    ?int $returnCode,
    bool $responsibilityAck = false
): void {
    $ackAt = $responsibilityAck ? date('Y-m-d H:i:s') : null;
    $stmt = $conn->prepare(
        'INSERT INTO service_actions (user_id, on_behalf_of_user_id, session_id, s_ins, deal_id, action, test_time, comm, responsibility_ack_at, source, trekant_return_code)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iiiissssssi', $userId, $onBehalfId, $sessionId, $sIns, $dealId, $action, $testTime, $comm, $ackAt, $source, $returnCode);
    $stmt->execute();
    $stmt->close();
}

function abas_service_max_hours_per_start(): float
{
    return 8.0;
}

function abas_service_max_consecutive_hours(): float
{
    return 12.0;
}

function abas_service_remaining_extend_hours(?array $session): float
{
    if (!$session) {
        return abas_service_max_hours_per_start();
    }
    $chainStart = strtotime((string) $session['started_at']);
    if ($chainStart === false) {
        return 0.0;
    }
    $maxEndTs = $chainStart + (int) round(abas_service_max_consecutive_hours() * 3600);
    $remaining = ($maxEndTs - time()) / 3600;

    return max(0.0, min(abas_service_max_hours_per_start(), round($remaining, 2)));
}

/**
 * @return array{ok:bool, message?:string, hours?:float, is_extend?:bool, session?:?array, max_end_ts?:int}
 */
function abas_service_resolve_hours(mysqli $conn, array $installation, float $requestedHours): array
{
    $maxPerStart = abas_service_max_hours_per_start();
    $maxConsecutive = abas_service_max_consecutive_hours();
    $activeSession = abas_active_session_for_installation($conn, (int) $installation['id']);

    if ($requestedHours < 0.5) {
        return ['ok' => false, 'message' => 'Varighed skal være mindst 0,5 time.'];
    }
    if ($requestedHours > $maxPerStart) {
        return ['ok' => false, 'message' => 'Maks. ' . (int) $maxPerStart . ' timer ad gangen.'];
    }

    if ($activeSession) {
        $chainStart = strtotime((string) $activeSession['started_at']);
        if ($chainStart === false) {
            return ['ok' => false, 'message' => 'Kunne ikke beregne servicevarighed.'];
        }
        $maxEndTs = $chainStart + (int) round($maxConsecutive * 3600);
        $remainingWindowHours = ($maxEndTs - time()) / 3600;
        if ($remainingWindowHours < 0.5) {
            return [
                'ok' => false,
                'message' => 'Maks. ' . (int) $maxConsecutive . ' sammenhængende timer i service er nået. Stop service først.',
            ];
        }
        $hours = min($requestedHours, $remainingWindowHours);

        return [
            'ok' => true,
            'hours' => round($hours, 2),
            'is_extend' => true,
            'session' => $activeSession,
            'max_end_ts' => $maxEndTs,
        ];
    }

    $hours = min($requestedHours, $maxConsecutive);

    return [
        'ok' => true,
        'hours' => round($hours, 2),
        'is_extend' => false,
        'session' => null,
        'max_end_ts' => time() + (int) round($hours * 3600),
    ];
}

function abas_service_compute_expires_at(array $resolved, float $hours): string
{
    if (!empty($resolved['is_extend']) && !empty($resolved['session'])) {
        $session = $resolved['session'];
        $baseTs = max(time(), strtotime((string) ($session['expires_at'] ?? 'now')) ?: time());
        $candidate = $baseTs + (int) round($hours * 3600);
        $maxEnd = (int) ($resolved['max_end_ts'] ?? $candidate);

        return date('Y-m-d H:i:s', min($candidate, $maxEnd));
    }

    return date('Y-m-d H:i:s', time() + (int) round($hours * 3600));
}

function abas_start_service_session(
    mysqli $conn,
    array $user,
    array $installation,
    float $hours,
    ?int $onBehalfUserId,
    string $comment,
    string $source = 'web',
    bool $responsibilityAck = false
): array {
    if (!abas_installation_allows_service((string) ($installation['mon_stat'] ?? ''))) {
        $label = abas_mon_stat_label((string) ($installation['mon_stat'] ?? ''));

        return ['ok' => false, 'code' => -1, 'message' => 'Anlægget er ' . strtolower($label) . ' og kan ikke sættes i service.'];
    }

    $resolved = abas_service_resolve_hours($conn, $installation, $hours);
    if (!$resolved['ok']) {
        return ['ok' => false, 'code' => -1, 'message' => $resolved['message'] ?? 'Ugyldig varighed.'];
    }
    $hours = (float) $resolved['hours'];
    $isExtend = !empty($resolved['is_extend']);
    $activeSession = $resolved['session'] ?? null;

    $client = abas_trekant();
    $sIns = (int) $installation['s_ins'];
    $dealId = (string) $installation['deal_id'];
    $testTime = abas_format_test_time_hours($hours);
    $comm = $comment !== '' ? abas_enrich_service_start_comment($conn, $user, $comment) : ($isExtend ? 'ABA Service forlængelse' : 'ABA Service start');
    $comm = abas_trekant_trim_comment($comm);
    $resp = $client->startService($sIns, $dealId, $testTime, $comm);
    $code = abas_trekant_return_code($resp);
    $userId = (int) $user['id'];
    $action = $isExtend ? 'extend_service' : 'start_service';
    $sessionIdForLog = $activeSession ? (int) $activeSession['id'] : null;
    abas_log_service_action($conn, $userId, $onBehalfUserId, $sessionIdForLog, $sIns, $dealId, $action, $testTime, $comm, $source, $code, $responsibilityAck);
    if ($code !== 0 && $code !== 15997) {
        return ['ok' => false, 'code' => $code, 'message' => $resp['message']['message'] ?? ($isExtend ? 'Forlængelse fejlede' : 'Start fejlede')];
    }

    $expiresAt = abas_service_compute_expires_at($resolved, $hours);
    $instId = (int) $installation['id'];

    if ($isExtend && $activeSession) {
        $sessionId = (int) $activeSession['id'];
        $chainStart = strtotime((string) $activeSession['started_at']);
        $totalHours = $chainStart !== false
            ? round((strtotime($expiresAt) - $chainStart) / 3600, 2)
            : (float) ($activeSession['duration_hours'] ?? 0) + $hours;
        $stmt = $conn->prepare(
            'UPDATE service_sessions SET expires_at = ?, duration_hours = ?, unlimited = 0, warning_sent_at = NULL WHERE id = ?'
        );
        $stmt->bind_param('sdi', $expiresAt, $totalHours, $sessionId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO service_sessions (user_id, on_behalf_of_user_id, installation_id, started_at, expires_at, duration_hours, unlimited, status, source)
             VALUES (?, ?, ?, NOW(), ?, ?, 0, "active", ?)'
        );
        $stmt->bind_param('iiisds', $userId, $onBehalfUserId, $instId, $expiresAt, $hours, $source);
        $stmt->execute();
        $sessionId = (int) $stmt->insert_id;
        $stmt->close();
    }

    $sInc = abas_trekant_extract_s_inc($resp);
    if ($sInc === null && ($code === 0 || $code === 15997)) {
        $sInc = abas_trekant_active_test_s_inc($client, $sIns, $dealId);
    }
    if ($sInc !== null && $sInc > 0 && $sessionId > 0) {
        $sIncStmt = $conn->prepare('UPDATE service_sessions SET s_inc = ? WHERE id = ? AND (s_inc IS NULL OR s_inc = 0)');
        $sIncStmt->bind_param('ii', $sInc, $sessionId);
        $sIncStmt->execute();
        $sIncStmt->close();
    }

    require_once __DIR__ . '/service_notifications.php';
    abas_notify_service_started($conn, $user, $installation, $onBehalfUserId, $sessionId, false, $hours, $isExtend);

    return ['ok' => true, 'code' => $code, 'session_id' => $sessionId, 'extended' => $isExtend, 'response' => $resp];
}

function abas_stop_service_session(
    mysqli $conn,
    array $user,
    array $installation,
    ?int $sessionId,
    string $comment,
    string $source = 'web'
): array {
    require_once __DIR__ . '/service_notifications.php';

    $client = abas_trekant();
    $sIns = (int) $installation['s_ins'];
    $dealId = (string) $installation['deal_id'];
    $instId = (int) $installation['id'];
    $sessionRow = abas_load_service_session_for_stop($conn, $instId, $sessionId);
    $onBehalfUserId = abas_service_on_behalf_user_id($sessionRow);
    $notifySessionId = $sessionRow ? (int) $sessionRow['id'] : $sessionId;
    $sInc = null;
    if ($sessionRow && !empty($sessionRow['s_inc'])) {
        $sInc = (int) $sessionRow['s_inc'];
    }
    if ($sInc === null || $sInc <= 0) {
        $sInc = abas_trekant_active_test_s_inc($client, $sIns, $dealId);
    }
    $stopComment = abas_trekant_trim_comment($comment !== '' ? $comment : 'ABA Service stop');
    $resp = $client->stopService($sIns, $dealId, $sInc > 0 ? $sInc : null, $stopComment);
    $code = abas_trekant_return_code($resp);
    $userId = (int) $user['id'];
    abas_log_service_action($conn, $userId, $onBehalfUserId, $notifySessionId, $sIns, $dealId, 'stop_service', null, $stopComment, $source, $code);
    if ($code !== 0 && $code !== 15974) {
        return ['ok' => false, 'code' => $code, 'message' => $resp['message']['message'] ?? 'Stop fejlede'];
    }
    if ($stopComment !== '' && $sInc > 0) {
        $addResp = $client->addLogComment($sIns, $dealId, $sInc, $stopComment);
        $addCode = abas_trekant_return_code($addResp);
        if ($addCode !== 0) {
            error_log('ABA addLogComment after stop failed: code ' . $addCode . ' s_inc=' . $sInc);
        }
    }
    if ($notifySessionId) {
        $u = $conn->prepare('UPDATE service_sessions SET ended_at=NOW(), status="ended" WHERE id=?');
        $u->bind_param('i', $notifySessionId);
        $u->execute();
        $u->close();
    } else {
        $u = $conn->prepare('UPDATE service_sessions SET ended_at=NOW(), status="ended" WHERE installation_id=? AND status="active"');
        $u->bind_param('i', $instId);
        $u->execute();
        $u->close();
    }

    abas_notify_service_stopped($conn, $user, $installation, $onBehalfUserId, $notifySessionId);

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
 * @return array<int, true>
 */
function abas_active_service_installation_ids(mysqli $conn, ?array $installationIds = null): array
{
    if ($installationIds !== null && $installationIds === []) {
        return [];
    }

    $sql = 'SELECT installation_id FROM service_sessions WHERE status = "active"';
    if ($installationIds !== null) {
        $ids = array_map('intval', $installationIds);
        $sql .= ' AND installation_id IN (' . implode(',', $ids) . ')';
    }

    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    $active = [];
    while ($row = $result->fetch_assoc()) {
        $active[(int) $row['installation_id']] = true;
    }
    $result->close();

    return $active;
}

/**
 * @return array<int, true>
 */
function abas_external_service_installation_ids(mysqli $conn, ?array $installationIds = null): array
{
    if ($installationIds !== null && $installationIds === []) {
        return [];
    }

    $sql = 'SELECT installation_id FROM installation_external_testqueue';
    if ($installationIds !== null) {
        $ids = array_map('intval', $installationIds);
        $sql .= ' WHERE installation_id IN (' . implode(',', $ids) . ')';
    }

    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    $external = [];
    while ($row = $result->fetch_assoc()) {
        $external[(int) $row['installation_id']] = true;
    }
    $result->close();

    return $external;
}

/**
 * @return list<array<string, mixed>>
 */
function abas_dashboard_in_service_installations(mysqli $conn, array $user, bool $includeCompany = true): array
{
    $role = (string) ($user['role'] ?? '');
    $userId = (int) ($user['id'] ?? 0);
    $installerId = (int) ($user['installer_id'] ?? 0);

    $sql = 'SELECT i.*, ss.id AS session_id, ss.started_at AS service_started_at,
            ss.expires_at AS service_expires_at, ss.unlimited AS service_unlimited,
            ss.user_id AS service_user_id, ss.on_behalf_of_user_id,
            su.username AS service_username, ou.username AS on_behalf_username
            FROM service_sessions ss
            JOIN installations i ON i.id = ss.installation_id
            JOIN users su ON su.id = ss.user_id
            LEFT JOIN users ou ON ou.id = ss.on_behalf_of_user_id
            WHERE ss.status = "active"';

    $types = '';
    $params = [];

    if ($role === 'montor') {
        if ($includeCompany && $installerId > 0) {
            $sql .= ' AND (
                ss.user_id = ? OR ss.on_behalf_of_user_id = ?
                OR ss.user_id IN (SELECT id FROM users WHERE installer_id = ?)
                OR ss.on_behalf_of_user_id IN (SELECT id FROM users WHERE installer_id = ?)
            )';
            $types .= 'iiii';
            array_push($params, $userId, $userId, $installerId, $installerId);
        } else {
            $sql .= ' AND (ss.user_id = ? OR ss.on_behalf_of_user_id = ?)';
            $types .= 'ii';
            array_push($params, $userId, $userId);
        }
    } elseif (!abas_user_can_access_all_installations($role)) {
        return [];
    }

    $sql .= ' ORDER BY ss.started_at DESC';

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as &$row) {
        $isMine = (int) ($row['service_user_id'] ?? 0) === $userId
            || (int) ($row['on_behalf_of_user_id'] ?? 0) === $userId;
        if ($role === 'montor' && !$isMine) {
            $row['service_scope'] = 'company';
        } elseif ($role === 'montor' && $isMine) {
            $row['service_scope'] = 'mine';
        } else {
            $row['service_scope'] = '';
        }
        $row['in_service'] = true;
    }
    unset($row);

    return $rows;
}

/**
 * @param list<array<string, mixed>> $installations
 * @return list<array<string, mixed>>
 */
function abas_flag_installations_in_service(mysqli $conn, array $installations): array
{
    if ($installations === []) {
        return [];
    }

    $ids = array_map(static fn (array $row): int => (int) $row['id'], $installations);
    $active = abas_active_service_installation_ids($conn, $ids);
    $external = abas_external_service_installation_ids($conn, $ids);

    foreach ($installations as &$installation) {
        $installationId = (int) $installation['id'];
        $installation['in_service'] = isset($active[$installationId]) || isset($external[$installationId]);
        $installation['in_external_service'] = !isset($active[$installationId]) && isset($external[$installationId]);
    }
    unset($installation);

    return $installations;
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
    $parts = abas_format_alarmlog_parts($row);
    if ($parts === []) {
        return '';
    }

    return implode(' · ', array_map(static fn (array $part): string => $part['text'], $parts));
}

function abas_alarmlog_field_value(array $row, string $key): string
{
    if (!array_key_exists($key, $row)) {
        return '';
    }

    return trim((string) $row[$key]);
}

function abas_alarmlog_status_label(string $code): string
{
    return match (strtoupper($code)) {
        'UA' => 'Udkald aktivt',
        'UR' => 'Tilbagestillet',
        'UO' => 'Udkald opstået',
        default => strtoupper($code),
    };
}

/**
 * @return array{zone_display?:string, sub?:string, status_code?:string, status_label?:string, extra?:string, raw?:string}
 */
function abas_parse_alarmlog_zone_text(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }
    if (preg_match('/^(\d+)\s+(\d+)\s+([A-Z]{2})(?:\s+(.*))?$/i', $raw, $m)) {
        $code = strtoupper($m[3]);

        return [
            'zone_display' => $m[1],
            'sub' => $m[2],
            'status_code' => $code,
            'status_label' => abas_alarmlog_status_label($code),
            'extra' => trim((string) ($m[4] ?? '')),
        ];
    }

    return ['raw' => $raw];
}

function abas_alarmlog_row_tone(array $row): string
{
    $color = strtoupper(trim((string) ($row['row_color'] ?? '')));
    if ($color === '008000' || $color === '00FF00') {
        return 'ok';
    }
    if (in_array($color, ['FFFF00', 'FFA500', 'FF0000'], true)) {
        return 'warn';
    }

    $event = strtoupper(trim((string) ($row['event'] ?? '')));
    if (str_contains($event, 'ALARM') || str_contains($event, 'FEJL')) {
        return 'warn';
    }
    if (str_contains($event, 'RESTORE') || str_contains($event, 'SYS KOMM')) {
        return 'ok';
    }

    return 'neutral';
}

/**
 * @return list<array{label:string, text:string}>
 */
function abas_format_alarmlog_parts(array $row): array
{
    $parts = [];
    $seen = [];

    $add = static function (string $label, string $text) use (&$parts, &$seen): void {
        $text = trim($text);
        if ($text === '') {
            return;
        }
        $key = strtolower($label . ':' . $text);
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $parts[] = ['label' => $label, 'text' => $text];
    };

    $event = abas_alarmlog_field_value($row, 'event');
    if ($event !== '') {
        $add('Hændelse', $event);
    }

    $text = abas_alarmlog_field_value($row, 'text');
    if ($text !== '') {
        $add('Tekst', $text);
    }

    $zoneNo = abas_alarmlog_field_value($row, 'zone');
    $zoneParsed = abas_parse_alarmlog_zone_text(abas_alarmlog_field_value($row, 'zone_text'));
    if (!empty($zoneParsed['zone_display'])) {
        $add('Zone', $zoneParsed['zone_display']);
    } elseif ($zoneNo !== '' && $zoneNo !== '0') {
        $add('Zone nr.', $zoneNo);
    }
    if ($zoneParsed !== []) {
        if (!empty($zoneParsed['status_label'])) {
            $status = $zoneParsed['status_label'];
            if (!empty($zoneParsed['status_code'])) {
                $status .= ' (' . $zoneParsed['status_code'] . ')';
            }
            $add('Zonestatus', $status);
        }
        if (!empty($zoneParsed['extra'])) {
            $add('Zoneinfo', $zoneParsed['extra']);
        }
        if (!empty($zoneParsed['raw'])) {
            $add('Zone', $zoneParsed['raw']);
        }
    }

    foreach (['area' => 'Område', 'earea' => 'Ekstra område'] as $key => $label) {
        $value = abas_alarmlog_field_value($row, $key);
        if ($value !== '') {
            $add($label, $value);
        }
    }

    foreach (['ecode' => 'Kode', 'type' => 'Type', 'alid' => 'Alarm-id'] as $key => $label) {
        $value = abas_alarmlog_field_value($row, $key);
        if ($value !== '') {
            $add($label, $value);
        }
    }

    $terminal = abas_alarmlog_field_value($row, 'terminal');
    if ($terminal !== '') {
        $add('Terminal', $terminal);
    }

    foreach (['comm_gen', 'comm', 'comment'] as $commKey) {
        $commText = abas_alarmlog_field_value($row, $commKey);
        if ($commText !== '') {
            $add('Kommentar', $commText);
        }
    }

    $operator = abas_alarmlog_field_value($row, 'operator');
    if ($operator !== '') {
        $add('Operatør', $operator);
    }

    if ($parts === []) {
        foreach ($row as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value === '' || in_array($key, ['tm_date', 'tm_time', 'logtime', 'datetime', 's_inc', 's_ins', 'deal_id', 'row_color', 'seq', 'tm', 'ntm'], true)) {
                continue;
            }
            $add((string) $key, $value);
        }
    }

    return $parts;
}

/**
 * @return list<list<array<string, mixed>>>
 */
function abas_group_alarmlog_rows(array $rows): array
{
    $groups = [];
    $order = [];
    foreach ($rows as $row) {
        $key = (string) ($row['tm'] ?? '');
        if ($key === '') {
            $key = trim((string) ($row['tm_date'] ?? '')) . ' ' . trim((string) ($row['tm_time'] ?? ''));
        }
        if ($key === '') {
            $key = 'row:' . count($order);
        }
        if (!isset($groups[$key])) {
            $groups[$key] = [];
            $order[] = $key;
        }
        $groups[$key][] = $row;
    }

    $result = [];
    foreach ($order as $key) {
        $group = $groups[$key];
        usort($group, static function (array $a, array $b): int {
            $tmod = ((int) ($a['tmod'] ?? 0)) <=> ((int) ($b['tmod'] ?? 0));
            if ($tmod !== 0) {
                return $tmod;
            }

            return ((int) ($a['seq'] ?? 0)) <=> ((int) ($b['seq'] ?? 0));
        });
        $result[] = $group;
    }

    return $result;
}

function abas_alarmlog_group_tone(array $group): string
{
    $toneRank = ['warn' => 3, 'ok' => 2, 'neutral' => 1];
    $best = 'neutral';
    foreach ($group as $row) {
        $tone = abas_alarmlog_row_tone($row);
        if (($toneRank[$tone] ?? 0) > ($toneRank[$best] ?? 0)) {
            $best = $tone;
        }
    }

    return $best;
}

function abas_format_alarmlog_compact(array $row, bool $includeEvent = true): string
{
    $bits = [];
    $event = abas_alarmlog_field_value($row, 'event');
    $text = abas_alarmlog_field_value($row, 'text');
    $comm = abas_alarmlog_field_value($row, 'comm_gen');

    if ($includeEvent && $event !== '') {
        $bits[] = $event;
    }

    if ($text !== '') {
        $bits[] = $text;
    } elseif ($comm !== '') {
        $bits[] = $comm;
        $comm = '';
    }

    $zoneNo = abas_alarmlog_field_value($row, 'zone');
    $zoneParsed = abas_parse_alarmlog_zone_text(abas_alarmlog_field_value($row, 'zone_text'));
    $zoneLabel = '';
    if (!empty($zoneParsed['zone_display'])) {
        $zoneLabel = $zoneParsed['zone_display'];
    } elseif ($zoneNo !== '' && $zoneNo !== '0') {
        $zoneLabel = $zoneNo;
    }
    if ($zoneLabel !== '') {
        $bits[] = 'Zone ' . $zoneLabel;
    }
    if (!empty($zoneParsed['status_label'])) {
        $status = $zoneParsed['status_label'];
        if (!empty($zoneParsed['status_code'])) {
            $status .= ' (' . $zoneParsed['status_code'] . ')';
        }
        $bits[] = $status;
    }
    if (!empty($zoneParsed['extra'])) {
        $bits[] = $zoneParsed['extra'];
    }

    $area = abas_alarmlog_field_value($row, 'area');
    if ($area !== '') {
        $bits[] = $area;
    }

    if ($text !== '' && $comm !== '') {
        $bits[] = $comm;
    }

    $terminal = abas_alarmlog_field_value($row, 'terminal');
    if ($terminal !== '') {
        $bits[] = $terminal;
    }

    if ($bits === []) {
        return abas_format_alarmlog_text($row);
    }

    return implode(' · ', $bits);
}

function abas_render_alarmlog_rows_html(array $rows): string
{
    if ($rows === []) {
        return '';
    }

    ob_start();
    foreach (abas_group_alarmlog_rows($rows) as $group) {
        $head = $group[0];
        $tone = abas_alarmlog_group_tone($group);
        ?>
        <tr>
            <td class="whitespace-nowrap align-top"><?= htmlspecialchars(abas_format_alarmlog_timestamp($head)) ?></td>
            <td class="align-top">
                <div class="abas-log-entry abas-log-entry--<?= htmlspecialchars($tone) ?>">
                    <div class="flex gap-2">
                        <span class="abas-log-dot abas-log-dot--<?= htmlspecialchars($tone) ?>" aria-hidden="true"></span>
                        <div class="flex-1 space-y-1 min-w-0">
                            <?php foreach ($group as $index => $row): ?>
                                <?php
                                $summary = abas_format_alarmlog_compact($row, $index > 0);
                                if ($summary === '') {
                                    continue;
                                }
                                ?>
                                <?php if ($index === 0): ?>
                                    <div class="font-medium text-gray-900 break-words"><?= htmlspecialchars($summary) ?></div>
                                <?php else: ?>
                                    <div class="abas-log-subline break-words"><?= htmlspecialchars($summary) ?></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }

    return (string) ob_get_clean();
}
