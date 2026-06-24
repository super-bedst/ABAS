<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/password_flow.php';
require_once __DIR__ . '/roles.php';

function abas_current_user(mysqli $conn): ?array
{
    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND active = 1 AND registration_status = "approved" LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function abas_login_user(array $user): void
{
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['username'];
    unset($_SESSION['mfa_verified']);
}

function abas_login_error_for_user(?array $user): string
{
    if (!$user) {
        return 'Forkert login eller adgangskode.';
    }
    if (($user['registration_status'] ?? 'approved') === 'pending') {
        return 'Din ansøgning afventer stadig godkendelse.';
    }
    if (($user['registration_status'] ?? '') === 'rejected') {
        return 'Din ansøgning blev afvist. Kontakt TrekantBrand.';
    }
    if (!(int) ($user['active'] ?? 0)) {
        return 'Kontoen er deaktiveret.';
    }

    return 'Forkert login eller adgangskode.';
}

function abas_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function abas_require_login(): array
{
    $conn = abas_db();

    if (!empty($_SESSION['mfa_pending_user_id']) && empty($_SESSION['user_id'])) {
        require_once __DIR__ . '/mfa.php';
        $conn = abas_db();
        $pendingUser = abas_mfa_pending_user($conn);
        if ($pendingUser) {
            abas_mfa_redirect_for_user($conn, $pendingUser);
        }
        abas_redirect('login.php');
        exit;
    }

    $user = abas_current_user($conn);
    if (!$user) {
        abas_redirect('login.php');
        exit;
    }
    if (empty($user['password_hash'])) {
        abas_redirect('forgot-password.php');
        exit;
    }

    require_once __DIR__ . '/mfa.php';
    if (abas_mfa_needs_step($conn, $user)) {
        abas_mfa_redirect_for_user($conn, $user);
        exit;
    }

    if (abas_access_needs_confirm($user) && !str_contains($_SERVER['PHP_SELF'] ?? '', 'access-confirm')) {
        abas_redirect('access-confirm.php');
        exit;
    }

    return $user;
}

function abas_email_domain(string $email): string
{
    $parts = explode('@', strtolower(trim($email)));
    return $parts[1] ?? '';
}

require_once __DIR__ . '/installers.php';

function abas_user_may_access_installation(mysqli $conn, array $user, array $installation): bool
{
    if (abas_user_can_access_all_installations($user['role'])) {
        return true;
    }
    $stmt = $conn->prepare(
        'SELECT 1 FROM user_installations ui WHERE ui.user_id = ? AND ui.installation_id = ? LIMIT 1'
    );
    $uid = (int) $user['id'];
    $iid = (int) $installation['id'];
    $stmt->bind_param('ii', $uid, $iid);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

function abas_find_installation_by_miscno2(mysqli $conn, string $miscno2): ?array
{
    $misc = strtoupper(trim($miscno2));
    $stmt = $conn->prepare('SELECT * FROM installations WHERE UPPER(miscno2) = ? LIMIT 1');
    $stmt->bind_param('s', $misc);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}
