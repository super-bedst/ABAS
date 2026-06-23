<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/sms.php';

$conn = abas_db();
$count = abas_sms_send_expiry_warnings($conn);
echo date('c') . " expiry warnings sent: $count\n";
