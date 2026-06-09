<?php

declare(strict_types=1);

use App\Controllers\AprendizController;
use App\Controllers\CatalogoController;
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
    ['GET', '/importar/resultado', [ImportacionController::class, 'showResult']],
    ['GET', '/importar/conflictos', [ImportacionController::class, 'showConflicts']],
    ['POST', '/importar/conflicto/resolver', [ImportacionController::class, 'resolveConflict']],
    ['POST', '/importar/conflicto/completado', [ImportacionController::class, 'completeConflictAprendiz']],
    ['GET', '/aprendices', [AprendizController::class, 'index']],
    ['GET', '/aprendices/create', [AprendizController::class, 'create']],
    ['POST', '/aprendices', [AprendizController::class, 'store']],
    ['GET', '/aprendices/show', [AprendizController::class, 'show']],
    ['GET', '/aprendices/jefes-por-empresa', [AprendizController::class, 'jefesPorEmpresa']],
    ['POST', '/aprendices/update', [AprendizController::class, 'update']],
    ['POST', '/aprendices/update-visitas', [AprendizController::class, 'updateVisitas']],
    ['GET', '/momentos/create', [MomentoController::class, 'create']],
    ['POST', '/momentos/store', [MomentoController::class, 'store']],
    ['POST', '/momentos/update', [MomentoController::class, 'update']],
    ['GET', '/documentos/generar', [DocumentoController::class, 'create']],
    ['POST', '/documentos/generar', [DocumentoController::class, 'generate']],
    ['GET', '/documentos/info', [DocumentoController::class, 'info']],
    ['POST', '/documentos/info', [DocumentoController::class, 'saveInfo']],
    ['GET', '/reportes/maestro', [ReporteController::class, 'index']],
    ['POST', '/reportes/update', [ReporteController::class, 'update']],
    ['POST', '/reportes/sync', [ReporteController::class, 'sync']],
    ['GET', '/reportes/exportar', [ReporteController::class, 'export']],
    ['GET', '/catalogo/programas', [CatalogoController::class, 'programas']],
    ['GET', '/catalogo/programas/pendientes', [CatalogoController::class, 'pendientesPrograma']],
    ['POST', '/catalogo/programas/pendientes/resolver', [CatalogoController::class, 'resolverPendientePrograma']],
    ['GET', '/catalogo/programas/importar', [CatalogoController::class, 'importarProgramaForm']],
    ['POST', '/catalogo/programas/importar/analizar', [CatalogoController::class, 'importarProgramaAnalizar']],
    ['POST', '/catalogo/programas/importar/guardar', [CatalogoController::class, 'importarProgramaGuardar']],
    ['GET', '/catalogo/programas/nuevo', [CatalogoController::class, 'nuevoProgramaForm']],
    ['POST', '/catalogo/programas/guardar', [CatalogoController::class, 'guardarProgramaFormacion']],
    ['POST', '/catalogo/programas/actualizar-datos', [CatalogoController::class, 'actualizarProgramaDatos']],
    ['POST', '/catalogo/programas/actualizar-competencia', [CatalogoController::class, 'actualizarProgramaCompetencia']],
    ['POST', '/catalogo/programas/agregar-competencia', [CatalogoController::class, 'agregarProgramaCompetencia']],
    ['POST', '/catalogo/programas/eliminar-competencia', [CatalogoController::class, 'eliminarProgramaCompetencia']],
    ['POST', '/catalogo/programas/eliminar', [CatalogoController::class, 'eliminarPrograma']],
    ['GET', '/catalogo/programas/ver', [CatalogoController::class, 'verPrograma']],
    ['GET', '/catalogo/grupos', [CatalogoController::class, 'grupos']],
    ['GET', '/catalogo/empresas', [CatalogoController::class, 'empresas']],
    ['GET', '/catalogo/empresas/nuevo', [CatalogoController::class, 'empresasNuevo']],
    ['POST', '/catalogo/empresas', [CatalogoController::class, 'empresasCrear']],
    ['GET', '/catalogo/empresas/ver', [CatalogoController::class, 'empresasVer']],
    ['GET', '/catalogo/empresas/editar', [CatalogoController::class, 'empresasEditar']],
    ['POST', '/catalogo/empresas/actualizar', [CatalogoController::class, 'empresasActualizar']],
    ['POST', '/catalogo/empresas/eliminar', [CatalogoController::class, 'empresasEliminar']],
    ['POST', '/catalogo/empresas/agregar-jefe', [CatalogoController::class, 'agregarEmpresaJefe']],
    ['POST', '/catalogo/empresas/actualizar-jefe', [CatalogoController::class, 'actualizarEmpresaJefe']],
    ['POST', '/catalogo/empresas/eliminar-jefe', [CatalogoController::class, 'eliminarEmpresaJefe']],
];

foreach ($routes as [$routeMethod, $routePath, $handler]) {
    if ($method === $routeMethod && $uri === $routePath) {
        [$class, $action] = $handler;
        try {
            (new $class())->{$action}();
        } catch (Throwable $e) {
            app_handle_exception($e);
        }

        return;
    }
}

http_response_code(404);
view('errors/404', ['uri' => $uri]);
