<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/user_management.php';
require_once __DIR__ . '/../includes/installation_sync.php';

$conn = abas_db();
$actor = abas_require_login();
abas_require_role(['anlaegsejer']);

$actorId = (int) $actor['id'];
$myInstallations = abas_user_linked_installations($conn, $actorId);
$canAddUsers = $myInstallations !== [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
    require_once __DIR__ . '/../includes/password_flow.php';

    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = abas_resolve_username_for_email($conn, $email, (string) ($_POST['username'] ?? ''));
    $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
    $displayName = trim($_POST['registration_display_name'] ?? '');
    $role = (string) ($_POST['role'] ?? 'anlaegsafprover');
    $installationIds = array_map(static fn ($id): int => (int) $id, (array) ($_POST['installation_ids'] ?? []));
    $smsServiceAllowed = !empty($_POST['sms_service_allowed']);
    $smsCode = trim($_POST['sms_code'] ?? '');

    $result = abas_create_anlaegsejer_managed_user(
        $conn,
        $actor,
        $email,
        $username,
        $phone,
        $displayName,
        $role,
        $installationIds,
        $smsServiceAllowed,
        $smsCode
    );

    if (!$result['ok']) {
        abas_flash_set('error', $result['message']);
        abas_redirect('anlaegsbrugere.php');
    }

    $newId = (int) ($result['user_id'] ?? 0);
    $sent = $newId > 0 && abas_password_send_flow_email($conn, $newId, 'welcome');
    abas_flash_set(
        $sent ? 'success' : 'error',
        $sent
            ? 'Bruger oprettet. Velkomst-e-mail med link til adgangskode er sendt.'
            : 'Bruger oprettet, men velkomst-e-mail kunne ikke sendes — kontakt TrekantBrand.'
    );
    abas_redirect('anlaegsbrugere.php');
}

$managedUsers = abas_list_anlaegsejer_managed_users($conn, $actorId);
abas_session_release();

$pageTitle = 'Anlægsbrugere';
$currentUser = $actor;
require __DIR__ . '/partials/header.php';
?>
<div class="flex flex-wrap items-start justify-between gap-3 mb-1">
    <div>
        <h1 class="abas-page-title !mb-0">Anlægsbrugere</h1>
    </div>
    <?php if ($canAddUsers): ?>
    <button
        type="button"
        id="open-add-user-modal"
        class="abas-btn-primary shrink-0"
        aria-controls="add-user-modal"
        aria-expanded="false"
    >Tilføj bruger</button>
    <?php endif; ?>
</div>
<p class="abas-page-lead">
    Brugere og afprøvere tilknyttet dine anlæg
    <?php if ($myInstallations !== []): ?>
        (<?= count($myInstallations) ?> anlæg)
    <?php endif; ?>.
</p>

<?php if (!$canAddUsers): ?>
    <div class="abas-panel mt-4">
        Du har ingen tilknyttede anlæg endnu — kontakt vagtcentral eller administrator for at få anlæg tilknyttet, før du kan oprette brugere.
    </div>
<?php endif; ?>

<?php if ($managedUsers === []): ?>
    <div class="abas-panel mt-4">
        Ingen andre anlægsejere eller afprøvere er tilknyttet de samme anlæg som dig.
        <?php if ($canAddUsers): ?>
            Brug «Tilføj bruger» for at oprette en ny bruger og tilknytte dine anlæg.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="abas-table-wrap mt-6">
        <table class="abas-table">
            <thead>
                <tr>
                    <th>Navn</th>
                    <th>E-mail</th>
                    <th>Telefon</th>
                    <th>Rolle</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($managedUsers as $u): ?>
                <tr>
                    <td><?= htmlspecialchars(abas_user_display_name($u)) ?></td>
                    <td><?= htmlspecialchars((string) $u['email']) ?></td>
                    <td><?= htmlspecialchars((string) ($u['phone'] ?? '')) ?></td>
                    <td><?= htmlspecialchars(abas_role_label((string) $u['role'])) ?></td>
                    <td>
                        <a href="<?= abas_url('anlaegsbruger-edit.php?id=' . (int) $u['id']) ?>" class="abas-link text-sm">Rediger</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($canAddUsers): ?>
<div id="add-user-modal" class="abas-modal hidden" role="dialog" aria-modal="true" aria-labelledby="add-user-modal-title">
    <div class="abas-modal-backdrop" data-abas-modal-close tabindex="-1"></div>
    <div class="abas-modal-panel">
        <div class="abas-modal-header">
            <h2 id="add-user-modal-title" class="abas-card-title !mb-0">Tilføj bruger</h2>
            <button type="button" class="abas-modal-close" data-abas-modal-close aria-label="Luk">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <form method="post" class="space-y-0" data-abas-loading="Opretter bruger…">
            <input type="hidden" name="action" value="create_user">
            <div class="abas-field">
                <label class="abas-label" for="add-email">E-mail</label>
                <input id="add-email" name="email" type="email" required class="abas-input" autocomplete="off">
            </div>
            <div class="abas-field">
                <label class="abas-label" for="add-username">Brugernavn</label>
                <input id="add-username" name="username" maxlength="255" class="abas-input" placeholder="Samme som e-mail hvis tom">
            </div>
            <div class="abas-field">
                <label class="abas-label" for="add-display-name">Navn</label>
                <input id="add-display-name" name="registration_display_name" class="abas-input" placeholder="Vises i lister og kommentarer">
            </div>
            <div class="abas-field">
                <label class="abas-label" for="add-phone">Telefon</label>
                <input id="add-phone" name="phone" required placeholder="+45..." class="abas-input">
            </div>
            <div class="abas-field">
                <label class="abas-label" for="add-role">Rolle</label>
                <select id="add-role" name="role" class="abas-select">
                    <option value="anlaegsafprover">Anlægsafprøver</option>
                    <option value="anlaegsejer">Anlægsejer</option>
                </select>
            </div>
            <fieldset class="abas-field">
                <legend class="abas-label">Tilknyt anlæg</legend>
                <div class="space-y-2 rounded-xl border border-gray-200 p-3 max-h-40 overflow-y-auto">
                    <?php foreach ($myInstallations as $inst): ?>
                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" name="installation_ids[]" value="<?= (int) $inst['id'] ?>" class="abas-checkbox mt-0.5" checked>
                        <span>
                            <span class="font-mono font-medium text-brand"><?= htmlspecialchars((string) $inst['miscno2']) ?></span>
                            <span class="text-gray-600"> — <?= htmlspecialchars((string) $inst['name']) ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p class="abas-hint">Brugeren får adgang til de valgte anlæg.</p>
            </fieldset>
            <div class="abas-field">
                <label class="abas-label" for="add-sms-code">SMS-kode (anlægsbetjening)</label>
                <input id="add-sms-code" name="sms_code" minlength="6" autocomplete="off" class="abas-input font-mono" placeholder="Min. 6 tegn">
                <p class="abas-hint">Påkrævet når «Må betjene anlæg via SMS» er valgt.</p>
            </div>
            <label class="flex items-center gap-2 text-sm mb-4">
                <input type="checkbox" name="sms_service_allowed" value="1" class="abas-checkbox">
                Må betjene anlæg via SMS
            </label>
            <p class="text-sm text-gray-600 mb-4">Der sendes automatisk velkomst-e-mail med link til valg af adgangskode.</p>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="abas-btn-primary">Opret bruger</button>
                <button type="button" class="abas-btn-secondary" data-abas-modal-close>Annuller</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.abasInitModal === 'function') {
        window.abasInitModal('open-add-user-modal', 'add-user-modal');
    }
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php';
