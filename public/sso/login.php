<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/bas_sso_client.php';

if (!abas_bas_sso_enabled()) {
    abas_flash_set('error', 'BAS SSO er ikke konfigureret.');
    abas_redirect('login.php');
}

if (!empty($_SESSION['user_id'])) {
    abas_redirect('dashboard.php');
}

$url = abas_bas_sso_build_authorize_url(abas_bas_sso_login_redirect_uri());
header('Location: ' . $url, true, 302);
exit;
