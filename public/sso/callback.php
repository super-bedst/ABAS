<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/bas_sso_auth.php';

$loginUrl = '/login.php';

if (($reason = abas_bas_sso_disabled_reason()) !== null) {
    abas_flash_set('error', $reason);
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

if ($code === '' || $state === '') {
    abas_flash_set('error', 'Ugyldigt SSO-svar (mangler code/state).');
    abas_redirect($loginUrl);
}
if ($expectedState === '') {
    abas_flash_set('error', 'SSO-session udløbet — prøv igen (slet evt. cookies for siden).');
    abas_redirect($loginUrl);
}
if (!hash_equals($expectedState, $state)) {
    abas_flash_set('error', 'Ugyldigt SSO-svar (state). Prøv igen.');
    abas_redirect($loginUrl);
}

try {
    $redirectUri = abas_bas_sso_login_redirect_uri();
    $tokenPayload = abas_bas_sso_exchange_authorization_code($code, $redirectUri, $verifier);
    if ($tokenPayload === null) {
        $detail = abas_bas_sso_last_exchange_error();
        throw new RuntimeException(
            $detail ?? ('Kunne ikke hente SSO-token. Tjek redirect URI: ' . $redirectUri)
        );
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
