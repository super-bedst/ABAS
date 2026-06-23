<?php

declare(strict_types=1);

require_once __DIR__ . '/sms.php';

function abas_service_notification_phone(mysqli $conn, array $actor, ?int $onBehalfUserId): string
{
    if ($onBehalfUserId !== null && $onBehalfUserId > 0) {
        $stmt = $conn->prepare('SELECT phone FROM users WHERE id = ? AND active = 1 LIMIT 1');
        $stmt->bind_param('i', $onBehalfUserId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return trim((string) ($row['phone'] ?? ''));
    }

    return trim((string) ($actor['phone'] ?? ''));
}

function abas_service_notification_misc(array $installation): string
{
    return strtoupper(trim((string) ($installation['miscno2'] ?? '')));
}

function abas_notify_service_started(
    mysqli $conn,
    array $actor,
    array $installation,
    ?int $onBehalfUserId,
    ?int $sessionId,
    bool $unlimited,
    ?float $hours
): void {
    $phone = abas_service_notification_phone($conn, $actor, $onBehalfUserId);
    if ($phone === '') {
        return;
    }

    $misc = abas_service_notification_misc($installation);
    $name = trim((string) ($installation['name'] ?? ''));
    $vcOnBehalf = $onBehalfUserId !== null
        && $onBehalfUserId > 0
        && in_array((string) ($actor['role'] ?? ''), ['vagtcentral', 'admin'], true);

    if ($vcOnBehalf) {
        $body = 'ABA: Vagtcentralen har sat ' . $misc . ' i service på dine vegne.';
    } else {
        $body = 'ABA: Anlæg ' . $misc;
        if ($name !== '') {
            $body .= ' (' . $name . ')';
        }
        $body .= ' er sat i service.';
    }

    if (!$unlimited && $hours !== null && $hours > 0) {
        $body .= ' Varighed: ' . rtrim(rtrim(number_format($hours, 1, ',', ''), '0'), ',') . ' t.';
    } elseif ($unlimited) {
        $body .= ' Uden tidsbegrænsning.';
    }

    abas_sms_queue($conn, $phone, $body, 'service_start', $sessionId);
}

function abas_notify_service_stopped(
    mysqli $conn,
    array $actor,
    array $installation,
    ?int $onBehalfUserId,
    ?int $sessionId
): void {
    $phone = abas_service_notification_phone($conn, $actor, $onBehalfUserId);
    if ($phone === '') {
        return;
    }

    $misc = abas_service_notification_misc($installation);
    $name = trim((string) ($installation['name'] ?? ''));
    $vcOnBehalf = $onBehalfUserId !== null
        && $onBehalfUserId > 0
        && in_array((string) ($actor['role'] ?? ''), ['vagtcentral', 'admin'], true);

    if ($vcOnBehalf) {
        $body = 'ABA: Vagtcentralen har sat ' . $misc . ' i drift igen på dine vegne.';
    } else {
        $body = 'ABA: Anlæg ' . $misc;
        if ($name !== '') {
            $body .= ' (' . $name . ')';
        }
        $body .= ' er sat i drift igen.';
    }

    abas_sms_queue($conn, $phone, $body, 'service_stop', $sessionId);
}

function abas_load_service_session_for_stop(mysqli $conn, int $installationId, ?int $sessionId): ?array
{
    if ($sessionId !== null && $sessionId > 0) {
        $stmt = $conn->prepare('SELECT * FROM service_sessions WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $sessionId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    $stmt = $conn->prepare(
        'SELECT * FROM service_sessions WHERE installation_id = ? AND status = "active" ORDER BY id DESC LIMIT 1'
    );
    $stmt->bind_param('i', $installationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function abas_service_on_behalf_user_id(?array $sessionRow): ?int
{
    if ($sessionRow === null) {
        return null;
    }
    $id = (int) ($sessionRow['on_behalf_of_user_id'] ?? 0);

    return $id > 0 ? $id : null;
}
