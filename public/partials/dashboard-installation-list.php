<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/installation_status.php';
require_once dirname(__DIR__, 2) . '/includes/table_list.php';

/** @var list<array<string, mixed>> $installations */
/** @var bool $showServiceInfo */
/** @var bool $showServiceScope */
/** @var array<string, mixed> $state */

if (!isset($installations) || $installations === []) {
    return;
}

$tableSort = (string) ($state['tableSort'] ?? 'miscno2');
$tableSortDir = (string) ($state['tableSortDir'] ?? 'asc');
$tableQuery = is_array($state['tableQuery'] ?? null) ? $state['tableQuery'] : [];
$sortColumns = ['miscno2', 'name', 'city', 'service'];
?>
<div class="hidden sm:block abas-table-wrap">
    <table class="abas-table">
        <thead>
            <tr>
                <?php abas_render_table_sort_th('ABA-nr.', abas_table_sort_link('dashboard.php', $tableQuery, 'miscno2', $tableSort, $tableSortDir, $sortColumns)); ?>
                <?php abas_render_table_sort_th('Navn', abas_table_sort_link('dashboard.php', $tableQuery, 'name', $tableSort, $tableSortDir, $sortColumns)); ?>
                <?php abas_render_table_sort_th('By', abas_table_sort_link('dashboard.php', $tableQuery, 'city', $tableSort, $tableSortDir, $sortColumns)); ?>
                <th class="hidden lg:table-cell"><?= !empty($showServiceInfo) ? 'Service' : 'Status' ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($installations as $inst):
            $href = abas_url('installation.php?id=' . (int) $inst['id']);
            ?>
            <tr class="abas-table-row-link"
                role="link"
                tabindex="0"
                data-href="<?= htmlspecialchars($href) ?>">
                <td class="font-mono font-medium text-brand">
                    <?= htmlspecialchars((string) $inst['miscno2']) ?>
                    <?php if (!empty($inst['in_service']) && empty($showServiceInfo)): ?>
                        <span class="abas-badge-in-service ml-2"><?= !empty($inst['in_external_service']) ? 'Ekstern service' : 'I service' ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= htmlspecialchars((string) $inst['name']) ?>
                    <?php if (!empty($showServiceScope) && ($inst['service_scope'] ?? '') === 'company'): ?>
                        <span class="abas-badge mt-1 bg-slate-100 text-slate-700 border-slate-200">Firma</span>
                    <?php elseif (!empty($showServiceScope) && ($inst['service_scope'] ?? '') === 'mine'): ?>
                        <span class="abas-badge mt-1 bg-brand/10 text-brand border-brand/20">Dit</span>
                    <?php endif; ?>
                </td>
                <td class="hidden md:table-cell"><?= htmlspecialchars((string) $inst['city']) ?></td>
                <td class="hidden lg:table-cell text-sm text-gray-600">
                    <?= abas_render_dashboard_installation_status($inst, !empty($showServiceInfo)) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="sm:hidden space-y-3">
    <?php foreach ($installations as $inst): ?>
        <a href="<?= abas_url('installation.php?id=' . (int) $inst['id']) ?>" class="abas-mobile-card">
            <div class="flex flex-wrap items-center gap-2">
                <div class="abas-mobile-card-title"><?= htmlspecialchars((string) $inst['miscno2']) ?></div>
                <?php if (!empty($inst['in_service']) && empty($showServiceInfo)): ?>
                    <span class="abas-badge-in-service"><?= !empty($inst['in_external_service']) ? 'Ekstern service' : 'I service' ?></span>
                <?php endif; ?>
                <?php if (!empty($showServiceScope) && ($inst['service_scope'] ?? '') === 'company'): ?>
                    <span class="abas-badge bg-slate-100 text-slate-700 border-slate-200">Firma</span>
                <?php elseif (!empty($showServiceScope) && ($inst['service_scope'] ?? '') === 'mine'): ?>
                    <span class="abas-badge bg-brand/10 text-brand border-brand/20">Dit</span>
                <?php endif; ?>
            </div>
            <div class="font-medium text-gray-800 mt-1"><?= htmlspecialchars((string) $inst['name']) ?></div>
            <div class="text-sm text-gray-500 mt-1"><?= htmlspecialchars((string) $inst['city']) ?></div>
            <?php if (!empty($showServiceInfo) && !empty($inst['service_started_at'])): ?>
                <div class="text-xs text-gray-500 mt-2">
                    Service siden <?= htmlspecialchars(abas_format_datetime((string) $inst['service_started_at'])) ?>
                </div>
            <?php elseif (!empty($inst['mon_stat']) || !empty($inst['in_service'])): ?>
                <div class="abas-installation-badges mt-2">
                    <?php
                    $monStat = (string) ($inst['mon_stat'] ?? '');
                    $inSvc = !empty($inst['in_service']);
                    $label = abas_mon_stat_label($monStat);
                    $badgeClass = abas_installation_is_active($monStat) && !$inSvc
                        ? 'abas-badge-ok'
                        : abas_mon_stat_badge_class($monStat);
                    ?>
                    <span class="<?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($label) ?></span>
                    <?php if ($inSvc): ?>
                        <span class="abas-badge-in-service"><?= !empty($inst['in_external_service']) ? 'Ekstern service' : 'I service' ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
