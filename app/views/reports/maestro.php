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
    <div class="flex flex-wrap items-center gap-3">

        <!-- Instructor -->
        <select class="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm min-w-[180px]">
            <option>Carlos Mendoza</option>
        </select>

        <!-- Ficha -->
        <select class="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm min-w-[160px]">
            <option>Todos</option>
            <option>2745623</option>
            <option>2801445</option>
        </select>

        <!-- Estado -->
        <select class="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm min-w-[180px]">
            <option>Todos</option>
            <option>En ejecución</option>
            <option>Finalizada</option>
            <option>Pendiente</option>
        </select>

        <div class="flex-1"></div>

        <!-- BOTÓN -->
        <a
            href="<?= APP_BASE_PATH ?>/reportes/export"
            class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 h-10 rounded-lg transition-colors"
        >
            Exportar a Excel
        </a>

    </div>

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

    <!-- TABLA -->
    <div class="bg-white border border-gray-200 rounded-2xl">

        <!-- SOLO ESTA PARTE SCROLLEA -->
        <div class="overflow-x-auto rounded-2xl">

            <table class="min-w-max w-full text-xs border-collapse">

               <!-- TABLA -->
                <div class="w-full max-w-full overflow-hidden bg-white border border-gray-200 rounded-2xl">

                    <!-- SOLO ESTA CAPA TIENE SCROLL -->
                    <div class="w-full overflow-x-auto overflow-y-hidden rounded-2xl">

                        <table class="w-max min-w-full text-xs border-collapse">

                            <!-- HEADER PRINCIPAL -->
                            <thead>

                                <tr>
                                    <th colspan="10" class="group-header bg-gray-100 text-gray-700">
                                        Aprendices y grupos
                                    </th>

                                    <th colspan="5" class="group-header bg-purple-100 text-purple-700">
                                        Reglamento
                                    </th>

                                    <th colspan="8" class="group-header bg-sky-100 text-sky-700">
                                        Información del aprendiz
                                    </th>

                                    <th colspan="12" class="group-header bg-teal-100 text-teal-700">
                                        Información de la etapa productiva
                                    </th>

                                    <th colspan="9" class="group-header bg-orange-100 text-orange-700">
                                        Proceso documental del seguimiento
                                    </th>

                                    <th colspan="11" class="group-header bg-emerald-100 text-emerald-700">
                                        Documentos para certificación
                                    </th>

                                    <th colspan="3" class="group-header bg-gray-100 text-gray-700">
                                        Instructor de seguimiento
                                    </th>
                                </tr>

                                <tr class="bg-gray-50">

                                    <!-- G1 -->
                                    <th class="th">#</th>
                                    <th class="th"># Grupo</th>
                                    <th class="th">Ficha</th>
                                    <th class="th">Cód. programa</th>
                                    <th class="th">Programa</th>
                                    <th class="th">Nivel</th>
                                    <th class="th">Modalidad</th>
                                    <th class="th">F. inicio plat.</th>
                                    <th class="th">F. fin plat.</th>
                                    <th class="th">Instructor jefe</th>

                                    <!-- G2 -->
                                    <th class="th">Ac. 007</th>
                                    <th class="th">Ac. 009</th>
                                    <th class="th">≤18 meses</th>
                                    <th class="th">≤12 meses</th>
                                    <th class="th">Venc. términos</th>

                                    <!-- G3 -->
                                    <th class="th">Nombre</th>
                                    <th class="th">N° identificación</th>
                                    <th class="th">Celular</th>
                                    <th class="th">Correo</th>
                                    <th class="th">Mod. práctica</th>
                                    <th class="th">F. aval</th>
                                    <th class="th">Estado ARL</th>
                                    <th class="th">ARL</th>

                                    <!-- G4 -->
                                    <th class="th">F. inicio</th>
                                    <th class="th">F. fin</th>
                                    <th class="th">Empresa</th>
                                    <th class="th">Dir. empresa</th>
                                    <th class="th">Ciudad</th>
                                    <th class="th">Contacto</th>
                                    <th class="th">Tel. contacto</th>
                                    <th class="th">Correo contacto</th>
                                    <th class="th">Estado etapa</th>
                                    <th class="th">Reingreso</th>
                                    <th class="th">Cambio/Cond./Canc.</th>
                                    <th class="th">Obs. novedad</th>

                                    <!-- G5 -->
                                    <th class="th">F-165</th>
                                    <th class="th">M1 023</th>
                                    <th class="th">Bit. 1</th>
                                    <th class="th">Bit. 2</th>
                                    <th class="th">Bit. 3</th>
                                    <th class="th">Bit. 4</th>
                                    <th class="th">Bit. 5</th>
                                    <th class="th">Bit. 6</th>
                                    <th class="th">M. Final</th>

                                    <!-- G6 -->
                                    <th class="th">Doc. ID</th>
                                    <th class="th">Paz y salvo</th>
                                    <th class="th">F-023</th>
                                    <th class="th">Bitácoras</th>
                                    <th class="th">Cert. cumpl.</th>
                                    <th class="th">APE</th>
                                    <th class="th">Carnet</th>
                                    <th class="th">Saber T&T</th>
                                    <th class="th">F. entrega</th>
                                    <th class="th">Estado</th>
                                    <th class="th">Obs.</th>

                                    <!-- G7 -->
                                    <th class="th">Instructor</th>
                                    <th class="th">Tel.</th>
                                    <th class="th">Correo</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($rows as $a): ?>

                                    <tr class="hover:bg-gray-50 transition-colors text-gray-700">

                                        <td class="td"><?= $a['num_aprendiz'] ?? '' ?></td>
                                        <td class="td"><?= $a['num_por_grupo'] ?? '' ?></td>
                                        <td class="td"><?= $a['ficha'] ?? '' ?></td>
                                        <td class="td"><?= $a['codigo_programa'] ?? '' ?></td>

                                        <td class="td truncate-cell">
                                            <?= $a['programa_formacion'] ?? '' ?>
                                        </td>

                                        <td class="td">
                                            <span class="pill-blue">
                                                <?= $a['nivel'] ?? '' ?>
                                            </span>
                                        </td>

                                        <td class="td"><?= $a['modalidad_programa'] ?? '' ?></td>
                                        <td class="td"><?= $a['fecha_inicio_plataforma'] ?? '' ?></td>
                                        <td class="td"><?= $a['fecha_fin_plataforma'] ?? '' ?></td>

                                        <td class="td truncate-cell">
                                            <?= $a['instructor_jefe'] ?? '' ?>
                                        </td>

                                        <td class="td text-center"><?= !empty($a['acuerdo_007']) ? '✓' : '' ?></td>
                                        <td class="td text-center"><?= !empty($a['acuerdo_009']) ? '✓' : '' ?></td>
                                        <td class="td text-center"><?= !empty($a['inicio_18_meses']) ? '✓' : '' ?></td>
                                        <td class="td text-center"><?= !empty($a['inicio_12_meses']) ? '✓' : '' ?></td>
                                        <td class="td text-center"><?= !empty($a['vencimiento_terminos']) ? '✓' : '' ?></td>

                                        <td class="td font-medium text-gray-900">
                                            <?= $a['nombre'] ?? '' ?>
                                        </td>

                                        <td class="td"><?= $a['identificacion'] ?? '' ?></td>
                                        <td class="td"><?= $a['celular'] ?? '' ?></td>

                                        <td class="td truncate-cell">
                                            <?= $a['correo'] ?? '' ?>
                                        </td>

                                        <td class="td"><?= $a['modalidad_practica'] ?? '' ?></td>
                                        <td class="td"><?= $a['fecha_aval_modalidad'] ?? '' ?></td>
                                        <td class="td"><?= $a['estado_arl'] ?? '' ?></td>
                                        <td class="td"><?= $a['arl'] ?? '' ?></td>

                                        <td class="td"><?= $a['fecha_inicio_etapa'] ?? '' ?></td>
                                        <td class="td"><?= $a['fecha_fin_etapa'] ?? '' ?></td>

                                        <td class="td truncate-cell">
                                            <?= $a['empresa'] ?? '' ?>
                                        </td>

                                        <td class="td truncate-cell">
                                            <?= $a['direccion_empresa'] ?? '' ?>
                                        </td>

                                        <td class="td"><?= $a['ciudad'] ?? '' ?></td>

                                        <td class="td truncate-cell">
                                            <?= $a['contacto_empresa'] ?? '' ?>
                                        </td>

                                        <td class="td"><?= $a['telefono_contacto'] ?? '' ?></td>

                                        <td class="td truncate-cell">
                                            <?= $a['correo_contacto'] ?? '' ?>
                                        </td>

                                        <td class="td">
                                            <span class="pill-blue">
                                                <?= $a['estado_etapa'] ?? '' ?>
                                            </span>
                                        </td>

                                        <td class="td text-center">
                                            <?= !empty($a['reingreso_vencimiento']) ? 'Sí' : 'No' ?>
                                        </td>

                                        <td class="td"><?= $a['cambio_modalidad'] ?? '' ?></td>

                                        <td class="td truncate-cell">
                                            <?= $a['observaciones_novedad'] ?: '—' ?>
                                        </td>

                                        <td class="td text-center"><?= !empty($a['doc_gfpi_165']) ? '✓' : '—' ?></td>

                                        <td class="td"><?= $a['instructor_asignado'] ?? '' ?></td>
                                        <td class="td"><?= $a['telefono_instructor'] ?? '' ?></td>

                                        <td class="td truncate-cell">
                                            <?= $a['correo_instructor'] ?? '' ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

        <style>

            .group-header{
                padding: 10px 8px;
                text-align: center;
                font-weight: 600;
                border-bottom: 1px solid #e5e7eb;
                border-right: 1px solid #e5e7eb;
                white-space: nowrap;
                font-size: 12px;
            }

            .th{
                padding: 10px 8px;
                text-align: left;
                font-weight: 600;
                white-space: nowrap;
                border-bottom: 1px solid #e5e7eb;
                border-right: 1px solid #e5e7eb;
                font-size: 12px;
                color: #475569;
                background: #f8fafc;
            }

            .td{
                padding: 10px 8px;
                border-right: 1px solid #e5e7eb;
                border-bottom: 1px solid #f1f5f9;
                white-space: nowrap;
                font-size: 12px;
            }

            .truncate-cell{
                max-width: 150px;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .pill-blue{
                display: inline-flex;
                align-items: center;
                border-radius: 9999px;
                padding: 2px 8px;
                font-size: 11px;
                background: #dbeafe;
                color: #0369a1;
            }

        </style>
</div>