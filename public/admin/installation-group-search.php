<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/installation_groups.php';

header('Content-Type: application/json; charset=utf-8');

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$q = trim((string) ($_GET['q'] ?? ''));
$useApi = !empty($_GET['api']);

if ($q === '') {
    echo json_encode(['installations' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$installations = abas_search_installations_for_group_editor($conn, $q, $useApi, $user);

echo json_encode(['installations' => $installations], JSON_UNESCAPED_UNICODE);
