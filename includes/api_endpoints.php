<?php

declare(strict_types=1);

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/cron_auth.php';
require_once __DIR__ . '/config.php';

/**
 * @return list<array{
 *   methods: list<string>,
 *   path: string,
 *   title: string,
 *   description: string,
 *   auth: string,
 *   auth_detail?: string,
 *   env_keys?: list<string>,
 *   example_query?: array<string, string>,
 *   example_body?: string,
 *   legacy?: bool
 * }>
 */
function abas_api_endpoint_registry(): array
{
    return [
        [
            'methods' => ['GET'],
            'path' => 'health',
            'title' => 'Health check',
            'description' => 'Tjek at API\'et svarer.',
            'auth' => 'none',
        ],
        [
            'methods' => ['GET'],
            'path' => 'installations/search',
            'title' => 'Søg anlæg',
            'description' => 'Søg i lokal anlægscache på anlægsnummer eller navn.',
            'auth' => 'api_token',
            'example_query' => ['q' => 'fab'],
        ],
        [
            'methods' => ['POST'],
            'path' => 'installations/{miscno2}/service',
            'title' => 'Start/stop service',
            'description' => 'Start eller stop service på anlæg. JSON-body med action, hours og comment.',
            'auth' => 'api_token',
            'example_body' => '{"action":"start","hours":2,"comment":"API"}',
        ],
        [
            'methods' => ['GET'],
            'path' => 'installations/{miscno2}/log',
            'title' => 'Alarmlog',
            'description' => 'Hent alarmlog for anlæg (mode: last20 eller 24h).',
            'auth' => 'api_token',
            'example_query' => ['mode' => 'last20'],
        ],
        [
            'methods' => ['GET', 'POST'],
            'path' => 'cron/sync-installations',
            'title' => 'Anlægssynk (cron)',
            'description' => 'Synkroniser anlæg fra TrekantBrand. Bruges typisk fra Node-RED eller planlagt job.',
            'auth' => 'cron_secret',
            'env_keys' => ['SYNC_CRON_SECRET'],
            'example_query' => ['key' => '<SYNC_CRON_SECRET>'],
        ],
        [
            'methods' => ['GET', 'POST'],
            'path' => 'cron/reconcile-service',
            'title' => 'Service-reconcile (cron)',
            'description' => 'Afstem aktive services mod Trekant testkø. Lukker sessions der er stoppet eksternt.',
            'auth' => 'cron_secret',
            'env_keys' => ['SERVICE_RECONCILE_CRON_SECRET', 'SYNC_CRON_SECRET'],
            'auth_detail' => 'Bruger SERVICE_RECONCILE_CRON_SECRET hvis sat, ellers SYNC_CRON_SECRET.',
            'example_query' => ['key' => '<secret>'],
        ],
        [
            'methods' => ['GET', 'POST'],
            'path' => 'cron/sms-expiry-warnings',
            'title' => 'Service-udløbs-SMS (cron)',
            'description' => 'Send 15-minutters SMS-advarsel for aktive services der snart udløber.',
            'auth' => 'cron_secret',
            'env_keys' => ['SERVICE_RECONCILE_CRON_SECRET', 'SYNC_CRON_SECRET'],
            'auth_detail' => 'Samme cron-nøgle som service-reconcile.',
            'example_query' => ['key' => '<secret>'],
        ],
        [
            'methods' => ['POST'],
            'path' => 'sms/inbound',
            'title' => 'SMS inbound webhook',
            'description' => 'Modtag SMS fra BAS/Inmobile-gateway. JSON med from og body.',
            'auth' => 'sms_secret',
            'env_keys' => ['SMS_INBOUND_SECRET'],
            'auth_detail' => 'Kun påkrævet hvis SMS_INBOUND_SECRET er sat i env.local.',
            'example_body' => '{"from":"+4512345678","body":"STATUS FAB0100"}',
        ],
    ];
}

/**
 * @return list<array{
 *   methods: list<string>,
 *   path: string,
 *   title: string,
 *   description: string,
 *   auth: string,
 *   env_keys?: list<string>,
 *   example_query?: array<string, string>
 * }>
 */
function abas_api_legacy_endpoint_registry(): array
{
    return [
        [
            'methods' => ['GET', 'POST'],
            'path' => 'cron/sync_installations.php',
            'title' => 'Anlægssynk (legacy)',
            'description' => 'Samme som /api/v1/cron/sync-installations. CLI uden nøgle virker stadig.',
            'auth' => 'cron_secret',
            'env_keys' => ['SYNC_CRON_SECRET'],
            'example_query' => ['key' => '<SYNC_CRON_SECRET>'],
        ],
        [
            'methods' => ['GET', 'POST'],
            'path' => 'cron/reconcile_service.php',
            'title' => 'Service-reconcile (legacy)',
            'description' => 'Samme som /api/v1/cron/reconcile-service.',
            'auth' => 'cron_secret',
            'env_keys' => ['SERVICE_RECONCILE_CRON_SECRET', 'SYNC_CRON_SECRET'],
            'example_query' => ['key' => '<secret>'],
        ],
        [
            'methods' => ['GET', 'POST'],
            'path' => 'cron/sms_expiry.php',
            'title' => 'Service-udløbs-SMS (legacy)',
            'description' => 'Samme som /api/v1/cron/sms-expiry-warnings.',
            'auth' => 'cron_secret',
            'env_keys' => ['SERVICE_RECONCILE_CRON_SECRET', 'SYNC_CRON_SECRET'],
            'example_query' => ['key' => '<secret>'],
        ],
    ];
}

function abas_api_endpoint_url(string $path, array $query = [], bool $legacy = false): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = $legacy ? abas_app_root_url($path) : abas_full_url('api/v1/' . $path);
    if ($query === []) {
        return $base;
    }

    return $base . '?' . http_build_query($query);
}

function abas_api_auth_label(string $auth): string
{
    return match ($auth) {
        'none' => 'Ingen',
        'api_token' => 'API-token (Bearer)',
        'cron_secret' => 'Cron-nøgle (env)',
        'sms_secret' => 'SMS webhook-nøgle (env)',
        default => $auth,
    };
}

/** @param list<string> $envKeys */
function abas_api_env_configured(array $envKeys): bool
{
    return abas_cron_resolve_secret($envKeys) !== '';
}

function abas_api_method_badge_class(string $method): string
{
    return match (strtoupper($method)) {
        'GET' => 'bg-emerald-100 text-emerald-800',
        'POST' => 'bg-blue-100 text-blue-800',
        'PUT', 'PATCH' => 'bg-amber-100 text-amber-800',
        'DELETE' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800',
    };
}
