<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/installers.php';
require_once __DIR__ . '/../../includes/table_list.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$sort = abas_table_resolve_sort((string) ($_GET['sort'] ?? ''), abas_installers_sort_columns(), 'company');
$sortDir = abas_table_normalize_sort_dir((string) ($_GET['dir'] ?? 'asc'));
$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$listQuery = array_filter([
    'q' => $search !== '' ? $search : null,
    'sort' => $sort !== 'company' ? $sort : null,
    'dir' => $sortDir !== 'asc' ? $sortDir : null,
]);
$listQueryBase = $listQuery;
$redirectUrl = abas_admin_installers_list_url(
    $sort !== 'company' ? $sort : null,
    $sortDir !== 'asc' ? $sortDir : null,
    $search !== '' ? $search : null,
    $page > 1 ? $page : null
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim((string) ($_POST['company_name'] ?? ''));
        $domain = trim((string) ($_POST['email_domain'] ?? ''));
        $result = abas_installer_create($conn, $name, $domain, (int) $user['id']);
        if ($result['ok'] && !empty($result['id'])) {
            abas_flash_set('success', 'Firma oprettet.');
            abas_redirect(abas_admin_installer_edit_url((int) $result['id']));
        }
        abas_flash_set('error', $result['message'] ?? ($result['ok'] ? 'Firma oprettet.' : 'Fejl'));
    }

    abas_redirect($redirectUrl);
}

$listResult = abas_list_installers_page($conn, $search, $sort, $sortDir, $page);
$rows = $listResult['rows'];
$totalInstallers = $listResult['total'];
$totalPages = $listResult['totalPages'];
$page = $listResult['page'];
$pageTitle = 'Installatører';
$adminSectionTitle = 'Godkendte installatører';
$adminSectionLead = 'Et firma kan have flere e-mail-domæner. Klik på et firma for at redigere detaljer, brugere og anlægsadgange.';
$currentUser = $user;
require __DIR__ . '/../partials/admin_shell_start.php';
?>

<form method="post" class="abas-card mb-6 max-w-lg abas-form">
    <input type="hidden" name="action" value="create">
    <h2 class="abas-card-title">Opret nyt firma</h2>
    <div class="abas-field">
        <label class="abas-label" for="company_name">Firmanavn</label>
        <input id="company_name" name="company_name" required class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="new_email_domain">Første e-mail-domæne</label>
        <input id="new_email_domain" name="email_domain" required placeholder="firma.dk" class="abas-input">
    </div>
    <button class="abas-btn-primary">Opret og åbn detaljer</button>
</form>

<form method="get" class="mb-4 flex flex-wrap gap-2 items-end max-w-2xl" role="search" data-abas-loading="Søger…">
    <div class="abas-field flex-1 min-w-[14rem] !mb-0">
        <label class="abas-label" for="installer-search">Søg</label>
        <input id="installer-search" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Firmanavn eller e-mail-domæne …" class="abas-input">
    </div>
    <?php if ($sort !== 'company'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
    <?php if ($sortDir !== 'asc'): ?><input type="hidden" name="dir" value="<?= htmlspecialchars($sortDir) ?>"><?php endif; ?>
    <button type="submit" class="abas-btn-secondary">Søg</button>
    <?php if ($search !== ''): ?>
        <a href="<?= htmlspecialchars(abas_admin_installers_list_url($sort !== 'company' ? $sort : null, $sortDir !== 'asc' ? $sortDir : null)) ?>" class="abas-btn-secondary">Ryd</a>
    <?php endif; ?>
</form>
<?php if ($search !== '' || $totalInstallers > count($rows)): ?>
    <p class="text-sm text-gray-600 mb-4">
        <?= $totalInstallers ?> firma<?= $totalInstallers === 1 ? '' : 'er' ?><?= $search !== '' ? ' matcher «' . htmlspecialchars($search) . '»' : '' ?>
        <?php if ($totalPages > 1): ?> · side <?= (int) $page ?> af <?= (int) $totalPages ?><?php endif; ?>
    </p>
<?php endif; ?>

<?php if ($rows === []): ?>
    <p class="text-gray-500"><?= $search !== '' ? 'Ingen firmaer matcher søgningen.' : 'Ingen godkendte installatører endnu.' ?></p>
<?php else: ?>
<div class="abas-table-wrap">
    <table class="abas-table">
        <thead>
            <tr>
                <?php abas_render_table_sort_th('Firma', abas_table_sort_link('admin/installers.php', $listQuery, 'company', $sort, $sortDir, abas_installers_sort_columns())); ?>
                <th scope="col">E-mail-domæner</th>
                <?php abas_render_table_sort_th('Brugere', abas_table_sort_link('admin/installers.php', $listQuery, 'montor_count', $sort, $sortDir, abas_installers_sort_columns())); ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $installerId = (int) $r['id'];
            $companyName = (string) $r['company_name'];
            $domains = $r['domains'] ?? [];
            $editUrl = abas_admin_installer_edit_url(
                $installerId,
                $sort !== 'company' ? $sort : null,
                $sortDir !== 'asc' ? $sortDir : null,
                $search !== '' ? $search : null,
                $page > 1 ? $page : null
            );
            $domainPreview = $domains !== [] ? implode(', ', array_slice($domains, 0, 3)) : '—';
            if (count($domains) > 3) {
                $domainPreview .= ' …';
            }
            ?>
            <tr class="abas-table-row-link"
                role="link"
                tabindex="0"
                data-href="<?= htmlspecialchars($editUrl) ?>"
                data-abas-loading="Åbner firma…">
                <td class="font-medium text-brand"><?= htmlspecialchars($companyName) ?></td>
                <td class="text-sm text-gray-600 font-mono"><?= htmlspecialchars($domainPreview) ?></td>
                <td class="whitespace-nowrap text-gray-700"><?= (int) ($r['montor_count'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php abas_render_table_pagination('admin/installers.php', $listQueryBase, $page, $totalPages); ?>
<?php endif; ?>
<?php require __DIR__ . '/../partials/admin_shell_end.php';
