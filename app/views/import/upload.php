<h1>Importar formulario de aprendices</h1>
<form method="post" action="<?= e(APP_BASE_PATH) ?>/importar" enctype="multipart/form-data">
    <input type="file" name="archivo" accept=".xlsx,.xls,.csv" required>
    <button type="submit">Importar</button>
</form>
