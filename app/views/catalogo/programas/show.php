<?php
$parsed = (array) ($parsed ?? []);
$meta = (array) ($parsed['meta'] ?? []);
$competencias = (array) ($parsed['competencias'] ?? []);
$warnings = (array) ($parsed['warnings'] ?? []);
$saved = ((string) ($_GET['saved'] ?? '')) === '1';
$programId = (int) (($programa['id'] ?? 0));
$totalResultados = 0;
foreach ($competencias as $comp) {
    $totalResultados += count((array) ($comp['resultados'] ?? []));
}
$programName = trim((string) ($meta['nombre'] ?? ''));
if ($programName === '') {
    $programName = 'Programa sin nombre detectado';
}
?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/programas" aria-label="Volver al catálogo de programas">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5 "><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Detalle del programa</h2>
        <p class="m-0 text-sm text-app-muted">Vista de consulta con edición puntual por sección.</p>
    </div>
</section>

<?php if ($saved): ?>
    <section class="mb-4 rounded-[10px] border border-app-borderSuccess bg-app-successBg p-4 text-sm text-app-successText">Cambios guardados correctamente.</section>
<?php endif; ?>

<section class="mt-4">
    <h3 class="m-0 text-2xl font-semibold text-app-text"><?= e($programName) ?></h3>
    <p class="mt-1 text-sm text-app-muted">Código: <?= e((string) (($meta['codigo'] ?? '') !== '' ? $meta['codigo'] : 'No detectado')) ?></p>
</section>

<section class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Nivel</p>
        <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) (($meta['nivel'] ?? '') !== '' ? $meta['nivel'] : 'N/D')) ?></p>
    </article>
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Competencias</p>
        <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) count($competencias)) ?></p>
    </article>
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Resultados de aprendizaje</p>
        <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) $totalResultados) ?></p>
    </article>
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Horas totales</p>
        <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) (($meta['horas_total'] ?? '') !== '' ? $meta['horas_total'] . 'h' : 'N/D')) ?></p>
    </article>
</section>

<?php if ($warnings !== []): ?>
    <section class="mt-4 <?= e(ui_warning_card_classes()) ?> mb-6">
        <h4 class="mb-2 mt-0 text-sm font-semibold">Advertencias de la importación</h4>
        <ul class="m-0 list-disc space-y-1 pl-4">
            <?php foreach ($warnings as $warning): ?>
                <?php $warningText = is_array($warning) ? (string) ($warning['message'] ?? '') : (string) $warning; ?>
                <li><?= e($warningText) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="<?= e(ui_card_classes()) ?> mb-6 mt-4 p-6" data-inline-edit-root>
    <div class="mb-4 flex items-center justify-between">
        <h4 class="m-0 text-md font-semibold text-app-text">Datos del programa</h4>
        <div class="flex gap-2">
            <button type="button" class="<?= e(ui_button_icon_classes()) ?>" data-inline-edit-open aria-label="Editar datos del programa">
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('pencil') ?></span>
            </button>
            <button type="submit" form="programa-datos-form" class="<?= e(ui_button_icon_classes()) ?> hidden" data-inline-edit-save aria-label="Guardar datos del programa">
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
            </button>
            <button type="button" class="<?= e(ui_button_icon_classes()) ?> hidden" data-inline-edit-cancel aria-label="Cancelar edición de datos del programa">
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
            </button>
        </div>
    </div>

    <div data-inline-view>
        <div class="overflow-hidden rounded-md">
            <table class="<?= e(ui_table_classes()) ?>">
                <thead>
                <tr>
                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Campo</th>
                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Valor</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">Código</td>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>"><?= e((string) (($meta['codigo'] ?? '') !== '' ? $meta['codigo'] : 'N/D')) ?></td>
                </tr>
                <tr>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">Nombre</td>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>"><?= e((string) (($meta['nombre'] ?? '') !== '' ? $meta['nombre'] : 'N/D')) ?></td>
                </tr>
                <tr>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">Nivel</td>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>"><?= e((string) (($meta['nivel'] ?? '') !== '' ? $meta['nivel'] : 'N/D')) ?></td>
                </tr>
                <tr>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">Modalidad</td>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>"><?= e((string) (($meta['modalidad'] ?? '') !== '' ? $meta['modalidad'] : 'N/D')) ?></td>
                </tr>
                <tr>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">Horas lectiva</td>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>"><?= e((string) (($meta['horas_lectiva'] ?? '') !== '' ? $meta['horas_lectiva'] : 'N/D')) ?></td>
                </tr>
                <tr>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">Horas productiva</td>
                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>"><?= e((string) (($meta['horas_productiva'] ?? '') !== '' ? $meta['horas_productiva'] : 'N/D')) ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <form id="programa-datos-form" method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/actualizar-datos" data-inline-edit-form class="hidden">
        <input type="hidden" name="programa_id" value="<?= (int) $programId ?>">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="<?= e(ui_label_classes()) ?>">Código
                <input type="text" name="codigo" value="<?= e((string) ($meta['codigo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
            </label>
            <label class="<?= e(ui_label_classes()) ?>">Nombre
                <input type="text" name="nombre" value="<?= e((string) ($meta['nombre'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
            </label>
            <label class="<?= e(ui_label_classes()) ?>">Nivel
                <input type="text" name="nivel" value="<?= e((string) ($meta['nivel'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
            </label>
            <label class="<?= e(ui_label_classes()) ?>">Modalidad
                <input type="text" name="modalidad" value="<?= e((string) ($meta['modalidad'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
            </label>
            <label class="<?= e(ui_label_classes()) ?>">Horas lectiva
                <input type="number" min="0" name="horas_lectiva" value="<?= e((string) ($meta['horas_lectiva'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
            </label>
            <label class="<?= e(ui_label_classes()) ?>">Horas productiva
                <input type="number" min="0" name="horas_productiva" value="<?= e((string) ($meta['horas_productiva'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
            </label>
        </div>
    </form>
</section>

<section class="<?= e(ui_card_classes()) ?> mb-6 mt-4 p-6">
    <h4 class="m-0 text-md font-semibold text-app-text">Competencias y Resultados de Aprendizaje</h4>
    <?php if ($competencias === []): ?>
        <div class="mt-6 rounded-md border border-app-border bg-app-panelSubtle p-4 text-sm text-app-muted">No se detectaron competencias automáticamente.</div>
    <?php else: ?>
        <div class="mt-6 space-y-6">
            <?php foreach ($competencias as $compIndex => $comp): ?>
                <?php $resultadosComp = (array) ($comp['resultados'] ?? []); ?>
                <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6" data-inline-edit-root>
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <?php if (trim((string) ($comp['codigo'] ?? '')) !== ''): ?>
                                <p class="m-0 text-xs font-semibold tracking-wide text-app-muted ui-monospace font-mono"><?= e((string) $comp['codigo']) ?></p>
                            <?php endif; ?>
                            <p class="m-0 mt-1 text-lg font-semibold text-app-text"><?= e((string) (($comp['nombre'] ?? '') !== '' ? $comp['nombre'] : 'Competencia ' . ($compIndex + 1))) ?></p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="<?= e(ui_button_icon_classes()) ?>" data-inline-edit-open aria-label="Editar competencia">
                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('pencil') ?></span>
                            </button>
                            <button type="submit" form="competencia-form-<?= (int) ($comp['id'] ?? 0) ?>" class="<?= e(ui_button_icon_classes()) ?> hidden" data-inline-edit-save aria-label="Guardar competencia">
                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                            </button>
                            <button type="button" class="<?= e(ui_button_icon_classes()) ?> hidden" data-inline-edit-cancel aria-label="Cancelar edición de competencia">
                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                            </button>
                        </div>
                    </div>

                    <div data-inline-view>
                        <div class="mb-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <p class="m-0 text-xs font-semibold tracking-wide text-app-muted">Código competencia</p>
                                <p class="mt-1 text-sm text-app-text"><?= e((string) (($comp['codigo'] ?? '') !== '' ? $comp['codigo'] : 'N/D')) ?></p>
                            </div>
                            <div>
                                <p class="m-0 text-xs font-semibold tracking-wide text-app-muted">Nombre competencia</p>
                                <p class="mt-1 text-sm text-app-text"><?= e((string) (($comp['nombre'] ?? '') !== '' ? $comp['nombre'] : 'N/D')) ?></p>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-md">
                            <table class="<?= e(ui_table_classes()) ?>">
                                <thead>
                                <tr>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Código RA</th>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Resultado de aprendizaje</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($resultadosComp === []): ?>
                                    <tr>
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>" colspan="2">Sin resultados detectados para esta competencia.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($resultadosComp as $raIndex => $resultado): ?>
                                        <tr class="<?= $raIndex < (count($resultadosComp) - 1) ? 'border-b border-app-borderSoft' : '' ?>">
                                            <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> w-32 ui-monospace font-mono"><?= e((string) (($resultado['codigo'] ?? '') !== '' ? $resultado['codigo'] : ('RA' . ($raIndex + 1)))) ?></td>
                                            <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>"><?= e((string) ($resultado['descripcion'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form id="competencia-form-<?= (int) ($comp['id'] ?? 0) ?>" method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/actualizar-competencia" data-inline-edit-form class="hidden">
                        <input type="hidden" name="programa_id" value="<?= (int) $programId ?>">
                        <input type="hidden" name="competencia_id" value="<?= (int) ($comp['id'] ?? 0) ?>">
                        <div class="mb-3 grid gap-3 md:grid-cols-2">
                            <label class="<?= e(ui_label_classes()) ?>">Código competencia
                                <input type="text" name="codigo" value="<?= e((string) ($comp['codigo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                            </label>
                            <label class="<?= e(ui_label_classes()) ?>">Nombre competencia
                                <input type="text" name="nombre" value="<?= e((string) ($comp['nombre'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                            </label>
                        </div>

                        <div class="overflow-hidden rounded-md">
                            <table class="<?= e(ui_table_classes()) ?>">
                                <thead>
                                <tr>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Código RA</th>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Resultado de aprendizaje</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if ($resultadosComp === []): ?>
                                    <tr>
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>" colspan="2">Sin resultados detectados para esta competencia.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($resultadosComp as $raIndex => $resultado): ?>
                                        <tr class="<?= $raIndex < (count($resultadosComp) - 1) ? 'border-b border-app-borderSoft' : '' ?>">
                                            <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> w-32">
                                                <input type="text" name="resultados[<?= (int) $raIndex ?>][codigo]" value="<?= e((string) (($resultado['codigo'] ?? '') !== '' ? $resultado['codigo'] : ('RA' . ($raIndex + 1)))) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                            </td>
                                            <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">
                                                <input type="text" name="resultados[<?= (int) $raIndex ?>][descripcion]" value="<?= e((string) ($resultado['descripcion'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
