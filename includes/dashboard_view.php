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
        require_once __DIR__ . '/roles.php';
        require_once __DIR__ . '/installation_groups.php';
        if (abas_user_has_full_installation_access($user)) {
            $installations = abas_search_installations_local($conn, $q, true, $userId);
        } elseif (abas_user_uses_scoped_installation_access($user)) {
            $installations = abas_search_user_accessible_installations_local($conn, $userId, $q);
            if ($installations === []) {
                $installations = abas_dashboard_denied_installation_search_hits($conn, $user, $q);
            }
        } else {
            $installations = [];
        }
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

/**
 * @param array<string, mixed> $installation
 * @return array<string, mixed>
 */
function abas_installation_mark_access_denied(array $installation): array
{
    $installation['access_denied'] = true;
    $installation['in_service'] = false;

    return $installation;
}

/**
 * Ved scoped søgning: vis anlæg fundet i cache/API uden adgang, så brugeren kan se korrekt ABA-nr.
 *
 * @return list<array<string, mixed>>
 */
function abas_dashboard_denied_installation_search_hits(mysqli $conn, array $user, string $q): array
{
    require_once __DIR__ . '/roles.php';
    require_once __DIR__ . '/installation_groups.php';
    require_once __DIR__ . '/auth.php';

    if ($q === '' || !abas_user_uses_scoped_installation_access($user) || !abas_is_miscno2_query($q)) {
        return [];
    }

    /** @var list<array<string, mixed>> $candidates */
    $candidates = [];
    $seenIds = [];

    $local = abas_find_installation_by_miscno2($conn, $q);
    if ($local) {
        $localId = (int) ($local['id'] ?? 0);
        if ($localId > 0) {
            $candidates[] = $local;
            $seenIds[$localId] = true;
        }
    }

    try {
        foreach (abas_search_installations_from_api($conn, $user, $q) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || isset($seenIds[$id])) {
                continue;
            }
            $seenIds[$id] = true;
            $candidates[] = $row;
        }
    } catch (Throwable) {
        // Vis lokalt fundet anlæg uden adgang selv om API fejler.
    }

    $denied = [];
    foreach ($candidates as $row) {
        if (!abas_user_may_access_installation($conn, $user, $row)) {
            $denied[] = abas_installation_mark_access_denied($row);
        }
    }

    return $denied;
}
