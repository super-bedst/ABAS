<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/password_flow.php';

$conn = abas_db();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$row = $token ? abas_password_validate_token($conn, $token) : null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (strlen($pass) < 8) {
        $error = 'Adgangskode skal være mindst 8 tegn.';
    } elseif ($pass !== $pass2) {
        $error = 'Adgangskoder matcher ikke.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $uid = (int) $row['user_id'];
        $stmt = $conn->prepare('UPDATE users SET password_hash=?, password_set_at=NOW() WHERE id=?');
        $stmt->bind_param('si', $hash, $uid);
        $stmt->execute();
        $stmt->close();
        abas_password_consume_token($conn, $token);
        abas_access_set_due($conn, $uid);
        abas_flash_set('success', 'Adgangskode gemt. Du kan nu logge ind.');
        abas_redirect('login.php');
    }
}

$pageTitle = 'Vælg adgangskode';
require __DIR__ . '/partials/header.php';
?>
<div class="abas-auth-card">
    <h1 class="abas-page-title !text-xl">Vælg adgangskode</h1>
    <?php if (!$row): ?>
        <p class="abas-alert-error">Linket er ugyldigt eller udløbet.</p>
    <?php else: ?>
        <?php if ($error): ?><p class="abas-alert-error !mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" class="abas-form">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="abas-field"><label class="abas-label" for="password">Ny adgangskode</label><input id="password" type="password" name="password" required minlength="8" class="abas-input"></div>
            <div class="abas-field"><label class="abas-label" for="password2">Gentag adgangskode</label><input id="password2" type="password" name="password2" required class="abas-input"></div>
            <button class="abas-btn-primary abas-btn-block">Gem</button>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php';
