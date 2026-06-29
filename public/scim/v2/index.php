<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 3) . '/includes/db.php';
require_once dirname(__DIR__, 3) . '/includes/scim_server.php';

$conn = abas_db();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$path = trim((string) ($_GET['path'] ?? ''), '/');

abas_scim_handle_request($conn, $method, $path);
