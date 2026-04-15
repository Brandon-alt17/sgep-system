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
        <select name="formato" class="mt-1 w-full rounded-lg border border-app-borderControlStrong px-3 py-2 text-sm md:max-w-xs">
            <option value="docx">Word (.docx)</option>
            <option value="pdf">PDF</option>
        </select>
    </label>
    <button type="submit" class="mt-4 <?= e(ui_button_primary_classes()) ?>">Generar y descargar</button>
</form>
