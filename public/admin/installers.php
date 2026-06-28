<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/installers.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['company_name'] ?? '');
        $domain = trim($_POST['email_domain'] ?? '');
        $result = abas_installer_create($conn, $name, $domain, (int) $user['id']);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? ($result['ok'] ? 'Firma oprettet.' : 'Fejl'));
    } elseif ($action === 'add_domain') {
        $installerId = (int) ($_POST['installer_id'] ?? 0);
        $domain = trim($_POST['email_domain'] ?? '');
        $result = abas_installer_add_domain($conn, $installerId, $domain);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Domæne tilføjet.');
    } elseif ($action === 'delete') {
        $installerId = (int) ($_POST['installer_id'] ?? 0);
        $result = abas_installer_delete($conn, $installerId);
        abas_flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    }

    abas_redirect('admin/installers.php');
}

$rows = abas_installers_with_domains($conn);
$pageTitle = 'Installatører';
$currentUser = $user;
require __DIR__ . '/../partials/header.php';
?>
<div class="mb-2"><a href="<?= abas_url('admin/index.php') ?>" class="abas-back-link">&larr; Admin</a></div>
<h1 class="abas-page-title !text-xl">Godkendte installatører</h1>
<p class="abas-page-lead">Et firma kan have flere e-mail-domæner. Montører matches på domæne ved registrering.</p>

<form method="post" class="abas-card mb-6 max-w-lg abas-form">
    <input type="hidden" name="action" value="create">
    <h2 class="abas-card-title">Opret nyt firma</h2>
    <div class="abas-field">
        <label class="abas-label" for="company_name">Firmanavn</label>
        <input id="company_name" name="company_name" required class="abas-input">
    </div>
    <div class="abas-field">
        <label class="abas-label" for="new_email_domain">Første e-mail-domæne</label>
        <input id="new_email_domain" name="email_domain" required placeholder="firma.dk" class="abas-input">
    </div>
    <button class="abas-btn-primary">Opret firma</button>
</form>

<?php if ($rows === []): ?>
    <p class="text-gray-500">Ingen godkendte installatører endnu.</p>
<?php else: ?>
<div class="space-y-4">
    <?php foreach ($rows as $r):
        $installerId = (int) $r['id'];
        $montorCount = (int) ($r['montor_count'] ?? 0);
        $companyName = (string) $r['company_name'];
        $domains = $r['domains'] ?? [];
        $hasPlaceholderDomain = false;
        foreach ($domains as $domain) {
            if (str_ends_with(strtolower((string) $domain), '.trekantbrand-import.local')) {
                $hasPlaceholderDomain = true;
                break;
            }
        }
        ?>
    <div class="abas-card">
        <div class="flex flex-wrap justify-between gap-3 mb-3">
            <div>
                <h2 class="text-lg font-semibold text-brand"><?= htmlspecialchars($companyName) ?></h2>
                <p class="text-sm text-gray-600"><?= $montorCount ?> montør(er) / virksomhedsadmin(s) tilknyttet</p>
                <?php if ($hasPlaceholderDomain): ?>
                    <p class="text-sm text-amber-800 mt-1">Import-placeholder domæne — tilføj det rigtige e-mail-domæne nedenfor, så montør-registrering virker.</p>
                <?php endif; ?>
            </div>
            <form method="post" class="shrink-0"
                  onsubmit="return confirm(<?= json_encode(
                      'ADVARSEL: Slet firmaet "' . $companyName . '"?\n\n'
                      . 'Alle ' . $montorCount . ' montør(er)/virksomhedsadmin(s) tilknyttet firmaet fjernes permanent.\n\n'
                      . 'Domæner: ' . ($domains !== [] ? implode(', ', $domains) : '(ingen)')
                  ) ?>);">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="installer_id" value="<?= $installerId ?>">
                <button type="submit" class="abas-btn-danger text-sm">Slet firma</button>
            </form>
        </div>

        <div class="mb-4">
            <h3 class="text-sm font-medium text-gray-700 mb-2">E-mail-domæner</h3>
            <?php if ($domains === []): ?>
                <p class="text-sm text-amber-700">Ingen domæner — tilføj mindst ét domæne.</p>
            <?php else: ?>
                <ul class="flex flex-wrap gap-2">
                    <?php foreach ($domains as $domain):
                        $isPlaceholder = str_ends_with(strtolower((string) $domain), '.trekantbrand-import.local');
                        ?>
                        <li class="abas-badge <?= $isPlaceholder ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-gray-100 text-gray-800 border border-gray-200' ?> font-mono text-xs"><?= htmlspecialchars($domain) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <form method="post" class="flex flex-wrap gap-2 items-end border-t border-gray-100 pt-4">
            <input type="hidden" name="action" value="add_domain">
            <input type="hidden" name="installer_id" value="<?= $installerId ?>">
            <div class="abas-field flex-1 min-w-[12rem]">
                <label class="abas-label text-xs">Tilføj domæne</label>
                <input name="email_domain" required placeholder="andet-domæne.dk" class="abas-input text-sm">
            </div>
            <button class="abas-btn-secondary text-sm">Tilføj domæne</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../partials/footer.php';
