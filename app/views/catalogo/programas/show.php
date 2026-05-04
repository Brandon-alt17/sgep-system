<?php
$parsed = (array) ($parsed ?? []);
$meta = (array) ($parsed['meta'] ?? []);
$competencias = (array) ($parsed['competencias'] ?? []);
$warnings = (array) ($parsed['warnings'] ?? []);
$saved = ((string) ($_GET['saved'] ?? '')) === '1';
$programId = (int) (($programa['id'] ?? 0));
$totalResultados = 0;
$totalHorasCompetencias = 0;
foreach ($competencias as $comp) {
    $totalResultados += count((array) ($comp['resultados'] ?? []));
    $horasComp = trim((string) ($comp['horas'] ?? ''));
    if ($horasComp !== '' && is_numeric($horasComp)) {
        $totalHorasCompetencias += (int) $horasComp;
    }
}
$programName = trim((string) ($meta['nombre'] ?? ''));
if ($programName === '') {
    $programName = 'Programa sin nombre detectado';
}
$programCode = trim((string) ($meta['codigo'] ?? ''));
$currentNivel = trim((string) ($meta['nivel'] ?? ''));
$autoEditCompetenciaId = (int) ($_GET['edit_competencia'] ?? 0);
$newCompetenciaDraft = ((string) ($_GET['new_competencia'] ?? '')) === '1';
$existingCompetenciaCodes = array_values(array_filter(array_map(
    static fn (array $comp): string => trim((string) ($comp['codigo'] ?? '')),
    $competencias
), static fn (string $code): bool => $code !== ''));
$toastKey = trim((string) ($_GET['toast'] ?? ''));
$toastMessage = match ($toastKey) {
    'programa_creado' => 'Programa creado correctamente.',
    'programa_actualizado' => 'Datos del programa actualizados.',
    'competencia_creada' => 'Competencia creada y lista para edición.',
    'competencia_actualizada' => 'Competencia actualizada correctamente.',
    'competencia_eliminada' => 'Competencia eliminada correctamente.',
    'competencia_invalidada' => 'Completa todos los campos de la competencia y sus RAEs antes de guardar.',
    'competencia_codigo_duplicado' => 'No se permiten códigos RAE duplicados dentro de la misma competencia.',
    'competencia_codigo_competencia_duplicado' => 'No se permiten códigos de competencia duplicados.',
    default => '',
};
$horasTotalesDisplay = $totalHorasCompetencias > 0
    ? (string) $totalHorasCompetencias
    : (string) ($meta['horas_total'] ?? '');
$errors = (array) ($errors ?? []);
?>

<?php if ($errors !== []): ?>
    <section class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
        <h3 class="m-0 mb-2 text-sm font-semibold">No se pudieron guardar los cambios</h3>
        <ul class="m-0 list-disc space-y-1 pl-5">
            <?php foreach ($errors as $err): ?>
                <li><?= e((string) $err) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/programas" aria-label="Volver al catálogo de programas">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5 "><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Detalle del programa</h2>
        <p class="m-0 text-sm text-app-muted">Vista de consulta con edición puntual por sección.</p>
    </div>
</section>

<section class="mt-4" data-inline-edit-root>
    <div>
        <div data-inline-view>
            <div class="flex items-start gap-2">
                <div>
                    <h3 class="m-0 text-2xl font-semibold text-app-text"><?= e($programName) ?></h3>
                    <p class="mt-1 text-sm text-app-muted">Código: <?= e($programCode !== '' ? $programCode : 'No detectado') ?></p>
                </div>
                <button type="button" class="<?= e(ui_button_icon_classes()) ?>" data-inline-edit-open aria-label="Editar título del programa">
                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('pencil') ?></span>
                </button>
            </div>
        </div>
    </div>
    <form id="programa-cabecera-form" method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/actualizar-datos" data-inline-edit-form class="hidden mt-3 max-w-3xl">
        <input type="hidden" name="programa_id" value="<?= (int) $programId ?>">
        <input type="hidden" name="modalidad" value="<?= e((string) ($meta['modalidad'] ?? '')) ?>">
        <input type="hidden" name="horas_lectiva" value="<?= e((string) ($meta['horas_lectiva'] ?? '')) ?>">
        <input type="hidden" name="horas_productiva" value="<?= e((string) ($meta['horas_productiva'] ?? '')) ?>">
        <div class="grid gap-3 max-w-xl w-full">
            <label class="<?= e(ui_label_classes()) ?>">Nombre del programa
                <input type="text" name="nombre" value="<?= e((string) ($meta['nombre'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
            </label>
            <label class="<?= e(ui_label_classes()) ?>">Código
                <input type="text" name="codigo" value="<?= e($programCode) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
            </label>
            <label class="<?= e(ui_label_classes()) ?>">Nivel
                <span class="<?= e(ui_select_wrapper_classes()) ?>">
                    <select name="nivel" class="<?= e(ui_select_classes()) ?>" data-inline-input>
                        <option value="Técnico" <?= $currentNivel === 'Técnico' ? 'selected' : '' ?>>Técnico</option>
                        <option value="Tecnólogo" <?= $currentNivel === 'Tecnólogo' ? 'selected' : '' ?>>Tecnólogo</option>
                    </select>
                    <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                    </span>
                </span>
            </label>
        </div>
        <div class="mt-3 flex items-center justify-between gap-3 max-w-xl w-full">
            <button type="submit" formaction="<?= e(APP_BASE_PATH) ?>/catalogo/programas/eliminar" formmethod="post" class="inline-flex items-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-[18px] py-2 text-sm font-medium text-rose-700 no-underline transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-confirm-modal data-confirm-title="Eliminar programa" data-confirm-message="¿Eliminar este programa? Esta acción no se puede deshacer.">
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                Eliminar programa
            </button>
            <div class="flex gap-2">
                <button type="submit" form="programa-cabecera-form" class="<?= e(ui_button_icon_classes()) ?> hidden border-green-600 bg-green-600 text-white hover:border-green-700 hover:bg-green-700 hover:text-white" data-inline-edit-save aria-label="Guardar título del programa">
                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                </button>
                <button type="button" class="<?= e(ui_button_icon_classes()) ?> hidden border-rose-600 bg-rose-600 text-white hover:border-rose-700 hover:bg-rose-700 hover:text-white" data-inline-edit-cancel aria-label="Cancelar edición de título">
                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                </button>
            </div>
        </div>
    </form>
</section>

<section class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <div class="flex items-center justify-between gap-2 h-full">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Nivel</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e($currentNivel !== '' ? $currentNivel : 'N/D') ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('book-open') ?></span>
        </div>
    </article>
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <div class="flex items-center justify-between gap-2 h-full">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Competencias</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) count($competencias)) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('target') ?></span>
        </div>
    </article>
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <div class="flex items-center justify-between gap-2 h-full">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Resultados de aprendizaje</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) $totalResultados) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('book') ?></span>
        </div>
    </article>
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <div class="flex items-center justify-between gap-2 h-full">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Horas totales</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) ($horasTotalesDisplay !== '' ? $horasTotalesDisplay . 'h' : 'N/D')) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('clock') ?></span>
        </div>
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

<?php if ($competencias !== []): ?>
    <?php partial('components/live_filter_search', [
        'items_target' => '#programa-competencias-live-items',
        'placeholder' => 'Buscar por competencia, RAE o código',
        'aria_label' => 'Buscar por competencia, RAE o código',
        'variant' => 'card',
        'class' => '',
    ]); ?>
<?php endif; ?>

<section class="<?= e(ui_card_classes()) ?> mb-6 mt-4 p-6">
    <div class="flex items-center justify-between gap-2">
        <h4 class="m-0 text-md font-semibold text-app-text">Competencias y Resultados de Aprendizaje</h4>
        <a href="<?= e(APP_BASE_PATH) ?>/catalogo/programas/ver?id=<?= (int) $programId ?>&new_competencia=1" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('plus') ?></span>
                Nueva competencia
        </a>
    </div>
    <?php if ($newCompetenciaDraft): ?>
        <article class="<?= e(ui_card_classes()) ?> mt-4 min-h-[96px] p-6 border-app-accent shadow-xsSoft">
            <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/agregar-competencia" data-new-competencia-form data-existing-competencia-codes="<?= e((string) json_encode($existingCompetenciaCodes, JSON_UNESCAPED_UNICODE)) ?>">
                <input type="hidden" name="programa_id" value="<?= (int) $programId ?>">
                <div class="mb-3 w-full grid grid-cols-10 gap-3">
                    <label class="<?= e(ui_label_classes()) ?> col-span-10">Nombre competencia
                        <input type="text" name="nombre" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                    <label class="<?= e(ui_label_classes()) ?> col-span-7">Código competencia
                        <input type="text" name="codigo" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                    <label class="<?= e(ui_label_classes()) ?> col-span-3">Horas competencia
                        <input type="number" min="0" name="horas" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                </div>
                <div class="mt-3 border-t border-app-borderSoft pt-6 flex justify-end">
                    <button type="button" class="<?= e(ui_button_small_classes()) ?> items-center justify-center gap-2" data-new-competencia-add-rae>
                        <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('plus') ?></span>
                        Nuevo RAE
                    </button>
                </div>
                <div class="overflow-hidden">
                    <table class="<?= e(ui_table_classes()) ?>">
                        <thead>
                        <tr>
                            <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Código RAE</th>
                            <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Resultado de aprendizaje</th>
                            <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle w-10">Acción</th>
                        </tr>
                        </thead>
                        <tbody data-new-competencia-raes-body>
                            <tr class="border-b border-app-borderSoft">
                                <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> w-32">
                                    <input type="text" name="resultados[0][codigo]" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                                </td>
                                <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">
                                    <textarea name="resultados[0][descripcion]" rows="1" class="<?= e(ui_input_classes()) ?> min-h-[2.5rem] resize-none overflow-hidden bg-white" data-auto-resize-textarea autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
                                </td>
                                <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">
                                    <button type="button" class="<?= e(ui_button_icon_classes()) ?> border-rose-300 bg-rose-50 text-rose-700 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-new-competencia-remove-rae aria-label="Eliminar RAE">
                                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 border-t border-app-borderSoft pt-3 flex items-center justify-end gap-2">
                    <a href="<?= e(APP_BASE_PATH) ?>/catalogo/programas/ver?id=<?= (int) $programId ?>" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                        Cancelar
                    </a>
                    <button type="submit" class="<?= e(ui_button_icon_classes()) ?> border-green-600 bg-green-600 text-white hover:border-green-700 hover:bg-green-700 hover:text-white" aria-label="Guardar nueva competencia">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                    </button>
                </div>
            </form>
        </article>
    <?php endif; ?>
    <?php if ($competencias === []): ?>
        <div class="mt-6 rounded-md border border-app-border bg-app-panelSubtle p-4 text-sm text-app-muted">No se detectaron competencias automáticamente.</div>
    <?php else: ?>
        <div id="programa-competencias-live-items" class="mt-6 space-y-6" data-live-filter-items>
            <?php foreach ($competencias as $compIndex => $comp): ?>
                <?php
                $resultadosComp = (array) ($comp['resultados'] ?? []);
                $searchParts = [
                    (string) ($comp['codigo'] ?? ''),
                    (string) ($comp['nombre'] ?? ''),
                    (string) ($comp['horas'] ?? ''),
                ];
                foreach ($resultadosComp as $raIndex => $resultado) {
                    $searchParts[] = 'RA' . ($raIndex + 1);
                    $searchParts[] = (string) ($resultado['descripcion'] ?? '');
                    $searchParts[] = (string) ($resultado['codigo'] ?? '');
                }
                $searchText = trim(implode(' ', array_filter(array_map(
                    static fn ($value): string => trim((string) $value),
                    $searchParts
                ))));
                ?>
                <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6 transition-[border-color,box-shadow] duration-300 ease-out motion-reduce:transition-none" data-inline-edit-root data-live-filter-item data-live-filter-text="<?= e($searchText) ?>" <?= $autoEditCompetenciaId === (int) ($comp['id'] ?? 0) ? 'data-inline-auto-open="1"' : '' ?>>
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div data-inline-header>
                            <?php if (trim((string) ($comp['codigo'] ?? '')) !== ''): ?>
                                <p class="m-0 text-xs font-semibold tracking-wide text-app-muted ui-monospace font-mono"><?= e((string) $comp['codigo']) ?></p>
                            <?php endif; ?>
                            <p class="m-0 mt-1 text-lg font-semibold text-app-text"><?= e((string) (($comp['nombre'] ?? '') !== '' ? $comp['nombre'] : 'Competencia ' . ($compIndex + 1))) ?></p>
                        </div>
                        <div class="ml-auto flex items-center gap-2">
                            <?php if (trim((string) ($comp['horas'] ?? '')) !== ''): ?>
                                <span class="inline-flex items-center rounded-full bg-app-accent px-2.5 py-1 text-xs font-semibold text-app-textOnBrand" data-inline-header><?= e((string) $comp['horas']) ?>h</span>
                            <?php endif; ?>
                            <button type="button" class="<?= e(ui_button_icon_classes()) ?>" data-inline-edit-open aria-label="Editar competencia">
                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('pencil') ?></span>
                            </button>
                        </div>
                    </div>

                    <div data-inline-view>
                        <div class="overflow-hidden rounded-md">
                            <table class="<?= e(ui_table_classes()) ?>">
                                <thead>
                                <tr>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Código RAE</th>
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

                    <div data-inline-competencia-drawer aria-expanded="false">
                        <div data-inline-competencia-drawer-inner>
                    <form id="competencia-form-<?= (int) ($comp['id'] ?? 0) ?>" method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/actualizar-competencia" data-inline-edit-form inert>
                        <input type="hidden" name="programa_id" value="<?= (int) $programId ?>">
                        <input type="hidden" name="competencia_id" value="<?= (int) ($comp['id'] ?? 0) ?>">
                        <div class="mb-3 w-full grid grid-cols-10 gap-3">
                            <label class="<?= e(ui_label_classes()) ?> col-span-10">Nombre competencia
                                <input type="text" name="nombre" value="<?= e((string) ($comp['nombre'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                            </label>
                            <label class="<?= e(ui_label_classes()) ?> col-span-7">Código competencia
                                <input type="text" name="codigo" value="<?= e((string) ($comp['codigo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                            </label>
                            <label class="<?= e(ui_label_classes()) ?> col-span-3">Horas competencia
                                <input type="number" min="0" name="horas" value="<?= e((string) ($comp['horas'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                            </label>
                        </div>
                        <div class="mt-3 border-t border-app-borderSoft pt-6 flex justify-end">
                            <button type="button" class="<?= e(ui_button_small_classes()) ?> items-center justify-center gap-2" data-inline-add-rae>
                                <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('plus') ?></span>
                                Nuevo RAE
                            </button>
                        </div>
                        <div class="overflow-hidden">
                            <table class="<?= e(ui_table_classes()) ?>">
                                <thead>
                                <tr>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Código RAE</th>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Resultado de aprendizaje</th>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle w-10">Acción</th>
                                </tr>
                                </thead>
                                <tbody data-inline-raes-body>
                                <?php if ($resultadosComp === []): ?>
                                    <tr class="border-b border-app-borderSoft">
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> w-32">
                                            <input type="text" name="resultados[0][codigo]" value="" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                        </td>
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">
                                            <textarea name="resultados[0][descripcion]" rows="1" class="<?= e(ui_input_classes()) ?> min-h-[2.5rem] resize-none overflow-hidden bg-white" data-inline-input data-auto-resize-textarea autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
                                        </td>
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">
                                            <button type="button" class="<?= e(ui_button_icon_classes()) ?> border-rose-300 bg-rose-50 text-rose-700 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-inline-remove-rae aria-label="Eliminar RAE">
                                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($resultadosComp as $raIndex => $resultado): ?>
                                        <tr class="<?= $raIndex < (count($resultadosComp) - 1) ? '' : '' ?>">
                                            <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> w-32">
                                                <input type="text" name="resultados[<?= (int) $raIndex ?>][codigo]" value="<?= e((string) (($resultado['codigo'] ?? '') !== '' ? $resultado['codigo'] : ('RA' . ($raIndex + 1)))) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                            </td>
                                            <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">
                                                <textarea name="resultados[<?= (int) $raIndex ?>][descripcion]" rows="1" class="<?= e(ui_input_classes()) ?> min-h-[2.5rem] resize-none overflow-hidden bg-white" data-inline-input data-auto-resize-textarea autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false"><?= e((string) ($resultado['descripcion'] ?? '')) ?></textarea>
                                            </td>
                                            <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>">
                                                <button type="button" class="<?= e(ui_button_icon_classes()) ?> border-rose-300 bg-rose-50 text-rose-700 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-inline-remove-rae aria-label="Eliminar RAE">
                                                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 border-t border-app-borderSoft pt-3 flex items-center justify-between gap-3">
                            <button type="submit" formaction="<?= e(APP_BASE_PATH) ?>/catalogo/programas/eliminar-competencia" formmethod="post" class="inline-flex items-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-[18px] py-2 text-sm font-medium text-rose-700 no-underline transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-confirm-modal data-confirm-title="Eliminar competencia" data-confirm-message="¿Eliminar esta competencia?">
                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                                Eliminar competencia
                            </button>
                            <div class="flex gap-2">
                                <button type="submit" form="competencia-form-<?= (int) ($comp['id'] ?? 0) ?>" class="<?= e(ui_button_icon_classes()) ?> hidden border-green-600 bg-green-600 text-white hover:border-green-700 hover:bg-green-700 hover:text-white" data-inline-edit-save aria-label="Guardar competencia">
                                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                                </button>
                                <button type="button" class="<?= e(ui_button_icon_classes()) ?> hidden border-rose-600 bg-rose-600 text-white hover:border-rose-700 hover:bg-rose-700 hover:text-white" data-inline-edit-cancel aria-label="Cancelar edición de competencia">
                                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                                </button>
                            </div>
                        </div>
                    </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php partial('components/toast', ['message' => $toastMessage]); ?>
