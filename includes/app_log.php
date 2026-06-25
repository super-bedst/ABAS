<?php

declare(strict_types=1);

function abas_app_log_path(): string
{
    return abas_root() . '/storage/app/error-last50.log';
}

/**
 * @param array<string, scalar|null> $context
 */
function abas_log_error(string $category, string $message, array $context = []): void
{
    $storage = abas_root() . '/storage/app';
    if (!is_dir($storage)) {
        @mkdir($storage, 0775, true);
    }

    $ctx = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    $line = sprintf('[%s] %s %s%s', date('c'), $category, $message, $ctx);

    $file = abas_app_log_path();
    $lines = [];
    if (is_file($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $lines = array_values(array_filter($lines, static fn (string $row): bool => $row !== ''));
    }
    $lines[] = $line;
    if (count($lines) > 50) {
        $lines = array_slice($lines, -50);
    }

    file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
}

/**
 * @return list<string>
 */
function abas_read_error_log(): array
{
    $file = abas_app_log_path();
    if (!is_file($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
    $lines = array_values(array_filter($lines, static fn (string $row): bool => $row !== ''));

    return array_reverse($lines);
}
