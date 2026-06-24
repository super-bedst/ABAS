<?php

declare(strict_types=1);

/** @var string $portalTitle */
/** @var bool $portalShowNav */
$appName = abas_config()['app_name'];
$portalTitle = $portalTitle ?? 'ABA Service';
$portalShowNav = $portalShowNav ?? true;
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($portalTitle) ?> — <?= htmlspecialchars($appName) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(abas_asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen flex flex-col font-sans bg-basbg">
<header class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-4xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
        <a href="<?= abas_url('index.php') ?>" class="flex items-center gap-3 no-underline text-inherit">
            <img src="<?= htmlspecialchars(abas_asset_url('assets/images/trekantbrand-logo.svg')) ?>" alt="TrekantBrand" class="h-9 w-auto">
            <span class="font-semibold text-brand text-sm sm:text-base"><?= htmlspecialchars($appName) ?></span>
        </a>
        <?php if ($portalShowNav): ?>
        <nav class="flex flex-wrap items-center gap-2 text-sm">
            <a href="<?= abas_url('index.php') ?>" class="text-gray-600 hover:text-brand px-2 py-1">Forside</a>
            <a href="mailto:alarmadm@trekantbrand.dk" class="text-gray-600 hover:text-brand px-2 py-1">Kontakt</a>
            <a href="<?= abas_url('login.php') ?>" class="abas-btn-primary !py-2 !px-4 text-sm inline-flex items-center gap-1.5">
                <span aria-hidden="true">🔒</span> Log ind
            </a>
        </nav>
        <?php endif; ?>
    </div>
</header>
<main class="flex-1 w-full">
