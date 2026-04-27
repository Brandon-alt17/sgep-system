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
        // safeLoad() solo oculta "archivo no encontrado"; un .env mal formado igual lanza excepción
        Dotenv::createImmutable(BASE_PATH)->load();
    } catch (\Throwable $e) {
        fwrite(STDERR, 'No se pudo cargar .env: ' . $e->getMessage() . PHP_EOL);
        fwrite(STDERR, 'Se usarán los valores por defecto de config/database.php si aplican.' . PHP_EOL);
    }   
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
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    /** @var array<int, string> $parts */
    $parts = array_filter(array_map('trim', explode(';', $sql)), static fn (string $s): bool => $s !== '');
    foreach ($parts as $stmt) {
        try {
            $statement = $pdo->prepare($stmt);
            if ($statement === false) {
                throw new \PDOException('No se pudo preparar sentencia SQL de migración.');
            }
            $statement->execute();
            // Evita "Cannot execute queries while other unbuffered queries are active".
            $statement->closeCursor();
        } catch (\PDOException $e) {
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            // MySQL/MariaDB: 1060 = columna duplicada, 1061 = índice duplicado
            if ($driverCode === 1060 || $driverCode === 1061) {
                continue;
            }
            throw $e;
        }
    }
    echo 'Ejecutado: ' . basename($file) . PHP_EOL;
}
