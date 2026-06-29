<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/service.php';
require_once __DIR__ . '/../includes/trekant_client.php';
require_once __DIR__ . '/../includes/service_reconcile.php';
require_once __DIR__ . '/../includes/installation_details.php';
require_once __DIR__ . '/../includes/installation_status.php';

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
    abas_json_error(404, 'Anlægget findes ikke, eller du har ikke adgang til det.', 'not_found', ['installation_id' => $id]);
}

$session = abas_active_session_for_installation($conn, $id);
try {
    abas_sync_installation_testqueue_status($conn, abas_trekant(), $installation);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Status: ' . $e->getMessage()]);
    exit;
}
$externalTest = abas_external_testqueue_for_installation($conn, $id);
$instDetails = abas_fetch_installation_details($installation, $user);
$log = ['rows' => [], 'code' => -1];

try {
    $log = abas_fetch_installation_log($installation, $logMode, $customRange, $user);
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

$externalLabel = '';
if ($externalTest && !$session) {
    $externalLabel = trim((string) ($externalTest['queue_comment'] ?? ''));
    if ($externalLabel === '') {
        $externalLabel = 'Ekstern testkø';
    }
}

echo json_encode([
    'sessionActive' => $session !== null,
    'externalActive' => $externalTest !== null && $session === null,
    'serviceViewChanged' => false,
    'sessionLabel' => $sessionLabel,
    'externalLabel' => $externalLabel,
    'logCode' => (int) $log['code'],
    'logHtml' => $log['code'] === 0 ? abas_render_alarmlog_rows_html($log['rows']) : '',
    'logEmpty' => $log['code'] === 0 && $log['rows'] === [],
    'zonesHtml' => abas_render_installation_zones_html($instDetails['zones'], $instDetails['zones_error']),
    'contactsHtml' => abas_render_installation_contacts_html($instDetails['contacts'], $instDetails['error']),
    'mapLat' => $instDetails['lat'],
    'mapLon' => $instDetails['lon'],
    'alid' => trim((string) ($instDetails['alid'] ?? '')),
    'updatedAt' => date('c'),
], JSON_UNESCAPED_UNICODE);
