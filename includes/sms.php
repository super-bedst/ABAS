<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/service.php';

function abas_sms_queue(mysqli $conn, string $to, string $body, string $trigger, ?int $sessionId = null): void
{
    $stmt = $conn->prepare(
        'INSERT INTO sms_outbound_log (to_number, body, trigger_type, session_id, status) VALUES (?, ?, ?, ?, "queued")'
    );
    $stmt->bind_param('sssi', $to, $body, $trigger, $sessionId);
    $stmt->execute();
    $stmt->close();
    $storage = abas_root() . '/storage/sms';
    if (!is_dir($storage)) {
        @mkdir($storage, 0775, true);
    }
    file_put_contents(
        $storage . '/out-' . date('Y-m-d') . '.log',
        sprintf("[%s] TO=%s TRIGGER=%s\n%s\n---\n", date('c'), $to, $trigger, $body),
        FILE_APPEND
    );
}

function abas_sms_parse_inbound(string $body): array
{
    $body = trim($body);
    $parts = preg_split('/\s+/', strtoupper($body)) ?: [];
    $secret = $parts[0] ?? '';
    $misc = isset($parts[1]) ? strtolower($parts[1]) : '';
    $cmd = $parts[2] ?? 'STATUS';
    if ($cmd === 'START' && isset($parts[3])) {
        return ['secret' => $secret, 'miscno2' => $misc, 'command' => 'START', 'hours' => (float) $parts[3]];
    }

    return ['secret' => $secret, 'miscno2' => $misc, 'command' => $cmd];
}

function abas_sms_handle_inbound(mysqli $conn, string $from, string $body): string
{
    $parsed = abas_sms_parse_inbound($body);
    $log = $conn->prepare('INSERT INTO sms_inbound_log (from_number, body, parsed_command) VALUES (?, ?, ?)');
    $cmdStr = json_encode($parsed, JSON_UNESCAPED_UNICODE);
    $log->bind_param('sss', $from, $body, $cmdStr);
    $log->execute();
    $logId = (int) $log->insert_id;
    $log->close();

    $stmt = $conn->prepare('SELECT * FROM users WHERE phone = ? AND active = 1 LIMIT 1');
    $stmt->bind_param('s', $from);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user || empty($user['sms_secret_hash'])) {
        return 'Ukendt afsender eller manglende SMS-hemmelighed.';
    }
    if (!password_verify($parsed['secret'], $user['sms_secret_hash'])) {
        return 'Forkert hemmelighed.';
    }
    $installation = abas_find_installation_by_miscno2($conn, $parsed['miscno2']);
    if (!$installation) {
        return 'Anlæg ikke fundet: ' . $parsed['miscno2'];
    }
    if (!abas_user_may_access_installation($conn, $user, $installation)) {
        return 'Ingen adgang til anlæg.';
    }

    $result = '';
    if ($parsed['command'] === 'START') {
        $hours = (float) ($parsed['hours'] ?? 2);
        $r = abas_start_service_session($conn, $user, $installation, $hours, false, null, 'SMS start', 'sms');
        $result = $r['ok'] ? 'Service startet.' : ($r['message'] ?? 'Start fejlede');
        if ($r['ok'] && !empty($user['phone'])) {
            abas_sms_queue($conn, $user['phone'], 'ABA: Service startet på ' . $installation['miscno2'], 'start_confirm', $r['session_id'] ?? null);
        }
    } elseif ($parsed['command'] === 'STOP') {
        $r = abas_stop_service_session($conn, $user, $installation, null, 'SMS stop', 'sms');
        $result = $r['ok'] ? 'Service stoppet.' : ($r['message'] ?? 'Stop fejlede');
    } else {
        $session = abas_active_session_for_installation($conn, (int) $installation['id']);
        $result = $session ? 'Anlæg i aktiv service.' : 'Anlæg ikke i service.';
    }
    $upd = $conn->prepare('UPDATE sms_inbound_log SET user_id=?, result=? WHERE id=?');
    $uid = (int) $user['id'];
    $upd->bind_param('isi', $uid, $result, $logId);
    $upd->execute();
    $upd->close();

    return $result;
}

function abas_sms_send_expiry_warnings(mysqli $conn): int
{
    $stmt = $conn->query(
        'SELECT ss.*, u.phone, i.miscno2 FROM service_sessions ss
         JOIN users u ON u.id = ss.user_id
         JOIN installations i ON i.id = ss.installation_id
         WHERE ss.status="active" AND ss.unlimited=0 AND ss.warning_sent_at IS NULL
         AND ss.expires_at IS NOT NULL AND ss.expires_at <= DATE_ADD(NOW(), INTERVAL 15 MINUTE)'
    );
    $count = 0;
    while ($row = $stmt->fetch_assoc()) {
        if (empty($row['phone'])) {
            continue;
        }
        abas_sms_queue($conn, $row['phone'], 'ABA: Service på ' . $row['miscno2'] . ' udløber om 15 min.', 'expiry_warning', (int) $row['id']);
        $u = $conn->prepare('UPDATE service_sessions SET warning_sent_at=NOW() WHERE id=?');
        $id = (int) $row['id'];
        $u->bind_param('i', $id);
        $u->execute();
        $u->close();
        $count++;
    }
    $stmt->close();

    return $count;
}
