<?php

declare(strict_types=1);

function abas_mon_stat_normalize(string $monStat): string
{
    return strtoupper(trim($monStat));
}

function abas_mon_stat_label(string $monStat): string
{
    $code = abas_mon_stat_normalize($monStat);

    return match ($code) {
        'AKT', 'AKT-' => 'Aktiv',
        'NED' => 'Nedtaget',
        'LOG' => 'Logges',
        'MNED' => 'Midlertidigt nedtaget',
        'UOPR' => 'Under oprettelse',
        'UREP' => 'Under reparation',
        default => str_starts_with($code, 'AKT') ? 'Aktiv' : ($code === '' ? 'Status ukendt' : 'Status ukendt'),
    };
}

function abas_mon_stat_description(string $monStat): string
{
    $code = abas_mon_stat_normalize($monStat);

    return match ($code) {
        'AKT' => 'Automatisk geokodning ud fra adresse',
        'AKT-' => 'Prik sat manuelt',
        'NED' => 'Ingen signaler logges',
        'LOG' => 'Alle signaler logges, men genereres ikke',
        'MNED' => 'Signaler logges ikke',
        'UOPR' => 'Signaler logges',
        'UREP' => 'Signaler logges',
        default => str_starts_with($code, 'AKT') ? 'Anlæg i aktiv drift' : '',
    };
}

function abas_mon_stat_badge_class(string $monStat): string
{
    $code = abas_mon_stat_normalize($monStat);

    if (str_starts_with($code, 'AKT')) {
        return 'abas-badge-ok';
    }

    return match ($code) {
        'NED' => 'abas-badge bg-gray-100 text-gray-700 border-gray-200',
        'LOG' => 'abas-badge bg-amber-100 text-amber-900 border-amber-200',
        'MNED' => 'abas-badge bg-orange-100 text-orange-900 border-orange-200',
        'UOPR', 'UREP' => 'abas-badge bg-sky-100 text-sky-900 border-sky-200',
        default => 'abas-badge bg-gray-50 text-gray-500 border-gray-200 border-dashed',
    };
}

function abas_installation_allows_service(string $monStat): bool
{
    $code = abas_mon_stat_normalize($monStat);

    return $code !== '' && str_starts_with($code, 'AKT');
}

function abas_installation_is_active(string $monStat): bool
{
    return abas_installation_allows_service($monStat);
}

/**
 * Dashboard statuscelle: grøn "Aktiv"-label når anlægget er aktivt uden service.
 */
function abas_render_dashboard_installation_status(array $inst, bool $showServiceInfo): string
{
    if (!empty($inst['access_denied'])) {
        return '<span class="abas-badge abas-badge-access-denied">Anlæg fundet — ingen rettighed til anlæg</span>';
    }

    if ($showServiceInfo) {
        if (empty($inst['service_started_at'])) {
            return '';
        }
        $html = 'Siden ' . htmlspecialchars(abas_format_datetime((string) $inst['service_started_at']));
        if (!empty($inst['service_expires_at'])) {
            $html .= ' <span class="text-gray-400">· udløber '
                . htmlspecialchars(abas_format_datetime((string) $inst['service_expires_at']))
                . '</span>';
        }

        return $html;
    }

    $monStat = (string) ($inst['mon_stat'] ?? '');
    $inSvc = !empty($inst['in_service']);
    $label = abas_mon_stat_label($monStat);
    $isActive = abas_installation_is_active($monStat);

    ob_start();
    if ($isActive && !$inSvc) {
        ?>
        <span class="abas-badge-ok"><?= htmlspecialchars($label) ?></span>
        <?php
    } else {
        ?>
        <span class="<?= htmlspecialchars(abas_mon_stat_badge_class($monStat)) ?>"><?= htmlspecialchars($label) ?></span>
        <?php if ($inSvc): ?>
            <span class="abas-badge-in-service ml-1.5">I service</span>
        <?php endif; ?>
        <?php
    }

    return (string) ob_get_clean();
}

function abas_render_installation_in_service_banner(?array $session): string
{
    if ($session === null) {
        return '';
    }

    $started = abas_format_datetime((string) ($session['started_at'] ?? ''));
    $expires = abas_format_datetime((string) ($session['expires_at'] ?? ''));

    ob_start();
    ?>
    <div class="abas-in-service-banner" id="inst-in-service-banner" role="status" aria-live="polite">
        <span class="abas-in-service-banner__dot" aria-hidden="true"></span>
        <div class="min-w-0">
            <p class="abas-in-service-banner__title">Anlægget er i service</p>
            <p class="abas-in-service-banner__times" id="inst-service-status">
                Aktiv siden <?= htmlspecialchars($started) ?><?= $expires !== '' ? ' — udløber ' . htmlspecialchars($expires) : '' ?>
            </p>
            <p class="abas-in-service-banner__hint">Brandalarmen er i test/service. Ved brand: ring <strong>112</strong> indtil anlægget er sat i normal drift igen.</p>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

function abas_render_installation_external_service_banner(?array $external): string
{
    if ($external === null) {
        return '';
    }

    $endAt = abas_format_datetime((string) ($external['end_at'] ?? ''));
    $comment = trim((string) ($external['queue_comment'] ?? ''));
    $operator = trim((string) ($external['trekant_user_id'] ?? ''));

    ob_start();
    ?>
    <div class="abas-external-service-banner mb-5" id="inst-external-service-banner" role="status" aria-live="polite">
        <span class="abas-external-service-banner__dot" aria-hidden="true"></span>
        <div class="min-w-0">
            <p class="abas-external-service-banner__title">I service uden for ABA Service</p>
            <p class="abas-external-service-banner__times" id="inst-external-service-status">
                <?php if ($endAt !== ''): ?>
                    Testkø udløber <?= htmlspecialchars($endAt) ?>
                <?php else: ?>
                    Anlægget står i TrekantBrand testkø
                <?php endif; ?>
                <?php if ($operator !== ''): ?>
                    · bruger <?= htmlspecialchars($operator) ?>
                <?php endif; ?>
            </p>
            <?php if ($comment !== ''): ?>
                <p class="abas-external-service-banner__hint"><?= htmlspecialchars($comment) ?></p>
            <?php endif; ?>
            <p class="abas-external-service-banner__hint">Startet af VC eller andet system — ikke fra ABA Service. Montør/admin kan sætte anlægget i drift igen herunder.</p>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

function abas_render_installation_status_badges(array $installation, bool $inAbasService, bool $externalService = false): string
{
    $monStat = (string) ($installation['mon_stat'] ?? '');
    $label = abas_mon_stat_label($monStat);
    $desc = abas_mon_stat_description($monStat);
    $badgeClass = abas_mon_stat_badge_class($monStat);

    ob_start();
    ?>
    <div class="abas-installation-badges">
        <span class="<?= htmlspecialchars($badgeClass) ?>"<?= $desc !== '' ? ' title="' . htmlspecialchars($desc) . '"' : '' ?>><?= htmlspecialchars($label) ?></span>
        <?php if ($inAbasService): ?>
            <span class="abas-badge-in-service text-sm px-3 py-1">I service (ABA)</span>
        <?php elseif ($externalService): ?>
            <span class="abas-badge-external-service text-sm px-3 py-1">I service (ekstern)</span>
        <?php endif; ?>
    </div>
    <?php

    return (string) ob_get_clean();
}
