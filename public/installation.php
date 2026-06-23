<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/service.php';
require_once __DIR__ . '/../includes/installation_sync.php';

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
        $unlimited = !empty($_POST['unlimited']);
        $hours = $unlimited ? null : (float) ($_POST['hours'] ?? 2);
        $comment = trim($_POST['comment'] ?? '');
        $r = abas_start_service_session($conn, $user, $installation, $hours, $unlimited, null, $comment);
        abas_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Service startet.' : ($r['message'] ?? 'Fejl'));
    } elseif ($action === 'stop') {
        $r = abas_stop_service_session($conn, $user, $installation, $session ? (int) $session['id'] : null, trim($_POST['comment'] ?? ''));
        abas_flash_set($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Service stoppet.' : ($r['message'] ?? 'Fejl'));
    }
    header('Location: /installation.php?id=' . $id);
    exit;
}

$log = ['rows' => [], 'code' => -1];
try {
    $log = abas_fetch_installation_log($installation, $logMode, $customRange);
} catch (Throwable $e) {
    abas_flash_set('error', 'Log: ' . $e->getMessage());
}

$pageTitle = $installation['miscno2'] ?? 'Anlæg';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<div class="mb-4">
    <a href="/dashboard.php" class="text-brand text-sm underline">&larr; Tilbage</a>
</div>
<h1 class="text-2xl font-semibold text-brand mb-1"><?= htmlspecialchars((string) $installation['miscno2']) ?></h1>
<p class="text-gray-700 mb-4"><?= htmlspecialchars((string) $installation['name']) ?> — <?= htmlspecialchars((string) $installation['address']) ?>, <?= htmlspecialchars((string) $installation['city']) ?></p>

<div class="grid md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white border rounded p-4 shadow-sm">
        <h2 class="font-semibold mb-2">Service</h2>
        <?php if ($session): ?>
            <p class="text-amber-700 mb-3">Aktiv service siden <?= htmlspecialchars($session['started_at']) ?><?= $session['expires_at'] ? ' — udløber ' . htmlspecialchars($session['expires_at']) : ' (uden tidsbegrænsning)' ?></p>
            <form method="post">
                <input type="hidden" name="action" value="stop">
                <textarea name="comment" rows="2" class="w-full border rounded px-2 py-1 mb-2" placeholder="Kommentar ved stop"></textarea>
                <button class="bg-red-700 text-white px-4 py-2 rounded">Stop service</button>
            </form>
        <?php else: ?>
            <form method="post" class="space-y-2">
                <input type="hidden" name="action" value="start">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="unlimited" value="1"> Uden tidsbegrænsning</label>
                <div>
                    <label class="text-sm">Varighed (timer)</label>
                    <input type="number" name="hours" step="0.5" min="0.5" value="2" class="w-full border rounded px-2 py-1">
                </div>
                <textarea name="comment" rows="2" class="w-full border rounded px-2 py-1" placeholder="Kommentar"></textarea>
                <button class="bg-brand text-white px-4 py-2 rounded">Start service</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="bg-white border rounded p-4 shadow-sm text-sm">
        <h2 class="font-semibold mb-2">Detaljer</h2>
        <dl class="grid grid-cols-2 gap-1">
            <dt class="text-gray-500">s_ins</dt><dd><?= (int) $installation['s_ins'] ?></dd>
            <dt class="text-gray-500">deal_id</dt><dd><?= htmlspecialchars((string) $installation['deal_id']) ?></dd>
            <dt class="text-gray-500">ins_no</dt><dd><?= htmlspecialchars((string) $installation['ins_no']) ?></dd>
            <dt class="text-gray-500">mon_stat</dt><dd><?= htmlspecialchars((string) $installation['mon_stat']) ?></dd>
        </dl>
    </div>
</div>

<div class="bg-white border rounded shadow-sm overflow-hidden">
    <div class="p-3 border-b flex flex-wrap gap-2 items-center">
        <h2 class="font-semibold flex-1">Alarmlog</h2>
        <a href="?id=<?= $id ?>&log=last20" class="text-sm px-2 py-1 rounded <?= $logMode==='last20'?'bg-brand text-white':'border' ?>">Sidste 20</a>
        <a href="?id=<?= $id ?>&log=24h" class="text-sm px-2 py-1 rounded <?= $logMode==='24h'?'bg-brand text-white':'border' ?>">24 timer</a>
    </div>
    <form method="get" class="p-3 border-b flex flex-wrap gap-2 text-sm items-end">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="log" value="custom">
        <div><label class="block text-xs">Fra</label><input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>"></div>
        <div><label class="block text-xs">Til</label><input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>"></div>
        <button class="border px-3 py-1 rounded">Vis periode</button>
    </form>
    <?php if ($log['code'] !== 0): ?>
        <p class="p-3 text-amber-700">Log kunne ikke hentes (kode <?= (int) $log['code'] ?>).</p>
    <?php elseif ($log['rows'] === []): ?>
        <p class="p-3 text-gray-500">Ingen loglinjer.</p>
    <?php else: ?>
    <div class="overflow-x-auto max-h-96">
        <table class="w-full text-xs">
            <thead class="table-head sticky top-0"><tr><th class="p-2 text-left">Tid</th><th class="p-2 text-left">Tekst</th></tr></thead>
            <tbody>
            <?php foreach ($log['rows'] as $row): ?>
                <tr class="border-t">
                    <td class="p-2 whitespace-nowrap"><?= htmlspecialchars((string) ($row['logtime'] ?? $row['datetime'] ?? '')) ?></td>
                    <td class="p-2"><?= htmlspecialchars((string) ($row['comm'] ?? $row['text'] ?? json_encode($row, JSON_UNESCAPED_UNICODE))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php';
