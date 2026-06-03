<?php
declare(strict_types=1);

$errors = (array) ($errors ?? []);
$old = (array) ($old ?? []);
$programasOptions = (array) ($programasOptions ?? []);
$empresasOptions = (array) ($empresasOptions ?? []);

$val = static function (string $key, string $default = '') use ($old): string {
    return e(trim((string) ($old[$key] ?? $default)));
};

$empresaIdAp = (int) ($old['empresa_id'] ?? 0);
$currentProgramaId = (int) ($old['programa_id'] ?? 0);
$currentJefeId = (int) ($old['jefe_id'] ?? 0);
$estadoActual = trim((string) ($old['estado'] ?? 'Pendiente por iniciar'));
if ($estadoActual === '') {
    $estadoActual = 'Pendiente por iniciar';
}

$estadosAprendiz = [
    'Pendiente por iniciar',
    'En ejecución',
    'Certificado',
    'Aplazada',
    'Finalizada',
    'Por certificar',
    'Pendiente por comité',
];

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

$empresaReadonlyNombre = '';
$empresaReadonlyNit = '';
$empresaReadonlyDireccion = '';
if ($empresaIdAp > 0) {
    foreach ($empresasCatalogForJs as $empRow) {
        if ((int) ($empRow['id'] ?? 0) === $empresaIdAp) {
            $empresaReadonlyNombre = (string) ($empRow['nombre'] ?? '');
            $empresaReadonlyNit = (string) ($empRow['nit'] ?? '');
            $empresaReadonlyDireccion = (string) ($empRow['direccion'] ?? '');
            break;
        }
    }
}
?>

<?php partial('aprendices/_form_page_styles'); ?>

<section class="mb-4 flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-1 self-start" href="<?= e(APP_BASE_PATH) ?>/aprendices" aria-label="Volver al listado de aprendices">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="pl-2">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Nuevo aprendiz</h2>
        <p class="m-0 mt-1 max-w-[720px] text-sm text-app-muted">
            Registra los datos del aprendiz y, si aplica, vincúlalo con una empresa co-formadora y supervisor.
        </p>
    </div>
</section>

<?php if ($errors !== []): ?>
    <ul class="mb-4 list-disc space-y-1 rounded-[10px] border border-rose-200 bg-rose-50 p-4 pl-8 text-sm text-rose-800">
        <?php foreach ($errors as $err): ?>
            <li><?= e(is_string($err) ? $err : (string) $err) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form
    method="post"
    action="<?= e(APP_BASE_PATH) ?>/aprendices"
    id="form-nuevo-aprendiz"
    class="aprendiz-form-shell <?= e(ui_card_classes()) ?> mx-auto max-w-3xl space-y-0 overflow-hidden"
    data-jefes-url="<?= e(APP_BASE_PATH) ?>/aprendices/jefes-por-empresa"
    data-empresas-catalog="<?= e(json_encode($empresasCatalogForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
    data-initial-empresa-id="<?= $empresaIdAp ?>"
    data-initial-jefe-id="<?= $currentJefeId ?>"
>
    <div class="border-b border-app-border px-6 pt-6">
        <div class="flex gap-6">
            <button type="button" id="tab-aprendiz" class="tab-button active py-2 text-sm font-medium" onclick="cambiarTabAprendizForm('aprendiz')">
                Datos del aprendiz
            </button>
            <button type="button" id="tab-empresa" class="tab-button inactive py-2 text-sm font-medium" onclick="cambiarTabAprendizForm('empresa')">
                Empresa co-formadora
            </button>
        </div>
    </div>

    <div class="p-6">
        <div id="contenido-aprendiz">
            <div class="form-grid">
                <div class="form-field md:col-span-2">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" name="nombre_completo" required value="<?= $val('nombre_completo') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Tipo de documento *</label>
                    <select name="tipo_documento" required class="form-input">
                        <?php
                        $tiposDoc = ['C.C.', 'T.I.', 'C.E.', 'P.A.', 'PEP'];
                        $tipoActual = trim((string) ($old['tipo_documento'] ?? 'C.C.'));
                        foreach ($tiposDoc as $tipoOpt):
                        ?>
                            <option value="<?= e($tipoOpt) ?>" <?= $tipoActual === $tipoOpt ? 'selected' : '' ?>><?= e($tipoOpt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label class="form-label">N° documento *</label>
                    <input type="text" name="numero_documento" required value="<?= $val('numero_documento') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" value="<?= $val('telefono') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Email personal</label>
                    <input type="email" name="correo_personal" value="<?= $val('correo_personal') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Email institucional</label>
                    <input type="email" name="correo_institucional" value="<?= $val('correo_institucional') ?>" class="form-input">
                </div>
                <div class="form-field md:col-span-2">
                    <label class="form-label">Dirección de domicilio</label>
                    <textarea name="direccion_domicilio" rows="1" data-auto-resize-textarea class="form-input min-h-10 resize-none overflow-hidden"><?= $val('direccion_domicilio') ?></textarea>
                </div>
                <div class="form-field">
                    <label class="form-label">Alternativa etapa productiva</label>
                    <input type="text" name="alternativa_ep" value="<?= $val('alternativa_ep') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Grupo</label>
                    <input type="text" name="ficha" value="<?= $val('ficha') ?>" class="form-input">
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
                    <input type="text" name="nombre_instructor_seguimiento" value="<?= $val('nombre_instructor_seguimiento') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Teléfono instructor seguimiento</label>
                    <input type="text" name="telefono_instructor_seguimiento" value="<?= $val('telefono_instructor_seguimiento') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Jefe de grupo</label>
                    <input type="text" name="jefe_grupo" value="<?= $val('jefe_grupo') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Área de coordinación</label>
                    <input type="text" name="coordinacion" value="<?= $val('coordinacion') ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-input">
                        <?php foreach ($estadosAprendiz as $estadoOpt): ?>
                            <option value="<?= e($estadoOpt) ?>" <?= $estadoActual === $estadoOpt ? 'selected' : '' ?>><?= e($estadoOpt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

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
                    'valueInputAttrs' => ['id' => 'nuevo-aprendiz-empresa-id'],
                ]); ?>
                <p class="mt-2 text-xs text-gray-500 m-0">Seleccione la empresa a la que pertenece el aprendiz. Los datos de la empresa se editan en el catálogo.</p>
            </div>

            <div id="edit-empresa-readonly-panel" class="rounded-lg border border-gray-100 bg-gray-50/80 p-4 <?= $empresaIdAp <= 0 ? 'hidden' : '' ?>">
                <p class="m-0 mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Datos de la empresa (solo lectura)</p>
                <div class="form-grid">
                    <div class="form-field">
                        <span class="form-label">Razón social</span>
                        <div id="edit-empresa-readonly-nombre" class="mt-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><?= $empresaReadonlyNombre !== '' ? e($empresaReadonlyNombre) : '<span class="italic text-gray-400">Dato no registrado</span>' ?></div>
                    </div>
                    <div class="form-field">
                        <span class="form-label">NIT</span>
                        <div id="edit-empresa-readonly-nit" class="mt-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><?= $empresaReadonlyNit !== '' ? e($empresaReadonlyNit) : '<span class="italic text-gray-400">Dato no registrado</span>' ?></div>
                    </div>
                    <div class="form-field md:col-span-2">
                        <span class="form-label">Dirección</span>
                        <div id="edit-empresa-readonly-direccion" class="mt-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700"><?= $empresaReadonlyDireccion !== '' ? e($empresaReadonlyDireccion) : '<span class="italic text-gray-400">Dato no registrado</span>' ?></div>
                    </div>
                </div>
            </div>

            <div id="edit-jefe-vinculo-wrap" class="form-field <?= $empresaIdAp <= 0 ? 'hidden' : '' ?>">
                <label class="form-label" for="select-jefe-id">Vincular supervisor</label>
                <select name="jefe_id" id="select-jefe-id" class="form-input">
                    <option value="">Sin supervisor asignado</option>
                </select>
                <p class="mt-2 text-xs text-gray-500 m-0">Para crear o editar supervisores, use la ficha de la empresa en el catálogo.</p>
            </div>

            <p id="edit-sin-empresa-aviso" class="m-0 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-500 <?= $empresaIdAp > 0 ? 'hidden' : '' ?>">
                Sin empresa vinculada. Seleccione una empresa para asignar un supervisor.
            </p>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-app-border px-6 py-4">
        <a href="<?= e(APP_BASE_PATH) ?>/aprendices" class="text-sm text-app-muted no-underline hover:text-app-text">Cancelar</a>
        <button type="submit" class="<?= e(ui_button_primary_classes()) ?>">Crear aprendiz</button>
    </div>
</form>

<script src="<?= e(APP_BASE_PATH) ?>/js/create-aprendiz.js"></script>
