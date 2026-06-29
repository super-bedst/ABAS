<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/password_policy.php';
require_once __DIR__ . '/../includes/activity_log.php';

$conn = abas_db();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$row = $token ? abas_password_validate_token($conn, $token) : null;
$flowKind = $row ? (string) ($row['kind'] ?? 'reset') : 'reset';
if ($flowKind === 'vc_invite') {
    $flowKind = 'welcome';
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
    $pass = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');
    if ($pass !== $pass2) {
        $error = 'Adgangskoderne matcher ikke.';
    } else {
        $policyError = abas_password_validate($pass);
        if ($policyError !== null) {
            $error = $policyError;
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $uid = (int) $row['user_id'];
            $stmt = $conn->prepare('UPDATE users SET password_hash=?, password_set_at=NOW() WHERE id=?');
            $stmt->bind_param('si', $hash, $uid);
            $stmt->execute();
            $stmt->close();
            abas_password_consume_token($conn, $token);
            abas_access_set_due($conn, $uid);
            abas_log_user_target_event(
                $conn,
                'auth',
                'password_set',
                $uid,
                $uid,
                (string) ($row['username'] ?? ''),
                $flowKind === 'welcome' ? 'Velkomst / første adgangskode' : 'Nulstilling via e-mail'
            );
            abas_flash_set('success', 'Adgangskode gemt. Du kan nu logge ind.');
            abas_redirect('login.php');
        }
    }
}

$pageTitle = $flowKind === 'welcome' ? 'Vælg adgangskode' : 'Nulstil adgangskode';
$extraHead = '<script src="' . htmlspecialchars(abas_asset_url('assets/js/password-policy.js')) . '" defer></script>';
require __DIR__ . '/partials/header.php';
?>
<style>
    .abas-pw-rules { font-size: 0.8rem; color: #4b5563; margin: 0 0 1rem; padding: 0.75rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.75rem; }
    .abas-pw-rules p { margin: 0 0 0.5rem; font-weight: 600; color: #374151; }
    .abas-pw-rules ul { list-style: none; margin: 0; padding: 0; }
    .abas-pw-rules li { display: flex; align-items: flex-start; gap: 0.35rem; margin-bottom: 0.35rem; line-height: 1.35; }
    .abas-pw-rule-ic { display: inline-block; width: 1.15rem; text-align: center; flex-shrink: 0; font-weight: 700; }
    .pw-rule-ok { color: #059669; }
    .pw-rule-bad { color: #dc2626; }
    .pw-rule-wait { color: #9ca3af; }
    .pw-rule-neutral { color: #9ca3af; }
</style>
<div class="abas-auth-card">
    <h1 class="abas-page-title !text-xl"><?= htmlspecialchars($pageTitle) ?></h1>
    <?php if (!$row): ?>
        <p class="abas-alert-error">Linket er ugyldigt eller udløbet.</p>
    <?php else: ?>
        <p class="abas-page-lead !mb-4">
            <?= $flowKind === 'welcome'
                ? 'Vælg en stærk adgangskode til din nye konto.'
                : 'Vælg en ny adgangskode til din konto.' ?>
            <?php if (!empty($row['expires_at'])): ?>
                <span class="block text-sm text-gray-500 mt-1">Linket udløber <?= htmlspecialchars(abas_format_datetime((string) $row['expires_at'])) ?>.</span>
            <?php endif; ?>
        </p>
        <?php if ($error): ?><p class="abas-alert-error !mb-4"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post" id="set-password-form" class="abas-form" novalidate autocomplete="off">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="abas-field">
                <label class="abas-label" for="password">Ny adgangskode</label>
                <input id="password" type="password" name="password" required minlength="12" maxlength="128" autocomplete="new-password" class="abas-input">
            </div>
            <div class="abas-field">
                <label class="abas-label" for="password2">Gentag adgangskode</label>
                <input id="password2" type="password" name="password2" required minlength="12" maxlength="128" autocomplete="new-password" class="abas-input">
            </div>
            <div class="abas-pw-rules" aria-live="polite">
                <p>Krav til adgangskode</p>
                <ul>
                    <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="len">·</span><span>12–128 tegn</span></li>
                    <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="match">·</span><span>Adgangskoderne matcher</span></li>
                    <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="lower">·</span><span>Mindst ét lille bogstav</span></li>
                    <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="upper">·</span><span>Mindst ét stort bogstav</span></li>
                    <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="digit">·</span><span>Mindst ét tal</span></li>
                    <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="symbol">·</span><span>Mindst ét specialtegn</span></li>
                    <li><span class="abas-pw-rule-ic pw-rule-neutral" data-rule="pwned">·</span><span>Ikke fundet i kendte datalæk</span></li>
                </ul>
            </div>
            <button type="submit" class="abas-btn-primary abas-btn-block" disabled>Gem adgangskode</button>
        </form>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.abasInitPasswordPolicy === 'function') {
                window.abasInitPasswordPolicy('set-password-form');
            }
        });
        </script>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php';
