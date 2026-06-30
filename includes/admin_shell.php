<?php

declare(strict_types=1);

/**
 * @return list<array{path:string, label:string, icon:string}>
 */
function abas_admin_nav_items(): array
{
    return [
        ['path' => 'admin/index.php', 'label' => 'Dashboard', 'icon' => '⌂'],
        ['path' => 'admin/activity-log.php', 'label' => 'Aktivitetslog', 'icon' => '☰'],
        ['path' => 'admin/users.php', 'label' => 'Brugere', 'icon' => '👤'],
        ['path' => 'admin/users.php?filter=montor', 'label' => 'Montører', 'icon' => '🔧'],
        ['path' => 'admin/installation-groups.php', 'label' => 'Anlægsgrupper', 'icon' => '🗂'],
        ['path' => 'admin/installers.php', 'label' => 'Installatører', 'icon' => '🏢'],
        ['path' => 'admin/registration-requests.php', 'label' => 'Ansøgninger', 'icon' => '📋'],
        ['path' => 'admin/sync.php', 'label' => 'Sync', 'icon' => '↻'],
        ['path' => 'admin/settings.php', 'label' => 'Indstillinger', 'icon' => '⚙'],
        ['path' => 'admin/mfa-whitelist.php', 'label' => 'MFA whitelist', 'icon' => '🔐'],
        ['path' => 'admin/api-tokens.php', 'label' => 'API-tokens', 'icon' => '🔗'],
        ['path' => 'admin/endpoints.php', 'label' => 'API-endpoints', 'icon' => '⇄'],
        ['path' => 'admin/sms-inbound-log.php', 'label' => 'SMS log', 'icon' => '💬'],
        ['path' => 'admin/error-log.php', 'label' => 'Fejllog', 'icon' => '⚠'],
    ];
}

function abas_admin_nav_active_basename(?string $section = null): string
{
    if ($section !== null && $section !== '') {
        return basename($section);
    }

    $base = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    /** @var array<string, string> */
    $parentSections = [
        'user-edit.php' => 'users.php',
        'installer-edit.php' => 'installers.php',
        'installation-group-edit.php' => 'installation-groups.php',
    ];

    return $parentSections[$base] ?? $base;
}

/**
 * @return list<array{href:string, label:string, icon:string, active:bool}>
 */
function abas_admin_nav_render_items(?string $section = null): array
{
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptBasename = basename($script);

    return array_map(
        static function (array $item) use ($section, $script, $scriptBasename): array {
            $itemPath = $item['path'];
            $active = false;

            if ($section !== null && $section !== '') {
                $active = $itemPath === $section || basename($itemPath) === basename($section);
            } elseif (str_contains($itemPath, '?')) {
                [$path, $queryString] = explode('?', $itemPath, 2);
                if (str_ends_with($script, basename($path))) {
                    parse_str($queryString, $expectedQuery);
                    $active = true;
                    foreach ($expectedQuery as $key => $value) {
                        if ((string) ($_GET[$key] ?? '') !== (string) $value) {
                            $active = false;
                            break;
                        }
                    }
                }
            } elseif (basename($itemPath) === 'users.php' && $scriptBasename === 'users.php') {
                $active = (string) ($_GET['filter'] ?? 'alle') !== 'montor';
            } else {
                $activeBasename = abas_admin_nav_active_basename(null);
                $active = basename($itemPath) === $activeBasename;
            }

            return [
                'href' => abas_url($itemPath),
                'label' => $item['label'],
                'icon' => $item['icon'],
                'active' => $active,
            ];
        },
        abas_admin_nav_items()
    );
}
