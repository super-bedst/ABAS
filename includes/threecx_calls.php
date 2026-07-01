<?php

declare(strict_types=1);

require_once __DIR__ . '/users.php';
require_once __DIR__ . '/sms_sender.php';
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/service.php';

function abas_threecx_calls_max(): int
{
    $max = (int) (abas_env('THREECX_CALLS_MAX', '15') ?? '15');

    return max(5, min(50, $max));
}

function abas_threecx_stale_minutes(): int
{
    $mins = (int) (abas_env('THREECX_CALLS_STALE_MINUTES', '3') ?? '3');

    return max(1, min(30, $mins));
}

/** Kun opkald agent har taget vises i VC — kø-opkald (ringing) ignoreres. */
function abas_threecx_accepts_new_call(string $status): bool
{
    return $status === 'connected';
}

/** @return list<string> */
function abas_threecx_caller_roles(): array
{
    return ['montor', 'anlaegsejer', 'anlaegsafprover', 'admin'];
}

function abas_threecx_caller_name_looks_like_number(string $name, string $phone): bool
{
    $name = trim($name);
    if ($name === '') {
        return true;
    }

    $nameDigits = preg_replace('/\D/', '', $name) ?? '';
    $phoneDigits = ltrim(preg_replace('/\D/', '', abas_normalize_phone($phone)) ?? '', '0');
    if ($nameDigits !== '' && $phoneDigits !== '') {
        if ($nameDigits === $phoneDigits) {
            return true;
        }
        if (str_ends_with($phoneDigits, $nameDigits) || str_ends_with($nameDigits, $phoneDigits)) {
            return true;
        }
    }

    return str_replace(' ', '', $name) === str_replace(' ', '', trim($phone));
}

function abas_threecx_match_caller(mysqli $conn, string $phone): ?array
{
    $user = abas_sms_find_user_by_phone($conn, $phone);
    if ($user === null) {
        return null;
    }
    if (!in_array((string) ($user['role'] ?? ''), abas_threecx_caller_roles(), true)) {
        return null;
    }
    if (!(int) ($user['active'] ?? 0)) {
        return null;
    }

    return $user;
}

function abas_threecx_owner_filters_installations(string $role): bool
{
    return in_array($role, ['anlaegsejer', 'anlaegsafprover'], true);
}

/** @param array<string, mixed> $payload */
function abas_threecx_normalize_event(array $payload): array
{
    $event = strtolower(trim((string) ($payload['event'] ?? $payload['status'] ?? 'connected')));
    if (!in_array($event, ['ringing', 'connected', 'ended'], true)) {
        if (in_array($event, ['start', 'incoming', 'callstart', 'ring'], true)) {
            $event = 'ringing';
        } elseif (in_array($event, ['answer', 'answered', 'active'], true)) {
            $event = 'connected';
        } elseif (in_array($event, ['end', 'hangup', 'disconnect', 'completed'], true)) {
            $event = 'ended';
        } else {
            $event = 'connected';
        }
    }

    $callId = trim((string) ($payload['call_id'] ?? $payload['callId'] ?? $payload['id'] ?? ''));
    $callerNumber = abas_normalize_phone(trim((string) (
        $payload['caller_number'] ?? $payload['callerNumber'] ?? $payload['from'] ?? $payload['number'] ?? ''
    )));
    $callerName = trim((string) ($payload['caller_name'] ?? $payload['callerName'] ?? $payload['name'] ?? ''));
    $queueName = trim((string) ($payload['queue'] ?? $payload['queue_name'] ?? $payload['queueName'] ?? ''));
    $did = abas_normalize_phone(trim((string) ($payload['did'] ?? $payload['called_number'] ?? $payload['to'] ?? '')));

    return [
        'event' => $event,
        'call_id' => $callId,
        'caller_number' => $callerNumber,
        'caller_name' => $callerName,
        'queue_name' => $queueName !== '' ? $queueName : null,
        'did' => $did !== '' ? $did : null,
    ];
}

function abas_threecx_expire_stale_calls(mysqli $conn): void
{
    $mins = abas_threecx_stale_minutes();
    $stmt = $conn->prepare(
        'UPDATE threecx_inbound_calls
         SET status = "ended", ended_at = COALESCE(ended_at, NOW())
         WHERE status = "connected"
           AND last_seen_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)'
    );
    $stmt->bind_param('i', $mins);
    $stmt->execute();
    $stmt->close();
}

/** @param array<string, mixed> $event */
function abas_threecx_upsert_call(mysqli $conn, array $event): array
{
    abas_threecx_expire_stale_calls($conn);

    $callId = (string) ($event['call_id'] ?? '');
    $callerNumber = (string) ($event['caller_number'] ?? '');
    if ($callId === '' || $callerNumber === '') {
        return ['ok' => false, 'error' => 'call_id og caller_number er påkrævet.'];
    }

    $status = (string) ($event['event'] ?? 'connected');
    $callerName = (string) ($event['caller_name'] ?? '');
    $queueName = $event['queue_name'] ?? null;
    $did = $event['did'] ?? null;

    $matched = abas_threecx_match_caller($conn, $callerNumber);
    $matchedUserId = $matched ? (int) $matched['id'] : null;
    $matchedRole = $matched ? (string) ($matched['role'] ?? '') : null;

    $existing = $conn->prepare('SELECT id, status FROM threecx_inbound_calls WHERE call_id = ? LIMIT 1');
    $existing->bind_param('s', $callId);
    $existing->execute();
    $row = $existing->get_result()->fetch_assoc();
    $existing->close();

    if ($status === 'ringing') {
        return [
            'ok' => true,
            'skipped' => true,
            'call_id' => $callId,
            'reason' => 'Opkald i kø sendes ikke til ABAS — brug event=connected når agent besvarer.',
        ];
    }

    if ($status === 'ended' && !$row) {
        return [
            'ok' => true,
            'skipped' => true,
            'call_id' => $callId,
            'reason' => 'Opkald fandtes ikke (agent har måske ikke besvaret).',
        ];
    }

    if (!$row && !abas_threecx_accepts_new_call($status)) {
        return ['ok' => false, 'error' => 'Nye opkald skal sendes med event=connected.'];
    }

    if ($row) {
        $endedAt = $status === 'ended' ? date('Y-m-d H:i:s') : null;
        $stmt = $conn->prepare(
            'UPDATE threecx_inbound_calls
             SET caller_number = ?, caller_name = CASE WHEN ? <> "" THEN ? ELSE caller_name END,
                 queue_name = COALESCE(?, queue_name), did = COALESCE(?, did),
                 status = ?, matched_user_id = ?, matched_role = ?,
                 ended_at = CASE WHEN ? = "ended" THEN COALESCE(ended_at, NOW()) ELSE ended_at END
             WHERE call_id = ?'
        );
        $stmt->bind_param(
            'ssssssisss',
            $callerNumber,
            $callerName,
            $callerName,
            $queueName,
            $did,
            $status,
            $matchedUserId,
            $matchedRole,
            $status,
            $callId
        );
        $stmt->execute();
        $stmt->close();
    } else {
        $endedAt = $status === 'ended' ? date('Y-m-d H:i:s') : null;
        $stmt = $conn->prepare(
            'INSERT INTO threecx_inbound_calls
             (call_id, caller_number, caller_name, queue_name, did, status, matched_user_id, matched_role, ended_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $callerNameDb = $callerName !== '' ? $callerName : null;
        $stmt->bind_param(
            'ssssssiss',
            $callId,
            $callerNumber,
            $callerNameDb,
            $queueName,
            $did,
            $status,
            $matchedUserId,
            $matchedRole,
            $endedAt
        );
        $stmt->execute();
        $stmt->close();
    }

    return ['ok' => true, 'call_id' => $callId, 'status' => $status];
}

/** @return list<array<string, mixed>> */
function abas_threecx_list_active_calls(mysqli $conn): array
{
    abas_threecx_expire_stale_calls($conn);

    $limit = abas_threecx_calls_max();
    $stmt = $conn->prepare(
        'SELECT c.*, u.username, u.registration_display_name, u.phone AS user_phone, ai.company_name AS matched_company_name
         FROM threecx_inbound_calls c
         LEFT JOIN users u ON u.id = c.matched_user_id
         LEFT JOIN approved_installers ai ON ai.id = u.installer_id
         WHERE c.status = "connected"
         ORDER BY c.last_seen_at DESC
         LIMIT ?'
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $out = [];
    $activeSessions = abas_load_active_service_sessions_with_phones($conn);
    foreach ($rows as $row) {
        $displayName = '';
        if ((int) ($row['matched_user_id'] ?? 0) > 0) {
            $displayName = abas_user_display_name([
                'username' => (string) ($row['username'] ?? ''),
                'registration_display_name' => (string) ($row['registration_display_name'] ?? ''),
            ]);
        }
        $callerName = (string) ($row['caller_name'] ?? '');
        $callerNumber = (string) ($row['caller_number'] ?? '');
        $matchedRole = (string) ($row['matched_role'] ?? '');

        $out[] = [
            'call_id' => (string) ($row['call_id'] ?? ''),
            'caller_number' => $callerNumber,
            'caller_name' => $callerName,
            'display_name' => $displayName,
            'caller_name_usable' => $callerName !== ''
                && !abas_threecx_caller_name_looks_like_number($callerName, $callerNumber),
            'queue_name' => (string) ($row['queue_name'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'matched_user_id' => (int) ($row['matched_user_id'] ?? 0) ?: null,
            'matched_role' => $matchedRole !== '' ? $matchedRole : null,
            'matched_role_label' => $matchedRole !== '' ? abas_role_label($matchedRole) : null,
            'matched_company_name' => trim((string) ($row['matched_company_name'] ?? '')),
            'matched_phone' => trim((string) ($row['user_phone'] ?? '')),
            'filters_installations' => abas_threecx_owner_filters_installations($matchedRole),
            'last_seen_at' => (string) ($row['last_seen_at'] ?? ''),
            'active_service_sessions' => abas_match_active_service_sessions_for_phone($activeSessions, $callerNumber),
        ];
    }

    return $out;
}

function abas_threecx_handle_webhook(mysqli $conn): never
{
    require_once __DIR__ . '/api_auth.php';
    $GLOBALS['_abas_api_route'] = '3cx/call';
    $GLOBALS['_abas_api_method'] = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'POST'));
    $token = abas_api_authenticate($conn);
    $GLOBALS['_abas_api_token'] = $token;

    $raw = file_get_contents('php://input');
    $payload = [];
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        }
    }
    if ($payload === [] && $_POST !== []) {
        $payload = $_POST;
    }
    if ($payload === [] && $_GET !== []) {
        $payload = $_GET;
        unset($payload['route'], $payload['key']);
    }

    $event = abas_threecx_normalize_event($payload);
    $result = abas_threecx_upsert_call($conn, $event);

    abas_api_audit_log($result['ok'] ? 200 : 400, $result);

    http_response_code($result['ok'] ? 200 : 400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
