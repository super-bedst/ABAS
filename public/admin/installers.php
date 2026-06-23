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
<h1 class="abas-page-title !text-xl">Godkendte installatører</h1>
<form method="post" class="abas-card mb-4 max-w-lg abas-form">
    <div class="abas-field"><label class="abas-label">Firmanavn</label><input name="company_name" required class="abas-input"></div>
    <div class="abas-field"><label class="abas-label">E-mail-domæne</label><input name="email_domain" required placeholder="firma.dk" class="abas-input"></div>
    <button class="abas-btn-primary">Tilføj</button>
</form>
<div class="abas-table-wrap">
<table class="abas-table">
    <thead><tr><th>Firma</th><th>Domæne</th><th>Aktiv</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr><td><?= htmlspecialchars($r['company_name']) ?></td><td><?= htmlspecialchars($r['email_domain']) ?></td><td class="text-center"><?= $r['active'] ? 'Ja' : 'Nej' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="mt-4"><a href="<?= abas_url('admin/index.php') ?>" class="abas-link text-sm">Tilbage</a></p>
<?php require __DIR__ . '/../partials/footer.php';
