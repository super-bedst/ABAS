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

$montors = $conn->query("SELECT id, username, email, phone FROM users WHERE role='montor' AND active=1 ORDER BY username")->fetch_all(MYSQLI_ASSOC);

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
            header('Location: /vc-service.php');
            exit;
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
            $msg = 'Du er inviteret til ABA Service. Registrér dig som montør: ' . abas_config()['app_url'] . '/register.php';
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
            header('Location: /installation.php?id=' . (int) $installation['id']);
            exit;
        }
    }
    header('Location: /vc-service.php');
    exit;
}

$pageTitle = 'VC — Hurtig service';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<h1 class="text-2xl font-semibold text-brand mb-4">Vagtcentral — service på vegne af montør</h1>
<form method="post" class="bg-white border rounded p-4 shadow-sm max-w-xl space-y-3">
    <div>
        <label class="block text-sm font-medium">Anlægsnr. (miscno2)</label>
        <input name="miscno2" required placeholder="fab0100" class="w-full border rounded px-3 py-2 font-mono">
    </div>
    <div>
        <label class="block text-sm font-medium">Montør</label>
        <select name="montor_id" class="w-full border rounded px-3 py-2">
            <option value="0">— Vælg montør —</option>
            <?php foreach ($montors as $m): ?>
                <option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars($m['username']) ?> (<?= htmlspecialchars($m['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium">Eller inviter via telefon (SMS)</label>
        <input name="invite_phone" placeholder="+45..." class="w-full border rounded px-3 py-2">
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="unlimited" value="1"> Uden tidsbegrænsning</label>
    <div>
        <label class="block text-sm">Varighed (timer)</label>
        <input type="number" name="hours" step="0.5" value="2" class="w-full border rounded px-3 py-2">
    </div>
    <textarea name="comment" rows="2" class="w-full border rounded px-3 py-2" placeholder="Kommentar">VC service</textarea>
    <button class="bg-brand text-white px-4 py-2 rounded">Start service</button>
</form>
<?php require __DIR__ . '/partials/footer.php';
