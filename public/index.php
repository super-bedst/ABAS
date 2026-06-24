<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['mfa_verified'])) {
    abas_redirect('dashboard.php');
}

if (!empty($_SESSION['mfa_pending_user_id'])) {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/mfa.php';
    $conn = abas_db();
    $pendingUser = abas_mfa_pending_user($conn);
    if ($pendingUser && !abas_user_mfa_enrolled($conn, (int) $pendingUser['id'])) {
        abas_redirect('mfa-enroll.php');
    }
    abas_redirect('mfa-verify.php');
}

require __DIR__ . '/register.php';
