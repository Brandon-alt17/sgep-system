<?php partial('components/page_header', [
    'title' => 'Nuevo aprendiz',
    'subtitle' => 'Complete la información base para registrar un aprendiz.',
]); ?>

<?php if (!empty($errors)): ?>
    <ul class="mb-4 list-disc space-y-1 rounded-[10px] border border-rose-200 bg-rose-50 p-4 pl-8 text-sm text-rose-800">
        <?php foreach ($errors as $err): ?>
            <li><?= e((string) $err) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/aprendices" class="<?= e(ui_card_classes()) ?>">
    <div class="grid gap-4 md:grid-cols-2">
        <label class="<?= e(ui_label_classes()) ?>">Nombre completo
            <input type="text" name="nombre_completo" required class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Tipo documento
            <select name="tipo_documento" required class="<?= e(ui_input_classes()) ?>">
                <option value="CC">Cédula de Ciudadanía</option>
                <option value="TI">Tarjeta de Identidad</option>
                <option value="CE">Cédula de Extranjería</option>
                <option value="PEP">PEP</option>
                <option value="PPT">PPT</option>
            </select>
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Número documento
            <input type="text" name="numero_documento" required class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Teléfono
            <input type="text" name="telefono" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Correo personal
            <input type="email" name="correo_personal" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Correo institucional
            <input type="email" name="correo_institucional" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Ficha
            <input type="text" name="ficha" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Estado
            <select name="estado" class="<?= e(ui_input_classes()) ?>">
            <option>Pendiente por iniciar</option>
            <option>En ejecución</option>
            <option>Aplazada</option>
            <option>Finalizada</option>
            <option>Por certificar</option>
            <option>Certificado</option>
            <option>Pendiente por comité</option>
        </select>
        </label>
    </div>
    <button type="submit" class="mt-4 <?= e(ui_button_primary_classes()) ?>">Guardar</button>
</form>
