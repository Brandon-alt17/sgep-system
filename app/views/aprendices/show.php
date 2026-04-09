<h1>Perfil del aprendiz</h1>
<p><strong>Nombre:</strong> <?= e((string) $aprendiz['nombre_completo']) ?></p>
<p><strong>Documento:</strong> <?= e((string) $aprendiz['numero_documento']) ?></p>
<p><strong>Estado:</strong> <?= e((string) $aprendiz['estado']) ?></p>

<h2>Actualizar perfil</h2>
<form method="post" action="<?= e(APP_BASE_PATH) ?>/aprendices/update">
    <input type="hidden" name="id" value="<?= (int) $aprendiz['id'] ?>">
    <label>Nombre <input type="text" name="nombre_completo" value="<?= e((string) $aprendiz['nombre_completo']) ?>"></label><br>
    <label>Teléfono <input type="text" name="telefono" value="<?= e((string) ($aprendiz['telefono'] ?? '')) ?>"></label><br>
    <label>Correo personal <input type="email" name="correo_personal" value="<?= e((string) ($aprendiz['correo_personal'] ?? '')) ?>"></label><br>
    <label>Correo institucional <input type="email" name="correo_institucional" value="<?= e((string) ($aprendiz['correo_institucional'] ?? '')) ?>"></label><br>
    <label>Estado <input type="text" name="estado" value="<?= e((string) $aprendiz['estado']) ?>"></label><br>
    <button type="submit">Actualizar</button>
</form>

<h2>Momentos</h2>
<ul>
    <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M1">Registrar M1</a></li>
    <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M2">Registrar M2</a></li>
    <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M3">Registrar M3</a></li>
    <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=EX">Registrar Extraordinario</a></li>
</ul>

<h2>Documentos</h2>
<a href="<?= e(APP_BASE_PATH) ?>/documentos/generar?aprendiz_id=<?= (int) $aprendiz['id'] ?>">Generar F-023</a>
