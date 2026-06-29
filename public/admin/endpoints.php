<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/api_endpoints.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$baseUrl = abas_app_url();
$endpoints = abas_api_endpoint_registry();
$legacy = abas_api_legacy_endpoint_registry();

$pageTitle = 'API-endpoints';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<h1 class="abas-page-title">API-endpoints</h1>
<p class="abas-page-lead">
    Fulde URL'er baseret på denne installation: <code class="text-sm bg-gray-100 px-1.5 py-0.5 rounded"><?= htmlspecialchars($baseUrl) ?></code>
</p>

<div class="mb-6 p-4 bg-sky-50 border border-sky-200 rounded-xl text-sm text-sky-950 space-y-2">
    <p class="font-medium">API-tokens vs. cron-nøgler</p>
    <p>
        <strong>API-tokens</strong> (under <a href="<?= abas_url('admin/api-tokens.php') ?>" class="text-brand underline">API-tokens</a>)
        bruges til REST-kald med <code>Authorization: Bearer …</code> — fx søgning, service start/stop og alarmlog.
    </p>
    <p>
        <strong>Cron-jobs</strong> bruger <em>ikke</em> API-tokens. De valideres mod hemmeligheder i
        <code>env.local</code> (<code>SYNC_CRON_SECRET</code>, valgfri <code>SERVICE_RECONCILE_CRON_SECRET</code>)
        via <code>?key=…</code> eller samme værdi som Bearer.
    </p>
    <p>
        <strong>SMS inbound webhook</strong> kræver ingen nøgle — kun <code>POST</code> med JSON-body.
    </p>
</div>

<section class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-3">REST API</h2>
    <div class="space-y-4">
        <?php foreach ($endpoints as $ep): ?>
            <?php
            $url = abas_api_endpoint_url($ep['path'], $ep['example_query'] ?? []);
            $envKeys = $ep['env_keys'] ?? [];
            ?>
            <article class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <?php foreach ($ep['methods'] as $method): ?>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded <?= abas_api_method_badge_class($method) ?>"><?= htmlspecialchars($method) ?></span>
                    <?php endforeach; ?>
                    <span class="font-medium text-gray-900"><?= htmlspecialchars($ep['title']) ?></span>
                    <span class="text-xs text-gray-500 ml-auto"><?= htmlspecialchars(abas_api_auth_label($ep['auth'])) ?></span>
                </div>
                <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($ep['description']) ?></p>
                <p class="font-mono text-xs break-all bg-gray-50 border border-gray-100 rounded px-2 py-1.5"><?= htmlspecialchars($url) ?></p>
                <?php if (!empty($ep['example_body'])): ?>
                    <p class="mt-2 text-xs text-gray-500">Eksempel-body: <code class="bg-gray-100 px-1 rounded"><?= htmlspecialchars($ep['example_body']) ?></code></p>
                <?php endif; ?>
                <?php if ($envKeys !== []): ?>
                    <p class="mt-2 text-xs <?= abas_api_env_configured($envKeys) ? 'text-emerald-700' : 'text-amber-700' ?>">
                        <?= abas_api_env_configured($envKeys) ? 'Nøgle er sat på serveren' : 'Nøgle mangler i env.local' ?>
                        (<?= htmlspecialchars(implode(' / ', $envKeys)) ?>)
                    </p>
                <?php endif; ?>
                <?php if (!empty($ep['auth_detail'])): ?>
                    <p class="mt-1 text-xs text-gray-500"><?= htmlspecialchars($ep['auth_detail']) ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-3">Legacy cron-URL'er</h2>
    <p class="text-sm text-gray-600 mb-3">Kan bruges parallelt med API-ruterne. HTTP kræver samme cron-nøgle som ovenfor.</p>
    <div class="space-y-4">
        <?php foreach ($legacy as $ep): ?>
            <?php
            $url = abas_api_endpoint_url($ep['path'], $ep['example_query'] ?? [], true);
            $envKeys = $ep['env_keys'] ?? [];
            ?>
            <article class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <?php foreach ($ep['methods'] as $method): ?>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded <?= abas_api_method_badge_class($method) ?>"><?= htmlspecialchars($method) ?></span>
                    <?php endforeach; ?>
                    <span class="font-medium text-gray-900"><?= htmlspecialchars($ep['title']) ?></span>
                </div>
                <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($ep['description']) ?></p>
                <p class="font-mono text-xs break-all bg-gray-50 border border-gray-100 rounded px-2 py-1.5"><?= htmlspecialchars($url) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<p><a href="<?= abas_url('admin/index.php') ?>" class="abas-link text-sm">Tilbage til administration</a></p>
<?php require __DIR__ . '/../partials/footer.php';
