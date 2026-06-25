<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/user_management.php';
require_once __DIR__ . '/../includes/installation_sync.php';

$conn = abas_db();
$actor = abas_require_login();
abas_require_role(['anlaegsejer']);

$actorId = (int) $actor['id'];
$managedUsers = abas_list_anlaegsejer_managed_users($conn, $actorId);
$myInstallations = abas_user_linked_installations($conn, $actorId);

$pageTitle = 'Anlægsbrugere';
$currentUser = $actor;
require __DIR__ . '/partials/header.php';
?>
<h1 class="abas-page-title">Anlægsbrugere</h1>
<p class="abas-page-lead">
    Brugere og afprøvere tilknyttet dine anlæg
    <?php if ($myInstallations !== []): ?>
        (<?= count($myInstallations) ?> anlæg)
    <?php endif; ?>.
</p>

<?php if ($managedUsers === []): ?>
    <div class="abas-panel mt-4">
        Ingen andre anlægsejere eller afprøvere er tilknyttet de samme anlæg som dig.
    </div>
<?php else: ?>
    <div class="abas-table-wrap mt-6">
        <table class="abas-table">
            <thead>
                <tr>
                    <th>Navn</th>
                    <th>E-mail</th>
                    <th>Telefon</th>
                    <th>Rolle</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($managedUsers as $u): ?>
                <tr>
                    <td><?= htmlspecialchars(abas_user_display_name($u)) ?></td>
                    <td><?= htmlspecialchars((string) $u['email']) ?></td>
                    <td><?= htmlspecialchars((string) ($u['phone'] ?? '')) ?></td>
                    <td><?= htmlspecialchars(abas_role_label((string) $u['role'])) ?></td>
                    <td>
                        <a href="<?= abas_url('anlaegsbruger-edit.php?id=' . (int) $u['id']) ?>" class="abas-link text-sm">Rediger</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php';
