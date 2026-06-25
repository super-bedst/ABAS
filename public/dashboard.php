<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/trekant_client.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/dashboard_view.php';

$conn = abas_db();
$user = abas_require_login();
$pendingRegistrations = 0;
if (($user['role'] ?? '') === 'admin') {
    require_once __DIR__ . '/../includes/registration.php';
    $pendingRegistrations = abas_pending_registration_count($conn);
}

$q = trim($_GET['q'] ?? '');
$scope = ($_GET['scope'] ?? 'all') === 'mine' ? 'mine' : 'all';
$state = abas_dashboard_build_state($conn, $user, $q, $scope);
$isOwner = $state['isOwner'];
$isMontor = $state['isMontor'];
$autoRefresh = $q === '';

if ($q !== '' && $state['installations'] === [] && abas_user_can_access_all_installations((string) $user['role']) && abas_is_miscno2_query($q)) {
    try {
        $fromApi = abas_search_installations_from_api($conn, $user, $q);
        if ($fromApi === []) {
            abas_flash_set('error', 'Ingen anlæg fundet i TrekantBrand for: ' . $q);
        } else {
            $state['installations'] = $fromApi;
        }
    } catch (Throwable $e) {
        abas_flash_set('error', 'API-søgning fejlede: ' . $e->getMessage());
    }
}

$pageTitle = 'Dashboard';
$currentUser = $user;
$extraHead = $autoRefresh
    ? '<script src="' . htmlspecialchars(abas_asset_url('assets/js/dashboard-auto-refresh.js')) . '" defer></script>'
    : '';
require __DIR__ . '/partials/header.php';
?>
<h1 class="abas-page-title">Dashboard</h1>
<?php if ($pendingRegistrations > 0): ?>
    <?= abas_render_pending_registrations_banner($pendingRegistrations) ?>
<?php endif; ?>
<?php if ($isOwner): ?>
    <p class="abas-page-lead">Dine tilknyttede anlæg — tryk på et anlæg for at se status og starte eller stoppe service.</p>
<?php elseif ($q === ''): ?>
    <p class="abas-page-lead">Anlæg med aktiv service — tryk på et anlæg for detaljer og alarmlog.</p>
<?php else: ?>
    <p class="abas-page-lead">Søg og find anlæg — se status og start eller stop service.</p>
<?php endif; ?>

<?php if ($autoRefresh): ?>
    <p id="abas-dashboard-refresh-status" class="text-xs text-gray-500 mb-3" aria-live="polite"></p>
<?php endif; ?>

<form method="get" class="abas-search mb-6">
    <div class="abas-field flex-1">
        <label class="abas-label" for="q">Søg anlæg</label>
        <input id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Anlægsnr., navn, by..." class="abas-input">
    </div>
  <?php if ($isMontor && $q === ''): ?>
    <div class="abas-field sm:w-52">
        <label class="abas-label" for="scope">Visning</label>
        <select id="scope" name="scope" class="abas-select" onchange="this.form.submit()">
            <option value="all" <?= $state['includeCompany'] ? 'selected' : '' ?>>Mine + firma i service</option>
            <option value="mine" <?= !$state['includeCompany'] ? 'selected' : '' ?>>Kun mine i service</option>
        </select>
    </div>
  <?php endif; ?>
    <div class="flex items-end">
        <button class="abas-btn-primary sm:min-w-[7rem]">Søg</button>
    </div>
</form>

<div id="abas-dashboard-external-wrap">
<?= abas_dashboard_render_external_queue($state) ?>
</div>

<div id="abas-dashboard-main-wrap">
<?= abas_dashboard_render_main($state) ?>
</div>

<?php if ($autoRefresh): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.abasInitDashboardAutoRefresh === 'function') {
        window.abasInitDashboardAutoRefresh({
            url: <?= json_encode(abas_url('dashboard-refresh.php?scope=' . rawurlencode($scope)), JSON_UNESCAPED_UNICODE) ?>,
            intervalMs: 5000
        });
    }
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php';
