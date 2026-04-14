<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_BASE_PATH) ?>/css/app.css">
</head>
<body>
<header>
    <nav>
        <a href="<?= e(APP_BASE_PATH) ?>/dashboard">Dashboard</a> |
        <a href="<?= e(APP_BASE_PATH) ?>/aprendices">Aprendices</a> |
        <a href="<?= e(APP_BASE_PATH) ?>/importar">Importar</a> |
        <a href="<?= e(APP_BASE_PATH) ?>/reportes/maestro">Reportes</a> |
        <a href="<?= e(APP_BASE_PATH) ?>/documentos/generar">Configuraciones</a> |
    </nav>
</header>
<main>
    <?php require $viewPath; ?>
</main>
<script src="<?= e(APP_BASE_PATH) ?>/js/app.js"></script>
</body>
</html>
