<?php

declare(strict_types=1);

require_once __DIR__ . '/app_log.php';

/**
 * @return array<string, scalar|null>
 */
function abas_http_error_log_context(array $context = []): array
{
    return array_merge([
        'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
        'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
        'user_id' => (int) ($_SESSION['user_id'] ?? 0),
        'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ], $context);
}

function abas_wants_json_response(): bool
{
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    if (str_contains($accept, 'application/json')) {
        return true;
    }

    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function abas_render_error_page(int $status = 500, ?string $hint = null): never
{
    if (PHP_SAPI !== 'cli') {
        http_response_code($status);
    }

    $hint = $hint ?? 'Der opstod en uventet fejl. Prøv igen om lidt, eller kontakt TrekantBrand hvis problemet fortsætter.';
    $portalTitle = 'Fejl';
    $errorStatus = $status;
    $errorTitle = match ($status) {
        403 => 'Adgang nægtet',
        404 => 'Ikke fundet',
        400 => 'Ugyldig forespørgsel',
        default => 'Noget gik galt',
    };
    $isPublic = true;

    require dirname(__DIR__) . '/public/partials/error-page.php';
    exit;
}

/**
 * @param array<string, scalar|null> $context
 */
function abas_http_error(int $status, string $message, string $logCategory = 'http_error', array $context = []): never
{
    abas_log_error($logCategory, $message, abas_http_error_log_context(array_merge(['status' => $status], $context)));

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    if (abas_wants_json_response()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    abas_render_error_page($status, $message);
}

/**
 * @param array<string, scalar|null> $context
 */
function abas_json_error(int $status, string $message, string $logCategory = 'http_error', array $context = []): never
{
    abas_log_error($logCategory, $message, abas_http_error_log_context(array_merge(['status' => $status], $context)));

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * @param array<string, scalar|null> $context
 */
function abas_forbidden(string $message = 'Du har ikke adgang til denne side.', array $context = []): never
{
    abas_http_error(403, $message, 'access_denied', $context);
}

/**
 * @param array<string, scalar|null> $context
 */
function abas_not_found(string $message = 'Den ønskede side eller ressource findes ikke.', array $context = []): never
{
    abas_http_error(404, $message, 'not_found', $context);
}

function abas_register_error_handlers(): void
{
    set_exception_handler(static function (Throwable $e): void {
        abas_log_error('uncaught', $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'type' => $e::class,
            'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'user_id' => (int) ($_SESSION['user_id'] ?? 0),
        ]);

        if (PHP_SAPI === 'cli') {
            throw $e;
        }

        abas_render_error_page(500);
    });

    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    });
}
