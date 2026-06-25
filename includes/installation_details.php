<?php

declare(strict_types=1);

require_once __DIR__ . '/trekant_client.php';
require_once __DIR__ . '/roles.php';

function abas_fetch_installation_details(array $installation, ?array $user): array
{
    $result = [
        'lat' => null,
        'lon' => null,
        'alid' => '',
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
            $result['alid'] = abas_installation_alid($detail);
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
            if ($result['alid'] === '') {
                foreach (abas_trekant_rows($zoneResp) as $zoneRow) {
                    if (!is_array($zoneRow)) {
                        continue;
                    }
                    $result['alid'] = abas_installation_alid($zoneRow);
                    if ($result['alid'] !== '') {
                        break;
                    }
                }
            }
        } else {
            $result['zones_error'] = abas_trekant_response_hint($zoneResp) ?: ('Zonestatus kunne ikke hentes (kode ' . $zoneCode . ').');
        }
    } catch (Throwable $e) {
        $result['error'] = $e->getMessage();
    }

    return $result;
}

function abas_installation_alid(array $row): string
{
    foreach (['alaid', 'primary_cid', 'alid'] as $field) {
        $value = trim((string) ($row[$field] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return '';
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

/**
 * @return list<string>
 */
function abas_zone_alarm_codes(): array
{
    return [
        'UA', 'UO', 'UT', 'LT', '_%',
        'BA', 'BC', 'BV', 'CA', 'FA', 'GA', 'HA', 'JA', 'KA', 'MA', 'PA', 'QA', 'TA', 'VA', 'YA', 'ZA',
        'AT', 'XT',
    ];
}

/**
 * @return list<string>
 */
function abas_zone_restore_codes(): array
{
    return [
        'UR', 'UJ', 'LR',
        'BR', 'CR', 'FR', 'GR', 'HR', 'JR', 'KR', 'MR', 'PR', 'QR', 'RR', 'TR', 'VR', 'YR', 'ZR',
        'RT', 'RJ',
    ];
}

function abas_zone_is_alarm_code(string $code): bool
{
    $code = strtoupper(trim($code));

    return $code !== '' && in_array($code, abas_zone_alarm_codes(), true);
}

function abas_zone_is_restore_code(string $code): bool
{
    $code = strtoupper(trim($code));

    return $code !== '' && in_array($code, abas_zone_restore_codes(), true);
}

function abas_extract_zone_ecode(array $row): string
{
    foreach (['ecode', 'c_ecode', 'event'] as $field) {
        $code = strtoupper(trim((string) ($row[$field] ?? '')));
        if ($code !== '') {
            return $code;
        }
    }

    return '';
}

function abas_zone_status_priority(string $code): int
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return 1;
    }
    if (abas_zone_is_alarm_code($code)) {
        return 3;
    }
    if (abas_zone_is_restore_code($code)) {
        return 0;
    }

    return 2;
}

function abas_zone_status_label(string $code): string
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return 'Normal';
    }

    if (abas_zone_is_alarm_code($code)) {
        return match ($code) {
            'UA', 'UO' => 'I alarm',
            'UT', 'LT', '_%' => 'Fejl aktiv',
            default => 'I alarm',
        };
    }
    if (abas_zone_is_restore_code($code)) {
        return match ($code) {
            'UR', 'UJ', 'LR' => 'Tilbagestillet',
            default => 'Tilbagestillet',
        };
    }

    return match ($code) {
        'UX' => 'Systemstatus',
        default => $code,
    };
}

function abas_zone_status_tone(string $code): string
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return 'ok';
    }
    if (abas_zone_is_alarm_code($code)) {
        return 'warn';
    }
    if (abas_zone_is_restore_code($code)) {
        return 'ok';
    }

    return match ($code) {
        'UX' => 'neutral',
        default => 'neutral',
    };
}

function abas_zone_status_display(string $code): string
{
    $code = strtoupper(trim($code));
    $label = abas_zone_status_label($code);
    if ($code === '' || $label === $code) {
        return $label;
    }

    return $label . ' (' . $code . ')';
}

function abas_zone_id_is_panel_number(string $id): bool
{
    if ($id === '%') {
        return true;
    }

    return $id !== '' && preg_match('/^\d{1,2}$/', $id) === 1;
}

function abas_zone_display_number(array $row): string
{
    $zix = (int) ($row['zix'] ?? 0);
    $id = trim((string) ($row['id'] ?? ''));
    $template = trim((string) ($row['template'] ?? ''));

    // Manually configured panel zones use id (matches vagtcentral zone list).
    if ($template === '' && $id !== '') {
        return $id;
    }

    // Dalko template status slots (IP, batteri, linje m.m.) are numbered by zix in drift.
    if ($zix >= 9 && $zix <= 22) {
        return (string) $zix;
    }

    if (abas_zone_id_is_panel_number($id)) {
        return $id;
    }

    return $zix > 0 ? (string) $zix : '';
}

function abas_zone_row_key(array $row): string
{
    $zoneNo = abas_zone_display_number($row);
    $label = strtolower(trim((string) ($row['atext'] ?? '')));

    return $zoneNo . "\0" . $label;
}

function abas_zone_number_sort_compare(string $left, string $right): int
{
    if ($left === $right) {
        return 0;
    }
    if ($left === '%') {
        return 1;
    }
    if ($right === '%') {
        return -1;
    }
    if (ctype_digit($left) && ctype_digit($right)) {
        return (int) $left <=> (int) $right;
    }

    return strnatcasecmp($left, $right);
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array{zone_no:string, zix:int, label:string, area:string, ecode:string, status_label:string, tone:string, in_test:bool, kind:string}>
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
    $byKey = [];

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

        $ecode = abas_extract_zone_ecode($row);
        $rowKey = abas_zone_row_key($row);
        $entry = [
            'zone_no' => abas_zone_display_number($row),
            'zix' => $zix,
            'label' => $label,
            'area' => trim((string) ($row['area'] ?? '')),
            'ecode' => $ecode,
            'status_label' => abas_zone_status_display($ecode),
            'tone' => abas_zone_status_tone($ecode),
            'in_test' => trim((string) ($row['in_test_flg'] ?? '')) !== '',
            'kind' => $kind,
        ];

        if (!isset($byKey[$rowKey])) {
            $byKey[$rowKey] = $entry;
            continue;
        }
        $existingPriority = abas_zone_status_priority($byKey[$rowKey]['ecode']);
        $newPriority = abas_zone_status_priority($ecode);
        if ($newPriority > $existingPriority) {
            $byKey[$rowKey] = $entry;
            continue;
        }
        if ($newPriority === $existingPriority && $ecode !== '' && $byKey[$rowKey]['ecode'] === '') {
            $byKey[$rowKey] = $entry;
            continue;
        }
        if ($kind === 'zone' && strlen($label) > strlen($byKey[$rowKey]['label'])) {
            $byKey[$rowKey] = $entry;
        }
    }

    $zones = array_values($byKey);
    usort($zones, static function (array $a, array $b): int {
        $kindOrder = ['zone' => 0, 'system' => 1];
        $kindCmp = ($kindOrder[$a['kind']] ?? 2) <=> ($kindOrder[$b['kind']] ?? 2);
        if ($kindCmp !== 0) {
            return $kindCmp;
        }

        return abas_zone_number_sort_compare($a['zone_no'], $b['zone_no']);
    });

    return $zones;
}

/**
 * @param list<array{zone_no:string, zix:int, label:string, area:string, ecode:string, status_label:string, tone:string, in_test:bool, kind:string}> $zones
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
                        <td class="whitespace-nowrap font-medium"><?= htmlspecialchars($zone['zone_no']) ?></td>
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
