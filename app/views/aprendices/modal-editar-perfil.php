<!-- MODAL EDITAR -->
<style>
    /* Estilos específicos para el modal */
    #modal-editar-aprendiz {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    #modal-editar-aprendiz.hidden {
        display: none !important;
    }

    #modal-editar-aprendiz:not(.hidden) {
        display: flex !important;
    }

    /* Overlay que captura clicks fuera del modal */
    #modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99998;
        cursor: pointer;
    }

    /* Contenedor del modal - debe estar por encima del overlay */
    .modal-container {
        position: relative;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        z-index: 99999;
        transition: all 0.3s ease;
    }

    /* Asegurar que todo el contenido del modal sea clickeable */
    .modal-container * {
        pointer-events: auto;
    }

    /* Tamaño para pestaña Aprendiz (más ancho) */
    .modal-container.aprendiz-size {
        max-width: 768px;
        width: 80%;
        max-height: 85vh;  /* Reducido de 90vh a 85vh */
    }

    /* Tamaño para pestaña Empresa (30% más pequeño) */
    .modal-container.empresa-size {
        max-width: 768px;  /* 600px - 30% = 420px */
        width: 85%;
    }

    @media (max-width: 640px) {
        .modal-container.aprendiz-size,
        .modal-container.empresa-size {
            width: 95%;
            margin: 1rem;
        }
    }

    /* Estilos de tabs */
    .tab-button {
        padding-bottom: 0.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
        border-bottom: 2px solid transparent;
    }

    .tab-button.active {
        border-bottom-color: #cacaca;
        color: #1f2937;
    }

    .tab-button.inactive {
        color: #abb6ca;
    }

    .tab-button.inactive:hover {
        color: #374151;
    }

    /* Grid para formularios */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-field {
        margin-bottom: 0.5rem;
    }

    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 500;
        color: #abb6ca;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .form-input {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s;
        background: white;
        pointer-events: auto;
    }

    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }

    /* Dividers */
    .section-divider {
        border-top: 1px solid #f3f4f6;
        margin: 1rem 0;
    }

    /* Asegurar que los botones sean clickeables */
    button, 
    .modal-container button,
    .modal-container input,
    .modal-container select,
    .modal-container a {
        pointer-events: auto !important;
        cursor: pointer;
    }

    .modal-container {
        overflow-y: auto;
        max-height: calc(85vh - 130px);
        padding-right: 6px;
    }   
    
    /* Botones personalizados */
    .btn-cancelar {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .btn-cancelar:hover {
        background-color: #f9fafb;
        color: #1f2937;
        border-color: #d1d5db;
    }

    .btn-guardar {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: white;
        background-color: #00bca5;
        border: none;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        cursor: pointer;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .btn-guardar:hover {
        background-color: #009b88;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .btn-guardar:active {
        transform: translateY(0);
}
    
</style>

<div id="modal-editar-aprendiz" class="hidden">
    <!-- Overlay separado con su propio z-index -->
    <div id="modal-overlay" onclick="cerrarModal()"></div>
    
    <!-- Contenedor del modal con tamaño dinámico -->
    <div id="modal-container" class="modal-container aprendiz-size">
        <!-- Header -->
        <div class="p-6 pb-2">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Editar datos — <?= e($aprendiz['nombre_completo']) ?></h2>
                    <p class="text-sm text-gray-500 mt-1">Actualiza la información personal del aprendiz y la vinculación con la empresa co-formadora.</p>
                </div>
                <button type="button" onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 transition text-2xl leading-none">&times;</button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="px-6 border-b border-gray-100">
            <div class="flex gap-6">
                <button type="button" onclick="cambiarTab('aprendiz')" id="tab-aprendiz" class="tab-button active py-2 text-sm font-medium">
                    Datos del aprendiz
                </button>
                <button type="button" onclick="cambiarTab('empresa')" id="tab-empresa" class="tab-button inactive py-2 text-sm font-medium">
                    Empresa co-formadora
                </button>
            </div>
        </div>

        <!-- FORM (resto del contenido igual) -->
        <?php
        $jefes = (array) ($jefes ?? []);
        $empresasOptions = (array) ($empresasOptions ?? []);
        $programasOptions = (array) ($programasOptions ?? []);
        $currentJefeId = (int) ($aprendiz['jefe_id'] ?? 0);
        $currentProgramaId = (int) ($aprendiz['programa_id'] ?? 0);
        $empresaIdAp = (int) ($aprendiz['empresa_id'] ?? 0);
        $jefeIdsInList = array_map(static fn (array $j): int => (int) ($j['id'] ?? 0), $jefes);
        $empresaComboboxOptions = [];
        $empresasCatalogForJs = [];
        foreach ($empresasOptions as $empresaRow) {
            $eidOpt = (int) ($empresaRow['id'] ?? 0);
            if ($eidOpt <= 0) {
                continue;
            }
            $empresaNombre = trim((string) ($empresaRow['nombre'] ?? 'Empresa #' . $eidOpt));
            $empresaComboboxOptions[] = [
                'value' => (string) $eidOpt,
                'label' => $empresaNombre,
                'search' => $empresaNombre . ' ' . trim((string) ($empresaRow['nit'] ?? '')),
            ];
            $empresasCatalogForJs[] = [
                'id' => $eidOpt,
                'nombre' => $empresaNombre,
                'nit' => trim((string) ($empresaRow['nit'] ?? '')),
                'direccion' => trim((string) ($empresaRow['direccion'] ?? '')),
            ];
        }
        $showEmpresaReadonly = static function (string $value): string {
            $t = trim($value);

            return $t !== '' ? e($t) : '<span class="italic text-gray-400">Dato no registrado</span>';
        };
        ?>
        <form
            method="POST"
            action="<?= e(APP_BASE_PATH) ?>/aprendices/update"
            class="p-6 pt-4"
            id="form-editar-aprendiz"
            data-jefes-url="<?= e(APP_BASE_PATH) ?>/aprendices/jefes-por-empresa"
            data-empresas-catalog="<?= e(json_encode($empresasCatalogForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
            data-initial-empresa-id="<?= $empresaIdAp ?>"
            data-initial-jefe-id="<?= $currentJefeId ?>"
        >
            <input type="hidden" name="id" value="<?= (int) $aprendiz['id'] ?>">

            <!-- ===================== -->
            <!-- DATOS APRENDIZ (Más campos, más ancho) -->
            <!-- ===================== -->
            <div id="contenido-aprendiz">
                <div class="form-grid">
                    <div class="form-field">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="nombre_completo" value="<?= e($aprendiz['nombre_completo']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Tipo de documento</label>
                        <input type="text" name="tipo_documento" value="<?= e($aprendiz['tipo_documento']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">N° documento</label>
                        <input type="text" name="numero_documento" value="<?= e($aprendiz['numero_documento']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" value="<?= e($aprendiz['telefono']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Email personal</label>
                        <input type="email" name="correo_personal" value="<?= e($aprendiz['correo_personal'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Correo institucional</label>
                        <input type="text" name="correo_institucional" value="<?= e($aprendiz['correo_institucional'] ?? '') ?>" class="form-input" placeholder="Correo @soy.sena.edu.co u otro dato de contacto">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Dirección de domicilio</label>
                        <textarea name="direccion_domicilio" rows="1" data-auto-resize-textarea class="form-input min-h-10 resize-none overflow-hidden"><?= e($aprendiz['direccion_domicilio'] ?? '') ?></textarea>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Alternativa etapa productiva</label>
                        <input type="text" name="alternativa_ep" value="<?= e($aprendiz['alternativa_ep'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Grupo</label>
                        <input type="text" name="ficha" value="<?= e($aprendiz['ficha'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field md:col-span-2">
                        <label class="form-label">Programa de formación</label>
                        <select name="programa_id" class="form-input">
                            <option value="">Sin programa</option>
                            <?php foreach ($programasOptions as $programaRow): ?>
                                <?php $pid = (int) ($programaRow['id'] ?? 0); ?>
                                <?php if ($pid <= 0) continue; ?>
                                <option value="<?= $pid ?>" <?= $currentProgramaId === $pid ? 'selected' : '' ?>>
                                    <?= e(trim((string) ($programaRow['nombre'] ?? 'Programa #' . $pid))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Instructor seguimiento</label>
                        <input type="text" name="nombre_instructor_seguimiento" value="<?= e($aprendiz['nombre_instructor_seguimiento'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Teléfono instructor seguimiento</label>
                        <input type="text" name="telefono_instructor_seguimiento" value="<?= e($aprendiz['telefono_instructor_seguimiento'] ?? '') ?>" class="form-input">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-input">
                            <?php
                            $estadoActual = (string) ($aprendiz['estado'] ?? 'Pendiente por iniciar');
                            $estadosAprendiz = [
                                'Pendiente por iniciar',
                                'En ejecución',
                                'Certificado',
                                'Aplazada',
                                'Finalizada',
                                'Por certificar',
                                'Pendiente por comité',
                            ];
                            foreach ($estadosAprendiz as $estadoOpt):
                            ?>
                                <option value="<?= e($estadoOpt) ?>" <?= $estadoActual === $estadoOpt ? 'selected' : '' ?>><?= e($estadoOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label class="form-label">Jefe de grupo</label>
                        <input type="text" name="jefe_grupo" value="<?= e($aprendiz['jefe_grupo'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Área de coordinación</label>
                        <input type="text" name="coordinacion" maxlength="120" value="<?= e($aprendiz['coordinacion'] ?? '') ?>" class="form-input" autocomplete="off">
                    </div>
                </div>
            </div>

            <!-- ===================== -->
            <!-- DATOS EMPRESA (Menos campos, más compacto) -->
            <!-- ===================== -->
            <div id="contenido-empresa" class="hidden space-y-5">
                <div class="form-field">
                    <label class="form-label">Vincular a empresa co-formadora</label>
                    <?php partial('components/combobox', [
                        'name' => 'empresa_id',
                        'value' => $empresaIdAp > 0 ? (string) $empresaIdAp : '',
                        'placeholder' => 'Buscar empresa co-formadora...',
                        'options' => $empresaComboboxOptions,
                        'comboboxDropUp' => true,
                        'comboboxPreserveValueOnSearch' => true,
                        'valueInputAttrs' => ['id' => 'edit-aprendiz-empresa-id', 'data-edit-empresa-vinculo' => '1'],
                    ]); ?>
                    <p class="mt-2 text-xs text-gray-500 m-0">Seleccione la empresa a la que pertenece el aprendiz. Los datos de la empresa se editan en el catálogo.</p>
                </div>

                <div id="edit-empresa-readonly-panel" class="rounded-lg border border-gray-100 bg-gray-50/80 p-4 <?= $empresaIdAp <= 0 ? 'hidden' : '' ?>">
                    <p class="m-0 mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Datos de la empresa (solo lectura)</p>
                    <div class="form-grid">
                        <div class="form-field">
                            <span class="form-label">Razón social</span>
                            <div id="edit-empresa-readonly-nombre" class="mt-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><?= $showEmpresaReadonly((string) ($aprendiz['empresa_nombre'] ?? '')) ?></div>
                        </div>
                        <div class="form-field">
                            <span class="form-label">NIT</span>
                            <div id="edit-empresa-readonly-nit" class="mt-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><?= $showEmpresaReadonly((string) ($aprendiz['nit'] ?? '')) ?></div>
                        </div>
                        <div class="form-field md:col-span-2">
                            <span class="form-label">Dirección</span>
                            <div id="edit-empresa-readonly-direccion" class="mt-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><?= $showEmpresaReadonly((string) ($aprendiz['direccion'] ?? '')) ?></div>
                        </div>
                    </div>
                </div>

                <div id="edit-jefe-vinculo-wrap" class="form-field <?= $empresaIdAp <= 0 ? 'hidden' : '' ?>">
                    <label class="form-label" for="select-jefe-id">Vincular supervisor</label>
                    <select name="jefe_id" id="select-jefe-id" class="form-input">
                        <option value="">Sin supervisor asignado</option>
                        <?php foreach ($jefes as $jefeRow): ?>
                            <?php $jid = (int) ($jefeRow['id'] ?? 0); ?>
                            <option value="<?= $jid ?>" <?= $currentJefeId === $jid ? 'selected' : '' ?>>
                                <?= e(trim((string) ($jefeRow['nombre'] ?? ''))) ?><?php
                                $cj = trim((string) ($jefeRow['cargo'] ?? ''));
                                echo $cj !== '' ? ' — ' . e($cj) : '';
                                ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($currentJefeId > 0 && !in_array($currentJefeId, $jefeIdsInList, true)): ?>
                            <option value="<?= $currentJefeId ?>" selected>
                                <?= e(trim((string) ($aprendiz['nombre_jefe'] ?? 'Supervisor #' . $currentJefeId))) ?> (actual)
                            </option>
                        <?php endif; ?>
                    </select>
                    <p class="mt-2 text-xs text-gray-500 m-0">Para crear o editar supervisores, use la ficha de la empresa en el catálogo.</p>
                </div>

                <p id="edit-sin-empresa-aviso" class="m-0 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500 <?= $empresaIdAp > 0 ? 'hidden' : '' ?>">
                    Sin empresa vinculada. Seleccione una empresa para asignar un supervisor.
                </p>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="button" onclick="cerrarModal()" class="btn-cancelar">
                    Cancelar
                </button>
                <button type="submit" class="btn-guardar">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>