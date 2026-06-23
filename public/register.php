<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/users.php';

$conn = abas_db();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
    $domain = abas_email_domain($email);
    $installer = abas_installer_approved_for_domain($conn, $domain);
    if (!$installer) {
        $error = 'E-mail-domænet er ikke godkendt til montør-registrering.';
    } elseif ($username === '' || strlen($username) < 3) {
        $error = 'Vælg et brugernavn (min. 3 tegn).';
    } elseif (!abas_validate_phone($phone)) {
        $error = 'Angiv et gyldigt telefonnummer (min. 8 cifre).';
    } else {
        $smsCode = trim($_POST['sms_code'] ?? '');
        if (!abas_validate_sms_code($smsCode)) {
            $error = 'SMS-kode skal være mindst 6 tegn.';
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
            abas_set_user_sms_code($conn, $uid, $smsCode);
            abas_password_send_flow_email($conn, $uid, 'welcome');
            $success = 'Konto oprettet. Tjek e-mail for at vælge adgangskode.';
        }
        $chk->close();
        }
    }
}

$pageTitle = 'Registrering';
require __DIR__ . '/partials/header.php';
?>
<div class="abas-auth-card">
    <h1 class="abas-page-title !text-xl">Montør-registrering</h1>
    <p class="text-sm text-gray-600 mb-4">Kun e-mail fra godkendte installatør-domæner. Firmanavn tilknyttes automatisk.</p>
    <?php if ($error): ?><p class="abas-alert-error !mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="abas-alert-success !mb-4"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if (!$success): ?>
    <form method="post" class="abas-form">
        <div class="abas-field"><label class="abas-label" for="email">E-mail</label><input id="email" name="email" type="email" required class="abas-input"></div>
        <div class="abas-field"><label class="abas-label" for="username">Brugernavn</label><input id="username" name="username" required class="abas-input"></div>
        <div class="abas-field"><label class="abas-label" for="phone">Telefon</label><input id="phone" name="phone" required class="abas-input" placeholder="+45..."></div>
        <div class="abas-field">
            <label class="abas-label" for="sms_code">SMS-kode</label>
            <input id="sms_code" name="sms_code" required minlength="6" autocomplete="off" class="abas-input font-mono" placeholder="Min. 6 tegn">
            <p class="abas-hint">Bruges sammen med dit telefonnummer ved SMS-kommandoer til anlæg.</p>
        </div>
        <button class="abas-btn-primary abas-btn-block">Opret konto</button>
    </form>
    <?php endif; ?>
    <p class="mt-4 text-sm"><a href="<?= abas_url('login.php') ?>" class="abas-link">Tilbage til login</a></p>
</div>
<?php require __DIR__ . '/partials/footer.php';
