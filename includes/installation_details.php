<?php

declare(strict_types=1);

require_once __DIR__ . '/trekant_client.php';
require_once __DIR__ . '/roles.php';

function abas_fetch_installation_details(array $installation, ?array $user): array
{
    $result = [
        'lat' => null,
        'lon' => null,
        'contacts' => [],
        'zones' => [],
        'zones_error' => null,
        'error' => null,
    ];
    $sIns = (int) ($installation['s_ins'] ?? 0);
    $dealId = (string) ($installation['deal_id'] ?? '');
    if ($sIns <= 0 || $dealId === '') {
        $result['error'] = 'Manglende anlægs-id.';

        return $result;
    }

    try {
        $client = abas_trekant();
        $userid = abas_trekant_userid($user);

        $detailResp = $client->getInstallationDetails($sIns, $dealId);
        if (abas_trekant_return_code($detailResp) === 0) {
            $detail = abas_trekant_rows($detailResp)[0] ?? [];
            $lat = trim((string) ($detail['a_lat'] ?? ''));
            $lon = trim((string) ($detail['a_lon'] ?? ''));
            if ($lat !== '' && $lon !== '' && is_numeric($lat) && is_numeric($lon)) {
                $result['lat'] = (float) $lat;
                $result['lon'] = (float) $lon;
            }
        }

        $contactResp = $client->getInstallationContacts($sIns, $dealId, $userid);
        if (abas_trekant_return_code($contactResp) === 0) {
            $contacts = abas_filter_installation_contacts(abas_trekant_rows($contactResp));
            if ($user !== null && !abas_user_may_view_contact_phones($user)) {
                $contacts = abas_redact_contact_phones($contacts);
            }
            $result['contacts'] = $contacts;
        }

        $zoneResp = $client->getInstallationZones($userid, $sIns, $dealId);
        $zoneCode = abas_trekant_return_code($zoneResp);
        if ($zoneCode === 0) {
            $result['zones'] = abas_prepare_installation_zones(abas_trekant_rows($zoneResp));
        } else {
            $result['zones_error'] = abas_trekant_response_hint($zoneResp) ?: ('Zonestatus kunne ikke hentes (kode ' . $zoneCode . ').');
        }
    } catch (Throwable $e) {
        $result['error'] = $e->getMessage();
    }

    return $result;
}

function abas_filter_installation_contacts(array $rows): array
{
    $filtered = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (abas_contact_is_system($row)) {
            continue;
        }
        $phones = abas_contact_phones($row);
        $name = trim((string) ($row['name'] ?? $row['add_name'] ?? ''));
        if ($name === '' && $phones === []) {
            continue;
        }
        $filtered[] = [
            'name' => $name,
            'phones' => $phones,
            'email' => trim((string) ($row['email'] ?? $row['add_email1'] ?? '')),
        ];
    }

    return $filtered;
}

/**
 * @param list<array{name:string, phones:list<array{number:string, label:string}>, email:string}> $contacts
 * @return list<array{name:string, phones:list<array{number:string, label:string}>, email:string}>
 */
function abas_redact_contact_phones(array $contacts): array
{
    foreach ($contacts as $index => $contact) {
        $contacts[$index]['phones'] = [];
    }

    return $contacts;
}

function abas_contact_is_system(array $row): bool
{
    $name = strtolower(trim((string) ($row['name'] ?? $row['add_name'] ?? '')));
    if ($name === '') {
        return true;
    }
    if (str_contains($name, 'tb ism dispatch') || str_contains($name, 'ism dispatch')) {
        return true;
    }
    if (str_contains($name, 'politi')) {
        return true;
    }
    $contTp = (int) ($row['cont_tp'] ?? 0);
    if (in_array($contTp, [21, 26], true)) {
        return true;
    }

    return false;
}

function abas_contact_phones(array $row): array
{
    $phones = [];
    foreach (['phone1', 'phone2', 'phone3', 'add_phone'] as $field) {
        $raw = trim((string) ($row[$field] ?? ''));
        if ($raw === '' || $raw === '00000000' || preg_match('/^0+$/', $raw)) {
            continue;
        }
        $label = match ($field) {
            'phone1' => trim((string) ($row['phtyp1'] ?? '')),
            'phone2' => trim((string) ($row['phtyp2'] ?? '')),
            'phone3' => trim((string) ($row['phtyp3'] ?? '')),
            default => 'Tlf.',
        };
        $phones[] = ['number' => $raw, 'label' => $label !== '' ? $label : 'Tlf.'];
    }

    $seen = [];
    $unique = [];
    foreach ($phones as $p) {
        $key = preg_replace('/\D/', '', $p['number']) ?? $p['number'];
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $unique[] = $p;
    }

    return $unique;
}

function abas_zone_status_label(string $code): string
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return 'Normal';
    }

    return match ($code) {
        'UA' => 'Udkald aktivt',
        'UO' => 'Udkald opstået',
        'UR' => 'Tilbagestillet',
        'UT', 'LT' => 'Fejl aktiv',
        'UJ', 'LR' => 'Fejl tilbagestillet',
        'UX' => 'Systemstatus',
        '_%' => 'Delvis fejl',
        default => $code,
    };
}

function abas_zone_status_tone(string $code): string
{
    $code = strtoupper(trim($code));

    return match ($code) {
        'UA', 'UO', 'UT', 'LT', '_%' => 'warn',
        'UR', 'UJ', 'LR', '' => 'ok',
        default => 'neutral',
    };
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array{zix:int, label:string, area:string, ecode:string, status_label:string, tone:string, in_test:bool, kind:string}>
 */
function abas_prepare_installation_zones(array $rows): array
{
    $skipLabels = [
        'brandalarm',
        'brandalarm restore',
        'linje fejl',
        'linje fejl restore',
        'ux (intern dalko)',
        'fejl aba',
        'fejl aba restore',
    ];
    $byZix = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $zix = (int) ($row['zix'] ?? 0);
        if ($zix <= 0) {
            continue;
        }
        $hidden = strtolower(trim((string) ($row['hidden'] ?? '')));
        if ($hidden !== '' && $hidden !== '0' && $hidden !== 'n') {
            continue;
        }

        $label = trim((string) ($row['atext'] ?? ''));
        if ($label === '' || strtolower($label) === 'ikke defineret') {
            continue;
        }
        $kind = $zix >= 23 ? 'system' : 'zone';
        if ($kind === 'zone' && in_array(strtolower($label), $skipLabels, true)) {
            continue;
        }

        $ecode = strtoupper(trim((string) ($row['ecode'] ?? '')));
        $entry = [
            'zix' => $zix,
            'label' => $label,
            'area' => trim((string) ($row['area'] ?? '')),
            'ecode' => $ecode,
            'status_label' => abas_zone_status_label($ecode),
            'tone' => abas_zone_status_tone($ecode),
            'in_test' => trim((string) ($row['in_test_flg'] ?? '')) !== '',
            'kind' => $kind,
        ];

        if (!isset($byZix[$zix])) {
            $byZix[$zix] = $entry;
            continue;
        }
        if ($ecode !== '' && $byZix[$zix]['ecode'] === '') {
            $byZix[$zix] = $entry;
            continue;
        }
        if ($kind === 'zone' && strlen($label) > strlen($byZix[$zix]['label'])) {
            $byZix[$zix] = $entry;
        }
    }

    $zones = array_values($byZix);
    usort($zones, static function (array $a, array $b): int {
        $kindOrder = ['zone' => 0, 'system' => 1];

        return [$kindOrder[$a['kind']] ?? 2, $a['zix']] <=> [$kindOrder[$b['kind']] ?? 2, $b['zix']];
    });

    return $zones;
}

/**
 * @param list<array{zix:int, label:string, area:string, ecode:string, status_label:string, tone:string, in_test:bool, kind:string}> $zones
 */
function abas_render_installation_zones_html(array $zones, ?string $error = null): string
{
    if ($error !== null && $error !== '') {
        return '<p class="text-amber-700 text-xs" id="inst-zones-error">' . htmlspecialchars($error) . '</p>';
    }
    if ($zones === []) {
        return '<p class="text-gray-500 text-xs" id="inst-zones-empty">Ingen zonedata.</p>';
    }

    ob_start();
    ?>
    <div class="overflow-x-auto" id="inst-zones-table-wrap">
        <table class="abas-table text-xs w-full" id="inst-zones-table">
            <thead>
                <tr>
                    <th class="w-14">Zone</th>
                    <th>Beskrivelse</th>
                    <th class="w-36">Status</th>
                </tr>
            </thead>
            <tbody id="inst-zones-rows">
                <?php foreach ($zones as $zone): ?>
                    <tr>
                        <td class="whitespace-nowrap font-medium"><?= (int) $zone['zix'] ?></td>
                        <td>
                            <?= htmlspecialchars($zone['label']) ?>
                            <?php if ($zone['in_test']): ?>
                                <span class="abas-badge-active text-[10px] ml-1 px-1.5 py-0">Test</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="abas-log-dot abas-log-dot--<?= htmlspecialchars($zone['tone']) ?>" aria-hidden="true"></span>
                                <span><?= htmlspecialchars($zone['status_label']) ?></span>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php

    return (string) ob_get_clean();
}
