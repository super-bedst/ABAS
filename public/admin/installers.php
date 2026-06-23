<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['company_name'] ?? '');
    $domain = strtolower(trim($_POST['email_domain'] ?? ''));
    if ($name && $domain) {
        $uid = (int) $user['id'];
        $stmt = $conn->prepare(
            'INSERT INTO approved_installers (company_name, email_domain, active, approved_at, approved_by_user_id)
             VALUES (?, ?, 1, NOW(), ?) ON DUPLICATE KEY UPDATE company_name=VALUES(company_name), active=1'
        );
        $stmt->bind_param('ssi', $name, $domain, $uid);
        $stmt->execute();
        $stmt->close();
        abas_flash_set('success', 'Installatør gemt.');
    }
    abas_redirect('admin/installers.php');
}

$rows = $conn->query('SELECT * FROM approved_installers ORDER BY company_name')->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Installatører';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="text-xl font-semibold text-brand mb-4">Godkendte installatører</h1>
<form method="post" class="bg-white border rounded p-4 mb-4 max-w-lg space-y-2">
    <input name="company_name" required placeholder="Firmanavn" class="w-full border rounded px-3 py-2">
    <input name="email_domain" required placeholder="e-mail-domæne (fx firma.dk)" class="w-full border rounded px-3 py-2">
    <button class="bg-brand text-white px-4 py-2 rounded">Tilføj</button>
</form>
<table class="w-full text-sm bg-white border rounded overflow-hidden">
    <thead class="table-head"><tr><th class="p-2 text-left">Firma</th><th class="p-2 text-left">Domæne</th><th class="p-2">Aktiv</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr class="border-t"><td class="p-2"><?= htmlspecialchars($r['company_name']) ?></td><td class="p-2"><?= htmlspecialchars($r['email_domain']) ?></td><td class="p-2 text-center"><?= $r['active'] ? 'Ja' : 'Nej' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p class="mt-4"><a href="<?= abas_url('admin/index.php') ?>" class="text-brand underline text-sm">Tilbage</a></p>
<?php require __DIR__ . '/../partials/footer.php';
