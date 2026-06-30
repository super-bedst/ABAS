<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/user_management.php';
require_once __DIR__ . '/../includes/service.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['vagtcentral', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_user') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $username = abas_resolve_username_for_email($conn, $email, (string) ($_POST['username'] ?? ''));
        $phone = abas_normalize_phone(trim($_POST['phone'] ?? ''));
        $misc = strtolower(trim($_POST['miscno2'] ?? ''));
        if (!abas_validate_phone($phone)) {
            abas_flash_set('error', 'Angiv et gyldigt telefonnummer (min. 8 cifre).');
            abas_redirect('vc-anlaegsbrugere.php');
        }
        $smsCode = trim($_POST['sms_code'] ?? '');
        if (!abas_validate_sms_code($smsCode)) {
            abas_flash_set('error', 'SMS-kode skal være mindst 6 tegn.');
            abas_redirect('vc-anlaegsbrugere.php');
        }
        $chk = $conn->prepare('SELECT id FROM users WHERE email=? OR username=? LIMIT 1');
        $chk->bind_param('ss', $email, $username);
        $chk->execute();
        if ($chk->get_result()->fetch_row()) {
            abas_flash_set('error', 'Bruger findes allerede.');
        } else {
            $vcId = (int) $user['id'];
            $stmt = $conn->prepare(
                'INSERT INTO users (email, username, role, phone, active, created_by_user_id) VALUES (?, ?, "anlaegsejer", ?, 1, ?)'
            );
            $stmt->bind_param('sssi', $email, $username, $phone, $vcId);
            $stmt->execute();
            $newId = (int) $stmt->insert_id;
            $stmt->close();
            abas_set_user_sms_code($conn, $newId, $smsCode);
            abas_password_send_flow_email($conn, $newId, 'welcome');
            $installation = abas_find_installation_by_miscno2($conn, $misc);
            if ($installation) {
                $iid = (int) $installation['id'];
                $link = $conn->prepare('INSERT IGNORE INTO user_installations (user_id, installation_id) VALUES (?, ?)');
                $link->bind_param('ii', $newId, $iid);
                $link->execute();
                $link->close();
            }
            abas_flash_set('success', 'Anlægsbruger oprettet og linket.');
        }
        $chk->close();
    } elseif ($action === 'link') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $iid = (int) ($_POST['installation_id'] ?? 0);
        $link = $conn->prepare('INSERT IGNORE INTO user_installations (user_id, installation_id) VALUES (?, ?)');
        $link->bind_param('ii', $uid, $iid);
        $link->execute();
        $link->close();
        abas_flash_set('success', 'Tilknytning gemt.');
    }
    abas_redirect('vc-anlaegsbrugere.php');
}

$sort = abas_table_resolve_sort((string) ($_GET['sort'] ?? ''), abas_vc_anlaegsbrugere_sort_columns(), 'name');
$sortDir = abas_table_normalize_sort_dir((string) ($_GET['dir'] ?? 'asc'));
$search = trim((string) ($_GET['q'] ?? ''));
$listQuery = array_filter(['q' => $search !== '' ? $search : null, 'sort' => $sort !== 'name' ? $sort : null, 'dir' => $sortDir !== 'asc' ? $sortDir : null]);

$owners = abas_list_vc_anlaegsbrugere($conn, $sort, $sortDir, $search);
$ownerIds = array_map(static fn (array $row): int => (int) $row['id'], $owners);
$directByUser = abas_user_installations_with_service_status_for_users($conn, $ownerIds);
$ownerAccess = abas_anlaegsbrugere_installation_access_for_users($conn, $ownerIds, $directByUser);
$installations = $conn->query('SELECT id, miscno2, name FROM installations ORDER BY miscno2 LIMIT 200')->fetch_all(MYSQLI_ASSOC);
abas_session_release();

$pageTitle = 'Anlægsbrugere';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<div class="flex flex-wrap items-start justify-between gap-3 mb-1">
    <div>
        <h1 class="abas-page-title !mb-0">Anlægsbrugere</h1>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
        <button type="button" id="open-link-user-modal" class="abas-btn-secondary">Tilknyt anlæg</button>
        <button type="button" id="open-create-user-modal" class="abas-btn-primary" aria-controls="create-user-modal" aria-expanded="false">Opret bruger</button>
    </div>
</div>
<p class="abas-page-lead">Opret og tilknyt anlægsejere og anlægsafprøvere til deres anlæg. Klik på en bruger for at redigere.</p>
<?php if ($user['role'] === 'admin'): ?>
<p class="text-sm text-gray-600 mb-4">
    Som administrator åbnes fuld brugerredigering. Vagtcentralen får redigering med VC-rettigheder.
</p>
<?php endif; ?>

<form method="get" class="mb-4 flex flex-wrap gap-2 items-end max-w-2xl" role="search" data-abas-loading="Søger…">
    <div class="abas-field flex-1 min-w-[14rem] !mb-0">
        <label class="abas-label" for="user-search">Søg</label>
        <input id="user-search" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Navn, e-mail, telefon, rolle, anlæg …" class="abas-input">
    </div>
    <?php if ($sort !== 'name'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>"><?php endif; ?>
    <?php if ($sortDir !== 'asc'): ?><input type="hidden" name="dir" value="<?= htmlspecialchars($sortDir) ?>"><?php endif; ?>
    <button type="submit" class="abas-btn-secondary">Søg</button>
    <?php if ($search !== ''): ?>
        <a href="<?= htmlspecialchars(abas_vc_anlaegsbrugere_page_url(['sort' => $sort !== 'name' ? $sort : null, 'dir' => $sortDir !== 'asc' ? $sortDir : null])) ?>" class="abas-btn-secondary">Ryd</a>
    <?php endif; ?>
</form>
<?php if ($search !== ''): ?>
<p class="text-sm text-gray-600 mb-4"><?= count($owners) ?> resultat<?= count($owners) === 1 ? '' : 'er' ?> for «<?= htmlspecialchars($search) ?>»</p>
<?php endif; ?>

<div class="abas-table-wrap">
    <table class="abas-table">
        <thead>
            <tr>
                <?php abas_render_table_sort_th('Bruger', abas_table_sort_link('vc-anlaegsbrugere.php', $listQuery, 'name', $sort, $sortDir, abas_vc_anlaegsbrugere_sort_columns())); ?>
                <?php abas_render_table_sort_th('E-mail', abas_table_sort_link('vc-anlaegsbrugere.php', $listQuery, 'email', $sort, $sortDir, abas_vc_anlaegsbrugere_sort_columns())); ?>
                <?php abas_render_table_sort_th('Telefon', abas_table_sort_link('vc-anlaegsbrugere.php', $listQuery, 'phone', $sort, $sortDir, abas_vc_anlaegsbrugere_sort_columns())); ?>
                <?php abas_render_table_sort_th('Rolle', abas_table_sort_link('vc-anlaegsbrugere.php', $listQuery, 'role', $sort, $sortDir, abas_vc_anlaegsbrugere_sort_columns())); ?>
                <?php abas_render_table_sort_th('Anlæg', abas_table_sort_link('vc-anlaegsbrugere.php', $listQuery, 'installations', $sort, $sortDir, abas_vc_anlaegsbrugere_sort_columns())); ?>
            </tr>
        </thead>
        <tbody>
        <?php if ($owners === []): ?>
            <tr><td colspan="5" class="text-gray-500 text-sm p-4"><?= $search !== '' ? 'Ingen brugere matcher søgningen.' : 'Ingen anlægsbrugere endnu.' ?></td></tr>
        <?php endif; ?>
        <?php foreach ($owners as $o):
            $editUrl = abas_anlaegsbruger_edit_url_for_actor($user, (int) $o['id'], $listQuery);
            $access = $ownerAccess[(int) $o['id']] ?? ['groups' => [], 'direct' => []];
            ?>
            <tr class="abas-table-row-link <?= empty($o['active']) ? 'opacity-60' : '' ?>"
                role="link"
                tabindex="0"
                data-href="<?= htmlspecialchars($editUrl) ?>"
                data-abas-loading="Åbner bruger…">
                <td class="font-medium text-brand">
                    <?= htmlspecialchars(abas_user_display_name($o)) ?>
                    <?php if (empty($o['active'])): ?>
                        <span class="text-xs text-amber-700">(inaktiv)</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) $o['email']) ?></td>
                <td><?= htmlspecialchars((string) ($o['phone'] ?? '—')) ?></td>
                <td><?= htmlspecialchars(abas_role_label((string) $o['role'])) ?></td>
                <td class="align-top max-w-md" data-abas-row-ignore="1">
                    <?php require __DIR__ . '/partials/vc-anlaegsbruger-installations.php'; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-gray-500">
    <span class="abas-badge-in-service">fab0100</span> = i service (vises først) &nbsp;
    <span class="abas-badge-ok">fab0100</span> = normal drift &nbsp;
    · Gruppeanlæg vises under gruppenavn · brug «+N anlæg» for at folde flere ud
</p>

<div id="create-user-modal" class="abas-modal hidden" role="dialog" aria-modal="true" aria-labelledby="create-user-modal-title">
    <div class="abas-modal-backdrop" data-abas-modal-close tabindex="-1"></div>
    <div class="abas-modal-panel">
        <div class="abas-modal-header">
            <h2 id="create-user-modal-title" class="abas-card-title !mb-0">Opret anlægsbruger</h2>
            <button type="button" class="abas-modal-close" data-abas-modal-close aria-label="Luk"><span aria-hidden="true">&times;</span></button>
        </div>
        <form method="post" class="space-y-0" data-abas-loading="Opretter bruger…">
            <input type="hidden" name="action" value="create_user">
            <div class="abas-field"><label class="abas-label">E-mail</label><input name="email" type="email" required class="abas-input"></div>
            <div class="abas-field"><label class="abas-label">Brugernavn</label><input name="username" maxlength="255" class="abas-input" placeholder="Samme som e-mail hvis tom"></div>
            <div class="abas-field"><label class="abas-label">Telefon</label><input name="phone" required placeholder="+45..." class="abas-input"></div>
            <div class="abas-field">
                <label class="abas-label" for="vc-sms-code">SMS-kode</label>
                <input id="vc-sms-code" name="sms_code" required minlength="6" autocomplete="off" class="abas-input font-mono" placeholder="Min. 6 tegn">
                <p class="abas-hint">Bruges sammen med telefonnummer ved SMS-kommandoer til anlæg.</p>
            </div>
            <div class="abas-field"><label class="abas-label">Anlægsnr. (valgfri)</label><input name="miscno2" placeholder="fab0100" class="abas-input font-mono"></div>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="abas-btn-primary">Opret</button>
                <button type="button" class="abas-btn-secondary" data-abas-modal-close>Annuller</button>
            </div>
        </form>
    </div>
</div>

<div id="link-user-modal" class="abas-modal hidden" role="dialog" aria-modal="true" aria-labelledby="link-user-modal-title">
    <div class="abas-modal-backdrop" data-abas-modal-close tabindex="-1"></div>
    <div class="abas-modal-panel">
        <div class="abas-modal-header">
            <h2 id="link-user-modal-title" class="abas-card-title !mb-0">Tilknyt bruger til anlæg</h2>
            <button type="button" class="abas-modal-close" data-abas-modal-close aria-label="Luk"><span aria-hidden="true">&times;</span></button>
        </div>
        <form method="post" class="space-y-0" data-abas-loading="Gemmer tilknytning…">
            <input type="hidden" name="action" value="link">
            <div class="abas-field"><label class="abas-label">Bruger</label>
            <select name="user_id" class="abas-select" required>
                <?php foreach ($owners as $o): ?>
                    <option value="<?= (int) $o['id'] ?>"><?= htmlspecialchars(abas_user_display_name($o)) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="abas-field"><label class="abas-label">Anlæg</label>
            <select name="installation_id" class="abas-select" required>
                <?php foreach ($installations as $i): ?>
                    <option value="<?= (int) $i['id'] ?>"><?= htmlspecialchars((string) $i['miscno2']) ?> — <?= htmlspecialchars((string) $i['name']) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="abas-btn-secondary">Tilknyt</button>
                <button type="button" class="abas-btn-secondary" data-abas-modal-close>Annuller</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.abasInitModal === 'function') {
        window.abasInitModal('open-create-user-modal', 'create-user-modal');
        window.abasInitModal('open-link-user-modal', 'link-user-modal');
    }
});
</script>
<?php require __DIR__ . '/partials/footer.php';
