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
        header('Location: /login.php');
        exit;
    }
}

$pageTitle = 'Vælg adgangskode';
require __DIR__ . '/partials/header.php';
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow p-6 border">
    <h1 class="text-xl font-semibold text-brand mb-4">Vælg adgangskode</h1>
    <?php if (!$row): ?>
        <p class="text-red-600">Linket er ugyldigt eller udløbet.</p>
    <?php else: ?>
        <?php if ($error): ?><p class="text-red-600 mb-3"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" class="space-y-3">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div><label class="block text-sm">Ny adgangskode</label><input type="password" name="password" required minlength="8" class="w-full border rounded px-3 py-2"></div>
            <div><label class="block text-sm">Gentag adgangskode</label><input type="password" name="password2" required class="w-full border rounded px-3 py-2"></div>
            <button class="w-full bg-brand text-white py-2 rounded">Gem</button>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php';
