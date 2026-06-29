<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 3) . '/includes/db.php';
require_once dirname(__DIR__, 3) . '/includes/api_auth.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';
require_once dirname(__DIR__, 3) . '/includes/service.php';
require_once dirname(__DIR__, 3) . '/includes/installation_sync.php';
require_once dirname(__DIR__, 3) . '/includes/sms.php';

$conn = abas_db();
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['route'] ?? '';
if ($path === '' && isset($_SERVER['PATH_INFO'])) {
    $path = trim((string) $_SERVER['PATH_INFO'], '/');
}
$GLOBALS['_abas_api_route'] = $path;
$GLOBALS['_abas_api_method'] = $method;
unset($GLOBALS['_abas_api_token'], $GLOBALS['_abas_api_installation'], $GLOBALS['_abas_api_audit_extra'], $GLOBALS['_abas_api_search_q']);

if ($path === 'health' && $method === 'GET') {
    abas_api_json(200, ['status' => 'ok', 'app' => abas_config()['app_name']]);
}

if ($path === 'sms/inbound') {
    if ($method === 'POST') {
        abas_handle_sms_inbound_webhook($conn);
    }
    abas_api_json(405, ['error' => 'Kun POST understøttes']);
}

if ($path === 'cron/sync-installations' && in_array($method, ['GET', 'POST'], true)) {
    abas_handle_sync_cron_webhook($conn);
}

if ($path === 'cron/reconcile-service' && in_array($method, ['GET', 'POST'], true)) {
    require_once dirname(__DIR__, 3) . '/includes/service_reconcile.php';
    abas_handle_reconcile_service_webhook($conn);
}

if ($path === 'cron/sms-expiry-warnings' && in_array($method, ['GET', 'POST'], true)) {
    abas_handle_sms_expiry_cron_webhook($conn);
}

$token = abas_api_authenticate($conn);
$apiUser = abas_api_user_from_token($conn, $token);
$GLOBALS['_abas_api_token'] = $token;

if ($path === 'installations/search' && $method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $GLOBALS['_abas_api_search_q'] = $q;
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
    $GLOBALS['_abas_api_installation'] = $installation;
    if (!$installation) {
        abas_api_json(404, ['error' => 'Anlæg ikke fundet']);
    }
    $action = $body['action'] ?? 'start';
    $GLOBALS['_abas_api_audit_extra'] = ['service_action' => $action];
    if ($action === 'start') {
        $hours = (float) ($body['hours'] ?? 2);
        $r = abas_start_service_session($conn, $apiUser, $installation, $hours, null, (string) ($body['comment'] ?? 'API'), 'api');
        abas_api_json($r['ok'] ? 200 : 400, $r);
    }
    $session = abas_active_session_for_installation($conn, (int) $installation['id']);
    $r = abas_stop_service_session($conn, $apiUser, $installation, $session ? (int) $session['id'] : null, (string) ($body['comment'] ?? 'API stop'), 'api');
    abas_api_json($r['ok'] ? 200 : 400, $r);
}

if (preg_match('#^installations/([^/]+)/log$#', $path, $m) && $method === 'GET') {
    $installation = abas_find_installation_by_miscno2($conn, strtolower($m[1]));
    $GLOBALS['_abas_api_installation'] = $installation;
    if (!$installation) {
        abas_api_json(404, ['error' => 'Anlæg ikke fundet']);
    }
    $mode = $_GET['mode'] ?? 'last20';
    $GLOBALS['_abas_api_audit_extra'] = ['log_mode' => $mode];
    $log = abas_fetch_installation_log($installation, $mode, null, $apiUser);
    abas_api_json(200, ['code' => $log['code'], 'items' => $log['rows']]);
}

abas_api_json(404, ['error' => 'Ukendt endpoint', 'path' => $path]);
