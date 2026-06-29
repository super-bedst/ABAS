<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/bas_sso_client.php';

if (($reason = abas_bas_sso_disabled_reason()) !== null) {
    abas_flash_set('error', $reason);
    abas_redirect('/login.php');
}

if (!empty($_SESSION['user_id'])) {
    abas_redirect('dashboard.php');
}

try {
    $redirectUri = abas_bas_sso_login_redirect_uri();
    $url = abas_bas_sso_build_authorize_url($redirectUri);
} catch (Throwable $e) {
    if (function_exists('abas_log_error')) {
        abas_log_error('bas_sso', 'Authorize redirect failed', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'redirect_uri' => abas_bas_sso_login_redirect_uri(),
        ]);
    }
    abas_flash_set('error', 'SSO kunne ikke startes: ' . $e->getMessage());
    abas_redirect('/login.php');
}

abas_session_release();
header('Location: ' . $url, true, 302);
exit;
