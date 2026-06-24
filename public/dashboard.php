<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/trekant_client.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/service.php';

$conn = abas_db();
$user = abas_require_login();
$pendingRegistrations = 0;
$externalInQueue = [];
$showExternalQueue = in_array($user['role'] ?? '', ['admin', 'vagtcentral'], true);
if (($user['role'] ?? '') === 'admin') {
    require_once __DIR__ . '/../includes/registration.php';
    $pendingRegistrations = abas_pending_registration_count($conn);
}
if ($showExternalQueue) {
    require_once __DIR__ . '/../includes/service_reconcile.php';
    $externalInQueue = abas_external_testqueue_installations($conn);
}
$q = trim($_GET['q'] ?? '');
$installations = [];
$listHeading = '';
$showServiceInfo = false;
$showServiceScope = false;
$isOwner = in_array($user['role'], ['anlaegsejer', 'anlaegsafprover'], true);
$isMontor = $user['role'] === 'montor';
$userId = (int) $user['id'];
$includeCompany = !$isMontor || ($_GET['scope'] ?? 'all') !== 'mine';

if ($isOwner) {
    $installations = $q === ''
        ? abas_user_linked_installations($conn, $userId)
        : abas_search_installations_local($conn, $q, false, $userId);
    if ($q === '') {
        $listHeading = 'Dine anlæg';
        $installations = abas_flag_installations_in_service($conn, $installations);
    }
} elseif ($q !== '') {
    $allAccess = abas_user_can_access_all_installations($user['role']);
    $installations = abas_search_installations_local($conn, $q, $allAccess, $userId);

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
} else {
    $installations = abas_dashboard_in_service_installations($conn, $user, $includeCompany);
    $showServiceInfo = true;
    $showServiceScope = $isMontor;
    $listHeading = $isMontor ? 'Anlæg i service' : 'Anlæg i service';
}

$pageTitle = 'Dashboard';
$currentUser = $user;
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

<form method="get" class="abas-search mb-6">
    <div class="abas-field flex-1">
        <label class="abas-label" for="q">Søg anlæg</label>
        <input id="q" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Anlægsnr., navn, by..." class="abas-input">
    </div>
  <?php if ($isMontor && $q === ''): ?>
    <div class="abas-field sm:w-52">
        <label class="abas-label" for="scope">Visning</label>
        <select id="scope" name="scope" class="abas-select" onchange="this.form.submit()">
            <option value="all" <?= $includeCompany ? 'selected' : '' ?>>Mine + firma i service</option>
            <option value="mine" <?= !$includeCompany ? 'selected' : '' ?>>Kun mine i service</option>
        </select>
    </div>
  <?php endif; ?>
    <div class="flex items-end">
        <button class="abas-btn-primary sm:min-w-[7rem]">Søg</button>
    </div>
</form>

<?php if ($showExternalQueue && $externalInQueue !== [] && $q === ''): ?>
    <div class="mb-8">
        <h2 class="abas-card-title mb-3">I testkø uden for ABA Service (<?= count($externalInQueue) ?>)</h2>
        <p class="text-sm text-gray-600 mb-3">Startet af VC eller andet — vises ikke på montør-dashboard. Opdateres via reconcile-poller.</p>
        <div class="abas-table-wrap">
            <table class="abas-table">
                <thead>
                    <tr>
                        <th>ABA-nr.</th>
                        <th>Navn</th>
                        <th>By</th>
                        <th>Udløber</th>
                        <th>Kommentar</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($externalInQueue as $ext): ?>
                    <tr class="abas-table-row-link" role="link" tabindex="0"
                        data-href="<?= htmlspecialchars(abas_url('installation.php?id=' . (int) $ext['installation_id'])) ?>">
                        <td class="font-mono font-medium text-sky-800"><?= htmlspecialchars((string) $ext['miscno2']) ?></td>
                        <td><?= htmlspecialchars((string) $ext['name']) ?></td>
                        <td><?= htmlspecialchars((string) $ext['city']) ?></td>
                        <td class="text-sm"><?= htmlspecialchars(abas_format_datetime((string) ($ext['end_at'] ?? '')) ?: '—') ?></td>
                        <td class="text-sm text-gray-600"><?= htmlspecialchars((string) ($ext['queue_comment'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($installations === []): ?>
    <div class="abas-panel">
        <?php if ($isOwner && $q === ''): ?>
            Du har ingen tilknyttede anlæg. Kontakt vagtcentralen.
        <?php elseif ($q !== ''): ?>
            Ingen anlæg fundet. Prøv et andet søgeord.
        <?php elseif ($showExternalQueue && $externalInQueue !== []): ?>
            Ingen anlæg i ABAS-service lige nu. <?= count($externalInQueue) ?> anlæg i ekstern testkø — se listen ovenfor.
        <?php elseif ($isMontor): ?>
            Ingen anlæg i service lige nu<?= $includeCompany ? '' : ' for dig' ?>. Søg efter et anlæg ovenfor.
        <?php else: ?>
            Ingen anlæg i service lige nu. Søg efter et anlæg ovenfor.
        <?php endif; ?>
    </div>
<?php else: ?>

<?php if ($listHeading !== ''): ?>
    <h2 class="abas-card-title mb-3"><?= htmlspecialchars($listHeading) ?> (<?= count($installations) ?>)</h2>
<?php endif; ?>

<?php
$showServiceInfo = $showServiceInfo;
$showServiceScope = $showServiceScope;
require __DIR__ . '/partials/dashboard-installation-list.php';
?>

<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php';
