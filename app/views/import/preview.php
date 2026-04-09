<h1>Resultado de importación</h1>
<ul>
    <li>Insertados: <?= e((string) ($resultado['inserted'] ?? 0)) ?></li>
    <li>Actualizados: <?= e((string) ($resultado['updated'] ?? 0)) ?></li>
    <li>Duplicados detectados: <?= e((string) ($resultado['duplicates'] ?? 0)) ?></li>
</ul>
<?php if (!empty($resultado['errors'])): ?>
    <h2>Errores</h2>
    <ul>
        <?php foreach ($resultado['errors'] as $error): ?>
            <li><?= e((string) $error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
