<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/users.php';

header('Content-Type: application/json; charset=utf-8');

$user = abas_require_login();
abas_require_role(['vagtcentral', 'admin']);

$conn = abas_db();
$type = (string) ($_GET['type'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));

if ($type === 'installations') {
    if (mb_strlen($q) < 2) {
        echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $items = abas_search_installations_local($conn, $q, true, 0);
    if ($items === [] && abas_is_miscno2_query($q)) {
        try {
            $items = abas_search_installations_from_api($conn, $user, $q);
        } catch (Throwable $e) {
            echo json_encode(['items' => [], 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $out = [];
    foreach ($items as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'miscno2' => (string) ($row['miscno2'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'city' => (string) ($row['city'] ?? ''),
        ];
    }

    echo json_encode(['items' => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($type === 'montors') {
    $rows = abas_search_montors($conn, $q, 40);
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'username' => (string) ($row['username'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'company_name' => (string) ($row['company_name'] ?? ''),
        ];
    }

    echo json_encode(['items' => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

abas_json_error(400, 'Ukendt søgetype.', 'http_error', ['type' => $type]);
