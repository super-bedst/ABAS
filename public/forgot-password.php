<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/password_flow.php';

$conn = abas_db();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $stmt = $conn->prepare('SELECT id FROM users WHERE (email = ? OR username = ?) AND active = 1 LIMIT 1');
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($user) {
        abas_password_send_flow_email($conn, (int) $user['id'], 'reset');
    }
    $msg = 'Hvis kontoen findes, er der sendt et nulstillingslink.';
}

$pageTitle = 'Glemt adgangskode';
require __DIR__ . '/partials/header.php';
?>
<div class="abas-auth-card">
    <h1 class="abas-page-title !text-xl">Glemt adgangskode</h1>
    <?php if ($msg): ?><p class="abas-alert-success !mb-4"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
    <form method="post" class="abas-form">
        <div class="abas-field">
            <label class="abas-label" for="login">E-mail eller brugernavn</label>
            <input id="login" name="login" required class="abas-input" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
        </div>
        <button class="abas-btn-primary abas-btn-block">Send link</button>
    </form>
    <p class="mt-4 text-sm"><a href="<?= abas_url('login.php') ?>" class="abas-link">Tilbage</a></p>
</div>
<?php require __DIR__ . '/partials/footer.php';
