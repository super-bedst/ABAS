<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/bas_sso_auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/service.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/users.php';

if (!empty($_GET['embed'])) {
    abas_set_embed_session(true);
}

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['vagtcentral', 'admin']);

$embed = abas_is_embed_session();
$vcUrl = abas_embed_url('vc-service.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $misc = strtolower(trim($_POST['miscno2'] ?? ''));
    $montorId = (int) ($_POST['montor_id'] ?? 0);
    $manualName = trim($_POST['manual_montor_name'] ?? '');
    $manualPhone = abas_normalize_phone(trim($_POST['manual_montor_phone'] ?? ''));
    $hours = (float) ($_POST['hours'] ?? 2);
    $comment = trim($_POST['comment'] ?? '');
    if ($comment === 'VC service') {
        $comment = '';
    }

    if ($misc === '') {
        abas_flash_set('error', 'Vælg et anlæg fra listen.');
        abas_redirect($vcUrl);
    }

    if ($montorId <= 0 && $manualPhone !== '' && !abas_validate_phone($manualPhone)) {
        abas_flash_set('error', 'Angiv et gyldigt telefonnummer til montøren.');
        abas_redirect($vcUrl);
    }

    $installation = abas_find_installation_by_miscno2($conn, $misc);
    if (!$installation) {
        try {
            $client = abas_trekant();
            $resp = $client->searchInstallations(abas_trekant_userid($user), $misc);
            foreach (abas_trekant_rows($resp) as $row) {
                abas_upsert_installation($conn, $row);
            }
            $installation = abas_find_installation_by_miscno2($conn, $misc);
        } catch (Throwable $e) {
            abas_flash_set('error', $e->getMessage());
            abas_redirect($vcUrl);
        }
    }

    if (!$installation) {
        abas_flash_set('error', 'Anlæg ikke fundet.');
        abas_redirect($vcUrl);
    }

    $onBehalf = null;
    if ($montorId > 0) {
        $m = $conn->prepare(
            'SELECT u.*, ai.company_name
             FROM users u
             LEFT JOIN approved_installers ai ON ai.id = u.installer_id
             WHERE u.id = ? AND u.role = "montor" AND u.active = 1
             LIMIT 1'
        );
        $m->bind_param('i', $montorId);
        $m->execute();
        $montorRow = $m->get_result()->fetch_assoc();
        $m->close();
        if ($montorRow) {
            $onBehalf = (int) $montorRow['id'];
        }
    }

    $manualActor = null;
    if (!$onBehalf && ($manualName !== '' || $manualPhone !== '')) {
        $manualActor = [
            'username' => $manualName !== '' ? $manualName : 'Montør',
            'phone' => $manualPhone,
            'role' => 'montor',
        ];
    }

    $r = abas_start_service_session($conn, $user, $installation, $hours, $onBehalf, $comment, 'web', false, $manualActor);
    abas_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Service startet på vegne af montør.' : ($r['message'] ?? 'Fejl'));
    if ($r['ok'] && !$embed) {
        abas_redirect('installation.php?id=' . (int) $installation['id']);
    }
    abas_redirect($vcUrl);
}

$searchUrl = abas_url('vc-service-search.php');
if ($embed) {
    $sep = str_contains($searchUrl, '?') ? '&' : '?';
    $searchUrl .= $sep . 'embed=1';
}

$pageTitle = 'VC — Hurtig service';
$currentUser = $user;
$extraHead = '<script src="' . htmlspecialchars(abas_asset_url('assets/js/vc-service.js'), ENT_QUOTES) . '" defer></script>';

if ($embed) {
    require __DIR__ . '/partials/embed_shell_start.php';
} else {
    require __DIR__ . '/partials/header.php';
}
?>
<h1 class="abas-page-title">Vagtcentral — service på vegne af montør</h1>
<p class="abas-page-lead">Søg anlæg og montør, eller angiv montør manuelt hvis vedkommende ikke er i systemet.</p>

<form method="post" class="abas-card max-w-xl abas-form" id="vc-service-form" data-abas-loading="Starter service…">
    <div class="abas-field abas-combobox" id="inst-combobox">
        <label class="abas-label" for="inst-search">Anlæg</label>
        <input
            type="text"
            id="inst-search"
            class="abas-input font-mono"
            placeholder="Søg anlægsnr. eller kundenavn…"
            autocomplete="off"
            aria-autocomplete="list"
            aria-controls="inst-results"
            aria-expanded="false"
        >
        <input type="hidden" name="miscno2" id="miscno2" required>
        <div id="inst-selected" class="abas-combobox-selected hidden" aria-live="polite"></div>
        <ul id="inst-results" class="abas-combobox-list hidden" role="listbox"></ul>
        <p class="abas-hint">Vælg fra listen — viser anlægsnr. og kundenavn.</p>
    </div>

    <div class="abas-field abas-combobox" id="montor-combobox">
        <label class="abas-label" for="montor-search">Montør</label>
        <input
            type="search"
            id="montor-search"
            class="abas-input"
            placeholder="Søg navn, firma eller telefon…"
            autocomplete="off"
            aria-autocomplete="list"
            aria-controls="montor-results"
            aria-expanded="false"
        >
        <input type="hidden" name="montor_id" id="montor_id" value="0">
        <div id="montor-selected" class="abas-combobox-selected hidden" aria-live="polite"></div>
        <ul id="montor-results" class="abas-combobox-list hidden" role="listbox"></ul>
        <p class="abas-hint">Søg på navn, firma eller telefonnummer.</p>
    </div>

    <div id="manual-montor-fields" class="space-y-4 border-t border-gray-100 pt-4">
        <p class="text-sm font-medium text-gray-700">Eller angiv montør manuelt</p>
        <div class="abas-field">
            <label class="abas-label" for="manual_montor_name">Navn</label>
            <input id="manual_montor_name" name="manual_montor_name" class="abas-input" placeholder="Fx Anders Andersen">
        </div>
        <div class="abas-field">
            <label class="abas-label" for="manual_montor_phone">Telefon</label>
            <input id="manual_montor_phone" name="manual_montor_phone" class="abas-input" placeholder="+45…">
        </div>
    </div>

    <div class="abas-field">
        <label class="abas-label" for="hours">Varighed (timer)</label>
        <input id="hours" type="number" name="hours" step="0.5" min="0.5" max="<?= (int) abas_service_max_hours_per_start() ?>" value="2" class="abas-input">
        <p class="abas-hint">Maks. <?= (int) abas_service_max_hours_per_start() ?> timer ad gangen.</p>
    </div>
    <div class="abas-field">
        <label class="abas-label" for="comment">Kommentar</label>
        <textarea id="comment" name="comment" rows="2" class="abas-textarea" placeholder="Valgfri kommentar"></textarea>
    </div>
    <button type="submit" class="abas-btn-primary">Start service</button>
</form>

<script>
window.abasVcService = <?= json_encode(['searchUrl' => $searchUrl], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<?php
if ($embed) {
    require __DIR__ . '/partials/embed_shell_end.php';
} else {
    require __DIR__ . '/partials/footer.php';
}
