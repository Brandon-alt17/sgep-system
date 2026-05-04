<?php
declare(strict_types=1);

$parsed = (array) ($parsed ?? []);
$meta = (array) ($parsed['meta'] ?? []);
$competencias = (array) ($parsed['competencias'] ?? []);
$errors = (array) ($errors ?? []);

$totalResultados = 0;
$totalHorasCompetencias = 0;
foreach ($competencias as $comp) {
    $totalResultados += count((array) ($comp['resultados'] ?? []));
    $h = trim((string) ($comp['horas'] ?? ''));
    if ($h !== '' && ctype_digit($h)) {
        $totalHorasCompetencias += (int) $h;
    }
}
$competenciasCount = count($competencias);
$currentNivel = trim((string) ($meta['nivel'] ?? ''));
$nivelCardText = $currentNivel !== '' ? $currentNivel : 'Nivel no seleccionado';
$horasTotalesCard = $totalHorasCompetencias === 0 ? '0' : ($totalHorasCompetencias . 'h');
?>

<?php if ($errors !== []): ?>
    <section class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
        <h3 class="m-0 mb-2 text-sm font-semibold">Revisa los siguientes puntos antes de guardar</h3>
        <ul class="m-0 list-disc space-y-1 pl-5">
            <?php foreach ($errors as $err): ?>
                <li><?= e((string) $err) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 shrink-0 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/programas" aria-label="Volver al catálogo de programas">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex min-w-0 flex-1 flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Nuevo programa manual</h2>
        <p class="m-0 text-sm text-app-muted">Define los datos del programa y las competencias con sus resultados de aprendizaje. Al guardar se creará el registro y podrás seguir editándolo en la vista de detalle.</p>
    </div>
</section>

<form id="programa-nuevo-form" method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/guardar" class="pb-28" data-programa-editor autocomplete="off">
    <input type="hidden" name="programa_id" value="0">

    <section class="<?= e(ui_card_classes()) ?> mb-6 mt-4 w-full max-w-md min-h-[96px] p-6">
        <h4 class="m-0 text-md font-semibold text-app-text">Datos del programa</h4>
        <div class="mt-4 min-w-0">
            <div class="grid w-full gap-4">
                <label class="<?= e(ui_label_classes()) ?>">Nombre del programa <span class="text-rose-600">*</span>
                    <input type="text" name="nombre" value="<?= e((string) ($meta['nombre'] ?? '')) ?>" required maxlength="220" class="<?= e(ui_input_classes()) ?> bg-white" placeholder="Ej. Técnico en sistemas">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Código <span class="text-rose-600">*</span>
                    <input type="text" name="codigo" value="<?= e((string) ($meta['codigo'] ?? '')) ?>" required maxlength="40" class="<?= e(ui_input_classes()) ?> bg-white ui-monospace font-mono" placeholder="Código del programa">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Nivel <span class="text-rose-600">*</span>
                    <span class="<?= e(ui_select_wrapper_classes()) ?>">
                        <select id="programa-nivel-select" name="nivel" class="<?= e(ui_select_classes()) ?>" required data-programa-nivel-select>
                            <option value="" disabled<?= $currentNivel === '' ? ' selected' : '' ?>>Selecciona…</option>
                            <option value="Técnico"<?= $currentNivel === 'Técnico' ? ' selected' : '' ?>>Técnico</option>
                            <option value="Tecnólogo"<?= $currentNivel === 'Tecnólogo' ? ' selected' : '' ?>>Tecnólogo</option>
                            <option value="Auxiliar"<?= $currentNivel === 'Auxiliar' ? ' selected' : '' ?>>Auxiliar</option>
                        </select>
                        <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    <section class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
            <div class="flex h-full items-center justify-between gap-2">
                <div>
                    <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Nivel</p>
                    <p class="mt-2 text-1xl font-semibold text-app-text" data-programa-nivel-display><?= e($nivelCardText) ?></p>
                </div>
                <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('book-open') ?></span>
            </div>
        </article>
        <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
            <div class="flex h-full items-center justify-between gap-2">
                <div>
                    <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Competencias</p>
                    <p class="mt-2 text-1xl font-semibold text-app-text" data-programa-total-competencias><?= e((string) $competenciasCount) ?></p>
                </div>
                <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('target') ?></span>
            </div>
        </article>
        <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
            <div class="flex h-full items-center justify-between gap-2">
                <div>
                    <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Resultados de aprendizaje</p>
                    <p class="mt-2 text-1xl font-semibold text-app-text" data-programa-total-raes><?= e((string) $totalResultados) ?></p>
                </div>
                <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('book') ?></span>
            </div>
        </article>
        <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
            <div class="flex h-full items-center justify-between gap-2">
                <div>
                    <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Horas totales</p>
                    <p class="mt-2 text-1xl font-semibold text-app-text" data-programa-total-horas><?= e($horasTotalesCard) ?></p>
                </div>
                <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('clock') ?></span>
            </div>
        </article>
    </section>

    <section class="<?= e(ui_card_classes()) ?> mb-6 mt-4 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h4 class="m-0 text-md font-semibold text-app-text">Competencias y Resultados de Aprendizaje</h4>
            <button type="button" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2" data-add-competencia>
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('plus') ?></span>
                Añadir competencia
            </button>
        </div>
        <p class="mt-2 text-sm text-app-muted">Cada competencia debe tener nombre, código, horas y al menos un RAE con código y descripción.</p>

        <div class="mt-6 space-y-6" data-competencias-container>
            <?php if ($competencias !== []): ?>
                <?php foreach ($competencias as $cIdx => $comp): ?>
                    <?php
                    $resultadosComp = (array) ($comp['resultados'] ?? []);
                    if ($resultadosComp === []) {
                        $resultadosComp = [['codigo' => '', 'descripcion' => '']];
                    }
                    ?>
                    <article class="rounded-[10px] border border-app-border bg-app-panelSubtle p-6 shadow-xsSoft min-h-[96px] transition-[border-color,box-shadow] duration-300 ease-out motion-reduce:transition-none" data-competencia-item>
                        <div class="mb-3 w-full grid grid-cols-1 gap-3 md:grid-cols-10 md:gap-3">
                            <label class="<?= e(ui_label_classes()) ?> md:col-span-10">Nombre competencia <span class="text-rose-600">*</span>
                                <input type="text" name="competencias[<?= (int) $cIdx ?>][nombre]" value="<?= e((string) ($comp['nombre'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?> bg-white" data-field="competencia-nombre">
                            </label>
                            <label class="<?= e(ui_label_classes()) ?> md:col-span-7">Código competencia <span class="text-rose-600">*</span>
                                <input type="text" name="competencias[<?= (int) $cIdx ?>][codigo]" value="<?= e((string) ($comp['codigo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?> bg-white ui-monospace font-mono" data-field="competencia-codigo">
                            </label>
                            <label class="<?= e(ui_label_classes()) ?> md:col-span-3">Horas competencia <span class="text-rose-600">*</span>
                                <input type="number" name="competencias[<?= (int) $cIdx ?>][horas]" value="<?= e((string) ($comp['horas'] ?? '')) ?>" min="0" step="1" inputmode="numeric" class="<?= e(ui_input_classes()) ?> bg-white" data-field="competencia-horas" placeholder="0">
                            </label>
                        </div>
                        <div class="mt-3 border-t border-app-borderSoft pt-6 flex justify-end">
                            <button type="button" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center justify-center gap-2" data-add-rae>
                                <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('plus') ?></span>
                                Nuevo RAE
                            </button>
                        </div>
                        <div class="mt-3 overflow-hidden">
                            <table class="<?= e(ui_table_classes()) ?>">
                                <thead>
                                <tr>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Código RAE</th>
                                    <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Resultado de aprendizaje</th>
                                    <th class="h-[50px] w-10 px-2 text-left text-sm font-semibold text-app-muted align-middle">Acción</th>
                                </tr>
                                </thead>
                                <tbody data-raes-container>
                                <?php foreach ($resultadosComp as $rIdx => $res): ?>
                                    <tr class="border-b border-app-borderSoft" data-rae-item>
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> w-32 align-top">
                                            <input type="text" name="competencias[<?= (int) $cIdx ?>][resultados][<?= (int) $rIdx ?>][codigo]" value="<?= e((string) ($res['codigo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?> bg-white" placeholder="Código RAE" data-field="rae-codigo">
                                        </td>
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> align-top">
                                            <textarea rows="1" name="competencias[<?= (int) $cIdx ?>][resultados][<?= (int) $rIdx ?>][descripcion]" class="<?= e(ui_input_classes()) ?> min-h-[2.5rem] resize-none overflow-hidden bg-white" placeholder="Descripción del RAE" data-field="rae-descripcion" data-auto-resize-textarea><?= e((string) ($res['descripcion'] ?? '')) ?></textarea>
                                        </td>
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> align-top">
                                            <button type="button" class="<?= e(ui_button_icon_classes()) ?> border-rose-300 bg-rose-50 text-rose-700 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-remove-rae aria-label="Eliminar RAE">
                                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 border-t border-app-borderSoft pt-3 flex justify-end">
                            <button type="button" class="inline-flex items-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-[18px] py-2 text-sm font-medium text-rose-700 no-underline transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-remove-competencia>
                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                                Eliminar competencia
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</form>

<div class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] border-t border-app-border bg-app-panel/95 shadow-[0_-4px_24px_rgba(16,24,40,0.08)] backdrop-blur-sm">
    <div class="pointer-events-auto flex min-h-[56px] w-full items-center justify-end gap-3 px-4 py-3 md:px-6">
        <a href="<?= e(APP_BASE_PATH) ?>/catalogo/programas" class="<?= e(ui_button_small_classes()) ?> inline-flex h-11 min-w-[11rem] shrink-0 items-center justify-center px-5"><?= e('Volver al catálogo') ?></a>
        <button type="submit" form="programa-nuevo-form" class="<?= e(ui_button_primary_classes()) ?> inline-flex h-11 min-w-[12rem] shrink-0 items-center justify-center px-5 text-app-textOnBrand"><?= e('Guardar programa') ?></button>
    </div>
</div>
