<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/password_flow.php';

$conn = abas_db();
$actor = abas_require_login();
abas_require_role(['virksomhedsadmin']);

$installerId = (int) ($actor['installer_id'] ?? 0);
if ($installerId <= 0) {
    http_response_code(403);
    exit('Ingen virksomhed tilknyttet.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if ($action === 'reset_password') {
        abas_password_send_flow_email($conn, $targetId, 'reset');
        abas_flash_set('success', 'Nulstillings-e-mail sendt.');
    } elseif ($action === 'delete') {
        $result = abas_delete_user($conn, $targetId, (int) $actor['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'save_phone') {
        $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
        if (!abas_validate_phone($phone)) {
            abas_flash_set('error', 'Ugyldigt telefonnummer.');
        } else {
            $upd = $conn->prepare('UPDATE users SET phone = ? WHERE id = ?');
            $upd->bind_param('si', $phone, $targetId);
            $upd->execute();
            $upd->close();
            abas_flash_set('success', 'Telefon opdateret.');
        }
    }
    abas_redirect('virksomhed/users.php');
}

$stmt = $conn->prepare(
    "SELECT id, email, username, phone, role, active FROM users
     WHERE installer_id = ? AND role NOT IN ('admin','vagtcentral','virksomhedsadmin')
     ORDER BY username"
);
$stmt->bind_param('i', $installerId);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Virksomhedsbrugere';
$currentUser = $actor;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="abas-page-title">Virksomhedsbrugere</h1>
<p class="abas-page-lead">Brugere tilknyttet <?= htmlspecialchars(abas_user_company_name($conn, $actor)) ?>.</p>

<div class="abas-table-wrap mt-6">
    <table class="abas-table">
        <thead><tr><th>Navn</th><th>E-mail</th><th>Telefon</th><th>Rolle</th><th>Handlinger</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <form method="post" class="flex gap-2 items-center">
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <input type="hidden" name="action" value="save_phone">
                        <input name="phone" value="<?= htmlspecialchars((string) $u['phone']) ?>" class="abas-input !py-1 text-sm w-36">
                        <button class="abas-btn-secondary !py-1 text-xs">Gem</button>
                    </form>
                </td>
                <td><?= htmlspecialchars(abas_role_label($u['role'])) ?></td>
                <td class="space-x-2">
                    <form method="post" class="inline">
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <button class="text-sm abas-link">Nulstil adgangskode</button>
                    </form>
                    <form method="post" class="inline" onsubmit="return confirm('Slet/deaktiver bruger?')">
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="text-sm text-red-700">Slet</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php';
