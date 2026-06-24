<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/installation_sync.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pfx = strtolower(trim($_POST['prefix'] ?? ''));
        $min = max(0, (int) ($_POST['min_suffix'] ?? 0));
        $max = (int) ($_POST['max_suffix'] ?? 9999);
        if ($min > $max) {
            abas_flash_set('error', 'Start skal være mindre end eller lig med max.');
        } else {
            $stmt = $conn->prepare('INSERT INTO sync_prefixes (prefix, min_suffix, max_suffix) VALUES (?, ?, ?)');
            $stmt->bind_param('sii', $pfx, $min, $max);
            $stmt->execute();
            $stmt->close();
            abas_flash_set('success', 'Prefix tilføjet.');
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $min = max(0, (int) ($_POST['min_suffix'] ?? 0));
        $max = (int) ($_POST['max_suffix'] ?? 9999);
        if ($min > $max) {
            abas_flash_set('error', 'Start skal være mindre end eller lig med max.');
        } else {
            $stmt = $conn->prepare('UPDATE sync_prefixes SET min_suffix = ?, max_suffix = ? WHERE id = ?');
            $stmt->bind_param('iii', $min, $max, $id);
            $stmt->execute();
            $stmt->close();
            abas_flash_set('success', 'Prefix opdateret.');
        }
    } elseif ($action === 'run') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $result = abas_sync_prefix($conn, $id);
            abas_flash_set('success', sprintf('Sync færdig: %d anlæg opdateret (%s).', $result['upserted'], $result['status']));
        } catch (Throwable $e) {
            abas_flash_set('error', $e->getMessage());
        }
    }
    abas_redirect('admin/sync.php');
}

$rows = $conn->query('SELECT * FROM sync_prefixes ORDER BY prefix')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Sync';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="text-xl font-semibold text-brand mb-4">Anlægssynkronisering</h1>
<p class="text-sm text-gray-600 mb-4">Synkroniserer via TrekantBrand <code>g_search_installations</code> i 100-blokke (fx <code>fab50</code> dækker suffix 5000–5099). Angiv start for at springe tomme blokke over.</p>
<form method="post" class="bg-white border rounded p-4 mb-4 flex flex-wrap gap-2 items-end">
    <input type="hidden" name="action" value="add">
    <div><label class="text-xs block">Prefix</label><input name="prefix" placeholder="fab" required class="border rounded px-2 py-1"></div>
    <div><label class="text-xs block">Start suffix</label><input name="min_suffix" type="number" min="0" value="0" class="border rounded px-2 py-1 w-24" title="Fx 5000 → første batch fab50"></div>
    <div><label class="text-xs block">Max suffix</label><input name="max_suffix" type="number" min="0" value="9999" class="border rounded px-2 py-1 w-24"></div>
    <button class="bg-brand text-white px-3 py-1 rounded">Tilføj</button>
</form>
<table class="w-full text-sm bg-white border rounded">
    <thead class="table-head"><tr><th class="p-2">Prefix</th><th class="p-2">Start</th><th class="p-2">Max</th><th class="p-2">Batch-eksempel</th><th class="p-2">Sidst</th><th class="p-2">Antal</th><th class="p-2"></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r):
        $min = (int) ($r['min_suffix'] ?? 0);
        $max = (int) $r['max_suffix'];
        $keys = abas_sync_batch_search_keys((string) $r['prefix'], $max, $min);
        $batchHint = $keys !== [] ? ($keys[0] . (count($keys) > 1 ? ' … ' . $keys[count($keys) - 1] : '')) : '—';
        $batchCount = count($keys);
    ?>
        <tr class="border-t">
            <td class="p-2 font-mono"><?= htmlspecialchars($r['prefix']) ?></td>
            <td class="p-2" colspan="3">
                <form method="post" class="flex flex-wrap gap-2 items-center">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <label class="text-xs text-gray-500">Start</label>
                    <input name="min_suffix" type="number" min="0" value="<?= $min ?>" class="border rounded px-2 py-1 w-20">
                    <label class="text-xs text-gray-500">Max</label>
                    <input name="max_suffix" type="number" min="0" value="<?= $max ?>" class="border rounded px-2 py-1 w-20">
                    <span class="font-mono text-xs text-gray-600" title="<?= $batchCount ?> API-kald"><?= htmlspecialchars($batchHint) ?> (<?= $batchCount ?>)</span>
                    <button class="text-brand underline text-xs">Gem</button>
                </form>
            </td>
            <td class="p-2"><?= htmlspecialchars((string) $r['last_sync_at']) ?></td>
            <td class="p-2"><?= (int) $r['last_sync_count'] ?></td>
            <td class="p-2">
                <form method="post" class="inline"><input type="hidden" name="action" value="run"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="text-brand underline">Kør sync</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p class="mt-4"><a href="<?= abas_url('admin/index.php') ?>" class="text-brand underline text-sm">Tilbage</a></p>
<?php require __DIR__ . '/../partials/footer.php';
