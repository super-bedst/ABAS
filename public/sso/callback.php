<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/bas_sso_auth.php';

$loginUrl = abas_url('login.php');

if (!abas_bas_sso_enabled()) {
    abas_flash_set('error', 'BAS SSO er ikke konfigureret.');
    abas_redirect($loginUrl);
}

$error = trim((string) ($_GET['error'] ?? ''));
if ($error !== '') {
    abas_flash_set('error', 'SSO login afvist: ' . $error);
    abas_redirect($loginUrl);
}

$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));
$expectedState = (string) ($_SESSION['abas_bas_sso_oauth_state'] ?? '');
$verifier = (string) ($_SESSION['abas_bas_sso_pkce_verifier'] ?? '');
unset($_SESSION['abas_bas_sso_oauth_state'], $_SESSION['abas_bas_sso_pkce_verifier']);

if ($code === '' || $state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    abas_flash_set('error', 'Ugyldigt SSO-svar (state).');
    abas_redirect($loginUrl);
}

try {
    $tokenPayload = abas_bas_sso_exchange_authorization_code(
        $code,
        abas_bas_sso_login_redirect_uri(),
        $verifier
    );
    if ($tokenPayload === null) {
        throw new RuntimeException('Kunne ikke hente SSO-token.');
    }
    $claims = abas_bas_sso_claims_from_token_response($tokenPayload);
    $conn = abas_db();
    $user = abas_bas_sso_find_user($conn, $claims);
    if ($user === null) {
        throw new RuntimeException('Ingen ABA-bruger matcher din BAS-konto. Kontakt administrator.');
    }
    abas_bas_sso_complete_login($conn, $user, $claims, false);
    abas_redirect('dashboard.php');
} catch (Throwable $e) {
    abas_flash_set('error', $e->getMessage());
    abas_redirect($loginUrl);
}
