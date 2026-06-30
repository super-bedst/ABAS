<?php

declare(strict_types=1);

require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/installation_groups.php';

/**
 * @return array<string, mixed>
 */
function abas_user_guide_context(mysqli $conn, array $user): array
{
    $role = (string) ($user['role'] ?? '');
    $smsRole = abas_user_role_uses_sms_code($role);
    $smsAllowed = $smsRole && abas_user_sms_service_allowed($user);
    $canService = in_array($role, ['montor', 'anlaegsejer', 'anlaegsafprover', 'vagtcentral', 'admin'], true);
    $isOwner = in_array($role, ['anlaegsejer', 'anlaegsafprover'], true);
    $fullInstallAccess = abas_user_has_full_installation_access($user);
    $scopedAccess = abas_user_uses_scoped_installation_access($user);

    return [
        'role' => $role,
        'role_label' => abas_role_label($role),
        'show_dashboard' => $role !== 'virksomhedsadmin',
        'show_dashboard_owner' => $isOwner,
        'show_dashboard_montor_in_service' => $role === 'montor',
        'show_dashboard_montor_scope' => $role === 'montor' && $fullInstallAccess,
        'show_dashboard_montor_scoped' => $role === 'montor' && $scopedAccess,
        'show_dashboard_search' => in_array($role, ['montor', 'vagtcentral', 'admin'], true) || $isOwner,
        'show_installation_page' => $canService || $isOwner,
        'show_service' => $canService,
        'show_responsibility_ack' => $canService,
        'show_sms' => $smsAllowed,
        'show_contacts_full' => abas_user_may_view_contact_phones($user),
        'show_zones' => $canService || $isOwner,
        'show_anlaegsbrugere' => $role === 'anlaegsejer',
        'show_vc_service' => in_array($role, ['vagtcentral', 'admin'], true),
        'show_vc_anlaegsbrugere' => in_array($role, ['vagtcentral', 'admin'], true),
        'show_virksomhed_users' => $role === 'virksomhedsadmin',
        'show_admin' => $role === 'admin',
        'show_profile' => true,
        'show_external_queue' => in_array($role, ['vagtcentral', 'admin'], true),
    ];
}

/**
 * @param array<string, mixed> $ctx
 * @return list<array{id:string, title:string}>
 */
function abas_user_guide_toc(array $ctx): array
{
    $items = [
        ['id' => 'intro', 'title' => 'Om vejledningen'],
        ['id' => 'navigation', 'title' => 'Menu og navigation'],
    ];

    if ($ctx['show_dashboard']) {
        $items[] = ['id' => 'dashboard', 'title' => 'Dashboard'];
    }
    if ($ctx['show_installation_page']) {
        $items[] = ['id' => 'installation', 'title' => 'Anlægssiden'];
    }
    if ($ctx['show_service']) {
        $items[] = ['id' => 'service', 'title' => 'Start og stop service'];
    }
    if ($ctx['show_responsibility_ack']) {
        $items[] = ['id' => 'responsibility', 'title' => 'Ansvarserklæring'];
    }
    if ($ctx['show_zones']) {
        $items[] = ['id' => 'zones', 'title' => 'Zonestatus'];
    }
    if ($ctx['show_sms']) {
        $items[] = ['id' => 'sms', 'title' => 'SMS-betjening'];
    }
    if ($ctx['show_anlaegsbrugere']) {
        $items[] = ['id' => 'anlaegsbrugere', 'title' => 'Anlægsbrugere'];
    }
    if ($ctx['show_vc_service']) {
        $items[] = ['id' => 'vc-service', 'title' => 'VC service'];
    }
    if ($ctx['show_vc_anlaegsbrugere']) {
        $items[] = ['id' => 'vc-anlaegsbrugere', 'title' => 'VC anlægsbrugere'];
    }
    if ($ctx['show_virksomhed_users']) {
        $items[] = ['id' => 'virksomhed', 'title' => 'Virksomhedsbrugere'];
    }
    if ($ctx['show_admin']) {
        $items[] = ['id' => 'admin', 'title' => 'Administration'];
    }
    if ($ctx['show_profile']) {
        $items[] = ['id' => 'profile', 'title' => 'Min konto'];
    }

    return $items;
}

function abas_user_guide_render(mysqli $conn, array $user): string
{
    $ctx = abas_user_guide_context($conn, $user);
    $toc = abas_user_guide_toc($ctx);

    ob_start();
    require __DIR__ . '/../public/partials/user-guide.php';

    return (string) ob_get_clean();
}
