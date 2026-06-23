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

    require_once __DIR__ . '/service_notifications.php';
    abas_notify_service_started($conn, $user, $installation, $onBehalfUserId, $sessionId, $unlimited, $hours);

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
    require_once __DIR__ . '/service_notifications.php';

    $client = abas_trekant();
    $sIns = (int) $installation['s_ins'];
    $dealId = (string) $installation['deal_id'];
    $instId = (int) $installation['id'];
    $sessionRow = abas_load_service_session_for_stop($conn, $instId, $sessionId);
    $onBehalfUserId = abas_service_on_behalf_user_id($sessionRow);
    $notifySessionId = $sessionRow ? (int) $sessionRow['id'] : $sessionId;
    $sInc = null;
    if ($sessionRow && isset($sessionRow['s_inc'])) {
        $sInc = (int) $sessionRow['s_inc'] ?: null;
    }
    $resp = $client->stopService($sIns, $dealId, $sInc, $comment !== '' ? $comment : 'ABA Service stop');
    $code = abas_trekant_return_code($resp);
    $userId = (int) $user['id'];
    abas_log_service_action($conn, $userId, $onBehalfUserId, $notifySessionId, $sIns, $dealId, 'stop_service', null, $comment, $source, $code);
    if ($code !== 0 && $code !== 15974) {
        return ['ok' => false, 'code' => $code, 'message' => $resp['message']['message'] ?? 'Stop fejlede'];
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

    foreach ($installations as &$installation) {
        $installation['in_service'] = isset($active[(int) $installation['id']]);
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

    $event = trim((string) ($row['event'] ?? ''));
    if ($event !== '') {
        $add('Hændelse', $event);
    }

    foreach (['zone_text' => 'Zone', 'zone' => 'Zone', 'zone_no' => 'Zone nr.', 'detector' => 'Detektor', 'point' => 'Punkt'] as $key => $label) {
        if (!empty($row[$key])) {
            $add($label, (string) $row[$key]);
        }
    }

    $text = trim((string) ($row['text'] ?? ''));
    if ($text !== '') {
        $add('Tekst', $text);
    }

    foreach (['comm_gen', 'comm', 'comment'] as $commKey) {
        $commText = trim((string) ($row[$commKey] ?? ''));
        if ($commText !== '') {
            $add('Kommentar', $commText);
        }
    }

    $operator = trim((string) ($row['operator'] ?? ''));
    if ($operator !== '') {
        $add('Operatør', $operator);
    }

    if ($parts === []) {
        foreach ($row as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value === '' || in_array($key, ['tm_date', 'tm_time', 'logtime', 'datetime', 's_inc', 's_ins', 'deal_id'], true)) {
                continue;
            }
            $add((string) $key, $value);
        }
    }

    return $parts;
}

function abas_render_alarmlog_rows_html(array $rows): string
{
    if ($rows === []) {
        return '';
    }

    ob_start();
    foreach ($rows as $row) {
        $parts = abas_format_alarmlog_parts($row);
        ?>
        <tr>
            <td class="whitespace-nowrap align-top"><?= htmlspecialchars(abas_format_alarmlog_timestamp($row)) ?></td>
            <td class="align-top">
                <?php if ($parts === []): ?>
                    <span class="text-gray-400">—</span>
                <?php else: ?>
                    <div class="abas-log-entry">
                        <?php foreach ($parts as $part): ?>
                            <?php if ($part['label'] === 'Hændelse'): ?>
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($part['text']) ?></div>
                            <?php else: ?>
                                <div class="abas-log-entry-detail">
                                    <span class="text-gray-500"><?= htmlspecialchars($part['label']) ?>:</span>
                                    <?= htmlspecialchars($part['text']) ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    return (string) ob_get_clean();
}
