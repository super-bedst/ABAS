<?php

declare(strict_types=1);

/** @var string $hint */
/** @var string $portalTitle */
$portalTitle = $portalTitle ?? 'Fejl';
require __DIR__ . '/public-header.php';
?>
<div class="max-w-xl mx-auto px-4 py-16 text-center">
    <p class="text-xs uppercase tracking-widest text-brand font-semibold mb-2">TrekantBrand</p>
    <h1 class="text-2xl font-bold text-gray-900 mb-3">Noget gik galt</h1>
    <p class="text-gray-600 mb-8"><?= htmlspecialchars($hint) ?></p>
    <div class="flex flex-wrap justify-center gap-3">
        <a href="<?= abas_url('index.php') ?>" class="abas-btn-primary">Til forsiden</a>
        <button type="button" class="abas-btn-secondary" onclick="history.back()">Tilbage</button>
    </div>
</div>
<?php require __DIR__ . '/public-footer.php';
