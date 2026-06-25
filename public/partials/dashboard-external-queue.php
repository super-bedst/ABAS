<?php

declare(strict_types=1);

/** @var array<string, mixed> $state */
$externalInQueue = $state['externalInQueue'] ?? [];
if ($externalInQueue === []) {
    return;
}
?>
<div class="mb-8">
    <h2 class="abas-card-title mb-3">I testkø uden for ABA Service (<?= count($externalInQueue) ?>)</h2>
    <p class="text-sm text-gray-600 mb-3">Startet af VC eller andet — vises ikke på montør-dashboard. Opdateres automatisk.</p>
    <div class="abas-table-wrap">
        <table class="abas-table">
            <thead>
                <tr>
                    <th>ABA-nr.</th>
                    <th>Navn</th>
                    <th>By</th>
                    <th>Udløber</th>
                    <th>Kommentar</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($externalInQueue as $ext): ?>
                <tr class="abas-table-row-link" role="link" tabindex="0"
                    data-href="<?= htmlspecialchars(abas_url('installation.php?id=' . (int) $ext['installation_id'])) ?>">
                    <td class="font-mono font-medium text-sky-800"><?= htmlspecialchars((string) $ext['miscno2']) ?></td>
                    <td><?= htmlspecialchars((string) $ext['name']) ?></td>
                    <td><?= htmlspecialchars((string) $ext['city']) ?></td>
                    <td class="text-sm"><?= htmlspecialchars(abas_format_datetime((string) ($ext['end_at'] ?? '')) ?: '—') ?></td>
                    <td class="text-sm text-gray-600"><?= htmlspecialchars((string) ($ext['queue_comment'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
