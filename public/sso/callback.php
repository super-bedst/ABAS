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

if ($code === '' || $state === '') {
    abas_flash_set('error', 'Ugyldigt SSO-svar (mangler code/state).');
    abas_redirect($loginUrl);
}

$oauthContext = abas_bas_sso_verify_oauth_callback($state);
if ($oauthContext === null) {
    abas_bas_sso_clear_oauth_context();
    abas_flash_set('error', 'Ugyldigt SSO-svar (state). Prøv igen — klik kun én gang på «Log ind via BAS».');
    abas_redirect($loginUrl);
}

$redirectUri = $oauthContext['redirect_uri'];
abas_bas_sso_clear_oauth_context();
$verifier = '';

$codeKey = 'abas_sso_code_' . hash('sha256', $code);
if (isset($_SESSION[$codeKey])) {
    abas_flash_set('error', 'SSO-koden er allerede brugt. Klik «Log ind via BAS» igen.');
    abas_redirect($loginUrl);
}
$_SESSION[$codeKey] = 'pending';

try {
    $tokenPayload = abas_bas_sso_exchange_authorization_code($code, $redirectUri, $verifier);
    if ($tokenPayload === null) {
        unset($_SESSION[$codeKey]);
        $detail = abas_bas_sso_last_exchange_error();
        throw new RuntimeException(
            $detail ?? ('Kunne ikke hente SSO-token. Tjek redirect URI: ' . $redirectUri)
        );
    }
    $claims = abas_bas_sso_claims_from_token_response($tokenPayload);
    $conn = abas_db();
    $user = abas_bas_sso_find_user($conn, $claims);
    if ($user === null) {
        unset($_SESSION[$codeKey]);
        throw new RuntimeException('Ingen ABA-bruger matcher din BAS-konto. Kontakt administrator.');
    }
    abas_bas_sso_complete_login($conn, $user, $claims, false);
    $_SESSION[$codeKey] = 'done';
    abas_redirect('dashboard.php');
} catch (Throwable $e) {
    if (($_SESSION[$codeKey] ?? '') === 'pending') {
        unset($_SESSION[$codeKey]);
    }
    abas_flash_set('error', $e->getMessage());
    abas_redirect($loginUrl);
}
