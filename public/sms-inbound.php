<?php

declare(strict_types=1);

/**
 * Alternativ SMS inbound webhook — virker uden mod_rewrite.
 * URL: .../public/sms-inbound.php
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/sms.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    abas_sms_inbound_respond(['ok' => false, 'reply' => 'Kun POST er tilladt']);
}

$conn = abas_db();
abas_handle_sms_inbound_webhook($conn);
