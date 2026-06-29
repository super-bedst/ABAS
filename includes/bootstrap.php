<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
abas_load_env(abas_root());
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/error_handler.php';
abas_session_start();
abas_register_error_handlers();
