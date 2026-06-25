<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sms.php';

function abas_client_ip(): string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (empty($_SERVER[$header])) {
            continue;
        }
        $value = (string) $_SERVER[$header];
        if ($header === 'HTTP_X_FORWARDED_FOR') {
            $value = trim(explode(',', $value)[0]);
        }
        if (filter_var($value, FILTER_VALIDATE_IP)) {
            return $value;
        }
    }

    return '0.0.0.0';
}

function abas_mfa_ip_whitelisted(mysqli $conn, ?string $ip = null): bool
{
    $ip = $ip ?? abas_client_ip();
    $result = $conn->query('SELECT ip_cidr FROM mfa_ip_whitelist WHERE active = 1');
    if (!$result) {
        return false;
    }
    while ($row = $result->fetch_assoc()) {
        $cidr = trim((string) $row['ip_cidr']);
        if ($cidr === $ip) {
            return true;
        }
        if (str_contains($cidr, '/') && abas_ip_in_cidr($ip, $cidr)) {
            return true;
        }
    }

    return false;
}

function abas_ip_in_cidr(string $ip, string $cidr): bool
{
    if (!str_contains($cidr, '/')) {
        return $ip === $cidr;
    }
    [$subnet, $mask] = explode('/', $cidr, 2);
    if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($subnet, FILTER_VALIDATE_IP)) {
        return false;
    }
    $mask = (int) $mask;
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false) {
        return false;
    }
    $maskLong = -1 << (32 - $mask);

    return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
}

function abas_mfa_required_globally(mysqli $conn): bool
{
    $stmt = $conn->prepare('SELECT value FROM system_settings WHERE `key` = "mfa_required" LIMIT 1');
    if (!$stmt) {
        return true;
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ($row['value'] ?? '1') === '1';
}

function abas_user_mfa_method(mysqli $conn, int $userId): string
{
    $stmt = $conn->prepare('SELECT method FROM user_mfa WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (string) ($row['method'] ?? 'passkey');
}

function abas_user_mfa_enrolled(mysqli $conn, int $userId): bool
{
    $method = abas_user_mfa_method($conn, $userId);
    if ($method === 'sms_otp') {
        return true;
    }
    $stmt = $conn->prepare('SELECT 1 FROM webauthn_credentials WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    return $ok;
}

function abas_mfa_needs_step(mysqli $conn, array $user): bool
{
    if (!abas_mfa_required_globally($conn)) {
        return false;
    }
    if (abas_mfa_ip_whitelisted($conn)) {
        return false;
    }
    if (!empty($_SESSION['mfa_verified'])) {
        return false;
    }

    return true;
}

function abas_mfa_send_otp(mysqli $conn, array $user): bool
{
    $phone = trim((string) ($user['phone'] ?? ''));
    if ($phone === '') {
        return false;
    }
    $code = (string) random_int(100000, 999999);
    $hash = hash('sha256', $code);
    $expires = date('Y-m-d H:i:s', time() + 300);
    $uid = (int) $user['id'];
    $stmt = $conn->prepare('INSERT INTO mfa_otp_challenges (user_id, code_hash, expires_at) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $uid, $hash, $expires);
    $stmt->execute();
    $stmt->close();

    abas_sms_queue($conn, $phone, 'ABA: Din login-kode er ' . $code . ' (gyldig 5 min).', 'mfa_otp');

    return true;
}

function abas_mfa_verify_otp(mysqli $conn, int $userId, string $code): bool
{
    $hash = hash('sha256', trim($code));
    $stmt = $conn->prepare(
        'SELECT id FROM mfa_otp_challenges WHERE user_id = ? AND code_hash = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1'
    );
    $stmt->bind_param('is', $userId, $hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return false;
    }
    $id = (int) $row['id'];
    $upd = $conn->prepare('UPDATE mfa_otp_challenges SET used_at = NOW() WHERE id = ?');
    $upd->bind_param('i', $id);
    $upd->execute();
    $upd->close();

    return true;
}

function abas_mfa_set_method(mysqli $conn, int $userId, string $method): void
{
    if (!in_array($method, ['passkey', 'sms_otp'], true)) {
        return;
    }
    $stmt = $conn->prepare(
        'INSERT INTO user_mfa (user_id, method, enrolled_at) VALUES (?, ?, NULL)
         ON DUPLICATE KEY UPDATE method = VALUES(method)'
    );
    $stmt->bind_param('is', $userId, $method);
    $stmt->execute();
    $stmt->close();
}

function abas_mfa_reset_user(mysqli $conn, int $userId): void
{
    $del1 = $conn->prepare('DELETE FROM webauthn_credentials WHERE user_id = ?');
    $del1->bind_param('i', $userId);
    $del1->execute();
    $del1->close();
    $del2 = $conn->prepare('DELETE FROM user_mfa WHERE user_id = ?');
    $del2->bind_param('i', $userId);
    $del2->execute();
    $del2->close();
}

function abas_mfa_mark_enrolled(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare('UPDATE user_mfa SET enrolled_at = NOW() WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $ins = $conn->prepare('INSERT INTO user_mfa (user_id, method, enrolled_at) VALUES (?, "passkey", NOW())');
        $ins->bind_param('i', $userId);
        $ins->execute();
        $ins->close();
    } else {
        $stmt->close();
    }
}

function abas_mfa_store_credential(mysqli $conn, int $userId, string $credentialIdB64, string $publicKeyJson, string $label = ''): void
{
    $credId = base64_decode(strtr($credentialIdB64, '-_', '+/'), true) ?: $credentialIdB64;
    $stmt = $conn->prepare(
        'INSERT INTO webauthn_credentials (user_id, credential_id, public_key, label) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isss', $userId, $credId, $publicKeyJson, $label);
    $stmt->execute();
    $stmt->close();
    abas_mfa_mark_enrolled($conn, $userId);
}

function abas_mfa_complete_verification(): void
{
    $_SESSION['mfa_verified'] = true;

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId > 0) {
        require_once __DIR__ . '/users.php';
        abas_record_user_login(abas_db(), $userId);
    }
}

function abas_mfa_clear_verification(): void
{
    unset($_SESSION['mfa_verified'], $_SESSION['mfa_pending_user_id']);
}

function abas_mfa_pending_user(mysqli $conn): ?array
{
    $pendingId = (int) ($_SESSION['mfa_pending_user_id'] ?? 0);
    if ($pendingId > 0) {
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND active = 1 AND registration_status = "approved" LIMIT 1');
        $stmt->bind_param('i', $pendingId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    if (!empty($_SESSION['user_id']) && empty($_SESSION['mfa_verified'])) {
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND active = 1 AND registration_status = "approved" LIMIT 1');
        $userId = (int) $_SESSION['user_id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    return null;
}

function abas_mfa_redirect_for_user(mysqli $conn, array $user): void
{
    $userId = (int) $user['id'];
    $_SESSION['mfa_pending_user_id'] = $userId;
    unset($_SESSION['mfa_verified'], $_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_name']);

    $method = abas_user_mfa_method($conn, $userId);

    if ($method === 'sms_otp') {
        abas_mfa_send_otp($conn, $user);
        abas_redirect('mfa-verify.php');
    }

    if (!abas_user_mfa_enrolled($conn, $userId)) {
        abas_redirect('mfa-enroll.php');
    }

    abas_redirect('mfa-verify.php');
}

function abas_mfa_webauthn_allow_credentials(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare('SELECT credential_id FROM webauthn_credentials WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $credentials = [];
    while ($row = $result->fetch_assoc()) {
        $credentials[] = [
            'type' => 'public-key',
            'id' => rtrim(strtr(base64_encode((string) $row['credential_id']), '+/', '-_'), '='),
        ];
    }
    $stmt->close();

    return $credentials;
}

function abas_require_mfa_enrollment(): array
{
    $conn = abas_db();
    $user = abas_mfa_pending_user($conn);
    if (!$user) {
        abas_redirect('login.php');
        exit;
    }
    if (abas_user_mfa_enrolled($conn, (int) $user['id'])) {
        abas_mfa_redirect_for_user($conn, $user);
        exit;
    }

    return $user;
}

function abas_mfa_finish_enrollment(mysqli $conn, array $user): void
{
    abas_login_user($user);
    abas_mfa_complete_verification();
    unset($_SESSION['mfa_pending_user_id']);
}
