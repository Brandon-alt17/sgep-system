<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <?php partial('components/inter_font_faces'); ?>
    <link rel="stylesheet" href="<?= e(APP_BASE_PATH) ?>/css/app.css">
</head>
<body>
<?php
// Contexto global de UI: ruta actual para resaltar el item activo del sidebar.
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim((string) APP_BASE_PATH, '/');
$currentPath = ($base !== '' && str_starts_with($uri, $base)) ? (substr($uri, strlen($base)) ?: '/') : $uri;
$currentPath = $currentPath === '' ? '/' : $currentPath;
$pathForTitle = $currentPath;
if (str_starts_with($currentPath, '/momentos/')) {
    $currentPath = '/aprendices';
}
if (str_starts_with($pathForTitle, '/documentos/info')) {
    $currentPath = '/aprendices';
}
$queryGenerar = [];
if ($pathForTitle === '/documentos/generar') {
    parse_str((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? ''), $queryGenerar);
    if ((int) ($queryGenerar['aprendiz_id'] ?? 0) > 0) {
        $currentPath = '/aprendices';
    }
}

$titles = [
    '/dashboard' => 'Dashboard',
    '/aprendices' => 'Aprendices',
    '/importar' => 'Importar datos',
    '/reportes/maestro' => 'Reportes',
    '/catalogo/programas' => 'Catálogo',
    '/catalogo/programas/ver' => 'Catálogo',
    '/catalogo/programas/pendientes' => 'Catálogo',
    '/catalogo/programas/importar' => 'Catálogo',
    '/catalogo/programas/nuevo' => 'Catálogo',
    '/catalogo/grupos' => 'Catálogo',
    '/catalogo/empresas' => 'Catálogo',
    '/catalogo/empresas/nuevo' => 'Catálogo',
    '/catalogo/empresas/ver' => 'Catálogo',
    '/catalogo/empresas/editar' => 'Catálogo',
    '/documentos/generar' => 'Generar F-023',
    '/momentos/create' => 'Momento F-023',
];
$pageTitle = $titles[$pathForTitle] ?? $titles[$currentPath] ?? 'SGEP';
?>
<?php partial('components/icons'); ?>
<?php partial('components/ui'); ?>
<div class="min-h-screen md:pl-[260px]">
    <?php partial('components/sidebar', ['currentPath' => $currentPath]); ?>
    <div class="grid h-screen min-h-0 grid-rows-[56px_minmax(0,1fr)]">
        <?php partial('components/topbar', ['title' => $pageTitle]); ?>
        <main class="min-h-0 overflow-y-auto p-6">
            <?php require $viewPath; ?>
        </main>
    </div>
</div>
<?php partial('components/confirm_modal'); ?>
<?php partial('components/download_progress_overlay'); ?>
<script src="<?= e(APP_BASE_PATH) ?>/js/app.js"></script>
<script>window.APP_BASE_PATH = "<?= e((string) APP_BASE_PATH) ?>";</script>
<script src="<?= e(APP_BASE_PATH) ?>/js/modal-manager.js"></script>
</body>
</html>
