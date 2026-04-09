<?php

declare(strict_types=1);

use Dotenv\Dotenv;

define('BASE_PATH', __DIR__);

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

if (class_exists(Dotenv::class) && file_exists(BASE_PATH . '/.env')) {
    Dotenv::createImmutable(BASE_PATH)->safeLoad();
}

require BASE_PATH . '/config/app.php';
require BASE_PATH . '/app/helpers/helpers.php';

set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    log_error($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    echo 'Error interno del servidor.';
});

require BASE_PATH . '/router.php';
