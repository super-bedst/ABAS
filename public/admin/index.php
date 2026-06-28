<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/activity_log.php';
require_once __DIR__ . '/../../includes/registration.php';

$conn = abas_db();
$user = abas_require_login();
abas_require_role(['admin']);

$stats = abas_admin_dashboard_stats($conn);
$serviceStats = abas_activity_service_stats($conn);
$recent = abas_activity_recent($conn, 15);

$pendingRows = $conn->query(
    "SELECT u.id, u.email, u.registration_display_name, u.registration_type, u.registration_requested_at,
            COALESCE(ai.company_name, u.registration_requested_company_name) AS company_name
     FROM users u
     LEFT JOIN approved_installers ai ON ai.id = u.installer_id
     WHERE u.registration_status = 'pending'
     ORDER BY u.registration_requested_at ASC
     LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Administration';
$adminSectionTitle = 'Dashboard';
$adminSectionLead = 'Overblik over brugere, service og seneste aktivitet.';
$currentUser = $user;
require __DIR__ . '/../partials/admin_shell_start.php';
?>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="abas-kpi-card">
        <p class="abas-kpi-label">Aktive brugere</p>
        <p class="abas-kpi-value"><?= (int) $stats['users_active'] ?></p>
        <p class="abas-kpi-meta"><?= (int) $stats['montors'] ?> montører · <?= (int) $stats['virksomhedsadmins'] ?> virksomhedsadmins</p>
    </div>
    <div class="abas-kpi-card">
        <p class="abas-kpi-label">Afventende ansøgninger</p>
        <p class="abas-kpi-value"><?= (int) $stats['users_pending'] ?></p>
        <p class="abas-kpi-meta"><a href="<?= abas_url('admin/registration-requests.php') ?>" class="abas-link">Gå til kø</a></p>
    </div>
    <div class="abas-kpi-card">
        <p class="abas-kpi-label">Godkendte installatører</p>
        <p class="abas-kpi-value"><?= (int) $stats['installers_active'] ?></p>
        <p class="abas-kpi-meta"><a href="<?= abas_url('admin/installers.php') ?>" class="abas-link">Administrer</a></p>
    </div>
    <div class="abas-kpi-card">
        <p class="abas-kpi-label">Anlæg i service nu</p>
        <p class="abas-kpi-value"><?= (int) $stats['service_active'] ?></p>
        <p class="abas-kpi-meta">Aktive service-sessioner</p>
    </div>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="abas-kpi-card abas-kpi-card--service-start">
        <p class="abas-kpi-label">Service startet (total)</p>
        <p class="abas-kpi-value"><?= (int) $serviceStats['start_service'] ?></p>
    </div>
    <div class="abas-kpi-card abas-kpi-card--service-stop">
        <p class="abas-kpi-label">Service stoppet (total)</p>
        <p class="abas-kpi-value"><?= (int) $serviceStats['stop_service'] ?></p>
    </div>
    <div class="abas-kpi-card">
        <p class="abas-kpi-label">Service forlænget</p>
        <p class="abas-kpi-value"><?= (int) $serviceStats['extend_service'] ?></p>
    </div>
    <div class="abas-kpi-card">
        <p class="abas-kpi-label">Servicekommentarer</p>
        <p class="abas-kpi-value"><?= (int) $serviceStats['add_comment'] ?></p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <section class="abas-card !p-0 overflow-hidden">
        <div class="abas-table-head px-5 py-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold">Afventende ansøgninger</h2>
            <a href="<?= abas_url('admin/registration-requests.php') ?>" class="text-sm abas-link">Se alle</a>
        </div>
        <?php if ($pendingRows === []): ?>
            <p class="p-5 text-gray-500 text-sm">Ingen afventende ansøgninger.</p>
        <?php else: ?>
            <table class="abas-table w-full">
                <thead>
                    <tr>
                        <th>Navn</th>
                        <th>Type</th>
                        <th>Firma</th>
                        <th>Modtaget</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['registration_display_name'] ?: $row['email'])) ?></td>
                            <td><?= htmlspecialchars(abas_registration_type_label((string) ($row['registration_type'] ?? ''))) ?></td>
                            <td><?= htmlspecialchars((string) ($row['company_name'] ?? '—')) ?></td>
                            <td class="whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars(substr((string) ($row['registration_requested_at'] ?? ''), 0, 16)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="abas-card !p-0 overflow-hidden">
        <div class="abas-table-head px-5 py-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold">Seneste aktivitet</h2>
            <a href="<?= abas_url('admin/activity-log.php') ?>" class="text-sm abas-link">Fuld log</a>
        </div>
        <?php if ($recent === []): ?>
            <p class="p-5 text-gray-500 text-sm">Ingen aktivitet endnu.</p>
        <?php else: ?>
            <table class="abas-table w-full">
                <thead>
                    <tr>
                        <th>Tidspunkt</th>
                        <th>Bruger</th>
                        <th>Handling</th>
                        <th>Objekt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $event): ?>
                        <tr>
                            <td class="whitespace-nowrap text-sm"><?= htmlspecialchars(substr((string) $event['created_at'], 0, 16)) ?></td>
                            <td class="text-sm"><?= htmlspecialchars((string) ($event['actor_username'] ?? '—')) ?></td>
                            <td class="text-sm"><?= htmlspecialchars(abas_activity_action_label((string) $event['category'], (string) $event['action'])) ?></td>
                            <td class="text-sm text-gray-700"><?= htmlspecialchars((string) ($event['object_label'] ?? $event['details'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../partials/admin_shell_end.php';
