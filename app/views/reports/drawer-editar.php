<!-- POPUP LATERAL -->
<div id="documentPopup" class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 transition-transform duration-300 ease-in-out" style="transform: translateX(100%);">
    
    <!-- HEADER -->
    <div class="p-4 border-b border-gray-200 bg-slate-50 sticky top-0 z-10">
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

    <!-- CONTENT - CON SCROLL -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4" style="height: calc(100% - 130px);">
        
        <!-- SECTION 1 -->
        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-gray-100 px-4 py-3 text-sm font-semibold text-left" data-section="s1">
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

        <!-- SECTION 2 -->
        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-gray-100 px-4 py-3 text-sm font-semibold text-left" data-section="s2">
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

        <!-- SECTION 3 -->
        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <button type="button" class="section-toggle w-full flex items-center justify-between bg-gray-100 px-4 py-3 text-sm font-semibold text-left" data-section="s3">
                Novedades / cambios
                <span class="section-icon" data-section="s3">⌄</span>
            </button>
            <div id="s3" class="section-content p-4 space-y-3" style="display: block;">
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1">Observaciones de novedad</label>
                    <textarea id="observaciones_novedad" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                
                <div>
                    <label class="text-xs text-gray-500 font-medium block mb-1">Cambio modalidad</label>
                    <select id="cambio_modalidad" class="w-full h-10 border border-gray-300 rounded-lg px-3">
                        <option>Ninguno</option>
                        <option>Condicionado</option>
                        <option>Cancelado</option>
                    </select>
                </div>
                
                <div class="flex items-center justify-between text-sm pt-2">
                    <span>Reingreso por vencimiento</span>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="reingreso" class="doc-checkbox hidden">
                        <div class="w-11 h-6 bg-gray-200 rounded-full relative transition-colors duration-200 cursor-pointer">
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform duration-200"></div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="p-4 border-t border-gray-200 bg-white sticky bottom-0">
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

<!-- Overlay -->
<div id="popupOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"></div>

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
    // Configurar checkboxes personalizados
    document.querySelectorAll('.doc-checkbox').forEach(checkbox => {
        const toggleDiv = checkbox.nextElementSibling;
        if (toggleDiv) {
            toggleDiv.addEventListener('click', function(e) {
                e.stopPropagation();
                checkbox.checked = !checkbox.checked;
                if (checkbox.checked) {
                    this.style.backgroundColor = '#059669';
                    this.querySelector('div').style.transform = 'translateX(20px)';
                } else {
                    this.style.backgroundColor = '#e5e7eb';
                    this.querySelector('div').style.transform = 'translateX(0px)';
                }
            });
        }
    });
    
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