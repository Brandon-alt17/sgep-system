<?php
declare(strict_types=1);

$empresa = (array) ($empresa ?? []);
$errors = (array) ($errors ?? []);
$isDetail = isset($empresa['id']) && (int) $empresa['id'] > 0;
$eid = $isDetail ? (int) $empresa['id'] : 0;
$editing = $isDetail && (bool) ($editing ?? false);
$aprendicesCount = $isDetail ? (int) ($aprendicesCount ?? 0) : 0;
$aprendicesVinculados = $isDetail ? (array) ($aprendicesVinculados ?? []) : [];

$val = static function (string $key) use ($empresa): string {
    return e(trim((string) ($empresa[$key] ?? '')));
};

$showField = static function (string $key) use ($empresa): string {
    $t = trim((string) ($empresa[$key] ?? ''));
    return $t !== '' ? e($t) : '—';
};

$toastKey = trim((string) ($_GET['toast'] ?? ''));
$toastMessage = match ($toastKey) {
    'empresa_actualizada' => 'Empresa actualizada correctamente.',
    default => '',
};

$verUrl = APP_BASE_PATH . '/catalogo/empresas/ver?id=' . $eid;
$verEditUrl = $verUrl . '&edit=1';
$listadoAprendicesUrl = APP_BASE_PATH . '/aprendices?' . http_build_query([
    'empresa_id' => (string) $eid,
    'from' => 'empresas',
]);
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

    <h3 class="text-sm font-semibold text-app-text">Contacto principal (jefe de área / prácticas)</h3>
    <div class="grid gap-4 md:grid-cols-2">
        <label class="<?= e(ui_label_classes()) ?>">
            Nombre
            <input type="text" name="nombre_jefe" value="<?= $val('nombre_jefe') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            Cargo
            <input type="text" name="cargo_jefe" value="<?= $val('cargo_jefe') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            Correo
            <input type="email" name="correo_jefe" value="<?= $val('correo_jefe') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            Teléfono
            <input type="text" name="telefono_jefe" value="<?= $val('telefono_jefe') ?>" class="<?= e(ui_input_classes()) ?>">
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

<section class="bg-app-bg mb-6 flex flex-row flex-wrap items-start gap-3">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-0.5 shrink-0" href="<?= e($verUrl) ?>" aria-label="Volver a la información de la empresa">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="min-w-0 flex-1 pl-1">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Editar información</h2>
        <p class="m-0 mt-1 text-sm text-app-muted">Modifique los campos y guarde o cancele para volver sin cambios.</p>
    </div>
</section>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/actualizar"
      class="<?= e(ui_card_classes()) ?> mx-auto max-w-5xl space-y-6 p-6">
    <input type="hidden" name="id" value="<?= $eid ?>">

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

    <h3 class="text-sm font-semibold text-app-text">Contacto principal (jefe de área / prácticas)</h3>
    <div class="grid gap-4 md:grid-cols-2">
        <label class="<?= e(ui_label_classes()) ?>">
            Nombre
            <input type="text" name="nombre_jefe" value="<?= $val('nombre_jefe') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            Cargo
            <input type="text" name="cargo_jefe" value="<?= $val('cargo_jefe') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            Correo
            <input type="email" name="correo_jefe" value="<?= $val('correo_jefe') ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">
            Teléfono
            <input type="text" name="telefono_jefe" value="<?= $val('telefono_jefe') ?>" class="<?= e(ui_input_classes()) ?>">
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
        <a href="<?= e($verUrl) ?>" class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-app-borderControl bg-white px-5 text-sm font-medium text-app-text shadow-xsSoft no-underline transition hover:bg-app-panelSubtle">
            Cancelar
        </a>
        <button type="submit" class="<?= e(ui_button_primary_classes()) ?> inline-flex min-h-[44px] items-center justify-center px-6">
            Guardar cambios
        </button>
    </div>
</form>

<?php else: ?>
<?php partial('components/ui'); ?>

<?php partial('components/toast', ['message' => $toastMessage]); ?>

<section class="bg-app-bg mb-6 flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 shrink-0 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/empresas" aria-label="Volver al catálogo de empresas">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="flex min-w-0 flex-1 flex-col gap-3 pl-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="m-0 text-2xl font-semibold text-app-text">Información de la empresa</h2>
                <a href="<?= e($verEditUrl) ?>" class="<?= e(ui_button_small_classes()) ?> inline-flex shrink-0 items-center gap-2">
                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4" aria-hidden="true"><?= ui_icon('pencil') ?></span>
                    Editar información
                </a>
            </div>
            <p class="m-0 mt-1 text-sm text-app-muted">Consulta los datos de contacto y los aprendices vinculados.</p>
        </div>
    </div>
</section>

<section class="<?= e(ui_card_classes()) ?> mx-auto max-w-5xl space-y-8 p-6">
    <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Razón social</p>
            <p class="mt-1 text-base font-semibold text-app-text"><?= $showField('nombre') ?></p>
        </div>
        <div>
            <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">NIT</p>
            <p class="mt-1 text-base text-app-text"><?= $showField('nit') ?></p>
        </div>
        <div>
            <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Ciudad</p>
            <p class="mt-1 text-base text-app-text"><?= $showField('ciudad') ?></p>
        </div>
        <div class="md:col-span-2">
            <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Dirección</p>
            <p class="mt-1 text-base text-app-text"><?= $showField('direccion') ?></p>
        </div>
        <div class="md:col-span-2">
            <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Dirección de práctica</p>
            <p class="mt-1 text-base text-app-text"><?= $showField('direccion_practica') ?></p>
        </div>
        <div class="md:col-span-2">
            <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Correo institucional / organización</p>
            <p class="mt-1 text-base text-app-text break-all"><?= $showField('correo_org') ?></p>
        </div>
    </div>

    <div class="border-t border-app-border pt-6">
        <h3 class="m-0 text-sm font-semibold text-app-text">Contacto principal (jefe de área / prácticas)</h3>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <div>
                <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Nombre</p>
                <p class="mt-1 text-base text-app-text"><?= $showField('nombre_jefe') ?></p>
            </div>
            <div>
                <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Cargo</p>
                <p class="mt-1 text-base text-app-text"><?= $showField('cargo_jefe') ?></p>
            </div>
            <div>
                <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Correo</p>
                <p class="mt-1 text-base text-app-text break-all"><?= $showField('correo_jefe') ?></p>
            </div>
            <div>
                <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Teléfono</p>
                <p class="mt-1 text-base text-app-text"><?= $showField('telefono_jefe') ?></p>
            </div>
        </div>
    </div>

    <div class="border-t border-app-border pt-6">
        <h3 class="m-0 text-sm font-semibold text-app-text">Contacto alternativo</h3>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <div>
                <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Nombre</p>
                <p class="mt-1 text-base text-app-text"><?= $showField('nombre_contacto2') ?></p>
            </div>
            <div>
                <p class="m-0 text-xs font-medium uppercase tracking-wide text-app-muted">Correo</p>
                <p class="mt-1 text-base text-app-text break-all"><?= $showField('correo_contacto2') ?></p>
            </div>
        </div>
    </div>

    <div class="border-t border-app-border pt-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="m-0 text-sm font-semibold text-app-text">Aprendices asociados</h3>
                <p class="m-0 mt-0.5 text-xs text-app-muted"><?= (int) $aprendicesCount ?> registro(s) en esta empresa.</p>
            </div>
            <a href="<?= e($listadoAprendicesUrl) ?>" class="<?= e(ui_button_small_classes()) ?> inline-flex w-fit shrink-0 items-center gap-2 self-start sm:self-auto">
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4" aria-hidden="true"><?= ui_icon('users') ?></span>
                Ver aprendices
            </a>
        </div>

        <?php if ($aprendicesVinculados === []): ?>
            <p class="mt-4 text-sm text-app-muted">No hay aprendices vinculados a esta empresa.</p>
        <?php else: ?>
            <div class="-mx-2 mt-4 overflow-x-auto sm:mx-0">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-app-border bg-app-panelSubtle text-app-muted">
                            <th class="py-2.5 pl-2 pr-3 font-medium sm:pl-0">Nombre</th>
                            <th class="py-2.5 px-3 font-medium">Documento</th>
                            <th class="py-2.5 px-3 font-medium">Estado</th>
                            <th class="w-[1%] py-2.5 pl-3 pr-2 text-right sm:pr-0"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aprendicesVinculados as $apr): ?>
                            <?php
                            $aid = (int) ($apr['id'] ?? 0);
                            $estado = (string) ($apr['estado'] ?? '');
                            $profileUrl = APP_BASE_PATH . '/aprendices/show?' . http_build_query([
                                'id' => $aid,
                                'empresa_id' => (string) $eid,
                                'from' => 'empresas',
                            ]);
                            $badgeClass = match ($estado) {
                                'En ejecución',
                                'Aplazada',
                                'Finalizada',
                                'Certificado',
                                'Pendiente por comité' => ui_badge_success_classes(),
                                'Pendiente por iniciar' => ui_badge_warning_classes(),
                                default => ui_badge_error_classes(),
                            };
                            ?>
                            <tr class="border-b border-app-borderSoft last:border-0">
                                <td class="py-3 pl-2 pr-3 align-middle font-medium text-app-text sm:pl-0"><?= e((string) ($apr['nombre_completo'] ?? '')) ?></td>
                                <td class="py-3 px-3 align-middle text-app-muted"><?= e((string) ($apr['numero_documento'] ?? '')) ?></td>
                                <td class="py-3 px-3 align-middle">
                                    <?php if ($estado !== ''): ?>
                                        <span class="<?= e($badgeClass) ?>"><?= e($estado) ?></span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 pl-3 pr-2 text-right align-middle sm:pr-0">
                                    <a class="<?= e(ui_button_small_classes()) ?>" href="<?= e($profileUrl) ?>">Ver perfil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/eliminar" class="border-t border-app-border pt-6">
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
</section>
<?php endif; ?>
