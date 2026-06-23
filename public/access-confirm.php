<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_flow.php';
require_once __DIR__ . '/../includes/roles.php';

$conn = abas_db();
$user = abas_require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    abas_access_set_due($conn, (int) $user['id']);
    abas_flash_set('success', 'Adgang bekræftet.');
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Bekræft adgang';
$currentUser = $user;
require __DIR__ . '/partials/header.php';
?>
<div class="max-w-lg mx-auto bg-white rounded-lg shadow p-6 border">
    <h1 class="text-xl font-semibold text-brand mb-2">Bekræft fortsat adgang</h1>
    <p class="text-sm text-gray-600 mb-4">Af sikkerhedshensyn skal du bekræfte adgang hver <?= abas_access_confirm_months($conn) ?>. måned.</p>
    <form method="post">
        <button class="bg-brand text-white px-4 py-2 rounded">Jeg bekræfter min adgang</button>
    </form>
</div>
<?php require __DIR__ . '/partials/footer.php';
