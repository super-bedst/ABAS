<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/password_flow.php';

$conn = abas_db();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=? AND active=1 LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($user) {
        abas_password_send_flow_email($conn, (int) $user['id'], 'reset');
    }
    $msg = 'Hvis e-mail findes, er der sendt et nulstillingslink.';
}

$pageTitle = 'Glemt adgangskode';
require __DIR__ . '/partials/header.php';
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow p-6 border">
    <h1 class="text-xl font-semibold text-brand mb-4">Glemt adgangskode</h1>
    <?php if ($msg): ?><p class="text-green-700 mb-3"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
    <form method="post" class="space-y-3">
        <div><label class="block text-sm">E-mail</label><input name="email" type="email" required class="w-full border rounded px-3 py-2"></div>
        <button class="w-full bg-brand text-white py-2 rounded">Send link</button>
    </form>
    <p class="mt-4 text-sm"><a href="/login.php" class="text-brand underline">Tilbage</a></p>
</div>
<?php require __DIR__ . '/partials/footer.php';
