<?php

declare(strict_types=1);

/** @var string $hint */
/** @var string $portalTitle */
/** @var int $errorStatus */
/** @var string $errorTitle */
$errorStatus = $errorStatus ?? 500;
$errorTitle = $errorTitle ?? 'Noget gik galt';
$portalTitle = $portalTitle ?? 'Fejl';
$loggedIn = !empty($_SESSION['user_id']);
$homeUrl = $loggedIn ? abas_url('dashboard.php') : abas_url('index.php');
$homeLabel = $loggedIn ? 'Til dashboard' : 'Til forsiden';
require __DIR__ . '/public-header.php';
?>
<div class="max-w-xl mx-auto px-4 py-12 sm:py-16">
    <div class="abas-card text-center p-8 sm:p-10">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-brand/10 text-brand text-2xl font-bold mb-5" aria-hidden="true">
            <?= (int) $errorStatus ?>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-3"><?= htmlspecialchars($errorTitle) ?></h1>
        <p class="text-gray-600 mb-8 leading-relaxed"><?= htmlspecialchars($hint) ?></p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="abas-btn-primary"><?= htmlspecialchars($homeLabel) ?></a>
            <button type="button" class="abas-btn-secondary" onclick="history.back()">Tilbage</button>
        </div>
    </div>
</div>
<?php require __DIR__ . '/public-footer.php';
