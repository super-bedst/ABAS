<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/user_management.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/service.php';
require_once __DIR__ . '/../includes/installation_sync.php';

$conn = abas_db();
$actor = abas_require_login();
abas_require_role(['vagtcentral']);

$targetId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$listQuery = array_filter([
    'q' => trim((string) ($_GET['q'] ?? $_POST['q'] ?? '')) ?: null,
    'sort' => (string) ($_GET['sort'] ?? $_POST['sort'] ?? '') ?: null,
    'dir' => (string) ($_GET['dir'] ?? $_POST['dir'] ?? '') ?: null,
]);
$listUrl = abas_vc_anlaegsbrugere_page_url($listQuery);
$selfUrl = abas_vc_anlaegsbruger_edit_url($targetId, $listQuery);

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $targetId);
$stmt->execute();
$editUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$editUser || !abas_vc_may_manage_anlaegsbruger($actor, $editUser)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        abas_flash_set('error', 'Ingen adgang til brugeren.');
        abas_redirect($listUrl);
    }
    abas_forbidden('Ingen adgang til brugeren.', ['target_user_id' => $targetId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'unlink') {
        $installationId = (int) ($_POST['installation_id'] ?? 0);
        if ($installationId > 0 && abas_unlink_user_installation($conn, $targetId, $installationId)) {
            abas_flash_set('success', 'Anlæg fjernet fra brugeren.');
        } else {
            abas_flash_set('error', 'Kunne ikke fjerne tilknytning.');
        }
        abas_redirect($selfUrl);
    }

    if ($action === 'link') {
        $linkError = abas_link_user_installation_by_miscno2($conn, $targetId, (string) ($_POST['miscno2'] ?? ''), $actor);
        if ($linkError !== null) {
            abas_flash_set('error', $linkError);
        } else {
            abas_flash_set('success', 'Anlæg tilknyttet.');
        }
        abas_redirect($selfUrl);
    }

    if ($action === 'reset_password') {
        abas_password_send_flow_email($conn, $targetId, 'reset', (int) $actor['id']);
        abas_flash_set('success', 'Nulstillings-e-mail sendt.');
        abas_redirect($selfUrl);
    }

    if ($action === 'send_welcome') {
        abas_password_send_flow_email($conn, $targetId, 'welcome', (int) $actor['id']);
        abas_flash_set('success', 'Velkomst-e-mail sendt.');
        abas_redirect($selfUrl);
    }

    if ($action === 'save') {
        $result = abas_save_managed_user_contact(
            $conn,
            $actor,
            $editUser,
            (string) ($_POST['phone'] ?? ''),
            (string) ($_POST['username'] ?? ''),
            (string) ($_POST['registration_display_name'] ?? ''),
            !empty($_POST['sms_service_allowed']),
            trim($_POST['sms_code'] ?? ''),
            'vagtcentral'
        );
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Bruger opdateret.');
        abas_redirect($selfUrl);
    }
}

$linkedInstallations = abas_user_installation_links($conn, $targetId);
$linkedWithStatus = [];
foreach ($linkedInstallations as $inst) {
    $session = abas_active_session_for_installation($conn, (int) $inst['id']);
    $linkedWithStatus[] = ['inst' => $inst, 'in_service' => $session !== null];
}

$pageTitle = 'Rediger anlægsbruger';
$currentUser = $actor;
require __DIR__ . '/partials/header.php';
?>
<div class="mb-2">
    <a href="<?= htmlspecialchars($listUrl) ?>" class="abas-back-link">&larr; Tilbage til anlægsbrugere</a>
</div>
<h1 class="abas-page-title !text-xl">Rediger anlægsbruger</h1>
<p class="abas-page-lead"><?= htmlspecialchars(abas_role_label((string) $editUser['role'])) ?> — <?= htmlspecialchars(abas_user_display_name($editUser)) ?></p>

<form method="post" class="abas-card max-w-lg abas-form mb-6">
    <input type="hidden" name="id" value="<?= $targetId ?>">
    <input type="hidden" name="action" value="save">
    <?php foreach ($listQuery as $key => $value): ?>
        <input type="hidden" name="<?= htmlspecialchars((string) $key) ?>" value="<?= htmlspecialchars((string) $value) ?>">
    <?php endforeach; ?>
    <div class="abas-field">
        <label class="abas-label" for="email">E-mail</label>
        <input id="email" readonly value="<?= htmlspecialchars((string) $editUser['email']) ?>" class="abas-input bg-gray-50">
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
        <input type="checkbox" name="sms_service_allowed" value="1" class="abas-checkbox" <?= !empty($editUser['sms_service_allowed']) ? 'checked' : '' ?>>
        Må betjene anlæg via SMS
    </label>
    <div class="flex flex-wrap gap-2 pt-2">
        <button type="submit" class="abas-btn-primary">Gem</button>
        <a href="<?= htmlspecialchars($listUrl) ?>" class="abas-btn-secondary">Annuller</a>
    </div>
</form>

<div class="abas-card max-w-lg mb-6">
    <h2 class="abas-card-title">Adgangskode-e-mail</h2>
    <p class="text-sm text-gray-600 mb-3">Send velkomst- eller nulstillingslink uden at gemme øvrige ændringer.</p>
    <div class="flex flex-wrap gap-2">
        <form method="post" class="inline">
            <input type="hidden" name="id" value="<?= $targetId ?>">
            <input type="hidden" name="action" value="send_welcome">
            <?php foreach ($listQuery as $key => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars((string) $key) ?>" value="<?= htmlspecialchars((string) $value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="abas-btn-secondary text-sm">Send velkomst-e-mail</button>
        </form>
        <form method="post" class="inline">
            <input type="hidden" name="id" value="<?= $targetId ?>">
            <input type="hidden" name="action" value="reset_password">
            <?php foreach ($listQuery as $key => $value): ?>
                <input type="hidden" name="<?= htmlspecialchars((string) $key) ?>" value="<?= htmlspecialchars((string) $value) ?>">
            <?php endforeach; ?>
            <button type="submit" class="abas-btn-secondary text-sm">Send nulstil adgangskode</button>
        </form>
    </div>
</div>

<div class="abas-card max-w-lg abas-form">
    <h2 class="abas-card-title">Tilknyttede anlæg</h2>
    <?php if ($linkedWithStatus === []): ?>
        <p class="text-gray-500 text-sm mb-4">Ingen anlæg tilknyttet endnu.</p>
    <?php else: ?>
        <ul class="space-y-2 mb-4">
            <?php foreach ($linkedWithStatus as $row):
                $inst = $row['inst'];
                ?>
                <li class="flex flex-wrap items-center justify-between gap-2 border border-gray-100 rounded-xl px-3 py-2">
                    <div>
                        <a href="<?= abas_url('installation.php?id=' . (int) $inst['id']) ?>" class="font-mono font-medium text-brand hover:underline"><?= htmlspecialchars((string) $inst['miscno2']) ?></a>
                        <span class="text-sm text-gray-600"> — <?= htmlspecialchars((string) $inst['name']) ?></span>
                        <span class="<?= $row['in_service'] ? 'abas-badge-in-service' : 'abas-badge-ok' ?> ml-2"><?= $row['in_service'] ? 'I service' : 'Drift' ?></span>
                    </div>
                    <form method="post" class="inline">
                        <input type="hidden" name="id" value="<?= $targetId ?>">
                        <input type="hidden" name="action" value="unlink">
                        <input type="hidden" name="installation_id" value="<?= (int) $inst['id'] ?>">
                        <?php foreach ($listQuery as $key => $value): ?>
                            <input type="hidden" name="<?= htmlspecialchars((string) $key) ?>" value="<?= htmlspecialchars((string) $value) ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="abas-btn-danger !py-1 !px-2 text-xs" onclick="return confirm('Fjern tilknytning?')">Fjern</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" class="flex flex-wrap gap-2 items-end border-t border-gray-100 pt-4">
        <input type="hidden" name="id" value="<?= $targetId ?>">
        <input type="hidden" name="action" value="link">
        <?php foreach ($listQuery as $key => $value): ?>
            <input type="hidden" name="<?= htmlspecialchars((string) $key) ?>" value="<?= htmlspecialchars((string) $value) ?>">
        <?php endforeach; ?>
        <div class="abas-field flex-1 min-w-[10rem] !mb-0">
            <label class="abas-label" for="link-miscno2">Tilknyt anlæg (ABA-nr.)</label>
            <input id="link-miscno2" name="miscno2" required placeholder="fab0100" class="abas-input font-mono">
        </div>
        <button type="submit" class="abas-btn-secondary">Tilknyt</button>
    </form>
</div>
<?php require __DIR__ . '/partials/footer.php';
