<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user_guide.php';

$conn = abas_db();
$user = abas_require_login();

$pageTitle = 'Vejledning';
$currentUser = $user;

require __DIR__ . '/partials/header.php';
?>
<h1 class="abas-page-title">Vejledning</h1>
<p class="abas-page-lead mb-6">Hjælp til de funktioner, du har adgang til i <?= htmlspecialchars(abas_config()['app_name']) ?>.</p>
<?= abas_user_guide_render($conn, $user) ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
