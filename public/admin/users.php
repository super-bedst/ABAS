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

$redirectUrl = 'admin/users.php' . ($filter !== 'alle' ? '?filter=' . rawurlencode($filter) : '');

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
    $username = trim($_POST['username'] ?? '');
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
    if (abas_user_role_uses_sms_code($role) && !abas_validate_sms_code($smsCode)) {
        abas_flash_set('error', 'SMS-kode skal være mindst 6 tegn for montør og anlægsbruger.');
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

    $chk = $conn->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
    $chk->bind_param('ss', $email, $username);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        abas_flash_set('error', 'Bruger findes.');
    } else {
        $adminId = (int) $user['id'];
        if ($installerId !== null) {
            $stmt = $conn->prepare('INSERT INTO users (email, username, role, phone, installer_id, active, sms_service_allowed, registration_status, created_by_user_id) VALUES (?, ?, ?, ?, ?, 1, ?, "approved", ?)');
            $stmt->bind_param('ssssiii', $email, $username, $role, $phone, $installerId, $smsAllowed, $adminId);
        } else {
            $stmt = $conn->prepare('INSERT INTO users (email, username, role, phone, active, sms_service_allowed, registration_status, created_by_user_id) VALUES (?, ?, ?, ?, 1, ?, "approved", ?)');
            $stmt->bind_param('ssssii', $email, $username, $role, $phone, $smsAllowed, $adminId);
        }
        $stmt->execute();
        $uid = (int) $stmt->insert_id;
        $stmt->close();
        if (abas_user_role_uses_sms_code($role)) {
            abas_set_user_sms_code($conn, $uid, $smsCode);
        }
        abas_password_send_flow_email($conn, $uid, 'welcome');
        if (($role === 'anlaegsejer' || $role === 'anlaegsafprover') && $miscno2 !== '') {
            $linkError = abas_link_user_installation_by_miscno2($conn, $uid, $miscno2);
            if ($linkError !== null) {
                abas_flash_set('error', 'Bruger oprettet, men anlæg: ' . $linkError);
                abas_redirect('admin/user-edit.php?id=' . $uid);
            }
            abas_flash_set('success', 'Bruger oprettet og anlæg tilknyttet.');
        } else {
            abas_flash_set('success', 'Bruger oprettet.');
        }
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

$placeholders = implode(',', array_fill(0, count($rolesInFilter), '?'));
$types = str_repeat('s', count($rolesInFilter));
$stmt = $conn->prepare(
    "SELECT u.id, u.email, u.username, u.role, u.active, u.phone, u.sms_secret_hash, u.sms_service_allowed,
            u.registration_status, ai.company_name
     FROM users u
     LEFT JOIN approved_installers ai ON ai.id = u.installer_id
     WHERE u.role IN ($placeholders)
     ORDER BY u.role, u.username"
);
$stmt->bind_param($types, ...$rolesInFilter);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$ownerInstallations = abas_user_installations_with_service_status($conn);

$pageTitle = 'Brugere';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2">
    <a href="<?= abas_url('admin/index.php') ?>" class="abas-back-link">&larr; Administration</a>
</div>
<h1 class="abas-page-title !text-xl">Brugere</h1>
<p class="abas-page-lead mb-4">Samlet oversigt over alle brugertyper — opret, rediger og slet fra ét sted.</p>

<nav class="flex flex-wrap gap-2 mb-6" aria-label="Filtrer efter rolle">
    <?php foreach ($filterLabels as $key => $label): ?>
        <?php
        $isActive = $filter === $key;
        $href = $key === 'alle' ? abas_url('admin/users.php') : abas_url('admin/users.php?filter=' . rawurlencode($key));
        ?>
        <a
            href="<?= $href ?>"
            class="px-3 py-1.5 rounded-full text-sm border transition-colors <?= $isActive ? 'bg-brand text-white border-brand' : 'bg-white text-gray-700 border-gray-200 hover:border-brand/40' ?>"
        ><?= htmlspecialchars($label) ?> <span class="<?= $isActive ? 'text-white/80' : 'text-gray-400' ?>">(<?= $filterCount($key) ?>)</span></a>
    <?php endforeach; ?>
</nav>

<details class="abas-card mb-6 max-w-lg abas-form group">
    <summary class="abas-card-title cursor-pointer list-none flex items-center justify-between gap-2">
        <span>Opret ny bruger</span>
        <span class="text-gray-400 text-sm group-open:rotate-180 transition-transform">▼</span>
    </summary>
    <form method="post" class="mt-4 space-y-0">
        <input type="hidden" name="action" value="create">
        <div class="abas-field"><label class="abas-label">E-mail</label><input name="email" type="email" required class="abas-input"></div>
        <div class="abas-field"><label class="abas-label">Brugernavn</label><input name="username" required class="abas-input"></div>
        <div class="abas-field"><label class="abas-label">Telefon</label><input name="phone" required placeholder="+45..." class="abas-input"></div>
        <div class="abas-field">
            <label class="abas-label" for="sms_code">SMS-kode</label>
            <input id="sms_code" name="sms_code" minlength="6" autocomplete="off" class="abas-input font-mono" placeholder="Min. 6 tegn">
            <p class="abas-hint">Påkrævet for montør, anlægsejer og anlægsafprøver.</p>
        </div>
        <div class="abas-field">
            <label class="abas-label">Rolle</label>
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
        <button class="abas-btn-primary">Opret bruger</button>
    </form>
</details>

<div class="abas-table-wrap">
<table class="abas-table">
    <thead>
        <tr>
            <th>Bruger</th>
            <th>Rolle</th>
            <th>Telefon</th>
            <th>Firma / anlæg</th>
            <th>SMS</th>
            <th>Aktiv</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php if ($rows === []): ?>
        <tr><td colspan="7" class="text-gray-500 text-sm">Ingen brugere i dette filter.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr class="<?= empty($r['active']) ? 'opacity-60' : '' ?>">
            <td>
                <?= htmlspecialchars($r['username']) ?><br>
                <span class="text-gray-500 text-xs"><?= htmlspecialchars($r['email']) ?></span>
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
            <td class="text-right whitespace-nowrap space-x-2">
                <a href="<?= abas_url('admin/user-edit.php?id=' . (int) $r['id'] . ($filter !== 'alle' ? '&filter=' . rawurlencode($filter) : '')) ?>" class="abas-link text-sm">Rediger</a>
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
<?php require __DIR__ . '/../partials/footer.php';
