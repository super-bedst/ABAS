<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/bas_sso_auth.php';

abas_send_embed_headers();

$conn = abas_db();
$error = trim((string) ($_GET['error'] ?? ''));
$code = trim((string) ($_GET['code'] ?? ''));

if (!abas_bas_sso_enabled()) {
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="da"><body><p>BAS SSO er ikke konfigureret.</p></body></html>';
    exit;
}

if (!empty($_SESSION['user_id']) && abas_is_embed_session()) {
    abas_redirect(abas_embed_url('vc-service.php'));
}

if ($error !== '') {
    abas_embed_error('SSO afvist: ' . $error);
}

if ($code === '') {
    abas_embed_error('Manglende SSO-kode. Åbn VC Service fra BAS-menuen.');
}

try {
    $tokenPayload = abas_bas_sso_exchange_authorization_code(
        $code,
        abas_bas_sso_embed_redirect_uri()
    );
    if ($tokenPayload === null) {
        throw new RuntimeException('Kunne ikke hente SSO-token fra BAS.');
    }
    $claims = abas_bas_sso_claims_from_token_response($tokenPayload);
    $user = abas_bas_sso_find_user($conn, $claims);
    if ($user === null) {
        $name = (string) ($claims['preferred_username'] ?? $claims['email'] ?? 'ukendt');
        throw new RuntimeException(
            'Ingen ABA-bruger er koblet til BAS-kontoen «' . $name . '». Kontakt administrator.'
        );
    }
    abas_bas_sso_complete_login($conn, $user, $claims, true);
    abas_redirect(abas_embed_url('vc-service.php'));
} catch (Throwable $e) {
    abas_embed_error($e->getMessage());
}

function abas_embed_error(string $message): never
{
    http_response_code(403);
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="da"><head><meta charset="utf-8"><title>ABA Service</title>'
        . '<style>body{font-family:Segoe UI,sans-serif;margin:1.5rem;color:#1f2937}'
        . '.box{border:1px solid #f59e0b;background:#fffbeb;padding:1rem;border-radius:.5rem;max-width:32rem}</style>'
        . '</head><body><div class="box">' . $safe . '</div></body></html>';
    exit;
}
