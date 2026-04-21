<?php
$insertedRows = (array) ($resultado['inserted_rows'] ?? []);
$updatedRows = (array) ($resultado['updated_rows'] ?? []);
$duplicateRows = (array) ($resultado['duplicate_rows'] ?? []);
$processed = (int) ($entry['records'] ?? ((int) ($resultado['inserted'] ?? 0) + (int) ($resultado['updated'] ?? 0)));
$tabs = [
    'nuevos' => [
        'label' => 'Nuevos',
        'icon' => 'user-plus',
        'count' => count($insertedRows),
        'rows' => $insertedRows,
        'empty' => 'Sin nuevos registros en esta ejecución.',
        'iconColor' => 'text-app-accent',
        'active' => 'bg-app-accentSoft text-app-accentStrong',
    ],
    'actualizados' => [
        'label' => 'Actualizados',
        'icon' => 'refresh-cw',
        'count' => count($updatedRows),
        'rows' => $updatedRows,
        'empty' => 'Sin actualizaciones en esta ejecución.',
        'iconColor' => 'text-blue-600',
        'active' => 'bg-blue-50 text-blue-700',
    ],
    'duplicados' => [
        'label' => 'Duplicados',
        'icon' => 'copy',
        'count' => count($duplicateRows),
        'rows' => $duplicateRows,
        'empty' => 'Sin duplicados en esta ejecución.',
        'iconColor' => 'text-amber-600',
        'active' => 'bg-amber-50 text-amber-700',
    ],
];
$activeTab = (string) ($_GET['tab'] ?? 'nuevos');
if (!isset($tabs[$activeTab])) {
    $activeTab = 'nuevos';
}
$detailBaseUrl = APP_BASE_PATH . '/importar/resultado?id=' . urlencode((string) ($entry['id'] ?? ''));
$activeRows = (array) $tabs[$activeTab]['rows'];
?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e(APP_BASE_PATH) ?>/importar" aria-label="Volver a importaciones">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5 "><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">    
        <h2 class="m-0 text-2xl font-semibold text-app-text">Detalle de importación</h2>
        <p class="m-0 text-sm text-app-muted">
        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('file') ?></span>
        <?= e((string) ($entry['file_name'] ?? '')) ?>
        &mdash;
        <?= e((string) ($entry['date'] ?? '')) ?>
        &mdash;
        <?= e((string) ($entry['status'] ?? '')) ?>
        &mdash;
        <?= e((string) $processed) ?> registros procesados
    </p>
    </div>
</section>

<section class="mt-4 grid gap-4 md:grid-cols-3">
    <article class="<?= e(ui_card_classes()) ?>">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-app-accentSoft text-app-accent [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('user-plus') ?></span>
            <div>
                <p class="m-0 text-2xl font-bold text-app-text"><?= e((string) ($resultado['inserted'] ?? 0)) ?></p>
                <p class="m-0 text-xs text-app-muted">Nuevos registros</p>
            </div>
        </div>
    </article>
    <article class="<?= e(ui_card_classes()) ?>">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-blue-50 text-blue-600 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('refresh-cw') ?></span>
            <div>
                <p class="m-0 text-2xl font-bold text-app-text"><?= e((string) ($resultado['updated'] ?? 0)) ?></p>
                <p class="m-0 text-xs text-app-muted">Actualizados</p>
            </div>
        </div>
    </article>
    <article class="<?= e(ui_card_classes()) ?>">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-amber-50 text-amber-600 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('copy') ?></span>
            <div>
                <p class="m-0 text-2xl font-bold text-app-text"><?= e((string) ($resultado['duplicates'] ?? 0)) ?></p>
                <p class="m-0 text-xs text-app-muted">Duplicados (omitidos)</p>
            </div>
        </div>
    </article>
</section>

<section class="mt-4 <?= e(ui_card_classes()) ?>">
    <div class="mb-5 inline-flex rounded-md border border-app-border bg-app-panelSubtle p-1 text-sm">
        <?php foreach ($tabs as $key => $tab): ?>
            <?php $isActive = $activeTab === $key; ?>
            <a
                class="<?= e('inline-flex items-center gap-1.5 rounded px-3 py-1.5 font-medium no-underline transition-colors duration-200 ' . ($isActive ? $tab['active'] : 'text-app-muted hover:bg-app-panel hover:text-app-text')) ?>"
                href="<?= e($detailBaseUrl . '&tab=' . urlencode((string) $key)) ?>"
            >
                <span class="<?= e('inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4 ' . ($isActive ? '' : $tab['iconColor'])) ?>"><?= ui_icon((string) $tab['icon']) ?></span>
                <?= e((string) $tab['label']) ?> (<?= e((string) $tab['count']) ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <div id="<?= e($activeTab) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>"><?= e((string) $tabs[$activeTab]['label']) ?></h3>
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
            <?php if ($activeRows !== []): ?>
                <?php foreach ($activeRows as $row): ?>
                    <tr>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['nombre'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['identificacion'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['ficha'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['programa'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td class="<?= e(ui_td_classes()) ?>" colspan="4"><?= e((string) $tabs[$activeTab]['empty']) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
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
