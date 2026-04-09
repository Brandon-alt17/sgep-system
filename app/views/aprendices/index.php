<h1>Aprendices</h1>
<a href="<?= e(APP_BASE_PATH) ?>/aprendices/create">Nuevo aprendiz</a>
<table border="1" cellpadding="6">
    <thead>
    <tr>
        <th>Documento</th>
        <th>Nombre</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (($aprendices ?? []) as $aprendiz): ?>
        <tr>
            <td><?= e((string) $aprendiz['numero_documento']) ?></td>
            <td><?= e((string) $aprendiz['nombre_completo']) ?></td>
            <td><?= e((string) $aprendiz['estado']) ?></td>
            <td><a href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int) $aprendiz['id'] ?>">Ver</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
