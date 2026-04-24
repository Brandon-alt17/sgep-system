<?php partial('components/page_header', [
    'title' => 'Generar GFPI-F-023',
    'subtitle' => 'Seleccione los apartados del formato y el tipo de archivo de salida.',
]); ?>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/documentos/generar" class="<?= e(ui_card_classes()) ?>">
    <input type="hidden" name="aprendiz_id" value="<?= (int) ($aprendiz_id ?? 0) ?>">
    <fieldset class="space-y-2">
        <legend class="text-sm font-semibold text-app-text">Partes a incluir</legend>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partes[]" value="info" checked class="h-4 w-4"> Información general</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partes[]" value="M1" checked class="h-4 w-4"> Momento 1</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partes[]" value="M2" checked class="h-4 w-4"> Momento 2</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partes[]" value="M3" checked class="h-4 w-4"> Momento 3</label>
    </fieldset>
    <label class="mt-4 block text-sm font-medium text-app-textSubtle">Formato
        <span class="<?= e(ui_select_wrapper_classes()) ?> md:max-w-xs">
            <select name="formato" class="<?= e(ui_select_classes()) ?>">
                <option value="docx">Word (.docx)</option>
                <option value="pdf">PDF</option>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </span>
    </label>
    <button type="submit" class="mt-4 <?= e(ui_button_primary_classes()) ?>">Generar y descargar</button>
</form>
