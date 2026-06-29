<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/app_log.php';

function abas_api_audit_should_log(string $route): bool
{
    if ($route === '' || $route === 'sms/inbound') {
        return false;
    }

    return !str_starts_with($route, 'cron/');
}

function abas_api_audit_resolve_action(string $route, int $status): string
{
    if ($status >= 400) {
        return 'error';
    }
    if ($route === 'health') {
        return 'health';
    }
    if ($route === 'installations/search') {
        return 'search';
    }
    if (preg_match('#^installations/[^/]+/service$#', $route)) {
        return 'service';
    }
    if (preg_match('#^installations/[^/]+/log$#', $route)) {
        return 'fetch_log';
    }

    return 'request';
}

function abas_api_audit_log(int $status, array $payload = []): void
{
    $route = (string) ($GLOBALS['_abas_api_route'] ?? '');
    if (!abas_api_audit_should_log($route)) {
        return;
    }

    require_once __DIR__ . '/activity_log.php';

    $method = strtoupper((string) ($GLOBALS['_abas_api_method'] ?? 'GET'));
    $token = $GLOBALS['_abas_api_token'] ?? null;
    $actorName = is_array($token) ? 'api:' . (string) ($token['name'] ?? '?') : 'api:ukendt';

    $action = abas_api_audit_resolve_action($route, $status);
    $objectId = null;
    $objectLabel = $route;
    $relatedSIns = null;
    $relatedDealId = null;

    if (preg_match('#^installations/([^/]+)/(service|log)$#', $route, $m)) {
        $misc = strtolower($m[1]);
        $objectId = $misc;
        $installation = $GLOBALS['_abas_api_installation'] ?? null;
        if (is_array($installation)) {
            $object = abas_activity_installation_object(
                (string) ($installation['miscno2'] ?? $misc),
                (string) ($installation['name'] ?? ''),
                (int) ($installation['s_ins'] ?? 0),
                (string) ($installation['deal_id'] ?? '')
            );
            $objectId = $object['id'];
            $objectLabel = $object['label'];
            $relatedSIns = (int) ($installation['s_ins'] ?? 0) ?: null;
            $relatedDealId = (string) ($installation['deal_id'] ?? '') ?: null;
        } else {
            $objectLabel = strtoupper($misc);
        }
    } elseif ($route === 'installations/search') {
        $q = trim((string) ($GLOBALS['_abas_api_search_q'] ?? ''));
        if ($q !== '') {
            $objectLabel = 'Søgning: ' . $q;
        }
    }

    $audit = [
        'method' => $method,
        'route' => $route,
        'status' => $status,
    ];
    $extra = $GLOBALS['_abas_api_audit_extra'] ?? null;
    if (is_array($extra) && $extra !== []) {
        $audit['extra'] = $extra;
    }
    if ($status >= 400) {
        $audit['error'] = (string) ($payload['error'] ?? $payload['message'] ?? 'API-fejl');
    } elseif ($route === 'installations/search' && isset($payload['items']) && is_array($payload['items'])) {
        $audit['result_count'] = count($payload['items']);
    } elseif (preg_match('#/log$#', $route) && isset($payload['items']) && is_array($payload['items'])) {
        $audit['result_count'] = count($payload['items']);
    }

    abas_log_activity(
        abas_db(),
        'api',
        $action,
        null,
        $actorName,
        'api_endpoint',
        $objectId,
        $objectLabel,
        json_encode($audit, JSON_UNESCAPED_UNICODE),
        $relatedSIns,
        $relatedDealId,
        'api',
        abas_activity_client_ip()
    );
}

function abas_api_json(int $status, array $payload): void
{
    abas_api_audit_log($status, $payload);

    if ($status >= 400) {
        $message = (string) ($payload['error'] ?? $payload['message'] ?? 'API-fejl');
        abas_log_error('api', $message, [
            'status' => $status,
            'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'user_id' => (int) ($_SESSION['user_id'] ?? 0),
        ]);
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function abas_api_bearer_token(): ?string
{
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
        return $m[1];
    }

    return null;
}

function abas_api_authenticate(mysqli $conn): array
{
    $raw = abas_api_bearer_token();
    if (!$raw) {
        abas_api_json(401, ['error' => 'Mangler Bearer token']);
    }
    $hash = hash('sha256', $raw);
    $stmt = $conn->prepare('SELECT * FROM api_tokens WHERE token_hash=? AND active=1 LIMIT 1');
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $token = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$token) {
        abas_api_json(401, ['error' => 'Ugyldigt token']);
    }
    if (!empty($token['allowed_ips'])) {
        $allowed = array_map('trim', explode(',', $token['allowed_ips']));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($ip && !in_array($ip, $allowed, true)) {
            abas_api_json(403, ['error' => 'IP ikke tilladt']);
        }
    }

    return $token;
}

function abas_api_user_from_token(mysqli $conn, array $token): array
{
    return [
        'id' => 0,
        'role' => $token['role'],
        'username' => 'api:' . $token['name'],
        'trekant_userid' => abas_config()['trekant']['user'],
    ];
}
