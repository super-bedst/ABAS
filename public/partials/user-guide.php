<?php

declare(strict_types=1);

/** @var array<string, mixed> $ctx */
/** @var list<array{id:string, title:string}> $toc */

require_once dirname(__DIR__, 2) . '/includes/user_guide_mocks.php';
?>
<div class="abas-guide-layout">
    <nav class="abas-guide-toc abas-card" aria-label="Indholdsfortegnelse">
        <h2 class="abas-card-title text-base">Indhold</h2>
        <ol class="abas-guide-toc-list">
            <?php foreach ($toc as $item): ?>
                <li><a href="#guide-<?= htmlspecialchars($item['id']) ?>" class="abas-guide-toc-link"><?= htmlspecialchars($item['title']) ?></a></li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="abas-guide-content space-y-8">
        <section id="guide-intro" class="abas-guide-section">
            <h2 class="abas-page-title !text-xl">Vejledning til <?= htmlspecialchars((string) $ctx['role_label']) ?></h2>
            <p class="abas-page-lead">Denne side beskriver de dele af <?= htmlspecialchars(abas_config()['app_name']) ?>, du har adgang til som <?= htmlspecialchars(strtolower((string) $ctx['role_label'])) ?>. Eksemplerne bruger fiktive anlægsdata.</p>
            <div class="abas-panel text-sm">
                <p class="mb-0">Vejledningen tilpasses automatisk: du ser kun afsnit om funktioner, du faktisk kan bruge. Kontakt TrekantBrand, hvis du mangler adgang til noget, du forventer at have.</p>
            </div>
        </section>

        <section id="guide-navigation" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Menu og navigation</h3>
            <p class="text-sm text-gray-600 mb-4">Øverst på siden finder du hovedmenuen. Her er de menupunkter, der er relevante for dig:</p>
            <?= abas_guide_mock_nav($ctx) ?>
            <ul class="abas-guide-bullets text-sm">
                <?php if ($ctx['show_dashboard']): ?>
                    <li><strong>Dashboard</strong> — oversigt og søgning efter anlæg.</li>
                <?php endif; ?>
                <?php if ($ctx['show_vc_service']): ?>
                    <li><strong>VC service</strong> — start service på vegne af montør eller anlægsejer.</li>
                <?php endif; ?>
                <?php if ($ctx['show_vc_anlaegsbrugere']): ?>
                    <li><strong>Anlægsbrugere</strong> (VC) — opret og tilknyt anlægsejere.</li>
                <?php endif; ?>
                <?php if ($ctx['show_anlaegsbrugere']): ?>
                    <li><strong>Anlægsbrugere</strong> — administrer brugere på dine anlæg.</li>
                <?php endif; ?>
                <?php if ($ctx['show_virksomhed_users']): ?>
                    <li><strong>Virksomhedsbrugere</strong> — brugere i dit installatørfirma.</li>
                <?php endif; ?>
                <?php if ($ctx['show_admin']): ?>
                    <li><strong>Admin</strong> — systemadministration.</li>
                <?php endif; ?>
                <li><strong>Min konto</strong> — telefon, adgangskode og sikkerhed.</li>
                <li><strong>Vejledning</strong> — denne side.</li>
            </ul>
        </section>

        <?php if ($ctx['show_dashboard']): ?>
        <section id="guide-dashboard" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Dashboard</h3>
            <?php if ($ctx['show_dashboard_owner']): ?>
                <p class="text-sm text-gray-600 mb-4">Som anlægsejer/afprøver ser du dine tilknyttede anlæg med det samme. Klik på et anlæg for at åbne detaljer og service.</p>
                <?= abas_guide_mock_dashboard_owner() ?>
            <?php elseif ($ctx['show_dashboard_montor_in_service']): ?>
                <p class="text-sm text-gray-600 mb-4">Som montør vises anlæg med aktiv service som standard. Brug søgefeltet til at finde andre anlæg.</p>
                <?= abas_guide_mock_dashboard_montor($ctx) ?>
                <?php if ($ctx['show_dashboard_montor_scope']): ?>
                    <p class="text-sm text-gray-600 mt-4">Du kan skifte mellem <strong>Mine + firma i service</strong> og <strong>Kun mine i service</strong> for at filtrere listen.</p>
                <?php endif; ?>
                <?php if ($ctx['show_dashboard_montor_scoped']): ?>
                    <p class="text-sm text-gray-600 mt-4">Din adgang er begrænset til tildelte anlæg og grupper. Søger du et anlæg uden adgang, vises det med beskeden <em>Anlæg fundet — ingen rettighed til anlæg</em>, så du kan se at nummeret findes.</p>
                    <?= abas_guide_mock_dashboard_denied() ?>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-sm text-gray-600 mb-4">Dashboard viser anlæg i aktiv service og lader dig søge på anlægsnummer, navn eller by.</p>
                <?= abas_guide_mock_dashboard_search() ?>
            <?php endif; ?>
            <?php if ($ctx['show_external_queue']): ?>
                <p class="text-sm text-gray-600 mt-4">Anlæg i ekstern testkø (startet uden for ABAS) vises i en separat liste øverst.</p>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_installation_page']): ?>
        <section id="guide-installation" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Anlægssiden</h3>
            <p class="text-sm text-gray-600 mb-4">Når du åbner et anlæg, ser du adresse, status og flere paneler. Siden opdateres automatisk hvert 5. sekund.</p>
            <?= abas_guide_mock_installation_header() ?>
            <ul class="abas-guide-bullets text-sm mt-4">
                <li><strong>Service</strong> — start, forlæng eller stop service (se nedenfor).</li>
                <li><strong>Alarmlog</strong> — seneste hændelser; vælg periode eller sidste 20/24 timer.</li>
                <?php if ($ctx['show_contacts_full']): ?>
                    <li><strong>Kontakter</strong> — navn, telefon og e-mail til anlægget.</li>
                <?php else: ?>
                    <li><strong>Kontakter</strong> — navne vises; telefonnumre kan være skjult for din rolle.</li>
                <?php endif; ?>
                <li><strong>Kort</strong> — placering af anlægget, når GPS-data findes.</li>
            </ul>
            <?= abas_guide_mock_alarmlog() ?>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_service']): ?>
        <section id="guide-service" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Start og stop service</h3>
            <p class="text-sm text-gray-600 mb-4">Service sætter anlægget i testtilstand i TrekantBrand. Du angiver varighed, kommentar og accepterer ansvar ved start.</p>
            <?= abas_guide_mock_service_start() ?>
            <ul class="abas-guide-bullets text-sm mt-4">
                <li><strong>Start service</strong> — vælg timer (maks. grænse vises) og skriv hvad du udfører.</li>
                <li><strong>Forlæng service</strong> — mens service kører, kan du tilføje tid og kommentar.</li>
                <li><strong>Stop service</strong> — sætter anlægget i drift igen. Restore-krav på zoner kan blokere stop indtil zoner er tilbagestillet.</li>
            </ul>
            <?= abas_guide_mock_service_active() ?>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_responsibility_ack']): ?>
        <section id="guide-responsibility" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Ansvarserklæring</h3>
            <p class="text-sm text-gray-600 mb-4">Før første service-start skal du acceptere, at du har ansvar for bygningen og brandvagter, og at du ringer 112 ved brand indtil anlægget er i normal drift.</p>
            <?= abas_guide_mock_responsibility_ack() ?>
            <p class="text-sm text-gray-600 mt-4">Accept gemmes på din bruger. SMS-start kræver også, at ansvar er accepteret via web først.</p>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_zones']): ?>
        <section id="guide-zones" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Zonestatus</h3>
            <p class="text-sm text-gray-600 mb-4">Under anlægssiden kan du udfolde <strong>Zonestatus</strong> for at se enkelte zoner, deres type og alarm/restore-status.</p>
            <?= abas_guide_mock_zones() ?>
            <ul class="abas-guide-bullets text-sm mt-4">
                <li><span class="abas-badge-zone-ok inline-flex">Grøn</span> — normal eller tilbagestillet.</li>
                <li><span class="abas-badge-zone-alarm inline-flex">Rose</span> — alarm uden restore-krav før stop.</li>
                <li><span class="abas-badge-zone-restore-pending inline-flex">Rød</span> — alarm med restore-krav; service kan ikke stoppes før restore.</li>
            </ul>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_sms']): ?>
        <section id="guide-sms" class="abas-guide-section">
            <h3 class="abas-guide-section-title">SMS-betjening</h3>
            <p class="text-sm text-gray-600 mb-4">Du kan starte og stoppe service via SMS fra din registrerede telefon. Format:</p>
            <div class="abas-guide-code">SMS-kode FAB1234 START 2</div>
            <div class="abas-guide-code">SMS-kode FAB1234 STOP</div>
            <ul class="abas-guide-bullets text-sm mt-4">
                <li><strong>SMS-kode</strong> — din personlige kode (min. 6 tegn), sat af administrator.</li>
                <li><strong>FAB1234</strong> — anlægsnummer (eksempel).</li>
                <li><strong>START 2</strong> — start service i 2 timer (maks. varighed gælder).</li>
                <li><strong>STOP</strong> — afslut service.</li>
            </ul>
            <p class="text-sm text-gray-600">Du skal have accepteret ansvarserklæringen i webappen mindst én gang, før SMS-start virker.</p>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_anlaegsbrugere']): ?>
        <section id="guide-anlaegsbrugere" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Anlægsbrugere</h3>
            <p class="text-sm text-gray-600 mb-4">Som anlægsejer kan du oprette medbrugere (anlægsafprøver) og knytte dem til dine anlæg.</p>
            <?= abas_guide_mock_anlaegsbrugere() ?>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_vc_service']): ?>
        <section id="guide-vc-service" class="abas-guide-section">
            <h3 class="abas-guide-section-title">VC service</h3>
            <p class="text-sm text-gray-600 mb-4">Vagtcentralen kan starte service på vegne af montør eller anlægsejer. Søg anlæg, vælg person og angiv varighed.</p>
            <?= abas_guide_mock_vc_service() ?>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_vc_anlaegsbrugere']): ?>
        <section id="guide-vc-anlaegsbrugere" class="abas-guide-section">
            <h3 class="abas-guide-section-title">VC anlægsbrugere</h3>
            <p class="text-sm text-gray-600 mb-4">Opret nye anlægsejere eller tilknyt eksisterende brugere til et anlæg via ABA-nummer.</p>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_virksomhed_users']): ?>
        <section id="guide-virksomhed" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Virksomhedsbrugere</h3>
            <p class="text-sm text-gray-600 mb-4">Som installatøradministrator administrerer du montører og andre brugere i dit godkendte installatørfirma.</p>
            <?= abas_guide_mock_virksomhed_users() ?>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_admin']): ?>
        <section id="guide-admin" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Administration</h3>
            <p class="text-sm text-gray-600 mb-4">Som administrator har du adgang til brugere, anlægsgrupper, installatører, sync og systemindstillinger under <strong>Admin</strong>.</p>
        </section>
        <?php endif; ?>

        <?php if ($ctx['show_profile']): ?>
        <section id="guide-profile" class="abas-guide-section">
            <h3 class="abas-guide-section-title">Min konto</h3>
            <p class="text-sm text-gray-600 mb-4">Under <strong>Min konto</strong> opdaterer du telefonnummer og adgangskode. Telefon bruges bl.a. til login og<?= $ctx['show_sms'] ? ' SMS-betjening' : ' sikkerhed' ?>.</p>
            <?= abas_guide_mock_profile($ctx) ?>
        </section>
        <?php endif; ?>
    </div>
</div>
