<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/installation_status.php';

/** @var list<array<string, mixed>> $installations */
/** @var bool $showServiceInfo */
/** @var bool $showServiceScope */

if (!isset($installations) || $installations === []) {
    return;
}
?>
<div class="hidden sm:block abas-table-wrap">
    <table class="abas-table">
        <thead>
            <tr>
                <th>ABA-nr.</th>
                <th>Navn</th>
                <th class="hidden md:table-cell">By</th>
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
                    <?php if (!empty($showServiceInfo)): ?>
                        <?php if (!empty($inst['service_started_at'])): ?>
                            Siden <?= htmlspecialchars(abas_format_datetime((string) $inst['service_started_at'])) ?>
                            <?php if (!empty($inst['service_expires_at'])): ?>
                                <span class="text-gray-400">· udløber <?= htmlspecialchars(abas_format_datetime((string) $inst['service_expires_at'])) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php
                        $inSvc = !empty($inst['in_service']);
                        echo abas_mon_stat_label((string) ($inst['mon_stat'] ?? ''));
                        if ($inSvc) {
                            echo ' · I service';
                        }
                        ?>
                    <?php endif; ?>
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
                    <span class="<?= htmlspecialchars(abas_mon_stat_badge_class((string) ($inst['mon_stat'] ?? ''))) ?>"><?= htmlspecialchars(abas_mon_stat_label((string) ($inst['mon_stat'] ?? ''))) ?></span>
                    <?php if (!empty($inst['in_service'])): ?>
                        <span class="abas-badge-in-service">I service</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.abas-table-row-link').forEach(function (row) {
    row.addEventListener('click', function () {
        window.location.href = row.dataset.href;
    });
    row.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            window.location.href = row.dataset.href;
        }
    });
});
</script>
