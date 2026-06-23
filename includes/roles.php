<?php

declare(strict_types=1);

function abas_roles(): array
{
    return ['admin', 'vagtcentral', 'montor', 'anlaegsejer'];
}

function abas_role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'vagtcentral' => 'Vagtcentral',
        'montor' => 'Montør',
        'anlaegsejer' => 'Anlægsejer',
        default => $role,
    };
}

function abas_user_can_access_all_installations(string $role): bool
{
    return in_array($role, ['admin', 'vagtcentral', 'montor'], true);
}

function abas_user_may_view_contact_phones(array $user): bool
{
    return ($user['role'] ?? '') !== 'montor';
}

function abas_require_role(array $allowed): void
{
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('Adgang nægtet.');
    }
}
