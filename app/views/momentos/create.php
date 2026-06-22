<?php
declare(strict_types=1);

$programaContenido = (array) ($programaContenido ?? []);
$tipo = (string) $tipo;
$aprendiz = (array) ($aprendiz ?? []);

$volverUrl = !empty($aprendiz['id'])
    ? APP_BASE_PATH . '/aprendices/show?id=' . (int) $aprendiz['id']
    : APP_BASE_PATH . '/aprendices';

$momentoTitulo = match ($tipo) {
    'M1' => 'Momento 1 — Planeación',
    'M2' => 'Momento 2 — Seguimiento',
    'M3' => 'Momento 3 — Evaluación final',
    'EX' => 'Momento extraordinario',
    default => 'Momento',
};

$pageTitle = ($modoEdicion ?? false) ? ('Editar: ' . $momentoTitulo) : ('Registrar: ' . $momentoTitulo);

$momentoSectionHeading = static function (string $icon, string $title): void {
    ?>
    <h3 class="font-app mb-4 mt-0 flex items-center gap-2.5 text-base font-semibold leading-snug text-app-text">
        <span class="inline-flex h-5 w-5 shrink-0 text-app-accent [&_svg]:h-5 [&_svg]:w-5" aria-hidden="true"><?= ui_icon($icon) ?></span>
        <?= e($title) ?>
    </h3>
    <?php
};

$ic = e(ui_input_classes());
$lc = e(ui_label_classes());
$grid = 'grid gap-5 md:grid-cols-3 md:gap-x-6 md:gap-y-6';
$ta = e(ui_input_classes()) . ' min-w-0 max-w-full min-h-24 resize-y py-2 leading-snug';

$m1CompetenciaComboboxOptions = [];
$m1ResultadoComboboxOptions = [];
foreach ($programaContenido as $comp) {
    $nom = \App\Helpers\Normalizer::normalizeSentenceCase(trim((string) ($comp['nombre'] ?? '')));
    if ($nom !== '') {
        $m1CompetenciaComboboxOptions[] = [
            'value' => $nom,
            'label' => $nom,
            'search' => $nom,
        ];
    }
    foreach ((array) ($comp['resultados'] ?? []) as $resultado) {
        $desc = \App\Helpers\Normalizer::normalizeSentenceCase(trim((string) ($resultado['descripcion'] ?? '')));
        if ($desc === '') {
            continue;
        }
        $m1ResultadoComboboxOptions[] = [
            'value' => $desc,
            'label' => $desc,
            'search' => $desc,
        ];
    }
}
$factorObsTextareaClass = e(ui_input_classes()) . ' mt-0 min-h-[72px] min-w-0 max-w-full resize-y py-2 text-sm leading-snug';
$compromisosHeaderClass = in_array($tipo, ['M2', 'EX'], true) ? ' font-semibold' : '';

$renderDiligenciamientoCard = static function () use ($momentoSectionHeading, $grid, $lc, $ic, $momento, $valueFrom, $maxShortText): void {
    $modalidadD = $valueFrom($momento, 'modalidad_diligenciamiento', '');
    ?>
    <section class="<?= e(ui_card_classes()) ?> !p-6">
        <?php $momentoSectionHeading('map-pin', 'Diligenciamiento'); ?>
        <div class="<?= e($grid) ?>">
            <label class="<?= $lc ?> min-w-0 self-start">Ciudad diligenciamiento
                <input type="text" name="ciudad_diligenciamiento" maxlength="<?= $maxShortText ?>" value="<?= e($valueFrom($momento, 'ciudad_diligenciamiento')) ?>" class="<?= $ic ?> mt-1.5">
            </label>
            <label class="<?= $lc ?> min-w-0 self-start">Fecha diligenciamiento
                <input type="text" name="fecha_diligenciamiento" value="<?= e(date_iso_to_dmY($valueFrom($momento, 'fecha_diligenciamiento'))) ?>" data-date-input="dmy" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" class="<?= $ic ?> mt-1.5">
            </label>
            <label class="<?= $lc ?> min-w-0 self-start">Modalidad diligenciamiento
                <span class="<?= e(ui_select_wrapper_classes()) ?> mt-1.5">
                    <select name="modalidad_diligenciamiento" class="<?= e(ui_select_classes()) ?>">
                        <option value="" <?= $modalidadD === '' ? 'selected' : '' ?>>Sin definir</option>
                        <option value="Presencial" <?= $modalidadD === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                        <option value="Virtual" <?= $modalidadD === 'Virtual' ? 'selected' : '' ?>>Virtual</option>
                    </select>
                    <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                    </span>
                </span>
            </label>
        </div>
    </section>
    <?php
};
?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e($volverUrl) ?>" aria-label="Volver al perfil del aprendiz">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text"><?= e($pageTitle) ?></h2>
        <p class="m-0 text-sm text-app-muted">
            Diligencie el momento según el formato F-023. Las fechas usan día, mes y año con barras (dd/mm/aaaa).
        </p>
    </div>
</section>

<?php if (empty($aprendiz)): ?>
    <section class="<?= e(ui_warning_card_classes()) ?>">Aprendiz no encontrado.</section>
<?php else: ?>

    <form id="momento-form" method="post" action="<?= e($accion) ?>" class="space-y-6 pb-28 md:space-y-7">
        <p class="sr-only">Las fechas usan formato día, mes y año con barras (dd/mm/aaaa).</p>
        <?php if ($modoEdicion): ?>
            <input type="hidden" name="id" value="<?= (int) ($momentoExistente['id'] ?? 0) ?>">
        <?php endif; ?>
        <input type="hidden" name="aprendiz_id" value="<?= (int) $aprendiz['id'] ?>">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

        <?php $renderDiligenciamientoCard(); ?>

        <?php if ($tipo === 'M1'): ?>
            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('calendar', 'Fechas y datos de etapa productiva'); ?>
                <div class="<?= e($grid) ?>">
                    <label class="<?= $lc ?> min-w-0 self-start">Fecha inicio etapa productiva
                        <input type="text" name="fecha_inicio_etapa" value="<?= e(date_iso_to_dmY($valueFrom($momento, 'fecha_inicio_etapa'))) ?>" data-date-input="dmy" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" class="<?= $ic ?> mt-1.5">
                    </label>
                    <label class="<?= $lc ?> min-w-0 self-start">Fecha fin etapa productiva
                        <input type="text" name="fecha_fin_etapa" value="<?= e(date_iso_to_dmY($valueFrom($momento, 'fecha_fin_etapa'))) ?>" data-date-input="dmy" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" class="<?= $ic ?> mt-1.5">
                    </label>
                    <label class="<?= $lc ?> min-w-0 self-start">Fecha afiliación ARL
                        <input type="text" name="fecha_arl" value="<?= e(date_iso_to_dmY($valueFrom($momento, 'fecha_arl'))) ?>" data-date-input="dmy" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" class="<?= $ic ?> mt-1.5">
                    </label>
                    <label class="<?= $lc ?> min-w-0 self-start">Número póliza ARL
                        <input type="text" name="numero_poliza_arl" maxlength="<?= $maxShortText ?>" value="<?= e($valueFrom($momento, 'numero_poliza_arl')) ?>" class="<?= $ic ?> mt-1.5">
                    </label>
                    <label class="<?= $lc ?> min-w-0 self-start">Horario
                        <input type="text" name="horario" maxlength="<?= $maxShortText ?>" value="<?= e($valueFrom($momento, 'horario')) ?>" placeholder="Diurno/nocturno, días y hora" class="<?= $ic ?> mt-1.5">
                    </label>
                    <label class="<?= $lc ?> min-w-0 self-start md:col-span-3">Enlace grabación momento 1
                        <input type="text" name="enlace_grabacion" maxlength="<?= $maxUrl ?>" value="<?= e($valueFrom($momento, 'enlace_grabacion')) ?>" class="<?= $ic ?> mt-1.5">
                    </label>
                </div>
            </section>

            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('file', 'Contenidos de planeación'); ?>
                <div class="grid gap-5 md:grid-cols-1 md:gap-y-6">
                    <div class="min-w-0">
                        <span class="<?= $lc ?> block">Competencias a desarrollar</span>
                        <?php if ($m1CompetenciaComboboxOptions !== []): ?>
                            <div class="mb-1.5 mt-1.5" data-m1-programa-append-root data-m1-programa-append-target="#momento-m1-competencias">
                                <?php partial('components/combobox', [
                                    'name' => '_m1_competencia_combobox',
                                    'placeholder' => 'Buscar competencia del programa…',
                                    'value' => '',
                                    'options' => $m1CompetenciaComboboxOptions,
                                ]); ?>
                            </div>
                        <?php endif; ?>
                        <textarea id="momento-m1-competencias" name="m1_competencias" maxlength="<?= $maxM1Competencias ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm1_competencias')) ?></textarea>
                    </div>
                    <div class="min-w-0">
                        <span class="<?= $lc ?> block">Resultados de aprendizaje</span>
                        <?php if ($m1ResultadoComboboxOptions !== []): ?>
                            <div class="mb-1.5 mt-1.5" data-m1-programa-append-root data-m1-programa-append-target="#momento-m1-resultados">
                                <?php partial('components/combobox', [
                                    'name' => '_m1_resultado_combobox',
                                    'placeholder' => 'Buscar resultado de aprendizaje…',
                                    'value' => '',
                                    'options' => $m1ResultadoComboboxOptions,
                                ]); ?>
                            </div>
                        <?php endif; ?>
                        <textarea id="momento-m1-resultados" name="m1_resultados" maxlength="<?= $maxM1Resultados ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm1_resultados')) ?></textarea>
                    </div>
                    <label class="<?= $lc ?> min-w-0">Actividades a desarrollar
                        <textarea name="m1_actividades" maxlength="<?= $maxM1Actividades ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm1_actividades')) ?></textarea>
                    </label>
                    <label class="<?= $lc ?> min-w-0">Evidencias de aprendizaje
                        <textarea name="m1_evidencias" maxlength="<?= $maxM1Evidencias ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm1_evidencias')) ?></textarea>
                    </label>
                    <label class="<?= $lc ?> min-w-0 font-semibold">Observaciones adicionales
                        <textarea name="m1_observaciones_adicionales" rows="2" data-max="<?= $maxM1ObsAdicionales ?>" data-max-lines="2" maxlength="<?= $maxM1ObsAdicionales ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm1_observaciones_adicionales')) ?></textarea>
                        <p class="mt-1 text-right text-xs text-app-muted" data-char-counter aria-live="polite"></p>
                    </label>
                </div>
            </section>
        <?php else: ?>
            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('calendar', 'Fechas'); ?>
                <div class="<?= e($grid) ?>">
                    <label class="<?= $lc ?> min-w-0 self-start">Fecha inicio etapa productiva
                        <input type="text" name="fecha_inicio_etapa" value="<?= e(date_iso_to_dmY($valueFrom($momento, 'fecha_inicio_etapa'))) ?>" data-date-input="dmy" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" class="<?= $ic ?> mt-1.5">
                    </label>
                    <label class="<?= $lc ?> min-w-0 self-start">
                        <?= $tipo === 'M2' ? 'Fecha momento de seguimiento' : 'Fecha fin etapa de ejecución' ?>
                        <input type="text" name="fecha_visita" value="<?= e(date_iso_to_dmY($valueFrom($momento, 'fecha_visita'))) ?>" data-date-input="dmy" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" class="<?= $ic ?> mt-1.5">
                    </label>
                    <?php if ($tipo === 'M3'): ?>
                        <label class="<?= $lc ?> min-w-0 self-start">Número de visitas realizadas
                            <input type="number" min="0" name="numero_visitas_realizadas" value="<?= e($valueFrom($momento, 'numero_visitas_realizadas')) ?>" class="<?= $ic ?> mt-1.5">
                        </label>
                    <?php endif; ?>
                    <?php if ($tipo === 'M2' || $tipo === 'M3' || $tipo === 'EX'): ?>
                        <label class="<?= $lc ?> min-w-0 self-start md:col-span-3">Enlace de grabación
                            <input type="text" name="enlace_grabacion" maxlength="<?= $maxUrl ?>" value="<?= e($valueFrom($momento, 'enlace_grabacion')) ?>" class="<?= $ic ?> mt-1.5" placeholder="https://…">
                            </label>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tipo === 'EX'): ?>
            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('clock', 'Próxima visita'); ?>
                <div class="<?= e($grid) ?>">
                    <label class="<?= $lc ?> min-w-0 self-start md:max-w-md">Próxima visita
                        <input type="text" name="proxima_visita" value="<?= e(date_iso_to_dmY($valueFrom($momento, 'proxima_visita', (string) ($aprendiz['proxima_visita'] ?? '')))) ?>" data-date-input="dmy" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" class="<?= $ic ?> mt-1.5">
                    </label>
                </div>
            </section>
        <?php endif; ?>

        <?php if (in_array($tipo, ['M2', 'M3', 'EX'], true)): ?>
            <?php $factores = require base_path('config/factores.php'); ?>

            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('briefcase', 'B. Factores técnicos'); ?>
                <div class="overflow-x-auto -mx-1 px-1 md:mx-0 md:px-0">
                    <div class="min-w-[36rem] rounded-lg border border-app-border bg-white md:min-w-0">
                        <div class="grid grid-cols-[minmax(8rem,1fr)_10.5rem_minmax(10rem,1fr)] gap-2 border-b border-app-border px-3 py-2 text-xs font-medium text-app-muted md:gap-3">
                            <span>Factor</span>
                            <span>Valoración</span>
                            <span class="min-w-0 leading-snug<?= e($compromisosHeaderClass) ?>">Observaciones / Compromisos de mejora</span>
                        </div>
                        <?php foreach ($factores['tecnicos'] as $idx => $nombre): ?>
                            <?php $valF = $factorValoracionPorIndice[$idx] ?? 'PM'; ?>
                            <div class="grid grid-cols-1 gap-3 border-b border-app-border px-3 py-3 last:border-b-0 md:grid-cols-[minmax(8rem,1fr)_10.5rem_minmax(10rem,1fr)] md:items-start md:gap-3">
                                <input type="hidden" name="factores[<?= $idx ?>][tipo_factor]" value="tecnico">
                                <input type="hidden" name="factores[<?= $idx ?>][nombre_factor]" value="<?= e($nombre) ?>">
                                <div class="text-sm font-semibold text-app-text md:pt-0.5"><?= e($nombre) ?></div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-app-text md:flex-col md:items-start md:gap-2">
                                    <span class="text-xs text-app-muted md:hidden">Valoración</span>
                                    <label class="inline-flex cursor-pointer items-center gap-2 whitespace-nowrap">
                                        <input type="radio" name="factores[<?= $idx ?>][valoracion]" value="S" <?= $valF === 'S' ? 'checked' : '' ?> required class="h-4 w-4 shrink-0">
                                        Satisfactorio
                                    </label>
                                    <label class="inline-flex cursor-pointer items-center gap-2 whitespace-nowrap">
                                        <input type="radio" name="factores[<?= $idx ?>][valoracion]" value="PM" <?= $valF === 'PM' ? 'checked' : '' ?> required class="h-4 w-4 shrink-0">
                                        Por mejorar
                                    </label>
                                </div>
                                <div class="min-w-0">
                                    <span class="mb-1 block text-xs text-app-muted md:hidden<?= e($compromisosHeaderClass) ?>">Observaciones / Compromisos de mejora</span>
                                    <textarea name="factores[<?= $idx ?>][observacion]" rows="2" data-max="<?= $maxCompromisos ?>" data-max-lines="2" maxlength="<?= $maxCompromisos ?>" placeholder="Observaciones..." class="<?= $factorObsTextareaClass ?> w-full"><?= e((string) ($factorObsPorIndice[$idx] ?? '')) ?></textarea>
                                    <p class="mt-1 text-right text-xs text-app-muted" data-char-counter aria-live="polite"></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('users', 'C. Factores actitudinales'); ?>
                <div class="overflow-x-auto -mx-1 px-1 md:mx-0 md:px-0">
                    <div class="min-w-[36rem] rounded-lg border border-app-border bg-white md:min-w-0">
                        <div class="grid grid-cols-[minmax(8rem,1fr)_10.5rem_minmax(10rem,1fr)] gap-2 border-b border-app-border px-3 py-2 text-xs font-medium text-app-muted md:gap-3">
                            <span>Factor</span>
                            <span>Valoración</span>
                            <span class="min-w-0 leading-snug<?= e($compromisosHeaderClass) ?>">Observaciones / Compromisos de mejora</span>
                        </div>
                        <?php foreach ($factores['actitudinales'] as $offset => $nombre): ?>
                            <?php $i = $offset + 8; ?>
                            <?php $valA = $factorValoracionPorIndice[$i] ?? 'PM'; ?>
                            <div class="grid grid-cols-1 gap-3 border-b border-app-border px-3 py-3 last:border-b-0 md:grid-cols-[minmax(8rem,1fr)_10.5rem_minmax(10rem,1fr)] md:items-start md:gap-3">
                                <input type="hidden" name="factores[<?= $i ?>][tipo_factor]" value="actitudinal">
                                <input type="hidden" name="factores[<?= $i ?>][nombre_factor]" value="<?= e($nombre) ?>">
                                <div class="text-sm font-semibold text-app-text md:pt-0.5"><?= e($nombre) ?></div>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-app-text md:flex-col md:items-start md:gap-2">
                                    <span class="text-xs text-app-muted md:hidden">Valoración</span>
                                    <label class="inline-flex cursor-pointer items-center gap-2 whitespace-nowrap">
                                        <input type="radio" name="factores[<?= $i ?>][valoracion]" value="S" <?= $valA === 'S' ? 'checked' : '' ?> required class="h-4 w-4 shrink-0">
                                        Satisfactorio
                                    </label>
                                    <label class="inline-flex cursor-pointer items-center gap-2 whitespace-nowrap">
                                        <input type="radio" name="factores[<?= $i ?>][valoracion]" value="PM" <?= $valA === 'PM' ? 'checked' : '' ?> required class="h-4 w-4 shrink-0">
                                        Por mejorar
                                    </label>
                                </div>
                                <div class="min-w-0">
                                    <span class="mb-1 block text-xs text-app-muted md:hidden<?= e($compromisosHeaderClass) ?>">Observaciones / Compromisos de mejora</span>
                                    <textarea name="factores[<?= $i ?>][observacion]" rows="2" data-max="<?= $maxCompromisos ?>" data-max-lines="2" maxlength="<?= $maxCompromisos ?>" placeholder="Observaciones..." class="<?= $factorObsTextareaClass ?> w-full"><?= e((string) ($factorObsPorIndice[$i] ?? '')) ?></textarea>
                                    <p class="mt-1 text-right text-xs text-app-muted" data-char-counter aria-live="polite"></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tipo === 'M2' || $tipo === 'M3'): ?>
            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('pencil', 'Observaciones'); ?>
                <div class="grid gap-5 md:grid-cols-1 md:gap-y-6">
                    <?php if ($tipo === 'M3'): ?>
                        <label class="<?= $lc ?> min-w-0">Observaciones del responsable ente co-formador
                            <textarea name="obs_coformador" rows="2" data-max="<?= $maxObsCoformador ?>" data-max-lines="2" maxlength="<?= $maxObsCoformador ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'obs_coformador')) ?></textarea>
                            <p class="mt-1 text-right text-xs text-app-muted" data-char-counter aria-live="polite"></p>
                        </label>
                    <?php endif; ?>
                    <label class="<?= $lc ?> min-w-0">Observaciones instructor de seguimiento
                        <textarea name="obs_instructor" rows="2" data-max="<?= $maxObsInstructor ?>" data-max-lines="2" maxlength="<?= $maxObsInstructor ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'obs_instructor')) ?></textarea>
                        <p class="mt-1 text-right text-xs text-app-muted" data-char-counter aria-live="polite"></p>
                    </label>
                    <label class="<?= $lc ?> min-w-0">Observaciones del aprendiz
                        <textarea name="obs_aprendiz" rows="2" data-max="<?= $maxObsAprendiz ?>" data-max-lines="2" maxlength="<?= $maxObsAprendiz ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'obs_aprendiz')) ?></textarea>
                        <p class="mt-1 text-right text-xs text-app-muted" data-char-counter aria-live="polite"></p>
                    </label>
                    <?php if ($tipo !== 'M3'): ?>
                        <label class="<?= $lc ?> min-w-0">Observaciones del responsable ente co-formador
                            <textarea name="obs_coformador" rows="2" data-max="<?= $maxObsCoformador ?>" data-max-lines="2" maxlength="<?= $maxObsCoformador ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'obs_coformador')) ?></textarea>
                            <p class="mt-1 text-right text-xs text-app-muted" data-char-counter aria-live="polite"></p>
                        </label>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tipo === 'M3'): ?>
            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('book-open', 'Retroalimentación (página 2)'); ?>
                <div class="grid gap-5 md:grid-cols-1 md:gap-y-6">
                    <label class="<?= $lc ?> min-w-0">Retroalimentación ente co-formador — Proceso de formación
                        <textarea name="m3_retro_coformador_proceso" maxlength="<?= $maxRetroM3 ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm3_retro_coformador_proceso')) ?></textarea>
                    </label>
                    <label class="<?= $lc ?> min-w-0">Retroalimentación ente co-formador — Desempeño de competencias
                        <textarea name="m3_retro_coformador_desempeno" maxlength="<?= $maxRetroM3 ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm3_retro_coformador_desempeno')) ?></textarea>
                    </label>
                    <label class="<?= $lc ?> min-w-0">Retroalimentación instructor — Proceso de formación
                        <textarea name="m3_retro_instructor_proceso" maxlength="<?= $maxRetroM3 ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm3_retro_instructor_proceso')) ?></textarea>
                    </label>
                    <label class="<?= $lc ?> min-w-0">Retroalimentación instructor — Desempeño de competencias
                        <textarea name="m3_retro_instructor_desempeno" maxlength="<?= $maxRetroM3 ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm3_retro_instructor_desempeno')) ?></textarea>
                    </label>
                    <label class="<?= $lc ?> min-w-0">Retroalimentación del aprendiz — Proceso de formación
                        <textarea name="m3_retro_aprendiz_proceso" maxlength="<?= $maxRetroM3 ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm3_retro_aprendiz_proceso')) ?></textarea>
                    </label>
                    <label class="<?= $lc ?> min-w-0">Retroalimentación del aprendiz — Desempeño de competencias
                        <textarea name="m3_retro_aprendiz_desempeno" maxlength="<?= $maxRetroM3 ?>" class="<?= $ta ?> mt-1.5"><?= e($valueFrom($momento, 'm3_retro_aprendiz_desempeno')) ?></textarea>
                    </label>
                </div>
            </section>

            <section class="<?= e(ui_card_classes()) ?> !p-6">
                <?php $momentoSectionHeading('circle-check', 'Evaluación final y cierre'); ?>
                <div class="<?= e($grid) ?>">
                    <label class="<?= $lc ?> min-w-0 self-start">Juicio de evaluación
                        <span class="<?= e(ui_select_wrapper_classes()) ?> mt-1.5">
                            <?php $juicio = $valueFrom($momento, 'juicio_final', 'Aprobado'); ?>
                            <select name="juicio_final" class="<?= e(ui_select_classes()) ?>">
                                <option value="Aprobado" <?= $juicio === 'Aprobado' ? 'selected' : '' ?>>Aprobado</option>
                                <option value="No aprobado" <?= $juicio === 'No aprobado' ? 'selected' : '' ?>>No aprobado</option>
                            </select>
                            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                            </span>
                        </span>
                    </label>
                </div>
            </section>
        <?php endif; ?>
    </form>

    <div class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] border-t border-app-border bg-app-panel/95 shadow-[0_-4px_24px_rgba(16,24,40,0.08)] backdrop-blur-sm" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        <div class="pointer-events-auto mx-auto flex min-h-[68px] w-full flex-wrap items-center justify-end gap-2 px-4 py-2 md:px-6">
            <button form="momento-form" type="submit" class="<?= e(ui_button_primary_classes()) ?> justify-center text-white">
                <?= $modoEdicion ? 'Actualizar momento' : 'Guardar momento' ?>
            </button>
        </div>
    </div>
    <?php partial('components/toast', [
        'message' => '',
        'variant' => 'warning',
        'positionClass' => 'bottom-28 left-4 right-4 z-[70] max-w-none sm:left-auto sm:right-6 sm:max-w-sm',
        'toastRootId' => 'f023-flow-toast',
    ]); ?>
    <script src="<?= e(APP_BASE_PATH) ?>/js/ui-toast.js"></script>
    <script src="<?= e(APP_BASE_PATH) ?>/js/date-input-dmy.js"></script>
    <?php if ($tipo === 'M1'): ?>
        <script src="<?= e(APP_BASE_PATH) ?>/js/m1-programa-append-combobox.js"></script>
    <?php endif; ?>
<?php endif; ?>
