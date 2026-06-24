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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        abas_redirect('admin/users.php');
    }

    $smsCode = trim($_POST['sms_code'] ?? '');
    if (abas_user_role_uses_sms_code($role) && !abas_validate_sms_code($smsCode)) {
        abas_flash_set('error', 'SMS-kode skal være mindst 6 tegn for montør og anlægsejer.');
        abas_redirect('admin/users.php');
    }

    $installerId = null;
    if ($role === 'montor' || $role === 'virksomhedsadmin') {
        $installerId = abas_assign_installer_for_montor($conn, $email);
        if ($installerId === null) {
            $msg = $role === 'virksomhedsadmin'
                ? 'Virksomhedsadmin skal have e-mail fra et godkendt installatør-domæne.'
                : 'Montør skal have e-mail fra et godkendt installatør-domæne.';
            abas_flash_set('error', $msg);
            abas_redirect('admin/users.php');
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
    abas_redirect('admin/users.php');
}

$rows = $conn->query(
    'SELECT u.id, u.email, u.username, u.role, u.active, u.phone, u.trekant_userid, u.sms_secret_hash, ai.company_name
     FROM users u
     LEFT JOIN approved_installers ai ON ai.id = u.installer_id
     ORDER BY u.role, u.username'
)->fetch_all(MYSQLI_ASSOC);
$ownerInstallations = abas_user_installations_with_service_status($conn);

$pageTitle = 'Brugere';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="abas-page-title !text-xl">Brugere</h1>
<form method="post" class="abas-card mb-4 max-w-lg abas-form">
    <div class="abas-field"><label class="abas-label">E-mail</label><input name="email" type="email" required class="abas-input"></div>
    <div class="abas-field"><label class="abas-label">Brugernavn</label><input name="username" required class="abas-input"></div>
    <div class="abas-field"><label class="abas-label">Telefon</label><input name="phone" required placeholder="+45..." class="abas-input"></div>
    <div class="abas-field">
        <label class="abas-label" for="sms_code">SMS-kode</label>
        <input id="sms_code" name="sms_code" minlength="6" autocomplete="off" class="abas-input font-mono" placeholder="Min. 6 tegn">
        <p class="abas-hint">Påkrævet for montør og anlægsejer.</p>
    </div>
    <div class="abas-field">
        <label class="abas-label">Rolle</label>
        <select name="role" id="role" class="abas-select">
        <?php foreach (abas_roles() as $r): ?>
            <option value="<?= $r ?>"><?= abas_role_label($r) ?></option>
        <?php endforeach; ?>
        </select>
        <p class="abas-hint">Montører får automatisk firmanavn ud fra e-mail-domænet.</p>
    </div>
    <div class="abas-field" id="owner-misc-field">
        <label class="abas-label" for="miscno2">Anlægsnr. (anlægsejer)</label>
        <input id="miscno2" name="miscno2" placeholder="fab0100" class="abas-input font-mono">
        <p class="abas-hint">Valgfrit ved oprettelse af anlægsejer — tilknytter anlæg med det samme.</p>
    </div>
    <button class="abas-btn-primary">Opret bruger</button>
</form>
<div class="abas-table-wrap">
<table class="abas-table">
    <thead>
        <tr>
            <th>Bruger</th>
            <th>Telefon</th>
            <th>SMS</th>
            <th>Rolle / anlæg</th>
            <th>Aktiv</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td>
                <?= htmlspecialchars($r['username']) ?><br>
                <span class="text-gray-500 text-xs"><?= htmlspecialchars($r['email']) ?></span>
            </td>
            <td class="whitespace-nowrap"><?= htmlspecialchars((string) ($r['phone'] ?? '—')) ?></td>
            <td class="text-center text-sm">
                <?php if (abas_user_role_uses_sms_code($r['role'])): ?>
                    <?php if (abas_user_has_sms_code($r)): ?>
                        Ja
                    <?php else: ?>
                        <span class="text-amber-700">Nej</span>
                    <?php endif; ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td>
                <?= abas_role_label($r['role']) ?>
                <?php if ($r['role'] === 'montor' && !empty($r['company_name'])): ?>
                    <br><span class="text-gray-500 text-xs"><?= htmlspecialchars($r['company_name']) ?></span>
                <?php endif; ?>
                <?php if ($r['role'] === 'anlaegsejer'):
                    $linked = $ownerInstallations[(int) $r['id']] ?? [];
                    if ($linked !== []): ?>
                        <div class="abas-installation-badges mt-1">
                            <?php foreach ($linked as $inst): ?>
                                <span class="<?= $inst['in_service'] ? 'abas-badge-in-service' : 'abas-badge-ok' ?>"><?= htmlspecialchars($inst['miscno2']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <br><span class="text-amber-700 text-xs">Ingen anlæg</span>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
            <td class="text-center"><?= $r['active'] ? 'Ja' : 'Nej' ?></td>
            <td class="text-right whitespace-nowrap">
                <a href="<?= abas_url('admin/user-edit.php?id=' . (int) $r['id']) ?>" class="abas-link">Rediger</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="mt-4"><a href="<?= abas_url('admin/index.php') ?>" class="abas-link text-sm">Tilbage</a></p>
<?php require __DIR__ . '/../partials/footer.php';
