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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/password_flow.php';
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND installer_id = ? LIMIT 1');
    $stmt->bind_param('ii', $targetId, $installerId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$target || !abas_virksomhedsadmin_may_manage_user($actor, $target)) {
        abas_flash_set('error', 'Ingen adgang til brugeren.');
        abas_redirect('virksomhed/users.php');
    }

    if ($action === 'delete') {
        $result = abas_delete_user($conn, $targetId, (int) $actor['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        abas_redirect('virksomhed/users.php');
    }

    if ($action === 'reset_password') {
        abas_password_send_flow_email($conn, $targetId, 'reset');
        abas_flash_set('success', 'Nulstillings-e-mail sendt.');
    }
    abas_redirect('virksomhed/users.php');
}

$sort = abas_table_resolve_sort((string) ($_GET['sort'] ?? ''), abas_virksomhed_users_sort_columns(), 'name');
$sortDir = abas_table_normalize_sort_dir((string) ($_GET['dir'] ?? 'asc'));
$search = trim((string) ($_GET['q'] ?? ''));
$listQuery = array_filter(['q' => $search !== '' ? $search : null, 'sort' => $sort !== 'name' ? $sort : null, 'dir' => $sortDir !== 'asc' ? $sortDir : null]);

$users = abas_list_virksomhed_installer_users($conn, $installerId, $sort, $sortDir, $search);

$actorId = (int) $actor['id'];
$companyName = abas_user_company_name($conn, $actor);
abas_session_release();

$pageTitle = 'Virksomhedsbrugere';
$currentUser = $actor;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="abas-page-title">Virksomhedsbrugere</h1>
<p class="abas-page-lead">Montører, virksomhedsadministratorer og øvrige brugere hos <?= htmlspecialchars($companyName) ?>.</p>

<form method="get" class="mb-4 flex flex-wrap gap-2 items-end max-w-2xl" role="search">
    <div class="abas-field flex-1 min-w-[14rem] !mb-0">
        <label class="abas-label" for="user-search">Søg</label>
        <input id="user-search" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Navn, e-mail, telefon, rolle, status …" class="abas-input">
    </div>
    <?php if ($sort !== 'name'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
    <?php if ($sortDir !== 'asc'): ?><input type="hidden" name="dir" value="<?= htmlspecialchars($sortDir) ?>"><?php endif; ?>
    <button type="submit" class="abas-btn-secondary">Søg</button>
    <?php if ($search !== ''): ?>
        <a href="<?= htmlspecialchars(abas_virksomhed_users_page_url(['sort' => $sort !== 'name' ? $sort : null, 'dir' => $sortDir !== 'asc' ? $sortDir : null])) ?>" class="abas-btn-secondary">Ryd</a>
    <?php endif; ?>
</form>
<?php if ($search !== ''): ?>
<p class="text-sm text-gray-600 mb-4"><?= count($users) ?> resultat<?= count($users) === 1 ? '' : 'er' ?> for «<?= htmlspecialchars($search) ?>»</p>
<?php endif; ?>

<div class="abas-table-wrap mt-6">
    <table class="abas-table">
        <thead>
            <tr>
                <?php abas_render_table_sort_th('Navn', abas_table_sort_link('virksomhed/users.php', $listQuery, 'name', $sort, $sortDir, abas_virksomhed_users_sort_columns())); ?>
                <?php abas_render_table_sort_th('E-mail', abas_table_sort_link('virksomhed/users.php', $listQuery, 'email', $sort, $sortDir, abas_virksomhed_users_sort_columns())); ?>
                <?php abas_render_table_sort_th('Telefon', abas_table_sort_link('virksomhed/users.php', $listQuery, 'phone', $sort, $sortDir, abas_virksomhed_users_sort_columns())); ?>
                <?php abas_render_table_sort_th('Rolle', abas_table_sort_link('virksomhed/users.php', $listQuery, 'role', $sort, $sortDir, abas_virksomhed_users_sort_columns())); ?>
                <?php abas_render_table_sort_th('Status', abas_table_sort_link('virksomhed/users.php', $listQuery, 'active', $sort, $sortDir, abas_virksomhed_users_sort_columns())); ?>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($users === []): ?>
            <tr>
                <td colspan="6" class="text-gray-500 text-sm p-4">
                    <?= $search !== '' ? 'Ingen brugere matcher søgningen.' : 'Ingen brugere tilknyttet virksomheden endnu.' ?>
                </td>
            </tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
            <tr<?= (int) $u['id'] === $actorId ? ' class="bg-basbg/40"' : '' ?>>
                <td><?= htmlspecialchars(abas_user_display_name($u)) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars((string) $u['phone']) ?></td>
                <td><?= htmlspecialchars(abas_role_label($u['role'])) ?></td>
                <td>
                    <?php if ($u['active']): ?>
                        <span class="abas-badge-ok">Aktiv</span>
                    <?php else: ?>
                        <span class="abas-badge bg-gray-100 text-gray-600 border-gray-200">Inaktiv</span>
                    <?php endif; ?>
                </td>
                <td class="whitespace-nowrap">
                    <a href="<?= abas_url('virksomhed/user-edit.php?id=' . (int) $u['id']) ?>" class="abas-link text-sm">Rediger</a>
                    <?php if ((int) $u['id'] !== $actorId): ?>
                    <form method="post" class="inline ml-2" onsubmit="return confirm('Slet eller deaktiver <?= htmlspecialchars(abas_user_display_name($u), ENT_QUOTES) ?>?')">
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="text-sm text-red-700 hover:underline">Slet</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php';
