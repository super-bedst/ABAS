<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/installation_groups.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$groupId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$group = abas_installation_group_get($conn, $groupId);
if (!$group) {
    abas_not_found('Anlægsgruppen findes ikke.', ['group_id' => $groupId]);
}

$listUrl = abas_admin_installation_groups_list_url();
$selfUrl = abas_admin_installation_group_edit_url($groupId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $result = abas_installation_group_delete($conn, $groupId);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        abas_redirect($listUrl);
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $update = abas_installation_group_update($conn, $groupId, $name, $description);
    if (!$update['ok']) {
        abas_flash_set('error', $update['message'] ?? 'Kunne ikke gemme gruppen.');
        abas_redirect($selfUrl);
    }

    $memberIdsRaw = trim((string) ($_POST['member_ids'] ?? ''));
    $memberIds = [];
    if ($memberIdsRaw !== '') {
        foreach (explode(',', $memberIdsRaw) as $part) {
            $memberIds[] = (int) $part;
        }
    }
    abas_installation_group_set_members($conn, $groupId, $memberIds);

    abas_flash_set('success', 'Anlægsgruppe gemt.');
    abas_redirect($selfUrl);
}

$members = abas_installation_group_members($conn, $groupId);
$initialMembers = array_map(static fn (array $row): array => [
    'id' => (int) $row['id'],
    'miscno2' => (string) ($row['miscno2'] ?? ''),
    'name' => (string) ($row['name'] ?? ''),
    'city' => (string) ($row['city'] ?? ''),
], $members);

$pageTitle = 'Rediger anlægsgruppe';
$currentUser = $user;
$extraHead = '<script src="' . htmlspecialchars(abas_asset_url('assets/js/installation-group-edit.js')) . '" defer></script>';
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2"><a href="<?= htmlspecialchars($listUrl) ?>" class="abas-back-link">&larr; Anlægsgrupper</a></div>
<h1 class="abas-page-title !text-xl"><?= htmlspecialchars((string) $group['name']) ?></h1>
<p class="abas-page-lead font-mono text-sm text-gray-500"><?= htmlspecialchars((string) $group['public_id']) ?></p>

<form method="post" class="abas-form" id="ig-form">
    <input type="hidden" name="id" value="<?= (int) $groupId ?>">
    <input type="hidden" name="member_ids" id="ig-member-ids" value="">
    <input type="hidden" name="action" value="save">

    <div class="grid lg:grid-cols-2 gap-4 mb-6 max-w-4xl">
        <div class="abas-field">
            <label class="abas-label" for="ig-name">Navn / label</label>
            <input id="ig-name" name="name" required class="abas-input" value="<?= htmlspecialchars((string) $group['name']) ?>">
        </div>
        <div class="abas-field">
            <label class="abas-label" for="ig-description">Beskrivelse</label>
            <input id="ig-description" name="description" class="abas-input" value="<?= htmlspecialchars((string) ($group['description'] ?? '')) ?>">
        </div>
    </div>

    <div class="abas-card mb-6">
        <h2 class="abas-card-title">Anlæg i gruppen</h2>
        <p class="text-sm text-gray-600 mb-4">Søg i cache (standard) eller tilvælg API-søgning. Valgte anlæg bevares når du skifter søgning.</p>

        <div class="flex flex-wrap gap-3 items-end mb-4 max-w-3xl">
            <div class="abas-field flex-1 min-w-[12rem] !mb-0">
                <label class="abas-label" for="ig-search">Søg anlæg</label>
                <input id="ig-search" type="search" class="abas-input font-mono" placeholder="ABA-nr., navn, by …" autocomplete="off">
            </div>
            <button type="button" id="ig-search-btn" class="abas-btn-secondary">Søg</button>
            <label class="flex items-center gap-2 text-sm text-gray-700 pb-2">
                <input type="checkbox" id="ig-use-api" class="abas-checkbox">
                <span>Inkl. TrekantBrand API</span>
            </label>
        </div>
        <p id="ig-search-status" class="text-xs text-gray-500 mb-3"></p>

        <div class="abas-dual-list">
            <div class="abas-dual-list-panel">
                <div class="abas-dual-list-panel-head">Søgeresultat</div>
                <ul id="ig-available" class="abas-dual-list-items" aria-label="Søgeresultat"></ul>
            </div>
            <div class="abas-dual-list-actions" aria-hidden="true">
                <button type="button" class="abas-btn-secondary !px-2" data-ig-action="add-selected" title="Tilføj valgte">&rsaquo;</button>
                <button type="button" class="abas-btn-secondary !px-2" data-ig-action="add-all" title="Tilføj alle viste">&raquo;</button>
                <button type="button" class="abas-btn-secondary !px-2" data-ig-action="remove-selected" title="Fjern valgte">&lsaquo;</button>
                <button type="button" class="abas-btn-secondary !px-2" data-ig-action="remove-all" title="Fjern alle fra gruppe">&laquo;</button>
            </div>
            <div class="abas-dual-list-panel">
                <div class="abas-dual-list-panel-head">I gruppen (<?= count($members) ?>)</div>
                <ul id="ig-selected" class="abas-dual-list-items" aria-label="Anlæg i gruppen"></ul>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-3">Tip: dobbeltklik for at flytte. Træk mellem listerne. Ctrl/⌘+klik for at vælge flere.</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="abas-btn-primary">Gem gruppe</button>
        <a href="<?= htmlspecialchars($listUrl) ?>" class="abas-btn-secondary">Annuller</a>
    </div>
</form>

<div class="abas-card max-w-lg border-red-200 mt-8">
    <h2 class="abas-card-title text-red-800">Slet gruppe</h2>
    <p class="text-sm text-gray-600 mb-3">Gruppen fjernes permanent fra alle brugere.</p>
    <form method="post" onsubmit="return confirm('Slet gruppen permanent?')">
        <input type="hidden" name="id" value="<?= (int) $groupId ?>">
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="abas-btn-danger">Slet gruppe</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.abasInitInstallationGroupEditor === 'function') {
        window.abasInitInstallationGroupEditor({
            searchUrl: <?= json_encode(abas_url('admin/installation-group-search.php'), JSON_UNESCAPED_UNICODE) ?>,
            initialMembers: <?= json_encode($initialMembers, JSON_UNESCAPED_UNICODE) ?>
        });
    }
});
</script>

<?php require __DIR__ . '/../partials/footer.php';
