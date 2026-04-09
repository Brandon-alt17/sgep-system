<h1>Registro de momento <?= e((string) $tipo) ?></h1>
<?php if (empty($aprendiz)): ?>
    <p>Aprendiz no encontrado.</p>
<?php else: ?>
    <p><?= e((string) $aprendiz['nombre_completo']) ?> - <?= e((string) $aprendiz['numero_documento']) ?></p>
    <form method="post" action="<?= e(APP_BASE_PATH) ?>/momentos/store">
        <input type="hidden" name="aprendiz_id" value="<?= (int) $aprendiz['id'] ?>">
        <input type="hidden" name="tipo" value="<?= e((string) $tipo) ?>">
        <label>Fecha visita <input type="date" name="fecha_visita" required></label><br>
        <label>Modalidad
            <select name="modalidad">
                <option value="Presencial">Presencial</option>
                <option value="Virtual">Virtual</option>
            </select>
        </label><br>
        <?php if ($tipo !== 'M3'): ?>
            <label>Próxima visita <input type="date" name="proxima_visita" required></label><br>
        <?php endif; ?>
        <label>Observación instructor</label><br>
        <textarea name="obs_instructor" data-max="500"></textarea><br>
        <?php if ($tipo === 'M3'): ?>
            <label>Juicio final
                <select name="juicio_final">
                    <option value="Aprobado">Aprobado</option>
                    <option value="No aprobado">No aprobado</option>
                </select>
            </label><br>
        <?php endif; ?>
        <?php if (in_array($tipo, ['M2', 'M3', 'EX'], true)): ?>
            <?php $factores = require base_path('config/factores.php'); ?>
            <h2>Factores técnicos</h2>
            <?php foreach ($factores['tecnicos'] as $idx => $nombre): ?>
                <div>
                    <input type="hidden" name="factores[<?= $idx ?>][tipo_factor]" value="tecnico">
                    <input type="hidden" name="factores[<?= $idx ?>][nombre_factor]" value="<?= e($nombre) ?>">
                    <strong><?= e($nombre) ?></strong>
                    <label><input type="radio" name="factores[<?= $idx ?>][valoracion]" value="S" required> S</label>
                    <label><input type="radio" name="factores[<?= $idx ?>][valoracion]" value="PM" required> PM</label>
                    <input type="text" name="factores[<?= $idx ?>][observacion]" placeholder="Observación">
                </div>
            <?php endforeach; ?>
            <h2>Factores actitudinales</h2>
            <?php foreach ($factores['actitudinales'] as $offset => $nombre): ?>
                <?php $i = $offset + 8; ?>
                <div>
                    <input type="hidden" name="factores[<?= $i ?>][tipo_factor]" value="actitudinal">
                    <input type="hidden" name="factores[<?= $i ?>][nombre_factor]" value="<?= e($nombre) ?>">
                    <strong><?= e($nombre) ?></strong>
                    <label><input type="radio" name="factores[<?= $i ?>][valoracion]" value="S" required> S</label>
                    <label><input type="radio" name="factores[<?= $i ?>][valoracion]" value="PM" required> PM</label>
                    <input type="text" name="factores[<?= $i ?>][observacion]" placeholder="Observación">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <button type="submit">Guardar momento</button>
    </form>
<?php endif; ?>
