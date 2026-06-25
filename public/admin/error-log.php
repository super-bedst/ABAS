<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/app_log.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$lines = abas_read_error_log();

$pageTitle = 'Fejllog';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2"><a href="<?= abas_url('admin/index.php') ?>" class="abas-back-link">&larr; Admin</a></div>
<h1 class="abas-page-title">Fejllog</h1>
<p class="abas-page-lead">Seneste <?= count($lines) ?> applikationsfejl (registrering, API m.m.).</p>

<div class="flex flex-wrap gap-2 mb-4">
    <a href="<?= abas_url('admin/error-log.php') ?>" class="abas-btn-secondary">Opdater</a>
</div>

<div class="abas-card !p-0 overflow-hidden">
    <?php if ($lines === []): ?>
        <p class="p-4 text-gray-500">Ingen fejl logget endnu.</p>
    <?php else: ?>
        <ol class="divide-y divide-gray-100">
            <?php foreach ($lines as $line): ?>
                <li class="p-4 text-sm font-mono text-gray-800 break-all whitespace-pre-wrap"><?= htmlspecialchars($line) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>

<p class="abas-hint mt-3">Logfil: <code>storage/app/error-last50.log</code> (max. 50 linjer)</p>
<?php require __DIR__ . '/../partials/footer.php';
