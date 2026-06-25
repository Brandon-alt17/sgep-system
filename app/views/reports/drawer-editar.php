<!-- POPUP LATERAL -->
<div id="popupOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden transition-opacity duration-300" style="z-index: 9998;"></div>
<div id="documentPopup" class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl transition-transform duration-300 ease-in-out flex flex-col" style="z-index: 9999; transform: translateX(100%);">
    <!-- HEADER (sin cambios) -->
    <div class="p-4 border-b border-gray-200 bg-slate-50 flex-shrink-0">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Completar / editar registro
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    <span id="popupAprendizNombre" class="font-semibold text-gray-900"></span>
                    <br>
                    Estos campos se pueden completar en cualquier momento.
                </p>
            </div>
            <button id="closePopupBtn" class="text-gray-400 hover:text-gray-700 text-xl cursor-pointer" style="background: none; border: none;">
                ✕
            </button>
        </div>
    </div>

    <!-- CONTENT - CON SCROLL CORRECTO -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4" style="min-height: 0;">
        <?php
        /** @var array<string, list<string>> $novedadesEtapaOptions */
        $novedadesEtapaOptions = require base_path('config/novedades_etapa_options.php');
        $novedadesEtapaLabels = [
            'estado_etapa' => 'Estado de la etapa productiva',
            'llamados_atencion' => 'Llamados de atención',
            'otros_novedad' => 'Otros',
            'comite_evaluacion' => 'Comité de evaluación',
            'reingreso_vencimiento' => 'Reingreso especial por vencimiento de términos',
        ];
        ?>

        <!-- REGLAMENTO -->
        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-purple-50 px-4 py-3 text-sm font-semibold text-left" data-section="s0">
                Reglamento
                <span class="section-icon" data-section="s0">⌄</span>
            </button>
            <div id="s0" class="section-content p-4 space-y-4" style="display: block;">
                <p class="text-xs text-gray-500 m-0">Seleccione un solo acuerdo. El vencimiento se calcula desde la fecha fin de plataforma.</p>
                <div class="space-y-2 text-sm">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="reglamento_acuerdo" value="007" id="reglamento_acuerdo_007" class="h-4 w-4">
                        <span>Acuerdo 007 de 2012 (18 meses)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="reglamento_acuerdo" value="009" id="reglamento_acuerdo_009" class="h-4 w-4">
                        <span>Acuerdo 009 de 2024 (12 meses)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="reglamento_acuerdo" value="" id="reglamento_acuerdo_ninguno" class="h-4 w-4">
                        <span>Ninguno</span>
                    </label>
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1" for="vencimiento_terminos_display">Venc. términos</label>
                    <input type="text" id="vencimiento_terminos_display" readonly class="w-full h-10 border border-gray-200 rounded-lg px-3 bg-gray-50 text-sm text-gray-700" placeholder="Seleccione un acuerdo y registre fecha fin de plataforma">
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1" for="semaforo_vencimiento_display">Semáforo de vencimiento</label>
                    <input type="text" id="semaforo_vencimiento_display" readonly class="w-full h-10 border border-gray-200 rounded-lg px-3 bg-gray-50 text-sm text-gray-700" placeholder="SIN FECHA">
                </div>
            </div>
        </div>

        <!-- INFORMACIÓN DEL PROGRAMA -->
        <div class="border border-orange-200 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-900 text-left" data-section="s_programa">
                Información del programa
                <span class="section-icon" data-section="s_programa">⌄</span>
            </button>
            <div id="s_programa" class="section-content p-4 space-y-3" style="display: block;">
                <p class="text-xs text-gray-500 m-0">Estas fechas no vienen en el Excel de importación; regístrelas aquí para el reporte.</p>
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1" for="fecha_inicio_plataforma">Fecha de inicio en plataforma</label>
                    <input type="date" id="fecha_inicio_plataforma" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1" for="inicio_etapa_productiva">Inicio de etapa productiva</label>
                    <input type="date" id="inicio_etapa_productiva" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1" for="fecha_fin_plataforma">Fecha de terminación en plataforma</label>
                    <input type="date" id="fecha_fin_plataforma" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm">
                </div>
            </div>
        </div>

        <!-- INFORMACIÓN DEL APRENDIZ -->
        <div class="border border-sky-200 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900 text-left" data-section="s_aprendiz">
                Información del aprendiz
                <span class="section-icon" data-section="s_aprendiz">⌄</span>
            </button>
            <div id="s_aprendiz" class="section-content p-4 space-y-3" style="display: block;">
                <p class="text-xs text-gray-500 m-0">La fecha aval no viene en el Excel de importación; regístrela aquí para el reporte.</p>
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1" for="fecha_aval_modalidad">Fecha aval modalidad</label>
                    <input type="date" id="fecha_aval_modalidad" class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm">
                </div>
            </div>
        </div>
        
        <!-- NOVEDADES DE LA ETAPA PRODUCTIVA -->
        <div class="border border-lime-300 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-lime-50 px-4 py-3 text-sm font-semibold text-lime-900 text-left" data-section="s_etapa">
                Novedades de la etapa productiva
                <span class="section-icon" data-section="s_etapa">⌄</span>
            </button>
            <div id="s_etapa" class="section-content p-4 space-y-3" style="display: block;">
                <?php foreach ($novedadesEtapaLabels as $fieldId => $fieldLabel): ?>
                    <div>
                        <label class="text-xs text-gray-500 font-medium block mb-1" for="<?= e($fieldId) ?>"><?= e($fieldLabel) ?></label>
                        <select id="<?= e($fieldId) ?>" class="w-full min-h-10 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— Sin seleccionar —</option>
                            <?php foreach ($novedadesEtapaOptions[$fieldId] ?? [] as $option): ?>
                                <option value="<?= e($option) ?>"><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1" for="observaciones_novedad">Observaciones de novedad</label>
                    <textarea id="observaciones_novedad" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>
        </div>

        <!-- PROCESO DOCUMENTAL DEL SEGUIMIENTO -->
        <div class="border border-orange-200 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-900 text-left" data-section="s1">
                Datos del proceso documental
                <span class="section-icon" data-section="s1">⌄</span>
            </button>
            <div id="s1" class="section-content p-4 space-y-3" style="display: block;">
                <?php
                $docSeguimiento = [
                    "doc_gfpi_165" => "GFPI-F-165 Selección/Modificación Alternativa",
                    "doc_momento_1" => "GFPI-F-023 Momento 1 — Planeación",
                    "doc_bitacora_1" => "GFPI-F-147 Bitácora 1",
                    "doc_bitacora_2" => "GFPI-F-147 Bitácora 2",
                    "doc_bitacora_3" => "GFPI-F-147 Bitácora 3 — Momento 2",
                    "doc_bitacora_4" => "GFPI-F-147 Bitácora 4",
                    "doc_bitacora_5" => "GFPI-F-147 Bitácora 5",
                    "doc_bitacora_6" => "GFPI-F-147 Bitácora 6",
                    "doc_momento_final" => "GFPI-F-023 Momento Final — Evaluación",
                ];
                foreach ($docSeguimiento as $key => $label):
                ?>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 text-sm">
                        <span><?= $label ?></span>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="doc-checkbox hidden" id="<?= $key ?>">
                            <div class="w-11 h-6 bg-gray-200 rounded-full relative transition-colors duration-200 cursor-pointer">
                                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200"></div>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center gap-3 py-1" aria-hidden="true">
            <div class="flex-1 border-t-2 border-dashed border-gray-300"></div>
        </div>

        <!-- DOCUMENTOS PARA CERTIFICACIÓN -->
        <div class="border border-emerald-200 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 text-left" data-section="s2">
                Documentos para certificación
                <span class="section-icon" data-section="s2">⌄</span>
            </button>
            <div id="s2" class="section-content p-4 space-y-3" style="display: block;">
                <?php
                $docCertificacion = [
                    "cert_doc_identidad" => "Documento de identidad vigente",
                    "cert_paz_salvo" => "Paz y salvo",
                    "cert_gfpi_023" => "GFPI-F-023 Formato completo",
                    "cert_bitacoras" => "GFPI-F-147 Bitácoras completas",
                    "cert_cumplimiento" => "Certificado cumplimiento",
                    "cert_ape" => "APE",
                    "cert_carnet" => "Destrucción carnet",
                    "cert_saber_tyt" => "Saber T&T",
                ];
                foreach ($docCertificacion as $key => $label):
                ?>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 text-sm">
                        <span><?= $label ?></span>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="doc-checkbox hidden" id="<?= $key ?>">
                            <div class="w-11 h-6 bg-gray-200 rounded-full relative transition-colors duration-200 cursor-pointer">
                                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200"></div>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1">Fecha entrega</label>
                    <input type="date" id="fecha_entrega" class="w-full h-10 border border-gray-300 rounded-lg px-3">
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1">Estado aprendiz</label>
                    <select id="estado_aprendiz" class="w-full h-10 border border-gray-300 rounded-lg px-3">
                        <option>En formación</option>
                        <option>Por certificar</option>
                        <option>Certificado</option>
                    </select>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1">Observaciones</label>
                    <textarea id="observaciones" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
            </div>
        </div>

        <!-- Espacio adicional para que el contenido no quede pegado al footer -->
        <div class="h-4"></div>
    </div>

    <!-- FOOTER - sin sticky, ya que el contenedor padre maneja el flex -->
    <div class="p-4 border-t border-gray-200 bg-white flex-shrink-0">
        <div class="flex justify-end gap-3">
            <button id="cancelPopupBtn" class="px-4 h-10 rounded-lg border border-gray-300 text-sm hover:bg-gray-50 cursor-pointer">
                Cancelar
            </button>
            <button id="saveDocsBtn" class="px-4 h-10 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm cursor-pointer">
                Guardar cambios
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos para los checkboxes */
.doc-checkbox:checked + div {
    background-color: #059669;
}

.doc-checkbox:checked + div div {
    transform: translateX(20px);
}

/* Scroll personalizado */
#documentPopup .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

#documentPopup .overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#documentPopup .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#documentPopup .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
// Inicializar checkboxes visuales
document.addEventListener('DOMContentLoaded', function() {
    // Función para actualizar el estado visual de un switch
    function updateSwitchVisual(checkbox) {
        const toggleDiv = checkbox.nextElementSibling;
        if (toggleDiv) {
            if (checkbox.checked) {
                toggleDiv.style.backgroundColor = '#059669';
                const innerDiv = toggleDiv.querySelector('div');
                if (innerDiv) innerDiv.style.transform = 'translateX(20px)';
            } else {
                toggleDiv.style.backgroundColor = '#e5e7eb';
                const innerDiv = toggleDiv.querySelector('div');
                if (innerDiv) innerDiv.style.transform = 'translateX(0px)';
            }
        }
    }

    // Configurar todos los switches existentes
    function setupSwitch(checkbox) {
        const toggleDiv = checkbox.nextElementSibling;
        if (!toggleDiv) return;
        
        // Actualizar visual inicial
        updateSwitchVisual(checkbox);
        
        // Remover event listeners anteriores para evitar duplicados
        const newToggleDiv = toggleDiv.cloneNode(true);
        toggleDiv.parentNode.replaceChild(newToggleDiv, toggleDiv);
        
        // Crear nuevo evento
        newToggleDiv.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            checkbox.checked = !checkbox.checked;
            updateSwitchVisual(checkbox);
            // Disparar evento change para que otros listeners se enteren
            const changeEvent = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(changeEvent);
        });
    }

    // Configurar todos los checkboxes existentes
    document.querySelectorAll('.doc-checkbox').forEach(checkbox => {
        setupSwitch(checkbox);
    });
    
    // Observer para nuevos checkboxes que puedan agregarse dinámicamente
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    if (node.classList && node.classList.contains('doc-checkbox')) {
                        setupSwitch(node);
                    }
                    node.querySelectorAll && node.querySelectorAll('.doc-checkbox').forEach(setupSwitch);
                }
            });
        });
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
    
    // Configurar toggles de secciones
    document.querySelectorAll('.section-toggle').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const sectionId = this.getAttribute('data-section');
            const content = document.getElementById(sectionId);
            const icon = this.querySelector('.section-icon');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.textContent = '⌄';
            } else {
                content.style.display = 'none';
                icon.textContent = '⌃';
            }
        });
    });
});
</script>