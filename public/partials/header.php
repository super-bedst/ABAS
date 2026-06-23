<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/theme.php';

/** @var string $pageTitle */
/** @var array|null $currentUser */
$palette = abas_theme_palette();
$appName = abas_config()['app_name'];
$title = ($pageTitle ?? 'Dashboard') . ' — ' . $appName;
$flash = abas_flash_get();
?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '<?= $palette['primary'] ?>', gold: '<?= $palette['secondary'] ?>' },
                        basbg: '<?= $palette['bg'] ?>',
                    }
                }
            }
        }
    </script>
    <style>
        body { background: <?= $palette['bg'] ?>; color: <?= $palette['text'] ?>; }
        .table-head { background: <?= $palette['table_header_bg'] ?>; }
    </style>
</head>
<body class="min-h-screen flex flex-col">
<header class="bg-brand text-white shadow">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-2">
        <a href="/dashboard.php" class="font-semibold text-lg tracking-wide"><?= htmlspecialchars($appName) ?></a>
        <?php if (!empty($currentUser)): ?>
        <nav class="flex flex-wrap gap-3 text-sm items-center">
            <a href="/dashboard.php" class="hover:underline">Dashboard</a>
            <?php if (in_array($currentUser['role'], ['vagtcentral', 'admin'], true)): ?>
                <a href="/vc-service.php" class="hover:underline">VC service</a>
                <a href="/vc-anlaegsbrugere.php" class="hover:underline">Anlægsbrugere</a>
            <?php endif; ?>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="/admin/index.php" class="hover:underline">Admin</a>
            <?php endif; ?>
            <span class="opacity-80"><?= htmlspecialchars($currentUser['username']) ?> (<?= htmlspecialchars(abas_role_label($currentUser['role'])) ?>)</span>
            <a href="/logout.php" class="bg-white/10 px-2 py-1 rounded hover:bg-white/20">Log ud</a>
        </nav>
        <?php endif; ?>
    </div>
</header>
<main class="flex-1 max-w-6xl w-full mx-auto px-4 py-6">
<?php if ($flash): ?>
    <div class="mb-4 p-3 rounded border <?= $flash['type'] === 'error' ? 'bg-red-50 border-red-300 text-red-800' : 'bg-green-50 border-green-300 text-green-800' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>
