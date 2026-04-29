<?php partial('components/page_header', [
    'title' => 'Importar datos',
    'subtitle' => 'Cargue el archivo Excel con la información de seguimiento de aprendices en etapa productiva.',
]); ?>

<section class="grid gap-6 lg:grid-cols-[2fr_1fr]">
    <?php
    partial('components/upload_dropzone_card', array_merge(
        ui_upload_dropzone_preset('excel'),
        ['action' => APP_BASE_PATH . '/importar']
    ));
    ?>

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
                    <td class="<?= e(ui_td_classes()) ?> align-middle text-center" colspan="4">Aún no hay importaciones registradas.</td>
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
