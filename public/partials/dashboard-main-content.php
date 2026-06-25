<?php

declare(strict_types=1);

/** @var array<string, mixed> $state */
$installations = $state['installations'] ?? [];
$listHeading = (string) ($state['listHeading'] ?? '');
$showServiceInfo = !empty($state['showServiceInfo']);
$showServiceScope = !empty($state['showServiceScope']);
$isOwner = !empty($state['isOwner']);
$isMontor = !empty($state['isMontor']);
$q = (string) ($state['q'] ?? '');
$externalInQueue = $state['externalInQueue'] ?? [];
$showExternalQueue = !empty($state['showExternalQueue']);
$includeCompany = !empty($state['includeCompany']);

if ($installations === []): ?>
    <div class="abas-panel">
        <?php if ($isOwner && $q === ''): ?>
            Du har ingen tilknyttede anlæg. Kontakt vagtcentralen.
        <?php elseif ($q !== ''): ?>
            Ingen anlæg fundet. Prøv et andet søgeord.
        <?php elseif ($showExternalQueue && $externalInQueue !== []): ?>
            Ingen anlæg i ABAS-service lige nu. <?= count($externalInQueue) ?> anlæg i ekstern testkø — se listen ovenfor.
        <?php elseif ($isMontor): ?>
            Ingen anlæg i service lige nu<?= $includeCompany ? '' : ' for dig' ?>. Søg efter et anlæg ovenfor.
        <?php else: ?>
            Ingen anlæg i service lige nu. Søg efter et anlæg ovenfor.
        <?php endif; ?>
    </div>
<?php else: ?>

<?php if ($listHeading !== ''): ?>
    <h2 class="abas-card-title mb-3" id="abas-dashboard-list-heading"><?= htmlspecialchars($listHeading) ?> (<?= count($installations) ?>)</h2>
<?php endif; ?>

<?php require __DIR__ . '/dashboard-installation-list.php'; ?>

<?php endif; ?>
