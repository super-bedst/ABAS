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
<h1 class="abas-page-title">Administration</h1>
<p class="abas-page-lead">Brugere, installatører, sync og systemindstillinger.</p>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <a href="<?= abas_url('admin/installers.php') ?>" class="abas-admin-tile">Godkendte installatører</a>
    <a href="<?= abas_url('admin/sync.php') ?>" class="abas-admin-tile">Sync-prefixes</a>
    <a href="<?= abas_url('admin/settings.php') ?>" class="abas-admin-tile">Systemindstillinger</a>
    <a href="<?= abas_url('admin/registration-requests.php') ?>" class="abas-admin-tile">Registreringsanmodninger</a>
    <a href="<?= abas_url('admin/mfa-whitelist.php') ?>" class="abas-admin-tile">MFA IP-whitelist</a>
    <a href="<?= abas_url('admin/api-tokens.php') ?>" class="abas-admin-tile">API-tokens</a>
    <a href="<?= abas_url('admin/sms-inbound-log.php') ?>" class="abas-admin-tile">SMS inbound log</a>
</div>
<?php require __DIR__ . '/../partials/footer.php';
