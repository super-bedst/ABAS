<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/service.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['vagtcentral', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_user') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $username = trim($_POST['username'] ?? '');
        $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
        $misc = strtolower(trim($_POST['miscno2'] ?? ''));
        if (!abas_validate_phone($phone)) {
            abas_flash_set('error', 'Angiv et gyldigt telefonnummer (min. 8 cifre).');
            abas_redirect('vc-anlaegsbrugere.php');
        }
        $chk = $conn->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
        $chk->bind_param('ss', $email, $username);
        $chk->execute();
        if ($chk->get_result()->fetch_row()) {
            abas_flash_set('error', 'Bruger findes allerede.');
        } else {
            $vcId = (int) $user['id'];
            $stmt = $conn->prepare(
                'INSERT INTO users (email, username, role, phone, active, created_by_user_id) VALUES (?, ?, "anlaegsejer", ?, 1, ?)'
            );
            $stmt->bind_param('sssi', $email, $username, $phone, $vcId);
            $stmt->execute();
            $newId = (int) $stmt->insert_id;
            $stmt->close();
            abas_password_send_flow_email($conn, $newId, 'welcome');
            $installation = abas_find_installation_by_miscno2($conn, $misc);
            if ($installation) {
                $iid = (int) $installation['id'];
                $link = $conn->prepare('INSERT IGNORE INTO user_installations (user_id, installation_id) VALUES (?, ?)');
                $link->bind_param('ii', $newId, $iid);
                $link->execute();
                $link->close();
            }
            abas_flash_set('success', 'Anlægsbruger oprettet og linket.');
        }
        $chk->close();
    } elseif ($action === 'link') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $iid = (int) ($_POST['installation_id'] ?? 0);
        $link = $conn->prepare('INSERT IGNORE INTO user_installations (user_id, installation_id) VALUES (?, ?)');
        $link->bind_param('ii', $uid, $iid);
        $link->execute();
        $link->close();
        abas_flash_set('success', 'Tilknytning gemt.');
    }
    abas_redirect('vc-anlaegsbrugere.php');
}

$owners = $conn->query("SELECT id, email, username, phone FROM users WHERE role='anlaegsejer' ORDER BY username")->fetch_all(MYSQLI_ASSOC);
$ownerInstallations = abas_user_installations_with_service_status($conn);
$installations = $conn->query('SELECT id, miscno2, name FROM installations ORDER BY miscno2 LIMIT 200')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Anlægsbrugere';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<h1 class="abas-page-title">Anlægsbrugere</h1>
<p class="abas-page-lead">Opret og tilknyt anlægsejere til deres anlæg.</p>

<div class="grid lg:grid-cols-2 gap-6">
    <form method="post" class="abas-card abas-form">
        <h2 class="abas-card-title">Opret anlægsbruger</h2>
        <input type="hidden" name="action" value="create_user">
        <div class="abas-field"><label class="abas-label">E-mail</label><input name="email" type="email" required class="abas-input"></div>
        <div class="abas-field"><label class="abas-label">Brugernavn</label><input name="username" required class="abas-input"></div>
        <div class="abas-field"><label class="abas-label">Telefon</label><input name="phone" required placeholder="+45..." class="abas-input"></div>
        <div class="abas-field"><label class="abas-label">Anlægsnr. (valgfri)</label><input name="miscno2" placeholder="fab0100" class="abas-input font-mono"></div>
        <button class="abas-btn-primary">Opret</button>
    </form>

    <form method="post" class="abas-card abas-form">
        <h2 class="abas-card-title">Tilknyt eksisterende bruger til anlæg</h2>
        <input type="hidden" name="action" value="link">
        <div class="abas-field"><label class="abas-label">Bruger</label>
        <select name="user_id" class="abas-select">
            <?php foreach ($owners as $o): ?>
                <option value="<?= (int) $o['id'] ?>"><?= htmlspecialchars($o['username']) ?></option>
            <?php endforeach; ?>
        </select></div>
        <div class="abas-field"><label class="abas-label">Anlæg</label>
        <select name="installation_id" class="abas-select">
            <?php foreach ($installations as $i): ?>
                <option value="<?= (int) $i['id'] ?>"><?= htmlspecialchars((string) $i['miscno2']) ?> — <?= htmlspecialchars((string) $i['name']) ?></option>
            <?php endforeach; ?>
        </select></div>
        <button class="abas-btn-secondary">Tilknyt</button>
    </form>
</div>

<div class="mt-6 abas-table-wrap">
    <table class="abas-table">
        <thead><tr><th>Bruger</th><th>E-mail</th><th>Telefon</th><th>Anlæg</th></tr></thead>
        <tbody>
        <?php foreach ($owners as $o): ?>
            <?php $linked = $ownerInstallations[(int) $o['id']] ?? []; ?>
            <tr>
                <td><?= htmlspecialchars($o['username']) ?></td>
                <td><?= htmlspecialchars($o['email']) ?></td>
                <td><?= htmlspecialchars((string) ($o['phone'] ?? '—')) ?></td>
                <td>
                    <?php if ($linked === []): ?>
                        <span class="text-gray-400 text-sm">Ingen anlæg</span>
                    <?php else: ?>
                        <div class="abas-installation-badges">
                            <?php foreach ($linked as $inst): ?>
                                <a
                                    href="<?= abas_url('installation.php?id=' . (int) $inst['installation_id']) ?>"
                                    class="<?= $inst['in_service'] ? 'abas-badge-in-service' : 'abas-badge-ok' ?> hover:opacity-90"
                                    title="<?= $inst['in_service'] ? 'I service' : 'Normal drift' ?>"
                                ><?= htmlspecialchars($inst['miscno2']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-gray-500">
    <span class="abas-badge-in-service">fab0100</span> = i service &nbsp;
    <span class="abas-badge-ok">fab0100</span> = normal drift
</p>
<?php require __DIR__ . '/partials/footer.php';
