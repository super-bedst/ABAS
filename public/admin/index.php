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
    <a href="<?= abas_url('admin/users.php') ?>" class="abas-admin-tile">Brugere</a>
    <a href="<?= abas_url('admin/installers.php') ?>" class="abas-admin-tile">Godkendte installatører</a>
    <a href="<?= abas_url('admin/sync.php') ?>" class="abas-admin-tile">Sync-prefixes</a>
    <a href="<?= abas_url('admin/settings.php') ?>" class="abas-admin-tile">Systemindstillinger</a>
    <a href="<?= abas_url('admin/registration-requests.php') ?>" class="abas-admin-tile">Registreringsanmodninger</a>
    <a href="<?= abas_url('admin/mfa-whitelist.php') ?>" class="abas-admin-tile">MFA IP-whitelist</a>
    <a href="<?= abas_url('admin/api-tokens.php') ?>" class="abas-admin-tile">API-tokens</a>
    <a href="<?= abas_url('admin/sms-inbound-log.php') ?>" class="abas-admin-tile">SMS inbound log</a>
</div>
<p class="text-sm text-gray-500 mt-6 space-y-1">
    <span class="block">Anlægssynk (Node-RED): <code class="text-xs bg-gray-100 px-1 rounded">/api/v1/cron/sync-installations?key=…</code></span>
    <span class="block">Service-reconcile: <code class="text-xs bg-gray-100 px-1 rounded">/api/v1/cron/reconcile-service?key=…</code> <span class="text-gray-400">(samme SYNC_CRON_SECRET)</span></span>
    <span class="block text-xs text-gray-400">Legacy: <code>/cron/sync_installations.php?key=…</code> og <code>/cron/reconcile_service.php?key=…</code></span>
</p>
<?php require __DIR__ . '/../partials/footer.php';
