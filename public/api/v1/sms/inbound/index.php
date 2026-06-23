<?php

declare(strict_types=1);

/**
 * Direkte SMS inbound webhook (uden mod_rewrite).
 * URL: .../public/api/v1/sms/inbound/index.php
 */
require_once dirname(__DIR__, 5) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 5) . '/includes/db.php';
require_once dirname(__DIR__, 5) . '/includes/sms.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    require_once dirname(__DIR__, 5) . '/includes/api_auth.php';
    abas_api_json(405, ['error' => 'Kun POST er tilladt']);
}

$conn = abas_db();
abas_handle_sms_inbound_webhook($conn);
