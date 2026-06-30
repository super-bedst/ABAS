<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/password_flow.php';
require_once __DIR__ . '/../../includes/users.php';
require_once __DIR__ . '/../../includes/installation_sync.php';
require_once __DIR__ . '/../../includes/service.php';
require_once __DIR__ . '/../../includes/mfa.php';
require_once __DIR__ . '/../../includes/bas_sso_auth.php';

$conn = abas_db();
$admin = abas_require_login();
abas_require_role(['admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $conn->prepare(
    'SELECT u.*, ai.company_name
     FROM users u
     LEFT JOIN approved_installers ai ON ai.id = u.installer_id
     WHERE u.id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$editUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$editUser) {
    abas_not_found('Brugeren findes ikke.', ['user_id' => $id]);
}

$basLink = abas_bas_user_link_get($conn, $id);
$basLinked = $basLink !== null && trim((string) ($basLink['bas_username'] ?? '')) !== '';

$listFilter = (string) ($_GET['filter'] ?? $_POST['filter'] ?? 'alle');
if ($listFilter === '') {
    $listFilter = 'alle';
}
$listSort = (string) ($_GET['sort'] ?? $_POST['sort'] ?? '');
if (!in_array($listSort, abas_admin_users_sort_columns(), true)) {
    $listSort = '';
}
$listDir = strtolower((string) ($_GET['dir'] ?? $_POST['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$listSearch = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$listUrl = abas_admin_users_list_url($listFilter, $listSort !== '' ? $listSort : null, $listSort !== '' ? $listDir : null, $listSearch !== '' ? $listSearch : null);
$selfUrl = abas_admin_user_edit_url($id, $listFilter, $listSort !== '' ? $listSort : null, $listSort !== '' ? $listDir : null, $listSearch !== '' ? $listSearch : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        if (empty($_POST['confirm_delete'])) {
            abas_flash_set('error', 'Bekræft sletning med afkrydsningsfeltet.');
            abas_redirect($selfUrl);
        }
        $result = abas_delete_user($conn, $id, (int) $admin['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        abas_redirect($result['ok'] ? $listUrl : $selfUrl);
    }

    if ($action === 'unlink') {
        $installationId = (int) ($_POST['installation_id'] ?? 0);
        if ($installationId > 0 && abas_unlink_user_installation($conn, $id, $installationId)) {
            abas_flash_set('success', 'Anlæg fjernet fra brugeren.');
        } else {
            abas_flash_set('error', 'Kunne ikke fjerne tilknytning.');
        }
        abas_redirect($selfUrl);
    }

    if ($action === 'link') {
        $linkError = abas_link_user_installation_by_miscno2($conn, $id, (string) ($_POST['miscno2'] ?? ''), $user);
        if ($linkError !== null) {
            abas_flash_set('error', $linkError);
        } else {
            abas_flash_set('success', 'Anlæg tilknyttet.');
        }
        abas_redirect($selfUrl);
    }

    if ($action === 'reset_mfa') {
        abas_mfa_reset_user($conn, $id, (int) $admin['id']);
        abas_flash_set('success', '2FA nulstillet — brugeren skal opsætte igen ved næste login.');
        abas_redirect($selfUrl);
    }

    if ($action === 'send_welcome') {
        $sent = abas_password_send_flow_email($conn, $id, 'welcome', (int) $admin['id']);
        abas_flash_set($sent ? 'success' : 'error', $sent
            ? 'Velkomst-e-mail sendt til ' . ($editUser['email'] ?? '') . '.'
            : 'Kunne ikke sende velkomst-e-mail — tjek mail-konfiguration.');
        abas_redirect($selfUrl);
    }

    if ($action === 'send_reset') {
        $sent = abas_password_send_flow_email($conn, $id, 'reset', (int) $admin['id']);
        abas_flash_set($sent ? 'success' : 'error', $sent
            ? 'Nulstillings-e-mail sendt til ' . ($editUser['email'] ?? '') . '.'
            : 'Kunne ikke sende nulstillings-e-mail — tjek mail-konfiguration.');
        abas_redirect($selfUrl);
    }

    if ($action !== 'save') {
        abas_redirect($selfUrl);
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $displayName = trim($_POST['registration_display_name'] ?? '');
    $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
    $role = $_POST['role'] ?? $editUser['role'];
    $active = !empty($_POST['active']) ? 1 : 0;
    $smsServiceAllowed = !empty($_POST['sms_service_allowed']) ? 1 : 0;
    $mfaMethod = $_POST['mfa_method'] ?? 'passkey';
    if (!in_array($mfaMethod, ['passkey', 'sms_otp'], true)) {
        $mfaMethod = 'passkey';
    }

    if (!in_array($role, abas_roles(), true)) {
        $role = (string) $editUser['role'];
    }
    if ($email === '' || $username === '') {
        abas_flash_set('error', 'E-mail og brugernavn er påkrævet.');
        abas_redirect($selfUrl);
    }
    if (!abas_validate_phone($phone)) {
        abas_flash_set('error', 'Angiv et gyldigt telefonnummer (min. 8 cifre).');
        abas_redirect($selfUrl);
    }

    $smsCode = trim($_POST['sms_code'] ?? '');
    if ($smsCode !== '' && !abas_validate_sms_code($smsCode)) {
        abas_flash_set('error', 'SMS-kode skal være mindst 6 tegn.');
        abas_redirect($selfUrl);
    }
    if (abas_user_sms_service_code_required_on_edit($role, $smsServiceAllowed === 1, $editUser, $smsCode)) {
        abas_flash_set('error', 'Angiv SMS-kode (min. 6 tegn) når SMS-betjening er aktiveret.');
        abas_redirect($selfUrl);
    }

    $installerId = null;
    if ($role === 'montor') {
        $installerId = abas_assign_installer_for_montor($conn, $email);
        if ($installerId === null) {
            abas_flash_set('error', 'Montør skal have e-mail fra et godkendt installatør-domæne.');
            abas_redirect($selfUrl);
        }
    }

    $dupEmail = $conn->prepare('SELECT id, username FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $dupEmail->bind_param('si', $email, $id);
    $dupEmail->execute();
    $emailConflict = $dupEmail->get_result()->fetch_assoc();
    $dupEmail->close();
    if ($emailConflict) {
        abas_flash_set('error', 'E-mail findes allerede (bruger: ' . ($emailConflict['username'] ?? '?') . ').');
        abas_redirect($selfUrl);
    }

    $dupUser = $conn->prepare('SELECT id, email FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $dupUser->bind_param('si', $username, $id);
    $dupUser->execute();
    $userConflict = $dupUser->get_result()->fetch_assoc();
    $dupUser->close();
    if ($userConflict) {
        abas_flash_set('error', 'Brugernavn findes allerede (e-mail: ' . ($userConflict['email'] ?? '?') . ').');
        abas_redirect($selfUrl);
    }

    if ($installerId !== null) {
        $displayNameDb = $displayName !== '' ? $displayName : null;
        $upd = $conn->prepare(
            'UPDATE users SET email=?, username=?, phone=?, role=?, active=?, installer_id=?, sms_service_allowed=?, registration_display_name=? WHERE id=?'
        );
        $upd->bind_param('ssssiiisi', $email, $username, $phone, $role, $active, $installerId, $smsServiceAllowed, $displayNameDb, $id);
    } else {
        $displayNameDb = $displayName !== '' ? $displayName : null;
        $upd = $conn->prepare(
            'UPDATE users SET email=?, username=?, phone=?, role=?, active=?, installer_id=NULL, sms_service_allowed=?, registration_display_name=? WHERE id=?'
        );
        $upd->bind_param('ssssiisi', $email, $username, $phone, $role, $active, $smsServiceAllowed, $displayNameDb, $id);
    }
    $upd->execute();
    $upd->close();

    abas_mfa_set_method($conn, $id, $mfaMethod);

    if ($smsCode !== '') {
        abas_set_user_sms_code($conn, $id, $smsCode);
    }

    require_once __DIR__ . '/../../includes/activity_log.php';
    abas_log_user_target_event(
        $conn,
        'user',
        'updated',
        $id,
        (int) $admin['id'],
        $displayName !== '' ? $displayName : $username,
        'Brugerdata opdateret i admin'
    );

    abas_flash_set('success', 'Bruger opdateret.');
    abas_redirect($selfUrl);
}

$linkedInstallations = abas_user_installation_links($conn, $id);
$ownerInstallStatus = abas_user_installations_with_service_status($conn);
$linkedWithStatus = $ownerInstallStatus[$id] ?? [];
$statusByInstId = [];
foreach ($linkedWithStatus as $row) {
    $statusByInstId[(int) $row['installation_id']] = (bool) $row['in_service'];
}

$pageTitle = 'Rediger bruger';
$currentUser = $admin;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2">
    <a href="<?= htmlspecialchars($listUrl) ?>" class="abas-back-link">&larr; Tilbage til brugere</a>
</div>
<h1 class="abas-page-title !text-xl">Rediger bruger</h1>
<p class="abas-page-lead"><?= htmlspecialchars(abas_role_label((string) $editUser['role'])) ?> — <?= htmlspecialchars(abas_user_display_name($editUser)) ?></p>

<form method="post" class="abas-card max-w-lg abas-form mb-6">
    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
    <input type="hidden" name="action" value="save">
    <?php if ($listFilter !== 'alle'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($listFilter) ?>"><?php endif; ?>
    <?php if ($listSort !== ''): ?>
    <input type="hidden" name="sort" value="<?= htmlspecialchars($listSort) ?>">
    <input type="hidden" name="dir" value="<?= htmlspecialchars($listDir) ?>">
    <?php endif; ?>
    <?php if ($listSearch !== ''): ?>
    <input type="hidden" name="q" value="<?= htmlspecialchars($listSearch) ?>">
    <?php endif; ?>
    <div class="abas-field">
        <label class="abas-label" for="registration_display_name">Visningsnavn</label>
        <input id="registration_display_name" name="registration_display_name" value="<?= htmlspecialchars((string) ($editUser['registration_display_name'] ?? '')) ?>" class="abas-input" placeholder="Navn fra ansøgning eller kontaktperson">
        <p class="abas-hint">Vises i lister og e-mails — adskilt fra login-brugernavn.</p>
    </div>
    <div class="abas-field">
        <label class="abas-label" for="email">E-mail</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars((string) $editUser['email']) ?>" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="username">Login-brugernavn</label>
        <input id="username" name="username" required value="<?= htmlspecialchars((string) $editUser['username']) ?>" class="abas-input">
        <p class="abas-hint">Bruges ved login — som udgangspunkt samme som e-mail.</p>
    </div>
    <div class="abas-field">
        <label class="abas-label" for="phone">Telefon</label>
        <input id="phone" name="phone" required value="<?= htmlspecialchars((string) ($editUser['phone'] ?? '')) ?>" class="abas-input" placeholder="+45...">
    </div>
    <div class="abas-field" id="sms-code-field">
        <label class="abas-label" for="sms_code">SMS-kode (anlægsbetjening)</label>
        <input id="sms_code" name="sms_code" autocomplete="new-password" class="abas-input font-mono" placeholder="<?= abas_user_has_sms_code($editUser) ? 'Tom = behold nuværende' : 'Valgfri — min. 6 tegn ved SMS-betjening' ?>">
        <p class="abas-hint">
            Bruges til at starte/stoppe anlæg via SMS — ikke til 2FA-login.
            Påkrævet når «Må betjene anlæg via SMS» er valgt.
            <?php if (abas_user_has_sms_code($editUser)): ?>
                Kode er sat; tom felt bevarer nuværende.
            <?php endif; ?>
        </p>
    </div>
    <div class="abas-field">
        <label class="abas-label" for="role">Rolle</label>
        <select id="role" name="role" class="abas-select">
            <?php foreach (abas_roles() as $r): ?>
                <option value="<?= $r ?>" <?= $editUser['role'] === $r ? 'selected' : '' ?>><?= abas_role_label($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($basLinked): ?>
    <div class="abas-field">
        <label class="abas-label" for="bas_username">BAS-bruger</label>
        <input id="bas_username" value="<?= htmlspecialchars((string) $basLink['bas_username']) ?>" class="abas-input font-mono bg-gray-100 text-gray-600" readonly disabled>
        <p class="abas-hint">Styres via BAS/SCIM — redigeres ikke her.</p>
    </div>
    <?php endif; ?>
    <?php if ($editUser['role'] === 'montor' || !empty($editUser['company_name'])): ?>
        <div class="abas-panel">
            <span class="text-gray-600">Firma (automatisk):</span>
            <span class="font-medium"><?= htmlspecialchars((string) ($editUser['company_name'] ?? 'Ikke tildelt')) ?></span>
            <p class="abas-hint !mt-2">Sættes automatisk ud fra e-mail-domænet ved montører.</p>
        </div>
    <?php endif; ?>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="active" value="1" class="abas-checkbox" <?= $editUser['active'] ? 'checked' : '' ?>>
        Aktiv konto
    </label>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="sms_service_allowed" value="1" class="abas-checkbox" <?= !empty($editUser['sms_service_allowed']) ? 'checked' : '' ?>>
        Må betjene anlæg via SMS
    </label>
    <div class="abas-field">
        <label class="abas-label" for="mfa_method">2FA-metode</label>
        <select id="mfa_method" name="mfa_method" class="abas-select">
            <option value="passkey" <?= abas_user_mfa_method($conn, $id) === 'passkey' ? 'selected' : '' ?>>Passkey (standard)</option>
            <option value="sms_otp" <?= abas_user_mfa_method($conn, $id) === 'sms_otp' ? 'selected' : '' ?>>SMS engangskode (login)</option>
        </select>
        <p class="abas-hint">SMS engangskode sendes automatisk ved login — kræver ikke SMS-kode til anlægsbetjening.</p>
    </div>
    <div class="flex flex-wrap gap-2 pt-2">
        <button class="abas-btn-primary">Gem</button>
        <a href="<?= htmlspecialchars($listUrl) ?>" class="abas-btn-secondary">Annuller</a>
    </div>
</form>

<div class="abas-card max-w-lg mb-6">
    <h2 class="abas-card-title">Adgangskode-e-mail</h2>
    <p class="text-sm text-gray-600 mb-3">Send link uden at gemme øvrige ændringer i formularen ovenfor.</p>
    <div class="flex flex-wrap gap-2">
        <form method="post" class="inline">
            <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
            <input type="hidden" name="action" value="send_welcome">
            <button type="submit" class="abas-btn-secondary text-sm">Send velkomst-e-mail</button>
        </form>
        <form method="post" class="inline">
            <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
            <input type="hidden" name="action" value="send_reset">
            <button type="submit" class="abas-btn-secondary text-sm">Send nulstil adgangskode</button>
        </form>
    </div>
</div>

<form method="post" class="mb-6">
    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
    <input type="hidden" name="action" value="reset_mfa">
    <button class="abas-btn-secondary text-sm">Nulstil 2FA</button>
</form>

<?php if (in_array($editUser['role'], ['anlaegsejer', 'anlaegsafprover'], true)): ?>
<div class="abas-card max-w-lg abas-form mb-6">
    <h2 class="abas-card-title">Tilknyttede anlæg</h2>
    <?php if ($linkedInstallations === []): ?>
        <p class="text-gray-500 text-sm mb-4">Ingen anlæg tilknyttet endnu.</p>
    <?php else: ?>
        <ul class="space-y-2 mb-4">
            <?php foreach ($linkedInstallations as $inst):
                $instId = (int) $inst['id'];
                $inService = $statusByInstId[$instId] ?? false;
                ?>
                <li class="flex flex-wrap items-center justify-between gap-2 border border-gray-100 rounded-xl px-3 py-2">
                    <div>
                        <a href="<?= abas_url('installation.php?id=' . $instId) ?>" class="font-mono font-medium text-brand hover:underline" data-abas-loading="Åbner anlæg…">
                            <?= htmlspecialchars((string) $inst['miscno2']) ?>
                        </a>
                        <span class="text-sm text-gray-600"> — <?= htmlspecialchars((string) $inst['name']) ?></span>
                        <span class="<?= $inService ? 'abas-badge-in-service' : 'abas-badge-ok' ?> ml-2"><?= $inService ? 'I service' : 'Drift' ?></span>
                    </div>
                    <form method="post" class="inline">
                        <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
                        <input type="hidden" name="action" value="unlink">
                        <input type="hidden" name="installation_id" value="<?= $instId ?>">
                        <button type="submit" class="abas-btn-danger !py-1 !px-2 text-xs" onclick="return confirm('Fjern tilknytning til <?= htmlspecialchars((string) $inst['miscno2'], ENT_QUOTES) ?>?')">Fjern</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" class="flex flex-wrap gap-2 items-end">
        <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
        <input type="hidden" name="action" value="link">
        <div class="abas-field flex-1 min-w-[10rem]">
            <label class="abas-label" for="miscno2">Tilknyt anlæg (ABA-nr.)</label>
            <input id="miscno2" name="miscno2" required placeholder="fab0100" class="abas-input font-mono">
        </div>
        <button class="abas-btn-secondary">Tilknyt</button>
    </form>
</div>
<?php endif; ?>

<?php if ((int) $editUser['id'] !== (int) $admin['id']): ?>
<div class="abas-card max-w-lg border-red-200">
    <h2 class="abas-card-title text-red-800">Slet bruger</h2>
    <p class="text-sm text-gray-600 mb-3">Brugere med servicehistorik deaktiveres i stedet for at blive slettet helt.</p>
    <form method="post" class="abas-form" onsubmit="return confirm('Er du sikker på at du vil slette/deaktivere denne bruger?')">
        <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
        <input type="hidden" name="action" value="delete">
        <label class="flex items-center gap-2 text-sm text-red-800">
            <input type="checkbox" name="confirm_delete" value="1" class="abas-checkbox" required>
            Jeg bekræfter sletning af denne bruger
        </label>
        <button type="submit" class="abas-btn-danger mt-3">Slet bruger</button>
    </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php';
