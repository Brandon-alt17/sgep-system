<?php partial('components/page_header', [
    'title' => 'Registro de momento ' . (string) $tipo,
    'subtitle' => 'Diligencie la visita y la valoración del aprendiz según el momento seleccionado.',
]); ?>

<?php if (empty($aprendiz)): ?>
    <section class="rounded-[10px] border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">Aprendiz no encontrado.</section>
<?php else: ?>
    <section class="mb-4 rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
        <p class="m-0 text-sm"><?= e((string) $aprendiz['nombre_completo']) ?> - <?= e((string) $aprendiz['numero_documento']) ?></p>
    </section>

    <form method="post" action="<?= e(APP_BASE_PATH) ?>/momentos/store" class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
        <input type="hidden" name="aprendiz_id" value="<?= (int) $aprendiz['id'] ?>">
        <input type="hidden" name="tipo" value="<?= e((string) $tipo) ?>">

        <div class="grid gap-4 md:grid-cols-2">
            <label class="block text-sm font-medium text-gray-700">Fecha visita
                <input type="date" name="fecha_visita" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </label>
            <label class="block text-sm font-medium text-gray-700">Modalidad
                <select name="modalidad" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="Presencial">Presencial</option>
                    <option value="Virtual">Virtual</option>
                </select>
            </label>
            <?php if ($tipo !== 'M3'): ?>
                <label class="block text-sm font-medium text-gray-700 md:col-span-2">Próxima visita
                    <input type="date" name="proxima_visita" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm md:max-w-xs">
                </label>
            <?php endif; ?>
        </div>

        <label class="mt-4 block text-sm font-medium text-gray-700">Observación instructor
            <textarea name="obs_instructor" data-max="500" class="mt-1 min-h-24 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
        </label>

        <?php if ($tipo === 'M3'): ?>
            <label class="mt-2 block text-sm font-medium text-gray-700">Juicio final
                <select name="juicio_final" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm md:max-w-xs">
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
                <h3 class="mb-2 mt-0 text-[15px] font-semibold">Factores técnicos</h3>
                <div class="space-y-2">
                    <?php foreach ($factores['tecnicos'] as $idx => $nombre): ?>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <input type="hidden" name="factores[<?= $idx ?>][tipo_factor]" value="tecnico">
                            <input type="hidden" name="factores[<?= $idx ?>][nombre_factor]" value="<?= e($nombre) ?>">
                            <p class="m-0 text-sm font-semibold text-gray-800"><?= e($nombre) ?></p>
                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $idx ?>][valoracion]" value="S" required class="h-4 w-4"> S</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $idx ?>][valoracion]" value="PM" required class="h-4 w-4"> PM</label>
                            </div>
                            <input type="text" name="factores[<?= $idx ?>][observacion]" placeholder="Observación" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="mt-6">
                <h3 class="mb-2 mt-0 text-[15px] font-semibold">Factores actitudinales</h3>
                <div class="space-y-2">
                    <?php foreach ($factores['actitudinales'] as $offset => $nombre): ?>
                        <?php $i = $offset + 8; ?>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <input type="hidden" name="factores[<?= $i ?>][tipo_factor]" value="actitudinal">
                            <input type="hidden" name="factores[<?= $i ?>][nombre_factor]" value="<?= e($nombre) ?>">
                            <p class="m-0 text-sm font-semibold text-gray-800"><?= e($nombre) ?></p>
                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $i ?>][valoracion]" value="S" required class="h-4 w-4"> S</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $i ?>][valoracion]" value="PM" required class="h-4 w-4"> PM</label>
                            </div>
                            <input type="text" name="factores[<?= $i ?>][observacion]" placeholder="Observación" class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <button type="submit" class="mt-6 inline-flex rounded-md border border-app-accent bg-app-accent px-4 py-2 text-sm font-medium text-white hover:bg-[#0e8f82]">Guardar momento</button>
    </form>
<?php endif; ?>
