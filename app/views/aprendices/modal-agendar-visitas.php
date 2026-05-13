<style>
#modal-visitas {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);
    z-index: 99999; align-items: center; justify-content: center;
}
#modal-visitas.hidden { display: none !important; }
#modal-visitas:not(.hidden) { display: flex !important; }
#modal-overlay-visitas {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    z-index: 99998; cursor: pointer;
}
.modal-container {
    position: relative; background: white; border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    z-index: 99999; display: flex; flex-direction: column;
}
.modal-container.aprendiz-size { max-width: 720px; width: 90%; max-height: 85vh; }
.modal-body { overflow-y: auto; max-height: calc(85vh - 130px); padding-right: 6px; }
.modal-body::-webkit-scrollbar { width: 6px; }
.modal-body::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
.visita-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 1rem; background: #f9fafb; }
.visita-card.disabled { opacity: 0.5; pointer-events: none; }
.form-input { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; }
.form-input:disabled { background-color: #f3f4f6; cursor: not-allowed; }
.form-label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem; }
.badge { font-size: 0.75rem; padding: 2px 8px; border-radius: 999px; font-weight: 500; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
</style>

<div id="modal-visitas" class="hidden">
    <div id="modal-overlay-visitas" onclick="cerrarModalVisitas()"></div>
    
    <div class="modal-container aprendiz-size">
        <!-- HEADER -->
        <div class="p-6 pb-2 border-b">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                        <span class="w-4 h-4 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('calendar') ?></span>
                        Programar visitas
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        La fecha del Momento 1 se guarda como la próxima visita.
                    </p>
                </div>
                <button onclick="cerrarModalVisitas()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
        </div>

        <!-- BODY -->
        <div class="modal-body p-6 pt-4">
            <!-- 1. Agregamos action y method al formulario -->
            <!-- 2. Agregamos el input hidden para el ID del aprendiz -->
            <form id="form-visitas" action="<?= e(APP_BASE_PATH) ?>/aprendices/update-visitas" method="POST" class="space-y-4">
                <input type="hidden" name="aprendiz_id" value="<?= (int)($aprendiz['id'] ?? 0) ?>">

                <!-- MOMENTO 1 -->
                <div class="visita-card" id="card-m1">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">Momento 1</span>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <!-- Checkbox para marcar completado -->
                            <input type="checkbox" name="completado_momento1" id="chk-m1" 
                                   class="rounded border-gray-300">
                            <span class="badge badge-warning">Pendiente</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="form-label">Fecha</label>
                            <!-- 3. Agregamos name, id y value -->
                            <input type="date" name="fecha_momento1" id="fecha-m1" 
                                   value="<?= e($aprendiz['proxima_visita'] ?? '') ?>" 
                                   class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Modalidad</label>
                            <select name="modalidad_momento1" class="form-input">
                                <option value="Presencial">Presencial</option>
                                <option value="Virtual">Virtual</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- MOMENTO 2 (Deshabilitado hasta que M1 se marque como completado) -->
                <div class="visita-card disabled" id="card-m2">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">Momento 2</span>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" name="completado_momento2" id="chk-m2" 
                                   class="rounded border-gray-300" disabled>
                            <span class="badge badge-warning">Bloqueado</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_momento2" id="fecha-m2" 
                                   class="form-input" disabled>
                        </div>
                        <div>
                            <label class="form-label">Modalidad</label>
                            <select name="modalidad_momento2" class="form-input" disabled>
                                <option value="Presencial">Presencial</option>
                                <option value="Virtual">Virtual</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- MOMENTO 3 -->
                <div class="visita-card disabled" id="card-m3">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">Momento 3</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="date" name="fecha_momento3" id="fecha-m3" 
                                   class="form-input" disabled>
                        </div>
                        <div>
                            <label class="form-label">Modalidad</label>
                            <select name="modalidad_momento3" class="form-input" disabled>
                                <option value="Presencial">Presencial</option>
                                <option value="Virtual">Virtual</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form> <!-- Cierre del formulario -->
        </div>

        <!-- FOOTER -->
        <div class="flex justify-end gap-3 p-6 border-t">
            <button onclick="cerrarModalVisitas()" type="button" class="btn-cancelar">
                Cancelar
            </button>
            <!-- 4. Movemos el botón DENTRO del footer pero usando form="form-visitas" para que active el form -->
            <button type="submit" form="form-visitas" id="btn-guardar-visitas" class="btn-guardar">
                Guardar visitas
            </button>
        </div>
    </div>
</div>   

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chk1 = document.getElementById('chk-m1');
    const chk2 = document.getElementById('chk-m2');
    const card2 = document.getElementById('card-m2');
    const card3 = document.getElementById('card-m3');
    const inputsM2 = card2.querySelectorAll('input, select');
    const inputsM3 = card3.querySelectorAll('input, select');

    function toggleM2(enabled) {
        card2.classList.toggle('disabled', !enabled);
        inputsM2.forEach(el => el.disabled = !enabled);
        chk2.disabled = !enabled;
        if (!enabled) {
            chk2.checked = false;
            inputsM2.forEach(el => el.value = '');
            toggleM3(false);
        }
    }

    function toggleM3(enabled) {
        card3.classList.toggle('disabled', !enabled);
        inputsM3.forEach(el => el.disabled = !enabled);
    }

    // Activar M2 si M1 está checkeado
    if(chk1) {
        chk1.addEventListener('change', () => toggleM2(chk1.checked));
        // Verificar estado inicial
        toggleM2(chk1.checked);
    }
    
    if(chk2) {
        chk2.addEventListener('change', () => toggleM3(chk2.checked));
    }

    // AJAX Submit
    const form = document.getElementById('form-visitas');
    if(form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-guardar-visitas');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Guardando...';

            const formData = new FormData(form);
            
            try {
                const res = await fetch(form.action, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.ok) {
                    alert('✅ Visitas actualizadas correctamente');
                    cerrarModalVisitas();
                    location.reload();
                } else {
                    alert('❌ Error: ' + (data.error || 'No se pudo guardar'));
                }
            } catch (err) {
                console.error(err);
                alert('️ Error de conexión o del servidor');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    }
});
</script>