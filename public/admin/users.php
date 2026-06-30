<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/password_flow.php';
require_once __DIR__ . '/../../includes/users.php';
require_once __DIR__ . '/../../includes/installation_sync.php';
require_once __DIR__ . '/../../includes/service.php';
require_once __DIR__ . '/../../includes/table_list.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

/** @var array<string, list<string>> */
$filterGroups = [
    'alle' => abas_roles(),
    'admin' => ['admin'],
    'vagtcentral' => ['vagtcentral'],
    'montor' => ['montor'],
    'anlaegsbrugere' => ['anlaegsejer', 'anlaegsafprover'],
    'virksomhedsadmin' => ['virksomhedsadmin'],
];

/** @var array<string, string> */
$filterLabels = [
    'alle' => 'Alle',
    'admin' => 'Administratorer',
    'vagtcentral' => 'Vagtcentral',
    'montor' => 'Montører',
    'anlaegsbrugere' => 'Anlægsbrugere',
    'virksomhedsadmin' => 'Virksomhedsadmin',
];

$filter = $_GET['filter'] ?? 'alle';
if (!isset($filterGroups[$filter])) {
    $filter = 'alle';
}
$rolesInFilter = $filterGroups[$filter];

$sort = (string) ($_GET['sort'] ?? '');
if (!in_array($sort, abas_admin_users_sort_columns(), true)) {
    $sort = '';
}
$sortDir = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));

$redirectUrl = abas_admin_users_list_url(
    $filter,
    $sort !== '' ? $sort : null,
    $sort !== '' ? $sortDir : null,
    $search !== '' ? $search : null,
    $page > 1 ? $page : null
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId > 0) {
            $result = abas_delete_user($conn, $targetId, (int) $user['id']);
            abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        }
        abas_redirect($redirectUrl);
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = abas_resolve_username_for_email($conn, $email, (string) ($_POST['username'] ?? ''));
    $displayName = trim($_POST['registration_display_name'] ?? '');
    $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
    $role = $_POST['role'] ?? 'montor';
    $miscno2 = strtolower(trim($_POST['miscno2'] ?? ''));
    if (!in_array($role, abas_roles(), true)) {
        $role = 'montor';
    }
    if (!abas_validate_phone($phone)) {
        abas_flash_set('error', 'Angiv et gyldigt telefonnummer (min. 8 cifre).');
        abas_redirect($redirectUrl);
    }

    $smsCode = trim($_POST['sms_code'] ?? '');
    $smsAllowed = !empty($_POST['sms_service_allowed']) ? 1 : 0;
    if (abas_user_sms_service_code_required_on_create($role, $smsAllowed === 1, $smsCode)) {
        abas_flash_set('error', 'SMS-kode skal være mindst 6 tegn når SMS-betjening er aktiveret.');
        abas_redirect($redirectUrl);
    }

    $installerId = null;
    if ($role === 'montor' || $role === 'virksomhedsadmin') {
        $installerId = abas_assign_installer_for_montor($conn, $email);
        if ($installerId === null) {
            $msg = $role === 'virksomhedsadmin'
                ? 'Virksomhedsadmin skal have e-mail fra et godkendt installatør-domæne.'
                : 'Montør skal have e-mail fra et godkendt installatør-domæne.';
            abas_flash_set('error', $msg);
            abas_redirect($redirectUrl);
        }
    }

    $smsAllowed = !empty($_POST['sms_service_allowed']) ? 1 : 0;
    $sendWelcome = !empty($_POST['send_welcome']);

    $chk = $conn->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
    $chk->bind_param('ss', $email, $username);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        abas_flash_set('error', 'Bruger findes.');
    } else {
        $adminId = (int) $user['id'];
        if ($installerId !== null) {
            $stmt = $conn->prepare('INSERT INTO users (email, username, role, phone, installer_id, active, sms_service_allowed, registration_status, registration_display_name, created_by_user_id) VALUES (?, ?, ?, ?, ?, 1, ?, "approved", ?, ?)');
            $stmt->bind_param('ssssiisi', $email, $username, $role, $phone, $installerId, $smsAllowed, $displayName, $adminId);
        } else {
            $stmt = $conn->prepare('INSERT INTO users (email, username, role, phone, active, sms_service_allowed, registration_status, registration_display_name, created_by_user_id) VALUES (?, ?, ?, ?, 1, ?, "approved", ?, ?)');
            $stmt->bind_param('ssssisi', $email, $username, $role, $phone, $smsAllowed, $displayName, $adminId);
        }
        $stmt->execute();
        $uid = (int) $stmt->insert_id;
        $stmt->close();
        if ($smsAllowed && $smsCode !== '' && abas_user_role_uses_sms_code($role)) {
            abas_set_user_sms_code($conn, $uid, $smsCode);
        }
        $successMsg = 'Bruger oprettet.';
        if ($sendWelcome) {
            $sent = abas_password_send_flow_email($conn, $uid, 'welcome');
            $successMsg = $sent
                ? 'Bruger oprettet. Velkomst-e-mail sendt.'
                : 'Bruger oprettet, men velkomst-e-mail kunne ikke sendes — tjek mail-konfiguration.';
        }
        if (($role === 'anlaegsejer' || $role === 'anlaegsafprover') && $miscno2 !== '') {
            $linkError = abas_link_user_installation_by_miscno2($conn, $uid, $miscno2);
            if ($linkError !== null) {
                abas_flash_set('error', 'Bruger oprettet, men anlæg: ' . $linkError);
                abas_redirect('admin/user-edit.php?id=' . $uid);
            }
            if ($sendWelcome) {
                $successMsg = str_replace('Bruger oprettet', 'Bruger oprettet og anlæg tilknyttet', $successMsg);
            } else {
                $successMsg = 'Bruger oprettet og anlæg tilknyttet.';
            }
        }
        abas_flash_set($sendWelcome && str_contains($successMsg, 'kunne ikke') ? 'error' : 'success', $successMsg);
    }
    $chk->close();
    abas_redirect($redirectUrl);
}

$roleCounts = [];
$countRes = $conn->query('SELECT role, COUNT(*) AS c FROM users GROUP BY role');
while ($row = $countRes->fetch_assoc()) {
    $roleCounts[(string) $row['role']] = (int) $row['c'];
}
$countRes->close();

$filterCount = static function (string $key) use ($filterGroups, $roleCounts): int {
    $total = 0;
    foreach ($filterGroups[$key] as $role) {
        $total += $roleCounts[$role] ?? 0;
    }

    return $total;
};

$listResult = abas_admin_users_list_page($conn, $rolesInFilter, $sort, $sortDir, $search, $page);
$rows = $listResult['rows'];
$totalUsers = $listResult['total'];
$totalPages = $listResult['totalPages'];
$page = $listResult['page'];
$listQueryBase = array_filter([
    'filter' => $filter !== 'alle' ? $filter : null,
    'q' => $search !== '' ? $search : null,
    'sort' => $sort !== '' ? $sort : null,
    'dir' => $sort !== '' ? $sortDir : null,
]);

$ownerUserIds = [];
foreach ($rows as $row) {
    if (in_array((string) ($row['role'] ?? ''), ['anlaegsejer', 'anlaegsafprover'], true)) {
        $ownerUserIds[] = (int) $row['id'];
    }
}
$ownerInstallations = $ownerUserIds !== []
    ? abas_user_installations_with_service_status_for_users($conn, $ownerUserIds)
    : [];

/** @param array{href: string, active: bool, indicator: string} $link */
$renderSortTh = static function (string $label, string $column) use ($sort, $sortDir, $filter, $search): void {
    $link = abas_admin_users_sort_link($column, $sort, $sortDir, $filter, $search);
    $class = 'abas-table-sort' . ($link['active'] ? ' abas-table-sort--active' : '');
    echo '<th scope="col"><a href="' . htmlspecialchars($link['href']) . '" class="' . $class . '">';
    echo htmlspecialchars($label);
    if ($link['indicator'] !== '') {
        echo ' <span class="abas-table-sort-indicator" aria-hidden="true">' . $link['indicator'] . '</span>';
    }
    echo '</a></th>';
};

$pageTitle = 'Brugere';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2">
    <a href="<?= abas_url('admin/index.php') ?>" class="abas-back-link">&larr; Administration</a>
</div>
<h1 class="abas-page-title !text-xl">Brugere</h1>
<p class="abas-page-lead mb-4">Samlet oversigt over alle brugertyper — opret, rediger og slet fra ét sted.</p>

<div class="flex flex-wrap items-center gap-2 mb-6">
    <nav class="flex flex-wrap gap-2 flex-1" aria-label="Filtrer efter rolle">
    <?php foreach ($filterLabels as $key => $label): ?>
        <?php
        $isActive = $filter === $key;
        $href = abas_admin_users_list_url($key, $sort !== '' ? $sort : null, $sort !== '' ? $sortDir : null, $search !== '' ? $search : null);
        ?>
        <a
            href="<?= $href ?>"
            class="px-3 py-1.5 rounded-full text-sm border transition-colors <?= $isActive ? 'bg-brand text-white border-brand' : 'bg-white text-gray-700 border-gray-200 hover:border-brand/40' ?>"
        ><?= htmlspecialchars($label) ?> <span class="<?= $isActive ? 'text-white/80' : 'text-gray-400' ?>">(<?= $filterCount($key) ?>)</span></a>
    <?php endforeach; ?>
    </nav>
    <button
        type="button"
        id="open-create-user"
        class="px-3 py-1.5 rounded-full text-sm font-semibold border border-brand bg-brand text-white hover:bg-brand-dark transition-colors shrink-0"
        aria-controls="create-user-modal"
        aria-expanded="false"
    >Opret bruger</button>
</div>

<div id="create-user-modal" class="abas-modal hidden" role="dialog" aria-modal="true" aria-labelledby="create-user-modal-title">
    <div class="abas-modal-backdrop" data-abas-modal-close tabindex="-1"></div>
    <div class="abas-modal-panel">
        <div class="abas-modal-header">
            <h2 id="create-user-modal-title" class="abas-card-title !mb-0">Opret ny bruger</h2>
            <button type="button" class="abas-modal-close" data-abas-modal-close aria-label="Luk">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <form method="post" class="space-y-0" data-abas-loading="Opretter bruger…">
            <input type="hidden" name="action" value="create">
            <div class="abas-field"><label class="abas-label" for="create-email">E-mail</label><input id="create-email" name="email" type="email" required class="abas-input" autocomplete="off"></div>
            <div class="abas-field"><label class="abas-label" for="create-display-name">Navn</label><input id="create-display-name" name="registration_display_name" maxlength="255" class="abas-input" placeholder="Fulde navn eller kontaktperson"></div>
            <div class="abas-field"><label class="abas-label" for="create-username">Brugernavn</label><input id="create-username" name="username" maxlength="255" class="abas-input" placeholder="Samme som e-mail hvis tom"></div>
            <div class="abas-field"><label class="abas-label" for="create-phone">Telefon</label><input id="create-phone" name="phone" required placeholder="+45..." class="abas-input"></div>
            <div class="abas-field">
                <label class="abas-label" for="create-sms-code">SMS-kode (anlægsbetjening)</label>
                <input id="create-sms-code" name="sms_code" minlength="6" autocomplete="off" class="abas-input font-mono" placeholder="Min. 6 tegn">
                <p class="abas-hint">Påkrævet når «Må betjene anlæg via SMS» er valgt — ikke til 2FA-login.</p>
            </div>
            <div class="abas-field">
                <label class="abas-label" for="role">Rolle</label>
                <select name="role" id="role" class="abas-select">
                <?php foreach (abas_roles() as $r): ?>
                    <option value="<?= $r ?>" <?= in_array($r, $rolesInFilter, true) && count($rolesInFilter) === 1 ? 'selected' : '' ?>><?= abas_role_label($r) ?></option>
                <?php endforeach; ?>
                </select>
                <p class="abas-hint">Montører og virksomhedsadmin får firma ud fra e-mail-domænet.</p>
            </div>
            <div class="abas-field" id="owner-misc-field">
                <label class="abas-label" for="miscno2">Anlægsnr.</label>
                <input id="miscno2" name="miscno2" placeholder="fab0100" class="abas-input font-mono">
                <p class="abas-hint">Valgfrit ved oprettelse af anlægsejer/anlægsafprøver.</p>
            </div>
            <label class="flex items-center gap-2 text-sm mb-4">
                <input type="checkbox" name="sms_service_allowed" value="1" class="abas-checkbox">
                Må betjene anlæg via SMS
            </label>
            <label class="flex items-center gap-2 text-sm mb-4">
                <input type="checkbox" name="send_welcome" value="1" class="abas-checkbox" checked>
                Send velkomst-e-mail med link til valg af adgangskode
            </label>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="abas-btn-primary">Opret bruger</button>
                <button type="button" class="abas-btn-secondary" data-abas-modal-close>Annuller</button>
            </div>
        </form>
    </div>
</div>

<form method="get" class="mb-4 flex flex-wrap gap-2 items-end max-w-2xl" role="search" data-abas-loading="Søger…">
    <?php if ($filter !== 'alle'): ?>
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
    <?php endif; ?>
    <?php if ($sort !== ''): ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($sortDir) ?>">
    <?php endif; ?>
    <div class="abas-field flex-1 min-w-[14rem] !mb-0">
        <label class="abas-label" for="user-search">Søg</label>
        <input
            id="user-search"
            type="search"
            name="q"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Navn, e-mail, telefon, firma, anlæg, rolle …"
            class="abas-input"
        >
    </div>
    <button type="submit" class="abas-btn-secondary">Søg</button>
    <?php if ($search !== ''): ?>
        <a href="<?= htmlspecialchars(abas_admin_users_list_url($filter, $sort !== '' ? $sort : null, $sort !== '' ? $sortDir : null)) ?>" class="abas-btn-secondary">Ryd</a>
    <?php endif; ?>
</form>
<?php if ($search !== '' || $totalUsers > count($rows)): ?>
<p class="text-sm text-gray-600 mb-4">
    <?= $totalUsers ?> bruger<?= $totalUsers === 1 ? '' : 'e' ?><?= $search !== '' ? ' matcher «' . htmlspecialchars($search) . '»' : '' ?>
    <?php if ($totalPages > 1): ?> · side <?= (int) $page ?> af <?= (int) $totalPages ?><?php endif; ?>
</p>
<?php endif; ?>

<div class="abas-table-wrap">
<table class="abas-table">
    <thead>
        <tr>
            <?php $renderSortTh('Bruger', 'username'); ?>
            <?php $renderSortTh('Rolle', 'role'); ?>
            <?php $renderSortTh('Telefon', 'phone'); ?>
            <?php $renderSortTh('Firma / anlæg', 'company'); ?>
            <?php $renderSortTh('SMS', 'sms'); ?>
            <?php $renderSortTh('Aktiv', 'active'); ?>
            <?php $renderSortTh('Senest aktiv', 'last_login'); ?>
            <th scope="col"></th>
        </tr>
    </thead>
    <tbody>
    <?php if ($rows === []): ?>
        <tr><td colspan="8" class="text-gray-500 text-sm"><?= $search !== '' ? 'Ingen brugere matcher søgningen.' : 'Ingen brugere i dette filter.' ?></td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr class="<?= empty($r['active']) ? 'opacity-60' : '' ?>">
            <td>
                <?= htmlspecialchars(abas_user_display_name($r)) ?><br>
                <span class="text-gray-500 text-xs"><?= htmlspecialchars($r['email']) ?></span>
                <?php if ((string) $r['username'] !== (string) $r['email']): ?>
                    <br><span class="text-gray-400 text-xs">Login: <?= htmlspecialchars((string) $r['username']) ?></span>
                <?php endif; ?>
            </td>
            <td class="whitespace-nowrap text-sm"><?= htmlspecialchars(abas_role_label($r['role'])) ?></td>
            <td class="whitespace-nowrap text-sm"><?= htmlspecialchars((string) ($r['phone'] ?? '—')) ?></td>
            <td class="text-sm">
                <?php if (in_array($r['role'], ['montor', 'virksomhedsadmin'], true)): ?>
                    <?= htmlspecialchars((string) ($r['company_name'] ?? '—')) ?>
                <?php elseif (in_array($r['role'], ['anlaegsejer', 'anlaegsafprover'], true)):
                    $linked = $ownerInstallations[(int) $r['id']] ?? [];
                    if ($linked === []): ?>
                        <span class="text-amber-700 text-xs">Ingen anlæg</span>
                    <?php else: ?>
                        <div class="abas-installation-badges">
                            <?php foreach ($linked as $inst): ?>
                                <span class="<?= $inst['in_service'] ? 'abas-badge-in-service' : 'abas-badge-ok' ?>"><?= htmlspecialchars($inst['miscno2']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-gray-400">—</span>
                <?php endif; ?>
            </td>
            <td class="text-center text-sm">
                <?php if (abas_user_role_uses_sms_code($r['role'])): ?>
                    <?php if (abas_user_has_sms_code($r)): ?>
                        Ja<?php if (empty($r['sms_service_allowed'])): ?><br><span class="text-amber-700 text-xs">ikke tilladt</span><?php endif; ?>
                    <?php else: ?>
                        <span class="text-amber-700">Mangler</span>
                    <?php endif; ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td class="text-center text-sm"><?= $r['active'] ? 'Ja' : 'Nej' ?></td>
            <td class="whitespace-nowrap text-sm text-gray-600">
                <?php
                $lastLogin = trim((string) ($r['last_login_at'] ?? ''));
                echo $lastLogin !== '' ? htmlspecialchars(abas_format_datetime($lastLogin, 'd/m/Y H:i')) : '—';
                ?>
            </td>
            <td class="text-right whitespace-nowrap space-x-2">
                <a href="<?= htmlspecialchars(abas_admin_user_edit_url((int) $r['id'], $filter, $sort !== '' ? $sort : null, $sort !== '' ? $sortDir : null, $search !== '' ? $search : null)) ?>" class="abas-link text-sm">Rediger</a>
                <?php if ((int) $r['id'] !== (int) $user['id']): ?>
                <form method="post" class="inline" onsubmit="return confirm('Slet eller deaktiver <?= htmlspecialchars($r['username'], ENT_QUOTES) ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
                    <button type="submit" class="text-sm text-red-700 hover:underline">Slet</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php abas_render_table_pagination('admin/users.php', $listQueryBase, $page, $totalPages); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.abasInitModal === 'function') {
        window.abasInitModal('open-create-user', 'create-user-modal');
    }
});
</script>
<?php require __DIR__ . '/../partials/footer.php';
