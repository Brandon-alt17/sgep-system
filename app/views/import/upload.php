<?php partial('components/page_header', [
    'title' => 'Importar datos',
    'subtitle' => 'Cargue el archivo Excel con la información de seguimiento de aprendices en etapa productiva.',
]); ?>

<section class="grid gap-4 lg:grid-cols-[2fr_1fr]">
    <article class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Cargar archivo</h3>
        <p class="<?= e(ui_text_muted_classes()) ?>">Formato aceptado: .xlsx - Máximo 5 MB</p>
        <form method="post" action="<?= e(APP_BASE_PATH) ?>/importar" enctype="multipart/form-data">
            <input class="sr-only" data-import-input type="file" name="archivo" accept=".xlsx,.xls,.csv" required>
            <div class="mt-3 grid min-h-[122px] place-items-center rounded-lg border border-dashed border-app-borderSoft bg-app-panelSoft p-2.5 text-center" data-import-dropzone>
                <div>
                    <div class="mx-auto mb-2 h-[22px] w-[22px] text-app-muted [&_svg]:h-[22px] [&_svg]:w-[22px] [&_svg]:fill-current"><?= ui_icon('upload') ?></div>
                    <strong class="text-[13px]">Arrastre el archivo Excel aquí o haga clic para seleccionar</strong>
                    <small class="mt-0.5 block text-[11px] text-app-mutedSoft">Solo archivos Excel (.xlsx, .xls, .csv)</small>
                    <button type="button" class="mt-2 <?= e(ui_button_small_classes()) ?>" data-import-trigger>Seleccionar archivo</button>
                    <small class="mt-0.5 block text-[11px] text-app-mutedSoft" data-import-filename>Sin archivo seleccionado</small>
                </div>
            </div>
            <button type="submit" class="mt-2 <?= e(ui_button_small_primary_classes()) ?>">Importar archivo</button>
        </form>
    </article>

    <article class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Instrucciones</h3>
        <ol class="m-0 list-decimal space-y-1 pl-5 text-sm text-app-textSubtle">
            <li>Descargue la plantilla oficial de seguimiento de aprendices.</li>
            <li>Complete la plantilla con los datos de los aprendices en etapa productiva.</li>
            <li>Cargue el archivo en el área de importación.</li>
            <li>Revise el resumen de importación y confirme los registros.</li>
        </ol>
        <h3 class="mb-2 mt-4 text-[15px] font-semibold">Plantilla disponible:</h3>
        <a class="<?= e(ui_button_small_classes()) ?> items-center gap-1" href="#" aria-disabled="true"><?= ui_icon('file') ?> Plantilla de seguimiento</a>
    </article>
</section>

<section class="mt-4 <?= e(ui_card_classes()) ?>">
    <h3 class="<?= e(ui_heading_sm_classes()) ?>">Historial de importaciones</h3>
    <p class="<?= e(ui_text_muted_classes()) ?>">Últimas cargas realizadas en el sistema</p>
    <table class="<?= e(ui_table_classes()) ?>">
        <thead>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Archivo</th>
            <th class="<?= e(ui_th_classes()) ?>">Fecha</th>
            <th class="<?= e(ui_th_classes()) ?>">Registros</th>
            <th class="<?= e(ui_th_classes()) ?>">Estado</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="<?= e(ui_td_classes()) ?>">aprendices_ficha_2745623.xlsx</td>
            <td class="<?= e(ui_td_classes()) ?>">2025-01-15</td>
            <td class="<?= e(ui_td_classes()) ?>">12</td>
            <td class="<?= e(ui_td_classes()) ?>"><span class="<?= e(ui_badge_success_classes()) ?>">Exitoso</span></td>
        </tr>
        <tr>
            <td class="<?= e(ui_td_classes()) ?>">aprendices_ficha_28U1445.xlsx</td>
            <td class="<?= e(ui_td_classes()) ?>">2025-02-03</td>
            <td class="<?= e(ui_td_classes()) ?>">14</td>
            <td class="<?= e(ui_td_classes()) ?>"><span class="<?= e(ui_badge_success_classes()) ?>">Exitoso</span></td>
        </tr>
        <tr>
            <td class="<?= e(ui_td_classes()) ?>">aprendices_seguimiento_2025.xlsx</td>
            <td class="<?= e(ui_td_classes()) ?>">2025-02-10</td>
            <td class="<?= e(ui_td_classes()) ?>">8</td>
            <td class="<?= e(ui_td_classes()) ?>"><span class="<?= e(ui_badge_success_classes()) ?>">Parcial</span></td>
        </tr>
        </tbody>
    </table>
</section>
