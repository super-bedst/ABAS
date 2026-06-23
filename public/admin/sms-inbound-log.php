<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/sms.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$lines = abas_sms_read_inbound_webhook_log();
$endpoint = abas_sms_inbound_webhook_url();

$pageTitle = 'SMS inbound log';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="abas-page-title">SMS inbound log</h1>
<p class="abas-page-lead">
    Seneste <?= count($lines) ?> webhook-kald. Konfigurer BAS med:
</p>
<p class="mb-4">
    <code class="text-sm bg-gray-100 px-2 py-1.5 rounded break-all block"><?= htmlspecialchars($endpoint) ?></code>
</p>

<div class="flex flex-wrap gap-2 mb-4">
    <a href="<?= abas_url('admin/sms-inbound-log.php') ?>" class="abas-btn-secondary">Opdater</a>
    <a href="<?= abas_url('admin/index.php') ?>" class="abas-btn-secondary">Tilbage til admin</a>
</div>

<div class="abas-card !p-0 overflow-hidden">
    <?php if ($lines === []): ?>
        <p class="p-4 text-gray-500">Ingen inbound-kald logget endnu.</p>
    <?php else: ?>
        <ol class="divide-y divide-gray-100">
            <?php foreach ($lines as $line): ?>
                <li class="p-4 text-sm font-mono text-gray-800 break-all whitespace-pre-wrap"><?= htmlspecialchars($line) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>

<p class="abas-hint mt-3">Logfil: <code>storage/sms/inbound-last20.log</code> (max. 20 linjer)</p>

<script>
setTimeout(function () {
    window.location.reload();
}, 15000);
</script>
<?php require __DIR__ . '/../partials/footer.php';
