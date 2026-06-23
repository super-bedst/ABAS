<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/installation_sync.php';

$conn = abas_db();
$res = $conn->query('SELECT id FROM sync_prefixes WHERE active=1');
$total = 0;
while ($row = $res->fetch_assoc()) {
    try {
        $r = abas_sync_prefix($conn, (int) $row['id']);
        $total += $r['upserted'];
        echo date('c') . " prefix {$row['id']}: {$r['upserted']} upserted\n";
    } catch (Throwable $e) {
        echo date('c') . " prefix {$row['id']} ERROR: {$e->getMessage()}\n";
    }
}
echo date('c') . " total upserted: $total\n";
