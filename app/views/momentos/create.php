<?php
$programaContenido = (array) ($programaContenido ?? []);
$momento = (array) ($momento ?? []);
$momentoExistente = (array) ($momentoExistente ?? []);
$factoresExistentes = (array) ($factoresExistentes ?? []);

$modoEdicion = !empty($momentoExistente);
$accion = $modoEdicion ? (APP_BASE_PATH . '/momentos/update') : (APP_BASE_PATH . '/momentos/store');
$tipoLabel = match ((string) $tipo) {
    'M1' => 'Momento 1',
    'M2' => 'Momento 2',
    'M3' => 'Momento 3',
    'EX' => 'Momento extraordinario',
    default => 'Momento',
};
$titulo = 'Registro de ' . $tipoLabel;
if ($modoEdicion) {
    $titulo = 'Edición de ' . $tipoLabel;
}

$factorMap = [];
foreach ($factoresExistentes as $factorItem) {
    $nombre = trim((string) ($factorItem['nombre_factor'] ?? ''));
    if ($nombre === '') {
        continue;
    }
    $factorMap[$nombre] = [
        'valoracion' => (string) ($factorItem['valoracion'] ?? ''),
        'observacion' => (string) ($factorItem['observacion'] ?? ''),
    ];
}

$valueFrom = static function (array $source, string $key, string $fallback = ''): string {
    $raw = $source[$key] ?? $fallback;
    if ($raw === null) {
        return '';
    }
    return trim((string) $raw);
};

partial('components/page_header', [
    'title' => $titulo,
    'subtitle' => 'Diligencie la información del formato GFPI-F-023. Todos los campos son editables y pueden quedar vacíos.',
]); ?>

<?php if (empty($aprendiz)): ?>
    <section class="<?= e(ui_warning_card_classes()) ?>">Aprendiz no encontrado.</section>
<?php else: ?>
    <section class="mb-4 <?= e(ui_card_classes()) ?>">
        <p class="m-0 text-sm"><?= e((string) $aprendiz['nombre_completo']) ?> - <?= e((string) $aprendiz['numero_documento']) ?></p>
    </section>

    <?php if ($programaContenido !== [] && $tipo === 'M1'): ?>
        <section class="mb-4 <?= e(ui_card_classes()) ?>">
            <h3 class="<?= e(ui_heading_sm_classes()) ?>">Resultados sugeridos por programa</h3>
            <div class="mt-2 max-h-56 overflow-auto rounded border border-app-border p-3">
                <?php foreach ($programaContenido as $competencia): ?>
                    <p class="mb-1 mt-0 text-sm font-semibold text-app-text"><?= e((string) ($competencia['nombre'] ?? '')) ?></p>
                    <ul class="mb-2 mt-0 list-disc pl-4 text-sm text-app-muted">
                        <?php foreach ((array) ($competencia['resultados'] ?? []) as $resultado): ?>
                            <li><?= e((string) ($resultado['descripcion'] ?? '')) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <form method="post" action="<?= e($accion) ?>" class="<?= e(ui_card_classes()) ?>">
        <?php if ($modoEdicion): ?>
            <input type="hidden" name="id" value="<?= (int) ($momentoExistente['id'] ?? 0) ?>">
        <?php endif; ?>
        <input type="hidden" name="aprendiz_id" value="<?= (int) $aprendiz['id'] ?>">
        <input type="hidden" name="tipo" value="<?= e((string) $tipo) ?>">

        <?php if ($tipo === 'M1'): ?>
            <div class="grid gap-4 md:grid-cols-3">
                <label class="<?= e(ui_label_classes()) ?>">Fecha inicio etapa productiva
                    <input type="date" name="fecha_inicio_etapa" value="<?= e($valueFrom($momento, 'fecha_inicio_etapa')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Fecha fin etapa productiva
                    <input type="date" name="fecha_fin_etapa" value="<?= e($valueFrom($momento, 'fecha_fin_etapa')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Fecha afiliación ARL
                    <input type="date" name="fecha_arl" value="<?= e($valueFrom($momento, 'fecha_arl')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Número póliza ARL
                    <input type="text" name="numero_poliza_arl" value="<?= e($valueFrom($momento, 'numero_poliza_arl')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Horario
                    <input type="text" name="horario" value="<?= e($valueFrom($momento, 'horario')) ?>" placeholder="Diurno/nocturno, días y hora" class="<?= e(ui_input_classes()) ?>">
                </label>
                <label class="<?= e(ui_label_classes()) ?> md:col-span-3">Enlace grabación momento 1
                    <input type="url" name="enlace_grabacion" value="<?= e($valueFrom($momento, 'enlace_grabacion')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
            </div>

            <div class="mt-5 grid gap-4">
                <label class="<?= e(ui_label_classes()) ?>">Competencias a desarrollar
                    <textarea name="m1_competencias" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm1_competencias')) ?></textarea>
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Resultados de aprendizaje
                    <textarea name="m1_resultados" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm1_resultados')) ?></textarea>
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Actividades a desarrollar
                    <textarea name="m1_actividades" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm1_actividades')) ?></textarea>
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Evidencias de aprendizaje
                    <textarea name="m1_evidencias" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm1_evidencias')) ?></textarea>
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Observaciones adicionales
                    <textarea name="m1_observaciones_adicionales" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm1_observaciones_adicionales')) ?></textarea>
                </label>
            </div>
        <?php else: ?>
            <div class="grid gap-4 md:grid-cols-3">
                <label class="<?= e(ui_label_classes()) ?>">Fecha inicio etapa productiva
                    <input type="date" name="fecha_inicio_etapa" value="<?= e($valueFrom($momento, 'fecha_inicio_etapa')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">
                    <?= $tipo === 'M2' ? 'Fecha momento de seguimiento' : 'Fecha fin etapa de ejecución' ?>
                    <input type="date" name="fecha_visita" value="<?= e($valueFrom($momento, 'fecha_visita')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
                <?php if ($tipo === 'M3'): ?>
                    <label class="<?= e(ui_label_classes()) ?>">Número de visitas realizadas
                        <input type="number" min="0" name="numero_visitas_realizadas" value="<?= e($valueFrom($momento, 'numero_visitas_realizadas')) ?>" class="<?= e(ui_input_classes()) ?>">
                    </label>
                <?php endif; ?>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="<?= e(ui_label_classes()) ?>">
                    <?= $tipo === 'M2' ? 'Modalidad del seguimiento' : 'Evaluación realizada de forma' ?>
                    <span class="<?= e(ui_select_wrapper_classes()) ?>">
                        <?php $modalidad = $valueFrom($momento, 'modalidad', 'Presencial'); ?>
                        <select name="modalidad" class="<?= e(ui_select_classes()) ?>">
                            <option value="Presencial" <?= $modalidad === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                            <option value="Virtual" <?= $modalidad === 'Virtual' ? 'selected' : '' ?>>Virtual</option>
                        </select>
                        <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                        </span>
                    </span>
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Enlace de grabación
                    <input type="url" name="enlace_grabacion" value="<?= e($valueFrom($momento, 'enlace_grabacion')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
            </div>
        <?php endif; ?>

        <?php if ($tipo !== 'M3'): ?>
            <label class="mt-4 <?= e(ui_label_classes()) ?> md:max-w-sm">Próxima visita
                <input type="date" name="proxima_visita" value="<?= e($valueFrom($momento, 'proxima_visita', (string) ($aprendiz['proxima_visita'] ?? ''))) ?>" class="<?= e(ui_input_classes()) ?>">
            </label>
        <?php endif; ?>

        <?php if (in_array($tipo, ['M2', 'M3', 'EX'], true)): ?>
            <?php $factores = require base_path('config/factores.php'); ?>
            <section class="mt-6">
                <h3 class="<?= e(ui_heading_sm_classes()) ?>">Factores técnicos</h3>
                <div class="space-y-2">
                    <?php foreach ($factores['tecnicos'] as $idx => $nombre): ?>
                        <?php $factorActual = (array) ($factorMap[$nombre] ?? []); ?>
                        <div class="rounded-lg border border-app-border bg-app-panelSubtle p-3">
                            <input type="hidden" name="factores[<?= $idx ?>][tipo_factor]" value="tecnico">
                            <input type="hidden" name="factores[<?= $idx ?>][nombre_factor]" value="<?= e($nombre) ?>">
                            <p class="m-0 text-sm font-semibold text-app-text"><?= e($nombre) ?></p>
                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $idx ?>][valoracion]" value="S" <?= ($factorActual['valoracion'] ?? '') === 'S' ? 'checked' : '' ?> class="h-4 w-4"> Satisfactorio</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $idx ?>][valoracion]" value="PM" <?= ($factorActual['valoracion'] ?? '') === 'PM' ? 'checked' : '' ?> class="h-4 w-4"> Por mejorar</label>
                            </div>
                            <input type="text" name="factores[<?= $idx ?>][observacion]" value="<?= e((string) ($factorActual['observacion'] ?? '')) ?>" placeholder="Observación / compromiso de mejora" class="<?= e(ui_input_classes()) ?> mt-2">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="mt-6">
                <h3 class="<?= e(ui_heading_sm_classes()) ?>">Factores actitudinales y comportamentales</h3>
                <div class="space-y-2">
                    <?php foreach ($factores['actitudinales'] as $offset => $nombre): ?>
                        <?php $i = $offset + 8; ?>
                        <?php $factorActual = (array) ($factorMap[$nombre] ?? []); ?>
                        <div class="rounded-lg border border-app-border bg-app-panelSubtle p-3">
                            <input type="hidden" name="factores[<?= $i ?>][tipo_factor]" value="actitudinal">
                            <input type="hidden" name="factores[<?= $i ?>][nombre_factor]" value="<?= e($nombre) ?>">
                            <p class="m-0 text-sm font-semibold text-app-text"><?= e($nombre) ?></p>
                            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm">
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $i ?>][valoracion]" value="S" <?= ($factorActual['valoracion'] ?? '') === 'S' ? 'checked' : '' ?> class="h-4 w-4"> Satisfactorio</label>
                                <label class="inline-flex items-center gap-2"><input type="radio" name="factores[<?= $i ?>][valoracion]" value="PM" <?= ($factorActual['valoracion'] ?? '') === 'PM' ? 'checked' : '' ?> class="h-4 w-4"> Por mejorar</label>
                            </div>
                            <input type="text" name="factores[<?= $i ?>][observacion]" value="<?= e((string) ($factorActual['observacion'] ?? '')) ?>" placeholder="Observación / compromiso de mejora" class="<?= e(ui_input_classes()) ?> mt-2">
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tipo === 'M2' || $tipo === 'M3'): ?>
            <section class="mt-6 grid gap-4">
                <label class="<?= e(ui_label_classes()) ?>">Observaciones instructor de seguimiento
                    <textarea name="obs_instructor" data-max="500" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'obs_instructor')) ?></textarea>
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Observaciones del aprendiz
                    <textarea name="obs_aprendiz" class="<?= e(ui_input_classes()) ?> min-h-20"><?= e($valueFrom($momento, 'obs_aprendiz')) ?></textarea>
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Observaciones del responsable ente co-formador
                    <textarea name="obs_coformador" class="<?= e(ui_input_classes()) ?> min-h-20"><?= e($valueFrom($momento, 'obs_coformador')) ?></textarea>
                </label>
            </section>
        <?php endif; ?>

        <?php if ($tipo === 'M3'): ?>
            <section class="mt-6 rounded-lg border border-app-border p-4">
                <h3 class="<?= e(ui_heading_sm_classes()) ?>">Página 2 - Retroalimentación</h3>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    <label class="<?= e(ui_label_classes()) ?>">Retroalimentación ente co-formador - Proceso de formación
                        <textarea name="m3_retro_coformador_proceso" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm3_retro_coformador_proceso')) ?></textarea>
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Retroalimentación ente co-formador - Desempeño de competencias
                        <textarea name="m3_retro_coformador_desempeno" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm3_retro_coformador_desempeno')) ?></textarea>
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Retroalimentación instructor - Proceso de formación
                        <textarea name="m3_retro_instructor_proceso" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm3_retro_instructor_proceso')) ?></textarea>
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Retroalimentación instructor - Desempeño de competencias
                        <textarea name="m3_retro_instructor_desempeno" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm3_retro_instructor_desempeno')) ?></textarea>
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Retroalimentación del aprendiz - Proceso de formación
                        <textarea name="m3_retro_aprendiz_proceso" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm3_retro_aprendiz_proceso')) ?></textarea>
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Retroalimentación del aprendiz - Desempeño de competencias
                        <textarea name="m3_retro_aprendiz_desempeno" class="<?= e(ui_input_classes()) ?> min-h-24"><?= e($valueFrom($momento, 'm3_retro_aprendiz_desempeno')) ?></textarea>
                    </label>
                </div>
            </section>

            <section class="mt-4 grid gap-4 md:grid-cols-3">
                <label class="<?= e(ui_label_classes()) ?>">Juicio de evaluación
                    <span class="<?= e(ui_select_wrapper_classes()) ?>">
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
                <label class="<?= e(ui_label_classes()) ?>">Ciudad diligenciamiento
                    <input type="text" name="ciudad_diligenciamiento" value="<?= e($valueFrom($momento, 'ciudad_diligenciamiento')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Fecha diligenciamiento
                    <input type="date" name="fecha_diligenciamiento" value="<?= e($valueFrom($momento, 'fecha_diligenciamiento')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
            </section>
        <?php endif; ?>

        <section class="mt-4 grid gap-4 md:grid-cols-3">
            <?php if ($tipo !== 'M3'): ?>
                <label class="<?= e(ui_label_classes()) ?>">Ciudad diligenciamiento
                    <input type="text" name="ciudad_diligenciamiento" value="<?= e($valueFrom($momento, 'ciudad_diligenciamiento')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
                <label class="<?= e(ui_label_classes()) ?>">Fecha diligenciamiento
                    <input type="date" name="fecha_diligenciamiento" value="<?= e($valueFrom($momento, 'fecha_diligenciamiento')) ?>" class="<?= e(ui_input_classes()) ?>">
                </label>
            <?php endif; ?>
            <label class="<?= e(ui_label_classes()) ?>">Modalidad diligenciamiento
                <span class="<?= e(ui_select_wrapper_classes()) ?>">
                    <?php $modalidadD = $valueFrom($momento, 'modalidad_diligenciamiento', ''); ?>
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
        </section>

        <button type="submit" class="mt-6 <?= e(ui_button_primary_classes()) ?>">
            <?= $modoEdicion ? 'Actualizar momento' : 'Guardar momento' ?>
        </button>
    </form>
<?php endif; ?>
