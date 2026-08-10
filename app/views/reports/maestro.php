<?php
$drawerEditarJsPath = base_path('public/js/drawer-editar.js');
$drawerEditarJsVersion = is_file($drawerEditarJsPath) ? (string) filemtime($drawerEditarJsPath) : '1';
?>
<script src="<?= e(APP_BASE_PATH) ?>/js/drawer-editar.js?v=<?= e($drawerEditarJsVersion) ?>"></script>
<?php require __DIR__ . '/drawer-editar.php'; ?>


<div class="min-w-0 w-full">
<div class="space-y-6">

    <!-- HEADER -->
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            Reporte de seguimiento — Etapa productiva
        </h1>

        <p class="text-sm text-slate-500 mt-1">
            Los datos se consolidan automáticamente desde el sistema.
            Algunos campos deben ser completados manualmente por el instructor.
        </p>
    </div>

    <!-- FILTROS -->
    <?php
    $activeFilters = is_array($activeFilters ?? null) ? $activeFilters : [];
    $initialQ = trim((string) ($activeFilters['q'] ?? ($_GET['q'] ?? '')));
    $initialFicha = trim((string) ($activeFilters['ficha'] ?? ($_GET['ficha'] ?? '')));
    $initialEstado = trim((string) ($activeFilters['estado'] ?? ($_GET['estado'] ?? '')));
    $initialProgramaId = (int) ($activeFilters['programa_id'] ?? ($_GET['programa_id'] ?? 0));
    $programasOptions = is_array($programasOptions ?? null) ? $programasOptions : [];
    $highlightAprendizId = (int) ($highlightAprendizId ?? ($_GET['aprendiz_id'] ?? 0));
    $mostrarFinalizados = !empty($activeFilters['mostrar_finalizados']);
    ?>
    <form method="GET" action="<?= e(APP_BASE_PATH) ?>/reportes/maestro" class="flex flex-wrap items-center gap-3">
        <?php if ($highlightAprendizId > 0): ?>
            <input type="hidden" name="aprendiz_id" value="<?= $highlightAprendizId ?>">
        <?php endif; ?>
        <?php
        // Extraer SOLO los valores que aparecen en la tabla actual
        $fichas_arr = [];
        $estados_arr = [];
        foreach ($rows as $r) {
            if (!empty($r['ficha'])) $fichas_arr[] = $r['ficha'];
            if (!empty($r['estado_etapa'])) $estados_arr[] = $r['estado_etapa'];
        }
        $fichas = array_unique(array_filter($fichas_arr)); sort($fichas);
        $estados = array_unique(array_filter($estados_arr)); sort($estados);
        ?>

        <div class="relative min-w-[220px] flex-1 sm:max-w-md">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
            </span>
            <input
                type="search"
                name="q"
                value="<?= e($initialQ) ?>"
                placeholder="Buscar por nombre o documento"
                class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-10 pr-3 text-sm"
                autocomplete="off"
            >
        </div>

        <button type="submit" class="inline-flex h-10 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Buscar
        </button>

        <?php if ($initialQ !== ''): ?>
            <a href="<?= e(APP_BASE_PATH) ?>/reportes/maestro<?= $initialFicha !== '' || $initialEstado !== '' || $initialProgramaId > 0 ? '?' . http_build_query(array_filter(['ficha' => $initialFicha, 'estado' => $initialEstado, 'programa_id' => $initialProgramaId > 0 ? (string) $initialProgramaId : ''])) : '' ?>"
               class="inline-flex h-10 items-center rounded-lg px-3 text-sm text-gray-500 hover:text-gray-800">
                Limpiar búsqueda
            </a>
        <?php endif; ?>

        <select name="ficha" onchange="this.form.submit()" class="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm min-w-[160px]">
            <option value="">Todas las fichas</option>
            <?php foreach ($fichas as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>" <?= ((string)$f === $initialFicha) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="estado" onchange="this.form.submit()" class="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm min-w-[180px]">
            <option value="">Todos los estados</option>
            <?php foreach ($estados as $e): ?>
                <option value="<?= htmlspecialchars($e) ?>" <?= ((string)$e === $initialEstado) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="programa_id" onchange="this.form.submit()" class="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm min-w-[220px] max-w-xs">
            <option value="">Todos los programas</option>
            <?php foreach ($programasOptions as $programa): ?>
                <?php
                $progId = (int) ($programa['id'] ?? 0);
                $progLabel = trim((string) ($programa['nombre'] ?? ''));
                $progNivel = trim((string) ($programa['nivel'] ?? ''));
                if ($progNivel !== '') {
                    $progLabel = $progLabel !== '' ? $progLabel . ' — ' . $progNivel : $progNivel;
                }
                ?>
                <option value="<?= $progId ?>" <?= $progId === $initialProgramaId ? 'selected' : '' ?>>
                    <?= e($progLabel !== '' ? $progLabel : 'Programa #' . $progId) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700">
            <input type="checkbox" name="mostrar_finalizados" value="1" class="rounded border-gray-300" <?= $mostrarFinalizados ? 'checked' : '' ?> onchange="this.form.submit()">
            Mostrar finalizados
        </label>

        <div class="flex-1"></div>

        <?php
        $exportQuery = array_filter([
            'estado' => $initialEstado,
            'ficha' => $initialFicha,
            'q' => $initialQ,
            'programa_id' => $initialProgramaId > 0 ? (string) $initialProgramaId : '',
            'aprendiz_id' => $highlightAprendizId > 0 ? (string) $highlightAprendizId : '',
            'mostrar_finalizados' => $mostrarFinalizados ? '1' : '',
        ], static fn (string $v): bool => $v !== '');
        $exportUrl = APP_BASE_PATH . '/reportes/exportar'
            . ($exportQuery === [] ? '' : '?' . http_build_query($exportQuery));
        ?>
        <a
            href="<?= e($exportUrl) ?>"
            class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 h-10 rounded-lg transition-colors"
            id="exportBtn"
            data-download-link
            data-download-before="prepareExportData"
            data-download-label="Exportando reporte maestro"
        >
            Exportar a Excel
        </a>
    </form>

    <!-- STATS -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Total aprendices</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">
                <?= count($rows) ?>
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">En ejecución</p>
            <p class="text-3xl font-bold text-sky-700 mt-1">
                <?= count(array_filter($rows, fn($r) => ($r['estado_etapa'] ?? '') === 'En ejecución')) ?>
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Por certificar</p>
            <p class="text-3xl font-bold text-amber-600 mt-1">
                <?= count(array_filter($rows, fn($r) => ($r['estado_aprendiz'] ?? '') === 'Por certificar')) ?>
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500">Certificados</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1">
                <?= count(array_filter($rows, fn($r) => ($r['estado_aprendiz'] ?? '') === 'Certificado')) ?>
            </p>
        </div>
    </div>

    <!-- SOLO LA TABLA SE DESPLAZA HORIZONTALMENTE -->
    <div class="bg-white border border-gray-200 rounded-2xl" data-reporte-maestro-root>
        <div class="reporte-maestro-table-scroll pb-2" data-reporte-table-scroll>
            <table style="width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0;" class="text-xs" data-reporte-table>
                <thead>
                    <tr>
                        <th colspan="11" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #f3f4f6;">Aprendices y grupos</th>
                        <th colspan="4" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #f3e8ff; cursor: pointer;" data-drawer-section="s0">Reglamento</th>
                        <th colspan="6" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #dbeafe; cursor: pointer;" data-drawer-section="s_aprendiz">Información del aprendiz</th>
                        <th colspan="10" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #ccfbf1; cursor: pointer;" data-drawer-section="s_etapa_info">Información de la etapa productiva</th>
                        <th colspan="6" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #bbf7d0; cursor: pointer;" data-drawer-section="s_etapa">Novedades de la etapa productiva</th>
                        <th colspan="9" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #ffedd5; cursor: pointer;" data-drawer-section="s1">Proceso documental del seguimiento</th>
                        <th colspan="11" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #d1fae5; cursor: pointer;" data-drawer-section="s2">Documentos para certificación</th>
                        <th colspan="3" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #f3f4f6;">Instructor de seguimiento</th>
                    </tr>
                    <tr style="background: #f9fafb;">
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">#</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"># por grupo</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Ficha</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Cód. programa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Programa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Nivel</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Modalidad</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; cursor: pointer;" data-drawer-section="s_programa">F. inicio plat.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; cursor: pointer;" data-drawer-section="s_programa">Inicio EP</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; cursor: pointer;" data-drawer-section="s_programa">F. fin plat.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Instructor jefe</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Ac. 007</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Ac. 009</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Venc. términos</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Semáforo</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="reporte-sticky-nombre">Nombre</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">N° identificación</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Celular</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Correo</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Mod. práctica</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F. aval</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Estado ARL</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">ARL</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; cursor: pointer;" data-drawer-section="s_etapa_info">F. inicio</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; cursor: pointer;" data-drawer-section="s_etapa_info">F. fin</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Empresa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Dir. empresa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Ciudad</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Contacto</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Tel. contacto</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Correo contacto</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; min-width: 120px;">Estado etapa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; min-width: 100px;">Llamados atenc.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; min-width: 120px;">Otros</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; min-width: 110px;">Comité eval.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; min-width: 140px;">Reingreso esp.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; min-width: 120px;">Obs. novedad</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F-165</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">M1 023</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Bit. 1</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Bit. 2</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Bit. 3</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Bit. 4</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Bit. 5</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Bit. 6</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">M. Final</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Doc. ID</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Paz y salvo</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F-023</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Bitácoras</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Cert. cumpl.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">APE</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Carnet</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Saber T&T</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F. entrega</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Estado</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Obs.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Instructor</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Tel.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Correo</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $index => $a): ?>
                        <tr 
                            id="reporte-aprendiz-<?= (int) ($a['id'] ?? 0) ?>"
                            class="cursor-pointer hover:bg-gray-50 transition apprentice-row<?= $highlightAprendizId > 0 && $highlightAprendizId === (int) ($a['id'] ?? 0) ? ' reporte-row-highlight' : '' ?>" 
                            data-id="<?= (int) ($a['id'] ?? 0) ?>"
                            data-identificacion="<?= e((string) ($a['identificacion'] ?? '')) ?>"
                            data-nombre="<?= e((string) ($a['nombre'] ?? '')) ?>"
                            data-index="<?= $index ?>"
                            data-fecha-fin-plataforma="<?= e((string) ($a['fecha_fin_plataforma'] ?? '')) ?>"
                            data-acuerdo-007="<?= !empty($a['acuerdo_007']) ? '1' : '0' ?>"
                            data-acuerdo-009="<?= !empty($a['acuerdo_009']) ? '1' : '0' ?>"
                        >
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['num_aprendiz'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; text-align: center;"><?= e((string) ($a['num_por_grupo'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['ficha'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['codigo_programa'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= e((string) ($a['programa_formacion'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><span style="display: inline-flex; border-radius: 9999px; padding: 2px 8px; font-size: 11px; background: #dbeafe;"><?= e((string) ($a['nivel'] ?? '')) ?></span></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['modalidad_programa'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="fecha-inicio-plataforma" data-drawer-section="s_programa"><?= e((string) ($a['fecha_inicio_plataforma'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="inicio-etapa-productiva" data-drawer-section="s_programa"><?= e((string) ($a['inicio_etapa_productiva'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="fecha-fin-plataforma" data-drawer-section="s_programa"><?= e((string) ($a['fecha_fin_plataforma'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= e((string) ($a['instructor_jefe'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="acuerdo-007"><?= !empty($a['acuerdo_007']) ? 'X' : '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="acuerdo-009"><?= !empty($a['acuerdo_009']) ? 'X' : '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="vencimiento-terminos"><?= e((string) ($a['vencimiento_terminos'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="semaforo-vencimiento"><?= e((string) ($a['semaforo_vencimiento'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; font-weight: 500;" class="reporte-sticky-nombre aprendiz-nombre"><?= e((string) ($a['nombre'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['identificacion'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['celular'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= e((string) ($a['correo'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['modalidad_practica'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="fecha-aval-modalidad"><?= e((string) ($a['fecha_aval_modalidad'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['estado_arl'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['arl'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="fecha-inicio-etapa" data-drawer-section="s_etapa_info"><?= e((string) ($a['fecha_inicio_etapa'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="fecha-fin-etapa" data-drawer-section="s_etapa_info"><?= e((string) ($a['fecha_fin_etapa'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= e((string) ($a['empresa'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= e((string) ($a['direccion_empresa'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['ciudad'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= e((string) ($a['contacto_empresa'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['telefono_contacto'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= e((string) ($a['correo_contacto'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 160px; overflow: hidden; text-overflow: ellipsis;"><span style="display: inline-flex; border-radius: 9999px; padding: 2px 8px; font-size: 11px; background: #dbeafe;" class="estado-etapa"><?= e((string) ($a['estado_etapa'] ?? '')) ?></span></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 120px; overflow: hidden; text-overflow: ellipsis;" class="llamados-atencion"><?= e((string) ($a['llamados_atencion'] ?? '')) ?: '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 160px; overflow: hidden; text-overflow: ellipsis;" class="otros-novedad"><?= e((string) ($a['otros_novedad'] ?? '')) ?: '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 140px; overflow: hidden; text-overflow: ellipsis;" class="comite-evaluacion"><?= e((string) ($a['comite_evaluacion'] ?? '')) ?: '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 180px; overflow: hidden; text-overflow: ellipsis;" class="reingreso-vencimiento"><?= e((string) ($a['reingreso_vencimiento'] ?? '')) ?: '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;" class="obs-novedad"><?= e((string) ($a['observaciones_novedad'] ?? '')) ?: '—' ?></td>
                            
                            <!-- PROCESO DOCUMENTAL DEL SEGUIMIENTO - Con clases específicas -->
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-gfpi-165"><?= !empty($a['doc_gfpi_165']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-momento-1"><?= !empty($a['doc_momento_1']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-bitacora-1"><?= !empty($a['doc_bitacora_1']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-bitacora-2"><?= !empty($a['doc_bitacora_2']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-bitacora-3"><?= !empty($a['doc_bitacora_3']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-bitacora-4"><?= !empty($a['doc_bitacora_4']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-bitacora-5"><?= !empty($a['doc_bitacora_5']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-bitacora-6"><?= !empty($a['doc_bitacora_6']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-momento-final"><?= !empty($a['doc_momento_final']) ? '✓' : '—' ?></td>
                            
                            <!-- DOCUMENTOS PARA CERTIFICACIÓN - Con clases específicas -->
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-cert-doc-identidad"><?= !empty($a['cert_doc_identidad']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-cert-paz-salvo"><?= !empty($a['cert_paz_salvo']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-cert-gfpi-023"><?= !empty($a['cert_gfpi_023']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-cert-bitacoras"><?= !empty($a['cert_bitacoras']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-cert-cumplimiento"><?= !empty($a['cert_cumplimiento']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-cert-ape"><?= !empty($a['cert_ape']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-cert-carnet"><?= !empty($a['cert_carnet']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;" class="doc-status-cert-saber-tyt"><?= !empty($a['cert_saber_tyt']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="fecha-entrega"><?= e((string) ($a['fecha_entrega_admin'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="estado-aprendiz"><?= e((string) ($a['estado_aprendiz'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;" class="observaciones"><?= e((string) ($a['observaciones_cert'] ?? '')) ?: '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['instructor_asignado'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['telefono_instructor'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['correo_instructor'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="reporte-maestro-hscroll-bar is-hidden" data-reporte-hscroll-bar aria-hidden="true">
            <div class="reporte-maestro-hscroll-spacer" data-reporte-hscroll-spacer></div>
        </div>
    </div>
</div>
</div>

<script>
(function () {
  var tableScroll = document.querySelector("[data-reporte-table-scroll]");
  var hScrollBar = document.querySelector("[data-reporte-hscroll-bar]");
  var hScrollSpacer = document.querySelector("[data-reporte-hscroll-spacer]");
  var main = document.querySelector("main");
  if (!tableScroll || !hScrollBar || !hScrollSpacer || !main) return;

  var syncing = false;

  var syncScroll = function (source, target) {
    if (syncing) return;
    syncing = true;
    target.scrollLeft = source.scrollLeft;
    syncing = false;
  };

  var updateGeometry = function () {
    var tableRect = tableScroll.getBoundingClientRect();
    var mainRect = main.getBoundingClientRect();
    hScrollBar.style.left = tableRect.left + "px";
    hScrollBar.style.width = tableRect.width + "px";
    hScrollBar.style.bottom = Math.max(0, window.innerHeight - mainRect.bottom) + "px";
  };

  var nombreColumnOffset = 0;

  var measureNombreColumnOffset = function () {
    var table = tableScroll.querySelector("[data-reporte-table]");
    if (!table) return;
    var headerRow = table.querySelector("thead tr:last-child");
    if (!headerRow) return;
    var offset = 0;
    var cells = headerRow.querySelectorAll("th");
    for (var i = 0; i < cells.length; i++) {
      if (cells[i].classList.contains("reporte-sticky-nombre")) {
        break;
      }
      offset += cells[i].offsetWidth;
    }
    nombreColumnOffset = offset;
  };

  var updateStickyNombreState = function () {
    tableScroll.classList.toggle("reporte-nombre-pinned", tableScroll.scrollLeft + 1 >= nombreColumnOffset);
  };

  var updateBar = function () {
    var table = tableScroll.querySelector("[data-reporte-table]");
    var scrollWidth = Math.max(tableScroll.scrollWidth, table ? table.scrollWidth : 0);
    hScrollSpacer.style.width = scrollWidth + "px";
    updateGeometry();
    measureNombreColumnOffset();
    updateStickyNombreState();

    var tableRect = tableScroll.getBoundingClientRect();
    var mainRect = main.getBoundingClientRect();
    var tableVisible = tableRect.bottom > mainRect.top && tableRect.top < mainRect.bottom;
    var needsScroll = scrollWidth > tableScroll.clientWidth + 1;
    hScrollBar.classList.toggle("is-hidden", !tableVisible || !needsScroll);
  };

  tableScroll.addEventListener("scroll", function () {
    syncScroll(tableScroll, hScrollBar);
    updateStickyNombreState();
  });
  hScrollBar.addEventListener("scroll", function () {
    syncScroll(hScrollBar, tableScroll);
    updateStickyNombreState();
  });

  main.addEventListener("scroll", updateBar, { passive: true });
  window.addEventListener("scroll", updateBar, { passive: true });
  window.addEventListener("resize", updateBar);

  if (typeof ResizeObserver !== "undefined") {
    var ro = new ResizeObserver(updateBar);
    ro.observe(tableScroll);
    var table = tableScroll.querySelector("[data-reporte-table]");
    if (table) ro.observe(table);
    ro.observe(main);
  }

  updateBar();
  window.addEventListener("load", updateBar);
  setTimeout(updateBar, 600);
})();
</script>
<style>
.reporte-row-highlight > td {
    background-color: #fef3c7 !important;
}
.reporte-row-highlight {
    outline: 2px solid #f59e0b;
    outline-offset: -2px;
}
</style>
<script>
(function () {
    var highlightId = <?= (int) $highlightAprendizId ?>;
    if (highlightId <= 0) return;

    function clearHighlightFromUrl() {
        var url = new URL(window.location.href);
        if (!url.searchParams.has('aprendiz_id')) {
            return;
        }
        url.searchParams.delete('aprendiz_id');
        var query = url.searchParams.toString();
        var next = url.pathname + (query ? '?' + query : '') + url.hash;
        window.history.replaceState(null, '', next);
        var hidden = document.querySelector('form[action*="reportes/maestro"] input[name="aprendiz_id"]');
        if (hidden) {
            hidden.remove();
        }
    }

    function focusRow() {
        var row = document.getElementById('reporte-aprendiz-' + highlightId)
            || document.querySelector('tr[data-id="' + highlightId + '"]');
        if (!row) {
            clearHighlightFromUrl();
            return;
        }
        row.classList.add('reporte-row-highlight');
        row.scrollIntoView({ block: 'center', behavior: 'smooth' });
        var scrollHost = document.querySelector('[data-reporte-table-scroll]');
        if (scrollHost) {
            var nombreCell = row.querySelector('.reporte-sticky-nombre');
            if (nombreCell) {
                scrollHost.scrollLeft = Math.max(0, nombreCell.offsetLeft - 24);
            }
        }
        clearHighlightFromUrl();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(focusRow, 150);
        });
    } else {
        setTimeout(focusRow, 150);
    }
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        if (typeof loadAllTableData === 'function') {
            loadAllTableData();
        } else if (window.loadAllTableData) {
            window.loadAllTableData();
        }
    }, 100);
});
</script>
