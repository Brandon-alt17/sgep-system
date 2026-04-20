<?php partial('components/page_header', [
    'title' => 'Importar datos',
    'subtitle' => 'Cargue el archivo Excel con la información de seguimiento de aprendices en etapa productiva.',
]); ?>

<section class="grid gap-4 lg:grid-cols-[2fr_1fr]">
    <article class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Cargar archivo</h3>
        <p class="<?= e(ui_text_muted_classes()) ?>">Formato aceptado: .xlsx - Máximo 5 MB</p>
        <form method="post" action="<?= e(APP_BASE_PATH) ?>/importar" enctype="multipart/form-data" data-import-form>
            <input class="sr-only" data-import-input type="file" name="archivo" accept=".xlsx,.xls,.csv" required>
            <div class="mt-3 grid min-h-[122px] place-items-center rounded-lg border border-dashed border-app-borderSoft bg-app-panelSoft p-2.5 text-center">
                <div>
                    <div class="mx-auto mb-2 h-[22px] w-[22px] text-app-muted [&_svg]:h-[22px] [&_svg]:w-[22px]"><?= ui_icon('upload') ?></div>
                    <strong class="text-[13px]">Seleccione el archivo Excel con el botón de carga</strong>
                    <small class="mt-0.5 block text-[11px] text-app-mutedSoft">Solo archivos Excel (.xlsx, .xls, .csv)</small>
                    <button type="button" class="mt-2 <?= e(ui_button_small_classes()) ?>" data-import-trigger>Seleccionar archivo</button>
                    <small class="mt-0.5 block text-[11px] text-app-mutedSoft" data-import-filename>Sin archivo seleccionado</small>
                </div>
            </div>
            <div class="mt-3 hidden" data-import-progress>
                <div class="mb-1 flex items-center justify-between text-xs text-app-muted">
                    <span data-import-progress-label>Preparando carga...</span>
                    <span data-import-progress-value>0%</span>
                </div>
                <div class="h-1.5 w-full rounded bg-app-panelSubtle">
                    <div class="h-full w-0 rounded bg-app-accent" data-import-progress-bar></div>
                </div>
            </div>
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
        <?php if (!empty($templateAvailable)): ?>
            <a class="<?= e(ui_button_small_classes()) ?> items-center gap-1" href="<?= e((string) ($templateUrl ?? '#')) ?>" download><?= ui_icon('download') ?> Plantilla de seguimiento</a>
        <?php else: ?>
            <span class="<?= e(ui_button_small_classes()) ?> items-center gap-1 opacity-60"><?= ui_icon('file') ?> Plantilla no cargada</span>
        <?php endif; ?>
    </article>
</section>

<?php if (!empty($flashError)): ?>
    <section class="mt-4 <?= e(ui_warning_card_classes()) ?>">
        <?= e((string) $flashError) ?>
    </section>
<?php endif; ?>

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
        <?php if (!empty($history)): ?>
            <?php foreach ($history as $row): ?>
                <?php
                $status = (string) ($row['status'] ?? 'Exitoso');
                $badgeClass = ui_badge_success_classes();
                if ($status === 'Parcial') {
                    $badgeClass = ui_badge_warning_classes();
                } elseif ($status === 'Fallido') {
                    $badgeClass = ui_badge_error_classes();
                }
                ?>
                <tr>
                    <td class="<?= e(ui_td_classes()) ?>">
                        <?php if (!empty($row['id'])): ?>
                            <a class="font-medium text-app-link" href="<?= e(APP_BASE_PATH) ?>/importar/resultado?id=<?= e((string) $row['id']) ?>"><?= e((string) ($row['file_name'] ?? 'archivo.xlsx')) ?></a>
                        <?php else: ?>
                            <?= e((string) ($row['file_name'] ?? 'archivo.xlsx')) ?>
                        <?php endif; ?>
                    </td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['date'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['records'] ?? 0)) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>"><span class="<?= e($badgeClass) ?>"><?= e($status) ?></span></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td class="<?= e(ui_td_classes()) ?>" colspan="4">Aún no hay importaciones registradas.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>
