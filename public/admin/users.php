<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/password_flow.php';
require_once __DIR__ . '/../../includes/users.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
    $role = $_POST['role'] ?? 'montor';
    if (!in_array($role, abas_roles(), true)) {
        $role = 'montor';
    }
    if (!abas_validate_phone($phone)) {
        abas_flash_set('error', 'Angiv et gyldigt telefonnummer (min. 8 cifre).');
        abas_redirect('admin/users.php');
    }

    $installerId = null;
    if ($role === 'montor') {
        $installerId = abas_assign_installer_for_montor($conn, $email);
        if ($installerId === null) {
            abas_flash_set('error', 'Montør skal have e-mail fra et godkendt installatør-domæne.');
            abas_redirect('admin/users.php');
        }
    }

    $chk = $conn->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
    $chk->bind_param('ss', $email, $username);
    $chk->execute();
    if ($chk->get_result()->fetch_row()) {
        abas_flash_set('error', 'Bruger findes.');
    } else {
        if ($installerId !== null) {
            $stmt = $conn->prepare('INSERT INTO users (email, username, role, phone, installer_id, active) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->bind_param('ssssi', $email, $username, $role, $phone, $installerId);
        } else {
            $stmt = $conn->prepare('INSERT INTO users (email, username, role, phone, active) VALUES (?, ?, ?, ?, 1)');
            $stmt->bind_param('ssss', $email, $username, $role, $phone);
        }
        $stmt->execute();
        $uid = (int) $stmt->insert_id;
        $stmt->close();
        abas_password_send_flow_email($conn, $uid, 'welcome');
        abas_flash_set('success', 'Bruger oprettet.');
    }
    $chk->close();
    abas_redirect('admin/users.php');
}

$rows = $conn->query(
    'SELECT u.id, u.email, u.username, u.role, u.active, u.phone, u.trekant_userid, ai.company_name
     FROM users u
     LEFT JOIN approved_installers ai ON ai.id = u.installer_id
     ORDER BY u.role, u.username'
)->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Brugere';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="text-xl font-semibold text-brand mb-4">Brugere</h1>
<form method="post" class="bg-white border rounded p-4 mb-4 max-w-lg space-y-2">
    <input name="email" type="email" required placeholder="E-mail" class="w-full border rounded px-3 py-2">
    <input name="username" required placeholder="Brugernavn" class="w-full border rounded px-3 py-2">
    <input name="phone" required placeholder="Telefon (+45...)" class="w-full border rounded px-3 py-2">
    <select name="role" class="w-full border rounded px-3 py-2">
        <?php foreach (abas_roles() as $r): ?>
            <option value="<?= $r ?>"><?= abas_role_label($r) ?></option>
        <?php endforeach; ?>
    </select>
    <p class="text-xs text-gray-500">Montører får automatisk firmanavn ud fra e-mail-domænet.</p>
    <button class="bg-brand text-white px-4 py-2 rounded">Opret bruger</button>
</form>
<div class="overflow-x-auto bg-white border rounded">
<table class="w-full text-sm">
    <thead class="table-head">
        <tr>
            <th class="p-2 text-left">Bruger</th>
            <th class="p-2 text-left">Telefon</th>
            <th class="p-2 text-left">Rolle / firma</th>
            <th class="p-2">Aktiv</th>
            <th class="p-2"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr class="border-t">
            <td class="p-2">
                <?= htmlspecialchars($r['username']) ?><br>
                <span class="text-gray-500 text-xs"><?= htmlspecialchars($r['email']) ?></span>
            </td>
            <td class="p-2 whitespace-nowrap"><?= htmlspecialchars((string) ($r['phone'] ?? '—')) ?></td>
            <td class="p-2">
                <?= abas_role_label($r['role']) ?>
                <?php if ($r['role'] === 'montor' && !empty($r['company_name'])): ?>
                    <br><span class="text-gray-500 text-xs"><?= htmlspecialchars($r['company_name']) ?></span>
                <?php endif; ?>
            </td>
            <td class="p-2 text-center"><?= $r['active'] ? 'Ja' : 'Nej' ?></td>
            <td class="p-2 text-right">
                <a href="<?= abas_url('admin/user-edit.php?id=' . (int) $r['id']) ?>" class="text-brand underline">Rediger</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="mt-4"><a href="<?= abas_url('admin/index.php') ?>" class="text-brand underline text-sm">Tilbage</a></p>
<?php require __DIR__ . '/../partials/footer.php';
