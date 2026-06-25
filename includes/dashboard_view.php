<?php

declare(strict_types=1);

require_once __DIR__ . '/installation_sync.php';
require_once __DIR__ . '/service.php';

/**
 * @return array{
 *   installations:list<array<string,mixed>>,
 *   listHeading:string,
 *   showServiceInfo:bool,
 *   showServiceScope:bool,
 *   externalInQueue:list<array<string,mixed>>,
 *   showExternalQueue:bool,
 *   isOwner:bool,
 *   isMontor:bool,
 *   q:string,
 *   includeCompany:bool
 * }
 */
function abas_dashboard_build_state(mysqli $conn, array $user, string $q, string $scope): array
{
    $userId = (int) $user['id'];
    $isOwner = in_array($user['role'] ?? '', ['anlaegsejer', 'anlaegsafprover'], true);
    $isMontor = ($user['role'] ?? '') === 'montor';
    $includeCompany = !$isMontor || $scope !== 'mine';
    $showExternalQueue = in_array($user['role'] ?? '', ['admin', 'vagtcentral'], true);
    $externalInQueue = [];

    if ($showExternalQueue) {
        require_once __DIR__ . '/service_reconcile.php';
        $externalInQueue = abas_external_testqueue_installations($conn);
    }

    $installations = [];
    $listHeading = '';
    $showServiceInfo = false;
    $showServiceScope = false;

    if ($isOwner) {
        $installations = $q === ''
            ? abas_user_linked_installations($conn, $userId)
            : abas_search_installations_local($conn, $q, false, $userId);
        if ($q === '') {
            $listHeading = 'Dine anlæg';
            $installations = abas_flag_installations_in_service($conn, $installations);
        }
    } elseif ($q !== '') {
        $allAccess = abas_user_can_access_all_installations((string) $user['role']);
        $installations = abas_search_installations_local($conn, $q, $allAccess, $userId);
    } else {
        $installations = abas_dashboard_in_service_installations($conn, $user, $includeCompany);
        $showServiceInfo = true;
        $showServiceScope = $isMontor;
        $listHeading = 'Anlæg i service';
    }

    return [
        'installations' => $installations,
        'listHeading' => $listHeading,
        'showServiceInfo' => $showServiceInfo,
        'showServiceScope' => $showServiceScope,
        'externalInQueue' => $externalInQueue,
        'showExternalQueue' => $showExternalQueue,
        'isOwner' => $isOwner,
        'isMontor' => $isMontor,
        'q' => $q,
        'includeCompany' => $includeCompany,
    ];
}

/**
 * @param array<string, mixed> $state
 */
function abas_dashboard_render_external_queue(array $state): string
{
    if (empty($state['showExternalQueue']) || ($state['externalInQueue'] ?? []) === [] || ($state['q'] ?? '') !== '') {
        return '';
    }

    ob_start();
    require __DIR__ . '/../public/partials/dashboard-external-queue.php';

    return (string) ob_get_clean();
}

/**
 * @param array<string, mixed> $state
 */
function abas_dashboard_render_main(array $state): string
{
    ob_start();
    require __DIR__ . '/../public/partials/dashboard-main-content.php';

    return (string) ob_get_clean();
}
