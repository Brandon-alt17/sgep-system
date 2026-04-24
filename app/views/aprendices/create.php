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
            <span class="<?= e(ui_select_wrapper_classes()) ?>">
                <select name="tipo_documento" required class="<?= e(ui_select_classes()) ?>">
                    <option value="CC">Cédula de Ciudadanía</option>
                    <option value="TI">Tarjeta de Identidad</option>
                    <option value="CE">Cédula de Extranjería</option>
                    <option value="PEP">PEP</option>
                    <option value="PPT">PPT</option>
                </select>
                <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </span>
            </span>
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
        <label class="<?= e(ui_label_classes()) ?>">Grupo
            <input type="text" name="ficha" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Estado
            <span class="<?= e(ui_select_wrapper_classes()) ?>">
                <select name="estado" class="<?= e(ui_select_classes()) ?>">
                    <option>Pendiente por iniciar</option>
                    <option>En ejecución</option>
                    <option>Aplazada</option>
                    <option>Finalizada</option>
                    <option>Por certificar</option>
                    <option>Certificado</option>
                    <option>Pendiente por comité</option>
                </select>
                <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </span>
            </span>
        </label>
    </div>
    <button type="submit" class="mt-4 <?= e(ui_button_primary_classes()) ?>">Guardar</button>
</form>
