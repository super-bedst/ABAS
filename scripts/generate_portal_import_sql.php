<?php

declare(strict_types=1);

/**
 * Genererer SQL til import af data fra trekantbrand_portal → ABAS.
 *
 * Kør på portalservren (har adgang til lokal MySQL):
 *   php scripts/generate_portal_import_sql.php --output=Database/imports/portal_migration_update.sql
 *
 * Standard (uden --include-audit): opdaterer eksisterende firmaer/brugere — sikkert at genkøre.
 *   --installers-only   kun firmaer + domæner
 *   --include-audit     inkl. historisk audit_logs (kun første import)
 *
 * Vigtigt: Gem filen som UTF-8 uden BOM. Undgå Windows PowerShell Out-File (ø/å bliver korrupte).
 *
 * Miljøvariabler (valgfri):
 *   PORTAL_DB_HOST, PORTAL_DB_NAME, PORTAL_DB_USER, PORTAL_DB_PASS
 */

$host = getenv('PORTAL_DB_HOST') ?: '127.0.0.1';
$db = getenv('PORTAL_DB_NAME') ?: 'trekantbrand_portal';
$user = getenv('PORTAL_DB_USER') ?: 'trekantbrand_portal_app';
$pass = getenv('PORTAL_DB_PASS') ?: '451X2wn:z6Ve';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    fwrite(STDERR, 'DB-forbindelse fejlede: ' . $mysqli->connect_error . PHP_EOL);
    exit(1);
}
$mysqli->set_charset('utf8mb4');

function sql_quote(?string $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], $value) . "'";
}

function portal_username(string $email, ?string $preferred, array &$used): string
{
    $base = trim($preferred ?? '');
    if ($base === '') {
        $local = strtolower(trim((string) strtok($email, '@')));
        $base = preg_replace('/[^a-z0-9._-]+/', '', $local) ?: 'bruger';
    }
    $base = substr($base, 0, 100);
    $candidate = $base;
    $n = 1;
    while (isset($used[strtolower($candidate)])) {
        $suffix = (string) $n;
        $candidate = substr($base, 0, max(1, 100 - strlen($suffix))) . $suffix;
        $n++;
    }
    $used[strtolower($candidate)] = true;

    return $candidate;
}

function portal_domain(string $domain, int $virksomhedId, string $cvr, string $ansvarligEmail = ''): string
{
    $domain = strtolower(trim(ltrim($domain, '@')));
    if ($domain !== '') {
        return $domain;
    }

    $ansvarligEmail = strtolower(trim($ansvarligEmail));
    if ($ansvarligEmail !== '' && str_contains($ansvarligEmail, '@')) {
        $fromEmail = trim((string) substr($ansvarligEmail, (int) strrpos($ansvarligEmail, '@') + 1));
        if ($fromEmail !== '' && str_contains($fromEmail, '.')) {
            return $fromEmail;
        }
    }

    $cvrClean = preg_replace('/\D+/', '', $cvr) ?: (string) $virksomhedId;

    return 'import-' . $cvrClean . '.trekantbrand-import.local';
}

function portal_is_placeholder_domain(string $domain): bool
{
    return str_ends_with(strtolower(trim($domain)), '.trekantbrand-import.local');
}

/** @param list<string> $out */
function portal_emit_domain_sql(array &$out, int $installerId, string $domain, string $approvedAtSql): void
{
    if (!portal_is_placeholder_domain($domain)) {
        $out[] = sprintf(
            "DELETE FROM approved_installer_domains WHERE installer_id = %d AND email_domain LIKE 'import-%%.trekantbrand-import.local';",
            $installerId
        );
        $out[] = sprintf(
            "INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)\nVALUES (%d, %s, %s)\nON DUPLICATE KEY UPDATE installer_id = VALUES(installer_id);",
            $installerId,
            sql_quote($domain),
            $approvedAtSql
        );

        return;
    }

    $out[] = sprintf(
        "INSERT INTO approved_installer_domains (installer_id, email_domain, created_at)\nSELECT %d, %s, %s FROM DUAL\nWHERE NOT EXISTS (SELECT 1 FROM approved_installer_domains WHERE installer_id = %d);",
        $installerId,
        sql_quote($domain),
        $approvedAtSql,
        $installerId
    );
}

function portal_map_audit_category(string $handling): array
{
    return match ($handling) {
        'login', 'logout' => ['auth', $handling],
        'montor_oprettet', 'firma_admin_oprettet' => ['user', 'created'],
        'montor_slettet', 'firma_admin_slettet' => ['user', 'deleted'],
        'firma_admin_aktiveret', 'firma_admin_deaktiveret' => ['user', 'updated'],
        'anmodning_oprettet' => ['registration', 'submitted'],
        'anmodning_afvist' => ['registration', 'rejected'],
        'virksomhed_opdateret' => ['installer', 'updated'],
        default => ['system', $handling],
    };
}

$includeAudit = in_array('--include-audit', $argv ?? [], true);
$installersOnly = in_array('--installers-only', $argv ?? [], true);

$usedUsernames = [];
$out = [];

$out[] = '-- ABAS opdatering/import fra trekantbrand_portal';
$out[] = '-- Genereret: ' . date('c');
$out[] = $includeAudit
    ? '-- Fuld import inkl. audit_logs (første gang).'
    : '-- Opdatering: genkør sikkert mod eksisterende ABAS-database (firmanavne, domæner, brugere).';
$out[] = '';
$out[] = 'SET NAMES utf8mb4;';
$out[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$out[] = '';

// Installers
$out[] = '-- Godkendte installatører (virksomheder) — INSERT eller UPDATE på id';
$virks = $mysqli->query('SELECT id, navn, cvr, email_domaene, ansvarlig_email, aktiv, created_at FROM virksomheder ORDER BY id');
while ($v = $virks->fetch_assoc()) {
    $id = (int) $v['id'];
    $name = (string) $v['navn'];
    $active = (int) $v['aktiv'];
    $approvedAt = $v['created_at'] ? sql_quote((string) $v['created_at']) : 'NOW()';
    $out[] = sprintf(
        "INSERT INTO approved_installers (id, company_name, active, approved_at, created_at)\nVALUES (%d, %s, %d, %s, %s)\nON DUPLICATE KEY UPDATE company_name = VALUES(company_name), active = VALUES(active), approved_at = COALESCE(approved_installers.approved_at, VALUES(approved_at));",
        $id,
        sql_quote($name),
        $active,
        $approvedAt,
        $approvedAt
    );

    $domain = portal_domain((string) $v['email_domaene'], $id, (string) $v['cvr'], (string) ($v['ansvarlig_email'] ?? ''));
    portal_emit_domain_sql($out, $id, $domain, $approvedAt);
    if (trim((string) $v['email_domaene']) === '') {
        $suffix = portal_is_placeholder_domain($domain)
            ? ' → placeholder ' . $domain . ' (tilføj rigtigt domæne i admin hvis nødvendigt)'
            : ' → domæne fra ansvarlig e-mail: ' . $domain;
        $out[] = '-- OBS: virksomhed ' . $id . ' (' . $name . ') manglede e-maildomæne i portal' . $suffix;
    }
    $out[] = '';
}

if ($installersOnly) {
    $out[] = 'SET FOREIGN_KEY_CHECKS = 1;';
    goto portal_emit_output;
}

// Portal firma_admin users → virksomhedsadmin
$out[] = '-- Virksomhedsadministratorer (portal users.rolle=firma_admin)';
$admins = $mysqli->query(
    "SELECT id, navn, email, telefon, virksomhed_id, aktiv, sidste_login_at, created_at
     FROM users WHERE rolle = 'firma_admin' ORDER BY id"
);
while ($u = $admins->fetch_assoc()) {
    $email = strtolower(trim((string) $u['email']));
    if ($email === 'admin@trekantbrand.dk') {
        $out[] = '-- Springer portal-bruger over (findes som ABAS admin): ' . $email;
        continue;
    }
    $username = portal_username($email, null, $usedUsernames);
    $installerId = $u['virksomhed_id'] !== null ? (int) $u['virksomhed_id'] : 'NULL';
    $phone = trim((string) ($u['telefon'] ?? ''));
    $phoneSql = $phone !== '' ? sql_quote($phone) : 'NULL';
    $active = (int) $u['aktiv'];
    $created = $u['created_at'] ? sql_quote((string) $u['created_at']) : 'NOW()';
    $lastLogin = $u['sidste_login_at'] ? sql_quote((string) $u['sidste_login_at']) : 'NULL';
    $displayName = sql_quote((string) $u['navn']);

    $out[] = sprintf(
        "INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_display_name, password_set_at, access_confirmed_at, access_confirm_due_at, last_login_at, created_at)\nVALUES (%s, %s, NULL, 'virksomhedsadmin', %s, %s, %d, 'approved', %s, NULL, NULL, NULL, %s, %s)\nON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);",
        sql_quote($email),
        sql_quote($username),
        $phoneSql,
        $installerId,
        $active,
        $displayName,
        $lastLogin,
        $created
    );
    $out[] = '-- Velkomst-mail skal sendes (password_hash NULL): ' . $email;
    $out[] = '';
}

// Portal medarbejder → vagtcentral
$out[] = '-- TrekantBrand medarbejdere (portal users.rolle=medarbejder → vagtcentral)';
$staff = $mysqli->query(
    "SELECT id, navn, email, telefon, aktiv, sidste_login_at, created_at FROM users WHERE rolle = 'medarbejder' ORDER BY id"
);
while ($u = $staff->fetch_assoc()) {
    $email = strtolower(trim((string) $u['email']));
    if ($email === 'admin@trekantbrand.dk') {
        continue;
    }
    $username = portal_username($email, null, $usedUsernames);
    $phone = trim((string) ($u['telefon'] ?? ''));
    $phoneSql = $phone !== '' ? sql_quote($phone) : 'NULL';
    $active = (int) $u['aktiv'];
    $created = $u['created_at'] ? sql_quote((string) $u['created_at']) : 'NOW()';
    $lastLogin = $u['sidste_login_at'] ? sql_quote((string) $u['sidste_login_at']) : 'NULL';
    $displayName = sql_quote((string) $u['navn']);

    $out[] = sprintf(
        "INSERT INTO users (email, username, password_hash, role, phone, active, registration_status, registration_display_name, last_login_at, created_at)\nVALUES (%s, %s, NULL, 'vagtcentral', %s, %d, 'approved', %s, %s, %s)\nON DUPLICATE KEY UPDATE username = VALUES(username), active = VALUES(active), registration_display_name = VALUES(registration_display_name);",
        sql_quote($email),
        sql_quote($username),
        $phoneSql,
        $active,
        $displayName,
        $lastLogin,
        $created
    );
    $out[] = '-- Velkomst-mail skal sendes: ' . $email;
    $out[] = '';
}

// Montører oprettet
$out[] = '-- Godkendte montører (montor_anmodninger.status=oprettet)';
$montors = $mysqli->query(
    "SELECT id, navn, email, telefon, brugernavn, virksomhed_id, oprettet_at, created_at
     FROM montor_anmodninger WHERE status = 'oprettet' ORDER BY id"
);
while ($m = $montors->fetch_assoc()) {
    $email = strtolower(trim((string) $m['email']));
    $username = portal_username($email, $m['brugernavn'] !== null ? (string) $m['brugernavn'] : null, $usedUsernames);
    $phone = trim((string) ($m['telefon'] ?? ''));
    $phoneSql = $phone !== '' ? sql_quote($phone) : 'NULL';
    $installerId = (int) $m['virksomhed_id'];
    $created = $m['oprettet_at'] ?: ($m['created_at'] ?: date('Y-m-d H:i:s'));
    $createdSql = sql_quote((string) $created);
    $displayName = sql_quote((string) $m['navn']);

    $out[] = sprintf(
        "INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_reviewed_at, password_set_at, access_confirmed_at, access_confirm_due_at, created_at)\nVALUES (%s, %s, NULL, 'montor', %s, %d, 1, 'approved', 'montor', %s, %s, NULL, NULL, NULL, %s)\nON DUPLICATE KEY UPDATE username = VALUES(username), installer_id = VALUES(installer_id), active = VALUES(active), registration_display_name = VALUES(registration_display_name);",
        sql_quote($email),
        sql_quote($username),
        $phoneSql,
        $installerId,
        $displayName,
        $createdSql,
        $createdSql
    );
    $out[] = '-- Velkomst-mail skal sendes: ' . $email;
    $out[] = '';
}

// Afventende montør-anmodninger → pending registration
$out[] = '-- Afventende montør-ansøgninger (montor_anmodninger.status=afventer)';
$pending = $mysqli->query(
    "SELECT id, navn, email, telefon, virksomhed_id, anmodning_modtaget_at, created_at
     FROM montor_anmodninger WHERE status = 'afventer' ORDER BY id"
);
while ($m = $pending->fetch_assoc()) {
    $email = strtolower(trim((string) $m['email']));
    $username = portal_username($email, null, $usedUsernames);
    $phone = trim((string) ($m['telefon'] ?? ''));
    $phoneSql = $phone !== '' ? sql_quote($phone) : 'NULL';
    $installerId = (int) $m['virksomhed_id'];
    $requestedAt = $m['anmodning_modtaget_at'] ?: ($m['created_at'] ?: date('Y-m-d H:i:s'));
    $requestedSql = sql_quote((string) $requestedAt);
    $displayName = sql_quote((string) $m['navn']);

    $out[] = sprintf(
        "INSERT INTO users (email, username, password_hash, role, phone, installer_id, active, registration_status, registration_type, registration_display_name, registration_requested_at, created_at)\nVALUES (%s, %s, NULL, 'montor', %s, %d, 0, 'pending', 'montor', %s, %s, %s)\nON DUPLICATE KEY UPDATE registration_status = 'pending', installer_id = VALUES(installer_id), registration_display_name = VALUES(registration_display_name);",
        sql_quote($email),
        sql_quote($username),
        $phoneSql,
        $installerId,
        $displayName,
        $requestedSql,
        $requestedSql
    );
    $out[] = '';
}

if ($includeAudit) {
// Historisk audit log → activity_events (kun ved --include-audit)
$out[] = '-- Portal audit_logs → activity_events';
$audit = $mysqli->query(
    'SELECT user_id, user_navn, handling, objekt_type, objekt_id, objekt_beskrivelse, detaljer, ip, created_at
     FROM audit_logs ORDER BY id'
);
while ($a = $audit->fetch_assoc()) {
    [$category, $action] = portal_map_audit_category((string) $a['handling']);
    $details = $a['detaljer'] ? (string) $a['detaljer'] : null;
    if ($details !== null && strlen($details) > 65000) {
        $details = substr($details, 0, 65000);
    }
    $userId = 'NULL'; // Portal-bruger-ID matcher ikke ABAS efter import — brug actor_username
    $out[] = sprintf(
        'INSERT INTO activity_events (user_id, actor_username, category, action, object_type, object_id, object_label, details, ip_address, source, created_at) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);',
        $userId === 'NULL' ? 'NULL' : (string) $userId,
        $a['user_navn'] !== null ? sql_quote((string) $a['user_navn']) : 'NULL',
        sql_quote($category),
        sql_quote($action),
        $a['objekt_type'] !== null ? sql_quote((string) $a['objekt_type']) : 'NULL',
        $a['objekt_id'] !== null ? sql_quote((string) $a['objekt_id']) : 'NULL',
        $a['objekt_beskrivelse'] !== null ? sql_quote((string) $a['objekt_beskrivelse']) : 'NULL',
        $details !== null ? sql_quote($details) : 'NULL',
        $a['ip'] !== null ? sql_quote((string) $a['ip']) : 'NULL',
        sql_quote('portal_import'),
        sql_quote((string) $a['created_at'])
    );
}
}

portal_emit_output:
$out[] = 'SET FOREIGN_KEY_CHECKS = 1;';
if (!$installersOnly) {
    $out[] = '-- Efter import: send velkomst-mails til brugere med password_hash IS NULL';
}

$output = implode(PHP_EOL, $out) . PHP_EOL;

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--output=')) {
        $path = substr($arg, strlen('--output='));
        if ($path === '') {
            fwrite(STDERR, 'Tom sti til --output' . PHP_EOL);
            exit(1);
        }
        if (file_put_contents($path, $output) === false) {
            fwrite(STDERR, 'Kunne ikke skrive ' . $path . PHP_EOL);
            exit(1);
        }
        fwrite(STDERR, 'Skrev ' . $path . ' (' . strlen($output) . ' bytes, UTF-8)' . PHP_EOL);
        exit(0);
    }
}

echo $output;
