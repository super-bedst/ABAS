<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/installation_groups.php';
require_once __DIR__ . '/../../includes/table_list.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$sort = abas_table_resolve_sort((string) ($_GET['sort'] ?? ''), abas_installation_groups_sort_columns(), 'name');
$sortDir = abas_table_normalize_sort_dir((string) ($_GET['dir'] ?? 'asc'));
$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$listQuery = array_filter([
    'q' => $search !== '' ? $search : null,
    'sort' => $sort !== 'name' ? $sort : null,
    'dir' => $sortDir !== 'asc' ? $sortDir : null,
]);
$redirectUrl = abas_admin_installation_groups_list_url(
    $sort !== 'name' ? $sort : null,
    $sortDir !== 'asc' ? $sortDir : null,
    $search !== '' ? $search : null,
    $page > 1 ? $page : null
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = abas_installation_group_create(
            $conn,
            (string) ($_POST['name'] ?? ''),
            (string) ($_POST['description'] ?? ''),
            (int) $user['id']
        );
        if ($result['ok'] && !empty($result['id'])) {
            abas_flash_set('success', 'Anlægsgruppe oprettet.');
            abas_redirect(abas_admin_installation_group_edit_url((int) $result['id']));
        }
        abas_flash_set('error', $result['message'] ?? 'Kunne ikke oprette gruppen.');
    } elseif ($action === 'delete') {
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $result = abas_installation_group_delete($conn, $groupId);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    }

    abas_redirect($redirectUrl);
}

$listResult = abas_list_installation_groups_page($conn, $search, $sort, $sortDir, $page);
$rows = $listResult['rows'];
$totalGroups = $listResult['total'];
$totalPages = $listResult['totalPages'];
$page = $listResult['page'];

$pageTitle = 'Anlægsgrupper';
$adminSectionTitle = 'Anlægsgrupper';
$adminSectionLead = 'Saml anlæg i grupper og tilknyt dem til montører eller anlægsbrugere. Gruppenavne behøver ikke være unikke.';
$currentUser = $user;
require __DIR__ . '/../partials/admin_shell_start.php';
?>

<form method="post" class="abas-card mb-6 max-w-lg abas-form">
    <input type="hidden" name="action" value="create">
    <h2 class="abas-card-title">Opret gruppe</h2>
    <div class="abas-field">
        <label class="abas-label" for="group-name">Navn / label</label>
        <input id="group-name" name="name" required class="abas-input" placeholder="Fx Region Nord, Kunde X …">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="group-description">Beskrivelse (valgfri)</label>
        <textarea id="group-description" name="description" rows="2" class="abas-textarea" placeholder="Intern note"></textarea>
    </div>
    <button class="abas-btn-primary">Opret og rediger anlæg</button>
</form>

<form method="get" class="mb-4 flex flex-wrap gap-2 items-end max-w-2xl" role="search" data-abas-loading="Søger…">
    <div class="abas-field flex-1 min-w-[14rem] !mb-0">
        <label class="abas-label" for="group-search">Søg grupper</label>
        <input id="group-search" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Navn, UUID eller beskrivelse …" class="abas-input">
    </div>
    <?php if ($sort !== 'name'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
    <?php if ($sortDir !== 'asc'): ?><input type="hidden" name="dir" value="<?= htmlspecialchars($sortDir) ?>"><?php endif; ?>
    <button type="submit" class="abas-btn-secondary">Søg</button>
    <?php if ($search !== ''): ?>
        <a href="<?= htmlspecialchars(abas_admin_installation_groups_list_url($sort !== 'name' ? $sort : null, $sortDir !== 'asc' ? $sortDir : null)) ?>" class="abas-btn-secondary">Ryd</a>
    <?php endif; ?>
</form>

<?php if ($search !== '' || $totalGroups > count($rows)): ?>
    <p class="text-sm text-gray-600 mb-4">
        <?= (int) $totalGroups ?> gruppe<?= $totalGroups === 1 ? '' : 'r' ?><?= $search !== '' ? ' matcher «' . htmlspecialchars($search) . '»' : '' ?>
        <?php if ($totalPages > 1): ?> · side <?= (int) $page ?> af <?= (int) $totalPages ?><?php endif; ?>
    </p>
<?php endif; ?>

<?php if ($rows === []): ?>
    <p class="text-gray-500"><?= $search !== '' ? 'Ingen grupper matcher søgningen.' : 'Ingen anlægsgrupper endnu.' ?></p>
<?php else: ?>
<div class="abas-table-wrap">
    <table class="abas-table">
        <thead>
            <tr>
                <?php abas_render_table_sort_th('Navn', abas_table_sort_link('admin/installation-groups.php', $listQuery, 'name', $sort, $sortDir, abas_installation_groups_sort_columns())); ?>
                <th scope="col">UUID</th>
                <?php abas_render_table_sort_th('Anlæg', abas_table_sort_link('admin/installation-groups.php', $listQuery, 'members', $sort, $sortDir, abas_installation_groups_sort_columns())); ?>
                <?php abas_render_table_sort_th('Brugere', abas_table_sort_link('admin/installation-groups.php', $listQuery, 'users', $sort, $sortDir, abas_installation_groups_sort_columns())); ?>
                <?php abas_render_table_sort_th('Opdateret', abas_table_sort_link('admin/installation-groups.php', $listQuery, 'updated', $sort, $sortDir, abas_installation_groups_sort_columns())); ?>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <a href="<?= htmlspecialchars(abas_admin_installation_group_edit_url((int) $row['id'])) ?>" class="font-medium text-brand hover:underline">
                            <?= htmlspecialchars((string) $row['name']) ?>
                        </a>
                        <?php if (!empty($row['description'])): ?>
                            <div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars((string) $row['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="font-mono text-xs text-gray-500"><?= htmlspecialchars((string) $row['public_id']) ?></td>
                    <td><?= (int) ($row['member_count'] ?? 0) ?></td>
                    <td><?= (int) ($row['user_count'] ?? 0) ?></td>
                    <td class="text-sm text-gray-600 whitespace-nowrap"><?= htmlspecialchars(abas_format_datetime((string) ($row['updated_at'] ?? ''))) ?></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="<?= htmlspecialchars(abas_admin_installation_group_edit_url((int) $row['id'])) ?>" class="abas-btn-secondary !py-1 !px-2 text-xs">Rediger</a>
                        <form method="post" class="inline ml-1" onsubmit="return confirm('Slet gruppen «<?= htmlspecialchars((string) $row['name'], ENT_QUOTES) ?>»? Den fjernes fra alle brugere.')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="group_id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" class="abas-btn-danger !py-1 !px-2 text-xs">Slet</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($totalPages > 1): ?>
    <div class="mt-4 flex flex-wrap gap-2">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <?php if ($p === $page): ?>
                <span class="abas-btn-secondary !py-1 !px-2 text-xs opacity-60"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= htmlspecialchars(abas_admin_installation_groups_list_url($sort !== 'name' ? $sort : null, $sortDir !== 'asc' ? $sortDir : null, $search !== '' ? $search : null, $p)) ?>" class="abas-btn-secondary !py-1 !px-2 text-xs"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_shell_end.php';
