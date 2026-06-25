<?php

declare(strict_types=1);

function abas_table_normalize_sort_dir(string $dir): string
{
    return strtolower($dir) === 'desc' ? 'desc' : 'asc';
}

function abas_table_sort_toggle(string $column, string $currentSort, string $currentDir): string
{
    return $currentSort === $column && $currentDir === 'asc' ? 'desc' : 'asc';
}

/**
 * @param array<string, scalar|null> $query
 * @param list<string> $allowedColumns
 * @return array{href: string, active: bool, indicator: string}
 */
function abas_table_sort_link(
    string $path,
    array $query,
    string $column,
    string $currentSort,
    string $currentDir,
    array $allowedColumns
): array {
    if (!in_array($column, $allowedColumns, true)) {
        throw new InvalidArgumentException('Unknown sort column: ' . $column);
    }

    $nextDir = abas_table_sort_toggle($column, $currentSort, $currentDir);
    $query['sort'] = $column;
    $query['dir'] = $nextDir;

    return [
        'href' => abas_table_page_url($path, $query),
        'active' => $currentSort === $column,
        'indicator' => $currentSort === $column ? ($currentDir === 'asc' ? '↑' : '↓') : '',
    ];
}

/**
 * @param array<string, scalar|null> $query
 */
function abas_table_page_url(string $path, array $query = []): string
{
    $query = array_filter(
        $query,
        static fn ($value): bool => $value !== null && $value !== ''
    );

    return $query === [] ? abas_url($path) : abas_url($path . '?' . http_build_query($query));
}

/**
 * @param array{href: string, active: bool, indicator: string} $link
 */
function abas_render_table_sort_th(string $label, array $link): void
{
    $class = 'abas-table-sort' . ($link['active'] ? ' abas-table-sort--active' : '');
    echo '<th scope="col"><a href="' . htmlspecialchars($link['href']) . '" class="' . $class . '">';
    echo htmlspecialchars($label);
    if ($link['indicator'] !== '') {
        echo ' <span class="abas-table-sort-indicator" aria-hidden="true">' . $link['indicator'] . '</span>';
    }
    echo '</a></th>';
}

function abas_render_client_table_sort_th(string $label, int $columnIndex, string $type = 'text'): void
{
    $type = in_array($type, ['text', 'number'], true) ? $type : 'text';
    echo '<th scope="col" data-sort-col="' . $columnIndex . '" data-sort-type="' . $type . '">';
    echo '<button type="button" class="abas-table-sort">' . htmlspecialchars($label) . '</button></th>';
}

/**
 * @param list<string> $allowedColumns
 */
function abas_table_resolve_sort(string $sort, array $allowedColumns, string $default = ''): string
{
    return in_array($sort, $allowedColumns, true) ? $sort : $default;
}

/**
 * @param list<array<string, mixed>> $rows
 * @param array<string, callable(array<string, mixed>): string> $valueGetters
 * @return list<array<string, mixed>>
 */
function abas_table_sort_rows(array $rows, string $sort, string $dir, array $valueGetters): array
{
    if ($sort === '' || !isset($valueGetters[$sort])) {
        return $rows;
    }

    $getter = $valueGetters[$sort];
    $mult = abas_table_normalize_sort_dir($dir) === 'desc' ? -1 : 1;
    usort($rows, static function (array $a, array $b) use ($getter, $mult): int {
        $left = mb_strtolower($getter($a));
        $right = mb_strtolower($getter($b));

        return $mult * ($left <=> $right);
    });

    return $rows;
}

/**
 * @param list<array<string, mixed>> $rows
 * @param list<callable(array<string, mixed>): string> $fieldGetters
 * @return list<array<string, mixed>>
 */
function abas_table_filter_rows(array $rows, string $search, array $fieldGetters): array
{
    $search = mb_strtolower(trim($search));
    if ($search === '') {
        return $rows;
    }

    return array_values(array_filter(
        $rows,
        static function (array $row) use ($search, $fieldGetters): bool {
            foreach ($fieldGetters as $getter) {
                if (str_contains(mb_strtolower($getter($row)), $search)) {
                    return true;
                }
            }

            return false;
        }
    ));
}

/**
 * @param list<array<string, mixed>> $installations
 * @return list<array<string, mixed>>
 */
function abas_table_sort_installations(array $installations, string $sort, string $dir): array
{
    return abas_table_sort_rows($installations, $sort, $dir, [
        'miscno2' => static fn (array $row): string => (string) ($row['miscno2'] ?? ''),
        'name' => static fn (array $row): string => (string) ($row['name'] ?? ''),
        'city' => static fn (array $row): string => (string) ($row['city'] ?? ''),
        'service' => static fn (array $row): string => (string) ($row['service_started_at'] ?? $row['mon_stat'] ?? ''),
        'expires' => static fn (array $row): string => (string) ($row['end_at'] ?? $row['service_expires_at'] ?? ''),
        'comment' => static fn (array $row): string => (string) ($row['queue_comment'] ?? ''),
    ]);
}
