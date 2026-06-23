<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$pageTitle = 'Administration';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="text-2xl font-semibold text-brand mb-4">Administration</h1>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <a href="/admin/installers.php" class="block bg-white border rounded p-4 shadow-sm hover:border-brand">Godkendte installatører</a>
    <a href="/admin/sync.php" class="block bg-white border rounded p-4 shadow-sm hover:border-brand">Sync-prefixes</a>
    <a href="/admin/settings.php" class="block bg-white border rounded p-4 shadow-sm hover:border-brand">Systemindstillinger</a>
    <a href="/admin/users.php" class="block bg-white border rounded p-4 shadow-sm hover:border-brand">Brugere</a>
    <a href="/admin/api-tokens.php" class="block bg-white border rounded p-4 shadow-sm hover:border-brand">API-tokens</a>
</div>
<?php require __DIR__ . '/../partials/footer.php';
