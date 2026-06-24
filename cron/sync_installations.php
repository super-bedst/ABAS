<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/installation_sync.php';

$conn = abas_db();

if (PHP_SAPI !== 'cli') {
    abas_handle_sync_cron_webhook($conn);
}

$result = abas_sync_all_active($conn);
echo date('c') . ' sync: ' . (int) $result['total_upserted'] . ' upserted in ' . (int) $result['duration_ms'] . "ms\n";
foreach ($result['prefixes'] as $pfx) {
    if (($pfx['status'] ?? '') === 'failed') {
        echo date('c') . " prefix {$pfx['prefix']}: ERROR {$pfx['error']}\n";
        continue;
    }
    echo date('c') . " prefix {$pfx['prefix']}: {$pfx['upserted']} upserted ({$pfx['batches']} batches)\n";
}
