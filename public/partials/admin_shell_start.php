<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var array|null $currentUser */
/** @var string|null $adminSectionTitle */
/** @var string|null $adminSectionLead */

$appName = abas_config()['app_name'];
$title = ($pageTitle ?? 'Admin') . ' — ' . $appName;
$flash = abas_flash_get();
abas_session_release();

$currentPath = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$navItems = array_map(
    static function (array $item) use ($currentPath): array {
        $itemPage = basename($item['path']);

        return [
            'href' => abas_url($item['path']),
            'label' => $item['label'],
            'icon' => $item['icon'],
            'active' => $itemPage === $currentPath,
        ];
    },
    [
        ['path' => 'admin/index.php', 'label' => 'Dashboard', 'icon' => '⌂'],
        ['path' => 'admin/activity-log.php', 'label' => 'Aktivitetslog', 'icon' => '☰'],
        ['path' => 'admin/users.php', 'label' => 'Brugere', 'icon' => '👤'],
        ['path' => 'admin/montors.php', 'label' => 'Montører', 'icon' => '🔧'],
        ['path' => 'admin/installers.php', 'label' => 'Installatører', 'icon' => '🏢'],
        ['path' => 'admin/registration-requests.php', 'label' => 'Ansøgninger', 'icon' => '📋'],
        ['path' => 'admin/sync.php', 'label' => 'Sync', 'icon' => '↻'],
        ['path' => 'admin/settings.php', 'label' => 'Indstillinger', 'icon' => '⚙'],
        ['path' => 'admin/mfa-whitelist.php', 'label' => 'MFA whitelist', 'icon' => '🔐'],
        ['path' => 'admin/api-tokens.php', 'label' => 'API-tokens', 'icon' => '🔗'],
        ['path' => 'admin/endpoints.php', 'label' => 'API-endpoints', 'icon' => '⇄'],
        ['path' => 'admin/sms-inbound-log.php', 'label' => 'SMS log', 'icon' => '💬'],
        ['path' => 'admin/error-log.php', 'label' => 'Fejllog', 'icon' => '⚠'],
    ]
);
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
                <div class="<?= $flash['type'] === 'error' ? 'abas-alert-error' : 'abas-alert-success' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>
