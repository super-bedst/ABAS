<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/config.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    abas_set_setting($conn, 'access_confirm_months', (string) max(1, (int) ($_POST['access_confirm_months'] ?? 3)));
    abas_set_setting($conn, 'password_reset_ttl_hours', (string) max(1, (int) ($_POST['password_reset_ttl_hours'] ?? 24)));
    abas_set_setting($conn, 'welcome_token_ttl_hours', (string) max(1, (int) ($_POST['welcome_token_ttl_hours'] ?? 72)));
    abas_flash_set('success', 'Indstillinger gemt.');
    header('Location: /admin/settings.php');
    exit;
}

$pageTitle = 'Indstillinger';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="text-xl font-semibold text-brand mb-4">Systemindstillinger</h1>
<form method="post" class="bg-white border rounded p-4 max-w-md space-y-3">
    <div>
        <label class="block text-sm">Adgangsbekræftelse (måneder)</label>
        <input type="number" name="access_confirm_months" min="1" value="<?= htmlspecialchars(abas_setting($conn, 'access_confirm_months', '3') ?? '3') ?>" class="w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm">Password-reset TTL (timer)</label>
        <input type="number" name="password_reset_ttl_hours" min="1" value="<?= htmlspecialchars(abas_setting($conn, 'password_reset_ttl_hours', '24') ?? '24') ?>" class="w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm">Velkomst-token TTL (timer)</label>
        <input type="number" name="welcome_token_ttl_hours" min="1" value="<?= htmlspecialchars(abas_setting($conn, 'welcome_token_ttl_hours', '72') ?? '72') ?>" class="w-full border rounded px-3 py-2">
    </div>
    <button class="bg-brand text-white px-4 py-2 rounded">Gem</button>
</form>
<p class="mt-4"><a href="/admin/index.php" class="text-brand underline text-sm">Tilbage</a></p>
<?php require __DIR__ . '/../partials/footer.php';
