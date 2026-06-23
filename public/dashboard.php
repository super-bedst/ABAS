<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/trekant_client.php';
require_once __DIR__ . '/../includes/installation_sync.php';

$conn = abas_db();
$user = abas_require_login();
$q = trim($_GET['q'] ?? '');
$installations = [];

if ($q !== '') {
  if (abas_user_can_access_all_installations($user['role'])) {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare(
      'SELECT * FROM installations WHERE miscno2 LIKE ? OR name LIKE ? OR ins_no LIKE ? OR city LIKE ? ORDER BY miscno2 LIMIT 50'
    );
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $installations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if ($installations === [] && preg_match('/^[a-z]{3}\d{4}$/i', $q)) {
      try {
        $client = abas_trekant();
        $resp = $client->searchInstallations(abas_trekant_userid($user), $q);
        foreach (abas_trekant_rows($resp) as $row) {
          $id = abas_upsert_installation($conn, $row);
          if ($id) {
            $inst = abas_find_installation_by_miscno2($conn, $q);
            if ($inst) {
              $installations[] = $inst;
            }
          }
        }
      } catch (Throwable $e) {
        abas_flash_set('error', 'API-søgning fejlede: ' . $e->getMessage());
      }
    }
  } else {
    $like = '%' . $q . '%';
    $uid = (int) $user['id'];
    $stmt = $conn->prepare(
      'SELECT i.* FROM installations i
       JOIN user_installations ui ON ui.installation_id = i.id
       WHERE ui.user_id = ? AND (i.miscno2 LIKE ? OR i.name LIKE ?)
       ORDER BY i.miscno2 LIMIT 50'
    );
    $stmt->bind_param('iss', $uid, $like, $like);
    $stmt->execute();
    $installations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  }
} elseif (!abas_user_can_access_all_installations($user['role'])) {
    $uid = (int) $user['id'];
    $stmt = $conn->prepare(
        'SELECT i.* FROM installations i
         JOIN user_installations ui ON ui.installation_id = i.id
         WHERE ui.user_id = ? ORDER BY i.miscno2 LIMIT 50'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $installations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$pageTitle = 'Dashboard';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<h1 class="text-2xl font-semibold text-brand mb-4">Dashboard</h1>
<form method="get" class="mb-6 flex flex-col sm:flex-row gap-2">
    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Søg anlægsnr., navn, by..." class="flex-1 border rounded px-3 py-2">
    <button class="bg-brand text-white px-4 py-2 rounded">Søg</button>
</form>
<?php if ($installations === []): ?>
    <p class="text-gray-600">Ingen anlæg fundet.<?= $q ? ' Prøv et andet søgeord.' : '' ?></p>
<?php else: ?>
<div class="overflow-x-auto bg-white rounded shadow border">
<table class="w-full text-sm">
    <thead class="table-head">
        <tr>
            <th class="text-left p-2">ABA-nr.</th>
            <th class="text-left p-2">Navn</th>
            <th class="text-left p-2 hidden sm:table-cell">By</th>
            <th class="text-left p-2 hidden md:table-cell">Status</th>
            <th class="text-left p-2"></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($installations as $inst): ?>
        <tr class="border-t hover:bg-basbg/50">
            <td class="p-2 font-mono"><?= htmlspecialchars((string) $inst['miscno2']) ?></td>
            <td class="p-2"><?= htmlspecialchars((string) $inst['name']) ?></td>
            <td class="p-2 hidden sm:table-cell"><?= htmlspecialchars((string) $inst['city']) ?></td>
            <td class="p-2 hidden md:table-cell"><?= htmlspecialchars((string) $inst['mon_stat']) ?></td>
            <td class="p-2"><a class="text-brand underline" href="<?= abas_url('installation.php?id=' . (int) $inst['id']) ?>">Åbn</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php';
