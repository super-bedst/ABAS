<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard_view.php';

header('Content-Type: application/json; charset=utf-8');

$conn = abas_db();
$user = abas_require_login();

$q = trim($_GET['q'] ?? '');
$scope = ($_GET['scope'] ?? 'all') === 'mine' ? 'mine' : 'all';

if ($q !== '') {
    echo json_encode(['skip' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$state = abas_dashboard_build_state($conn, $user, $q, $scope);

echo json_encode([
    'externalHtml' => abas_dashboard_render_external_queue($state),
    'mainHtml' => abas_dashboard_render_main($state),
    'installationCount' => count($state['installations']),
    'externalCount' => count($state['externalInQueue']),
    'updatedAt' => date('c'),
], JSON_UNESCAPED_UNICODE);
