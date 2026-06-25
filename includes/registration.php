<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/password_flow.php';
require_once __DIR__ . '/installation_sync.php';
require_once __DIR__ . '/app_log.php';

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
    array $miscno2List,
    bool $requestNewCompany = false,
    string $requestedCompanyName = ''
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
    $requestedCompanyName = trim($requestedCompanyName);
    if ($type === 'montor') {
        $installer = abas_installer_approved_for_domain($conn, abas_email_domain($email));
        if ($installer) {
            $installerId = (int) $installer['id'];
        } elseif ($requestNewCompany && $requestedCompanyName !== '') {
            if (strlen($requestedCompanyName) < 2) {
                return ['ok' => false, 'message' => 'Angiv et gyldigt virksomhedsnavn.'];
            }
        } else {
            return ['ok' => false, 'message' => 'E-mail-domænet er ikke godkendt til montør-registrering. Kryds af for at ansøge om oprettelse af ny virksomhed.'];
        }
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

    $username = abas_generate_username_from_email($conn, $email);

    $role = $type;
    $storeRequestedCompany = ($type === 'montor' && $installerId === null && $requestedCompanyName !== '') ? $requestedCompanyName : null;

    try {
        if ($installerId !== null) {
            $stmt = $conn->prepare(
                'INSERT INTO users (email, username, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at)
                 VALUES (?, ?, ?, ?, ?, 0, "pending", ?, ?, NOW())'
            );
            $stmt->bind_param('ssssiss', $email, $username, $role, $phone, $installerId, $type, $displayName);
        } elseif ($storeRequestedCompany !== null) {
            $stmt = $conn->prepare(
                'INSERT INTO users (email, username, role, phone, active, registration_status, registration_type, registration_display_name, registration_requested_company_name, registration_requested_at)
                 VALUES (?, ?, ?, ?, 0, "pending", ?, ?, ?, NOW())'
            );
            $stmt->bind_param('sssssss', $email, $username, $role, $phone, $type, $displayName, $storeRequestedCompany);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO users (email, username, role, phone, active, registration_status, registration_type, registration_display_name, registration_requested_at)
                 VALUES (?, ?, ?, ?, 0, "pending", ?, ?, NOW())'
            );
            $stmt->bind_param('ssssss', $email, $username, $role, $phone, $type, $displayName);
        }
        $stmt->execute();
        $userId = (int) $stmt->insert_id;
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        abas_log_error('registration', $e->getMessage(), [
            'email' => $email,
            'type' => $type,
            'username' => $username,
        ]);

        if ((int) $e->getCode() === 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
            if (str_contains($e->getMessage(), 'uq_email')) {
                return ['ok' => false, 'message' => 'Der findes allerede en bruger med denne e-mail.'];
            }

            return ['ok' => false, 'message' => 'Ansøgningen kunne ikke gemmes. Kontakt TrekantBrand hvis problemet fortsætter.'];
        }

        return ['ok' => false, 'message' => 'Ansøgningen kunne ikke gemmes lige nu. Prøv igen om lidt.'];
    }

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
    if ($role === 'montor' && empty($user['installer_id'])) {
        $requestedCompany = trim((string) ($user['registration_requested_company_name'] ?? ''));
        if ($requestedCompany === '') {
            return ['ok' => false, 'message' => 'Montør-ansøgning mangler tilknyttet firma. Opret firma/domæne først eller afvis ansøgningen.'];
        }
        $domain = abas_email_domain((string) $user['email']);
        $create = abas_installer_create($conn, $requestedCompany, $domain, $adminId);
        if (!$create['ok']) {
            return ['ok' => false, 'message' => $create['message'] ?? 'Kunne ikke oprette firma.'];
        }
        $newInstallerId = (int) $create['id'];
        $link = $conn->prepare('UPDATE users SET installer_id = ? WHERE id = ?');
        $link->bind_param('ii', $newInstallerId, $userId);
        $link->execute();
        $link->close();
        $user['installer_id'] = $newInstallerId;
    }

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

function abas_pending_registration_count(mysqli $conn): int
{
    $res = $conn->query('SELECT COUNT(*) AS c FROM users WHERE registration_status = "pending"');
    if (!$res) {
        return 0;
    }
    $row = $res->fetch_assoc();
    $res->free();

    return (int) ($row['c'] ?? 0);
}

function abas_render_pending_registrations_banner(int $count): string
{
    if ($count <= 0) {
        return '';
    }

    $label = $count === 1 ? '1 ventende godkendelse' : $count . ' ventende godkendelser';
    $url = abas_url('admin/registration-requests.php');

    ob_start();
    ?>
    <div class="abas-pending-banner mb-5" role="status">
        <span class="abas-pending-banner__dot" aria-hidden="true"></span>
        <div class="min-w-0 flex-1">
            <p class="abas-pending-banner__title"><?= htmlspecialchars($label) ?></p>
            <p class="abas-pending-banner__hint">Der er nye registreringsanmodninger, der afventer din godkendelse.</p>
        </div>
        <a href="<?= htmlspecialchars($url) ?>" class="abas-pending-banner__action shrink-0">Gå til godkendelse</a>
    </div>
    <?php

    return (string) ob_get_clean();
}
