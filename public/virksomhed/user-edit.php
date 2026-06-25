<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/user_management.php';
require_once __DIR__ . '/../includes/password_flow.php';

$conn = abas_db();
$actor = abas_require_login();
abas_require_role(['virksomhedsadmin']);

$installerId = (int) ($actor['installer_id'] ?? 0);
if ($installerId <= 0) {
    http_response_code(403);
    exit('Ingen virksomhed tilknyttet.');
}

$targetId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND installer_id = ? LIMIT 1');
$stmt->bind_param('ii', $targetId, $installerId);
$stmt->execute();
$editUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$editUser || !abas_virksomhedsadmin_may_manage_user($actor, $editUser)) {
    http_response_code(403);
    exit('Ingen adgang til brugeren.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'reset_password') {
        abas_password_send_flow_email($conn, $targetId, 'reset');
        abas_flash_set('success', 'Nulstillings-e-mail sendt.');
        abas_redirect('virksomhed/user-edit.php?id=' . $targetId);
    }

    if ($action === 'delete') {
        if (empty($_POST['confirm_delete'])) {
            abas_flash_set('error', 'Bekræft sletning med afkrydsningsfeltet.');
            abas_redirect('virksomhed/user-edit.php?id=' . $targetId);
        }
        $result = abas_delete_user($conn, $targetId, (int) $actor['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        abas_redirect($result['ok'] ? 'virksomhed/users.php' : 'virksomhed/user-edit.php?id=' . $targetId);
    }

    if ($action === 'save') {
        $result = abas_save_virksomhed_managed_user(
            $conn,
            $actor,
            $editUser,
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['phone'] ?? ''),
            (string) ($_POST['username'] ?? ''),
            (string) ($_POST['registration_display_name'] ?? ''),
            !empty($_POST['active']),
            !empty($_POST['sms_service_allowed']),
            trim($_POST['sms_code'] ?? '')
        );
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Bruger opdateret.');
        abas_redirect('virksomhed/user-edit.php?id=' . $targetId);
    }
}

$pageTitle = 'Rediger bruger';
$currentUser = $actor;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2">
    <a href="<?= abas_url('virksomhed/users.php') ?>" class="abas-back-link">&larr; Tilbage til virksomhedsbrugere</a>
</div>
<h1 class="abas-page-title !text-xl">Rediger bruger</h1>
<p class="abas-page-lead"><?= htmlspecialchars(abas_role_label((string) $editUser['role'])) ?> — <?= htmlspecialchars(abas_user_display_name($editUser)) ?></p>

<form method="post" class="abas-card max-w-lg abas-form mb-6">
    <input type="hidden" name="id" value="<?= $targetId ?>">
    <input type="hidden" name="action" value="save">
    <div class="abas-field">
        <label class="abas-label" for="email">E-mail</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars((string) $editUser['email']) ?>" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="username">Brugernavn</label>
        <input id="username" name="username" required value="<?= htmlspecialchars((string) $editUser['username']) ?>" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="registration_display_name">Visningsnavn</label>
        <input id="registration_display_name" name="registration_display_name" value="<?= htmlspecialchars((string) ($editUser['registration_display_name'] ?? '')) ?>" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="phone">Telefon</label>
        <input id="phone" name="phone" required value="<?= htmlspecialchars((string) ($editUser['phone'] ?? '')) ?>" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="sms_code">SMS-kode (anlægsbetjening)</label>
        <input id="sms_code" name="sms_code" minlength="6" autocomplete="new-password" class="abas-input font-mono" placeholder="<?= abas_user_has_sms_code($editUser) ? 'Tom = behold nuværende' : 'Min. 6 tegn' ?>">
        <p class="abas-hint">Påkrævet når «Må betjene anlæg via SMS» er valgt.</p>
    </div>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="active" value="1" class="abas-checkbox" <?= $editUser['active'] ? 'checked' : '' ?>>
        Aktiv konto
    </label>
    <label class="flex items-center gap-2 text-sm mt-2">
        <input type="checkbox" name="sms_service_allowed" value="1" class="abas-checkbox" <?= !empty($editUser['sms_service_allowed']) ? 'checked' : '' ?>>
        Må betjene anlæg via SMS
    </label>
    <div class="flex flex-wrap gap-2 pt-2">
        <button type="submit" class="abas-btn-primary">Gem</button>
        <a href="<?= abas_url('virksomhed/users.php') ?>" class="abas-btn-secondary">Annuller</a>
    </div>
</form>

<div class="abas-card max-w-lg mb-6">
    <h2 class="abas-card-title">Adgangskode</h2>
    <p class="text-sm text-gray-600 mb-3">Send link til nulstilling af adgangskode.</p>
    <form method="post">
        <input type="hidden" name="id" value="<?= $targetId ?>">
        <input type="hidden" name="action" value="reset_password">
        <button type="submit" class="abas-btn-secondary text-sm">Send nulstil adgangskode</button>
    </form>
</div>

<?php if ((int) $editUser['id'] !== (int) $actor['id']): ?>
<div class="abas-card max-w-lg border-red-200">
    <h2 class="abas-card-title text-red-800">Slet bruger</h2>
    <form method="post" class="abas-form" onsubmit="return confirm('Er du sikker?')">
        <input type="hidden" name="id" value="<?= $targetId ?>">
        <input type="hidden" name="action" value="delete">
        <label class="flex items-center gap-2 text-sm text-red-800">
            <input type="checkbox" name="confirm_delete" value="1" class="abas-checkbox" required>
            Jeg bekræfter sletning/deaktivering
        </label>
        <button type="submit" class="abas-btn-danger mt-3">Slet bruger</button>
    </form>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php';
