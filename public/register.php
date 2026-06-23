<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/roles.php';

$conn = abas_db();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $domain = abas_email_domain($email);
    $installer = abas_installer_approved_for_domain($conn, $domain);
    if (!$installer) {
        $error = 'E-mail-domænet er ikke godkendt til montør-registrering.';
    } elseif ($username === '' || strlen($username) < 3) {
        $error = 'Vælg et brugernavn (min. 3 tegn).';
    } else {
        $chk = $conn->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
        $chk->bind_param('ss', $email, $username);
        $chk->execute();
        if ($chk->get_result()->fetch_row()) {
            $error = 'E-mail eller brugernavn findes allerede.';
        } else {
            $instId = (int) $installer['id'];
            $stmt = $conn->prepare(
                'INSERT INTO users (email, username, role, phone, installer_id, active) VALUES (?, ?, "montor", ?, ?, 1)'
            );
            $stmt->bind_param('sssi', $email, $username, $phone, $instId);
            $stmt->execute();
            $uid = (int) $stmt->insert_id;
            $stmt->close();
            abas_password_send_flow_email($conn, $uid, 'welcome');
            $success = 'Konto oprettet. Tjek e-mail for at vælge adgangskode.';
        }
        $chk->close();
    }
}

$pageTitle = 'Registrering';
require __DIR__ . '/partials/header.php';
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow p-6 border">
    <h1 class="text-xl font-semibold text-brand mb-2">Montør-registrering</h1>
    <p class="text-sm text-gray-600 mb-4">Kun e-mail fra godkendte installatør-domæner.</p>
    <?php if ($error): ?><p class="text-red-600 mb-3"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="text-green-700 mb-3"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if (!$success): ?>
    <form method="post" class="space-y-3">
        <div><label class="block text-sm">E-mail</label><input name="email" type="email" required class="w-full border rounded px-3 py-2"></div>
        <div><label class="block text-sm">Brugernavn</label><input name="username" required class="w-full border rounded px-3 py-2"></div>
        <div><label class="block text-sm">Telefon (valgfri)</label><input name="phone" class="w-full border rounded px-3 py-2"></div>
        <button class="w-full bg-brand text-white py-2 rounded">Opret konto</button>
    </form>
    <?php endif; ?>
    <p class="mt-4 text-sm"><a href="/login.php" class="text-brand underline">Tilbage til login</a></p>
</div>
<?php require __DIR__ . '/partials/footer.php';
