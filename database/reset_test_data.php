<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use App\Helpers\Database;
use App\Helpers\ImportHistory;

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

$host = (string) env('DB_HOST', '');
$dbName = (string) env('DB_DATABASE', '');

if (!in_array(strtolower($host), ['127.0.0.1', 'localhost'], true)) {
    fwrite(STDERR, 'Abortado: este script solo se permite con DB_HOST local.' . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'ATENCION: Esto eliminara TODOS los datos de negocio de la BD actual y el historial de importaciones.' . PHP_EOL);
fwrite(STDOUT, 'Base detectada: ' . $dbName . ' en host ' . $host . PHP_EOL);
fwrite(STDOUT, 'Escribe RESET para continuar: ');
$confirm = trim((string) fgets(STDIN));

if ($confirm !== 'RESET') {
    fwrite(STDOUT, 'Operacion cancelada.' . PHP_EOL);
    exit(0);
}

$pdo = Database::connection();

// Ordenado de tablas hijas a padres para evitar bloqueos por relaciones.
$tables = [
    'factores_valoracion',
    'plan_trabajo',
    'momentos',
    'documentos_generados',
    'reporte_campos',
    'estados_historial',
    'aprendiz_info_general',
    'programa_resultados_aprendizaje',
    'programa_competencias',
    'programa_importaciones_pdf',
    'programa_enlaces_pendientes',
    'empresa_jefes',
    'aprendices',
    'programas',
    'empresas',
];

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($tables as $table) {
        $pdo->exec('TRUNCATE TABLE `' . $table . '`');
        fwrite(STDOUT, 'Limpia: ' . $table . PHP_EOL);
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    if (!ImportHistory::clear()) {
        $historyPath = BASE_PATH . '/storage/app/imports/history.json';
        fwrite(STDERR, 'Error: no se pudo limpiar el historial de importaciones.' . PHP_EOL);
        fwrite(STDERR, 'Archivo: ' . $historyPath . PHP_EOL);
        fwrite(STDERR, 'Sugerencia: sudo rm ' . $historyPath . ' o ajuste permisos del directorio storage/app/imports.' . PHP_EOL);
        exit(1);
    }

    fwrite(STDOUT, 'Limpia: historial de importaciones (history.json)' . PHP_EOL);
    fwrite(STDOUT, 'Reset completado. Estructura intacta, datos e historial de imports eliminados.' . PHP_EOL);
} catch (\Throwable $e) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    fwrite(STDERR, 'Error en reset: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
