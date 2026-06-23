<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/trekant_client.php';
require_once __DIR__ . '/../includes/installation_sync.php';

$conn = abas_db();
$user = abas_require_login();
$q = trim($_GET['q'] ?? '');
$installations = [];

if ($q !== '') {
    $allAccess = abas_user_can_access_all_installations($user['role']);
    $installations = abas_search_installations_local($conn, $q, $allAccess, (int) $user['id']);

    if ($installations === [] && $allAccess && abas_is_miscno2_query($q)) {
        try {
            $installations = abas_search_installations_from_api($conn, $user, $q);
            if ($installations === []) {
                abas_flash_set('error', 'Ingen anlæg fundet i TrekantBrand for: ' . $q);
            }
        } catch (Throwable $e) {
            abas_flash_set('error', 'API-søgning fejlede: ' . $e->getMessage());
        }
    }
} elseif (!abas_user_can_access_all_installations($user['role'])) {
    $uid = (int) $user['id'];
    $stmt = $conn->prepare(
        'SELECT i.* FROM installations i
         JOIN user_installations ui ON ui.installation_id = i.id
         WHERE ui.user_id = ? ORDER BY i.miscno2 LIMIT 50'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $installations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pageTitle = 'Dashboard';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<h1 class="abas-page-title">Dashboard</h1>
<p class="abas-page-lead">Søg og find anlæg — se status og start eller stop service.</p>

<form method="get" class="abas-search mb-6">
    <div class="abas-field flex-1">
        <label class="abas-label" for="q">Søg anlæg</label>
        <input id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Anlægsnr., navn, by..." class="abas-input">
    </div>
    <div class="flex items-end">
        <button class="abas-btn-primary sm:min-w-[7rem]">Søg</button>
    </div>
</form>

<?php if ($installations === []): ?>
    <div class="abas-panel">Ingen anlæg fundet.<?= $q ? ' Prøv et andet søgeord.' : '' ?></div>
<?php else: ?>

<div class="hidden sm:block abas-table-wrap">
    <table class="abas-table">
        <thead>
            <tr>
                <th>ABA-nr.</th>
                <th>Navn</th>
                <th class="hidden md:table-cell">By</th>
                <th class="hidden lg:table-cell">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($installations as $inst): ?>
            <tr>
                <td class="font-mono font-medium text-brand"><?= htmlspecialchars((string) $inst['miscno2']) ?></td>
                <td><?= htmlspecialchars((string) $inst['name']) ?></td>
                <td class="hidden md:table-cell"><?= htmlspecialchars((string) $inst['city']) ?></td>
                <td class="hidden lg:table-cell"><?= htmlspecialchars((string) $inst['mon_stat']) ?></td>
                <td><a class="abas-link" href="<?= abas_url('installation.php?id=' . (int) $inst['id']) ?>">Åbn</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="sm:hidden space-y-3">
    <?php foreach ($installations as $inst): ?>
        <a href="<?= abas_url('installation.php?id=' . (int) $inst['id']) ?>" class="abas-mobile-card">
            <div class="abas-mobile-card-title"><?= htmlspecialchars((string) $inst['miscno2']) ?></div>
            <div class="font-medium text-gray-800 mt-1"><?= htmlspecialchars((string) $inst['name']) ?></div>
            <div class="text-sm text-gray-500 mt-1"><?= htmlspecialchars((string) $inst['city']) ?></div>
            <?php if (!empty($inst['mon_stat'])): ?>
                <span class="abas-badge-ok mt-2"><?= htmlspecialchars((string) $inst['mon_stat']) ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php';
