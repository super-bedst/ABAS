<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/password_flow.php';
require_once __DIR__ . '/installation_sync.php';

function abas_registration_types(): array
{
    return ['montor', 'anlaegsejer', 'anlaegsafprover'];
}

function abas_registration_type_label(string $type): string
{
    return match ($type) {
        'montor' => 'Teknikker (montør)',
        'anlaegsejer' => 'Anlægsejer',
        'anlaegsafprover' => 'Anlægsafprøver',
        default => $type,
    };
}

function abas_generate_username_from_email(mysqli $conn, string $email): string
{
    $local = preg_replace('/[^a-z0-9._-]/', '', strtolower(explode('@', $email)[0] ?? 'user')) ?: 'user';
    $base = substr($local, 0, 80);
    $candidate = $base;
    $n = 1;
    while (true) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();
        if (!$exists) {
            return $candidate;
        }
        $candidate = substr($base, 0, 75) . $n;
        $n++;
    }
}

/**
 * @param list<string> $miscno2List
 * @return array{ok:bool, message?:string, user_id?:int}
 */
function abas_submit_registration(
    mysqli $conn,
    string $type,
    string $displayName,
    string $email,
    string $phone,
    array $miscno2List
): array {
    $type = strtolower(trim($type));
    if (!in_array($type, abas_registration_types(), true)) {
        return ['ok' => false, 'message' => 'Ugyldig ansøgningstype.'];
    }

    $displayName = trim($displayName);
    $email = strtolower(trim($email));
    $phone = abas_normalize_phone($phone);

    if ($displayName === '' || strlen($displayName) < 2) {
        return ['ok' => false, 'message' => 'Angiv dit fulde navn.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Angiv en gyldig e-mail.'];
    }
    if (!abas_validate_phone($phone)) {
        return ['ok' => false, 'message' => 'Angiv et gyldigt telefonnummer.'];
    }

    $installerId = null;
    if ($type === 'montor') {
        $installer = abas_installer_approved_for_domain($conn, abas_email_domain($email));
        if (!$installer) {
            return ['ok' => false, 'message' => 'E-mail-domænet er ikke godkendt til montør-registrering.'];
        }
        $installerId = (int) $installer['id'];
    } else {
        $miscno2List = array_values(array_unique(array_filter(array_map(
            static fn (string $m): string => strtolower(trim($m)),
            $miscno2List
        ))));
        if ($miscno2List === []) {
            return ['ok' => false, 'message' => 'Angiv mindst ét anlægsnummer (ABA-nr.).'];
        }
    }

    $chk = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $chk->bind_param('s', $email);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        $chk->close();

        return ['ok' => false, 'message' => 'Der findes allerede en bruger med denne e-mail.'];
    }
    $chk->close();

    $username = $displayName;
    $base = $displayName;
    $n = 1;
    while (true) {
        $chkU = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $chkU->bind_param('s', $username);
        $chkU->execute();
        $exists = (bool) $chkU->get_result()->fetch_row();
        $chkU->close();
        if (!$exists) {
            break;
        }
        $username = $base . ' ' . $n;
        $n++;
    }

    $role = $type;

    if ($installerId !== null) {
        $stmt = $conn->prepare(
            'INSERT INTO users (email, username, role, phone, installer_id, active, registration_status, registration_type, registration_requested_at)
             VALUES (?, ?, ?, ?, ?, 0, "pending", ?, NOW())'
        );
        $stmt->bind_param('ssssis', $email, $displayName, $role, $phone, $installerId, $type);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO users (email, username, role, phone, active, registration_status, registration_type, registration_requested_at)
             VALUES (?, ?, ?, ?, 0, "pending", ?, NOW())'
        );
        $stmt->bind_param('sssss', $email, $displayName, $role, $phone, $type);
    }
    $stmt->execute();
    $userId = (int) $stmt->insert_id;
    $stmt->close();

    if ($type !== 'montor') {
        $ins = $conn->prepare('INSERT INTO registration_installation_requests (user_id, miscno2) VALUES (?, ?)');
        foreach ($miscno2List as $misc) {
            $ins->bind_param('is', $userId, $misc);
            $ins->execute();
        }
        $ins->close();
    }

    return ['ok' => true, 'user_id' => $userId];
}

/**
 * @return list<array{id:int, miscno2:string}>
 */
function abas_registration_installation_requests(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare('SELECT id, miscno2 FROM registration_installation_requests WHERE user_id = ? ORDER BY miscno2');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * @return array{ok:bool, message:string}
 */
function abas_approve_registration(mysqli $conn, int $userId, int $adminId, bool $smsAllowed = false, ?string $smsCode = null): array
{
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND registration_status = "pending" LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['ok' => false, 'message' => 'Ansøgning ikke fundet eller allerede behandlet.'];
    }

    $role = (string) $user['role'];
    if (in_array($role, ['anlaegsejer', 'anlaegsafprover'], true)) {
        $requests = abas_registration_installation_requests($conn, $userId);
        if ($requests === []) {
            return ['ok' => false, 'message' => 'Ingen anlæg angivet på ansøgningen.'];
        }
        foreach ($requests as $req) {
            $misc = (string) $req['miscno2'];
            $installation = abas_find_installation_by_miscno2($conn, $misc);
            if (!$installation) {
                return ['ok' => false, 'message' => 'Anlæg ikke fundet: ' . $misc . ' — synkronisér anlæg før godkendelse.'];
            }
            abas_link_user_installation($conn, $userId, (int) $installation['id']);
            $upd = $conn->prepare('UPDATE registration_installation_requests SET installation_id = ? WHERE id = ?');
            $iid = (int) $installation['id'];
            $rid = (int) $req['id'];
            $upd->bind_param('ii', $iid, $rid);
            $upd->execute();
            $upd->close();
        }
    }

    $smsFlag = $smsAllowed ? 1 : 0;
    $upd = $conn->prepare(
        'UPDATE users SET active=1, registration_status="approved", registration_reviewed_at=NOW(),
         registration_reviewed_by_user_id=?, sms_service_allowed=? WHERE id=?'
    );
    $upd->bind_param('iii', $adminId, $smsFlag, $userId);
    $upd->execute();
    $upd->close();

    if ($smsAllowed && $smsCode !== null && $smsCode !== '' && abas_validate_sms_code($smsCode)) {
        abas_set_user_sms_code($conn, $userId, $smsCode);
    }

    abas_password_send_flow_email($conn, $userId, 'welcome');

    return ['ok' => true, 'message' => 'Ansøgning godkendt. Velkomst-e-mail sendt.'];
}

function abas_reject_registration(mysqli $conn, int $userId, int $adminId): array
{
    $upd = $conn->prepare(
        'UPDATE users SET active=0, registration_status="rejected", registration_reviewed_at=NOW(),
         registration_reviewed_by_user_id=? WHERE id=? AND registration_status="pending"'
    );
    $upd->bind_param('ii', $adminId, $userId);
    $upd->execute();
    $ok = $upd->affected_rows > 0;
    $upd->close();

    return $ok
        ? ['ok' => true, 'message' => 'Ansøgning afvist.']
        : ['ok' => false, 'message' => 'Kunne ikke afvise ansøgning.'];
}
