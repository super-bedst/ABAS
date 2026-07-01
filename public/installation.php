<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/service.php';
require_once __DIR__ . '/../includes/trekant_client.php';
require_once __DIR__ . '/../includes/service_reconcile.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/installation_details.php';
require_once __DIR__ . '/../includes/installation_status.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/installation_links.php';
require_once __DIR__ . '/../includes/table_list.php';
require_once __DIR__ . '/../includes/theme.php';

$conn = abas_db();
$user = abas_require_login();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM installations WHERE id=? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$installation = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$installation || !abas_user_may_access_installation($conn, $user, $installation)) {
    abas_not_found('Anlægget findes ikke, eller du har ikke adgang til det.', ['installation_id' => $id]);
}

$session = abas_active_session_for_installation($conn, $id);
$externalTest = abas_external_testqueue_for_installation($conn, $id);
$logMode = $_GET['log'] ?? 'last20';
$customRange = null;
if ($logMode === 'custom' && !empty($_GET['from']) && !empty($_GET['to'])) {
    $customRange = [
        'startdate' => substr($_GET['from'], 0, 10),
        'starttime' => '00:00:00',
        'enddate' => substr($_GET['to'], 0, 10),
        'endtime' => '23:59:59',
    ];
}
$deferApi = $logMode === 'last20';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'start') {
        if (empty($_POST['responsibility_ack'])) {
            abas_flash_set('error', 'Du skal acceptere ansvarserklæringen.');
            abas_redirect('installation.php?id=' . $id);
        }
        $hours = (float) ($_POST['hours'] ?? 2);
        $comment = trim($_POST['comment'] ?? '');
        abas_set_user_responsibility_ack($conn, (int) $user['id']);
        $linkedMisc = array_values(array_unique(array_filter(
            array_map(static fn ($v) => strtolower(trim((string) $v)), (array) ($_POST['linked_miscno2'] ?? []))
        )));
        $resolved = abas_vc_resolve_service_installations(
            $conn,
            strtolower((string) ($installation['miscno2'] ?? '')),
            $linkedMisc
        );
        if (!$resolved['ok']) {
            abas_flash_set('error', $resolved['message'] ?? 'Ugyldigt anlægsvalg.');
            abas_redirect('installation.php?id=' . $id);
        }
        $result = abas_execute_linked_service_starts(
            $conn,
            $user,
            $resolved['primary'],
            $resolved['linked'],
            $hours,
            null,
            $comment,
            'web',
            true
        );
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'stop') {
        $r = abas_stop_service_session($conn, $user, $installation, $session ? (int) $session['id'] : null, trim($_POST['comment'] ?? ''));
        abas_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Service stoppet.' : ($r['message'] ?? 'Fejl'));
    } elseif ($action === 'stop_external') {
        $r = abas_stop_external_testqueue($conn, $user, $installation, trim($_POST['comment'] ?? ''));
        abas_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Anlæg sat i drift igen.' : ($r['message'] ?? 'Fejl'));
    } elseif ($action === 'link_installations' && ($user['role'] ?? '') === 'admin') {
        $targetIds = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) $v, (array) ($_POST['link_installation_ids'] ?? [])),
            static fn (int $targetId): bool => $targetId > 0
        )));
        $result = abas_installation_link_create_many($conn, $id, $targetIds, (int) $user['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'unlink_installation' && ($user['role'] ?? '') === 'admin') {
        $unlinkId = (int) ($_POST['unlink_installation_id'] ?? 0);
        if ($unlinkId <= 0) {
            abas_flash_set('error', 'Ugyldig kobling.');
        } else {
            $result = abas_installation_link_delete($conn, $id, $unlinkId);
            abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        }
    }
    abas_redirect('installation.php?id=' . $id);
}

$log = ['rows' => [], 'code' => 0];
if (!$deferApi) {
    try {
        $log = abas_fetch_installation_log($installation, $logMode, $customRange, $user, $conn);
    } catch (Throwable $e) {
        abas_flash_set('error', 'Log: ' . $e->getMessage());
        $log = ['rows' => [], 'code' => -1];
    }
}

$instDetails = [
    'lat' => null,
    'lon' => null,
    'contacts' => [],
    'zones' => [],
    'zones_error' => null,
    'error' => null,
    'alid' => '',
];
$mapLat = null;
$mapLon = null;
$contacts = [];
$zones = [];
$zonesError = null;
$alid = '';
$canStartService = abas_installation_allows_service((string) ($installation['mon_stat'] ?? ''));
$inAbasService = $session !== null;
$externalService = $externalTest !== null && !$inAbasService;
$inService = $inAbasService || $externalService;
$maxExtendHours = abas_service_remaining_extend_hours($session);
$linkedServiceOptions = $canStartService && !$inService
    ? abas_linked_installation_service_options($conn, $id)
    : [];
$isInstallationLinksAdmin = ($user['role'] ?? '') === 'admin';
$installationLinks = $isInstallationLinksAdmin
    ? abas_installation_linked_installations($conn, $id)
    : [];
$installationLinkIds = array_map(static fn (array $row): int => (int) $row['id'], $installationLinks);

$pageTitle = $installation['miscno2'] ?? 'Anlæg';
$currentUser = $user;
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">';
$extraHead .= '<script src="' . htmlspecialchars(abas_asset_url('assets/js/installation-auto-refresh.js')) . '" defer></script>';
if ($isInstallationLinksAdmin) {
    $extraHead .= '<script src="' . htmlspecialchars(abas_asset_url('assets/js/installation-links.js')) . '" defer></script>';
}
require __DIR__ . '/partials/header.php';
?>
<div class="mb-2">
    <a href="<?= abas_url('dashboard.php') ?>" class="abas-back-link">&larr; Tilbage til dashboard</a>
</div>
<h1 class="abas-page-title"><?= htmlspecialchars((string) $installation['miscno2']) ?></h1>
<div class="mb-2"><?= abas_render_installation_status_badges($installation, $inAbasService, $externalService) ?></div>
<?php if ($inAbasService): ?>
    <?= abas_render_installation_in_service_banner($session) ?>
<?php elseif ($externalService): ?>
    <?= abas_render_installation_external_service_banner($externalTest) ?>
<?php endif; ?>
<p class="abas-page-lead mb-6"><?= htmlspecialchars((string) $installation['name']) ?> — <?= htmlspecialchars((string) $installation['address']) ?>, <?= htmlspecialchars((string) $installation['city']) ?></p>

<div class="grid md:grid-cols-2 gap-4 mb-6">
    <div class="abas-card<?= $inAbasService ? ' abas-card--in-service' : ($externalService ? ' abas-card--external-service' : '') ?>" id="service-card">
        <h2 class="abas-card-title flex flex-wrap items-center gap-2">
            Service
            <?php if ($inAbasService): ?>
                <span class="abas-badge-in-service text-sm px-3 py-1">ABA aktiv</span>
            <?php elseif ($externalService): ?>
                <span class="abas-badge-external-service text-sm px-3 py-1">Ekstern test</span>
            <?php endif; ?>
        </h2>
        <?php if ($session): ?>
            <p class="text-sm text-gray-600 mb-4">Maks. <?= (int) abas_service_max_hours_per_start() ?> timer ad gangen og <?= (int) abas_service_max_consecutive_hours() ?> timer i alt fra første start.</p>
            <?php if ($maxExtendHours >= 0.5): ?>
            <form method="post" class="abas-form mb-4" data-abas-loading="Forlænger service…">
                <input type="hidden" name="action" value="start">
                <div class="abas-field">
                    <label class="abas-label" for="extend-hours">Forlæng med (timer)</label>
                    <input id="extend-hours" type="number" name="hours" step="0.5" min="0.5" max="<?= htmlspecialchars((string) $maxExtendHours) ?>" value="<?= htmlspecialchars((string) min(2.0, $maxExtendHours)) ?>" class="abas-input">
                    <p class="abas-hint">Du kan forlænge med op til <?= htmlspecialchars(rtrim(rtrim(number_format($maxExtendHours, 1, ',', ''), '0'), ',')) ?> timer nu.</p>
                </div>
                <div class="abas-field">
                    <label class="abas-label" for="extend-comment">Kommentar</label>
                    <textarea id="extend-comment" name="comment" rows="2" class="abas-textarea" placeholder="Årsag til forlængelse"></textarea>
                </div>
                <div class="abas-field border border-amber-200 bg-amber-50 rounded-xl p-3">
                    <label class="flex items-start gap-2 text-sm text-gray-800">
                        <input type="checkbox" name="responsibility_ack" value="1" class="abas-checkbox abas-ack-checkbox mt-1" id="extend_responsibility_ack" required>
                        <span>Jeg bekræfter fortsat ansvar for bygningen og brandvagter m.v. Ved brand er jeg ansvarlig for at ringe 112 indtil anlægget sættes tilbage i normal drift.</span>
                    </label>
                </div>
                <button type="submit" class="abas-btn-primary abas-ack-submit" id="extend-service-btn" disabled>Forlæng service</button>
            </form>
            <?php else: ?>
            <p class="text-sm text-amber-800 mb-4">Maks. <?= (int) abas_service_max_consecutive_hours() ?> timer i service er nået. Stop service for at starte forfra.</p>
            <?php endif; ?>
            <form method="post" class="abas-form" data-abas-loading="Stopper service…">
                <input type="hidden" name="action" value="stop">
                <div class="abas-field">
                    <label class="abas-label" for="stop-comment">Kommentar ved stop</label>
                    <textarea id="stop-comment" name="comment" rows="2" class="abas-textarea" placeholder="Beskriv kort hvad der er udført"></textarea>
                </div>
                <button class="abas-btn-danger">Stop service</button>
            </form>
        <?php elseif ($externalService && $canStartService): ?>
            <p class="text-sm text-sky-900 mb-4">Anlægget er i test/service via TrekantBrand/VC. Du kan sætte det i normal drift igen herfra.</p>
            <form method="post" class="abas-form" data-abas-loading="Sætter i drift…">
                <input type="hidden" name="action" value="stop_external">
                <div class="abas-field">
                    <label class="abas-label" for="external-stop-comment">Kommentar</label>
                    <textarea id="external-stop-comment" name="comment" rows="2" class="abas-textarea" placeholder="Beskriv kort hvad der er udført"></textarea>
                </div>
                <button class="abas-btn-primary">Sæt i drift igen</button>
            </form>
        <?php elseif ($externalService): ?>
            <p class="text-amber-800 text-sm mb-4">Anlægget er i ekstern test, men linjen er <strong><?= htmlspecialchars(strtolower(abas_mon_stat_label((string) $installation['mon_stat']))) ?></strong> — kan ikke sættes i drift fra ABAS.</p>
        <?php elseif (!$canStartService): ?>
            <p class="text-amber-800 text-sm mb-4">Anlægget er <strong><?= htmlspecialchars(strtolower(abas_mon_stat_label((string) $installation['mon_stat']))) ?></strong> og kan ikke sættes i service.</p>
        <?php else: ?>
            <form method="post" class="abas-form" id="start-service-form" data-abas-loading="Sætter i service…">
                <input type="hidden" name="action" value="start">
                <div class="abas-field">
                    <label class="abas-label" for="hours">Varighed (timer)</label>
                    <input id="hours" type="number" name="hours" step="0.5" min="0.5" max="<?= (int) abas_service_max_hours_per_start() ?>" value="2" class="abas-input">
                    <p class="abas-hint">Maks. <?= (int) abas_service_max_hours_per_start() ?> timer ad gangen og <?= (int) abas_service_max_consecutive_hours() ?> timer i alt.</p>
                </div>
                <div class="abas-field">
                    <label class="abas-label" for="start-comment">Kommentar</label>
                    <textarea id="start-comment" name="comment" rows="2" class="abas-textarea" placeholder="Årsag til service eller udført arbejde"></textarea>
                    <?php if ($user['role'] !== 'vagtcentral'): ?>
                        <p class="abas-hint">Din kommentar får automatisk tilføjet navn, telefon og rolle<?= $user['role'] === 'montor' ? ' samt firmanavn' : '' ?>.</p>
                    <?php endif; ?>
                </div>
                <?php abas_render_linked_installation_service_options($linkedServiceOptions); ?>
                <div class="abas-field border border-amber-200 bg-amber-50 rounded-xl p-3">
                    <label class="flex items-start gap-2 text-sm text-gray-800">
                        <input type="checkbox" name="responsibility_ack" value="1" class="abas-checkbox abas-ack-checkbox mt-1" id="responsibility_ack" required>
                        <span>Jeg bekræfter, at jeg overtager ansvaret for bygningen, herunder ansvar for brandvagter m.v. Ved brand er jeg ansvarlig for at ringe 112 indtil anlægget sættes tilbage i normal drift.</span>
                    </label>
                </div>
                <button type="submit" class="abas-btn-primary abas-ack-submit" id="start-service-btn" disabled>Start service</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="abas-card text-sm">
        <h2 class="abas-card-title">Placering og kontakter</h2>
        <div id="inst-map-wrap" class="h-44 w-full rounded-xl border border-gray-200 mb-3 bg-gray-50 flex items-center justify-center">
            <?= abas_loading_panel_html('Henter kort…', 'abas-loading-panel--centered') ?>
        </div>
        <div id="inst-map" class="h-44 w-full rounded-xl border border-gray-200 mb-3 z-0 hidden"></div>

        <div id="inst-contacts-content">
            <?= abas_loading_panel_html('Henter kontakter…') ?>
        </div>

        <dl class="grid grid-cols-2 gap-1 mt-4 pt-3 border-t text-xs">
            <span id="inst-alid-wrap" class="contents<?= $alid === '' ? ' hidden' : '' ?>">
                <dt class="text-gray-500">ALID</dt><dd id="inst-alid" class="font-mono"><?= htmlspecialchars($alid) ?></dd>
            </span>
            <dt class="text-gray-500">ins_no</dt><dd><?= htmlspecialchars((string) $installation['ins_no']) ?></dd>
            <dt class="text-gray-500">Driftstatus</dt><dd><?= htmlspecialchars(abas_mon_stat_label((string) $installation['mon_stat'])) ?></dd>
        </dl>
    </div>
</div>

<?php if ($isInstallationLinksAdmin): ?>
<div class="abas-card mb-6" id="installation-links-admin">
    <h2 class="abas-card-title">Koblede anlæg</h2>
    <p class="text-sm text-gray-600 mb-4">Kun administratorer kan administrere koblinger. Tilknyt flere anlæg der hører sammen — vagtcentralen kan vælge at sætte dem i service samtidig.</p>
    <?php if ($installationLinks === []): ?>
        <p class="text-sm text-gray-500 mb-4" id="installation-links-empty">Ingen koblinger endnu.</p>
    <?php else: ?>
        <p class="text-sm text-gray-500 mb-4 hidden" id="installation-links-empty">Ingen koblinger endnu.</p>
    <?php endif; ?>
    <ul class="space-y-2 mb-4" id="installation-links-list">
        <?php foreach ($installationLinks as $linked): ?>
            <li class="flex flex-wrap items-center justify-between gap-2 border border-gray-100 rounded-xl px-3 py-2 text-sm">
                <div>
                    <a href="<?= abas_url('installation.php?id=' . (int) $linked['id']) ?>" class="font-mono font-medium text-brand hover:underline">
                        <?= htmlspecialchars((string) $linked['miscno2']) ?>
                    </a>
                    <span class="text-gray-600 ml-2"><?= htmlspecialchars((string) ($linked['name'] ?? '')) ?></span>
                </div>
                <form method="post" class="inline" onsubmit="return confirm('Fjern kobling?')">
                    <input type="hidden" name="action" value="unlink_installation">
                    <input type="hidden" name="unlink_installation_id" value="<?= (int) $linked['id'] ?>">
                    <button type="submit" class="abas-btn-secondary !py-1 !px-2 text-xs">Fjern</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <form method="post" class="abas-form max-w-xl" id="installation-links-form">
        <input type="hidden" name="action" value="link_installations">
        <div class="abas-field abas-combobox mb-3" id="inst-link-combobox">
            <label class="abas-label" for="inst-link-search">Søg anlæg at koble</label>
            <input
                type="text"
                id="inst-link-search"
                class="abas-input font-mono"
                placeholder="Søg anlægsnr. eller kundenavn…"
                autocomplete="off"
                aria-autocomplete="list"
                aria-controls="inst-link-results"
                aria-expanded="false"
            >
            <ul id="inst-link-results" class="abas-combobox-list hidden" role="listbox"></ul>
            <p class="abas-hint">Vælg fra listen — du kan tilføje flere anlæg før du gemmer koblingerne.</p>
        </div>

        <div id="inst-link-pending-wrap" class="mb-4 hidden">
            <p class="text-sm font-medium text-gray-800 mb-2">Valgte til kobling</p>
            <ul id="inst-link-pending-list" class="space-y-2 mb-3"></ul>
            <div id="inst-link-hidden-inputs"></div>
            <button type="submit" class="abas-btn-primary text-sm" id="inst-link-submit">Kobl valgte anlæg</button>
        </div>
    </form>
</div>
<script>
window.abasInstallationLinks = <?= json_encode([
    'searchUrl' => abas_url('vc-service-search.php'),
    'installationId' => $id,
    'linkedIds' => $installationLinkIds,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<?php endif; ?>

<div class="abas-card !p-0" id="inst-log-card">
    <div class="abas-log-toolbar">
        <h2 class="abas-card-title !mb-0 flex-1">Alarmlog</h2>
        <span id="inst-log-spinner" class="hidden abas-loading-panel !text-xs"><span class="abas-spinner !h-3.5 !w-3.5" aria-hidden="true"></span><span>Opdaterer…</span></span>
        <span class="text-xs text-gray-400 hidden sm:inline" title="Træk i nederste højre hjørne af loggen for at ændre højden">Justerbar højde</span>
        <a href="?id=<?= $id ?>&log=last20" class="<?= $logMode === 'last20' ? 'abas-chip-active' : 'abas-chip' ?>">Sidste 20</a>
        <a href="?id=<?= $id ?>&log=24h" class="<?= $logMode === '24h' ? 'abas-chip-active' : 'abas-chip' ?>">24 timer</a>
    </div>
    <form method="get" class="p-4 border-b border-gray-100 flex flex-wrap gap-3 items-end bg-basbg/40" data-abas-loading="Henter alarmlog…">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="log" value="custom">
        <div class="abas-field">
            <label class="abas-label" for="log-from">Fra</label>
            <input id="log-from" type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>" class="abas-input" title="dd/mm/åååå">
            <span class="abas-hint">dd/mm/åååå</span>
        </div>
        <div class="abas-field">
            <label class="abas-label" for="log-to">Til</label>
            <input id="log-to" type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>" class="abas-input" title="dd/mm/åååå">
            <span class="abas-hint">dd/mm/åååå</span>
        </div>
        <button class="abas-btn-secondary">Vis periode</button>
    </form>
    <?php if ($deferApi): ?>
        <p class="p-4" id="inst-log-loading"><?= abas_loading_panel_html('Henter alarmlog…') ?></p>
        <p class="p-4 text-amber-800 hidden" id="inst-log-error"></p>
        <p class="p-4 text-gray-500 hidden" id="inst-log-empty">Ingen loglinjer.</p>
        <div class="abas-log-body hidden" id="inst-log-body">
            <table class="abas-table abas-log-table text-xs" data-abas-client-sort>
                <thead><tr><?php abas_render_client_table_sort_th('Tidspunkt', 0); ?><?php abas_render_client_table_sort_th('Detaljer', 1); ?></tr></thead>
                <tbody id="inst-log-rows"></tbody>
            </table>
        </div>
    <?php elseif ($log['code'] !== 0): ?>
        <p class="p-4 text-amber-800" id="inst-log-error">Log kunne ikke hentes (kode <?= (int) $log['code'] ?>).</p>
        <p class="p-4 text-gray-500 hidden" id="inst-log-empty">Ingen loglinjer.</p>
        <div class="abas-log-body hidden" id="inst-log-body">
            <table class="abas-table abas-log-table text-xs" data-abas-client-sort>
                <thead><tr><?php abas_render_client_table_sort_th('Tidspunkt', 0); ?><?php abas_render_client_table_sort_th('Detaljer', 1); ?></tr></thead>
                <tbody id="inst-log-rows"></tbody>
            </table>
        </div>
    <?php elseif ($log['rows'] === []): ?>
        <p class="p-4 text-amber-800 hidden" id="inst-log-error"></p>
        <p class="p-4 text-gray-500" id="inst-log-empty">Ingen loglinjer.</p>
        <div class="abas-log-body hidden" id="inst-log-body">
            <table class="abas-table abas-log-table text-xs" data-abas-client-sort>
                <thead><tr><?php abas_render_client_table_sort_th('Tidspunkt', 0); ?><?php abas_render_client_table_sort_th('Detaljer', 1); ?></tr></thead>
                <tbody id="inst-log-rows"></tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="p-4 text-amber-800 hidden" id="inst-log-error"></p>
        <p class="p-4 text-gray-500 hidden" id="inst-log-empty">Ingen loglinjer.</p>
        <div class="abas-log-body" id="inst-log-body">
            <table class="abas-table abas-log-table text-xs" data-abas-client-sort>
                <thead><tr><?php abas_render_client_table_sort_th('Tidspunkt', 0); ?><?php abas_render_client_table_sort_th('Detaljer', 1); ?></tr></thead>
                <tbody id="inst-log-rows">
                <?= abas_render_alarmlog_rows_html($log['rows']) ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<details class="abas-card abas-collapsible mt-4" id="inst-zones-wrap">
    <summary class="abas-collapsible-summary">
        <span class="abas-card-title !mb-0">Zonestatus<?php if ($zones !== []): ?> (<?= count($zones) ?>)<?php endif; ?></span>
    </summary>
    <div id="inst-zones-content" class="abas-collapsible-body">
        <?= abas_loading_panel_html('Henter zonestatus…') ?>
    </div>
</details>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.abasInitInstallationAutoRefresh === 'function') {
        window.abasInitInstallationAutoRefresh({
            url: <?= json_encode(abas_url('installation-refresh.php?id=' . $id . '&log=' . rawurlencode($logMode)
                . ($logMode === 'custom' && !empty($_GET['from']) && !empty($_GET['to'])
                    ? '&from=' . rawurlencode((string) $_GET['from']) . '&to=' . rawurlencode((string) $_GET['to'])
                    : ''))) ?>,
            sessionActive: <?= $session ? 'true' : 'false' ?>,
            externalActive: <?= $externalService ? 'true' : 'false' ?>,
            intervalMs: 5000,
            deferInitial: <?= $deferApi ? 'true' : 'false' ?>,
            leafletScript: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        });
    }
});
</script>
<?php require __DIR__ . '/partials/footer.php';
