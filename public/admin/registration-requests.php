<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/registration.php';
require_once __DIR__ . '/../../includes/users.php';
require_once __DIR__ . '/../../includes/installation_sync.php';
require_once __DIR__ . '/../../includes/installation_status.php';
require_once __DIR__ . '/../../includes/installers.php';

$conn = abas_db();
$admin = abas_require_login();
abas_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $smsAllowed = !empty($_POST['sms_service_allowed']);
        $smsCode = trim($_POST['sms_code'] ?? '');
        $finalRole = !empty($_POST['as_virksomhedsadmin']) ? 'virksomhedsadmin' : null;
        $result = abas_approve_registration($conn, $userId, (int) $admin['id'], $smsAllowed, $smsCode, $finalRole);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'reject') {
        $result = abas_reject_registration($conn, $userId, (int) $admin['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'create_company') {
        $result = abas_registration_attach_new_company(
            $conn,
            $userId,
            (int) $admin['id'],
            trim((string) ($_POST['company_name'] ?? '')),
            trim((string) ($_POST['email_domain'] ?? ''))
        );
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'sync_installations') {
        $result = abas_registration_sync_missing_installations($conn, $userId, $admin);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    }

    abas_redirect('admin/registration-requests.php');
}

$pending = $conn->query(
    "SELECT u.*, ai.company_name,
            COALESCE(ai.company_name, u.registration_requested_company_name) AS display_company_name
     FROM users u
     LEFT JOIN approved_installers ai ON ai.id = u.installer_id
     WHERE u.registration_status = 'pending'
     ORDER BY u.registration_requested_at ASC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Registreringsanmodninger';
$currentUser = $admin;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2"><a href="<?= abas_url('admin/index.php') ?>" class="abas-back-link">&larr; Admin</a></div>
<h1 class="abas-page-title">Registreringsanmodninger</h1>
<p class="abas-page-lead">Afventende ansøgninger — opret firma, synk anlæg og godkend direkte fra kortet.</p>

<?php if ($pending === []): ?>
    <p class="text-gray-500 mt-6">Ingen afventende anmodninger.</p>
<?php else: ?>
<div class="mt-6 space-y-4">
    <?php foreach ($pending as $p):
        $userId = (int) $p['id'];
        $regType = (string) ($p['registration_type'] ?? $p['role']);
        $isMontor = $regType === 'montor';
        $isOwnerType = in_array($regType, ['anlaegsejer', 'anlaegsafprover'], true);
        $needsNewCompany = $isMontor
            && empty($p['installer_id'])
            && trim((string) ($p['registration_requested_company_name'] ?? '')) !== '';
        $hasInstaller = !empty($p['installer_id']);
        $emailDomain = abas_email_domain((string) $p['email']);
        $instPreview = $isOwnerType ? abas_registration_installation_preview($conn, $userId) : [];
        $allInstFound = $instPreview !== [] && !in_array(false, array_column($instPreview, 'found'), true);
        ?>
    <div class="abas-card">
        <div class="flex flex-wrap justify-between gap-2 mb-3">
            <div>
                <div class="font-semibold text-lg"><?= htmlspecialchars((string) ($p['registration_display_name'] ?? $p['username'])) ?></div>
                <div class="text-sm text-gray-600"><?= htmlspecialchars(abas_registration_type_label($regType)) ?></div>
            </div>
            <span class="text-xs text-gray-500"><?= htmlspecialchars((string) ($p['registration_requested_at'] ?? '')) ?></span>
        </div>

        <dl class="grid sm:grid-cols-2 gap-2 text-sm mb-4">
            <div><dt class="text-gray-500">E-mail</dt><dd><?= htmlspecialchars((string) $p['email']) ?></dd></div>
            <div><dt class="text-gray-500">Telefon</dt><dd><?= htmlspecialchars((string) $p['phone']) ?></dd></div>
            <?php if ($hasInstaller || !empty($p['display_company_name'])): ?>
            <div class="sm:col-span-2"><dt class="text-gray-500">Firma</dt><dd>
                <?= htmlspecialchars((string) ($p['display_company_name'] ?? '')) ?>
                <?php if ($hasInstaller): ?>
                    <span class="text-emerald-700 text-xs block">Tilknyttet godkendt installatør</span>
                <?php endif; ?>
            </dd></div>
            <?php endif; ?>
        </dl>

        <?php if ($needsNewCompany): ?>
        <div class="border border-amber-200 bg-amber-50 rounded-xl p-4 mb-4 space-y-3">
            <h3 class="font-medium text-amber-950">Ny virksomhed</h3>
            <p class="text-sm text-amber-900">Opret firma og tilknyt ansøgeren, før du godkender. Du kan godkende som virksomhedsadministrator.</p>
            <form method="post" class="grid sm:grid-cols-2 gap-3 items-end">
                <input type="hidden" name="user_id" value="<?= $userId ?>">
                <input type="hidden" name="action" value="create_company">
                <div class="abas-field">
                    <label class="abas-label text-xs">Firmanavn</label>
                    <input name="company_name" required class="abas-input text-sm"
                           value="<?= htmlspecialchars((string) ($p['registration_requested_company_name'] ?? '')) ?>">
                </div>
                <div class="abas-field">
                    <label class="abas-label text-xs">E-mail-domæne</label>
                    <input name="email_domain" required class="abas-input text-sm font-mono"
                           value="<?= htmlspecialchars($emailDomain) ?>">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="abas-btn-secondary text-sm">Opret firma og tilknyt ansøger</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($isOwnerType && $instPreview !== []): ?>
        <div class="mb-4">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <h3 class="font-medium text-gray-900">Ønskede anlæg</h3>
                <?php if (!$allInstFound): ?>
                <form method="post" class="inline">
                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                    <input type="hidden" name="action" value="sync_installations">
                    <button type="submit" class="abas-btn-secondary text-xs">Hent manglende fra Trekant</button>
                </form>
                <?php endif; ?>
            </div>
            <div class="abas-table-wrap">
                <table class="abas-table text-sm">
                    <thead>
                        <tr>
                            <th>ABA-nr.</th>
                            <th>Navn</th>
                            <th>By</th>
                            <th>Status</th>
                            <th>I cache</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($instPreview as $row): ?>
                        <?php $inst = $row['installation']; ?>
                        <tr>
                            <td class="font-mono font-medium"><?= htmlspecialchars($row['miscno2']) ?></td>
                            <?php if ($inst): ?>
                                <td><?= htmlspecialchars((string) ($inst['name'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($inst['city'] ?? '—')) ?></td>
                                <td>
                                    <span class="<?= htmlspecialchars(abas_mon_stat_badge_class((string) ($inst['mon_stat'] ?? ''))) ?> text-xs">
                                        <?= htmlspecialchars(abas_mon_stat_label((string) ($inst['mon_stat'] ?? ''))) ?>
                                    </span>
                                </td>
                                <td class="text-emerald-700">Ja</td>
                            <?php else: ?>
                                <td colspan="3" class="text-amber-800">Ikke i lokal cache</td>
                                <td class="text-amber-700">Nej</td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!$allInstFound): ?>
                <p class="abas-hint mt-2">Synkronisér manglende anlæg før godkendelse, så brugeren kan tilknyttes korrekt.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="post" class="border-t pt-4 space-y-3">
            <input type="hidden" name="user_id" value="<?= $userId ?>">
            <?php if ($isMontor): ?>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="as_virksomhedsadmin" value="1" class="abas-checkbox">
                Godkend som virksomhedsadministrator (i stedet for montør)
            </label>
            <?php endif; ?>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="sms_service_allowed" value="1" class="abas-checkbox">
                Må betjene via SMS
            </label>
            <div class="abas-field max-w-xs">
                <label class="abas-label text-xs">SMS-kode (hvis SMS tilladt)</label>
                <input name="sms_code" class="abas-input font-mono text-sm" placeholder="Min. 6 tegn" minlength="6">
            </div>
            <div class="flex flex-wrap gap-2">
                <button name="action" value="approve" class="abas-btn-primary"
                    <?= ($isOwnerType && !$allInstFound) ? ' disabled title="Synk anlæg først"' : '' ?>>
                    Godkend
                </button>
                <button name="action" value="reject" type="submit" class="abas-btn-secondary" onclick="return confirm('Afvis ansøgning?')">Afvis</button>
            </div>
            <?php if ($isOwnerType && !$allInstFound): ?>
                <p class="text-xs text-gray-500">Godkend er låst indtil alle anlæg findes i cache.</p>
            <?php endif; ?>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php';
