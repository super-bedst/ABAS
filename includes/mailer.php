<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app_log.php';
require_once __DIR__ . '/mail_templates.php';

/** @var string|null */
$GLOBALS['_abas_smtp_last_response'] = null;

function abas_mail_log(string $message): void
{
    $storage = abas_root() . '/storage/mail';
    if (!is_dir($storage)) {
        @mkdir($storage, 0775, true);
    }
    $logFile = $storage . '/mail-' . date('Y-m-d') . '.log';
    file_put_contents($logFile, sprintf("[%s] %s\n", date('c'), $message), FILE_APPEND);
}

/**
 * @param array<string, scalar|null> $context
 */
function abas_mail_report_failure(string $message, array $context = []): void
{
    abas_mail_log($message);
    abas_log_error('mail', $message, $context);
}

function abas_mail_send(string $to, string $subject, string $bodyHtml): bool
{
    $cfg = abas_config()['mail'];
    $from = (string) $cfg['from'];
    $fromName = (string) $cfg['from_name'];

    abas_mail_log(sprintf('TO=%s SUBJECT=%s', $to, $subject) . "\n" . strip_tags($bodyHtml) . "\n---");

    $smtpHost = trim((string) ($cfg['smtp_host'] ?? ''));
    if ($smtpHost !== '') {
        return abas_mail_send_smtp($to, $subject, $bodyHtml, $from, $fromName, $cfg);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $fromName . ' <' . $from . '>',
    ];

    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $bodyHtml, implode("\r\n", $headers));
    if (!$ok) {
        abas_mail_report_failure('PHP mail() failed — konfigurer SMTP_HOST i .env', [
            'to' => $to,
            'subject' => $subject,
        ]);
    }

    return $ok;
}

/**
 * @param array<string, mixed> $cfg
 */
function abas_mail_send_smtp(
    string $to,
    string $subject,
    string $bodyHtml,
    string $from,
    string $fromName,
    array $cfg
): bool {
    $host = trim((string) ($cfg['smtp_host'] ?? ''));
    $port = (int) ($cfg['smtp_port'] ?? 587);
    $user = (string) ($cfg['smtp_user'] ?? '');
    $pass = (string) ($cfg['smtp_pass'] ?? '');
    $secure = (string) ($cfg['smtp_secure'] ?? '');

    if ($host === '') {
        return false;
    }

    if ($secure === '') {
        $secure = $port === 465 ? 'ssl' : 'tls';
    }

    $sslOptions = [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
    ];
    $caFile = abas_env('CURL_CAINFO');
    if ($caFile !== null && $caFile !== '' && is_readable($caFile)) {
        $sslOptions['cafile'] = $caFile;
    }
    $context = stream_context_create(['ssl' => $sslOptions]);

    $remote = $secure === 'ssl'
        ? 'ssl://' . $host . ':' . $port
        : 'tcp://' . $host . ':' . $port;

    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($remote, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        abas_mail_report_failure('SMTP connect failed: ' . $errstr, [
            'host' => $host,
            'port' => $port,
            'errno' => $errno,
            'to' => $to,
        ]);

        return false;
    }

    stream_set_timeout($fp, 30);

    try {
        if (!abas_smtp_expect(abas_smtp_read($fp), [220])) {
            throw new RuntimeException('Unexpected SMTP greeting');
        }

        $ehloHost = parse_url((string) abas_config()['app_url'], PHP_URL_HOST) ?: 'localhost';
        if (!abas_smtp_cmd($fp, 'EHLO ' . $ehloHost, [250])) {
            throw new RuntimeException('EHLO failed');
        }

        if ($secure === 'tls') {
            if (!abas_smtp_cmd($fp, 'STARTTLS', [220])) {
                throw new RuntimeException('STARTTLS failed');
            }
            $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!stream_socket_enable_crypto($fp, true, $crypto)) {
                throw new RuntimeException('TLS handshake failed');
            }
            if (!abas_smtp_cmd($fp, 'EHLO ' . $ehloHost, [250])) {
                throw new RuntimeException('EHLO after STARTTLS failed');
            }
        }

        if ($user !== '') {
            if (!abas_smtp_cmd($fp, 'AUTH LOGIN', [334], 'AUTH LOGIN')) {
                throw new RuntimeException('AUTH LOGIN failed');
            }
            if (!abas_smtp_cmd($fp, base64_encode($user), [334], 'AUTH user')) {
                throw new RuntimeException('SMTP username rejected');
            }
            if (!abas_smtp_cmd($fp, base64_encode($pass), [235], 'AUTH pass')) {
                throw new RuntimeException('SMTP password rejected');
            }
        }

        if (!abas_smtp_cmd($fp, 'MAIL FROM:<' . $from . '>', [250])) {
            throw new RuntimeException('MAIL FROM failed');
        }
        if (!abas_smtp_cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251])) {
            throw new RuntimeException('RCPT TO failed');
        }
        if (!abas_smtp_cmd($fp, 'DATA', [354])) {
            throw new RuntimeException('DATA failed');
        }

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $message = implode("\r\n", [
            'From: ' . abas_mail_encode_address($fromName, $from),
            'To: ' . $to,
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . date('r'),
            '',
            $bodyHtml,
            '',
        ]);
        fwrite($fp, $message . "\r\n.\r\n");
        if (!abas_smtp_expect(abas_smtp_read($fp), [250])) {
            throw new RuntimeException('Message body rejected');
        }

        abas_smtp_cmd($fp, 'QUIT', [221]);

        return true;
    } catch (Throwable $e) {
        $smtpResponse = (string) ($GLOBALS['_abas_smtp_last_response'] ?? '');
        $detail = $e->getMessage();
        if ($smtpResponse !== '' && !str_contains($detail, $smtpResponse)) {
            $detail .= ' — ' . $smtpResponse;
        }
        abas_mail_report_failure('SMTP send failed: ' . $detail, [
            'host' => $host,
            'port' => $port,
            'to' => $to,
            'from' => $from,
            'subject' => $subject,
        ]);

        return false;
    } finally {
        fclose($fp);
    }
}

function abas_mail_encode_address(string $name, string $email): string
{
    if ($name === '') {
        return $email;
    }

    return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
}

function abas_smtp_read($fp): string
{
    $data = '';
    while (!feof($fp)) {
        $line = fgets($fp, 8192);
        if ($line === false) {
            break;
        }
        $data .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    return $data;
}

/**
 * @param list<int> $expectCodes
 */
function abas_smtp_expect(string $response, array $expectCodes): bool
{
    if ($response === '') {
        return false;
    }
    $code = (int) substr($response, 0, 3);

    return in_array($code, $expectCodes, true);
}

/**
 * @param list<int> $expectCodes
 */
function abas_smtp_cmd($fp, string $command, array $expectCodes, ?string $logLabel = null): bool
{
    fwrite($fp, $command . "\r\n");
    $response = abas_smtp_read($fp);
    $GLOBALS['_abas_smtp_last_response'] = trim($response);
    if (!abas_smtp_expect($response, $expectCodes)) {
        abas_mail_log('SMTP cmd failed: ' . ($logLabel ?? trim($command)) . ' => ' . trim($response));

        return false;
    }

    return true;
}

function abas_mail_password_link(
    string $email,
    string $username,
    string $displayName,
    string $token,
    string $kind,
    string $expiresAt
): bool {
    $url = abas_full_url('set-password.php') . '?token=' . urlencode($token);
    $subject = $kind === 'welcome'
        ? 'Velkommen til ABA Service — vælg adgangskode'
        : 'Nulstil adgangskode — ABA Service';
    $body = abas_mail_password_flow_html($displayName, $username, $url, $kind, $expiresAt);

    return abas_mail_send($email, $subject, $body);
}
