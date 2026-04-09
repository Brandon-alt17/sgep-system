<h1>Nuevo aprendiz</h1>
<?php if (!empty($errors)): ?>
    <ul>
        <?php foreach ($errors as $err): ?>
            <li><?= e((string) $err) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/aprendices">
    <label>Nombre completo <input type="text" name="nombre_completo" required></label><br>
    <label>Tipo documento <input type="text" name="tipo_documento" value="CC" required></label><br>
    <label>Número documento <input type="text" name="numero_documento" required></label><br>
    <label>Teléfono <input type="text" name="telefono"></label><br>
    <label>Correo personal <input type="email" name="correo_personal"></label><br>
    <label>Correo institucional <input type="email" name="correo_institucional"></label><br>
    <label>Ficha <input type="text" name="ficha"></label><br>
    <label>Estado
        <select name="estado">
            <option>Pendiente por iniciar</option>
            <option>En ejecución</option>
            <option>Aplazada</option>
            <option>Finalizada</option>
            <option>Por certificar</option>
            <option>Certificado</option>
            <option>Pendiente por comité</option>
        </select>
    </label><br>
    <button type="submit">Guardar</button>
</form>
