<?php

declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
load_env(__DIR__);

require_once __DIR__ . '/config/database.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'BsoutVendas\\';
    $baseDir = __DIR__ . '/src/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
