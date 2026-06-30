<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/table_list.php';

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

$sort = abas_table_resolve_sort((string) ($_GET['sort'] ?? ''), ['name', 'role', 'created', 'active'], 'created');
$sortDir = abas_table_normalize_sort_dir((string) ($_GET['dir'] ?? 'desc'));
$listQuery = array_filter(['sort' => $sort !== 'created' ? $sort : null, 'dir' => $sortDir !== 'desc' ? $sortDir : null]);
$rows = $conn->query('SELECT id, name, role, active, created_at FROM api_tokens')->fetch_all(MYSQLI_ASSOC);
$rows = abas_table_sort_rows($rows, $sort, $sortDir, [
    'name' => static fn (array $row): string => (string) ($row['name'] ?? ''),
    'role' => static fn (array $row): string => (string) ($row['role'] ?? ''),
    'created' => static fn (array $row): string => (string) ($row['created_at'] ?? ''),
    'active' => static fn (array $row): string => !empty($row['active']) ? '1' : '0',
]);
$pageTitle = 'API-tokens';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="text-xl font-semibold text-brand mb-4">API-tokens</h1>
<?php if ($newToken): ?>
<div class="mb-4 p-3 bg-amber-50 border border-amber-300 rounded font-mono text-sm break-all"><?= htmlspecialchars($newToken) ?></div>
<?php endif; ?>
<form method="post" class="bg-white border rounded p-4 mb-4 flex flex-wrap gap-2 items-end">
    <input name="name" required placeholder="Navn (fx 3CX vagtcentral)" class="border rounded px-3 py-2">
    <select name="role" class="border rounded px-3 py-2">
        <option value="vagtcentral">Vagtcentral</option>
        <option value="montor">Montør</option>
        <option value="admin">Admin</option>
    </select>
    <button class="bg-brand text-white px-4 py-2 rounded">Opret token</button>
</form>
<p class="abas-hint mb-4">Brug token som <code>Authorization: Bearer …</code> eller <code>?key=…</code> på URL (fx 3CX uden custom headers) til <code>/api/v1/3cx/call</code>. Vælg rolle <strong>vagtcentral</strong> til 3CX.</p>
<div class="abas-table-wrap">
<table class="abas-table">
    <thead><tr>
        <?php abas_render_table_sort_th('Navn', abas_table_sort_link('admin/api-tokens.php', $listQuery, 'name', $sort, $sortDir, ['name', 'role', 'created', 'active'])); ?>
        <?php abas_render_table_sort_th('Rolle', abas_table_sort_link('admin/api-tokens.php', $listQuery, 'role', $sort, $sortDir, ['name', 'role', 'created', 'active'])); ?>
        <?php abas_render_table_sort_th('Oprettet', abas_table_sort_link('admin/api-tokens.php', $listQuery, 'created', $sort, $sortDir, ['name', 'role', 'created', 'active'])); ?>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= htmlspecialchars($r['name']) ?></td><td><?= abas_role_label($r['role']) ?></td><td><?= htmlspecialchars($r['created_at']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="mt-4"><a href="<?= abas_url('admin/index.php') ?>" class="text-brand underline text-sm">Tilbage</a></p>
<?php require __DIR__ . '/../partials/footer.php';
