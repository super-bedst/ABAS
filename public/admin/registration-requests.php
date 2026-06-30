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
require_once __DIR__ . '/../../includes/table_list.php';
require_once __DIR__ . '/../../includes/installation_groups.php';

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
        $sendWelcome = !empty($_POST['send_welcome_email']);
        $montorScoped = !empty($_POST['montor_scoped_access']);
        $groupIds = array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) $v, (array) ($_POST['group_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));
        $result = abas_approve_registration(
            $conn,
            $userId,
            (int) $admin['id'],
            $smsAllowed,
            $smsCode,
            $finalRole,
            $sendWelcome,
            $montorScoped,
            $groupIds
        );
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

$installationGroups = abas_list_all_installation_groups($conn);
$groupPickerUsesSearch = count($installationGroups) > abas_installation_groups_user_picker_threshold();

$pageTitle = 'Registreringsanmodninger';
$adminSectionTitle = 'Registreringsanmodninger';
$adminSectionLead = 'Afventende ansøgninger — godkend, afvis og tilpas adgang direkte fra kortet.';
$currentUser = $admin;
$extraHead = '<script src="' . htmlspecialchars(abas_asset_url('assets/js/registration-requests.js')) . '" defer></script>';
require __DIR__ . '/../partials/admin_shell_start.php';
?>

<?php if ($pending === []): ?>
    <p class="text-gray-500">Ingen afventende anmodninger.</p>
<?php else: ?>
<div class="abas-reg-request-list">
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
        $approveLocked = $isOwnerType && !$allInstFound;
        require __DIR__ . '/../partials/admin-registration-request-card.php';
    endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../partials/admin_shell_end.php';
