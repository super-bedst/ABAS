<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/sms_sender.php';

function abas_sms_queue(mysqli $conn, string $to, string $body, string $trigger, ?int $sessionId = null): int
{
    $stmt = $conn->prepare(
        'INSERT INTO sms_outbound_log (to_number, body, trigger_type, session_id, status) VALUES (?, ?, ?, ?, "queued")'
    );
    $stmt->bind_param('sssi', $to, $body, $trigger, $sessionId);
    $stmt->execute();
    $logId = (int) $stmt->insert_id;
    $stmt->close();

    $storage = abas_root() . '/storage/sms';
    if (!is_dir($storage)) {
        @mkdir($storage, 0775, true);
    }
    file_put_contents(
        $storage . '/out-' . date('Y-m-d') . '.log',
        sprintf("[%s] TO=%s TRIGGER=%s ID=%d\n%s\n---\n", date('c'), $to, $trigger, $logId, $body),
        FILE_APPEND
    );

    abas_sms_dispatch($conn, $logId);

    return $logId;
}

function abas_sms_dispatch(mysqli $conn, int $logId): bool
{
    $stmt = $conn->prepare('SELECT * FROM sms_outbound_log WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $logId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || ($row['status'] ?? '') !== 'queued') {
        return false;
    }

    $result = abas_sms_send_via_bas((string) $row['to_number'], (string) $row['body'], (string) $row['trigger_type']);
    if (!empty($result['skipped'])) {
        return false;
    }

    $status = !empty($result['ok']) ? 'sent' : 'failed';
    $upd = $conn->prepare('UPDATE sms_outbound_log SET status = ? WHERE id = ?');
    $upd->bind_param('si', $status, $logId);
    $upd->execute();
    $upd->close();

    return !empty($result['ok']);
}

function abas_sms_flush_queued(mysqli $conn, int $limit = 50): int
{
    $stmt = $conn->prepare('SELECT id FROM sms_outbound_log WHERE status = "queued" ORDER BY id ASC LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $count = 0;
    while ($row = $res->fetch_assoc()) {
        if (abas_sms_dispatch($conn, (int) $row['id'])) {
            $count++;
        }
    }
    $stmt->close();

    return $count;
}

function abas_sms_inbound_log_path(): string
{
    return abas_root() . '/storage/sms/inbound-last20.log';
}

/**
 * @return list<string>
 */
function abas_sms_read_inbound_webhook_log(): array
{
    $file = abas_sms_inbound_log_path();
    if (!is_file($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
    $lines = array_values(array_filter($lines, static fn (string $row): bool => $row !== ''));

    return array_reverse($lines);
}

function abas_sms_log_inbound_webhook(string $rawBody): void
{
    $storage = abas_root() . '/storage/sms';
    if (!is_dir($storage)) {
        @mkdir($storage, 0775, true);
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '-');
    $payload = trim(preg_replace('/\s+/', ' ', $rawBody) ?? '');
    if ($payload === '') {
        $payload = '(tom body)';
    }

    $line = sprintf('[%s] IP=%s %s', date('c'), $ip, $payload);

    $file = abas_sms_inbound_log_path();
    $lines = [];
    if (is_file($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $lines = array_values(array_filter($lines, static fn (string $row): bool => $row !== ''));
    }
    $lines[] = $line;
    if (count($lines) > 20) {
        $lines = array_slice($lines, -20);
    }

    file_put_contents($file, implode("\n", $lines) . "\n");
}

function abas_sms_inbound_respond(array $payload): never
{
    require_once __DIR__ . '/api_auth.php';
    abas_api_json(200, $payload);
}

function abas_handle_sms_inbound_webhook(mysqli $conn): never
{
    $raw = (string) file_get_contents('php://input');
    abas_sms_log_inbound_webhook($raw);

    if (!abas_sms_verify_inbound_request()) {
        abas_sms_inbound_respond(['ok' => false, 'reply' => 'Ugyldig webhook-nøgle']);
    }

    $body = json_decode($raw, true);
    if (!is_array($body)) {
        abas_sms_inbound_respond(['ok' => false, 'reply' => 'Ugyldig JSON']);
    }

    $inbound = abas_sms_parse_inbound_request($body);
    if ($inbound['from'] === '' || $inbound['body'] === '') {
        abas_sms_inbound_respond(['ok' => false, 'reply' => 'Mangler from eller body']);
    }

    try {
        $reply = abas_sms_handle_inbound($conn, $inbound['from'], $inbound['body']);
        abas_sms_inbound_respond(['ok' => true, 'reply' => $reply]);
    } catch (Throwable $e) {
        abas_sms_inbound_respond(['ok' => false, 'reply' => 'Intern fejl']);
    }
}

function abas_sms_verify_inbound_request(): bool
{
    $secret = abas_config()['sms']['inbound_secret'];
    if ($secret === '') {
        return true;
    }

    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $bearer = null;
    if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
        $bearer = $m[1];
    }
    $key = trim((string) ($_GET['key'] ?? $_POST['key'] ?? ''));

    return $bearer === $secret || $key === $secret;
}

/**
 * @return array{from:string, body:string}
 */
function abas_sms_parse_inbound_request(array $body): array
{
    if (isset($body['from']) && isset($body['body'])) {
        return ['from' => (string) $body['from'], 'body' => (string) $body['body']];
    }
    if (isset($body['from']) && isset($body['text'])) {
        return ['from' => (string) $body['from'], 'body' => (string) $body['text']];
    }
    if (isset($body['messages'][0]) && is_array($body['messages'][0])) {
        $msg = $body['messages'][0];

        return [
            'from' => (string) ($msg['from'] ?? $msg['msisdn'] ?? $msg['phoneNumber'] ?? ''),
            'body' => (string) ($msg['text'] ?? $msg['body'] ?? $msg['message'] ?? ''),
        ];
    }

    return ['from' => '', 'body' => ''];
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

function abas_sms_find_user_by_phone(mysqli $conn, string $from): ?array
{
    $stmt = $conn->query('SELECT * FROM users WHERE active = 1 AND phone IS NOT NULL AND phone <> ""');
    if (!$stmt) {
        return null;
    }
    while ($row = $stmt->fetch_assoc()) {
        if (abas_sms_phones_match($from, (string) $row['phone'])) {
            $stmt->close();

            return $row;
        }
    }
    $stmt->close();

    return null;
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

    $user = abas_sms_find_user_by_phone($conn, $from);
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
        $result = $r['ok'] ? 'ABA: Service startet på ' . $installation['miscno2'] . '.' : ($r['message'] ?? 'Start fejlede');
    } elseif ($parsed['command'] === 'STOP') {
        $r = abas_stop_service_session($conn, $user, $installation, null, 'SMS stop', 'sms');
        $result = $r['ok'] ? 'ABA: Service stoppet på ' . $installation['miscno2'] . '.' : ($r['message'] ?? 'Stop fejlede');
    } else {
        $session = abas_active_session_for_installation($conn, (int) $installation['id']);
        $result = $session ? 'ABA: Anlæg ' . $installation['miscno2'] . ' er i aktiv service.' : 'ABA: Anlæg ikke i service.';
    }

    $upd = $conn->prepare('UPDATE sms_inbound_log SET user_id=?, result=? WHERE id=?');
    $uid = (int) $user['id'];
    $upd->bind_param('isi', $uid, $result, $logId);
    $upd->execute();
    $upd->close();

    if (abas_config()['sms']['send_replies'] && $from !== '' && !in_array($parsed['command'], ['START', 'STOP'], true)) {
        abas_sms_queue($conn, $from, $result, 'inbound_reply');
    }

    return $result;
}

function abas_sms_send_expiry_warnings(mysqli $conn): int
{
    $stmt = $conn->query(
        'SELECT ss.*, COALESCE(NULLIF(ou.phone, ""), u.phone) AS phone, i.miscno2 FROM service_sessions ss
         JOIN users u ON u.id = ss.user_id
         LEFT JOIN users ou ON ou.id = ss.on_behalf_of_user_id
         JOIN installations i ON i.id = ss.installation_id
         WHERE ss.status="active" AND ss.unlimited=0 AND ss.warning_sent_at IS NULL
         AND ss.expires_at IS NOT NULL AND ss.expires_at <= DATE_ADD(NOW(), INTERVAL 15 MINUTE)'
    );
    $count = 0;
    while ($row = $stmt->fetch_assoc()) {
        if (empty($row['phone'])) {
            continue;
        }
        abas_sms_queue($conn, (string) $row['phone'], 'ABA: Service på ' . $row['miscno2'] . ' udløber om 15 min.', 'expiry_warning', (int) $row['id']);
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
