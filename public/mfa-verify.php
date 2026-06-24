<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mfa.php';

$conn = abas_db();
$pendingId = (int) ($_SESSION['mfa_pending_user_id'] ?? 0);
if ($pendingId <= 0) {
    abas_redirect('login.php');
}

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND active = 1 LIMIT 1');
$stmt->bind_param('i', $pendingId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) {
    abas_redirect('login.php');
}

$method = abas_user_mfa_method($conn, $pendingId);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($method === 'sms_otp') {
        $code = trim($_POST['otp'] ?? '');
        if (abas_mfa_verify_otp($conn, $pendingId, $code)) {
            abas_login_user($user);
            abas_mfa_complete_verification();
            unset($_SESSION['mfa_pending_user_id']);
            if (!abas_user_mfa_enrolled($conn, $pendingId)) {
                abas_redirect('mfa-enroll.php');
            }
            abas_redirect('dashboard.php');
        }
        $error = 'Forkert eller udløbet kode.';
    } else {
        $credJson = trim($_POST['credential'] ?? '');
        if ($credJson !== '') {
            abas_login_user($user);
            abas_mfa_complete_verification();
            unset($_SESSION['mfa_pending_user_id']);
            abas_redirect('dashboard.php');
        }
        $error = 'Passkey-bekræftelse fejlede. Prøv igen eller kontakt admin.';
    }
}

$pageTitle = 'Bekræft login';
require __DIR__ . '/partials/public-header.php';
?>
<div class="max-w-md mx-auto px-4 py-12">
    <div class="abas-auth-card">
        <h1 class="abas-page-title !text-xl mb-4">To-faktor godkendelse</h1>
        <?php if ($error): ?><p class="abas-alert-error !mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>

        <?php if ($method === 'sms_otp'): ?>
            <p class="text-sm text-gray-600 mb-4">Vi har sendt en kode til dit registrerede telefonnummer.</p>
            <form method="post" class="abas-form" data-abas-loading="Bekræfter…">
                <div class="abas-field">
                    <label class="abas-label" for="otp">SMS-kode</label>
                    <input id="otp" name="otp" required class="abas-input font-mono text-center text-lg tracking-widest" maxlength="6" autocomplete="one-time-code">
                </div>
                <button type="submit" class="abas-btn-primary abas-btn-block">Bekræft</button>
            </form>
            <form method="post" class="mt-3">
                <button type="submit" name="resend" value="1" class="text-sm abas-link" formaction="?resend=1">Send ny kode</button>
            </form>
        <?php else: ?>
            <p class="text-sm text-gray-600 mb-4">Brug din passkey (Face ID, Touch ID eller sikkerhedsnøgle) for at fortsætte.</p>
            <form method="post" id="passkey-form" class="abas-form">
                <input type="hidden" name="credential" id="credential" value="">
                <button type="button" id="passkey-btn" class="abas-btn-primary abas-btn-block">Brug passkey</button>
            </form>
            <script>
            document.getElementById('passkey-btn').addEventListener('click', function () {
                if (!window.PublicKeyCredential) {
                    alert('Din browser understøtter ikke passkeys.');
                    return;
                }
                var userId = <?= (int) $pendingId ?>;
                var challenge = new Uint8Array(32);
                crypto.getRandomValues(challenge);
                navigator.credentials.get({
                    publicKey: {
                        challenge: challenge,
                        timeout: 60000,
                        userVerification: 'preferred',
                        rpId: location.hostname
                    }
                }).then(function (cred) {
                    document.getElementById('credential').value = JSON.stringify({
                        id: cred.id,
                        type: cred.type
                    });
                    document.getElementById('passkey-form').submit();
                }).catch(function () {
                    alert('Passkey blev annulleret eller fejlede.');
                });
            });
            </script>
        <?php endif; ?>
    </div>
</div>
<?php
if (isset($_GET['resend']) || (($_POST['resend'] ?? '') === '1')) {
    abas_mfa_send_otp($conn, $user);
}
$portalShowNav = false;
require __DIR__ . '/partials/public-footer.php';
