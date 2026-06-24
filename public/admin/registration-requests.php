<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/registration.php';
require_once __DIR__ . '/../../includes/users.php';

$conn = abas_db();
$admin = abas_require_login();
abas_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        $smsAllowed = !empty($_POST['sms_service_allowed']);
        $smsCode = trim($_POST['sms_code'] ?? '');
        $result = abas_approve_registration($conn, $userId, (int) $admin['id'], $smsAllowed, $smsCode);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'reject') {
        $result = abas_reject_registration($conn, $userId, (int) $admin['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    }
    abas_redirect('admin/registration-requests.php');
}

$pending = $conn->query(
    "SELECT u.*, ai.company_name FROM users u
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
<p class="abas-page-lead">Afventende ansøgninger om adgang til ABA Service.</p>

<?php if ($pending === []): ?>
    <p class="text-gray-500 mt-6">Ingen afventende anmodninger.</p>
<?php else: ?>
<div class="mt-6 space-y-4">
    <?php foreach ($pending as $p):
        $reqInst = abas_registration_installation_requests($conn, (int) $p['id']);
        ?>
    <div class="abas-card">
        <div class="flex flex-wrap justify-between gap-2 mb-3">
            <div>
                <div class="font-semibold text-lg"><?= htmlspecialchars($p['username']) ?></div>
                <div class="text-sm text-gray-600"><?= htmlspecialchars(abas_registration_type_label((string) $p['registration_type'])) ?></div>
            </div>
            <span class="text-xs text-gray-500"><?= htmlspecialchars((string) ($p['registration_requested_at'] ?? '')) ?></span>
        </div>
        <dl class="grid sm:grid-cols-2 gap-2 text-sm mb-4">
            <div><dt class="text-gray-500">E-mail</dt><dd><?= htmlspecialchars($p['email']) ?></dd></div>
            <div><dt class="text-gray-500">Telefon</dt><dd><?= htmlspecialchars((string) $p['phone']) ?></dd></div>
            <?php if (!empty($p['company_name'])): ?>
            <div><dt class="text-gray-500">Firma</dt><dd><?= htmlspecialchars($p['company_name']) ?></dd></div>
            <?php endif; ?>
            <?php if ($reqInst !== []): ?>
            <div class="sm:col-span-2"><dt class="text-gray-500">Ønskede anlæg</dt><dd class="font-mono"><?= htmlspecialchars(implode(', ', array_column($reqInst, 'miscno2'))) ?></dd></div>
            <?php endif; ?>
        </dl>
        <form method="post" class="border-t pt-4 space-y-3">
            <input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="sms_service_allowed" value="1" class="abas-checkbox">
                Må betjene via SMS
            </label>
            <div class="abas-field max-w-xs">
                <label class="abas-label text-xs">SMS-kode (hvis SMS tilladt)</label>
                <input name="sms_code" class="abas-input font-mono text-sm" placeholder="Min. 6 tegn" minlength="6">
            </div>
            <div class="flex flex-wrap gap-2">
                <button name="action" value="approve" class="abas-btn-primary">Godkend</button>
                <button name="action" value="reject" class="abas-btn-secondary" onclick="return confirm('Afvis ansøgning?')">Afvis</button>
            </div>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php';
