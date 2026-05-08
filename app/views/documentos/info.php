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

$faltantesKeys = [
    'programa_formacion',
    'nivel_formativo',
    'numero_grupo',
    'modalidad_formacion',
    'nombre_completo',
    'tipo_documento',
    'numero_documento',
    'telefono',
    'correo_personal',
    'correo_institucional',
    'nombre_instructor_seguimiento',
    'empresa_nombre',
    'empresa_nit',
    'jefe_nombre',
];
$faltantes = 0;
foreach ($faltantesKeys as $k) {
    if ($valueFrom($info, $k) === '') {
        $faltantes++;
    }
}

$fieldLabel = static function (
    string $name,
    string $label,
    bool $editable = false,
    string $type = 'text'
) use ($valueFrom, $info): void {
    $value = $valueFrom($info, $name);
    $missing = $value === '';
    $baseInputClass = e(ui_input_classes());
    $missingBorderClass = $missing ? ' border-amber-300 focus:border-amber-400 focus:ring-amber-100' : '';
    $disabledClass = $editable ? '' : ' bg-app-panelSubtle text-gray-400';
    ?>
    <label class="<?= e(ui_label_classes()) ?>">
        <span class="flex items-center gap-2">
            <?= e($label) ?>
            <?php if ($missing): ?>
                <span class="inline-flex h-4 w-4 text-amber-600 [&_svg]:h-4 [&_svg]:w-4" aria-label="Falta completar"><?= ui_icon('alert-circle') ?></span>
            <?php endif; ?>
        </span>
        <?php if ($editable): ?>
            <input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" class="<?= $baseInputClass . $missingBorderClass ?>">
        <?php else: ?>
            <input type="<?= e($type) ?>" value="<?= e($value) ?>" disabled class="<?= $baseInputClass . $disabledClass . $missingBorderClass ?>">
        <?php endif; ?>
    </label>
    <?php
};
?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e($volverUrl) ?>" aria-label="Volver al perfil del aprendiz">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Información general F-023</h2>
        <p class="m-0 text-sm text-app-muted">Complete y actualice los datos generales del aprendiz. Los faltantes se señalan como aviso, sin bloquear el guardado.</p>
    </div>
</section>

<section class="mb-4 <?= e(ui_card_classes()) ?>">
    <p class="m-0 text-sm"><?= e((string) ($aprendiz['nombre_completo'] ?? '')) ?> - <?= e((string) ($aprendiz['numero_documento'] ?? '')) ?></p>
</section>

<?php if ($saved): ?>
    <section class="mb-4 <?= e(ui_success_card_classes()) ?>">
        Información general guardada correctamente.
    </section>
<?php endif; ?>

<?php if ($faltantes > 0): ?>
    <section class="mb-4 <?= e(ui_warning_card_classes()) ?>">
        Hay <?= (int) $faltantes ?> campos pendientes por completar. Puedes guardar de todos modos.
    </section>
<?php endif; ?>

<form id="info-general-form" method="post" action="<?= e(APP_BASE_PATH) ?>/documentos/info" class="space-y-4">
    <input type="hidden" name="aprendiz_id" value="<?= (int) ($aprendiz['id'] ?? 0) ?>">

    <section class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">A. Información de formación</h3>
        <div class="mt-3 grid gap-4 md:grid-cols-3">
            <?php $fieldLabel('regional', 'Regional', true); ?>
            <?php $fieldLabel('centro_formacion', 'Centro de formación', true); ?>
            <?php $fieldLabel('nivel_formativo', 'Nivel formativo'); ?>
            <?php $fieldLabel('programa_formacion', 'Programa de formación'); ?>
            <?php $fieldLabel('numero_grupo', 'No. grupo'); ?>
            <?php $fieldLabel('modalidad_formacion', 'Modalidad de formación'); ?>
            <?php $fieldLabel('estrategia_formativa', 'Estrategia formativa', true); ?>
            <?php $fieldLabel('fecha_fin_etapa_lectiva', 'Fecha fin etapa lectiva', true, 'date'); ?>
            <?php $fieldLabel('fecha_registro_sofiaplus', 'Fecha de registro en SofiaPlus', true, 'date'); ?>
        </div>
    </section>

    <section class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">B. Datos del aprendiz</h3>
        <div class="mt-3 grid gap-4 md:grid-cols-3">
            <?php $fieldLabel('nombre_completo', 'Nombre completo'); ?>
            <?php $fieldLabel('tipo_documento', 'Tipo de documento'); ?>
            <?php $fieldLabel('numero_documento', 'No. de identificación'); ?>
            <?php $fieldLabel('telefono', 'Contacto telefónico'); ?>
            <?php $fieldLabel('direccion_domicilio', 'Dirección'); ?>
            <?php $fieldLabel('correo_personal', 'Correo personal'); ?>
            <?php $fieldLabel('correo_institucional', 'Correo institucional'); ?>
            <?php $fieldLabel('alternativa_ep', 'Alternativa etapa productiva'); ?>
        </div>
    </section>

    <section class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">C. Datos del instructor de seguimiento</h3>
        <div class="mt-3 grid gap-4 md:grid-cols-3">
            <?php $fieldLabel('nombre_instructor_seguimiento', 'Nombre'); ?>
            <?php $fieldLabel('telefono_instructor_seguimiento', 'Contacto telefónico'); ?>
            <?php $fieldLabel('correo_instructor_seguimiento', 'Correo institucional'); ?>
        </div>
    </section>

    <section class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">D. Datos del ente co-formador / jefe</h3>
        <div class="mt-3 grid gap-4 md:grid-cols-3">
            <?php $fieldLabel('empresa_nombre', 'Nombre empresa o entidad co-formadora'); ?>
            <?php $fieldLabel('empresa_direccion', 'Dirección'); ?>
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
                <?= $presentaDiscapacidad ? 'checked' : '' ?>
                class="h-4 w-4"
            >
            Presenta discapacidad
        </label>
    </div>

    <div
        id="discapacidad-card-wrap"
        class="overflow-hidden transition-all duration-300 ease-out <?= $presentaDiscapacidad ? 'max-h-[500px] translate-y-0 opacity-100' : 'max-h-0 -translate-y-1 opacity-0' ?>"
    >
        <section id="discapacidad-card" class="<?= e(ui_card_classes()) ?>">
            <h3 class="<?= e(ui_heading_sm_classes()) ?>">E. Persona en situación de discapacidad (si aplica)</h3>
            <div class="mt-3 grid gap-4 md:grid-cols-3">
                <?php $fieldLabel('asistencia_nombre', 'Nombre de quien asiste al aprendiz', true); ?>
                <?php $fieldLabel('asistencia_tipo', 'Tipo de asistencia', true); ?>
                <?php $fieldLabel('asistencia_contacto', 'Contacto telefónico', true); ?>
            </div>
        </section>
    </div>
</form>

<div class="pb-20" aria-hidden="true"></div>
<div class="fixed inset-x-0 bottom-0 z-[60] border-t border-app-border bg-app-panel shadow-xsSoft">
    <div class="mx-auto flex min-h-[70px] w-full items-center justify-end gap-3 px-4 py-3">
        <button form="info-general-form" type="submit" class="<?= e(ui_button_primary_classes()) ?> self-center justify-center text-white">
            Guardar información
        </button>
    </div>
</div>

<script>
(() => {
    const toggle = document.getElementById('toggle-discapacidad');
    const wrap = document.getElementById('discapacidad-card-wrap');
    const card = document.getElementById('discapacidad-card');
    if (!toggle || !wrap || !card) return;

    const updatePanel = () => {
        const show = toggle.checked;
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
</script>
