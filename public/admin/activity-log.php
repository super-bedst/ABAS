<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/activity_log.php';
require_once __DIR__ . '/../../includes/table_list.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? '')),
    'action' => trim((string) ($_GET['action'] ?? '')),
    'user_id' => (int) ($_GET['user_id'] ?? 0),
    's_ins' => trim((string) ($_GET['s_ins'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$result = abas_activity_search($conn, $filters, $perPage, $offset);
$rows = $result['rows'];
$total = $result['total'];
$totalPages = max(1, (int) ceil($total / $perPage));

$categoryActions = abas_activity_category_actions($conn, $filters['category'] !== '' ? $filters['category'] : null);
$actionOptions = $filters['category'] !== ''
    ? abas_activity_category_actions($conn, $filters['category'])
    : array_values(array_unique(array_map(
        static fn (string $key): string => substr($key, strpos($key, '/') + 1),
        abas_activity_category_actions($conn)
    )));

$userOptions = $conn->query(
    'SELECT id, username, email FROM users ORDER BY username LIMIT 500'
)->fetch_all(MYSQLI_ASSOC);

$queryBase = array_filter($filters, static fn ($v): bool => $v !== null && $v !== '' && $v !== 0);

$pageTitle = 'Aktivitetslog';
$adminSectionTitle = 'Aktivitetslog';
$retentionDays = abas_activity_log_retention_days();
$retentionNote = $retentionDays === null
    ? 'Aktivitetslog bevares uden automatisk sletning.'
    : 'Hændelser ældre end ' . $retentionDays . ' dage slettes automatisk (én gang dagligt via service-reconcile cron).';
$adminSectionLead = 'Alle hændelser inkl. service, login, brugerændringer og API-kald. ' . $retentionNote . ' Fejllogfil viser kun de seneste 50 linjer.';
$currentUser = $user;
require __DIR__ . '/../partials/admin_shell_start.php';
?>

<form method="get" class="abas-card mb-4" data-abas-loading="Søger i log…">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        <div class="abas-field sm:col-span-2 lg:col-span-1">
            <label class="abas-label" for="q">Søg</label>
            <input id="q" name="q" class="abas-input" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Bruger, objekt, detaljer…">
        </div>
        <div class="abas-field">
            <label class="abas-label" for="category">Kategori</label>
            <select id="category" name="category" class="abas-input">
                <option value="">Alle</option>
                <?php foreach (abas_activity_categories() as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"<?= $filters['category'] === $cat ? ' selected' : '' ?>><?= htmlspecialchars(ucfirst($cat)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="abas-field">
            <label class="abas-label" for="action">Handling</label>
            <select id="action" name="action" class="abas-input">
                <option value="">Alle</option>
                <?php foreach ($actionOptions as $action): ?>
                    <option value="<?= htmlspecialchars($action) ?>"<?= $filters['action'] === $action ? ' selected' : '' ?>>
                        <?= htmlspecialchars(abas_activity_action_label($filters['category'] ?: 'service', $action)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="abas-field">
            <label class="abas-label" for="user_id">Bruger</label>
            <select id="user_id" name="user_id" class="abas-input">
                <option value="">Alle</option>
                <?php foreach ($userOptions as $u): ?>
                    <option value="<?= (int) $u['id'] ?>"<?= $filters['user_id'] === (int) $u['id'] ? ' selected' : '' ?>>
                        <?= htmlspecialchars($u['username'] . ' · ' . $u['email']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="abas-field">
            <label class="abas-label" for="s_ins">Anlæg (s_ins / ABA-nr.)</label>
            <input id="s_ins" name="s_ins" type="text" class="abas-input" value="<?= htmlspecialchars($filters['s_ins']) ?>" placeholder="s_ins eller fab0100">
        </div>
        <div class="abas-field">
            <label class="abas-label" for="date_from">Fra dato</label>
            <input id="date_from" name="date_from" type="date" class="abas-input" value="<?= htmlspecialchars($filters['date_from']) ?>">
        </div>
        <div class="abas-field">
            <label class="abas-label" for="date_to">Til dato</label>
            <input id="date_to" name="date_to" type="date" class="abas-input" value="<?= htmlspecialchars($filters['date_to']) ?>">
        </div>
    </div>
    <div class="flex flex-wrap gap-2 mt-4">
        <button type="submit" class="abas-btn-primary">Filtrer</button>
        <a href="<?= abas_url('admin/activity-log.php') ?>" class="abas-btn-secondary">Nulstil</a>
        <span class="text-sm text-gray-500 self-center ml-auto"><?= (int) $total ?> hændelser</span>
    </div>
</form>

<div class="abas-card !p-0 overflow-hidden">
    <?php if ($rows === []): ?>
        <p class="p-5 text-gray-500">Ingen hændelser matcher filteret.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="abas-table w-full">
                <thead>
                    <tr>
                        <th>Tidspunkt</th>
                        <th>Bruger</th>
                        <th>Handling</th>
                        <th>Objekt</th>
                        <th>Detaljer</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $event): ?>
                        <tr>
                            <td class="whitespace-nowrap text-sm"><?= htmlspecialchars((string) $event['created_at']) ?></td>
                            <td class="text-sm">
                                <?= htmlspecialchars((string) ($event['actor_username'] ?? '—')) ?>
                                <?php if (!empty($event['actor_role'])): ?>
                                    <span class="text-xs text-gray-500 block"><?= htmlspecialchars(abas_role_label((string) $event['actor_role'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm whitespace-nowrap">
                                <span class="abas-activity-badge abas-activity-badge--<?= htmlspecialchars((string) $event['category']) ?>">
                                    <?= htmlspecialchars(abas_activity_action_label((string) $event['category'], (string) $event['action'])) ?>
                                </span>
                            </td>
                            <td class="text-sm"><?= htmlspecialchars((string) ($event['object_label'] ?? $event['object_type'] ?? '—')) ?></td>
                            <td class="text-sm text-gray-700 max-w-xs truncate" title="<?= htmlspecialchars((string) ($event['details'] ?? '')) ?>">
                                <?= htmlspecialchars((string) ($event['details'] ?? '—')) ?>
                            </td>
                            <td class="text-sm font-mono text-gray-500"><?= htmlspecialchars((string) ($event['ip_address'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="flex flex-wrap gap-2 justify-center mt-4" aria-label="Paginering">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <?php if ($p === 1 || $p === $totalPages || abs($p - $page) <= 2): ?>
                <a href="<?= htmlspecialchars(abas_table_page_url('admin/activity-log.php', array_merge($queryBase, ['page' => $p]))) ?>"
                   class="abas-chip<?= $p === $page ? ' abas-chip-active' : '' ?>"><?= $p ?></a>
            <?php elseif (abs($p - $page) === 3): ?>
                <span class="px-2 text-gray-400">…</span>
            <?php endif; ?>
        <?php endfor; ?>
    </nav>
<?php endif; ?>

<?php require __DIR__ . '/../partials/admin_shell_end.php';
