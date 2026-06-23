<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

function abas_password_issue_token(mysqli $conn, int $userId, string $kind): string
{
    if (!in_array($kind, ['welcome', 'reset', 'vc_invite'], true)) {
        throw new InvalidArgumentException('Invalid token kind');
    }
    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $hours = $kind === 'welcome'
        ? (int) (abas_setting($conn, 'welcome_token_ttl_hours', '72') ?? 72)
        : (int) (abas_setting($conn, 'password_reset_ttl_hours', '24') ?? 24);
    $expires = date('Y-m-d H:i:s', time() + $hours * 3600);
    $stmt = $conn->prepare('INSERT INTO password_flow_tokens (user_id, token_hash, kind, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $userId, $hash, $kind, $expires);
    $stmt->execute();
    $stmt->close();

    return $raw;
}

function abas_password_validate_token(mysqli $conn, string $rawToken): ?array
{
    $hash = hash('sha256', $rawToken);
    $stmt = $conn->prepare(
        'SELECT t.*, u.email, u.username, u.id AS user_id FROM password_flow_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function abas_password_consume_token(mysqli $conn, string $rawToken): void
{
    $hash = hash('sha256', $rawToken);
    $stmt = $conn->prepare('UPDATE password_flow_tokens SET used_at = NOW() WHERE token_hash = ?');
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $stmt->close();
}

function abas_password_send_flow_email(mysqli $conn, int $userId, string $kind): void
{
    $stmt = $conn->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) {
        return;
    }
    $token = abas_password_issue_token($conn, $userId, $kind);
    abas_mail_password_link($userId, $user['email'], $token, $kind === 'vc_invite' ? 'welcome' : $kind);
}

function abas_access_confirm_months(mysqli $conn): int
{
    return max(1, (int) (abas_setting($conn, 'access_confirm_months', '3') ?? 3));
}

function abas_access_set_due(mysqli $conn, int $userId): void
{
    $months = abas_access_confirm_months($conn);
    $stmt = $conn->prepare('UPDATE users SET access_confirmed_at = NOW(), access_confirm_due_at = DATE_ADD(NOW(), INTERVAL ? MONTH) WHERE id = ?');
    $stmt->bind_param('ii', $months, $userId);
    $stmt->execute();
    $stmt->close();
}

function abas_access_needs_confirm(?array $user): bool
{
    if (!$user || empty($user['access_confirm_due_at'])) {
        return false;
    }

    return strtotime((string) $user['access_confirm_due_at']) <= time();
}
