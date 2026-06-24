<?php

declare(strict_types=1);

function abas_app_timezone_name(): string
{
    return (string) abas_env('APP_TIMEZONE', 'Europe/Copenhagen');
}

function abas_app_timezone(): DateTimeZone
{
    static $tz = null;
    if ($tz instanceof DateTimeZone) {
        return $tz;
    }

    try {
        $tz = new DateTimeZone(abas_app_timezone_name());
    } catch (Throwable) {
        $tz = new DateTimeZone('Europe/Copenhagen');
    }

    return $tz;
}

function abas_datetime_bootstrap(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    date_default_timezone_set(abas_app_timezone()->getName());
    $done = true;
}

function abas_mysql_timezone_offset(): string
{
    return (new DateTimeImmutable('now', abas_app_timezone()))->format('P');
}

function abas_format_datetime(?string $value, string $format = 'd/m/Y H:i:s'): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, abas_app_timezone());
    if ($dt === false) {
        $ts = strtotime($value);

        return $ts !== false ? date($format, $ts) : $value;
    }

    return $dt->format($format);
}
