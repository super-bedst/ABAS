<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/password_flow.php';
require_once __DIR__ . '/../../includes/users.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
    $role = $_POST['role'] ?? $editUser['role'];
    $active = !empty($_POST['active']) ? 1 : 0;
    $trekantUserid = trim($_POST['trekant_userid'] ?? '');
    $trekantUserid = $trekantUserid !== '' ? strtoupper($trekantUserid) : null;
    $sendWelcome = !empty($_POST['send_welcome']);

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

    $installerId = null;
    if ($role === 'montor') {
        $installerId = abas_assign_installer_for_montor($conn, $email);
        if ($installerId === null) {
            abas_flash_set('error', 'Montør skal have e-mail fra et godkendt installatør-domæne.');
            abas_redirect('admin/user-edit.php?id=' . $id);
        }
    }

    $dup = $conn->prepare('SELECT id FROM users WHERE (email = ? OR username = ?) AND id <> ? LIMIT 1');
    $dup->bind_param('ssi', $email, $username, $id);
    $dup->execute();
    if ($dup->get_result()->fetch_row()) {
        $dup->close();
        abas_flash_set('error', 'E-mail eller brugernavn findes allerede.');
        abas_redirect('admin/user-edit.php?id=' . $id);
    }
    $dup->close();

    if ($installerId !== null) {
        $upd = $conn->prepare(
            'UPDATE users SET email=?, username=?, phone=?, role=?, active=?, trekant_userid=?, installer_id=? WHERE id=?'
        );
        $upd->bind_param('ssssisii', $email, $username, $phone, $role, $active, $trekantUserid, $installerId, $id);
    } else {
        $upd = $conn->prepare(
            'UPDATE users SET email=?, username=?, phone=?, role=?, active=?, trekant_userid=?, installer_id=NULL WHERE id=?'
        );
        $upd->bind_param('ssssisi', $email, $username, $phone, $role, $active, $trekantUserid, $id);
    }
    $upd->execute();
    $upd->close();

    if ($sendWelcome) {
        abas_password_send_flow_email($conn, $id, 'welcome');
    }

    abas_flash_set('success', 'Bruger opdateret.');
    abas_redirect('admin/users.php');
}

$pageTitle = 'Rediger bruger';
$currentUser = $admin;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="abas-page-title !text-xl">Rediger bruger</h1>
<form method="post" class="abas-card max-w-lg abas-form">
    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
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
        <input type="checkbox" name="send_welcome" value="1" class="abas-checkbox">
        Send velkomst/e-mail til valg af adgangskode
    </label>
    <div class="flex flex-wrap gap-2 pt-2">
        <button class="abas-btn-primary">Gem</button>
        <a href="<?= abas_url('admin/users.php') ?>" class="abas-btn-secondary">Annuller</a>
    </div>
</form>
<?php require __DIR__ . '/../partials/footer.php';
