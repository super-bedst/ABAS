<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mfa.php';

$conn = abas_db();
$user = abas_require_login();
$userId = (int) $user['id'];

if (abas_user_mfa_enrolled($conn, $userId) && abas_user_mfa_method($conn, $userId) === 'passkey') {
    $creds = $conn->prepare('SELECT 1 FROM webauthn_credentials WHERE user_id = ? LIMIT 1');
    $creds->bind_param('i', $userId);
    $creds->execute();
    if ($creds->get_result()->fetch_row()) {
        $creds->close();
        abas_redirect('dashboard.php');
    }
    $creds->close();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credJson = trim($_POST['credential'] ?? '');
    if ($credJson !== '') {
        $data = json_decode($credJson, true);
        if (is_array($data) && !empty($data['id'])) {
            abas_mfa_store_credential($conn, $userId, (string) $data['id'], $credJson, 'Passkey');
            abas_redirect('dashboard.php');
        }
    }
    $error = 'Kunne ikke registrere passkey.';
}

$pageTitle = 'Opsæt passkey';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<div class="max-w-lg mx-auto">
    <h1 class="abas-page-title !text-xl">Opsæt passkey</h1>
    <p class="text-gray-600 mb-6">Registrér en passkey som standard to-faktor godkendelse ved login.</p>
    <?php if ($error): ?><p class="abas-alert-error mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post" id="enroll-form" class="abas-card abas-form">
        <input type="hidden" name="credential" id="credential">
        <button type="button" id="enroll-btn" class="abas-btn-primary">Opret passkey</button>
        <p class="abas-hint mt-3">Du kan også bede en administrator om SMS-kode som alternativ.</p>
    </form>
</div>
<script>
document.getElementById('enroll-btn').addEventListener('click', function () {
    if (!window.PublicKeyCredential) {
        alert('Din browser understøtter ikke passkeys.');
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
    }).catch(function (e) {
        alert('Passkey-oprettelse fejlede.');
    });
});
</script>
<?php require __DIR__ . '/partials/footer.php';
