<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/service.php';

header('Content-Type: application/json; charset=utf-8');

$conn = abas_db();
$user = abas_require_login();
$id = (int) ($_GET['id'] ?? 0);
$logMode = $_GET['log'] ?? 'last20';
$customRange = null;

if ($logMode === 'custom' && !empty($_GET['from']) && !empty($_GET['to'])) {
    $customRange = [
        'startdate' => substr((string) $_GET['from'], 0, 10),
        'starttime' => '00:00:00',
        'enddate' => substr((string) $_GET['to'], 0, 10),
        'endtime' => '23:59:59',
    ];
}

$stmt = $conn->prepare('SELECT * FROM installations WHERE id=? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$installation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$installation || !abas_user_may_access_installation($conn, $user, $installation)) {
    http_response_code(404);
    echo json_encode(['error' => 'Anlæg ikke fundet.']);
    exit;
}

$session = abas_active_session_for_installation($conn, $id);
$log = ['rows' => [], 'code' => -1];

try {
    $log = abas_fetch_installation_log($installation, $logMode, $customRange);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Log: ' . $e->getMessage()]);
    exit;
}

$sessionLabel = '';
if ($session) {
    $sessionLabel = 'Aktiv siden ' . abas_format_datetime($session['started_at']);
    if (!empty($session['expires_at'])) {
        $sessionLabel .= ' — udløber ' . abas_format_datetime($session['expires_at']);
    }
}

echo json_encode([
    'sessionActive' => $session !== null,
    'sessionLabel' => $sessionLabel,
    'logCode' => (int) $log['code'],
    'logHtml' => $log['code'] === 0 ? abas_render_alarmlog_rows_html($log['rows']) : '',
    'logEmpty' => $log['code'] === 0 && $log['rows'] === [],
], JSON_UNESCAPED_UNICODE);
