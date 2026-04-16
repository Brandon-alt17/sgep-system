<?php partial('components/page_header', [
    'title' => 'Registro de momento ' . (string) $tipo,
    'subtitle' => 'Diligencie la visita y la valoración del aprendiz según el momento seleccionado.',
]); ?>

<?php if (empty($aprendiz)): ?>
    <section class="<?= e(ui_warning_card_classes()) ?>">Aprendiz no encontrado.</section>
<?php else: ?>
    <section class="mb-4 <?= e(ui_card_classes()) ?>">
        <p class="m-0 text-sm"><?= e((string) $aprendiz['nombre_completo']) ?> - <?= e((string) $aprendiz['numero_documento']) ?></p>
    </section>

    <form method="post" action="<?= e(APP_BASE_PATH) ?>/momentos/store" class="<?= e(ui_card_classes()) ?>">
        <input type="hidden" name="aprendiz_id" value="<?= (int) $aprendiz['id'] ?>">
        <input type="hidden" name="tipo" value="<?= e((string) $tipo) ?>">

        <div class="grid gap-4 md:grid-cols-2">
            <label class="<?= e(ui_label_classes()) ?>">Fecha visita
                <input type="date" name="fecha_visita" required class="<?= e(ui_input_classes()) ?>">
            </label>
            <label class="<?= e(ui_label_classes()) ?>">Modalidad
                <select name="modalidad" class="<?= e(ui_input_classes()) ?>">
                    <option value="Presencial">Presencial</option>
                    <option value="Virtual">Virtual</option>
                </select>
            </label>
            <?php if ($tipo !== 'M3'): ?>
                <label class="<?= e(ui_label_classes()) ?> md:col-span-2">Próxima visita
                    <input type="date" name="proxima_visita" required class="<?= e(ui_input_classes()) ?> md:max-w-xs">
                </label>
            <?php endif; ?>
        </div>

        <label class="mt-4 <?= e(ui_label_classes()) ?>">Observación instructor
            <textarea name="obs_instructor" data-max="500" class="<?= e(ui_input_classes()) ?> min-h-24"></textarea>
        </label>

        <?php if ($tipo === 'M3'): ?>
            <label class="mt-2 <?= e(ui_label_classes()) ?>">Juicio final
                <select name="juicio_final" class="<?= e(ui_input_classes()) ?> md:max-w-xs">
                    <option value="Aprobado">Aprobado</option>
                    <option value="No aprobado">No aprobado</option>
                </select>
            </label>
        <?php endif; ?>

        <?php if (in_array($tipo, ['M2', 'M3', 'EX'], true)): ?>
            <?php $factores = require base_path('config/factores.php'); ?>
            <?php
            // Se renderizan factores técnicos y actitudinales en tarjetas uniformes para facilitar futuras iteraciones de UX.
            ?>
            <section class="mt-6">
                <h3 class="<?= e(ui_heading_sm_classes()) ?>">Factores técnicos</h3>
                <div class="space-y-2">
                    <?php foreach ($factores['tecnicos'] as $idx => $nombre): ?>
                        <div class="rounded-lg border border-app-border bg-app-panelSubtle p-3">
                            <input type="hidden" name="factores[<?= $idx ?>][tipo_factor]" value="tecnico">
                            <input type="hidden" name="factores[<?= $idx ?>][nombre_factor]" value="<?= e($nombre) ?>">
                            <p class="m-0 text-sm font-semibold text-app-text"><?= e($nombre) ?></p>
                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $idx ?>][valoracion]" value="S" required class="h-4 w-4"> S</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $idx ?>][valoracion]" value="PM" required class="h-4 w-4"> PM</label>
                            </div>
                            <input type="text" name="factores[<?= $idx ?>][observacion]" placeholder="Observación" class="<?= e(ui_input_classes()) ?> mt-2">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="mt-6">
                <h3 class="<?= e(ui_heading_sm_classes()) ?>">Factores actitudinales</h3>
                <div class="space-y-2">
                    <?php foreach ($factores['actitudinales'] as $offset => $nombre): ?>
                        <?php $i = $offset + 8; ?>
                        <div class="rounded-lg border border-app-border bg-app-panelSubtle p-3">
                            <input type="hidden" name="factores[<?= $i ?>][tipo_factor]" value="actitudinal">
                            <input type="hidden" name="factores[<?= $i ?>][nombre_factor]" value="<?= e($nombre) ?>">
                            <p class="m-0 text-sm font-semibold text-app-text"><?= e($nombre) ?></p>
                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $i ?>][valoracion]" value="S" required class="h-4 w-4"> S</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $i ?>][valoracion]" value="PM" required class="h-4 w-4"> PM</label>
                            </div>
                            <input type="text" name="factores[<?= $i ?>][observacion]" placeholder="Observación" class="<?= e(ui_input_classes()) ?> mt-2">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <button type="submit" class="mt-6 <?= e(ui_button_primary_classes()) ?>">Guardar momento</button>
    </form>
<?php endif; ?>
