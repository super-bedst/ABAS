<?php

declare(strict_types=1);

require_once __DIR__ . '/app_log.php';

function abas_render_error_page(int $status = 500, ?string $hint = null): never
{
    if (PHP_SAPI !== 'cli') {
        http_response_code($status);
    }

    $hint = $hint ?? 'Der opstod en uventet fejl. Prøv igen om lidt, eller kontakt TrekantBrand hvis problemet fortsætter.';
    $portalTitle = 'Fejl';
    $isPublic = true;

    require dirname(__DIR__) . '/public/partials/error-page.php';
    exit;
}

function abas_register_error_handlers(): void
{
    set_exception_handler(static function (Throwable $e): void {
        abas_log_error('uncaught', $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'type' => $e::class,
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
