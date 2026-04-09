<h1>Reporte maestro</h1>
<p><a href="<?= e(APP_BASE_PATH) ?>/reportes/exportar">Exportar a Excel</a></p>
<table border="1" cellpadding="6">
    <thead>
    <tr>
        <th>Documento</th>
        <th>Nombre</th>
        <th>Estado</th>
        <th>Últ. edición reporte</th>
        <th>Editar campo libre</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (($rows ?? []) as $row): ?>
        <tr>
            <td><?= e((string) $row['numero_documento']) ?></td>
            <td><?= e((string) $row['nombre_completo']) ?></td>
            <td><?= e((string) $row['estado']) ?></td>
            <td><?= e((string) ($row['reporte_actualizado'] ?? '')) ?></td>
            <td>
                <form method="post" action="<?= e(APP_BASE_PATH) ?>/reportes/update">
                    <input type="hidden" name="aprendiz_id" value="<?= (int) $row['id'] ?>">
                    <input type="text" name="campo" placeholder="campo">
                    <input type="text" name="valor" placeholder="valor">
                    <button type="submit">Guardar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
