<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/roles.php';

$conn = abas_db();
if (!empty($_SESSION['user_id'])) {
    abas_redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = $conn->prepare('SELECT * FROM users WHERE (email = ? OR username = ?) AND active = 1 LIMIT 1');
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($user && $user['password_hash'] && password_verify($pass, $user['password_hash'])) {
        abas_login_user($user);
        abas_redirect('dashboard.php');
    }
    $error = 'Forkert login eller adgangskode.';
}

$pageTitle = 'Log ind';
require __DIR__ . '/partials/header.php';
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow p-6 border border-brand-gold/30">
    <h1 class="text-xl font-semibold text-brand mb-4">Log ind</h1>
    <?php if ($error): ?><p class="text-red-600 mb-3"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post" class="space-y-4">
        <div>
            <label class="block text-sm mb-1">E-mail eller brugernavn</label>
            <input name="login" required class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm mb-1">Adgangskode</label>
            <input type="password" name="password" required class="w-full border rounded px-3 py-2">
        </div>
        <button type="submit" class="w-full bg-brand text-white py-2 rounded hover:opacity-90">Log ind</button>
    </form>
    <p class="mt-4 text-sm text-center">
        <a href="<?= abas_url('forgot-password.php') ?>" class="text-brand underline">Glemt adgangskode</a>
        &middot;
        <a href="<?= abas_url('register.php') ?>" class="text-brand underline">Montør-registrering</a>
    </p>
</div>
<?php require __DIR__ . '/partials/footer.php';
