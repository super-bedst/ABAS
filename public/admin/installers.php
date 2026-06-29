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
        $name = trim($_POST['company_name'] ?? '');
        $domain = trim($_POST['email_domain'] ?? '');
        $result = abas_installer_create($conn, $name, $domain, (int) $user['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? ($result['ok'] ? 'Firma oprettet.' : 'Fejl'));
    } elseif ($action === 'add_domain') {
        $installerId = (int) ($_POST['installer_id'] ?? 0);
        $domain = trim($_POST['email_domain'] ?? '');
        $result = abas_installer_add_domain($conn, $installerId, $domain);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Domæne tilføjet.');
    } elseif ($action === 'delete') {
        $installerId = (int) ($_POST['installer_id'] ?? 0);
        $result = abas_installer_delete($conn, $installerId);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    }

    abas_redirect($redirectUrl);
}

$listResult = abas_list_installers_page($conn, $search, $sort, $sortDir, $page);
$rows = $listResult['rows'];
$totalInstallers = $listResult['total'];
$totalPages = $listResult['totalPages'];
$page = $listResult['page'];
$pageTitle = 'Installatører';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2"><a href="<?= abas_url('admin/index.php') ?>" class="abas-back-link">&larr; Admin</a></div>
<h1 class="abas-page-title !text-xl">Godkendte installatører</h1>
<p class="abas-page-lead">Et firma kan have flere e-mail-domæner. Montører matches på domæne ved registrering.</p>

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
    <button class="abas-btn-primary">Opret firma</button>
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
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r):
            $installerId = (int) $r['id'];
            $montorCount = (int) ($r['montor_count'] ?? 0);
            $companyName = (string) $r['company_name'];
            $domains = $r['domains'] ?? [];
            $hasPlaceholderDomain = false;
            foreach ($domains as $domain) {
                if (str_ends_with(strtolower((string) $domain), '.trekantbrand-import.local')) {
                    $hasPlaceholderDomain = true;
                    break;
                }
            }
            $deleteConfirm = 'ADVARSEL: Slet firmaet "' . $companyName . '"?\n\n'
                . 'Alle ' . $montorCount . ' montør(er)/virksomhedsadmin(s) tilknyttet firmaet fjernes permanent.\n\n'
                . 'Domæner: ' . ($domains !== [] ? implode(', ', $domains) : '(ingen)');
            ?>
            <tr>
                <td>
                    <div class="font-semibold text-brand"><?= htmlspecialchars($companyName) ?></div>
                    <?php if ($hasPlaceholderDomain): ?>
                        <p class="text-xs text-amber-800 mt-1">Import-placeholder domæne — tilføj rigtigt domæne.</p>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($domains === []): ?>
                        <span class="text-sm text-amber-700">Ingen domæner</span>
                    <?php else: ?>
                        <ul class="flex flex-wrap gap-1.5">
                            <?php foreach ($domains as $domain):
                                $isPlaceholder = str_ends_with(strtolower((string) $domain), '.trekantbrand-import.local');
                                ?>
                                <li class="abas-badge <?= $isPlaceholder ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-gray-100 text-gray-800 border border-gray-200' ?> font-mono text-xs"><?= htmlspecialchars($domain) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </td>
                <td class="whitespace-nowrap text-gray-700"><?= $montorCount ?></td>
                <td class="whitespace-nowrap align-top">
                    <details class="abas-collapsible inline-block mr-3">
                        <summary class="abas-link text-sm abas-collapsible-summary">Tilføj domæne</summary>
                        <form method="post" class="mt-2 flex flex-wrap gap-2 items-end min-w-[14rem]">
                            <input type="hidden" name="action" value="add_domain">
                            <input type="hidden" name="installer_id" value="<?= $installerId ?>">
                            <div class="abas-field flex-1 min-w-[10rem] !mb-0">
                                <label class="sr-only">Nyt domæne</label>
                                <input name="email_domain" required placeholder="andet-domæne.dk" class="abas-input text-sm">
                            </div>
                            <button class="abas-btn-secondary text-sm">Tilføj</button>
                        </form>
                    </details>
                    <form method="post" class="inline"
                          onsubmit="return confirm(<?= json_encode($deleteConfirm, JSON_UNESCAPED_UNICODE) ?>);">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="installer_id" value="<?= $installerId ?>">
                        <button type="submit" class="text-sm text-red-700 hover:underline">Slet</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php abas_render_table_pagination('admin/installers.php', $listQueryBase, $page, $totalPages); ?>
<?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php';
