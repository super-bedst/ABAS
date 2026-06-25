<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/user_management.php';

$conn = abas_db();
$user = abas_require_login();
$userId = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_phone') {
        $error = abas_update_user_phone($conn, $userId, (string) ($_POST['phone'] ?? ''));
        abas_flash_set($error === null ? 'success' : 'error', $error ?? 'Telefonnummer opdateret.');
        abas_redirect('profile.php');
    }

    if ($action === 'change_password') {
        $error = abas_update_user_password_with_current(
            $conn,
            $user,
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['password2'] ?? '')
        );
        if ($error !== null) {
            abas_flash_set('error', $error);
            abas_redirect('profile.php');
        }
        abas_flash_set('success', 'Adgangskode opdateret.');
        abas_redirect('profile.php');
    }
}

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?: $user;
$stmt->close();

$pageTitle = 'Min konto';
$currentUser = $user;
$extraHead = '<script src="' . htmlspecialchars(abas_asset_url('assets/js/password-policy.js'), ENT_QUOTES) . '" defer></script>';
require __DIR__ . '/partials/header.php';
?>
<h1 class="abas-page-title">Min konto</h1>
<p class="abas-page-lead"><?= htmlspecialchars(abas_user_display_name($user)) ?> · <?= htmlspecialchars(abas_role_label((string) $user['role'])) ?></p>

<div class="grid gap-6 lg:grid-cols-2 max-w-4xl">
    <form method="post" class="abas-card abas-form">
        <input type="hidden" name="action" value="save_phone">
        <h2 class="abas-card-title">Telefonnummer</h2>
        <div class="abas-field">
            <label class="abas-label" for="phone">Telefon</label>
            <input id="phone" name="phone" required value="<?= htmlspecialchars((string) ($user['phone'] ?? '')) ?>" class="abas-input" placeholder="+45...">
        </div>
        <p class="abas-hint">Bruges til SMS-login og SMS-betjening af anlæg.</p>
        <button type="submit" class="abas-btn-primary">Gem telefon</button>
    </form>

    <form method="post" id="change-password-form" class="abas-card abas-form" novalidate autocomplete="off">
        <input type="hidden" name="action" value="change_password">
        <h2 class="abas-card-title">Skift adgangskode</h2>
        <div class="abas-field">
            <label class="abas-label" for="current_password">Nuværende adgangskode</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="abas-input">
        </div>
        <div class="abas-field">
            <label class="abas-label" for="password">Ny adgangskode</label>
            <input id="password" type="password" name="password" required minlength="12" maxlength="128" autocomplete="new-password" class="abas-input">
        </div>
        <div class="abas-field">
            <label class="abas-label" for="password2">Gentag ny adgangskode</label>
            <input id="password2" type="password" name="password2" required minlength="12" maxlength="128" autocomplete="new-password" class="abas-input">
        </div>
        <div class="abas-pw-rules text-sm text-gray-600 mb-4 p-3 bg-gray-50 border border-gray-200 rounded-xl" aria-live="polite">
            <p class="font-semibold text-gray-700 mb-2">Krav til adgangskode</p>
            <ul class="space-y-1">
                <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="len">·</span> 12–128 tegn</li>
                <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="match">·</span> Adgangskoderne matcher</li>
                <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="lower">·</span> Mindst ét lille bogstav</li>
                <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="upper">·</span> Mindst ét stort bogstav</li>
                <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="digit">·</span> Mindst ét tal</li>
                <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="symbol">·</span> Mindst ét specialtegn</li>
                <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="pwned">·</span> Ikke fundet i kendte datalæk</li>
            </ul>
        </div>
        <button type="submit" class="abas-btn-primary" disabled>Gem adgangskode</button>
    </form>
</div>

<div class="abas-card max-w-lg mt-6 text-sm text-gray-600">
    <p><span class="text-gray-500">E-mail:</span> <?= htmlspecialchars((string) $user['email']) ?></p>
    <p class="mt-1"><span class="text-gray-500">Brugernavn:</span> <?= htmlspecialchars((string) $user['username']) ?></p>
    <?php if (trim((string) ($user['registration_display_name'] ?? '')) !== ''): ?>
        <p class="mt-1"><span class="text-gray-500">Visningsnavn:</span> <?= htmlspecialchars((string) $user['registration_display_name']) ?></p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.abasInitPasswordPolicy === 'function') {
        window.abasInitPasswordPolicy('change-password-form');
    }
});
</script>
<?php require __DIR__ . '/partials/footer.php';
