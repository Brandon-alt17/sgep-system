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

        <div class="w-64 shrink-0">
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
                                'En ejecución',
                                'Aplazada',
                                'Finalizada',
                                'Certificado',
                                'Pendiente por comité', => ui_badge_success_classes(),
                                'Pendiente por iniciar' => ui_badge_warning_classes(),
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
        
        <!-- 👇 HEADER con fondo gris como la imagen -->
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

        <tbody>
        <?php if (empty($aprendices)): ?>
            <tr class="border-b bg-app-panelSubtle/40">
                <td class="<?= e(ui_td_classes()) ?> align-middle text-center text-app-muted" colspan="7">Sin aprendices registrados aún</td>
            </tr>
        <?php else: ?>
        <?php foreach (($aprendices ?? []) as $aprendiz): ?>

            <!-- 👇 hover suave como la imagen -->
            <tr class="border-b hover:bg-gray-50 transition">

                <td class="<?= e(ui_td_classes()) ?> font-medium">
                    <?= e($aprendiz['nombre_completo']) ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?> text-gray-600">
                    <?= e($aprendiz['numero_documento']) ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?>">
                    <?= e( $aprendiz['empresa_nombre'] ?? '-') ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?> text-gray-600">
                    <?= e($aprendiz['ficha'] ?? '-') ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?>">
                    <?php
                        $estado = $aprendiz['estado'] ?? '';

                        if ($estado === 'En etapa productiva') {
                            echo '<span class="'.e(ui_badge_success_classes()).'">'.$estado.'</span>';
                        } elseif ($estado === 'Pendiente') {
                            echo '<span class="'.e(ui_badge_warning_classes()).'">'.$estado.'</span>';
                        } elseif ($estado === 'Certificado') {
                            echo '<span class="'.e(ui_badge_success_classes()).'">'.$estado.'</span>';
                        } else {
                            echo '<span class="'.e(ui_badge_error_classes()).'">'.$estado.'</span>';
                        }
                    ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?> text-gray-500">
                    <?= e($aprendiz['ultima_visita'] ?? '—') ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?>">
                    <a class="<?= e(ui_button_small_classes()) ?>" 
                       href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int)$aprendiz['id'] ?>">
                        Ver perfil
                    </a>
                </td>

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

        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

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