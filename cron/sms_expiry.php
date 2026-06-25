<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/sms.php';

$conn = abas_db();

if (PHP_SAPI !== 'cli') {
    abas_handle_sms_expiry_cron_webhook($conn);
}

$result = abas_sms_run_expiry_warnings($conn);
echo date('c') . ' sms-expiry: ' . json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL;
