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
<div class="abas-auth-card">
    <p class="text-xs uppercase tracking-wider text-brand font-semibold mb-2">TrekantBrand</p>
    <h1 class="abas-page-title !text-xl mb-4">Log ind</h1>
    <?php if ($error): ?><p class="abas-alert-error !mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post" class="abas-form">
        <div class="abas-field">
            <label class="abas-label" for="login">E-mail eller brugernavn</label>
            <input id="login" name="login" required class="abas-input" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
        </div>
        <div class="abas-field">
            <label class="abas-label" for="password">Adgangskode</label>
            <input id="password" type="password" name="password" required class="abas-input">
        </div>
        <button type="submit" class="abas-btn-primary abas-btn-block">Log ind</button>
    </form>
    <p class="mt-5 text-sm text-center text-gray-600">
        <a href="<?= abas_url('forgot-password.php') ?>" class="abas-link">Glemt adgangskode</a>
        <span class="mx-1">·</span>
        <a href="<?= abas_url('register.php') ?>" class="abas-link">Montør-registrering</a>
    </p>
</div>
<?php require __DIR__ . '/partials/footer.php';
