<?php

declare(strict_types=1);

function abas_roles(): array
{
    return ['admin', 'vagtcentral', 'montor', 'anlaegsejer', 'anlaegsafprover', 'virksomhedsadmin'];
}

function abas_role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'vagtcentral' => 'Vagtcentral',
        'montor' => 'Montør',
        'anlaegsejer' => 'Anlægsejer',
        'anlaegsafprover' => 'Anlægsafprøver',
        'virksomhedsadmin' => 'Virksomhedsadministrator',
        default => $role,
    };
}

function abas_user_can_access_all_installations(string $role): bool
{
    return in_array($role, ['admin', 'vagtcentral', 'montor'], true);
}

function abas_user_may_view_contact_phones(array $user): bool
{
    return !in_array($user['role'] ?? '', ['montor', 'anlaegsafprover'], true);
}

function abas_user_same_installer(array $actor, array $target): bool
{
    $a = (int) ($actor['installer_id'] ?? 0);
    $t = (int) ($target['installer_id'] ?? 0);

    return $a > 0 && $a === $t;
}

function abas_virksomhedsadmin_may_manage_user(array $actor, array $target): bool
{
    if (($actor['role'] ?? '') !== 'virksomhedsadmin') {
        return false;
    }
    if (!abas_user_same_installer($actor, $target)) {
        return false;
    }
    if (in_array($target['role'] ?? '', ['admin', 'vagtcentral', 'virksomhedsadmin'], true)) {
        return false;
    }

    return true;
}

function abas_require_role(array $allowed): void
{
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('Adgang nægtet.');
    }
}
