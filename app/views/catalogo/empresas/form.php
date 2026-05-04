<?php
declare(strict_types=1);

$empresa = (array) ($empresa ?? []);
$errors = (array) ($errors ?? []);
$isEdit = isset($empresa['id']) && (int) $empresa['id'] > 0;
$eid = $isEdit ? (int) $empresa['id'] : 0;

$val = static function (string $key) use ($empresa): string {
    return e(trim((string) ($empresa[$key] ?? '')));
};

partial('components/page_header', [
    'title' => $isEdit ? 'Editar empresa' : 'Nueva empresa',
    'subtitle' => $isEdit ? 'Actualiza los datos de la empresa coformadora.' : 'Registra una empresa para asociarla a aprendices.',
]);
?>

<?php if ($errors !== []): ?>
    <ul class="mb-4 list-disc space-y-1 rounded-[10px] border border-rose-200 bg-rose-50 p-4 pl-8 text-sm text-rose-800">
        <?php foreach ($errors as $err): ?>
            <li><?= e(is_string($err) ? $err : (string) $err) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?= e(APP_BASE_PATH) ?><?= $isEdit ? '/catalogo/empresas/actualizar' : '/catalogo/empresas' ?>"
      class="<?= e(ui_card_classes()) ?> max-w-5xl space-y-6 p-6 mx-auto">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $eid ?>">
    <?php endif; ?>

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
        <button type="submit" class="<?= e(ui_button_primary_classes()) ?>">
            <?= $isEdit ? 'Guardar cambios' : 'Crear empresa' ?>
        </button>
    </div>
</form>
