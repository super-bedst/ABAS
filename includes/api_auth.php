<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/app_log.php';

function abas_api_json(int $status, array $payload): void
{
    if ($status >= 400) {
        $message = (string) ($payload['error'] ?? $payload['message'] ?? 'API-fejl');
        abas_log_error('api', $message, [
            'status' => $status,
            'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'user_id' => (int) ($_SESSION['user_id'] ?? 0),
        ]);
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function abas_api_bearer_token(): ?string
{
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $hdr, $m)) {
        return $m[1];
    }

    return null;
}

function abas_api_authenticate(mysqli $conn): array
{
    $raw = abas_api_bearer_token();
    if (!$raw) {
        abas_api_json(401, ['error' => 'Mangler Bearer token']);
    }
    $hash = hash('sha256', $raw);
    $stmt = $conn->prepare('SELECT * FROM api_tokens WHERE token_hash=? AND active=1 LIMIT 1');
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $token = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$token) {
        abas_api_json(401, ['error' => 'Ugyldigt token']);
    }
    if (!empty($token['allowed_ips'])) {
        $allowed = array_map('trim', explode(',', $token['allowed_ips']));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($ip && !in_array($ip, $allowed, true)) {
            abas_api_json(403, ['error' => 'IP ikke tilladt']);
        }
    }

    return $token;
}

function abas_api_user_from_token(mysqli $conn, array $token): array
{
    return [
        'id' => 0,
        'role' => $token['role'],
        'username' => 'api:' . $token['name'],
        'trekant_userid' => abas_config()['trekant']['user'],
    ];
}
