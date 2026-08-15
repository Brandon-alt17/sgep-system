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
        fwrite(STDERR, 'No se pudo cargar .env: ' . $e->getMessage() . PHP_EOL);
    }
}
require BASE_PATH . '/config/app.php';

use App\Helpers\Database;

$seedFile = BASE_PATH . '/database/seeds/aprendices_sample.sql';
if (!is_file($seedFile)) {
    exit(0);
}

$pdo = Database::connection();

// Solo carga el aprendiz de ejemplo en una base de datos realmente vacía (primera instalación).
// Instalar.bat se ejecuta también en cada "actualización" (se reemplaza la carpeta del proyecto
// y se corre el instalador de nuevo), así que sin esta comprobación el registro demo reaparecería
// en cada actualización aunque el instructor ya lo hubiera borrado.
$count = (int) $pdo->query('SELECT COUNT(*) FROM aprendices')->fetchColumn();
if ($count > 0) {
    echo 'Ya hay aprendices registrados; se omite el aprendiz de ejemplo.' . PHP_EOL;
    exit(0);
}

$sql = file_get_contents($seedFile);
if ($sql === false) {
    exit(0);
}

$statements = array_filter(array_map('trim', explode(';', $sql)), static fn (string $s): bool => $s !== '');
foreach ($statements as $statement) {
    $pdo->exec($statement);
}

echo 'Aprendiz de ejemplo cargado (base de datos vacia detectada).' . PHP_EOL;
