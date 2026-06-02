<script src="<?= e(APP_BASE_PATH) ?>/js/drawer-editar.js"></script>
<?php require __DIR__ . '/drawer-editar.php'; ?>

<!-- Agregar script para manejar la exportación con datos de localStorage -->
<script>
// Función para obtener los datos del localStorage y agregarlos a la tabla antes de exportar
function prepareExportData() {
    if (typeof window.exportToExcelWithLocalData === 'function') {
        const exportData = window.exportToExcelWithLocalData();
        
        // Actualizar la tabla con los datos de localStorage antes de exportar
        document.querySelectorAll('tr[data-id]').forEach(row => {
            const aprendizId = row.dataset.id;
            const savedData = localStorage.getItem(`formulario_${aprendizId}`);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    updateTableRowForExport(row, data);
                } catch(e) {
                    console.error('Error:', e);
                }
            }
        });
        
        return true;
    }
    return false;
}

// Función para actualizar la tabla específicamente para exportación
function updateTableRowForExport(row, data) {
    // Mapeo de columnas (basado en la estructura de tu tabla)
    const columnMapping = {
        'doc_gfpi_165': 34,
        'doc_momento_1': 35,
        'doc_bitacora_1': 36,
        'doc_bitacora_2': 37,
        'doc_bitacora_3': 38,
        'doc_bitacora_4': 39,
        'doc_bitacora_5': 40,
        'doc_bitacora_6': 41,
        'doc_momento_final': 42,
        'cert_doc_identidad': 43,
        'cert_paz_salvo': 44,
        'cert_gfpi_023': 45,
        'cert_bitacoras': 46,
        'cert_cumplimiento': 47,
        'cert_ape': 48,
        'cert_carnet': 49,
        'fecha_entrega': 51,
        'estado_aprendiz': 52,
        'observaciones': 53,
        'cambio_modalidad': 32,
        'observaciones_novedad': 33,
        'reingreso': 31
    };
    
    const cells = row.querySelectorAll('td');
    
    for (const [key, colIndex] of Object.entries(columnMapping)) {
        if (data[key] !== undefined && cells[colIndex]) {
            if (key === 'reingreso') {
                cells[colIndex].innerHTML = data[key] ? 'Sí' : 'No';
            } else if (key === 'doc_gfpi_165' || key === 'doc_momento_1' || key === 'doc_bitacora_1' || 
                       key === 'doc_bitacora_2' || key === 'doc_bitacora_3' || key === 'doc_bitacora_4' || 
                       key === 'doc_bitacora_5' || key === 'doc_bitacora_6' || key === 'doc_momento_final' ||
                       key === 'cert_doc_identidad' || key === 'cert_paz_salvo' || key === 'cert_gfpi_023' ||
                       key === 'cert_bitacoras' || key === 'cert_cumplimiento' || key === 'cert_ape' || key === 'cert_carnet') {
                cells[colIndex].innerHTML = data[key] ? 'X' : '—';
            } else {
                cells[colIndex].innerHTML = data[key] || '—';
            }
        }
    }
}

// Interceptar el clic en el botón de exportar para incluir datos de localStorage
document.addEventListener('DOMContentLoaded', function() {
    const exportBtn = document.querySelector('a[href*="exportar"]');
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            prepareExportData();
            // Dar tiempo para que se actualice la tabla
            setTimeout(() => {
                // El enlace se abrirá normalmente después de este pequeño delay
            }, 100);
        });
    }
});
</script>

<!-- CONTENEDOR PRINCIPAL CON ANCHO FIJO Y OVERFLOW ESCONDIDO -->
<div class="w-full overflow-x-hidden" style="max-width: 100vw;">
<div style="max-width: 100vw; overflow-x: hidden;">
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
    <form method="GET" action="" class="flex flex-wrap items-center gap-3">
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

        <select name="ficha" onchange="this.form.submit()" class="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm min-w-[160px]">
            <option value="">Todos</option>
            <?php foreach ($fichas as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>" <?= ((string)$f === (string)($_GET['ficha'] ?? '')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="estado" onchange="this.form.submit()" class="h-10 px-3 rounded-lg border border-gray-300 bg-white text-sm min-w-[180px]">
            <option value="">Todos</option>
            <?php foreach ($estados as $e): ?>
                <option value="<?= htmlspecialchars($e) ?>" <?= ((string)$e === (string)($_GET['estado'] ?? '')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="flex-1"></div>

        <?php
        $exportQuery = [];
        foreach (['estado', 'ficha', 'programa_id'] as $filterKey) {
            $v = trim((string) ($_GET[$filterKey] ?? ''));
            if ($v !== '') {
                $exportQuery[$filterKey] = $v;
            }
        }
        $exportUrl = APP_BASE_PATH . '/reportes/exportar'
            . ($exportQuery === [] ? '' : '?' . http_build_query($exportQuery));
        ?>
        <a href="<?= e($exportUrl) ?>" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 h-10 rounded-lg transition-colors" id="exportBtn">
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

    <!-- TABLA CON SCROLL HORIZONTAL -->
    <div class="bg-white border border-gray-200 rounded-2xl">
        <div style="overflow-x: auto; overflow-y: visible; width: 100%;">
            <table style="min-width: 2800px; width: 100%; border-collapse: collapse;" class="text-xs">
                <thead>
                    <tr>
                        <th colspan="10" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #f3f4f6;">Aprendices y grupos</th>
                        <th colspan="5" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #f3e8ff;">Reglamento</th>
                        <th colspan="8" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #dbeafe;">Información del aprendiz</th>
                        <th colspan="12" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #ccfbf1;">Información de la etapa productiva</th>
                        <th colspan="9" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #ffedd5;">Proceso documental del seguimiento</th>
                        <th colspan="11" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #d1fae5;">Documentos para certificación</th>
                        <th colspan="3" style="padding: 10px 8px; text-align: center; font-weight: 600; border: 1px solid #e5e7eb; background: #f3f4f6;">Instructor de seguimiento</th>
                    </tr>
                    <tr style="background: #f9fafb;">
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">#</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Ficha</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Cód. programa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Programa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Nivel</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Modalidad</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F. inicio plat.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F. fin plat.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Instructor jefe</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Ac. 007</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Ac. 009</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">≤18 meses</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">≤12 meses</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Venc. términos</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Nombre</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">N° identificación</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Celular</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Correo</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Mod. práctica</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F. aval</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Estado ARL</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">ARL</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F. inicio</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">F. fin</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Empresa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Dir. empresa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Ciudad</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Contacto</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Tel. contacto</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Correo contacto</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Estado etapa</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Reingreso</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Cambio/Cond./Canc.</th>
                        <th style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;">Obs. novedad</th>
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
                            class="cursor-pointer hover:bg-gray-50 transition apprentice-row" 
                            data-id="<?= $a['identificacion'] ?? $a['num_aprendiz'] ?? '' ?>"
                            data-nombre="<?= htmlspecialchars($a['nombre'] ?? '') ?>"
                            data-index="<?= $index ?>"
                        >                        
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['num_aprendiz'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['ficha'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['codigo_programa'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['programa_formacion'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><span style="display: inline-flex; border-radius: 9999px; padding: 2px 8px; font-size: 11px; background: #dbeafe;"><?= $a['nivel'] ?? '' ?></span></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['modalidad_programa'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['fecha_inicio_plataforma'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['fecha_fin_plataforma'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['instructor_jefe'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;"><?= !empty($a['acuerdo_007']) ? '✓' : '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;"><?= !empty($a['acuerdo_009']) ? '✓' : '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;"><?= !empty($a['inicio_18_meses']) ? '✓' : '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;"><?= !empty($a['inicio_12_meses']) ? '✓' : '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;"><?= !empty($a['vencimiento_terminos']) ? '✓' : '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; font-weight: 500;"><?= $a['nombre'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['identificacion'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['celular'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['correo'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['modalidad_practica'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['fecha_aval_modalidad'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['estado_arl'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['arl'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['fecha_inicio_etapa'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['fecha_fin_etapa'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['empresa'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['direccion_empresa'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['ciudad'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['contacto_empresa'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['telefono_contacto'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['correo_contacto'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><span style="display: inline-flex; border-radius: 9999px; padding: 2px 8px; font-size: 11px; background: #dbeafe;"><?= $a['estado_etapa'] ?? '' ?></span></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;"><?= !empty($a['reingreso_vencimiento']) ? 'Sí' : 'No' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['cambio_modalidad'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['observaciones_novedad'] ?: '—' ?></td>
                            
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
                            
                            <td style="padding: 8px; border: 1px solid #e5e7eb; text-align: center;"><?= !empty($a['cert_saber_tyt']) ? '✓' : '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="fecha-entrega"><?= e((string) ($a['fecha_entrega_admin'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;" class="estado-aprendiz"><?= e((string) ($a['estado_aprendiz'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;" class="observaciones"><?= e((string) ($a['observaciones_cert'] ?? '')) ?: '—' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= e((string) ($a['instructor_asignado'] ?? '')) ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap;"><?= $a['telefono_instructor'] ?? '' ?></td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; white-space: nowrap; max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= $a['correo_instructor'] ?? '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>

<script>
// Script adicional para asegurar que los datos de localStorage se carguen después de que la tabla esté lista
document.addEventListener('DOMContentLoaded', function() {
    // Pequeño delay para asegurar que drawer-editar.js ya se haya ejecutado
    setTimeout(function() {
        if (typeof loadAllTableData === 'function') {
            loadAllTableData();
        } else if (window.loadAllTableData) {
            window.loadAllTableData();
        }
    }, 100);
});
</script>