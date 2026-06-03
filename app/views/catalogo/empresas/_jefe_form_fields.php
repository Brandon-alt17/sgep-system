<?php
declare(strict_types=1);

$jefe = (array) ($jefe ?? []);
$autocompleteOff = (bool) ($autocompleteOff ?? false);
$ac = $autocompleteOff
    ? ' autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false"'
    : '';
?>
<div class="grid gap-4 md:grid-cols-2">
    <label class="<?= e(ui_label_classes()) ?> md:col-span-2">Supervisor *
        <input type="text" name="nombre" value="<?= e((string) ($jefe['nombre'] ?? '')) ?>" required class="<?= e(ui_input_classes()) ?>"<?= $ac ?>>
    </label>
    <label class="<?= e(ui_label_classes()) ?>">Cargo
        <input type="text" name="cargo" value="<?= e((string) ($jefe['cargo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>"<?= $ac ?>>
    </label>
    <label class="<?= e(ui_label_classes()) ?>">Teléfono supervisor
        <input type="text" name="telefono" value="<?= e((string) ($jefe['telefono'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>"<?= $ac ?>>
    </label>
    <label class="<?= e(ui_label_classes()) ?> md:col-span-2">Correo supervisor
        <input type="email" name="correo" value="<?= e((string) ($jefe['correo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>"<?= $ac ?>>
    </label>
    <label class="<?= e(ui_label_classes()) ?>">Contacto alternativo (nombre)
        <input type="text" name="nombre_contacto2" value="<?= e((string) ($jefe['nombre_contacto2'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>"<?= $ac ?>>
    </label>
    <label class="<?= e(ui_label_classes()) ?>">Contacto alternativo (correo)
        <input type="email" name="correo_contacto2" value="<?= e((string) ($jefe['correo_contacto2'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>"<?= $ac ?>>
    </label>
</div>
