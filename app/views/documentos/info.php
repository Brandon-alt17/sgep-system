<?php
declare(strict_types=1);

$aprendiz = (array) ($aprendiz ?? []);
$info = (array) ($info ?? []);
$saved = (bool) ($saved ?? false);

$volverUrl = APP_BASE_PATH . '/aprendices/show?id=' . (int) ($aprendiz['id'] ?? 0);

$valueFrom = static function (array $source, string $key): string {
    return trim((string) ($source[$key] ?? ''));
};

$presentaDiscapacidad = $valueFrom($info, 'asistencia_nombre') !== ''
    || $valueFrom($info, 'asistencia_tipo') !== ''
    || $valueFrom($info, 'asistencia_contacto') !== '';

$faltantesKeyLabels = [
    'programa_formacion' => 'Programa de formación',
    'nivel_formativo' => 'Nivel formativo',
    'numero_grupo' => 'No. grupo',
    'modalidad_formacion' => 'Modalidad de formación',
    'nombre_completo' => 'Nombre completo',
    'tipo_documento' => 'Tipo de documento',
    'numero_documento' => 'No. de identificación',
    'telefono' => 'Contacto telefónico',
    'correo_personal' => 'Correo personal',
    'correo_institucional' => 'Correo institucional',
    'nombre_instructor_seguimiento' => 'Nombre instructor de seguimiento',
    'empresa_nombre' => 'Nombre empresa o entidad co-formadora',
    'empresa_nit' => 'NIT',
    'jefe_nombre' => 'Nombre del jefe inmediato / tutor',
];
$faltantesKeys = array_keys($faltantesKeyLabels);
$faltantesKeysLookup = array_fill_keys($faltantesKeys, true);
$faltantesDetalle = [];
foreach ($faltantesKeys as $k) {
    if ($valueFrom($info, $k) === '') {
        $faltantesDetalle[] = $faltantesKeyLabels[$k];
    }
}
$faltantes = count($faltantesDetalle);

$fieldLabel = static function (
    string $name,
    string $label,
    bool $editable = false,
    string $type = 'text'
) use ($valueFrom, $info, $faltantesKeysLookup): void {
    $value = $valueFrom($info, $name);
    if ($type === 'textarea') {
        $value = normalize_multiline_text($value);
    }
    $missing = $value === '';
    $baseInputClass = e(ui_input_classes());
    $missingBorderClass = $missing ? ' border-amber-300 focus:border-amber-400 focus:ring-amber-100' : '';
    $pendienteAttr = ($missing && isset($faltantesKeysLookup[$name])) ? ' data-f023-pendiente="1"' : '';
    ?>
    <div class="min-w-0 self-start" id="f023-campo-<?= e($name) ?>"<?= $pendienteAttr ?>>
    <label class="<?= e(ui_label_classes()) ?>">
        <span class="flex flex-wrap items-center gap-2">
            <?= e($label) ?>
            <?php if (!$editable): ?>
                <span class="inline-flex h-3.5 w-3.5 shrink-0 text-gray-400 [&_svg]:h-3.5 [&_svg]:w-3.5" title="Solo lectura" aria-hidden="true"><?= ui_icon('lock') ?></span>
            <?php endif; ?>
            <?php if ($missing): ?>
                <span class="inline-flex h-4 w-4 text-amber-600 [&_svg]:h-4 [&_svg]:w-4" aria-label="Falta completar"><?= ui_icon('circle-alert') ?></span>
            <?php endif; ?>
        </span>
        <?php
        $textareaClass = $baseInputClass . $missingBorderClass . ' min-h-10 min-w-0 max-w-full resize-none overflow-hidden py-2 leading-snug';
        $readonlyTextareaFilled = 'mt-1.5 min-w-0 max-w-full rounded-lg border border-app-borderControlStrong bg-gray-50 px-3 py-2 text-sm leading-snug text-gray-500 whitespace-pre-wrap break-words';
        $readonlyTextareaEmpty = $readonlyTextareaFilled . ' flex h-10 items-center';
        ?>
        <?php if ($editable): ?>
            <?php if ($type === 'date'): ?>
                <input type="text" name="<?= e($name) ?>" value="<?= e(date_iso_to_dmY($value)) ?>" data-date-input="dmy" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off" aria-describedby="f023-date-format-hint" class="<?= $baseInputClass . $missingBorderClass ?>">
            <?php elseif ($type === 'textarea'): ?>
                <textarea name="<?= e($name) ?>" rows="1" spellcheck="true" data-auto-resize-textarea class="<?= $textareaClass ?>"><?= e($value) ?></textarea>
            <?php else: ?>
                <input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" class="<?= $baseInputClass . $missingBorderClass ?>">
            <?php endif; ?>
        <?php else: ?>
            <?php if ($type === 'date'): ?>
                <div class="mt-1.5 flex h-10 w-full items-center rounded-lg border border-app-borderControlStrong bg-gray-50 px-3 text-sm text-gray-500">
                    <?php if ($value !== ''): ?>
                        <?= e(date_iso_to_dmY($value)) ?>
                    <?php else: ?>
                        <span class="text-gray-400">Sin dato</span>
                    <?php endif; ?>
                </div>
            <?php elseif ($type === 'textarea'): ?>
                <?php if ($value !== ''): ?>
                    <div class="<?= $readonlyTextareaFilled ?>"><?= e($value) ?></div>
                <?php else: ?>
                    <div class="<?= $readonlyTextareaEmpty ?>"><span class="text-gray-400">Sin dato</span></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="mt-1.5 flex h-10 w-full items-center rounded-lg border border-app-borderControlStrong bg-gray-50 px-3 text-sm text-gray-500">
                    <?php if ($value !== ''): ?>
                        <?= e($value) ?>
                    <?php else: ?>
                        <span class="text-gray-400">Sin dato</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </label>
    </div>
    <?php
};

$f023SectionHeading = static function (string $icon, string $title): void {
    ?>
    <h3 class="font-app mb-4 mt-0 flex items-center gap-2.5 text-base font-semibold leading-snug text-app-text">
        <span class="inline-flex h-5 w-5 shrink-0 text-app-accent [&_svg]:h-5 [&_svg]:w-5" aria-hidden="true"><?= ui_icon($icon) ?></span>
        <?= e($title) ?>
    </h3>
    <?php
};
?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e($volverUrl) ?>" aria-label="Volver al perfil del aprendiz">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Información general F-023</h2>
        <p class="m-0 text-sm text-app-muted">Datos generales del aprendiz. Algunos campos provienen de otros módulos y no son editables aquí.</p>
    </div>
</section>

<?php if ($saved): ?>
    <section class="mb-4 <?= e(ui_success_card_classes()) ?>">
        Información general guardada correctamente.
    </section>
<?php endif; ?>

<?php if ($faltantes > 0): ?>
    <section class="mb-4 <?= e(ui_warning_card_classes()) ?>">
        <p class="m-0 text-sm font-medium text-app-text">Hay <?= (int) $faltantes ?> campos pendientes por completar (se listan abajo). Puede guardar de todos modos.</p>
        <ul class="mb-0 mt-2 list-disc pl-5 text-sm text-app-muted">
            <?php foreach ($faltantesDetalle as $detLabel): ?>
                <li><?= e($detLabel) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" id="f023-ir-primer-pendiente" class="mt-3 text-sm font-medium text-amber-900 underline decoration-amber-700/60 underline-offset-2 hover:decoration-amber-900">
            Ir al primero pendiente
        </button>
    </section>
<?php endif; ?>

<form id="info-general-form" method="post" action="<?= e(APP_BASE_PATH) ?>/documentos/info" class="space-y-6 md:space-y-7">
    <input type="hidden" name="aprendiz_id" value="<?= (int) ($aprendiz['id'] ?? 0) ?>">
    <p id="f023-date-format-hint" class="sr-only">Las fechas editables usan formato día, mes y año con barras, por ejemplo 01/12/2025.</p>

    <section class="<?= e(ui_card_classes()) ?> !p-6">
        <?php $f023SectionHeading('user-round', 'Datos del aprendiz'); ?>
        <div class="grid gap-5 md:grid-cols-3 md:gap-x-6 md:gap-y-6">
            <?php $fieldLabel('nombre_completo', 'Nombre completo'); ?>
            <?php $fieldLabel('tipo_documento', 'Tipo de documento'); ?>
            <?php $fieldLabel('numero_documento', 'No. de identificación'); ?>
            <?php $fieldLabel('telefono', 'Contacto telefónico'); ?>
            <?php $fieldLabel('direccion_domicilio', 'Dirección', false, 'textarea'); ?>
            <?php $fieldLabel('correo_personal', 'Correo personal'); ?>
            <?php $fieldLabel('correo_institucional', 'Correo institucional'); ?>
            <?php $fieldLabel('alternativa_ep', 'Alternativa etapa productiva'); ?>
        </div>
    </section>

    <section class="<?= e(ui_card_classes()) ?> !p-6">
        <?php $f023SectionHeading('graduation-cap', 'Formación'); ?>
        <div class="grid gap-5 md:grid-cols-3 md:gap-x-6 md:gap-y-6">
            <?php $fieldLabel('regional', 'Regional', true); ?>
            <?php $fieldLabel('centro_formacion', 'Centro de formación', true); ?>
            <?php $fieldLabel('nivel_formativo', 'Nivel formativo'); ?>
            <?php $fieldLabel('programa_formacion', 'Programa de formación'); ?>
            <?php $fieldLabel('numero_grupo', 'No. grupo'); ?>
            <?php $fieldLabel('modalidad_formacion', 'Modalidad de formación'); ?>
            <?php $fieldLabel('estrategia_formativa', 'Estrategia formativa', true, 'textarea'); ?>
            <?php $fieldLabel('fecha_fin_etapa_lectiva', 'Fecha fin etapa lectiva', true, 'date'); ?>
            <?php $fieldLabel('fecha_registro_sofiaplus', 'Fecha de registro en SofiaPlus', true, 'date'); ?>
        </div>
    </section>

    <section class="<?= e(ui_card_classes()) ?> !p-6">
        <?php $f023SectionHeading('user-check', 'Instructor de seguimiento'); ?>
        <div class="grid gap-5 md:grid-cols-3 md:gap-x-6 md:gap-y-6">
            <?php $fieldLabel('nombre_instructor_seguimiento', 'Nombre'); ?>
            <?php $fieldLabel('telefono_instructor_seguimiento', 'Contacto telefónico'); ?>
            <?php $fieldLabel('correo_instructor_seguimiento', 'Correo institucional'); ?>
        </div>
    </section>

    <section class="<?= e(ui_card_classes()) ?> !p-6">
        <?php $f023SectionHeading('building-2', 'Ente co-formador y jefe'); ?>
        <div class="grid gap-5 md:grid-cols-3 md:gap-x-6 md:gap-y-6">
            <?php $fieldLabel('empresa_nombre', 'Nombre empresa o entidad co-formadora'); ?>
            <?php $fieldLabel('empresa_direccion', 'Dirección', false, 'textarea'); ?>
            <?php $fieldLabel('empresa_nit', 'NIT'); ?>
            <?php $fieldLabel('empresa_correo', 'Correo electrónico'); ?>
            <?php $fieldLabel('jefe_nombre', 'Nombre del jefe inmediato / tutor'); ?>
            <?php $fieldLabel('jefe_cargo', 'Cargo'); ?>
            <?php $fieldLabel('jefe_telefono', 'Contacto telefónico'); ?>
            <?php $fieldLabel('jefe_correo', 'Correo electrónico jefe'); ?>
            <?php $fieldLabel('contacto2_nombre', 'Nombre otro contacto'); ?>
            <?php $fieldLabel('contacto2_correo', 'Correo otro contacto'); ?>
        </div>
    </section>

    <div class="px-1 py-1">
        <label class="inline-flex items-center gap-2 text-sm text-app-text">
            <input
                id="toggle-discapacidad"
                type="checkbox"
                name="presenta_discapacidad"
                value="1"
                aria-controls="discapacidad-card-wrap"
                <?= $presentaDiscapacidad ? 'aria-expanded="true"' : 'aria-expanded="false"' ?>
                <?= $presentaDiscapacidad ? 'checked' : '' ?>
                class="h-4 w-4"
            >
            Presenta discapacidad
        </label>
    </div>

    <div
        id="discapacidad-card-wrap"
        role="region"
        <?= $presentaDiscapacidad ? '' : 'aria-hidden="true"' ?>
        aria-labelledby="f023-discapacidad-titulo"
        class="overflow-hidden transition-all duration-300 ease-out <?= $presentaDiscapacidad ? 'max-h-[500px] translate-y-0 opacity-100' : 'max-h-0 -translate-y-1 opacity-0' ?>"
    >
        <section id="f023-discapacidad-panel" class="<?= e(ui_card_classes()) ?> !p-6">
            <h3 id="f023-discapacidad-titulo" class="font-app mb-4 mt-0 flex items-center gap-2.5 text-base font-semibold leading-snug text-app-text">
                <span class="inline-flex h-5 w-5 shrink-0 text-app-accent [&_svg]:h-5 [&_svg]:w-5" aria-hidden="true"><?= ui_icon('users') ?></span>
                Persona en situación de discapacidad (si aplica)
            </h3>
            <div class="grid gap-5 md:grid-cols-3 md:gap-x-6 md:gap-y-6">
                <?php $fieldLabel('asistencia_nombre', 'Nombre de quien asiste al aprendiz', true); ?>
                <?php $fieldLabel('asistencia_tipo', 'Tipo de asistencia', true); ?>
                <?php $fieldLabel('asistencia_contacto', 'Contacto telefónico', true); ?>
            </div>
        </section>
    </div>
</form>

<div class="pb-24" aria-hidden="true"></div>
<div class="fixed inset-x-0 bottom-0 z-[60] border-t border-app-border bg-app-panel shadow-xsSoft" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
    <div class="mx-auto flex min-h-[52px] w-full flex-wrap items-center justify-end gap-2 px-4 py-2">
        <a id="f023-footer-leave" href="#" data-f023-leave-ok="1" class="hidden <?= e(ui_button_small_classes()) ?> no-underline decoration-transparent hover:no-underline border-rose-200 bg-rose-50 text-rose-900 hover:!border-rose-400 hover:!bg-rose-100 hover:!text-rose-950">
            Salir sin guardar
        </a>
        <button form="info-general-form" type="submit" class="<?= e(ui_button_primary_classes()) ?> justify-center text-white">
            Guardar información
        </button>
    </div>
</div>

<?php partial('components/toast', [
    'message' => '',
    'variant' => 'warning',
    'positionClass' => 'bottom-24 left-4 right-4 z-[70] max-w-none sm:left-auto sm:right-6 sm:max-w-sm',
    'toastRootId' => 'f023-flow-toast',
]); ?>

<script src="<?= e(APP_BASE_PATH) ?>/js/ui-toast.js"></script>
<script src="<?= e(APP_BASE_PATH) ?>/js/date-input-dmy.js"></script>
<script src="<?= e(APP_BASE_PATH) ?>/js/f023-info-form.js"></script>
<script>
(() => {
    const toggle = document.getElementById('toggle-discapacidad');
    const wrap = document.getElementById('discapacidad-card-wrap');
    const card = document.getElementById('f023-discapacidad-panel');
    if (!toggle || !wrap || !card) return;

    const updatePanel = () => {
        const show = toggle.checked;
        toggle.setAttribute("aria-expanded", show ? "true" : "false");
        wrap.setAttribute("aria-hidden", show ? "false" : "true");
        wrap.classList.toggle('max-h-[500px]', show);
        wrap.classList.toggle('translate-y-0', show);
        wrap.classList.toggle('opacity-100', show);
        wrap.classList.toggle('max-h-0', !show);
        wrap.classList.toggle('-translate-y-1', !show);
        wrap.classList.toggle('opacity-0', !show);

        if (show) {
            window.setTimeout(() => {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 140);
        }
    };

    toggle.addEventListener('change', updatePanel);
    updatePanel();
})();

(() => {
    const btn = document.getElementById('f023-ir-primer-pendiente');
    if (!btn) return;
    btn.addEventListener('click', () => {
        const el = document.querySelector('[data-f023-pendiente="1"]');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const input = el.querySelector('input:not([type="hidden"])');
            if (input && typeof input.focus === 'function') {
                window.setTimeout(() => input.focus(), 400);
            }
        }
    });
})();
</script>
