<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/datetime.php';

function abas_mail_password_flow_html(
    string $displayName,
    string $username,
    string $actionUrl,
    string $kind,
    string $expiresAt
): string {
    $appName = (string) (abas_config()['app_name'] ?? 'ABA Service');
    $primary = '#91191A';
    $secondary = '#caa14a';
    $bg = '#F5F5EF';
    $buttonText = '#111827';

    $safeName = htmlspecialchars($displayName !== '' ? $displayName : $username, ENT_QUOTES, 'UTF-8');
    $safeUser = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
    $safeApp = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');
    $logoUrl = htmlspecialchars(abas_full_url('assets/images/trekantbrand-logo.svg'), ENT_QUOTES, 'UTF-8');
    $expiresLabel = htmlspecialchars(abas_mail_password_expiry_text($expiresAt), ENT_QUOTES, 'UTF-8');

    if ($kind === 'welcome') {
        $title = 'Velkommen — vælg adgangskode';
        $lead = 'Din konto til ' . $safeApp . ' er oprettet. Klik på knappen for at vælge din adgangskode.';
        $button = 'Vælg adgangskode';
    } else {
        $title = 'Nulstil adgangskode';
        $lead = 'Du har anmodet om at nulstille adgangskoden til ' . $safeApp . '.';
        $button = 'Nulstil adgangskode';
    }

    $html = '<!doctype html><html lang="da"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>';
    $html .= '<body style="margin:0;padding:0;background:' . $bg . ';font-family:Segoe UI,Calibri,Arial,sans-serif;">';
    $html .= '<div style="max-width:640px;margin:0 auto;padding:24px;">';

    $html .= '<div style="background:' . $primary . ';color:#ffffff;border-radius:14px 14px 0 0;padding:14px 20px;">';
    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>';
    $html .= '<td style="vertical-align:middle;"><div style="font-size:20px;font-weight:800;color:#ffffff;">' . $title . '</div></td>';
    $html .= '<td style="vertical-align:middle;text-align:right;"><img src="' . $logoUrl . '" alt="TrekantBrand" style="width:52px;height:auto;display:inline-block;border:0;"></td>';
    $html .= '</tr></table></div>';

    $html .= '<div style="background:#ffffff;border:1px solid #e5e7eb;border-top:0;border-radius:0 14px 14px 14px;padding:20px;">';
    $html .= '<p style="margin:0 0 12px;color:#111827;font-size:14px;line-height:1.5;">Hej ' . $safeName . ',</p>';
    $html .= '<p style="margin:0 0 16px;color:#111827;font-size:14px;line-height:1.5;">' . $lead . '</p>';

    $html .= '<div style="border-left:6px solid ' . $secondary . ';background:' . $bg . ';border-radius:12px;padding:14px;margin:12px 0;">';
    $html .= '<div style="font-size:12px;color:' . $primary . ';text-transform:uppercase;letter-spacing:.03em;font-weight:700;">Brugernavn</div>';
    $html .= '<div style="font-size:16px;font-weight:800;color:' . $primary . ';margin-top:4px;word-break:break-word;">' . $safeUser . '</div>';
    $html .= '</div>';

    $html .= '<div style="text-align:center;margin:18px 0 8px;">';
    $html .= '<a href="' . $safeUrl . '" style="display:inline-block;background:' . $secondary . ';color:' . $buttonText . ';text-decoration:none;padding:12px 22px;border-radius:12px;font-weight:800;font-size:14px;">' . $button . '</a>';
    $html .= '</div>';

    $html .= '<p style="margin:12px 0 0;color:#6b7280;font-size:12px;line-height:1.5;text-align:center;">' . $expiresLabel . '</p>';
    $html .= '<p style="margin:10px 0 0;color:#9ca3af;font-size:11px;line-height:1.45;text-align:center;">Hvis du ikke har bedt om denne e-mail, kan du ignorere den.</p>';
    $html .= '<p style="margin:14px 0 0;color:#6b7280;font-size:12px;text-align:center;">Venlig hilsen<br><strong>' . $safeApp . '</strong></p>';
    $html .= '</div></div></body></html>';

    return $html;
}

function abas_mail_password_expiry_text(string $expiresAt): string
{
    $formatted = abas_format_datetime($expiresAt);
    if ($formatted === '') {
        return 'Linket kan kun bruges én gang.';
    }

    return 'Linket udløber ' . $formatted . ' og kan kun bruges én gang.';
}
