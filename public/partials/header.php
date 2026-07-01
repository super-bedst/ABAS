<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/roles.php';
require_once dirname(__DIR__, 2) . '/includes/theme.php';

/** @var string $pageTitle */
/** @var array|null $currentUser */
$appName = abas_config()['app_name'];
$title = ($pageTitle ?? 'Dashboard') . ' — ' . $appName;
$flash = abas_flash_get();
abas_session_release();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <?= $extraHead ?? '' ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(abas_asset_url('assets/css/app.css')) ?>">
</head>
<body class="min-h-screen flex flex-col font-sans">
<header class="bg-brand text-white shadow-md">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
        <a href="<?= abas_url('dashboard.php') ?>" class="flex items-center gap-3 no-underline text-white hover:text-white/90">
            <img src="<?= htmlspecialchars(abas_asset_url('assets/images/trekantbrand-logo.svg')) ?>" alt="TrekantBrand" class="h-9 w-auto">
            <span class="font-semibold text-sm sm:text-base tracking-wide"><?= htmlspecialchars($appName) ?></span>
        </a>
        <?php if (!empty($currentUser)): ?>
        <nav class="flex flex-wrap gap-2 text-sm items-center">
            <a href="<?= abas_url('dashboard.php') ?>" class="px-3 py-1.5 rounded-full hover:bg-white/15">Dashboard</a>
            <?php if (in_array($currentUser['role'], ['vagtcentral', 'admin'], true)): ?>
                <a href="<?= abas_url('vc-service.php') ?>" class="px-3 py-1.5 rounded-full hover:bg-white/15">VC service</a>
                <a href="<?= abas_url('vc-anlaegsbrugere.php') ?>" class="px-3 py-1.5 rounded-full hover:bg-white/15">Anlægsbrugere</a>
            <?php endif; ?>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="<?= abas_url('admin/index.php') ?>" class="px-3 py-1.5 rounded-full hover:bg-white/15">Admin</a>
            <?php endif; ?>
            <?php if ($currentUser['role'] === 'anlaegsejer'): ?>
                <a href="<?= abas_url('anlaegsbrugere.php') ?>" class="px-3 py-1.5 rounded-full hover:bg-white/15">Anlægsbrugere</a>
            <?php endif; ?>
            <?php if ($currentUser['role'] === 'virksomhedsadmin'): ?>
                <a href="<?= abas_url('virksomhed/users.php') ?>" class="px-3 py-1.5 rounded-full hover:bg-white/15">Virksomhedsbrugere</a>
            <?php endif; ?>
            <a href="<?= abas_url('vejledning.php') ?>" class="px-3 py-1.5 rounded-full hover:bg-white/15">Vejledning</a>
            <a href="<?= abas_url('profile.php') ?>" class="px-3 py-1.5 rounded-full hover:bg-white/15">Min konto</a>
            <span class="hidden sm:inline px-2.5 py-1 rounded-full bg-white/10 text-white/90 text-xs">
                <?= htmlspecialchars($currentUser['username']) ?> · <?= htmlspecialchars(abas_role_label($currentUser['role'])) ?>
            </span>
            <a href="<?= abas_url('logout.php') ?>" class="bg-white/15 px-3 py-1.5 rounded-full hover:bg-white/25 font-medium">Log ud</a>
        </nav>
        <?php endif; ?>
    </div>
</header>
<main class="flex-1 max-w-6xl w-full mx-auto px-4 py-6 sm:py-8">
<?php if ($flash): ?>
    <?php require __DIR__ . '/flash.php'; ?>
<?php endif; ?>
