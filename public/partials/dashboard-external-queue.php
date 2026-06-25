<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/table_list.php';

/** @var array<string, mixed> $state */
$externalInQueue = $state['externalInQueue'] ?? [];
if ($externalInQueue === []) {
    return;
}

$tableSort = (string) ($state['tableSort'] ?? 'miscno2');
$tableSortDir = (string) ($state['tableSortDir'] ?? 'asc');
$tableQuery = is_array($state['tableQuery'] ?? null) ? $state['tableQuery'] : [];
$sortColumns = ['miscno2', 'name', 'city', 'expires', 'comment'];
?>
<div class="mb-8">
    <h2 class="abas-card-title mb-3">I testkø uden for ABA Service (<?= count($externalInQueue) ?>)</h2>
    <p class="text-sm text-gray-600 mb-3">Startet af VC eller andet — vises ikke på montør-dashboard. Opdateres automatisk.</p>
    <div class="abas-table-wrap">
        <table class="abas-table">
            <thead>
                <tr>
                    <?php abas_render_table_sort_th('ABA-nr.', abas_table_sort_link('dashboard.php', $tableQuery, 'miscno2', $tableSort, $tableSortDir, $sortColumns)); ?>
                    <?php abas_render_table_sort_th('Navn', abas_table_sort_link('dashboard.php', $tableQuery, 'name', $tableSort, $tableSortDir, $sortColumns)); ?>
                    <?php abas_render_table_sort_th('By', abas_table_sort_link('dashboard.php', $tableQuery, 'city', $tableSort, $tableSortDir, $sortColumns)); ?>
                    <?php abas_render_table_sort_th('Udløber', abas_table_sort_link('dashboard.php', $tableQuery, 'expires', $tableSort, $tableSortDir, $sortColumns)); ?>
                    <?php abas_render_table_sort_th('Kommentar', abas_table_sort_link('dashboard.php', $tableQuery, 'comment', $tableSort, $tableSortDir, $sortColumns)); ?>
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
