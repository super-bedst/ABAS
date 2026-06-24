<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/service.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/installation_details.php';
require_once __DIR__ . '/../includes/installation_status.php';
require_once __DIR__ . '/../includes/users.php';

$conn = abas_db();
$user = abas_require_login();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM installations WHERE id=? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$installation = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$installation || !abas_user_may_access_installation($conn, $user, $installation)) {
    http_response_code(404);
    exit('Anlæg ikke fundet.');
}

$session = abas_active_session_for_installation($conn, $id);
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
        $r = abas_start_service_session($conn, $user, $installation, $hours, null, $comment, 'web', true);
        abas_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? (($r['extended'] ?? false) ? 'Service forlænget.' : 'Service startet.') : ($r['message'] ?? 'Fejl'));
    } elseif ($action === 'stop') {
        $r = abas_stop_service_session($conn, $user, $installation, $session ? (int) $session['id'] : null, trim($_POST['comment'] ?? ''));
        abas_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Service stoppet.' : ($r['message'] ?? 'Fejl'));
    }
    abas_redirect('installation.php?id=' . $id);
}

$log = ['rows' => [], 'code' => -1];
try {
    $log = abas_fetch_installation_log($installation, $logMode, $customRange);
} catch (Throwable $e) {
    abas_flash_set('error', 'Log: ' . $e->getMessage());
}

$instDetails = abas_fetch_installation_details($installation, $user);
$mapLat = $instDetails['lat'];
$mapLon = $instDetails['lon'];
$contacts = $instDetails['contacts'];
$canStartService = abas_installation_allows_service((string) ($installation['mon_stat'] ?? ''));
$inService = $session !== null;
$maxExtendHours = abas_service_remaining_extend_hours($session);

$pageTitle = $installation['miscno2'] ?? 'Anlæg';
$currentUser = $user;
$extraHead = ($mapLat !== null && $mapLon !== null)
    ? '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">'
    : '';
require __DIR__ . '/partials/header.php';
?>
<div class="mb-2">
    <a href="<?= abas_url('dashboard.php') ?>" class="abas-back-link">&larr; Tilbage til dashboard</a>
</div>
<h1 class="abas-page-title"><?= htmlspecialchars((string) $installation['miscno2']) ?></h1>
<div class="mb-2"><?= abas_render_installation_status_badges($installation, $inService) ?></div>
<?php if ($inService): ?>
    <?= abas_render_installation_in_service_banner($session) ?>
<?php endif; ?>
<p class="abas-page-lead mb-6"><?= htmlspecialchars((string) $installation['name']) ?> — <?= htmlspecialchars((string) $installation['address']) ?>, <?= htmlspecialchars((string) $installation['city']) ?></p>

<div class="grid md:grid-cols-2 gap-4 mb-6">
    <div class="abas-card<?= $inService ? ' abas-card--in-service' : '' ?>" id="service-card">
        <h2 class="abas-card-title flex flex-wrap items-center gap-2">
            Service
            <?php if ($inService): ?>
                <span class="abas-badge-in-service text-sm px-3 py-1">Aktiv nu</span>
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
        <?php if ($mapLat !== null && $mapLon !== null): ?>
            <div id="inst-map" class="h-44 w-full rounded-xl border border-gray-200 mb-3 z-0"></div>
        <?php else: ?>
            <p class="text-gray-500 mb-3 text-xs">GPS-koordinater ikke tilgængelige for dette anlæg.</p>
        <?php endif; ?>

        <?php if ($instDetails['error']): ?>
            <p class="text-amber-700 text-xs mb-2">Kontakter kunne ikke hentes: <?= htmlspecialchars($instDetails['error']) ?></p>
        <?php endif; ?>

        <?php if ($contacts === []): ?>
            <p class="text-gray-500 text-xs">Ingen registrerede kontakter.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($contacts as $contact): ?>
                    <li class="border-t pt-2 first:border-t-0 first:pt-0">
                        <div class="font-medium"><?= htmlspecialchars($contact['name']) ?></div>
                        <?php foreach ($contact['phones'] as $phone): ?>
                            <div class="text-gray-600">
                                <?php if ($phone['label'] !== 'Tlf.'): ?>
                                    <span class="text-gray-400 text-xs"><?= htmlspecialchars($phone['label']) ?>:</span>
                                <?php endif; ?>
                                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $phone['number']) ?? $phone['number']) ?>" class="text-brand underline"><?= htmlspecialchars($phone['number']) ?></a>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($contact['email'] !== '' && $contact['email'] !== ' '): ?>
                            <div class="text-gray-600">
                                <a href="mailto:<?= htmlspecialchars(trim($contact['email'])) ?>" class="text-brand underline"><?= htmlspecialchars(trim($contact['email'])) ?></a>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <dl class="grid grid-cols-2 gap-1 mt-4 pt-3 border-t text-xs">
            <dt class="text-gray-500">s_ins</dt><dd><?= (int) $installation['s_ins'] ?></dd>
            <dt class="text-gray-500">deal_id</dt><dd><?= htmlspecialchars((string) $installation['deal_id']) ?></dd>
            <dt class="text-gray-500">ins_no</dt><dd><?= htmlspecialchars((string) $installation['ins_no']) ?></dd>
            <dt class="text-gray-500">Driftstatus</dt><dd><?= htmlspecialchars(abas_mon_stat_label((string) $installation['mon_stat'])) ?></dd>
        </dl>
    </div>
</div>

<?php if ($mapLat !== null && $mapLon !== null): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    var lat = <?= json_encode($mapLat) ?>;
    var lon = <?= json_encode($mapLon) ?>;
    var map = L.map('inst-map', { scrollWheelZoom: false }).setView([lat, lon], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    L.marker([lat, lon]).addTo(map);
    setTimeout(function () { map.invalidateSize(); }, 100);
})();
</script>
<?php endif; ?>

<div class="abas-card !p-0 overflow-hidden">
    <div class="abas-log-toolbar">
        <h2 class="abas-card-title !mb-0 flex-1">Alarmlog</h2>
        <span id="inst-log-spinner" class="hidden text-xs text-gray-500 animate-pulse">Opdaterer…</span>
        <a href="?id=<?= $id ?>&log=last20" class="<?= $logMode === 'last20' ? 'abas-chip-active' : 'abas-chip' ?>">Sidste 20</a>
        <a href="?id=<?= $id ?>&log=24h" class="<?= $logMode === '24h' ? 'abas-chip-active' : 'abas-chip' ?>">24 timer</a>
    </div>
    <form method="get" class="p-4 border-b border-gray-100 flex flex-wrap gap-3 items-end bg-basbg/40">
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
    <?php if ($log['code'] !== 0): ?>
        <p class="p-4 text-amber-800" id="inst-log-error">Log kunne ikke hentes (kode <?= (int) $log['code'] ?>).</p>
    <?php elseif ($log['rows'] === []): ?>
        <p class="p-4 text-gray-500" id="inst-log-empty">Ingen loglinjer.</p>
    <?php else: ?>
    <div class="abas-log-body" id="inst-log-body">
        <table class="abas-table abas-log-table text-xs">
            <thead><tr><th>Tidspunkt</th><th>Detaljer</th></tr></thead>
            <tbody id="inst-log-rows">
            <?= abas_render_alarmlog_rows_html($log['rows']) ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<script>
(function () {
    var refreshUrl = <?= json_encode(abas_url('installation-refresh.php?id=' . $id . '&log=' . rawurlencode($logMode)
        . ($logMode === 'custom' && !empty($_GET['from']) && !empty($_GET['to'])
            ? '&from=' . rawurlencode((string) $_GET['from']) . '&to=' . rawurlencode((string) $_GET['to'])
            : ''))) ?>;
    var initialSessionActive = <?= $session ? 'true' : 'false' ?>;
    var logRows = document.getElementById('inst-log-rows');
    var logBody = document.getElementById('inst-log-body');
    var logEmpty = document.getElementById('inst-log-empty');
    var logError = document.getElementById('inst-log-error');
    var serviceStatus = document.getElementById('inst-service-status');

    function refreshInstallationView() {
        fetch(refreshUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.error) {
                    return;
                }
                if (data.sessionActive !== initialSessionActive) {
                    window.location.reload();
                    return;
                }
                if (serviceStatus && data.sessionLabel) {
                    serviceStatus.textContent = data.sessionLabel;
                }
                if (data.logCode !== 0) {
                    return;
                }
                if (logError) {
                    logError.style.display = 'none';
                }
                if (data.logEmpty) {
                    if (logBody) {
                        logBody.style.display = 'none';
                    }
                    if (logEmpty) {
                        logEmpty.style.display = '';
                    }
                    return;
                }
                if (logEmpty) {
                    logEmpty.style.display = 'none';
                }
                if (logBody) {
                    logBody.style.display = '';
                }
                if (logRows && data.logHtml) {
                    logRows.innerHTML = data.logHtml;
                }
            })
            .catch(function () {});
    }

    setInterval(refreshInstallationView, 5000);
})();
</script>
<?php require __DIR__ . '/partials/footer.php';
