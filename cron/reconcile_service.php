<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/service_reconcile.php';

$conn = abas_db();
$result = abas_reconcile_service_testqueue($conn);
echo date('c') . ' reconcile: ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
