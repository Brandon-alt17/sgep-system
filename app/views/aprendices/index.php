<?php partial('components/page_header', [
    'title' => 'Aprendices',
    'actions' => '
        <a class="'.e(ui_button_small_primary_classes()).'" href="'.e(APP_BASE_PATH).'/aprendices/create">
            Nuevo aprendiz
        </a>
    '
]); ?>

<!-- Buscador -->
<section class="<?= e(ui_card_classes()) ?> mb-4">
    <div class="flex items-center gap-3 w-full">
        <!-- 🔍 Buscador -->
        <input type="text" id="search-input" placeholder="Buscar por nombre o documento" class="<?= e(ui_input_classes()) ?> flex-1 min-w-0">

        <div class="w-64 shrink-0">
            <select id="ficha-filter" class="<?= e(ui_select_classes()) ?>">
                <option value="">Todas las fichas</option>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </div>

        <div  classs="w-64 shrink-0">
            <select id="estado-filter" class="<?= e(ui_select_classes()) ?>">
                <option value="">Todos los estados</option>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </div>
    </div>
</section>

<!-- Tabla -->
<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="aprendices-table" class="<?= e(ui_table_classes()) ?>">
            <thead class="bg-app-panelSubtle border-b">
                <tr>
                    <th class="<?= e(ui_th_classes()) ?>">Nombre completo</th>
                    <th class="<?= e(ui_th_classes()) ?>">Documento</th>
                    <th class="<?= e(ui_th_classes()) ?>">Empresa</th>
                    <th class="<?= e(ui_th_classes()) ?>">Grupo</th>
                    <th class="<?= e(ui_th_classes()) ?>">Estado</th>
                    <th class="<?= e(ui_th_classes()) ?>">Última visita</th>
                    <th class="<?= e(ui_th_classes()) ?>"></th>
                </tr>
            </thead>
            <tbody id="table-body">
            <?php foreach (($aprendices ?? []) as $aprendiz): ?>
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="<?= e(ui_td_classes()) ?> font-medium" data-field="nombre">
                        <?= e($aprendiz['nombre_completo']) ?>
                    </td>
                    <td class="<?= e(ui_td_classes()) ?> text-gray-600" data-field="documento">
                        <?= e($aprendiz['numero_documento']) ?>
                    </td>
                    <td class="<?= e(ui_td_classes()) ?>" data-field="empresa">
                        <?= e($aprendiz['empresa_nombre'] ?? '-') ?>
                    </td>
                    <td class="<?= e(ui_td_classes()) ?> text-gray-600" data-field="ficha">
                        <?= e($aprendiz['ficha'] ?? '-') ?>
                    </td>
                    <td class="<?= e(ui_td_classes()) ?>" data-field="estado">
                        <?php
                            $estado = $aprendiz['estado'] ?? '';
                            $badgeClass = match($estado) {
                                'En etapa productiva', 'Certificado' => ui_badge_success_classes(),
                                'Pendiente' => ui_badge_warning_classes(),
                                default => ui_badge_error_classes()
                            };
                        ?>
                        <span class="<?= e($badgeClass) ?>"><?= e($estado) ?></span>
                    </td>
                    <td class="<?= e(ui_td_classes()) ?> text-gray-500" data-field="ultima_visita">
                        <?= e($aprendiz['ultima_visita'] ?? '—') ?>
                    </td>
                    <td class="<?= e(ui_td_classes()) ?>">
                        <a class="<?= e(ui_button_small_classes()) ?>" 
                           href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int)$aprendiz['id'] ?>">
                            Ver perfil
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Agregar el JavaScript al final -->
<script>
// Función para actualizar los filtros
function actualizarFiltros() {
    console.log('=== ACTUALIZANDO FILTROS ===');
    
    // Obtener el cuerpo de la tabla
    const tbody = document.getElementById('table-body');
    if (!tbody) {
        console.error('No se encontró el tbody');
        return;
    }
    
    // Obtener todas las filas
    const filas = tbody.querySelectorAll('tr');
    console.log('Filas encontradas:', filas.length);
    
    // Colecciones para valores únicos
    const fichasUnicas = new Set();
    const estadosUnicos = new Set();
    
    // Recorrer cada fila
    filas.forEach((fila, index) => {
        // Obtener todas las celdas
        const celdas = fila.querySelectorAll('td');
        
        if (celdas.length >= 5) {
            // La ficha está en la celda 4 (índice 3)
            const ficha = celdas[3].textContent.trim();
            console.log(`Fila ${index} - Ficha: "${ficha}"`);
            if (ficha && ficha !== '-' && ficha !== '—') {
                fichasUnicas.add(ficha);
            }
            
            // El estado está en la celda 5 (índice 4)
            const spanEstado = celdas[4].querySelector('span');
            let estado = spanEstado ? spanEstado.textContent.trim() : celdas[4].textContent.trim();
            console.log(`Fila ${index} - Estado: "${estado}"`);
            if (estado && estado !== '') {
                estadosUnicos.add(estado);
            }
        }
    });
    
    console.log('Fichas únicas:', Array.from(fichasUnicas));
    
    // Actualizar select de fichas
    const selectFicha = document.getElementById('ficha-filter');
    if (selectFicha) {
        // Guardar el valor seleccionado actual
        const valorActual = selectFicha.value;
        
        // Limpiar opciones (dejar solo la primera)
        while (selectFicha.options.length > 1) {
            selectFicha.remove(1);
        }
        
        // Agregar nuevas opciones
        const fichasArray = Array.from(fichasUnicas).sort();
        fichasArray.forEach(ficha => {
            const option = document.createElement('option');
            option.value = ficha;
            option.textContent = ficha;
            selectFicha.appendChild(option);
        });
        
        // Restaurar valor si aún existe
        if (valorActual && fichasArray.includes(valorActual)) {
            selectFicha.value = valorActual;
        }
        
        console.log('Select de fichas actualizado, opciones:', selectFicha.options.length);
    } else {
        console.error('No se encontró el select con id "ficha-filter"');
    }
    
    // Actualizar select de estados
    const selectEstado = document.getElementById('estado-filter');
    if (selectEstado) {
        // Guardar el valor seleccionado actual
        const valorActual = selectEstado.value;
        
        // Limpiar opciones (dejar solo la primera)
        while (selectEstado.options.length > 1) {
            selectEstado.remove(1);
        }
        
        // Agregar nuevas opciones
        const estadosArray = Array.from(estadosUnicos).sort();
        estadosArray.forEach(estado => {
            const option = document.createElement('option');
            option.value = estado;
            option.textContent = estado;
            selectEstado.appendChild(option);
        });
        
        // Restaurar valor si aún existe
        if (valorActual && estadosArray.includes(valorActual)) {
            selectEstado.value = valorActual;
        }
        
        console.log('Select de estados actualizado, opciones:', selectEstado.options.length);
    } else {
        console.error('No se encontró el select con id "estado-filter"');
    }
}

// Función para filtrar la tabla
function filtrarTabla() {
    const busqueda = document.getElementById('search-input')?.value.toLowerCase().trim() || '';
    const fichaSeleccionada = document.getElementById('ficha-filter')?.value || '';
    const estadoSeleccionado = document.getElementById('estado-filter')?.value || '';
    
    const tbody = document.getElementById('table-body');
    if (!tbody) return;
    
    const filas = tbody.querySelectorAll('tr');
    let filasVisibles = 0;
    
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length >= 5) {
            const nombre = celdas[0].textContent.toLowerCase();
            const documento = celdas[1].textContent;
            const ficha = celdas[3].textContent.trim();
            const spanEstado = celdas[4].querySelector('span');
            const estado = spanEstado ? spanEstado.textContent.trim() : celdas[4].textContent.trim();
            
            const coincideBusqueda = busqueda === '' || nombre.includes(busqueda) || documento.includes(busqueda);
            const coincideFicha = fichaSeleccionada === '' || ficha === fichaSeleccionada;
            const coincideEstado = estadoSeleccionado === '' || estado === estadoSeleccionado;
            
            if (coincideBusqueda && coincideFicha && coincideEstado) {
                fila.style.display = '';
                filasVisibles++;
            } else {
                fila.style.display = 'none';
            }
        }
    });
    
    // Mostrar mensaje si no hay resultados
    mostrarMensaje(filasVisibles === 0);
}

// Función para mostrar/ocultar mensaje de sin resultados
function mostrarMensaje(mostrar) {
    const tbody = document.getElementById('table-body');
    if (!tbody) return;
    
    let mensajeRow = document.getElementById('no-results-message');
    
    if (mostrar && !mensajeRow) {
        mensajeRow = document.createElement('tr');
        mensajeRow.id = 'no-results-message';
        const thead = document.querySelector('#aprendices-table thead');
        const numColumnas = thead ? thead.querySelectorAll('th').length : 7;
        mensajeRow.innerHTML = `
            <td colspan="${numColumnas}" class="text-center py-8 text-gray-500">
                No se encontraron resultados que coincidan con los filtros seleccionados.
            </td>
        `;
        tbody.appendChild(mensajeRow);
    } else if (!mostrar && mensajeRow) {
        mensajeRow.remove();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando...');
    
    // Esperar un momento para asegurar que la tabla esté cargada
    setTimeout(function() {
        actualizarFiltros();
        
        // Agregar event listeners
        const searchInput = document.getElementById('search-input');
        const fichaFilter = document.getElementById('ficha-filter');
        const estadoFilter = document.getElementById('estado-filter');
        
        if (searchInput) {
            searchInput.addEventListener('input', filtrarTabla);
            console.log('Event listener agregado al buscador');
        }
        
        if (fichaFilter) {
            fichaFilter.addEventListener('change', filtrarTabla);
            console.log('Event listener agregado al filtro de fichas');
        }
        
        if (estadoFilter) {
            estadoFilter.addEventListener('change', filtrarTabla);
            console.log('Event listener agregado al filtro de estados');
        }
        
        // Forzar una actualización adicional después de cargar todo
        setTimeout(actualizarFiltros, 100);
    }, 100);
});
</script>