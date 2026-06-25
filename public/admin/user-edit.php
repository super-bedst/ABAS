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
    http_response_code(404);
    exit('Bruger ikke fundet.');
}

$listFilter = $_GET['filter'] ?? $_POST['filter'] ?? '';
$listUrl = 'admin/users.php' . ($listFilter !== '' && $listFilter !== 'alle' ? '?filter=' . rawurlencode($listFilter) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        if (empty($_POST['confirm_delete'])) {
            abas_flash_set('error', 'Bekræft sletning med afkrydsningsfeltet.');
            abas_redirect('admin/user-edit.php?id=' . $id);
        }
        $result = abas_delete_user($conn, $id, (int) $admin['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        abas_redirect($result['ok'] ? $listUrl : 'admin/user-edit.php?id=' . $id . ($listFilter !== '' ? '&filter=' . rawurlencode($listFilter) : ''));
    }

    if ($action === 'unlink') {
        $installationId = (int) ($_POST['installation_id'] ?? 0);
        if ($installationId > 0 && abas_unlink_user_installation($conn, $id, $installationId)) {
            abas_flash_set('success', 'Anlæg fjernet fra brugeren.');
        } else {
            abas_flash_set('error', 'Kunne ikke fjerne tilknytning.');
        }
        abas_redirect('admin/user-edit.php?id=' . $id);
    }

    if ($action === 'link') {
        $linkError = abas_link_user_installation_by_miscno2($conn, $id, (string) ($_POST['miscno2'] ?? ''));
        if ($linkError !== null) {
            abas_flash_set('error', $linkError);
        } else {
            abas_flash_set('success', 'Anlæg tilknyttet.');
        }
        abas_redirect('admin/user-edit.php?id=' . $id);
    }

    if ($action === 'reset_mfa') {
        abas_mfa_reset_user($conn, $id);
        abas_flash_set('success', '2FA nulstillet — brugeren skal opsætte igen ved næste login.');
        abas_redirect('admin/user-edit.php?id=' . $id);
    }

    if ($action === 'send_welcome') {
        $sent = abas_password_send_flow_email($conn, $id, 'welcome');
        abas_flash_set($sent ? 'success' : 'error', $sent
            ? 'Velkomst-e-mail sendt til ' . ($editUser['email'] ?? '') . '.'
            : 'Kunne ikke sende velkomst-e-mail — tjek mail-konfiguration.');
        abas_redirect('admin/user-edit.php?id=' . $id . ($listFilter !== '' ? '&filter=' . rawurlencode($listFilter) : ''));
    }

    if ($action === 'send_reset') {
        $sent = abas_password_send_flow_email($conn, $id, 'reset');
        abas_flash_set($sent ? 'success' : 'error', $sent
            ? 'Nulstillings-e-mail sendt til ' . ($editUser['email'] ?? '') . '.'
            : 'Kunne ikke sende nulstillings-e-mail — tjek mail-konfiguration.');
        abas_redirect('admin/user-edit.php?id=' . $id . ($listFilter !== '' ? '&filter=' . rawurlencode($listFilter) : ''));
    }

    if ($action !== 'save') {
        abas_redirect('admin/user-edit.php?id=' . $id);
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
    $role = $_POST['role'] ?? $editUser['role'];
    $active = !empty($_POST['active']) ? 1 : 0;
    $trekantUserid = trim($_POST['trekant_userid'] ?? '');
    $trekantUserid = $trekantUserid !== '' ? strtoupper($trekantUserid) : null;
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
        abas_redirect('admin/user-edit.php?id=' . $id);
    }
    if (!abas_validate_phone($phone)) {
        abas_flash_set('error', 'Angiv et gyldigt telefonnummer (min. 8 cifre).');
        abas_redirect('admin/user-edit.php?id=' . $id);
    }

    $smsCode = trim($_POST['sms_code'] ?? '');
    if ($smsCode !== '' && !abas_validate_sms_code($smsCode)) {
        abas_flash_set('error', 'SMS-kode skal være mindst 6 tegn.');
        abas_redirect('admin/user-edit.php?id=' . $id);
    }
    if (abas_user_role_uses_sms_code($role) && $smsCode === '' && !abas_user_has_sms_code($editUser)) {
        abas_flash_set('error', 'Angiv SMS-kode (min. 6 tegn) for montør og anlægsejer.');
        abas_redirect('admin/user-edit.php?id=' . $id);
    }

    $installerId = null;
    if ($role === 'montor') {
        $installerId = abas_assign_installer_for_montor($conn, $email);
        if ($installerId === null) {
            abas_flash_set('error', 'Montør skal have e-mail fra et godkendt installatør-domæne.');
            abas_redirect('admin/user-edit.php?id=' . $id);
        }
    }

    $dupEmail = $conn->prepare('SELECT id, username FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $dupEmail->bind_param('si', $email, $id);
    $dupEmail->execute();
    $emailConflict = $dupEmail->get_result()->fetch_assoc();
    $dupEmail->close();
    if ($emailConflict) {
        abas_flash_set('error', 'E-mail findes allerede (bruger: ' . ($emailConflict['username'] ?? '?') . ').');
        abas_redirect('admin/user-edit.php?id=' . $id);
    }

    $dupUser = $conn->prepare('SELECT id, email FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $dupUser->bind_param('si', $username, $id);
    $dupUser->execute();
    $userConflict = $dupUser->get_result()->fetch_assoc();
    $dupUser->close();
    if ($userConflict) {
        abas_flash_set('error', 'Brugernavn findes allerede (e-mail: ' . ($userConflict['email'] ?? '?') . ').');
        abas_redirect('admin/user-edit.php?id=' . $id);
    }

    if ($installerId !== null) {
        $upd = $conn->prepare(
            'UPDATE users SET email=?, username=?, phone=?, role=?, active=?, trekant_userid=?, installer_id=?, sms_service_allowed=? WHERE id=?'
        );
        $upd->bind_param('ssssisiii', $email, $username, $phone, $role, $active, $trekantUserid, $installerId, $smsServiceAllowed, $id);
    } else {
        $upd = $conn->prepare(
            'UPDATE users SET email=?, username=?, phone=?, role=?, active=?, trekant_userid=?, installer_id=NULL, sms_service_allowed=? WHERE id=?'
        );
        $upd->bind_param('ssssisii', $email, $username, $phone, $role, $active, $trekantUserid, $smsServiceAllowed, $id);
    }
    $upd->execute();
    $upd->close();

    abas_mfa_set_method($conn, $id, $mfaMethod);

    if ($smsCode !== '') {
        abas_set_user_sms_code($conn, $id, $smsCode);
    }

    abas_flash_set('success', 'Bruger opdateret.');
    abas_redirect('admin/user-edit.php?id=' . $id);
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
    <a href="<?= abas_url($listUrl) ?>" class="abas-back-link">&larr; Tilbage til brugere</a>
</div>
<h1 class="abas-page-title !text-xl">Rediger bruger</h1>
<p class="abas-page-lead"><?= htmlspecialchars(abas_role_label((string) $editUser['role'])) ?> — <?= htmlspecialchars((string) $editUser['username']) ?></p>

<form method="post" class="abas-card max-w-lg abas-form mb-6">
    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
    <input type="hidden" name="action" value="save">
    <?php if ($listFilter !== ''): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($listFilter) ?>"><?php endif; ?>
    <div class="abas-field">
        <label class="abas-label" for="email">E-mail</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars((string) $editUser['email']) ?>" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="username">Brugernavn</label>
        <input id="username" name="username" required value="<?= htmlspecialchars((string) $editUser['username']) ?>" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="phone">Telefon</label>
        <input id="phone" name="phone" required value="<?= htmlspecialchars((string) ($editUser['phone'] ?? '')) ?>" class="abas-input" placeholder="+45...">
    </div>
    <div class="abas-field" id="sms-code-field">
        <label class="abas-label" for="sms_code">SMS-kode</label>
        <input id="sms_code" name="sms_code" minlength="6" autocomplete="new-password" class="abas-input font-mono" placeholder="<?= abas_user_has_sms_code($editUser) ? 'Tom = behold nuværende' : 'Min. 6 tegn' ?>">
        <p class="abas-hint">
            Påkrævet for montør og anlægsejer — bruges sammen med telefonnummer ved SMS.
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
    <div class="abas-field">
        <label class="abas-label" for="trekant_userid">TrekantBrand userid</label>
        <input id="trekant_userid" name="trekant_userid" value="<?= htmlspecialchars((string) ($editUser['trekant_userid'] ?? '')) ?>" class="abas-input font-mono" maxlength="8">
    </div>
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
            <option value="sms_otp" <?= abas_user_mfa_method($conn, $id) === 'sms_otp' ? 'selected' : '' ?>>SMS engangskode</option>
        </select>
    </div>
    <div class="flex flex-wrap gap-2 pt-2">
        <button class="abas-btn-primary">Gem</button>
        <a href="<?= abas_url($listUrl) ?>" class="abas-btn-secondary">Annuller</a>
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
                        <a href="<?= abas_url('installation.php?id=' . $instId) ?>" class="font-mono font-medium text-brand hover:underline">
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
