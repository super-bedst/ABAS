<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/users.php';

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

$stmt = $conn->prepare(
    "SELECT id, email, username, phone, role, active, registration_display_name, sms_service_allowed
     FROM users
     WHERE installer_id = ? AND role NOT IN ('admin','vagtcentral')
     ORDER BY role, username"
);
$stmt->bind_param('i', $installerId);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$actorId = (int) $actor['id'];
$companyName = abas_user_company_name($conn, $actor);
abas_session_release();

$pageTitle = 'Virksomhedsbrugere';
$currentUser = $actor;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="abas-page-title">Virksomhedsbrugere</h1>
<p class="abas-page-lead">Montører, virksomhedsadministratorer og øvrige brugere hos <?= htmlspecialchars($companyName) ?>.</p>

<div class="abas-table-wrap mt-6">
    <table class="abas-table">
        <thead>
            <tr>
                <th>Navn</th>
                <th>E-mail</th>
                <th>Telefon</th>
                <th>Rolle</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($users === []): ?>
            <tr>
                <td colspan="6" class="text-gray-500 text-sm p-4">
                    Ingen brugere tilknyttet virksomheden endnu.
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
