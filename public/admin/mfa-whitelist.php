<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';

$conn = abas_db();
$admin = abas_require_login();
abas_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = trim($_POST['ip_cidr'] ?? '');
    $label = trim($_POST['label'] ?? '');
    if ($ip !== '') {
        $uid = (int) $admin['id'];
        $stmt = $conn->prepare('INSERT INTO mfa_ip_whitelist (ip_cidr, label, active, created_by_user_id) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE label=VALUES(label), active=1');
        $stmt->bind_param('ssi', $ip, $label, $uid);
        $stmt->execute();
        $stmt->close();
        abas_flash_set('success', 'IP tilføjet til whitelist.');
    }
    if (!empty($_POST['delete_id'])) {
        $delId = (int) $_POST['delete_id'];
        $conn->query('DELETE FROM mfa_ip_whitelist WHERE id = ' . $delId);
        abas_flash_set('success', 'IP fjernet.');
    }
    abas_redirect('admin/mfa-whitelist.php');
}

$rows = $conn->query('SELECT * FROM mfa_ip_whitelist ORDER BY ip_cidr')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'MFA IP-whitelist';
$currentUser = $admin;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2"><a href="<?= abas_url('admin/index.php') ?>" class="abas-back-link">&larr; Admin</a></div>
<h1 class="abas-page-title">MFA IP-whitelist</h1>
<p class="abas-page-lead">IP-adresser der springer to-faktor godkendelse over ved login.</p>

<form method="post" class="abas-card max-w-lg abas-form mb-6">
    <div class="abas-field"><label class="abas-label">IP / CIDR</label><input name="ip_cidr" required class="abas-input" placeholder="192.168.1.10 eller 10.0.0.0/24"></div>
    <div class="abas-field"><label class="abas-label">Label</label><input name="label" class="abas-input" placeholder="Kontor"></div>
    <button class="abas-btn-primary">Tilføj</button>
</form>

<div class="abas-table-wrap">
    <table class="abas-table">
        <thead><tr><th>IP</th><th>Label</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="font-mono"><?= htmlspecialchars($r['ip_cidr']) ?></td>
                <td><?= htmlspecialchars((string) ($r['label'] ?? '')) ?></td>
                <td>
                    <form method="post" class="inline">
                        <input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>">
                        <button class="text-red-700 text-sm">Fjern</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../partials/footer.php';
