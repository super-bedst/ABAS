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

function abas_user_linked_installations(mysqli $conn, int $userId): array
{
    require_once __DIR__ . '/installation_groups.php';

    return abas_user_accessible_installations($conn, $userId);
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
        require_once __DIR__ . '/installation_groups.php';
        $stmt = $conn->prepare(
            'SELECT DISTINCT i.* FROM installations i
             WHERE i.id IN (
                 SELECT ui.installation_id FROM user_installations ui WHERE ui.user_id = ?
                 UNION
                 SELECT igm.installation_id
                 FROM user_installation_groups uig
                 JOIN installation_group_members igm ON igm.group_id = uig.group_id
                 WHERE uig.user_id = ?
             )
             AND (i.miscno2 LIKE ? OR i.name LIKE ? OR i.city LIKE ?)
             ORDER BY i.miscno2 LIMIT 50'
        );
        $stmt->bind_param('iisss', $userId, $userId, $like, $like, $like);
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
    $resp = $client->searchInstallations(abas_trekant_userid($user, $conn), $misc);
    $code = abas_trekant_return_code($resp);
    if ($code !== 0) {
        $hint = abas_trekant_response_hint($resp);
        throw new RuntimeException(
            'TrekantBrand søgning fejlede (kode ' . $code . ($hint !== '' ? ': ' . $hint : '') . ').'
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

/** miscno2: 3 bogstaver + 1–6 cifre (typisk 4, fx fab0100 eller fab700001). */
function abas_is_miscno2_query(string $q): bool
{
    return (bool) preg_match('/^[a-z]{3}\d{1,6}$/i', trim($q));
}

/**
 * Batch-søgenøgler til g_search_installations (prefix-match på miscno2).
 * Fx prefix fab, min 5000, max 9999 → fab50 … fab99 (springer fab00–fab49 over).
 *
 * @return list<string>
 */
function abas_sync_batch_search_keys(string $prefix, int $maxSuffix, int $minSuffix = 0): array
{
    $pfx = strtolower(trim($prefix));
    if ($pfx === '' || $maxSuffix < 0) {
        return [];
    }
    $minSuffix = max(0, $minSuffix);
    if ($minSuffix > $maxSuffix) {
        return [];
    }
    if ($maxSuffix < 100) {
        return [$pfx];
    }

    $suffixWidth = strlen(str_pad((string) $maxSuffix, 4, '0', STR_PAD_LEFT));
    $batchKeyDigits = max(1, $suffixWidth - 2);
    $startBatch = (int) floor($minSuffix / 100);
    $endBatch = (int) floor($maxSuffix / 100);

    $keys = [];
    for ($b = $startBatch; $b <= $endBatch; $b++) {
        $keys[] = $pfx . str_pad((string) $b, $batchKeyDigits, '0', STR_PAD_LEFT);
    }

    return $keys;
}

function abas_sync_upsert_rows(mysqli $conn, array $rows): array
{
    $received = count($rows);
    $upserted = 0;
    $seen = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $key = ($row['s_ins'] ?? $row['insid'] ?? '') . ':' . ($row['deal_id'] ?? '');
        if ($key === ':' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        if (abas_upsert_installation($conn, $row) > 0) {
            $upserted++;
        }
    }

    return ['received' => $received, 'upserted' => $upserted];
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
    $minSuffix = max(0, (int) ($prefix['min_suffix'] ?? 0));
    $maxSuffix = (int) $prefix['max_suffix'];
    $maxRows = min(100, max(1, (int) $prefix['batch_size']));
    $searchKeys = abas_sync_batch_search_keys($pfx, $maxSuffix, $minSuffix);
    $batches = 0;
    $received = 0;
    $upserted = 0;
    $errors = [];

    foreach ($searchKeys as $misc) {
        try {
            $resp = $client->searchInstallations($userid, $misc, null, $maxRows);
            $code = abas_trekant_return_code($resp);
            if ($code !== 0) {
                $hint = abas_trekant_response_hint($resp);
                $errors[] = $misc . ': ReturnCode ' . $code . ($hint !== '' ? ' (' . $hint . ')' : '');
                continue;
            }
            $rows = abas_trekant_rows($resp);
            $counts = abas_sync_upsert_rows($conn, $rows);
            $received += $counts['received'];
            $upserted += $counts['upserted'];
        } catch (Throwable $e) {
            $errors[] = $misc . ': ' . $e->getMessage();
        }
        $batches++;
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

function abas_sync_verify_cron_request(): bool
{
    require_once __DIR__ . '/cron_auth.php';

    return abas_cron_verify_request(['SYNC_CRON_SECRET']);
}

function abas_sync_cron_auth_error(): string
{
    require_once __DIR__ . '/cron_auth.php';

    return abas_cron_auth_error(['SYNC_CRON_SECRET'], 'sync');
}

function abas_sync_all_active(mysqli $conn): array
{
    $res = $conn->query('SELECT id, prefix FROM sync_prefixes WHERE active = 1 ORDER BY prefix');
    if (!$res) {
        throw new RuntimeException('Kunne ikke læse sync-prefixes');
    }

    $prefixes = [];
    $totalUpserted = 0;
    $startedAt = microtime(true);

    while ($row = $res->fetch_assoc()) {
        $prefixId = (int) $row['id'];
        try {
            $result = abas_sync_prefix($conn, $prefixId);
            $totalUpserted += (int) $result['upserted'];
            $prefixes[] = [
                'prefix_id' => $prefixId,
                'prefix' => (string) $row['prefix'],
                'batches' => (int) $result['batches'],
                'received' => (int) $result['received'],
                'upserted' => (int) $result['upserted'],
                'status' => (string) $result['status'],
                'errors' => array_slice($result['errors'], 0, 5),
            ];
        } catch (Throwable $e) {
            $prefixes[] = [
                'prefix_id' => $prefixId,
                'prefix' => (string) $row['prefix'],
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
    $res->close();

    return [
        'ok' => true,
        'total_upserted' => $totalUpserted,
        'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        'prefixes' => $prefixes,
    ];
}

function abas_handle_sync_cron_webhook(mysqli $conn): never
{
    require_once __DIR__ . '/api_auth.php';

    if (!abas_sync_verify_cron_request()) {
        abas_api_json(403, ['ok' => false, 'error' => abas_sync_cron_auth_error()]);
    }

    @set_time_limit(600);

    try {
        $result = abas_sync_all_active($conn);
        abas_api_json(200, $result);
    } catch (Throwable $e) {
        abas_api_json(500, ['ok' => false, 'error' => $e->getMessage()]);
    }
}
