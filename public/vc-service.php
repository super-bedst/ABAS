<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/service.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/sms.php';
require_once __DIR__ . '/../includes/password_flow.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['vagtcentral', 'admin']);

$montors = $conn->query(
    "SELECT u.id, u.username, u.email, u.phone, ai.company_name
     FROM users u
     LEFT JOIN approved_installers ai ON ai.id = u.installer_id
     WHERE u.role='montor' AND u.active=1
     ORDER BY u.username"
)->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $misc = strtolower(trim($_POST['miscno2'] ?? ''));
    $montorId = (int) ($_POST['montor_id'] ?? 0);
    $phone = trim($_POST['invite_phone'] ?? '');
    $hours = (float) ($_POST['hours'] ?? 2);
    $unlimited = !empty($_POST['unlimited']);
    $comment = trim($_POST['comment'] ?? 'VC service');
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
            abas_redirect('vc-service.php');
        }
    }
    if (!$installation) {
        abas_flash_set('error', 'Anlæg ikke fundet.');
    } else {
        $onBehalf = null;
        if ($montorId > 0) {
            $m = $conn->prepare("SELECT id FROM users WHERE id=? AND role='montor' LIMIT 1");
            $m->bind_param('i', $montorId);
            $m->execute();
            $onBehalf = $m->get_result()->fetch_assoc();
            $m->close();
            $onBehalf = $onBehalf ? (int) $onBehalf['id'] : null;
        }
        if (!$onBehalf && $phone !== '') {
            $msg = 'Du er inviteret til ABA Service. Registrér dig som montør: ' . abas_full_url('index.php');
            abas_sms_queue($conn, $phone, $msg, 'montor_invite');
            $vcId = (int) $user['id'];
            $instId = (int) $installation['id'];
            $log = $conn->prepare('INSERT INTO montor_outreach_log (vc_user_id, phone, message, miscno2, installation_id) VALUES (?,?,?,?,?)');
            $log->bind_param('isssi', $vcId, $phone, $msg, $misc, $instId);
            $log->execute();
            $log->close();
        }
        $r = abas_start_service_session($conn, $user, $installation, $hours, $unlimited, $onBehalf, $comment);
        abas_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Service startet på vegne af montør.' : ($r['message'] ?? 'Fejl'));
        if ($r['ok']) {
            abas_redirect('installation.php?id=' . (int) $installation['id']);
        }
    }
    abas_redirect('vc-service.php');
}

$pageTitle = 'VC — Hurtig service';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<h1 class="abas-page-title">Vagtcentral — service på vegne af montør</h1>
<p class="abas-page-lead">Find anlæg, vælg montør og start service med kommentar.</p>
<form method="post" class="abas-card max-w-xl abas-form">
    <div class="abas-field">
        <label class="abas-label" for="miscno2">Anlægsnr. (miscno2)</label>
        <input id="miscno2" name="miscno2" required placeholder="fab0100" class="abas-input font-mono">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="montor_id">Montør</label>
        <select id="montor_id" name="montor_id" class="abas-select">
            <option value="0">— Vælg montør —</option>
            <?php foreach ($montors as $m): ?>
                <option value="<?= (int) $m['id'] ?>">
                    <?= htmlspecialchars($m['username']) ?>
                    <?php if (!empty($m['company_name'])): ?>
                        (<?= htmlspecialchars($m['company_name']) ?>)
                    <?php endif; ?>
                    — <?= htmlspecialchars((string) ($m['phone'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="abas-field">
        <label class="abas-label" for="invite_phone">Eller inviter via telefon (SMS)</label>
        <input id="invite_phone" name="invite_phone" placeholder="+45..." class="abas-input">
    </div>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="unlimited" value="1" class="abas-checkbox">
        Uden tidsbegrænsning
    </label>
    <div class="abas-field">
        <label class="abas-label" for="hours">Varighed (timer)</label>
        <input id="hours" type="number" name="hours" step="0.5" value="2" class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="comment">Kommentar</label>
        <textarea id="comment" name="comment" rows="2" class="abas-textarea" placeholder="Kommentar">VC service</textarea>
    </div>
    <button class="abas-btn-primary">Start service</button>
</form>
<?php require __DIR__ . '/partials/footer.php';
