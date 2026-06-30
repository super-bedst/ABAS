<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/installers.php';
require_once __DIR__ . '/../../includes/users.php';
require_once __DIR__ . '/../../includes/table_list.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$installerId = (int) ($_GET['id'] ?? $_POST['installer_id'] ?? 0);
$sort = abas_table_resolve_sort((string) ($_GET['sort'] ?? $_POST['sort'] ?? ''), abas_installers_sort_columns(), 'company');
$sortDir = abas_table_normalize_sort_dir((string) ($_GET['dir'] ?? $_POST['dir'] ?? 'asc'));
$search = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));

$listUrl = abas_admin_installers_list_url(
    $sort !== 'company' ? $sort : null,
    $sortDir !== 'asc' ? $sortDir : null,
    $search !== '' ? $search : null,
    $page > 1 ? $page : null
);
$selfUrl = abas_admin_installer_edit_url(
    $installerId,
    $sort !== 'company' ? $sort : null,
    $sortDir !== 'asc' ? $sortDir : null,
    $search !== '' ? $search : null,
    $page > 1 ? $page : null
);

$listContextFields = static function () use ($sort, $sortDir, $search, $page): void {
    if ($sort !== 'company') {
        echo '<input type="hidden" name="sort" value="' . htmlspecialchars($sort) . '">';
    }
    if ($sortDir !== 'asc') {
        echo '<input type="hidden" name="dir" value="' . htmlspecialchars($sortDir) . '">';
    }
    if ($search !== '') {
        echo '<input type="hidden" name="q" value="' . htmlspecialchars($search) . '">';
    }
    if ($page > 1) {
        echo '<input type="hidden" name="page" value="' . (int) $page . '">';
    }
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_company') {
        $name = trim((string) ($_POST['company_name'] ?? ''));
        $result = abas_installer_update_company($conn, $installerId, $name);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? ($result['ok'] ? 'Firmanavn opdateret.' : 'Fejl'));
    } elseif ($action === 'add_domain') {
        $domain = trim((string) ($_POST['email_domain'] ?? ''));
        $result = abas_installer_add_domain($conn, $installerId, $domain);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Domæne tilføjet.');
    } elseif ($action === 'remove_domain') {
        $domain = trim((string) ($_POST['email_domain'] ?? ''));
        $result = abas_installer_remove_domain($conn, $installerId, $domain);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? ($result['ok'] ? 'Domæne fjernet.' : 'Fejl'));
    } elseif ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $result = abas_installer_delete_user($conn, $installerId, $userId, (int) $user['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'delete') {
        if (empty($_POST['confirm_delete'])) {
            abas_flash_set('error', 'Bekræft sletning med afkrydsningsfeltet.');
            abas_redirect($selfUrl);
        }
        $result = abas_installer_delete($conn, $installerId);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        abas_redirect($result['ok'] ? $listUrl : $selfUrl);
    }

    abas_redirect($selfUrl);
}

$installer = abas_installer_get($conn, $installerId);
if (!$installer) {
    abas_not_found('Installatørfirmaet findes ikke.', ['installer_id' => $installerId]);
}

$installerUsers = abas_installer_list_users($conn, $installerId);
$companyName = (string) $installer['company_name'];
$domains = $installer['domains'] ?? [];
$montorCount = (int) ($installer['montor_count'] ?? 0);
$hasPlaceholderDomain = false;
foreach ($domains as $domain) {
    if (str_ends_with(strtolower((string) $domain), '.trekantbrand-import.local')) {
        $hasPlaceholderDomain = true;
        break;
    }
}

$pageTitle = $companyName;
$adminSectionTitle = $companyName;
$adminSectionLead = 'Virksomhedsoplysninger, domæner, brugere og anlægsadgange.';
$adminNavSection = 'installers.php';
$currentUser = $user;
require __DIR__ . '/../partials/admin_shell_start.php';
?>
<div class="mb-4"><a href="<?= htmlspecialchars($listUrl) ?>" class="abas-back-link">&larr; Installatører</a></div>

<div class="grid lg:grid-cols-2 gap-6 mb-8 max-w-5xl">
    <form method="post" class="abas-card abas-form">
        <input type="hidden" name="action" value="update_company">
        <input type="hidden" name="installer_id" value="<?= $installerId ?>">
        <?php $listContextFields(); ?>
        <h2 class="abas-card-title">Firmanavn</h2>
        <div class="abas-field">
            <label class="abas-label" for="company_name">Navn</label>
            <input id="company_name" name="company_name" required class="abas-input" value="<?= htmlspecialchars($companyName) ?>">
        </div>
        <button class="abas-btn-primary">Gem navn</button>
    </form>

    <div class="abas-card">
        <h2 class="abas-card-title">E-mail-domæner</h2>
        <p class="text-sm text-gray-600 mb-4">Montører matches på domæne ved registrering. Firmaet skal have mindst ét domæne.</p>
        <?php if ($hasPlaceholderDomain): ?>
            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mb-4">Import-placeholder domæne — tilføj rigtigt domæne.</p>
        <?php endif; ?>
        <?php if ($domains === []): ?>
            <p class="text-sm text-amber-700 mb-4">Ingen domæner tilknyttet.</p>
        <?php else: ?>
            <ul class="space-y-2 mb-4">
                <?php foreach ($domains as $domain):
                    $isPlaceholder = str_ends_with(strtolower((string) $domain), '.trekantbrand-import.local');
                    ?>
                    <li class="flex flex-wrap items-center gap-2">
                        <span class="abas-badge <?= $isPlaceholder ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-gray-100 text-gray-800 border border-gray-200' ?> font-mono text-xs"><?= htmlspecialchars((string) $domain) ?></span>
                        <?php if (count($domains) > 1): ?>
                        <form method="post" class="inline" onsubmit="return confirm('Fjern domænet <?= htmlspecialchars((string) $domain, ENT_QUOTES) ?>?');">
                            <input type="hidden" name="action" value="remove_domain">
                            <input type="hidden" name="installer_id" value="<?= $installerId ?>">
        <?php $listContextFields(); ?>
                            <input type="hidden" name="email_domain" value="<?= htmlspecialchars((string) $domain) ?>">
                            <button type="submit" class="text-xs text-red-700 hover:underline">Fjern</button>
                        </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" class="flex flex-wrap gap-2 items-end">
            <input type="hidden" name="action" value="add_domain">
            <input type="hidden" name="installer_id" value="<?= $installerId ?>">
        <?php $listContextFields(); ?>
            <div class="abas-field flex-1 min-w-[10rem] !mb-0">
                <label class="sr-only" for="new_domain">Nyt domæne</label>
                <input id="new_domain" name="email_domain" required placeholder="firma.dk" class="abas-input text-sm">
            </div>
            <button class="abas-btn-secondary text-sm">Tilføj domæne</button>
        </form>
    </div>
</div>

<div class="abas-card mb-8">
    <h2 class="abas-card-title">Brugere (<?= count($installerUsers) ?>)</h2>
    <p class="text-sm text-gray-600 mb-4">Montører og installatøradministratorer tilknyttet firmaet.</p>
    <?php if ($installerUsers === []): ?>
        <p class="text-gray-500 text-sm">Ingen brugere endnu.</p>
    <?php else: ?>
    <div class="abas-table-wrap">
        <table class="abas-table">
            <thead>
                <tr>
                    <th scope="col">Navn</th>
                    <th scope="col">Rolle</th>
                    <th scope="col">Telefon</th>
                    <th scope="col">Status</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($installerUsers as $installerUser):
                $userId = (int) $installerUser['id'];
                $displayName = trim((string) ($installerUser['registration_display_name'] ?? ''));
                if ($displayName === '') {
                    $displayName = (string) ($installerUser['username'] ?? $installerUser['email'] ?? '');
                }
                $deleteConfirm = 'Slet brugeren «' . $displayName . '»?\n\n'
                    . (empty($installerUser['active']) ? 'Brugeren er allerede inaktiv og har servicehistorik — deaktiveres permanent.' : 'Brugeren fjernes permanent hvis der ikke er servicehistorik.');
                ?>
                <tr>
                    <td>
                        <a href="<?= htmlspecialchars(abas_admin_user_edit_url($userId)) ?>" class="font-medium text-brand hover:underline">
                            <?= htmlspecialchars($displayName) ?>
                        </a>
                        <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars((string) ($installerUser['email'] ?? '')) ?></div>
                    </td>
                    <td class="text-sm"><?= htmlspecialchars(abas_role_label((string) ($installerUser['role'] ?? ''))) ?></td>
                    <td class="text-sm whitespace-nowrap"><?= htmlspecialchars((string) ($installerUser['phone'] ?? '—')) ?></td>
                    <td>
                        <?php if (!empty($installerUser['active'])): ?>
                            <span class="abas-badge abas-badge-ok">Aktiv</span>
                        <?php else: ?>
                            <span class="abas-badge abas-badge-rejected">Inaktiv</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <a href="<?= htmlspecialchars(abas_admin_user_edit_url($userId)) ?>" class="abas-btn-secondary !py-1 !px-2 text-xs">Rediger</a>
                        <form method="post" class="inline ml-1" onsubmit="return confirm(<?= json_encode($deleteConfirm, JSON_UNESCAPED_UNICODE) ?>);">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="installer_id" value="<?= $installerId ?>">
        <?php $listContextFields(); ?>
                            <input type="hidden" name="user_id" value="<?= $userId ?>">
                            <button type="submit" class="abas-btn-danger !py-1 !px-2 text-xs">Slet</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="abas-card mb-8">
    <h2 class="abas-card-title">Anlægsadgange</h2>
    <p class="text-sm text-gray-600 mb-4">Oversigt over direkte anlæg og anlægsgrupper pr. montør og installatøradministrator. Rediger adgang via brugerens profil.</p>
    <?php if ($installerUsers === []): ?>
        <p class="text-gray-500 text-sm">Ingen brugere at vise adgang for.</p>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($installerUsers as $installerUser):
            if (!abas_user_role_supports_optional_installation_scope((string) ($installerUser['role'] ?? ''))) {
                continue;
            }
            $userId = (int) $installerUser['id'];
            $displayName = trim((string) ($installerUser['registration_display_name'] ?? ''));
            if ($displayName === '') {
                $displayName = (string) ($installerUser['username'] ?? $installerUser['email'] ?? '');
            }
            $access = abas_installer_user_access_summary($conn, $installerUser);
            ?>
            <details class="abas-collapsible border border-gray-200 rounded-xl overflow-hidden">
                <summary class="abas-collapsible-summary px-4 py-3 bg-gray-50/80 font-medium text-sm cursor-pointer">
                    <?= htmlspecialchars($displayName) ?>
                    <span class="text-xs text-gray-500 font-normal ml-1">· <?= htmlspecialchars(abas_role_label((string) ($installerUser['role'] ?? ''))) ?></span>
                    <?php if ($access['mode'] === 'full'): ?>
                        <span class="abas-badge abas-badge-ok ml-2">Fuld adgang</span>
                    <?php elseif ($access['direct'] === [] && $access['groups'] === []): ?>
                        <span class="abas-badge abas-badge-access-denied ml-2">Ingen tilknytninger</span>
                    <?php else: ?>
                        <span class="text-xs text-gray-500 font-normal ml-2">
                            <?= count($access['direct']) ?> direkte · <?= count($access['groups']) ?> grupper
                        </span>
                    <?php endif; ?>
                </summary>
                <div class="px-4 py-3 text-sm border-t border-gray-100">
                    <?php if ($access['mode'] === 'full'): ?>
                        <p class="text-gray-600 mb-0">Brugeren har fuld adgang til alle anlæg (begrænsning ikke aktiveret).</p>
                    <?php else: ?>
                        <?php if (!empty($installerUser['montor_scoped_access'])): ?>
                            <p class="text-xs text-amber-800 mb-3">Begrænset adgang er aktiveret — brugeren ser kun nedenstående.</p>
                        <?php endif; ?>
                        <?php if ($access['groups'] !== []): ?>
                            <p class="font-semibold text-gray-800 mb-1">Anlægsgrupper</p>
                            <ul class="list-disc pl-5 mb-3 space-y-1">
                                <?php foreach ($access['groups'] as $group): ?>
                                    <li>
                                        <?= htmlspecialchars((string) $group['name']) ?>
                                        <span class="text-gray-500 font-mono text-xs">(<?= htmlspecialchars((string) $group['public_id']) ?> · <?= (int) ($group['member_count'] ?? 0) ?> anlæg)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($access['direct'] !== []): ?>
                            <p class="font-semibold text-gray-800 mb-1">Direkte anlæg</p>
                            <ul class="list-disc pl-5 space-y-1 font-mono text-xs">
                                <?php foreach ($access['direct'] as $installation): ?>
                                    <li>
                                        <?= htmlspecialchars((string) $installation['miscno2']) ?>
                                        <span class="text-gray-600 font-sans"><?= htmlspecialchars((string) $installation['name']) ?><?= ($installation['city'] ?? '') !== '' ? ', ' . htmlspecialchars((string) $installation['city']) : '' ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($access['groups'] === [] && $access['direct'] === []): ?>
                            <p class="text-gray-500 mb-0">Ingen grupper eller direkte anlæg tilknyttet.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <p class="mt-3 mb-0"><a href="<?= htmlspecialchars(abas_admin_user_edit_url($userId)) ?>" class="text-brand text-sm hover:underline">Rediger adgang →</a></p>
                </div>
            </details>
        <?php endforeach; ?>
        <?php
        $hasMontors = false;
        foreach ($installerUsers as $installerUser) {
            if ((string) ($installerUser['role'] ?? '') === 'montor') {
                $hasMontors = true;
                break;
            }
        }
        if (!$hasMontors):
        ?>
            <p class="text-gray-500 text-sm">Ingen montører — anlægsadgang gælder kun montører.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="abas-card border-red-200 bg-red-50/40 max-w-2xl">
    <h2 class="abas-card-title text-red-900">Slet firma</h2>
    <p class="text-sm text-red-900/90 mb-4">Sletter firmaet permanent og fjerner alle <?= $montorCount ?> tilknyttede bruger(e). Domæner slettes sammen med firmaet.</p>
    <?php
    $deleteConfirm = 'ADVARSEL: Slet firmaet "' . $companyName . '"?\n\n'
        . 'Alle ' . $montorCount . ' montør(er)/installatøradministrator(er) tilknyttet firmaet fjernes permanent.\n\n'
        . 'Domæner: ' . ($domains !== [] ? implode(', ', $domains) : '(ingen)');
    ?>
    <form method="post" class="abas-form" onsubmit="return confirm(<?= json_encode($deleteConfirm, JSON_UNESCAPED_UNICODE) ?>);">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="installer_id" value="<?= $installerId ?>">
        <?php $listContextFields(); ?>
        <label class="flex items-start gap-2 text-sm text-red-900 mb-4">
            <input type="checkbox" name="confirm_delete" value="1" class="abas-checkbox mt-0.5" required>
            <span>Jeg forstår at firmaet og alle tilknyttede brugere slettes permanent.</span>
        </label>
        <button type="submit" class="abas-btn-danger">Slet firma</button>
    </form>
</div>

<?php require __DIR__ . '/../partials/admin_shell_end.php';
