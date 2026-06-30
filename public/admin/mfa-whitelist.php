<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/table_list.php';

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

$sort = abas_table_resolve_sort((string) ($_GET['sort'] ?? ''), ['ip', 'label'], 'ip');
$sortDir = abas_table_normalize_sort_dir((string) ($_GET['dir'] ?? 'asc'));
$listQuery = array_filter(['sort' => $sort !== 'ip' ? $sort : null, 'dir' => $sortDir !== 'asc' ? $sortDir : null]);
$orderCol = $sort === 'label' ? 'label' : 'ip_cidr';
$dirSql = $sortDir === 'desc' ? 'DESC' : 'ASC';
$rows = $conn->query("SELECT * FROM mfa_ip_whitelist ORDER BY $orderCol $dirSql, ip_cidr ASC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'MFA IP-whitelist';
$adminSectionTitle = 'MFA IP-whitelist';
$adminSectionLead = 'IP-adresser der springer to-faktor godkendelse over ved login.';
$currentUser = $admin;
require __DIR__ . '/../partials/admin_shell_start.php';
?>

<form method="post" class="abas-card max-w-lg abas-form mb-6">
    <div class="abas-field"><label class="abas-label">IP / CIDR</label><input name="ip_cidr" required class="abas-input" placeholder="192.168.1.10 eller 10.0.0.0/24"></div>
    <div class="abas-field"><label class="abas-label">Label</label><input name="label" class="abas-input" placeholder="Kontor"></div>
    <button class="abas-btn-primary">Tilføj</button>
</form>

<div class="abas-table-wrap">
    <table class="abas-table">
        <thead><tr>
            <?php abas_render_table_sort_th('IP', abas_table_sort_link('admin/mfa-whitelist.php', $listQuery, 'ip', $sort, $sortDir, ['ip', 'label'])); ?>
            <?php abas_render_table_sort_th('Label', abas_table_sort_link('admin/mfa-whitelist.php', $listQuery, 'label', $sort, $sortDir, ['ip', 'label'])); ?>
            <th scope="col"></th>
        </tr></thead>
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
<?php require __DIR__ . '/../partials/admin_shell_end.php';
