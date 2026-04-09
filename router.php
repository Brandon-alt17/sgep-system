<?php

declare(strict_types=1);

use App\Controllers\AprendizController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentoController;
use App\Controllers\ImportacionController;
use App\Controllers\MomentoController;
use App\Controllers\ReporteController;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim((string) env('APP_BASE_PATH', ''), '/');

if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base)) ?: '/';
}

$routes = [
    ['GET', '/', [DashboardController::class, 'index']],
    ['GET', '/dashboard', [DashboardController::class, 'index']],
    ['GET', '/importar', [ImportacionController::class, 'upload']],
    ['POST', '/importar', [ImportacionController::class, 'process']],
    ['GET', '/aprendices', [AprendizController::class, 'index']],
    ['GET', '/aprendices/create', [AprendizController::class, 'create']],
    ['POST', '/aprendices', [AprendizController::class, 'store']],
    ['GET', '/aprendices/show', [AprendizController::class, 'show']],
    ['POST', '/aprendices/update', [AprendizController::class, 'update']],
    ['GET', '/momentos/create', [MomentoController::class, 'create']],
    ['POST', '/momentos/store', [MomentoController::class, 'store']],
    ['POST', '/momentos/update', [MomentoController::class, 'update']],
    ['GET', '/documentos/generar', [DocumentoController::class, 'create']],
    ['POST', '/documentos/generar', [DocumentoController::class, 'generate']],
    ['GET', '/reportes/maestro', [ReporteController::class, 'index']],
    ['POST', '/reportes/update', [ReporteController::class, 'update']],
    ['GET', '/reportes/exportar', [ReporteController::class, 'export']],
];

foreach ($routes as [$routeMethod, $routePath, $handler]) {
    if ($method === $routeMethod && $uri === $routePath) {
        [$class, $action] = $handler;
        (new $class())->{$action}();
        return;
    }
}

http_response_code(404);
view('errors/404', ['uri' => $uri]);
