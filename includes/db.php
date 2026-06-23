<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function abas_db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }
    $cfg = abas_config()['db'];
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port']);
    if ($conn->connect_errno) {
        throw new RuntimeException('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    return $conn;
}
