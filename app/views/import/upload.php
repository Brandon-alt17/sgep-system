<?php partial('components/page_header', [
    'title' => 'Importar datos',
    'subtitle' => 'Cargue el archivo Excel con la información de seguimiento de aprendices en etapa productiva.',
]); ?>

<section class="grid gap-4 lg:grid-cols-[2fr_1fr]">
    <article class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
        <h3 class="mb-2 mt-0 text-[15px] font-semibold">Cargar archivo</h3>
        <p class="mb-2 mt-0 text-xs text-app-muted">Formato aceptado: .xlsx - Máximo 5 MB</p>
        <form method="post" action="<?= e(APP_BASE_PATH) ?>/importar" enctype="multipart/form-data">
            <input class="sr-only" data-import-input type="file" name="archivo" accept=".xlsx,.xls,.csv" required>
            <div class="mt-3 grid min-h-[122px] place-items-center rounded-lg border border-dashed border-[#d5dbe3] bg-[#fbfcfd] p-2.5 text-center" data-import-dropzone>
                <div>
                    <div class="mx-auto mb-2 h-[22px] w-[22px] text-app-muted [&_svg]:h-[22px] [&_svg]:w-[22px] [&_svg]:fill-current"><?= ui_icon('upload') ?></div>
                    <strong class="text-[13px]">Arrastre el archivo Excel aquí o haga clic para seleccionar</strong>
                    <small class="mt-0.5 block text-[11px] text-[#9aa2af]">Solo archivos Excel (.xlsx, .xls, .csv)</small>
                    <button type="button" class="mt-2 cursor-pointer rounded-md border border-[#cfd6df] bg-[#f8fafb] px-2.5 py-1.5 text-xs" data-import-trigger>Seleccionar archivo</button>
                    <small class="mt-0.5 block text-[11px] text-[#9aa2af]" data-import-filename>Sin archivo seleccionado</small>
                </div>
            </div>
            <button type="submit" class="mt-2 cursor-pointer rounded-md border border-app-accent bg-app-accent px-2.5 py-1.5 text-xs text-white">Importar archivo</button>
        </form>
    </article>

    <article class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
        <h3 class="mb-2 mt-0 text-[15px] font-semibold">Instrucciones</h3>
        <ol class="m-0 list-decimal space-y-1 pl-5 text-sm text-gray-700">
            <li>Descargue la plantilla oficial de seguimiento de aprendices.</li>
            <li>Complete la plantilla con los datos de los aprendices en etapa productiva.</li>
            <li>Cargue el archivo en el área de importación.</li>
            <li>Revise el resumen de importación y confirme los registros.</li>
        </ol>
        <h3 class="mb-2 mt-4 text-[15px] font-semibold">Plantilla disponible:</h3>
        <a class="inline-flex items-center gap-1 rounded-md border border-[#cfd6df] bg-[#f8fafb] px-2.5 py-1.5 text-xs text-teal-700 no-underline hover:bg-[#eef3f6]" href="#" aria-disabled="true"><?= ui_icon('file') ?> Plantilla de seguimiento</a>
    </article>
</section>

<section class="mt-4 rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
    <h3 class="mb-2 mt-0 text-[15px] font-semibold">Historial de importaciones</h3>
    <p class="mb-2 mt-0 text-xs text-app-muted">Últimas cargas realizadas en el sistema</p>
    <table class="mt-2.5 w-full border-collapse">
        <thead>
        <tr>
            <th class="border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted">Archivo</th>
            <th class="border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted">Fecha</th>
            <th class="border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted">Registros</th>
            <th class="border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted">Estado</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">aprendices_ficha_2745623.xlsx</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">2025-01-15</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">12</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs"><span class="rounded-full border border-[#d4ead7] bg-app-successBg px-2 py-0.5 text-[11px] font-semibold text-app-successText">Exitoso</span></td>
        </tr>
        <tr>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">aprendices_ficha_28U1445.xlsx</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">2025-02-03</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">14</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs"><span class="rounded-full border border-[#d4ead7] bg-app-successBg px-2 py-0.5 text-[11px] font-semibold text-app-successText">Exitoso</span></td>
        </tr>
        <tr>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">aprendices_seguimiento_2025.xlsx</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">2025-02-10</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs">8</td>
            <td class="border-b border-app-border px-2 py-2.5 text-left text-xs"><span class="rounded-full border border-[#d4ead7] bg-app-successBg px-2 py-0.5 text-[11px] font-semibold text-app-successText">Parcial</span></td>
        </tr>
        </tbody>
    </table>
</section>
