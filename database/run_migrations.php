<?php

declare(strict_types=1);

use Dotenv\Dotenv;

define('BASE_PATH', dirname(__DIR__));
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} elseif (!function_exists('env')) {
    require_once BASE_PATH . '/app/helpers/helpers.php';
}
if (class_exists(Dotenv::class) && file_exists(BASE_PATH . '/.env')) {
    Dotenv::createImmutable(BASE_PATH)->safeLoad();
}
require BASE_PATH . '/config/app.php';

use App\Helpers\Database;

$pdo = Database::connection();
$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        continue;
    }
    $pdo->exec($sql);
    echo 'Ejecutado: ' . basename($file) . PHP_EOL;
}
