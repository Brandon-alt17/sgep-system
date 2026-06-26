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
            // MySQL/MariaDB: 1060 = columna duplicada, 1061 = índice duplicado,
            // 1054 = columna inexistente (migraciones idempotentes tras DROP),
            // 1091 = no se puede DROP columna que ya no existe,
            // 1826 = FK duplicada / ya existe (re-ejecución).
            if (in_array($driverCode, [1060, 1061, 1054, 1091, 1826], true)) {
                continue;
            }
            throw $e;
        }
    }
    ensureInnoDbTables($pdo);
    echo 'Ejecutado: ' . basename($file) . PHP_EOL;
}

/**
 * WAMP puede crear tablas en MyISAM; las FK de migraciones 021+ requieren InnoDB.
 * Se ejecuta tras cada archivo de migración para convertir tablas recién creadas.
 */
function ensureInnoDbTables(\PDO $pdo): void
{
    $stmt = $pdo->query(
        "SELECT TABLE_NAME
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_TYPE = 'BASE TABLE'
           AND ENGINE = 'MyISAM'"
    );
    if ($stmt === false) {
        return;
    }

    $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    $stmt->closeCursor();

    foreach ($tables as $table) {
        if (!is_string($table) || $table === '') {
            continue;
        }
        $safeTable = str_replace('`', '``', $table);
        $pdo->exec("ALTER TABLE `{$safeTable}` ENGINE=InnoDB");
        echo 'Convertido a InnoDB: ' . $table . PHP_EOL;
    }
}
