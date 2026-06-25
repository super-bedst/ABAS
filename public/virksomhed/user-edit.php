<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/users.php';
require_once __DIR__ . '/../../includes/user_management.php';

$conn = abas_db();
$actor = abas_require_login();
abas_require_role(['virksomhedsadmin']);

$installerId = (int) ($actor['installer_id'] ?? 0);
if ($installerId <= 0) {
    abas_forbidden('Ingen virksomhed tilknyttet din konto.', ['installer_id' => $installerId]);
}

$targetId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND installer_id = ? LIMIT 1');
$stmt->bind_param('ii', $targetId, $installerId);
$stmt->execute();
$editUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$editUser || !abas_virksomhedsadmin_may_manage_user($actor, $editUser)) {
    abas_forbidden('Ingen adgang til brugeren.', ['target_user_id' => $targetId]);
}

$isSelf = (int) $editUser['id'] === (int) $actor['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

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
        $active = $isSelf ? true : !empty($_POST['active']);
        $result = abas_save_virksomhed_managed_user(
            $conn,
            $actor,
            $editUser,
            (string) ($_POST['registration_display_name'] ?? ''),
            (string) ($_POST['phone'] ?? ''),
            $active
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
<p class="abas-page-lead">
    <?= htmlspecialchars(abas_role_label((string) $editUser['role'])) ?>
    · <?= htmlspecialchars((string) $editUser['email']) ?>
</p>

<form method="post" class="abas-card max-w-lg abas-form mb-6">
    <input type="hidden" name="id" value="<?= $targetId ?>">
    <input type="hidden" name="action" value="save">
    <div class="abas-field">
        <label class="abas-label" for="registration_display_name">Navn</label>
        <input id="registration_display_name" name="registration_display_name" required minlength="2" value="<?= htmlspecialchars((string) ($editUser['registration_display_name'] ?? '')) ?>" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="phone">Telefon</label>
        <input id="phone" name="phone" required value="<?= htmlspecialchars((string) ($editUser['phone'] ?? '')) ?>" class="abas-input" placeholder="+45...">
    </div>
    <label class="flex items-center gap-2 text-sm">
        <?php if ($isSelf): ?>
            <input type="checkbox" class="abas-checkbox" checked disabled>
            <input type="hidden" name="active" value="1">
            <span>Aktiv konto</span>
            <span class="text-gray-500 text-xs">(din egen konto kan ikke deaktiveres her)</span>
        <?php else: ?>
            <input type="checkbox" name="active" value="1" class="abas-checkbox" <?= $editUser['active'] ? 'checked' : '' ?>>
            Aktiv konto
        <?php endif; ?>
    </label>
    <div class="flex flex-wrap gap-2 pt-4">
        <button type="submit" class="abas-btn-primary">Gem</button>
        <a href="<?= abas_url('virksomhed/users.php') ?>" class="abas-btn-secondary">Annuller</a>
    </div>
</form>

<?php if (!$isSelf): ?>
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
