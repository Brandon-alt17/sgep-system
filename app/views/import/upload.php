<?php partial('components/page_header', [
    'title' => 'Importar datos',
    'subtitle' => 'Cargue el archivo Excel con la información de seguimiento de aprendices en etapa productiva.',
]); ?>

<section class="grid gap-6 lg:grid-cols-[2fr_1fr]">
    <article class="<?= e(ui_card_surface_classes()) ?>">
        <div class="<?= e(ui_card_header_classes()) ?>">
            <div class="<?= e(ui_card_header_stack_classes()) ?>">
                <h3 class="<?= e(ui_card_title_classes()) ?>">Cargar archivo</h3>
                <p class="<?= e(ui_card_description_classes()) ?>">Formato aceptado: .xlsx - Máximo 5 MB</p>
            </div>
        </div>
        <div class="<?= e(ui_card_body_classes()) ?>">
            <form method="post" action="<?= e(APP_BASE_PATH) ?>/importar" enctype="multipart/form-data" data-import-form>
                <input class="sr-only" data-import-input type="file" name="archivo" accept=".xlsx,.xls,.csv" required>
                <div class="upload-dropzone-dashed grid min-h-[122px] place-items-center rounded-lg bg-app-panel p-10 text-center transition-colors duration-150" data-import-dropzone>
                    <div>
                        <div class="mx-auto mb-3 h-[40px] w-[40px] text-app-muted [&_svg]:h-[40px] [&_svg]:w-[40px]"><?= ui_icon('upload') ?></div>
                        <strong class="text-sm font-medium">Arrastre el archivo Excel aquí o haga clic para seleccionar</strong>
                        <small class="mt-0.5 block text-xs text-app-mutedSoft">Solo archivos Excel (.xlsx, .xls, .csv)</small>
                        <button type="button" class="mt-2 <?= e(ui_button_small_classes()) ?> text-app-text" data-import-trigger>Seleccionar archivo</button>
                        <small class="mt-0.5 block text-xs text-app-mutedSoft" data-import-filename>Sin archivo seleccionado</small>
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
        </div>
    </article>

    <article class="<?= e(ui_card_surface_classes()) ?>">
        <div class="<?= e(ui_card_header_classes()) ?>">
            <div class="<?= e(ui_card_header_stack_classes()) ?>">
                <h3 class="<?= e(ui_card_title_classes()) ?>">Instrucciones</h3>
            </div>
        </div>
        <div class="<?= e(ui_card_body_classes()) ?>">
            <ol class="m-0 list-decimal space-y-1 pl-5 text-sm text-app-muted">
                <li>Descargue la plantilla oficial de seguimiento de aprendices.</li>
                <li>Complete la plantilla con los datos de los aprendices en etapa productiva.</li>
                <li>Cargue el archivo en el área de importación.</li>
                <li>Revise la vista previa con los registros nuevos, actualizados y duplicados.</li>
                <li>Confirme la importación para guardar los cambios.</li>
            </ol>
            <h3 class="mb-2 mt-6 text-[15px] font-semibold border-t border-app-border pt-4">Plantilla disponible:</h3>
            <?php if (!empty($templateAvailable)): ?>
                <a class="<?= e(ui_button_small_classes()) ?> items-center gap-1 w-full" href="<?= e((string) ($templateUrl ?? '#')) ?>" download><span class="inline-flex h-4 w-4 shrink-0 items-center justify-center [&_svg]:h-4 [&_svg]:w-4 mr-1"><?= ui_icon('download') ?></span> Plantilla de seguimiento</a>
            <?php else: ?>
                <span class="<?= e(ui_button_small_classes()) ?> items-center gap-1 opacity-60"><?= ui_icon('file') ?> Plantilla no cargada</span>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php if (!empty($flashError)): ?>
    <section class="mt-4 <?= e(ui_warning_card_classes()) ?>">
        <?= e((string) $flashError) ?>
    </section>
<?php endif; ?>

<section id="historial-importaciones" class="<?= e(ui_card_surface_classes()) ?> mt-6">
    <div class="<?= e(ui_card_header_classes()) ?>">
        <div class="<?= e(ui_card_header_stack_classes()) ?>">
            <h3 class="<?= e(ui_card_title_classes()) ?>">Historial de importaciones</h3>
            <p class="<?= e(ui_card_description_classes()) ?>">Últimas cargas realizadas en el sistema</p>
        </div>
    </div>
    <div class="<?= e(ui_card_body_classes()) ?>">
        <table class="<?= e(ui_table_in_card_classes()) ?>">
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
                    <?php $detailUrl = !empty($row['id']) ? (APP_BASE_PATH . '/importar/resultado?id=' . (string) $row['id']) : ''; ?>
                    <tr<?= $detailUrl !== '' ? ' class="cursor-pointer" role="link" tabindex="0" onclick="window.location.href=\'' . e($detailUrl) . '\'" onkeydown="if(event.key===\'Enter\' || event.key===\' \'){event.preventDefault();window.location.href=\'' . e($detailUrl) . '\';}"' : '' ?>>
                        <td class="<?= e(ui_td_classes()) ?>">
                            <?php if (!empty($row['id'])): ?>
                                <a class="inline-flex items-center gap-2 font-medium text-app-link no-underline hover:no-underline" href="<?= e($detailUrl) ?>">
                                    <span class="inline-flex h-4 w-4 text-app-muted [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('file') ?></span>
                                    <span><?= e((string) ($row['file_name'] ?? 'archivo.xlsx')) ?></span>
                                </a>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-flex h-4 w-4 text-app-muted [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('file') ?></span>
                                    <span><?= e((string) ($row['file_name'] ?? 'archivo.xlsx')) ?></span>
                                </span>
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
        <?php
        ui_render_pagination(
            (int) ($historyPage ?? 1),
            (int) ($historyTotalPages ?? 1),
            APP_BASE_PATH . '/importar?page=%d',
            'Paginación de historial',
            'historial-importaciones'
        );
        ?>
    </div>
</section>
