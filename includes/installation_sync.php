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
    $misc = isset($row['miscno2']) && (string) $row['miscno2'] !== ''
        ? strtoupper((string) $row['miscno2'])
        : '';
    $name = (string) ($row['name'] ?? $row['namecom'] ?? '');
    $addr = trim(
        trim((string) ($row['address'] ?? $row['street1'] ?? ''))
        . ' '
        . trim((string) ($row['address2'] ?? $row['street2'] ?? ''))
    );
    $city = trim((string) ($row['city'] ?? ''));
    $mon = trim((string) ($row['mon_stat'] ?? ''));
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

function abas_search_installations_local(mysqli $conn, string $q, bool $allAccess, int $userId = 0): array
{
    $like = '%' . $q . '%';
    if ($allAccess) {
        $stmt = $conn->prepare(
            'SELECT * FROM installations WHERE miscno2 LIKE ? OR name LIKE ? OR ins_no LIKE ? OR city LIKE ?
             ORDER BY miscno2 LIMIT 50'
        );
        $stmt->bind_param('ssss', $like, $like, $like, $like);
    } else {
        $stmt = $conn->prepare(
            'SELECT i.* FROM installations i
             JOIN user_installations ui ON ui.installation_id = i.id
             WHERE ui.user_id = ? AND (i.miscno2 LIKE ? OR i.name LIKE ?)
             ORDER BY i.miscno2 LIMIT 50'
        );
        $stmt->bind_param('iss', $userId, $like, $like);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

function abas_search_installations_from_api(mysqli $conn, ?array $user, string $q): array
{
    $cfg = abas_config()['trekant'];
    if ($cfg['user'] === '' || $cfg['pass'] === '') {
        throw new RuntimeException('TrekantBrand API er ikke konfigureret (TREKANT_API_USER/PASS i .env).');
    }

    $client = abas_trekant();
    $misc = strtolower(trim($q));
    $resp = $client->searchInstallations(abas_trekant_userid($user), $misc);
    $code = abas_trekant_return_code($resp);
    if ($code !== 0) {
        $msg = (string) ($resp['message']['message'] ?? $resp['Message'] ?? '');
        throw new RuntimeException(
            'TrekantBrand søgning fejlede (kode ' . $code . ($msg !== '' ? ': ' . $msg : '') . ').'
        );
    }

    $rows = abas_trekant_rows($resp);
    if ($rows === []) {
        return [];
    }

    $savedIds = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = abas_upsert_installation($conn, $row);
        if ($id > 0) {
            $savedIds[$id] = true;
        }
    }

    if ($savedIds === []) {
        throw new RuntimeException('API returnerede anlæg, men de kunne ikke gemmes i databasen.');
    }

    $ids = array_keys($savedIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare('SELECT * FROM installations WHERE id IN (' . $placeholders . ') ORDER BY miscno2');
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $result;
}

function abas_is_miscno2_query(string $q): bool
{
    return (bool) preg_match('/^[a-z]{3}\d{4}$/i', trim($q));
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
