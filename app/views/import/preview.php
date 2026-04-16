<?php
$insertedRows = (array) ($resultado['inserted_rows'] ?? []);
$updatedRows = (array) ($resultado['updated_rows'] ?? []);
$duplicateRows = (array) ($resultado['duplicate_rows'] ?? []);
$processed = (int) ($entry['records'] ?? ((int) ($resultado['inserted'] ?? 0) + (int) ($resultado['updated'] ?? 0)));
?>

<section class="<?= e(ui_card_classes()) ?>">
    <div class="mb-3 flex items-center gap-2">
        <a class="<?= e(ui_button_small_classes()) ?>" href="<?= e(APP_BASE_PATH) ?>/importar">&larr;</a>
        <h2 class="m-0 text-2xl font-semibold text-app-text">Detalle de importación</h2>
    </div>
    <p class="m-0 text-sm text-app-muted">
        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('file') ?></span>
        <?= e((string) ($entry['file_name'] ?? '')) ?>
        &mdash;
        <?= e((string) ($entry['date'] ?? '')) ?>
        &mdash;
        <?= e((string) $processed) ?> registros procesados
    </p>
</section>

<section class="mt-4 grid gap-4 md:grid-cols-3">
    <article class="<?= e(ui_card_classes()) ?>">
        <p class="m-0 text-3xl font-bold text-app-text"><?= e((string) ($resultado['inserted'] ?? 0)) ?></p>
        <p class="m-0 text-sm text-app-muted">Nuevos registros</p>
    </article>
    <article class="<?= e(ui_card_classes()) ?>">
        <p class="m-0 text-3xl font-bold text-app-text"><?= e((string) ($resultado['updated'] ?? 0)) ?></p>
        <p class="m-0 text-sm text-app-muted">Actualizados</p>
    </article>
    <article class="<?= e(ui_card_classes()) ?>">
        <p class="m-0 text-3xl font-bold text-app-text"><?= e((string) ($resultado['duplicates'] ?? 0)) ?></p>
        <p class="m-0 text-sm text-app-muted">Duplicados (omitidos)</p>
    </article>
</section>

<section class="mt-4 <?= e(ui_card_classes()) ?>">
    <div class="mb-3 inline-flex rounded-md border border-app-border bg-app-panelSubtle p-1 text-sm">
        <a class="rounded px-2 py-1 hover:bg-app-panel" href="#nuevos">Nuevos (<?= e((string) count($insertedRows)) ?>)</a>
        <a class="rounded px-2 py-1 hover:bg-app-panel" href="#actualizados">Actualizados (<?= e((string) count($updatedRows)) ?>)</a>
        <a class="rounded px-2 py-1 hover:bg-app-panel" href="#duplicados">Duplicados (<?= e((string) count($duplicateRows)) ?>)</a>
    </div>

    <div id="nuevos">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Nuevos</h3>
        <table class="<?= e(ui_table_classes()) ?>">
            <thead>
            <tr>
                <th class="<?= e(ui_th_classes()) ?>">Nombre</th>
                <th class="<?= e(ui_th_classes()) ?>">Identificación</th>
                <th class="<?= e(ui_th_classes()) ?>">Ficha</th>
                <th class="<?= e(ui_th_classes()) ?>">Programa</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($insertedRows !== []): ?>
                <?php foreach ($insertedRows as $row): ?>
                    <tr>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['nombre'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['identificacion'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['ficha'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['programa'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td class="<?= e(ui_td_classes()) ?>" colspan="4">Sin nuevos registros en esta ejecución.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="actualizados" class="mt-6">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Actualizados</h3>
        <table class="<?= e(ui_table_classes()) ?>">
            <thead>
            <tr>
                <th class="<?= e(ui_th_classes()) ?>">Nombre</th>
                <th class="<?= e(ui_th_classes()) ?>">Identificación</th>
                <th class="<?= e(ui_th_classes()) ?>">Ficha</th>
                <th class="<?= e(ui_th_classes()) ?>">Programa</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($updatedRows !== []): ?>
                <?php foreach ($updatedRows as $row): ?>
                    <tr>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['nombre'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['identificacion'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['ficha'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['programa'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td class="<?= e(ui_td_classes()) ?>" colspan="4">Sin actualizaciones en esta ejecución.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="duplicados" class="mt-6">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Duplicados</h3>
        <table class="<?= e(ui_table_classes()) ?>">
            <thead>
            <tr>
                <th class="<?= e(ui_th_classes()) ?>">Nombre</th>
                <th class="<?= e(ui_th_classes()) ?>">Identificación</th>
                <th class="<?= e(ui_th_classes()) ?>">Ficha</th>
                <th class="<?= e(ui_th_classes()) ?>">Programa</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($duplicateRows !== []): ?>
                <?php foreach ($duplicateRows as $row): ?>
                    <tr>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['nombre'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['identificacion'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['ficha'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['programa'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td class="<?= e(ui_td_classes()) ?>" colspan="4">Sin duplicados en esta ejecución.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="mt-4 <?= e(ui_card_classes()) ?>">
    <div class="grid gap-3 md:grid-cols-4">
        <div>
            <p class="m-0 text-xs text-app-muted">ID</p>
            <p class="m-0 text-sm font-medium"><?= e((string) ($entry['id'] ?? '')) ?></p>
        </div>
        <div>
            <p class="m-0 text-xs text-app-muted">Archivo</p>
            <p class="m-0 text-sm font-medium"><?= e((string) ($entry['file_name'] ?? '')) ?></p>
        </div>
        <div>
            <p class="m-0 text-xs text-app-muted">Fecha</p>
            <p class="m-0 text-sm font-medium"><?= e((string) ($entry['date'] ?? '')) ?></p>
        </div>
        <div>
            <p class="m-0 text-xs text-app-muted">Estado</p>
            <p class="m-0 text-sm font-medium"><?= e((string) ($entry['status'] ?? '')) ?></p>
        </div>
    </div>
</section>

<section class="mt-4 <?= e(ui_card_classes()) ?>">
    <table class="<?= e(ui_table_classes()) ?>">
        <tbody>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Insertados</th>
            <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($resultado['inserted'] ?? 0)) ?></td>
        </tr>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Actualizados</th>
            <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($resultado['updated'] ?? 0)) ?></td>
        </tr>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Duplicados detectados</th>
            <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($resultado['duplicates'] ?? 0)) ?></td>
        </tr>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Omitidos por requeridos vacíos</th>
            <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($resultado['skipped'] ?? 0)) ?></td>
        </tr>
        </tbody>
    </table>
</section>

<?php if (!empty($resultado['warnings'])): ?>
    <section class="mt-4 <?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Advertencias</h3>
        <ul class="m-0 list-disc space-y-1 pl-5 text-sm text-app-warningText">
            <?php foreach ($resultado['warnings'] as $warning): ?>
                <li><?= e((string) $warning) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if (!empty($resultado['errors'])): ?>
    <section class="mt-4 <?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Errores</h3>
        <ul class="m-0 list-disc space-y-1 pl-5 text-sm text-rose-700">
            <?php foreach ($resultado['errors'] as $error): ?>
                <li><?= e((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
