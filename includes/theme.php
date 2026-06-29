<?php

declare(strict_types=1);

function abas_theme_palette(): array
{
    return [
        'primary' => '#91191A',
        'secondary' => '#caa14a',
        'bg' => '#F5F5EF',
        'table_header_bg' => '#d7b777',
        'text' => '#111827',
    ];
}

function abas_loading_panel_html(string $message, string $extraClass = ''): string
{
    $class = trim('abas-loading-panel ' . $extraClass);

    return '<div class="' . htmlspecialchars($class) . '" role="status" aria-live="polite">'
        . '<span class="abas-spinner" aria-hidden="true"></span>'
        . '<span>' . htmlspecialchars($message) . '</span></div>';
}
