<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_shell.php';

/** @var string $pageTitle */
/** @var array|null $currentUser */
/** @var string|null $adminSectionTitle */
/** @var string|null $adminSectionLead */
/** @var string|null $adminNavSection */

$appName = abas_config()['app_name'];
$title = ($pageTitle ?? 'Admin') . ' — ' . $appName;
$flash = abas_flash_get();
abas_session_release();

$navItems = abas_admin_nav_render_items($adminNavSection ?? null);
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
<body class="min-h-screen font-sans abas-admin-body">
<div class="abas-admin-shell">
    <aside class="abas-admin-rail" aria-label="Admin navigation">
        <a href="<?= abas_url('dashboard.php') ?>" class="abas-admin-rail-logo" title="Tilbage til app">
            <span class="abas-admin-rail-logo-mark">
                <img src="<?= htmlspecialchars(abas_asset_url('assets/images/trekantbrand-logo.svg')) ?>" alt="TrekantBrand">
            </span>
        </a>
        <nav class="abas-admin-rail-nav">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= htmlspecialchars($item['href']) ?>"
                   class="abas-admin-rail-link<?= $item['active'] ? ' abas-admin-rail-link--active' : '' ?>"
                   title="<?= htmlspecialchars($item['label']) ?>">
                    <span aria-hidden="true"><?= $item['icon'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="abas-admin-panel">
        <header class="abas-admin-topbar">
            <div>
                <p class="text-xs uppercase tracking-wider text-brand/70 font-semibold"><?= htmlspecialchars($appName) ?> · Admin</p>
                <h1 class="abas-admin-topbar-title"><?= htmlspecialchars($adminSectionTitle ?? ($pageTitle ?? 'Admin')) ?></h1>
                <?php if (!empty($adminSectionLead)): ?>
                    <p class="abas-admin-topbar-lead"><?= htmlspecialchars($adminSectionLead) ?></p>
                <?php endif; ?>
            </div>
            <div class="abas-admin-topbar-actions">
                <span class="text-sm text-gray-600 hidden sm:inline"><?= htmlspecialchars($currentUser['username'] ?? '') ?></span>
                <a href="<?= abas_url('dashboard.php') ?>" class="abas-btn-secondary text-sm">App</a>
                <a href="<?= abas_url('logout.php') ?>" class="abas-btn-secondary text-sm">Log ud</a>
            </div>
        </header>
        <aside class="abas-admin-sidebar hidden lg:block">
            <nav class="abas-admin-sidebar-nav">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>"
                       class="abas-admin-sidebar-link<?= $item['active'] ? ' abas-admin-sidebar-link--active' : '' ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <main class="abas-admin-main">
            <?php if ($flash): ?>
                <?php require __DIR__ . '/flash.php'; ?>
            <?php endif; ?>
