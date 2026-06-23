<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function abas_mail_send(string $to, string $subject, string $bodyHtml): bool
{
    $cfg = abas_config()['mail'];
    $from = $cfg['from'];
    $fromName = $cfg['from_name'];
    $storage = abas_root() . '/storage/mail';
    if (!is_dir($storage)) {
        @mkdir($storage, 0775, true);
    }
    $logFile = $storage . '/mail-' . date('Y-m-d') . '.log';
    $entry = sprintf(
        "[%s] TO=%s SUBJECT=%s\n%s\n---\n",
        date('c'),
        $to,
        $subject,
        strip_tags($bodyHtml)
    );
    file_put_contents($logFile, $entry, FILE_APPEND);

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $fromName . ' <' . $from . '>',
    ];

    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $bodyHtml, implode("\r\n", $headers));
}

function abas_mail_password_link(int $userId, string $email, string $token, string $kind): void
{
    $url = abas_full_url('set-password.php') . '?token=' . urlencode($token);
    $subject = $kind === 'welcome' ? 'Velkommen til ABA Service — vælg adgangskode' : 'Nulstil adgangskode — ABA Service';
    $body = '<p>Hej,</p><p>Klik på linket for at ' . ($kind === 'welcome' ? 'oprette' : 'nulstille') . ' din adgangskode:</p>'
        . '<p><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></p>'
        . '<p>Linket udløber snart.</p>';
    abas_mail_send($email, $subject, $body);
}
