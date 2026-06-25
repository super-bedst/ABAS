<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard_view.php';
require_once __DIR__ . '/../includes/table_list.php';

header('Content-Type: application/json; charset=utf-8');

$conn = abas_db();
$user = abas_require_login();
abas_session_release();

$q = trim($_GET['q'] ?? '');
$scope = ($_GET['scope'] ?? 'all') === 'mine' ? 'mine' : 'all';
$instSort = abas_table_resolve_sort((string) ($_GET['sort'] ?? ''), ['miscno2', 'name', 'city', 'service', 'expires', 'comment'], 'miscno2');
$instDir = abas_table_normalize_sort_dir((string) ($_GET['dir'] ?? 'asc'));

if ($q !== '') {
    echo json_encode(['skip' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$state = abas_dashboard_build_state($conn, $user, $q, $scope);
$state['installations'] = abas_table_sort_installations($state['installations'], $instSort, $instDir);
$state['externalInQueue'] = abas_table_sort_installations($state['externalInQueue'], $instSort, $instDir);
$state['tableSort'] = $instSort;
$state['tableSortDir'] = $instDir;
$state['tableQuery'] = array_filter([
    'q' => $q !== '' ? $q : null,
    'scope' => $scope !== 'all' ? $scope : null,
    'sort' => $instSort !== 'miscno2' ? $instSort : null,
    'dir' => $instDir !== 'asc' ? $instDir : null,
]);

echo json_encode([
    'externalHtml' => abas_dashboard_render_external_queue($state),
    'mainHtml' => abas_dashboard_render_main($state),
    'installationCount' => count($state['installations']),
    'externalCount' => count($state['externalInQueue']),
    'updatedAt' => date('c'),
], JSON_UNESCAPED_UNICODE);
