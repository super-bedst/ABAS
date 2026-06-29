<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var array|null $currentUser */
/** @var string|null $extraHead */

$appName = abas_config()['app_name'];
$title = ($pageTitle ?? 'ABA Service') . ' — ' . $appName;
$flash = abas_flash_get();
abas_session_release();
abas_send_embed_headers();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <?= $extraHead ?? '' ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(abas_asset_url('assets/css/app.css')) ?>">
    <style>
        html, body { height: 100%; margin: 0; }
        body.abas-embed-body { min-height: 100%; background: var(--tw-bg-basbg, #F5F5EF); }
        .abas-embed-main { padding: 1rem 1.25rem; max-width: 42rem; margin: 0 auto; }
    </style>
</head>
<body class="font-sans abas-embed-body">
<main class="abas-embed-main">
    <?php if ($flash): ?>
        <div class="<?= $flash['type'] === 'error' ? 'abas-alert-error' : 'abas-alert-success' ?> mb-4">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>
