<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/installation_links.php';

header('Content-Type: application/json; charset=utf-8');

$user = abas_require_login();
$conn = abas_db();
$installationId = (int) ($_GET['installation_id'] ?? 0);

if ($installationId <= 0) {
    echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare('SELECT * FROM installations WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $installationId);
$stmt->execute();
$installation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$installation || !abas_user_may_access_installation($conn, $user, $installation)) {
    abas_json_error(403, 'Ingen adgang til anlægget.', 'forbidden', ['installation_id' => $installationId]);
}

echo json_encode(
    ['items' => abas_linked_installation_service_options($conn, $installationId)],
    JSON_UNESCAPED_UNICODE
);
