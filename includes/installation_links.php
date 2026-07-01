<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * @return array{0:int, 1:int}
 */
function abas_installation_link_canonical_pair(int $installationIdA, int $installationIdB): array
{
    if ($installationIdA === $installationIdB) {
        return [$installationIdA, $installationIdB];
    }

    return $installationIdA < $installationIdB
        ? [$installationIdA, $installationIdB]
        : [$installationIdB, $installationIdA];
}

function abas_installation_are_linked(mysqli $conn, int $installationIdA, int $installationIdB): bool
{
    if ($installationIdA <= 0 || $installationIdB <= 0 || $installationIdA === $installationIdB) {
        return false;
    }

    [$lo, $hi] = abas_installation_link_canonical_pair($installationIdA, $installationIdB);
    $stmt = $conn->prepare(
        'SELECT id FROM installation_links WHERE installation_id_lo = ? AND installation_id_hi = ? LIMIT 1'
    );
    $stmt->bind_param('ii', $lo, $hi);
    $stmt->execute();
    $found = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $found;
}

/**
 * @return list<array<string, mixed>>
 */
function abas_installation_linked_installations(mysqli $conn, int $installationId): array
{
    if ($installationId <= 0) {
        return [];
    }

    $stmt = $conn->prepare(
        'SELECT i.*
         FROM installation_links l
         JOIN installations i ON i.id = CASE
             WHEN l.installation_id_lo = ? THEN l.installation_id_hi
             ELSE l.installation_id_lo
         END
         WHERE l.installation_id_lo = ? OR l.installation_id_hi = ?
         ORDER BY i.miscno2'
    );
    $stmt->bind_param('iii', $installationId, $installationId, $installationId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * @return array{ok:bool, message:string}
 */
function abas_installation_link_create(
    mysqli $conn,
    int $installationIdA,
    int $installationIdB,
    ?int $actorUserId = null,
    string $note = ''
): array {
    if ($installationIdA <= 0 || $installationIdB <= 0) {
        return ['ok' => false, 'message' => 'Ugyldigt anlæg.'];
    }
    if ($installationIdA === $installationIdB) {
        return ['ok' => false, 'message' => 'Et anlæg kan ikke kobles til sig selv.'];
    }

    [$lo, $hi] = abas_installation_link_canonical_pair($installationIdA, $installationIdB);
    if (abas_installation_are_linked($conn, $lo, $hi)) {
        return ['ok' => false, 'message' => 'Anlæggene er allerede koblet.'];
    }

    $noteDb = trim($note) !== '' ? trim($note) : null;
    if ($actorUserId !== null && $actorUserId > 0) {
        $stmt = $conn->prepare(
            'INSERT INTO installation_links (installation_id_lo, installation_id_hi, note, created_by_user_id)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iisi', $lo, $hi, $noteDb, $actorUserId);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO installation_links (installation_id_lo, installation_id_hi, note)
             VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iis', $lo, $hi, $noteDb);
    }
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok
        ? ['ok' => true, 'message' => 'Anlæg koblet.']
        : ['ok' => false, 'message' => 'Kunne ikke oprette kobling.'];
}

/**
 * @return array{ok:bool, message:string}
 */
function abas_installation_link_delete(mysqli $conn, int $installationIdA, int $installationIdB): array
{
    if ($installationIdA <= 0 || $installationIdB <= 0 || $installationIdA === $installationIdB) {
        return ['ok' => false, 'message' => 'Ugyldigt anlæg.'];
    }

    [$lo, $hi] = abas_installation_link_canonical_pair($installationIdA, $installationIdB);
    $stmt = $conn->prepare(
        'DELETE FROM installation_links WHERE installation_id_lo = ? AND installation_id_hi = ?'
    );
    $stmt->bind_param('ii', $lo, $hi);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    return $ok
        ? ['ok' => true, 'message' => 'Kobling fjernet.']
        : ['ok' => false, 'message' => 'Kobling ikke fundet.'];
}

/**
 * @param list<int> $targetInstallationIds
 * @return array{ok:bool, message:string, created:int, skipped:int}
 */
function abas_installation_link_create_many(
    mysqli $conn,
    int $installationId,
    array $targetInstallationIds,
    ?int $actorUserId = null
): array {
    if ($installationId <= 0) {
        return ['ok' => false, 'message' => 'Ugyldigt anlæg.', 'created' => 0, 'skipped' => 0];
    }

    $created = 0;
    $skipped = 0;
    $errors = [];
    $seen = [];

    foreach ($targetInstallationIds as $targetId) {
        $targetId = (int) $targetId;
        if ($targetId <= 0 || isset($seen[$targetId])) {
            continue;
        }
        $seen[$targetId] = true;

        $result = abas_installation_link_create($conn, $installationId, $targetId, $actorUserId);
        if ($result['ok']) {
            $created++;
            continue;
        }
        if (str_contains($result['message'], 'allerede koblet')) {
            $skipped++;
            continue;
        }
        $errors[] = $result['message'];
    }

    if ($created === 0 && $errors === [] && $skipped === 0) {
        return ['ok' => false, 'message' => 'Vælg mindst ét anlæg at koble.', 'created' => 0, 'skipped' => 0];
    }

    if ($created === 0 && $errors !== []) {
        return ['ok' => false, 'message' => $errors[0], 'created' => 0, 'skipped' => $skipped];
    }

    $parts = [];
    if ($created > 0) {
        $parts[] = $created === 1 ? '1 anlæg koblet' : $created . ' anlæg koblet';
    }
    if ($skipped > 0) {
        $parts[] = $skipped === 1 ? '1 var allerede koblet' : $skipped . ' var allerede koblet';
    }

    return [
        'ok' => true,
        'message' => implode('. ', $parts) . '.',
        'created' => $created,
        'skipped' => $skipped,
    ];
}

/**
 * @param list<string> $linkedMiscno2
 * @return array{ok:bool, message?:string, primary?:array<string, mixed>, linked?:list<array<string, mixed>>}
 */
function abas_vc_resolve_service_installations(
    mysqli $conn,
    string $primaryMiscno2,
    array $linkedMiscno2
): array {
    $primaryMiscno2 = strtolower(trim($primaryMiscno2));
    if ($primaryMiscno2 === '') {
        return ['ok' => false, 'message' => 'Vælg et anlæg fra listen.'];
    }

    require_once __DIR__ . '/installation_sync.php';
    $primary = abas_find_installation_by_miscno2($conn, $primaryMiscno2);
    if (!$primary) {
        return ['ok' => false, 'message' => 'Anlæg ikke fundet: ' . $primaryMiscno2];
    }

    $primaryId = (int) $primary['id'];
    $linkedRows = [];
    $seenMisc = [$primaryMiscno2 => true];

    foreach ($linkedMiscno2 as $miscRaw) {
        $misc = strtolower(trim((string) $miscRaw));
        if ($misc === '' || isset($seenMisc[$misc])) {
            continue;
        }
        $seenMisc[$misc] = true;

        $row = abas_find_installation_by_miscno2($conn, $misc);
        if (!$row) {
            return ['ok' => false, 'message' => 'Koblet anlæg ikke fundet: ' . $misc];
        }
        if (!abas_installation_are_linked($conn, $primaryId, (int) $row['id'])) {
            return ['ok' => false, 'message' => 'Anlæg ' . $misc . ' er ikke koblet til ' . $primaryMiscno2 . '.'];
        }
        $linkedRows[] = $row;
    }

    return [
        'ok' => true,
        'primary' => $primary,
        'linked' => $linkedRows,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function abas_linked_installation_service_options(mysqli $conn, int $installationId): array
{
    require_once __DIR__ . '/installation_status.php';
    require_once __DIR__ . '/service.php';

    $out = [];
    foreach (abas_installation_linked_installations($conn, $installationId) as $row) {
        $id = (int) $row['id'];
        $session = abas_active_session_for_installation($conn, $id);
        $monStat = (string) ($row['mon_stat'] ?? '');
        $out[] = [
            'id' => $id,
            'miscno2' => (string) ($row['miscno2'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'city' => (string) ($row['city'] ?? ''),
            'mon_stat' => $monStat,
            'mon_stat_label' => abas_mon_stat_label($monStat),
            'allows_service' => abas_installation_allows_service($monStat),
            'in_service' => $session !== null,
        ];
    }

    return $out;
}

/** @deprecated Use abas_linked_installation_service_options() */
function abas_vc_linked_installation_options(mysqli $conn, int $installationId): array
{
    return abas_linked_installation_service_options($conn, $installationId);
}

/**
 * @param list<array<string, mixed>> $linkedInstallations
 * @return array{ok:bool, partial:bool, message:string, started_misc:list<string>, errors:list<string>, primary_id:int}
 */
function abas_execute_linked_service_starts(
    mysqli $conn,
    array $user,
    array $primaryInstallation,
    array $linkedInstallations,
    float $hours,
    ?int $onBehalfUserId,
    string $comment,
    string $source = 'web',
    bool $responsibilityAck = false,
    ?array $actorOverride = null,
    string $successMessageSingle = 'Service startet.',
    string $successMessageVcSingle = 'Service startet på vegne af montør.'
): array {
    require_once __DIR__ . '/service.php';

    $installationsToStart = array_merge([$primaryInstallation], $linkedInstallations);
    $startedMisc = [];
    $errors = [];
    $isVcBehalf = $onBehalfUserId !== null || $actorOverride !== null;

    foreach ($installationsToStart as $instRow) {
        $r = abas_start_service_session(
            $conn,
            $user,
            $instRow,
            $hours,
            $onBehalfUserId,
            $comment,
            $source,
            $responsibilityAck,
            $actorOverride
        );
        if ($r['ok']) {
            $startedMisc[] = (string) ($instRow['miscno2'] ?? '');
        } else {
            $errors[] = ((string) ($instRow['miscno2'] ?? '?')) . ': ' . ($r['message'] ?? 'Fejl');
        }
    }

    if ($startedMisc !== [] && $errors === []) {
        if (count($startedMisc) === 1) {
            $message = $isVcBehalf ? $successMessageVcSingle : $successMessageSingle;
        } else {
            $message = 'Service startet på ' . count($startedMisc) . ' anlæg: ' . implode(', ', $startedMisc) . '.';
        }

        return [
            'ok' => true,
            'partial' => false,
            'message' => $message,
            'started_misc' => $startedMisc,
            'errors' => [],
            'primary_id' => (int) ($primaryInstallation['id'] ?? 0),
        ];
    }

    if ($startedMisc !== []) {
        return [
            'ok' => false,
            'partial' => true,
            'message' => 'Service startet på ' . implode(', ', $startedMisc) . '. Fejl: ' . implode(' · ', $errors),
            'started_misc' => $startedMisc,
            'errors' => $errors,
            'primary_id' => (int) ($primaryInstallation['id'] ?? 0),
        ];
    }

    return [
        'ok' => false,
        'partial' => false,
        'message' => $errors[0] ?? 'Kunne ikke starte service.',
        'started_misc' => [],
        'errors' => $errors,
        'primary_id' => (int) ($primaryInstallation['id'] ?? 0),
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function abas_linked_installation_stop_options(mysqli $conn, int $installationId): array
{
    return array_values(array_filter(
        abas_linked_installation_service_options($conn, $installationId),
        static fn (array $row): bool => !empty($row['in_service'])
    ));
}

/**
 * @param list<array<string, mixed>> $linkedInstallations
 * @return array{ok:bool, partial:bool, message:string, stopped_misc:list<string>, errors:list<string>, failed_installations:list<array{id:int, miscno2:string, name:string, message:string}>, primary_id:int}
 */
function abas_execute_linked_service_stops(
    mysqli $conn,
    array $user,
    array $primaryInstallation,
    array $linkedInstallations,
    ?int $primarySessionId,
    string $comment,
    string $source = 'web',
    string $successMessageSingle = 'Service stoppet.'
): array {
    require_once __DIR__ . '/service.php';

    $stoppedMisc = [];
    $errors = [];
    $failedInstallations = [];

    $recordStopFailure = static function (array $installationRow, string $message) use (&$errors, &$failedInstallations): void {
        $miscno2 = (string) ($installationRow['miscno2'] ?? '?');
        $errors[] = $miscno2 . ': ' . $message;
        $failedInstallations[] = [
            'id' => (int) ($installationRow['id'] ?? 0),
            'miscno2' => $miscno2,
            'name' => (string) ($installationRow['name'] ?? ''),
            'message' => $message,
        ];
    };

    $primaryResult = abas_stop_service_session(
        $conn,
        $user,
        $primaryInstallation,
        $primarySessionId,
        $comment,
        $source
    );
    if ($primaryResult['ok']) {
        $stoppedMisc[] = (string) ($primaryInstallation['miscno2'] ?? '');
    } else {
        $recordStopFailure($primaryInstallation, (string) ($primaryResult['message'] ?? 'Fejl'));
    }

    foreach ($linkedInstallations as $instRow) {
        $linkedSession = abas_active_session_for_installation($conn, (int) ($instRow['id'] ?? 0));
        if ($linkedSession === null) {
            $recordStopFailure($instRow, 'Ikke i service.');
            continue;
        }
        $r = abas_stop_service_session(
            $conn,
            $user,
            $instRow,
            (int) $linkedSession['id'],
            $comment,
            $source
        );
        if ($r['ok']) {
            $stoppedMisc[] = (string) ($instRow['miscno2'] ?? '');
        } else {
            $recordStopFailure($instRow, (string) ($r['message'] ?? 'Fejl'));
        }
    }

    if ($stoppedMisc !== [] && $errors === []) {
        $message = count($stoppedMisc) === 1
            ? $successMessageSingle
            : 'Service stoppet på ' . count($stoppedMisc) . ' anlæg: ' . implode(', ', $stoppedMisc) . '.';

        return [
            'ok' => true,
            'partial' => false,
            'message' => $message,
            'stopped_misc' => $stoppedMisc,
            'errors' => [],
            'failed_installations' => [],
            'primary_id' => (int) ($primaryInstallation['id'] ?? 0),
        ];
    }

    if ($stoppedMisc !== []) {
        return [
            'ok' => false,
            'partial' => true,
            'message' => 'Service stoppet på ' . implode(', ', $stoppedMisc) . '. Fejl: ' . implode(' · ', $errors),
            'stopped_misc' => $stoppedMisc,
            'errors' => $errors,
            'failed_installations' => $failedInstallations,
            'primary_id' => (int) ($primaryInstallation['id'] ?? 0),
        ];
    }

    return [
        'ok' => false,
        'partial' => false,
        'message' => $errors[0] ?? 'Kunne ikke stoppe service.',
        'stopped_misc' => [],
        'errors' => $errors,
        'failed_installations' => $failedInstallations,
        'primary_id' => (int) ($primaryInstallation['id'] ?? 0),
    ];
}

/**
 * @param array{partial?:bool, failed_installations?:list<array{id:int, miscno2:string, name?:string}>} $stopResult
 * @return list<array{id:int, miscno2:string, name:string}>
 */
function abas_linked_stop_flash_installation_links(array $stopResult, int $currentInstallationId): array
{
    if (empty($stopResult['partial'])) {
        return [];
    }

    $links = [];
    foreach ($stopResult['failed_installations'] ?? [] as $row) {
        $instId = (int) ($row['id'] ?? 0);
        if ($instId <= 0 || $instId === $currentInstallationId) {
            continue;
        }
        $links[] = [
            'id' => $instId,
            'miscno2' => (string) ($row['miscno2'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
        ];
    }

    return $links;
}

/**
 * @param list<array<string, mixed>> $options
 */
function abas_render_linked_installation_service_options(array $options, string $context = 'start'): void
{
    if ($options === []) {
        return;
    }

    $linkedOptionsContext = $context;
    require __DIR__ . '/../public/partials/linked-installation-service-options.php';
}
