<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/sms.php';

$conn = abas_db();
$sent = abas_sms_flush_queued($conn);
echo date('c') . " sms outbound dispatched: $sent\n";
