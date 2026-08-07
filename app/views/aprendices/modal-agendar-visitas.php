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
$horaMomento1 = trim((string) ($aprendiz['hora_momento1'] ?? ''));
$horaMomento2 = trim((string) ($aprendiz['hora_momento2'] ?? ''));
$horaMomento3 = trim((string) ($aprendiz['hora_momento3'] ?? ''));
$modalidadVisitaM1 = trim((string) ($aprendiz['modalidad_visita_m1'] ?? 'Presencial'));
$modalidadVisitaM2 = trim((string) ($aprendiz['modalidad_visita_m2'] ?? 'Presencial'));
$modalidadVisitaM3 = trim((string) ($aprendiz['modalidad_visita_m3'] ?? 'Presencial'));
$visitaM1Completada = !empty($aprendiz['visita_m1_completada']);
$visitaM2Completada = !empty($aprendiz['visita_m2_completada']);
$visitaM3Completada = !empty($aprendiz['visita_m3_completada']);
$visitasExtraordinarias = is_array($visitasExtraordinarias ?? null) ? $visitasExtraordinarias : [];
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
                        Puede programar M1, M2, M3 y visitas extraordinarias. La próxima visita mostrada será la del primer momento pendiente.
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
                            <label class="form-label">Hora</label>
                            <input type="time" name="hora_momento1" id="hora-m1" value="<?= e(substr($horaMomento1, 0, 5)) ?>" class="form-input">
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
                            <label class="form-label">Hora</label>
                            <input type="time" name="hora_momento2" id="hora-m2" value="<?= e(substr($horaMomento2, 0, 5)) ?>" class="form-input">
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
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" name="completado_momento3" id="chk-m3"
                                   class="rounded border-gray-300"<?= $visitaM3Completada ? ' checked' : '' ?>>
                            <span class="badge <?= $visitaM3Completada ? 'badge-success' : 'badge-warning' ?>"><?= $visitaM3Completada ? 'Completado' : 'Pendiente' ?></span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="text" name="fecha_momento3" id="fecha-m3"
                                   value="<?= e(date_iso_to_dmY($visitaProgramadaM3)) ?>"
                                   class="form-input" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" data-date-input="dmy">
                        </div>
                        <div>
                            <label class="form-label">Hora</label>
                            <input type="time" name="hora_momento3" id="hora-m3" value="<?= e(substr($horaMomento3, 0, 5)) ?>" class="form-input">
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

                <!-- VISITAS EXTRAORDINARIAS -->
                <div class="border-t border-gray-200 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Visitas extraordinarias</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Agregue una o más visitas adicionales fuera de M1, M2 y M3.</p>
                        </div>
                        <button type="button" id="btn-add-extraordinaria" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-1">
                            <span class="text-base leading-none">+</span> Agregar
                        </button>
                    </div>
                    <div id="extraordinarias-list" class="space-y-3">
                        <?php foreach ($visitasExtraordinarias as $index => $visitaEx): ?>
                            <?php
                            $exCompletada = !empty($visitaEx['completada']);
                            $exModalidad = trim((string) ($visitaEx['modalidad'] ?? 'Presencial'));
                            $exFecha = trim((string) ($visitaEx['fecha_programada'] ?? ''));
                            $exHora = trim((string) ($visitaEx['hora_programada'] ?? ''));
                            $exNumero = (int) ($visitaEx['numero_visita'] ?? ($index + 1));
                            ?>
                            <div class="visita-card extraordinaria-row" data-extra-row>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-gray-700">Visita extraordinaria <?= (int) $exNumero ?></span>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                                            <input type="checkbox" name="extraordinarias[<?= (int) $index ?>][completada]"
                                                   class="rounded border-gray-300"<?= $exCompletada ? ' checked' : '' ?>>
                                            <span class="badge <?= $exCompletada ? 'badge-success' : 'badge-warning' ?>"><?= $exCompletada ? 'Completado' : 'Pendiente' ?></span>
                                        </label>
                                        <button type="button" class="text-sm text-red-600 hover:text-red-800" data-remove-extra>&times; Quitar</button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                                    <div>
                                        <label class="form-label">Fecha</label>
                                        <input type="text" name="extraordinarias[<?= (int) $index ?>][fecha]"
                                               value="<?= e(date_iso_to_dmY($exFecha)) ?>"
                                               class="form-input" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" data-date-input="dmy">
                                    </div>
                                    <div>
                                        <label class="form-label">Hora</label>
                                        <input type="time" name="extraordinarias[<?= (int) $index ?>][hora]"
                                               value="<?= e(substr($exHora, 0, 5)) ?>" class="form-input">
                                    </div>
                                    <div>
                                        <label class="form-label">Modalidad</label>
                                        <select name="extraordinarias[<?= (int) $index ?>][modalidad]" class="form-input">
                                            <option value="Presencial"<?= $modalidadVisitaSelected($exModalidad, 'Presencial') ?>>Presencial</option>
                                            <option value="Virtual"<?= $modalidadVisitaSelected($exModalidad, 'Virtual') ?>>Virtual</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

    if (chk1) {
        chk1.addEventListener('change', () => toggleM2(chk1.checked));
        toggleM2(chk1.checked);
    }

    const chk3 = document.getElementById('chk-m3');

    if (chk2) {
        chk2.addEventListener('change', () => toggleM3(chk2.checked));
        toggleM3(chk2.checked);
    }

    function updateCompletionBadge(checkbox) {
        const badge = checkbox.closest('label')?.querySelector('.badge');
        if (!badge) return;
        if (checkbox.checked) {
            badge.textContent = 'Completado';
            badge.classList.remove('badge-warning');
            badge.classList.add('badge-success');
        } else {
            badge.textContent = 'Pendiente';
            badge.classList.remove('badge-success');
            badge.classList.add('badge-warning');
        }
    }

    [chk1, chk2, chk3].forEach((chk) => {
        if (!chk) return;
        chk.addEventListener('change', () => updateCompletionBadge(chk));
    });

    const extraordinariasList = document.getElementById('extraordinarias-list');
    const btnAddExtra = document.getElementById('btn-add-extraordinaria');

    function bindDateInputsIn(root) {
        if (typeof window.sgBindDateInputDmy === 'function') {
            root.querySelectorAll('input[type="text"][data-date-input="dmy"]').forEach(window.sgBindDateInputDmy);
        }
    }

    function reindexExtraordinarias() {
        if (!extraordinariasList) return;
        const rows = extraordinariasList.querySelectorAll('[data-extra-row]');
        rows.forEach((row, index) => {
            const title = row.querySelector('.text-sm.font-medium');
            if (title) title.textContent = 'Visita extraordinaria ' + (index + 1);
            const fecha = row.querySelector('input[name*="[fecha]"]');
            const hora = row.querySelector('input[name*="[hora]"]');
            const modalidad = row.querySelector('select[name*="[modalidad]"]');
            const completada = row.querySelector('input[type="checkbox"][name*="[completada]"]');
            if (fecha) fecha.name = 'extraordinarias[' + index + '][fecha]';
            if (hora) hora.name = 'extraordinarias[' + index + '][hora]';
            if (modalidad) modalidad.name = 'extraordinarias[' + index + '][modalidad]';
            if (completada) completada.name = 'extraordinarias[' + index + '][completada]';
        });
    }

    function createExtraordinariaRow(index) {
        const row = document.createElement('div');
        row.className = 'visita-card extraordinaria-row';
        row.setAttribute('data-extra-row', '');
        row.innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-700">Visita extraordinaria ${index + 1}</span>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="extraordinarias[${index}][completada]" class="rounded border-gray-300">
                        <span class="badge badge-warning">Pendiente</span>
                    </label>
                    <button type="button" class="text-sm text-red-600 hover:text-red-800" data-remove-extra>&times; Quitar</button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="form-label">Fecha</label>
                    <input type="text" name="extraordinarias[${index}][fecha]" value=""
                           class="form-input" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" data-date-input="dmy">
                </div>
                <div>
                    <label class="form-label">Hora</label>
                    <input type="time" name="extraordinarias[${index}][hora]" value="" class="form-input">
                </div>
                <div>
                    <label class="form-label">Modalidad</label>
                    <select name="extraordinarias[${index}][modalidad]" class="form-input">
                        <option value="Presencial" selected>Presencial</option>
                        <option value="Virtual">Virtual</option>
                    </select>
                </div>
            </div>`;
        return row;
    }

    if (btnAddExtra && extraordinariasList) {
        btnAddExtra.addEventListener('click', () => {
            const index = extraordinariasList.querySelectorAll('[data-extra-row]').length;
            const row = createExtraordinariaRow(index);
            extraordinariasList.appendChild(row);
            bindDateInputsIn(row);
        });

        extraordinariasList.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('[data-remove-extra]');
            if (!removeBtn) return;
            const row = removeBtn.closest('[data-extra-row]');
            if (row) {
                row.remove();
                reindexExtraordinarias();
            }
        });

        extraordinariasList.addEventListener('change', (e) => {
            const chk = e.target;
            if (!(chk instanceof HTMLInputElement) || chk.type !== 'checkbox' || !chk.name.includes('[completada]')) return;
            updateCompletionBadge(chk);
        });

        bindDateInputsIn(extraordinariasList);
    }

    function showVisitasToast(message, variant) {
        const text = (message || '').trim();
        if (text === '') return;
        if (typeof window.sgToastShow === 'function' && document.querySelector('#aprendiz-page-toast')) {
            window.sgToastShow('#aprendiz-page-toast', text, variant || 'success');
            return;
        }
        if (typeof window.sgToastNotify === 'function') {
            window.sgToastNotify(text, variant || 'success');
            return;
        }
        if (typeof window.showGlobalToast === 'function') {
            window.showGlobalToast(text, variant || 'success');
        }
    }

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
                    showVisitasToast('Visitas actualizadas correctamente.', 'success');
                    cerrarModalVisitas();
                    window.setTimeout(function () {
                        location.reload();
                    }, 1800);
                    return;
                }
                showVisitasToast(data.error || 'No se pudo guardar las visitas.', 'error');
            } catch (err) {
                console.error(err);
                showVisitasToast('Error de conexión o del servidor.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    }
});
</script>