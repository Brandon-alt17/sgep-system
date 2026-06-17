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
<?php
$visitaProgramadaM1 = trim((string) ($aprendiz['visita_programada_m1'] ?? ''));
if ($visitaProgramadaM1 === '') {
    $visitaProgramadaM1 = trim((string) ($aprendiz['proxima_visita'] ?? ''));
}
$visitaProgramadaM2 = trim((string) ($aprendiz['visita_programada_m2'] ?? ''));
$visitaProgramadaM3 = trim((string) ($aprendiz['visita_programada_m3'] ?? ''));
$modalidadVisitaM1 = trim((string) ($aprendiz['modalidad_visita_m1'] ?? 'Presencial'));
$modalidadVisitaM2 = trim((string) ($aprendiz['modalidad_visita_m2'] ?? 'Presencial'));
$modalidadVisitaM3 = trim((string) ($aprendiz['modalidad_visita_m3'] ?? 'Presencial'));
$visitaM1Completada = !empty($aprendiz['visita_m1_completada']);
$visitaM2Completada = !empty($aprendiz['visita_m2_completada']);
$modalidadVisitaSelected = static function (string $current, string $option): string {
    return strcasecmp($current, $option) === 0 ? ' selected' : '';
};
?>

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
                        Puede programar las tres visitas a la vez. La próxima visita mostrada será la del primer momento pendiente.
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
                            <input type="checkbox" name="completado_momento1" id="chk-m1"
                                   class="rounded border-gray-300"<?= $visitaM1Completada ? ' checked' : '' ?>>
                            <span class="badge <?= $visitaM1Completada ? 'badge-success' : 'badge-warning' ?>"><?= $visitaM1Completada ? 'Completado' : 'Pendiente' ?></span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="text" name="fecha_momento1" id="fecha-m1"
                                   value="<?= e(date_iso_to_dmY($visitaProgramadaM1)) ?>"
                                   class="form-input" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" data-date-input="dmy">
                        </div>
                        <div>
                            <label class="form-label">Modalidad</label>
                            <select name="modalidad_momento1" class="form-input">
                                <option value="Presencial"<?= $modalidadVisitaSelected($modalidadVisitaM1, 'Presencial') ?>>Presencial</option>
                                <option value="Virtual"<?= $modalidadVisitaSelected($modalidadVisitaM1, 'Virtual') ?>>Virtual</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- MOMENTO 2 -->
                <div class="visita-card" id="card-m2">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">Momento 2</span>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" name="completado_momento2" id="chk-m2"
                                   class="rounded border-gray-300"<?= $visitaM2Completada ? ' checked' : '' ?>>
                            <span class="badge <?= $visitaM2Completada ? 'badge-success' : 'badge-warning' ?>"><?= $visitaM2Completada ? 'Completado' : 'Pendiente' ?></span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="text" name="fecha_momento2" id="fecha-m2"
                                   value="<?= e(date_iso_to_dmY($visitaProgramadaM2)) ?>"
                                   class="form-input" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" data-date-input="dmy">
                        </div>
                        <div>
                            <label class="form-label">Modalidad</label>
                            <select name="modalidad_momento2" class="form-input">
                                <option value="Presencial"<?= $modalidadVisitaSelected($modalidadVisitaM2, 'Presencial') ?>>Presencial</option>
                                <option value="Virtual"<?= $modalidadVisitaSelected($modalidadVisitaM2, 'Virtual') ?>>Virtual</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- MOMENTO 3 -->
                <div class="visita-card" id="card-m3">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">Momento 3</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="text" name="fecha_momento3" id="fecha-m3"
                                   value="<?= e(date_iso_to_dmY($visitaProgramadaM3)) ?>"
                                   class="form-input" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" data-date-input="dmy">
                        </div>
                        <div>
                            <label class="form-label">Modalidad</label>
                            <select name="modalidad_momento3" class="form-input">
                                <option value="Presencial"<?= $modalidadVisitaSelected($modalidadVisitaM3, 'Presencial') ?>>Presencial</option>
                                <option value="Virtual"<?= $modalidadVisitaSelected($modalidadVisitaM3, 'Virtual') ?>>Virtual</option>
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
                    if (typeof window.sgToastNotify === "function") {
                        window.sgToastNotify("Visitas actualizadas correctamente.", "success", 2200);
                    }
                    cerrarModalVisitas();
                    window.setTimeout(function () {
                        location.reload();
                    }, 1800);
                } else {
                    if (typeof window.sgToastNotify === "function") {
                        window.sgToastNotify(data.error || "No se pudo guardar las visitas.", "error");
                    }
                }
            } catch (err) {
                console.error(err);
                if (typeof window.sgToastNotify === "function") {
                    window.sgToastNotify("Error de conexión o del servidor.", "error");
                }
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    }
});
</script>