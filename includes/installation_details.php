<?php

declare(strict_types=1);

require_once __DIR__ . '/trekant_client.php';

function abas_fetch_installation_details(array $installation, ?array $user): array
{
    $result = [
        'lat' => null,
        'lon' => null,
        'contacts' => [],
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
            $result['contacts'] = abas_filter_installation_contacts(abas_trekant_rows($contactResp));
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
