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

$titles = [
    '/dashboard' => 'Dashboard',
    '/aprendices' => 'Aprendices',
    '/importar' => 'Importar datos',
    '/reportes/maestro' => 'Reportes',
    '/documentos/generar' => 'Configuración',
];
$pageTitle = $titles[$currentPath] ?? 'SGEP';
$currentUserName = (string) ($_SESSION['user_name'] ?? 'Usuario no registrado');
?>
<?php partial('components/icons'); ?>
<?php partial('components/ui'); ?>
<div class="grid min-h-screen md:grid-cols-[260px_1fr]">
    <?php partial('components/sidebar', ['currentPath' => $currentPath]); ?>
    <div class="grid grid-rows-[56px_1fr]">
        <?php partial('components/topbar', ['title' => $pageTitle, 'userName' => $currentUserName]); ?>
        <main class="p-6">
            <?php require $viewPath; ?>
        </main>
    </div>
</div>
<script src="<?= e(APP_BASE_PATH) ?>/js/app.js"></script>
<script>window.APP_BASE_PATH = "<?= e((string) APP_BASE_PATH) ?>";</script>
<script src="<?= e(APP_BASE_PATH) ?>/js/modal-manager.js"></script>
</body>
</html>
