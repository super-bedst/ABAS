<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/trekant_client.php';

function abas_upsert_installation(mysqli $conn, array $row): int
{
    $sIns = (int) ($row['s_ins'] ?? $row['insid'] ?? 0);
    $dealId = (string) ($row['deal_id'] ?? '');
    if ($sIns <= 0 || $dealId === '') {
        return 0;
    }
    $insNo = (string) ($row['ins_no'] ?? '');
    $misc = isset($row['miscno2']) ? strtoupper((string) $row['miscno2']) : null;
    $name = (string) ($row['name'] ?? '');
    $addr = trim(($row['address'] ?? '') . ' ' . ($row['address2'] ?? ''));
    $city = (string) ($row['city'] ?? '');
    $mon = (string) ($row['mon_stat'] ?? '');
    $stmt = $conn->prepare(
        'INSERT INTO installations (s_ins, deal_id, ins_no, miscno2, name, address, city, mon_stat, last_synced_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE ins_no=VALUES(ins_no), miscno2=VALUES(miscno2), name=VALUES(name),
         address=VALUES(address), city=VALUES(city), mon_stat=VALUES(mon_stat), last_synced_at=NOW()'
    );
    $stmt->bind_param('isssssss', $sIns, $dealId, $insNo, $misc, $name, $addr, $city, $mon);
    $stmt->execute();
    $id = (int) ($stmt->insert_id ?: 0);
    $stmt->close();
    if ($id === 0) {
        $q = $conn->prepare('SELECT id FROM installations WHERE s_ins = ? AND deal_id = ? LIMIT 1');
        $q->bind_param('is', $sIns, $dealId);
        $q->execute();
        $found = $q->get_result()->fetch_assoc();
        $q->close();
        $id = (int) ($found['id'] ?? 0);
    }

    return $id;
}

function abas_sync_prefix(mysqli $conn, int $prefixId, ?string $trekantUserid = null): array
{
    $stmt = $conn->prepare('SELECT * FROM sync_prefixes WHERE id = ? AND active = 1 LIMIT 1');
    $stmt->bind_param('i', $prefixId);
    $stmt->execute();
    $prefix = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$prefix) {
        throw new RuntimeException('Sync-prefix ikke fundet');
    }
    $userid = $trekantUserid ?: abas_config()['trekant']['user'];
    $userid = strtoupper((string) $userid);
    $client = abas_trekant();
    $runStmt = $conn->prepare(
        'INSERT INTO installation_sync_runs (sync_prefix_id, started_at, status) VALUES (?, NOW(), "running")'
    );
    $runStmt->bind_param('i', $prefixId);
    $runStmt->execute();
    $runId = (int) $runStmt->insert_id;
    $runStmt->close();

    $pfx = strtolower((string) $prefix['prefix']);
    $maxSuffix = (int) $prefix['max_suffix'];
    $batchSize = min(100, max(1, (int) $prefix['batch_size']));
    $batches = 0;
    $received = 0;
    $upserted = 0;
    $errors = [];

    for ($start = 0; $start <= $maxSuffix; $start += $batchSize) {
        $end = min($start + $batchSize - 1, $maxSuffix);
        for ($i = $start; $i <= $end; $i++) {
            $misc = $pfx . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            try {
                $resp = $client->searchInstallations($userid, $misc, null, 100);
                $code = abas_trekant_return_code($resp);
                if ($code !== 0) {
                    continue;
                }
                $rows = abas_trekant_rows($resp);
                $received += count($rows);
                $seen = [];
                foreach ($rows as $row) {
                    $key = ($row['s_ins'] ?? '') . ':' . ($row['deal_id'] ?? '');
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $id = abas_upsert_installation($conn, $row);
                    if ($id > 0) {
                        $upserted++;
                    }
                }
            } catch (Throwable $e) {
                $errors[] = $misc . ': ' . $e->getMessage();
            }
        }
        $batches++;
        usleep(100000);
    }

    $status = $errors === [] ? 'success' : ($upserted > 0 ? 'partial' : 'failed');
    $errMsg = $errors === [] ? null : implode("\n", array_slice($errors, 0, 20));
    $upd = $conn->prepare(
        'UPDATE installation_sync_runs SET finished_at=NOW(), batches_requested=?, rows_received=?, rows_upserted=?, status=?, error_message=? WHERE id=?'
    );
    $upd->bind_param('iiissi', $batches, $received, $upserted, $status, $errMsg, $runId);
    $upd->execute();
    $upd->close();
    $pfxUpd = $conn->prepare('UPDATE sync_prefixes SET last_sync_at=NOW(), last_sync_count=? WHERE id=?');
    $pfxUpd->bind_param('ii', $upserted, $prefixId);
    $pfxUpd->execute();
    $pfxUpd->close();

    return ['run_id' => $runId, 'batches' => $batches, 'received' => $received, 'upserted' => $upserted, 'status' => $status, 'errors' => $errors];
}
