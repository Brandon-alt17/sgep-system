<?php
declare(strict_types=1);

$empresa = (array) ($empresa ?? []);
$errors = (array) ($errors ?? []);
$isDetail = isset($empresa['id']) && (int) $empresa['id'] > 0;
$eid = $isDetail ? (int) $empresa['id'] : 0;
$editing = $isDetail && (bool) ($editing ?? false);
$aprendicesCount = $isDetail ? (int) ($aprendicesCount ?? 0) : 0;
$jefes = (array) ($jefes ?? []);
$val = static function (string $key) use ($empresa): string {
    return e(trim((string) ($empresa[$key] ?? '')));
};

$showField = static function (string $key) use ($empresa): string {
    $t = trim((string) ($empresa[$key] ?? ''));
    return $t !== ''
        ? e($t)
        : '<span class="italic">Dato no registrado</span>';
};

$toastKey = trim((string) ($_GET['toast'] ?? ''));
$toastMessage = match ($toastKey) {
    'empresa_actualizada' => 'Empresa actualizada correctamente.',
    'empresa_sin_cambios' => 'No se detectaron cambios para guardar.',
    default => '',
};
$toastVariant = $toastKey === 'empresa_sin_cambios' ? 'warning' : 'success';

$verUrl = APP_BASE_PATH . '/catalogo/empresas/ver?id=' . $eid;
$verEditUrl = $verUrl . '&edit=1';
$listadoAprendicesUrl = APP_BASE_PATH . '/aprendices?' . http_build_query([
    'empresa_id' => (string) $eid,
    'from' => 'empresas',
]);

$formFieldCls = ui_input_classes();
$editInputCls = $formFieldCls . ' mt-2 mb-1';
$viewLabelCls = 'm-0 text-xs font-medium uppercase tracking-wide text-app-muted';
?>

<?php if (!$isDetail): ?>

<?php partial('components/page_header', [
    'title' => 'Nueva empresa',
    'subtitle' => 'Registra una empresa para asociarla a aprendices.',
]); ?>

<?php if ($errors !== []): ?>
    <ul class="mb-4 list-disc space-y-1 rounded-[10px] border border-rose-200 bg-rose-50 p-4 pl-8 text-sm text-rose-800">
        <?php foreach ($errors as $err): ?>
            <li><?= e(is_string($err) ? $err : (string) $err) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas"
      class="<?= e(ui_card_classes()) ?> mx-auto max-w-5xl space-y-6 p-6">
    <div class="grid gap-4 md:grid-cols-2">
        <label class="<?= e(ui_label_classes()) ?> md:col-span-2">
            Razón social *
            <input type="text" name="nombre" required value="<?= $val('nombre') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            NIT
            <input type="text" name="nit" value="<?= $val('nit') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            Ciudad
            <input type="text" name="ciudad" value="<?= $val('ciudad') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?> md:col-span-2">
            Dirección
            <input type="text" name="direccion" value="<?= $val('direccion') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?> md:col-span-2">
            Dirección de práctica
            <input type="text" name="direccion_practica" value="<?= $val('direccion_practica') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?> md:col-span-2">
            Correo institucional / organización
            <input type="email" name="correo_org" value="<?= $val('correo_org') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
    </div>

    <h3 class="text-sm font-semibold text-app-text">Contacto alternativo</h3>
    <div class="grid gap-4 md:grid-cols-2">
        <label class="<?= e(ui_label_classes()) ?>">
            Nombre
            <input type="text" name="nombre_contacto2" value="<?= $val('nombre_contacto2') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            Correo
            <input type="email" name="correo_contacto2" value="<?= $val('correo_contacto2') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-app-border pt-6">
        <a href="<?= e(APP_BASE_PATH) ?>/catalogo/empresas" class="text-sm text-app-muted hover:text-app-text">Cancelar</a>
        <button type="submit" class="<?= e(ui_button_primary_classes()) ?>">Crear empresa</button>
    </div>
</form>

<?php elseif ($editing): ?>
<?php partial('components/ui'); ?>
<?php partial('components/toast', ['message' => $toastMessage, 'variant' => $toastVariant, 'positionClass' => 'bottom-24 right-6']); ?>

<?php if ($errors !== []): ?>
    <section class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
        <h3 class="m-0 mb-2 text-sm font-semibold">No se pudieron guardar los cambios</h3>
        <ul class="m-0 list-disc space-y-1 pl-5">
            <?php foreach ($errors as $err): ?>
                <li><?= e(is_string($err) ? $err : (string) $err) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="bg-app-bg mb-4 flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 shrink-0 self-start" href="<?= e($verUrl) ?>" aria-label="Volver a la información de la empresa">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 min-w-0 flex-1 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Editar información</h2>
        <p class="m-0 mt-1 text-sm text-app-muted">Modifique los campos y guarde o cancele para volver sin cambios.</p>
    </div>
</section>

<form id="empresa-edit-form" method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/actualizar"
      class="mx-auto max-w-5xl space-y-8 pb-28">
    <input type="hidden" name="id" value="<?= $eid ?>">

    <section class="<?= e(ui_card_classes()) ?> p-6">
        <div class="border-b border-app-border pb-6">
            <p class="<?= e($viewLabelCls) ?>">Razón social *</p>
            <input type="text" name="nombre" required value="<?= $val('nombre') ?>" class="<?= e($editInputCls) ?>">
        </div>

        <div class="pt-6 pb-6">
            <h3 class="m-0 text-sm font-semibold text-app-text">Datos generales</h3>
            <div class="mt-4 grid gap-6 md:grid-cols-2">
                <div>
                    <p class="<?= e($viewLabelCls) ?>">NIT</p>
                    <input type="text" name="nit" value="<?= $val('nit') ?>" class="<?= e($editInputCls) ?>">
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Ciudad</p>
                    <input type="text" name="ciudad" value="<?= $val('ciudad') ?>" class="<?= e($editInputCls) ?>">
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Dirección</p>
                    <input type="text" name="direccion" value="<?= $val('direccion') ?>" class="<?= e($editInputCls) ?>">
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Dirección de práctica</p>
                    <input type="text" name="direccion_practica" value="<?= $val('direccion_practica') ?>" class="<?= e($editInputCls) ?>">
                </div>
            </div>
        </div>

        <div class="border-t border-app-border py-6">
            <h3 class="m-0 text-sm font-semibold text-app-text">Contactos</h3>
            <div class="mt-4 grid gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <p class="<?= e($viewLabelCls) ?>">Correo institucional / organización</p>
                    <input type="email" name="correo_org" value="<?= $val('correo_org') ?>" class="<?= e($editInputCls) ?>">
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (nombre)</p>
                    <input type="text" name="nombre_contacto2" value="<?= $val('nombre_contacto2') ?>" class="<?= e($editInputCls) ?>">
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (correo)</p>
                    <input type="email" name="correo_contacto2" value="<?= $val('correo_contacto2') ?>" class="<?= e($editInputCls) ?>">
                </div>
            </div>
        </div>

        <div class="border-t border-app-border py-6">
            <h3 class="m-0 text-sm font-semibold text-app-text">Jefes y contacto alternativo</h3>
            <p class="m-0 mt-1 text-xs text-app-muted">Puedes actualizar cada jefe desde aquí.</p>
            <?php if ($jefes === []): ?>
                <div class="mt-4 grid gap-6 md:grid-cols-2">
                    <div>
                        <p class="<?= e($viewLabelCls) ?>">Supervisor</p>
                        <p class="mt-1 text-base text-app-text"><span class="italic">Dato no registrado</span></p>
                    </div>
                    <div>
                        <p class="<?= e($viewLabelCls) ?>">Contacto alternativo</p>
                        <p class="mt-1 text-base text-app-text"><span class="italic">Dato no registrado</span></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-4 space-y-5">
                <?php foreach ($jefes as $idx => $jefe): ?>
                    <?php $jefeId = (int) ($jefe['id'] ?? 0); ?>
                    <article class="rounded-lg border border-app-border bg-app-panelSubtle p-4">
                        <input type="hidden" name="jefes[<?= $jefeId ?>][id]" value="<?= $jefeId ?>">
                        <h4 class="m-0 text-sm font-semibold text-app-text">Jefe #<?= (int) ($idx + 1) ?></h4>
                        <div class="mt-4 grid gap-6 md:grid-cols-2">
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Supervisor</p>
                                <input type="text" name="jefes[<?= $jefeId ?>][nombre]" value="<?= e((string) ($jefe['nombre'] ?? '')) ?>" class="<?= e($editInputCls) ?>">
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Cargo</p>
                                <input type="text" name="jefes[<?= $jefeId ?>][cargo]" value="<?= e((string) ($jefe['cargo'] ?? '')) ?>" class="<?= e($editInputCls) ?>">
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Correo supervisor</p>
                                <input type="email" name="jefes[<?= $jefeId ?>][correo]" value="<?= e((string) ($jefe['correo'] ?? '')) ?>" class="<?= e($editInputCls) ?>">
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Teléfono supervisor</p>
                                <input type="text" name="jefes[<?= $jefeId ?>][telefono]" value="<?= e((string) ($jefe['telefono'] ?? '')) ?>" class="<?= e($editInputCls) ?>">
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (nombre)</p>
                                <input type="text" name="jefes[<?= $jefeId ?>][nombre_contacto2]" value="<?= e((string) ($jefe['nombre_contacto2'] ?? '')) ?>" class="<?= e($editInputCls) ?>">
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (correo)</p>
                                <input type="email" name="jefes[<?= $jefeId ?>][correo_contacto2]" value="<?= e((string) ($jefe['correo_contacto2'] ?? '')) ?>" class="<?= e($editInputCls) ?>">
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</form>

<div class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] border-t border-app-border bg-app-panel/95 shadow-[0_-4px_24px_rgba(16,24,40,0.08)] backdrop-blur-sm">
    <div class="pointer-events-auto flex min-h-[56px] w-full flex-row items-center justify-end gap-3 px-4 py-3 md:px-6">
        <a href="<?= e($verUrl) ?>" class="<?= e(ui_button_small_classes()) ?> inline-flex h-11 min-w-[11rem] shrink-0 items-center justify-center px-5">Cancelar</a>
        <button type="submit" form="empresa-edit-form" class="<?= e(ui_button_primary_classes()) ?> inline-flex h-11 min-w-[12rem] shrink-0 items-center justify-center px-5 text-app-textOnBrand">Guardar cambios</button>
    </div>
</div>

<?php else: ?>
<?php partial('components/ui'); ?>

<?php partial('components/toast', ['message' => $toastMessage]); ?>

<section class="bg-app-bg mb-4 flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 shrink-0 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/empresas" aria-label="Volver al catálogo de empresas">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 min-w-0 flex-1 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Información de la empresa</h2>
        <p class="m-0 mt-1 text-sm text-app-muted">Consulta los datos de contacto y el listado filtrado de aprendices.</p>
    </div>
</section>

<section class="<?= e(ui_card_classes()) ?> relative mx-auto max-w-5xl space-y-8 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-app-border pb-6">
        <div class="min-w-0 flex flex-wrap items-center gap-3">
            <h3 class="m-0 text-sm font-semibold text-app-text">Aprendices asociados</h3>
            <p class="m-0 mt-0.5 text-xs text-app-muted"><?= (int) $aprendicesCount ?> <?= (int) $aprendicesCount === 1 ? 'aprendiz' : 'aprendices' ?></p>
            <a href="<?= e($listadoAprendicesUrl) ?>" class="<?= e(ui_button_small_classes()) ?> inline-flex shrink-0 items-center gap-2">
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4" aria-hidden="true"><?= ui_icon('users') ?></span>
                Ver aprendices (<?= (int) $aprendicesCount ?>)
            </a>
        </div>
        <a href="<?= e($verEditUrl) ?>"
           class="<?= e(ui_button_small_classes()) ?> inline-flex shrink-0 items-center gap-2 shadow-xsSoft bg-white hover:bg-app-panelSubtle"
           aria-label="Editar información">
            <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4" aria-hidden="true"><?= ui_icon('pencil') ?></span>
            Editar
        </a>
    </div>

    <div class="pb-4">
        <p class="<?= e($viewLabelCls) ?>">Razón social</p>
        <p class="mt-1 text-2xl font-semibold leading-tight text-app-text"><?= $showField('nombre') ?></p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <p class="<?= e($viewLabelCls) ?>">NIT</p>
            <p class="mt-1 text-base text-app-text"><?= $showField('nit') ?></p>
        </div>
        <div>
            <p class="<?= e($viewLabelCls) ?>">Ciudad</p>
            <p class="mt-1 text-base text-app-text"><?= $showField('ciudad') ?></p>
        </div>
        <div>
            <p class="<?= e($viewLabelCls) ?>">Dirección</p>
            <p class="mt-1 text-base text-app-text"><?= $showField('direccion') ?></p>
        </div>
        <div>
            <p class="<?= e($viewLabelCls) ?>">Dirección de práctica</p>
            <p class="mt-1 text-base text-app-text"><?= $showField('direccion_practica') ?></p>
        </div>
        <div>
            <p class="<?= e($viewLabelCls) ?>">Correo institucional / organización</p>
            <p class="mt-1 text-base text-app-text break-all"><?= $showField('correo_org') ?></p>
        </div>
    </div>

    <div class="border-t border-app-border pt-6">
        <h3 class="m-0 text-sm font-semibold text-app-text">Jefes y contacto alternativo</h3>
        <?php if ($jefes === []): ?>
            <div class="mt-4 grid gap-6 md:grid-cols-2">
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Supervisor</p>
                    <p class="mt-1 text-base text-app-text"><span class="italic">Dato no registrado</span></p>
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Cargo</p>
                    <p class="mt-1 text-base text-app-text"><span class="italic">Dato no registrado</span></p>
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Correo supervisor</p>
                    <p class="mt-1 text-base text-app-text"><span class="italic">Dato no registrado</span></p>
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Teléfono supervisor</p>
                    <p class="mt-1 text-base text-app-text"><span class="italic">Dato no registrado</span></p>
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (nombre)</p>
                    <p class="mt-1 text-base text-app-text"><span class="italic">Dato no registrado</span></p>
                </div>
                <div>
                    <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (correo)</p>
                    <p class="mt-1 text-base text-app-text break-all"><span class="italic">Dato no registrado</span></p>
                </div>
            </div>
        <?php else: ?>
            <div class="mt-4 space-y-6">
                <?php foreach ($jefes as $idx => $jefe): ?>
                    <?php $num = $idx + 1; ?>
                    <article class="rounded-lg border border-app-border bg-app-panelSubtle p-4">
                        <h4 class="m-0 text-sm font-semibold text-app-text">Jefe <?= (int) $num ?></h4>
                        <div class="mt-4 grid gap-6 md:grid-cols-2">
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Supervisor</p>
                                <p class="mt-1 text-base text-app-text"><?= trim((string) ($jefe['nombre'] ?? '')) !== '' ? e((string) $jefe['nombre']) : '<span class="italic">Dato no registrado</span>' ?></p>
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Cargo</p>
                                <p class="mt-1 text-base text-app-text"><?= trim((string) ($jefe['cargo'] ?? '')) !== '' ? e((string) $jefe['cargo']) : '<span class="italic">Dato no registrado</span>' ?></p>
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Correo supervisor</p>
                                <p class="mt-1 text-base text-app-text break-all"><?= trim((string) ($jefe['correo'] ?? '')) !== '' ? e((string) $jefe['correo']) : '<span class="italic">Dato no registrado</span>' ?></p>
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Teléfono supervisor</p>
                                <p class="mt-1 text-base text-app-text"><?= trim((string) ($jefe['telefono'] ?? '')) !== '' ? e((string) $jefe['telefono']) : '<span class="italic">Dato no registrado</span>' ?></p>
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (nombre)</p>
                                <p class="mt-1 text-base text-app-text"><?= trim((string) ($jefe['nombre_contacto2'] ?? '')) !== '' ? e((string) $jefe['nombre_contacto2']) : '<span class="italic">Dato no registrado</span>' ?></p>
                            </div>
                            <div>
                                <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (correo)</p>
                                <p class="mt-1 text-base text-app-text break-all"><?= trim((string) ($jefe['correo_contacto2'] ?? '')) !== '' ? e((string) $jefe['correo_contacto2']) : '<span class="italic">Dato no registrado</span>' ?></p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex justify-end border-t border-app-border pt-6">
        <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/eliminar">
            <input type="hidden" name="empresa_id" value="<?= $eid ?>">
            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-[18px] py-2 text-sm font-medium text-rose-700 transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100"
                data-confirm-modal
                data-confirm-title="Eliminar empresa"
                data-confirm-message="¿Eliminar esta empresa? Solo se permite si no tiene aprendices vinculados."
            >
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                Eliminar empresa
            </button>
        </form>
    </div>
</section>
<?php endif; ?>
