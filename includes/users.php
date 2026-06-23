<?php

declare(strict_types=1);

require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/auth.php';

function abas_normalize_phone(string $phone): string
{
    return (string) preg_replace('/\s+/', '', trim($phone));
}

function abas_validate_phone(string $phone): bool
{
    $normalized = abas_normalize_phone($phone);

    return (bool) preg_match('/^\+?\d{8,}$/', $normalized);
}

function abas_installer_id_for_email(mysqli $conn, string $email): ?int
{
    $installer = abas_installer_approved_for_domain($conn, abas_email_domain($email));
    if (!$installer) {
        return null;
    }

    return (int) $installer['id'];
}

function abas_user_company_name(mysqli $conn, array $user): string
{
    $installerId = (int) ($user['installer_id'] ?? 0);
    if ($installerId <= 0) {
        return '';
    }
    $stmt = $conn->prepare('SELECT company_name FROM approved_installers WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $installerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return trim((string) ($row['company_name'] ?? ''));
}

function abas_assign_installer_for_montor(mysqli $conn, string $email): ?int
{
    return abas_installer_id_for_email($conn, $email);
}

function abas_enrich_service_start_comment(mysqli $conn, array $user, string $comment): string
{
    if ($comment === '' || ($user['role'] ?? '') === 'vagtcentral') {
        return $comment;
    }

    $meta = [
        (string) ($user['username'] ?? ''),
        trim((string) ($user['phone'] ?? '')),
        abas_role_label((string) ($user['role'] ?? '')),
    ];
    if (($user['role'] ?? '') === 'montor') {
        $company = abas_user_company_name($conn, $user);
        if ($company !== '') {
            $meta[] = $company;
        }
    }
    $meta = array_values(array_filter($meta, static fn (string $part): bool => $part !== ''));
    if ($meta === []) {
        return $comment;
    }

    $enriched = $comment . ' — ' . implode(', ', $meta);

    return function_exists('mb_substr')
        ? (string) mb_substr($enriched, 0, 255)
        : substr($enriched, 0, 255);
}
