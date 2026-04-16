<?php

declare(strict_types=1);

use Dotenv\Dotenv;

define('BASE_PATH', dirname(__DIR__));

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} elseif (!function_exists('env')) {
    require_once BASE_PATH . '/app/helpers/helpers.php';
}

if (class_exists(Dotenv::class) && is_readable(BASE_PATH . '/.env')) {
    try {
        Dotenv::createImmutable(BASE_PATH)->load();
    } catch (\Throwable $e) {
        log_error('Dotenv: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    }
}

require BASE_PATH . '/config/app.php';

set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    log_error($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    echo 'Error interno del servidor.';
});

require BASE_PATH . '/router.php';
