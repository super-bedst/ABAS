<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/installation_sync.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['vagtcentral', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_user') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $username = trim($_POST['username'] ?? '');
        $misc = strtolower(trim($_POST['miscno2'] ?? ''));
        $chk = $conn->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
        $chk->bind_param('ss', $email, $username);
        $chk->execute();
        if ($chk->get_result()->fetch_row()) {
            abas_flash_set('error', 'Bruger findes allerede.');
        } else {
            $vcId = (int) $user['id'];
            $stmt = $conn->prepare(
                'INSERT INTO users (email, username, role, active, created_by_user_id) VALUES (?, ?, "anlaegsejer", 1, ?)'
            );
            $stmt->bind_param('ssi', $email, $username, $vcId);
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

$owners = $conn->query("SELECT id, email, username FROM users WHERE role='anlaegsejer' ORDER BY username")->fetch_all(MYSQLI_ASSOC);
$installations = $conn->query('SELECT id, miscno2, name FROM installations ORDER BY miscno2 LIMIT 200')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Anlægsbrugere';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<h1 class="text-2xl font-semibold text-brand mb-4">Anlægsbrugere</h1>

<div class="grid lg:grid-cols-2 gap-6">
    <form method="post" class="bg-white border rounded p-4 space-y-3">
        <h2 class="font-semibold">Opret anlægsbruger</h2>
        <input type="hidden" name="action" value="create_user">
        <input name="email" type="email" required placeholder="E-mail" class="w-full border rounded px-3 py-2">
        <input name="username" required placeholder="Brugernavn" class="w-full border rounded px-3 py-2">
        <input name="miscno2" placeholder="Anlægsnr. (valgfri)" class="w-full border rounded px-3 py-2 font-mono">
        <button class="bg-brand text-white px-4 py-2 rounded">Opret</button>
    </form>

    <form method="post" class="bg-white border rounded p-4 space-y-3">
        <h2 class="font-semibold">Tilknyt eksisterende bruger til anlæg</h2>
        <input type="hidden" name="action" value="link">
        <select name="user_id" class="w-full border rounded px-3 py-2">
            <?php foreach ($owners as $o): ?>
                <option value="<?= (int) $o['id'] ?>"><?= htmlspecialchars($o['username']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="installation_id" class="w-full border rounded px-3 py-2">
            <?php foreach ($installations as $i): ?>
                <option value="<?= (int) $i['id'] ?>"><?= htmlspecialchars((string) $i['miscno2']) ?> — <?= htmlspecialchars((string) $i['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="border px-4 py-2 rounded">Tilknyt</button>
    </form>
</div>

<div class="mt-6 bg-white border rounded overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="table-head"><tr><th class="p-2 text-left">Bruger</th><th class="p-2 text-left">E-mail</th></tr></thead>
        <tbody>
        <?php foreach ($owners as $o): ?>
            <tr class="border-t"><td class="p-2"><?= htmlspecialchars($o['username']) ?></td><td class="p-2"><?= htmlspecialchars($o['email']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/partials/footer.php';
