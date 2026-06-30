<?php

declare(strict_types=1);

/**
 * @param array<string, mixed> $ctx
 */
function abas_guide_mock_nav(array $ctx): string
{
    $items = [];
    if ($ctx['show_dashboard']) {
        $items[] = 'Dashboard';
    }
    if ($ctx['show_vc_service']) {
        $items[] = 'VC service';
    }
    if ($ctx['show_vc_anlaegsbrugere'] || $ctx['show_anlaegsbrugere']) {
        $items[] = 'Anlægsbrugere';
    }
    if ($ctx['show_virksomhed_users']) {
        $items[] = 'Virksomhedsbrugere';
    }
    if ($ctx['show_admin']) {
        $items[] = 'Admin';
    }
    $items[] = 'Min konto';
    $items[] = 'Vejledning';

    $links = '';
    foreach ($items as $item) {
        $active = $item === 'Vejledning' ? ' bg-white/25 font-medium' : ' hover:bg-white/15';
        $links .= '<span class="px-3 py-1.5 rounded-full text-sm' . $active . '">' . htmlspecialchars($item) . '</span>';
    }

    return '<div class="abas-guide-mock"><p class="abas-guide-mock-label">Eksempel — menu</p><div class="abas-guide-mock-nav flex flex-wrap gap-2 bg-brand text-white rounded-xl px-3 py-2">' . $links . '</div></div>';
}

function abas_guide_mock_dashboard_owner(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — dine anlæg</p>
    <div class="abas-card !p-0 overflow-hidden">
        <table class="abas-table">
            <thead><tr><th>Anlæg</th><th>Adresse</th><th>Status</th></tr></thead>
            <tbody>
                <tr>
                    <td><span class="font-medium">FAB1234</span><br><span class="text-xs text-gray-500">Eksempel Skole</span></td>
                    <td class="text-sm">Skolevej 1, 7000 Fredericia</td>
                    <td><span class="abas-badge abas-badge-ok">Normal drift</span></td>
                </tr>
                <tr>
                    <td><span class="font-medium">FAB5678</span><br><span class="text-xs text-gray-500">Demo Butik</span></td>
                    <td class="text-sm">Handelsgade 12, 7100 Vejle</td>
                    <td><span class="abas-badge abas-badge-in-service">I service</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
HTML;
}

/**
 * @param array<string, mixed> $ctx
 */
function abas_guide_mock_dashboard_montor(array $ctx): string
{
    $scope = '';
    if ($ctx['show_dashboard_montor_scope']) {
        $scope = '<div class="flex gap-2 mb-3 text-xs"><span class="abas-badge abas-badge-active">Mine + firma i service</span><span class="text-gray-400">|</span><span class="text-gray-500">Kun mine i service</span></div>';
    }

    return <<<HTML
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — montør dashboard</p>
    {$scope}
    <div class="flex gap-2 mb-3">
        <span class="abas-input abas-guide-mock-input flex-1 text-sm text-gray-400">Søg anlægsnummer, navn eller by…</span>
        <span class="abas-btn abas-btn-primary abas-guide-mock-btn">Søg</span>
    </div>
    <div class="abas-card !p-0 overflow-hidden">
        <table class="abas-table">
            <thead><tr><th>Anlæg</th><th>Service</th><th>Montør</th></tr></thead>
            <tbody>
                <tr>
                    <td><span class="font-medium">FAB5678</span><br><span class="text-xs text-gray-500">Demo Butik</span></td>
                    <td><span class="abas-badge abas-badge-in-service">I service</span></td>
                    <td class="text-sm">Dig (Eksempel Montør)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
HTML;
}

function abas_guide_mock_dashboard_denied(): string
{
    return <<<'HTML'
<div class="abas-guide-mock mt-4">
    <p class="abas-guide-mock-label">Eksempel — søgning uden adgang</p>
    <div class="abas-card !p-0 overflow-hidden opacity-90">
        <table class="abas-table">
            <tbody>
                <tr class="bg-amber-50/50">
                    <td><span class="font-medium">FAB9999</span><br><span class="text-xs text-gray-500">Ukendt adresse</span></td>
                    <td><span class="abas-badge abas-badge-access-denied">Anlæg fundet — ingen rettighed til anlæg</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
HTML;
}

function abas_guide_mock_dashboard_search(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — søgning</p>
    <div class="flex gap-2 mb-3">
        <span class="abas-input abas-guide-mock-input flex-1 text-sm text-gray-400">FAB1234</span>
        <span class="abas-btn abas-btn-primary abas-guide-mock-btn">Søg</span>
    </div>
    <p class="text-xs text-gray-500 mb-0">Resultater vises som klikbare rækker, når du har adgang til anlægget.</p>
</div>
HTML;
}

function abas_guide_mock_installation_header(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — anlægsoversigt</p>
    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
        <div>
            <p class="text-lg font-semibold mb-0">FAB1234 — Eksempel Skole</p>
            <p class="text-sm text-gray-600 mb-0">Skolevej 1, 7000 Fredericia</p>
        </div>
        <span class="abas-badge abas-badge-ok">Normal drift</span>
    </div>
    <div class="grid gap-2 sm:grid-cols-2 text-sm">
        <div class="abas-panel !py-2"><strong>Service</strong><br><span class="text-gray-600">Start / stop</span></div>
        <div class="abas-panel !py-2"><strong>Alarmlog</strong><br><span class="text-gray-600">Seneste hændelser</span></div>
    </div>
</div>
HTML;
}

function abas_guide_mock_alarmlog(): string
{
    return <<<'HTML'
<div class="abas-guide-mock mt-4">
    <p class="abas-guide-mock-label">Eksempel — alarmlog</p>
    <div class="abas-card !p-0 overflow-hidden">
        <table class="abas-table text-sm">
            <thead><tr><th>Tid</th><th>Zone</th><th>Hændelse</th></tr></thead>
            <tbody>
                <tr><td>24.06.2026 10:15</td><td>Zone 3</td><td>Alarm — Indgang</td></tr>
                <tr><td>24.06.2026 10:18</td><td>Zone 3</td><td>Restore — Indgang</td></tr>
            </tbody>
        </table>
    </div>
</div>
HTML;
}

function abas_guide_mock_service_start(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — start service</p>
    <div class="abas-panel space-y-2 text-sm">
        <div><label class="text-xs text-gray-500">Varighed (timer)</label><br><span class="abas-input abas-guide-mock-input inline-block w-20">2</span></div>
        <div><label class="text-xs text-gray-500">Kommentar</label><br><span class="abas-input abas-guide-mock-input block w-full text-gray-400">Udskiftning af detektor i zone 3</span></div>
        <span class="abas-btn abas-btn-primary abas-guide-mock-btn">Start service</span>
    </div>
</div>
HTML;
}

function abas_guide_mock_service_active(): string
{
    return <<<'HTML'
<div class="abas-guide-mock mt-4">
    <p class="abas-guide-mock-label">Eksempel — aktiv service</p>
    <div class="abas-panel border-l-4 border-l-amber-400 text-sm">
        <p class="font-medium mb-1">Service aktiv — 1t 45m tilbage</p>
        <p class="text-gray-600 mb-2">Montør: Eksempel Montør · Kommentar: Udskiftning af detektor</p>
        <div class="flex gap-2">
            <span class="abas-btn abas-btn-secondary abas-guide-mock-btn">Forlæng</span>
            <span class="abas-btn abas-btn-danger abas-guide-mock-btn">Stop service</span>
        </div>
    </div>
</div>
HTML;
}

function abas_guide_mock_responsibility_ack(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — ansvarserklæring</p>
    <div class="abas-panel text-sm">
        <p class="mb-2">Jeg bekræfter, at jeg har ansvar for bygningen og brandvagter under service, og at jeg ringer 112 ved brand indtil anlægget er i normal drift.</p>
        <label class="flex items-center gap-2"><input type="checkbox" disabled checked class="rounded"> Jeg accepterer</label>
        <span class="abas-btn abas-btn-primary abas-guide-mock-btn mt-3 inline-block">Gem accept</span>
    </div>
</div>
HTML;
}

function abas_guide_mock_zones(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — zonestatus</p>
    <div class="abas-card !p-0 overflow-hidden">
        <table class="abas-table text-sm">
            <thead><tr><th>Zone</th><th>Navn</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td>1</td><td>Indgang</td><td><span class="abas-badge-zone-ok">OK</span></td></tr>
                <tr><td>3</td><td>Køkken</td><td><span class="abas-badge-zone-restore-pending">Restore påkrævet</span></td></tr>
            </tbody>
        </table>
    </div>
</div>
HTML;
}

function abas_guide_mock_anlaegsbrugere(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — anlægsbrugere</p>
    <div class="abas-card !p-0 overflow-hidden">
        <table class="abas-table text-sm">
            <thead><tr><th>Navn</th><th>Rolle</th><th>Anlæg</th></tr></thead>
            <tbody>
                <tr><td>Eksempel Afprøver</td><td>Anlægsafprøver</td><td>FAB1234</td></tr>
            </tbody>
        </table>
    </div>
    <span class="abas-btn abas-btn-primary abas-guide-mock-btn mt-3 inline-block">Opret bruger</span>
</div>
HTML;
}

function abas_guide_mock_vc_service(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — VC start service</p>
    <div class="abas-panel space-y-2 text-sm">
        <div><span class="abas-input abas-guide-mock-input block w-full text-gray-400">Anlæg: FAB1234</span></div>
        <div><span class="abas-input abas-guide-mock-input block w-full text-gray-400">På vegne af: Eksempel Montør</span></div>
        <div><span class="abas-input abas-guide-mock-input inline-block w-20">4</span> <span class="text-gray-500">timer</span></div>
        <span class="abas-btn abas-btn-primary abas-guide-mock-btn">Start service</span>
    </div>
</div>
HTML;
}

function abas_guide_mock_virksomhed_users(): string
{
    return <<<'HTML'
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — virksomhedsbrugere</p>
    <div class="abas-card !p-0 overflow-hidden">
        <table class="abas-table text-sm">
            <thead><tr><th>Navn</th><th>Rolle</th><th>Telefon</th></tr></thead>
            <tbody>
                <tr><td>Eksempel Montør</td><td>Montør</td><td>12 34 56 78</td></tr>
                <tr><td>Anden Montør</td><td>Montør</td><td>87 65 43 21</td></tr>
            </tbody>
        </table>
    </div>
</div>
HTML;
}

/**
 * @param array<string, mixed> $ctx
 */
function abas_guide_mock_profile(array $ctx): string
{
    $sms = $ctx['show_sms']
        ? '<div class="text-sm"><span class="text-xs text-gray-500">SMS-kode</span><br><span class="font-mono">••••••</span></div>'
        : '';

    return <<<HTML
<div class="abas-guide-mock">
    <p class="abas-guide-mock-label">Eksempel — min konto</p>
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="text-sm"><span class="text-xs text-gray-500">Telefon</span><br>12 34 56 78</div>
        {$sms}
    </div>
    <span class="abas-btn abas-btn-secondary abas-guide-mock-btn mt-3 inline-block">Skift adgangskode</span>
</div>
HTML;
}
