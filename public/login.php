<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/mfa.php';

$conn = abas_db();
if (!empty($_SESSION['user_id']) && !empty($_SESSION['mfa_verified'])) {
    abas_redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = $conn->prepare('SELECT * FROM users WHERE (email = ? OR username = ?) LIMIT 1');
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || empty($user['password_hash']) || !password_verify($pass, $user['password_hash'])) {
        $error = abas_login_error_for_user($user);
    } elseif (($user['registration_status'] ?? 'approved') !== 'approved' || !(int) $user['active']) {
        $error = abas_login_error_for_user($user);
    } else {
        $_SESSION['mfa_pending_user_id'] = (int) $user['id'];
        unset($_SESSION['mfa_verified'], $_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name']);

        if (abas_mfa_ip_whitelisted($conn) || !abas_mfa_required_globally($conn)) {
            abas_login_user($user);
            abas_mfa_complete_verification();
            if (!abas_user_mfa_enrolled($conn, (int) $user['id'])) {
                abas_redirect('mfa-enroll.php');
            }
            abas_redirect('dashboard.php');
        }

        $method = abas_user_mfa_method($conn, (int) $user['id']);
        if ($method === 'sms_otp') {
            abas_mfa_send_otp($conn, $user);
        }
        abas_redirect('mfa-verify.php');
    }
}

$pageTitle = 'Log ind';
require __DIR__ . '/partials/public-header.php';
?>
<div class="max-w-md mx-auto px-4 py-12">
    <div class="abas-auth-card">
        <p class="text-xs uppercase tracking-wider text-brand font-semibold mb-2">TrekantBrand</p>
        <h1 class="abas-page-title !text-xl mb-4">Log ind</h1>
        <?php if ($error): ?><p class="abas-alert-error !mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" class="abas-form" data-abas-loading="Logger ind…">
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
            <a href="<?= abas_url('register.php') ?>" class="abas-link">Anmod om adgang</a>
        </p>
    </div>
</div>
<?php
$portalShowNav = false;
require __DIR__ . '/partials/public-footer.php';
