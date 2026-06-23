<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/api_auth.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/service.php';
require_once dirname(__DIR__, 2) . '/includes/installation_sync.php';
require_once dirname(__DIR__, 2) . '/includes/sms.php';

$conn = abas_db();
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['route'] ?? '';
if ($path === '' && isset($_SERVER['PATH_INFO'])) {
    $path = trim((string) $_SERVER['PATH_INFO'], '/');
}

if ($path === 'health' && $method === 'GET') {
    abas_api_json(200, ['status' => 'ok', 'app' => abas_config()['app_name']]);
}

if ($path === 'sms/inbound' && $method === 'POST') {
    abas_sms_verify_inbound_request();
    $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $inbound = abas_sms_parse_inbound_request($body);
    if ($inbound['from'] === '' || $inbound['body'] === '') {
        abas_api_json(400, ['error' => 'from og body/text påkrævet']);
    }
    $reply = abas_sms_handle_inbound($conn, $inbound['from'], $inbound['body']);
    abas_api_json(200, ['reply' => $reply]);
}

$token = abas_api_authenticate($conn);
$apiUser = abas_api_user_from_token($conn, $token);

if ($path === 'installations/search' && $method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $like = '%' . $q . '%';
    $stmt = $conn->prepare('SELECT id, miscno2, name, city, s_ins, deal_id FROM installations WHERE miscno2 LIKE ? OR name LIKE ? LIMIT 20');
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    abas_api_json(200, ['items' => $rows]);
}

if (preg_match('#^installations/([^/]+)/service$#', $path, $m) && $method === 'POST') {
    $_GET['miscno2'] = $m[1];
    $misc = strtolower($m[1]);
    $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $installation = abas_find_installation_by_miscno2($conn, $misc);
    if (!$installation) {
        abas_api_json(404, ['error' => 'Anlæg ikke fundet']);
    }
    $action = $body['action'] ?? 'start';
    if ($action === 'start') {
        $unlimited = !empty($body['unlimited']);
        $hours = $unlimited ? null : (float) ($body['hours'] ?? 2);
        $r = abas_start_service_session($conn, $apiUser, $installation, $hours, $unlimited, null, (string) ($body['comment'] ?? 'API'), 'api');
        abas_api_json($r['ok'] ? 200 : 400, $r);
    }
    $session = abas_active_session_for_installation($conn, (int) $installation['id']);
    $r = abas_stop_service_session($conn, $apiUser, $installation, $session ? (int) $session['id'] : null, (string) ($body['comment'] ?? 'API stop'), 'api');
    abas_api_json($r['ok'] ? 200 : 400, $r);
}

if (preg_match('#^installations/([^/]+)/log$#', $path, $m) && $method === 'GET') {
    $installation = abas_find_installation_by_miscno2($conn, strtolower($m[1]));
    if (!$installation) {
        abas_api_json(404, ['error' => 'Anlæg ikke fundet']);
    }
    $mode = $_GET['mode'] ?? 'last20';
    $log = abas_fetch_installation_log($installation, $mode);
    abas_api_json(200, ['code' => $log['code'], 'items' => $log['rows']]);
}

abas_api_json(404, ['error' => 'Ukendt endpoint', 'path' => $path]);
