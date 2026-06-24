<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mfa.php';

$conn = abas_db();
$user = abas_require_mfa_enrollment();
$userId = (int) $user['id'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credJson = trim($_POST['credential'] ?? '');
    if ($credJson !== '') {
        $data = json_decode($credJson, true);
        if (is_array($data) && !empty($data['id'])) {
            abas_mfa_store_credential($conn, $userId, (string) $data['id'], $credJson, 'Passkey');
            abas_mfa_finish_enrollment($conn, $user);
            abas_redirect('dashboard.php');
        }
    }
    $error = 'Kunne ikke registrere passkey.';
}

$pageTitle = 'Opsæt passkey';
require __DIR__ . '/partials/public-header.php';
?>
<div class="max-w-md mx-auto px-4 py-12">
    <div class="abas-auth-card">
        <h1 class="abas-page-title !text-xl mb-4">Opsæt passkey</h1>
        <p class="text-gray-600 mb-4">Før du kan bruge systemet, skal du oprette en passkey til to-faktor godkendelse ved login.</p>
        <div class="abas-portal-note text-sm mb-6">
            <p class="font-semibold text-gray-900 mb-1">Brug din mobiltelefon</p>
            <p>Opret passkey på den mobil, du vil logge ind fra. Face ID og Touch ID følger ikke med til computeren — du skal bruge samme enhed ved login fremover.</p>
        </div>
        <?php if ($error): ?><p class="abas-alert-error !mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" id="enroll-form" class="abas-form">
            <input type="hidden" name="credential" id="credential">
            <button type="button" id="enroll-btn" class="abas-btn-primary abas-btn-block">Opret passkey</button>
            <p class="abas-hint mt-3 text-center">Du kan også bede en administrator om SMS-kode som alternativ.</p>
        </form>
    </div>
</div>
<script>
document.getElementById('enroll-btn').addEventListener('click', function () {
    if (!window.PublicKeyCredential) {
        alert('Din browser understøtter ikke passkeys. Brug en mobil med Face ID eller Touch ID.');
        return;
    }
    var userId = <?= (int) $userId ?>;
    var userBytes = new TextEncoder().encode(String(userId));
    var challenge = new Uint8Array(32);
    crypto.getRandomValues(challenge);
    navigator.credentials.create({
        publicKey: {
            challenge: challenge,
            rp: { name: 'ABA Service', id: location.hostname },
            user: {
                id: userBytes,
                name: <?= json_encode($user['email']) ?>,
                displayName: <?= json_encode($user['username']) ?>
            },
            pubKeyCredParams: [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
            authenticatorSelection: { userVerification: 'preferred', residentKey: 'preferred' },
            timeout: 60000
        }
    }).then(function (cred) {
        var response = cred.response;
        document.getElementById('credential').value = JSON.stringify({
            id: cred.id,
            type: cred.type,
            publicKey: response.publicKey ? btoa(String.fromCharCode.apply(null, new Uint8Array(response.publicKey))) : ''
        });
        document.getElementById('enroll-form').submit();
    }).catch(function () {
        alert('Passkey-oprettelse fejlede. Prøv igen på din mobil.');
    });
});
</script>
<?php
$portalShowNav = false;
require __DIR__ . '/partials/public-footer.php';
