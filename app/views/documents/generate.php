<?php partial('components/page_header', [
    'title' => 'Generar GFPI-F-023',
    'subtitle' => 'Seleccione los apartados del formato y el tipo de archivo de salida.',
]); ?>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/documentos/generar" class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
    <input type="hidden" name="aprendiz_id" value="<?= (int) ($aprendiz_id ?? 0) ?>">
    <fieldset class="space-y-2">
        <legend class="text-sm font-semibold text-gray-800">Partes a incluir</legend>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partes[]" value="info" checked class="h-4 w-4"> Información general</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partes[]" value="M1" checked class="h-4 w-4"> Momento 1</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partes[]" value="M2" checked class="h-4 w-4"> Momento 2</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="partes[]" value="M3" checked class="h-4 w-4"> Momento 3</label>
    </fieldset>
    <label class="mt-4 block text-sm font-medium text-gray-700">Formato
        <select name="formato" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm md:max-w-xs">
            <option value="docx">Word (.docx)</option>
            <option value="pdf">PDF</option>
        </select>
    </label>
    <button type="submit" class="mt-4 inline-flex rounded-md border border-app-accent bg-app-accent px-4 py-2 text-sm font-medium text-white hover:bg-[#0e8f82]">Generar y descargar</button>
</form>
