<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['mfa_verified'])) {
    abas_redirect('dashboard.php');
}

if (!empty($_SESSION['mfa_pending_user_id'])) {
    abas_redirect('mfa-verify.php');
}

require __DIR__ . '/register.php';
