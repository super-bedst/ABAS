<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);
$newToken = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $role = $_POST['role'] ?? 'vagtcentral';
    if (!in_array($role, ['vagtcentral', 'montor', 'admin'], true)) {
        $role = 'vagtcentral';
    }
    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $stmt = $conn->prepare('INSERT INTO api_tokens (name, token_hash, role) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $hash, $role);
    $stmt->execute();
    $stmt->close();
    $newToken = $raw;
    abas_flash_set('success', 'Token oprettet — kopiér den nu (vises kun én gang).');
}

$rows = $conn->query('SELECT id, name, role, active, created_at FROM api_tokens ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'API-tokens';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="text-xl font-semibold text-brand mb-4">API-tokens</h1>
<?php if ($newToken): ?>
<div class="mb-4 p-3 bg-amber-50 border border-amber-300 rounded font-mono text-sm break-all"><?= htmlspecialchars($newToken) ?></div>
<?php endif; ?>
<form method="post" class="bg-white border rounded p-4 mb-4 flex flex-wrap gap-2 items-end">
    <input name="name" required placeholder="Navn (fx telefonsystem)" class="border rounded px-3 py-2">
    <select name="role" class="border rounded px-3 py-2">
        <option value="vagtcentral">Vagtcentral</option>
        <option value="montor">Montør</option>
        <option value="admin">Admin</option>
    </select>
    <button class="bg-brand text-white px-4 py-2 rounded">Opret token</button>
</form>
<table class="w-full text-sm bg-white border rounded">
    <thead class="table-head"><tr><th class="p-2">Navn</th><th class="p-2">Rolle</th><th class="p-2">Oprettet</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr class="border-t"><td class="p-2"><?= htmlspecialchars($r['name']) ?></td><td class="p-2"><?= abas_role_label($r['role']) ?></td><td class="p-2"><?= htmlspecialchars($r['created_at']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p class="mt-4"><a href="<?= abas_url('admin/index.php') ?>" class="text-brand underline text-sm">Tilbage</a></p>
<?php require __DIR__ . '/../partials/footer.php';
