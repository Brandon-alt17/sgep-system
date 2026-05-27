<?php
$insertedRows = (array) ($resultado['inserted_rows'] ?? []);
$updatedRows = (array) ($resultado['updated_rows'] ?? []);
$duplicateRows = (array) ($resultado['duplicate_rows'] ?? []);
$pendingRows = (array) ($resultado['pending_rows'] ?? []);
$programaPendingRows = (array) ($resultado['programa_pending_rows'] ?? []);
$conflictRows = (array) ($resultado['conflict_rows'] ?? []);
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
$perPage = 10;

$activeRowsTotalPages = max(1, (int) ceil(count($activeRows) / $perPage));
$activeRowsPage = (int) ($_GET['rows_page'] ?? 1);
$activeRowsPage = min(max(1, $activeRowsPage), $activeRowsTotalPages);
$activeRowsOffset = ($activeRowsPage - 1) * $perPage;
$activeRowsPageItems = array_slice($activeRows, $activeRowsOffset, $perPage);

$pendingCount = (int) ($datosPendientesCount ?? count($pendingRows));
$importId = (string) ($entry['id'] ?? '');
$conflictsManageUrl = import_conflicts_url($importId, true);
?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e(APP_BASE_PATH) ?>/importar" aria-label="Volver a importaciones">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5 "><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">    
        <h2 class="m-0 cursor-default select-none text-2xl font-semibold text-app-text">Detalle de importación</h2>
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
                <p class="m-0 cursor-default text-2xl font-bold text-app-text"><?= e((string) ($resultado['inserted'] ?? 0)) ?></p>
                <p class="m-0 cursor-default text-xs text-app-muted">Nuevos registros</p>
            </div>
        </div>
    </article>
    <article class="<?= e(ui_card_classes()) ?>">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-blue-50 text-blue-600 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('refresh-cw') ?></span>
            <div>
                <p class="m-0 cursor-default text-2xl font-bold text-app-text"><?= e((string) ($resultado['updated'] ?? 0)) ?></p>
                <p class="m-0 cursor-default text-xs text-app-muted">Actualizados</p>
            </div>
        </div>
    </article>
    <article class="<?= e(ui_card_classes()) ?>">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-amber-50 text-amber-600 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('copy') ?></span>
            <div>
                <p class="m-0 cursor-default text-2xl font-bold text-app-text"><?= e((string) ($resultado['duplicates'] ?? 0)) ?></p>
                <p class="m-0 cursor-default text-xs text-app-muted">Duplicados (omitidos)</p>
            </div>
        </div>
    </article>
</section>

<?php
$pendingAprendicesUrl = APP_BASE_PATH . '/aprendices?datos_pendientes=1';
if ($importId !== '') {
    $pendingAprendicesUrl .= '&from=import&import_id=' . urlencode($importId);
}
$actionItems = [];
if ($conflictRows !== []) {
    $actionItems[] = [
        'icon' => 'triangle-alert',
        'iconBg' => 'bg-rose-50 text-rose-600',
        'count' => count($conflictRows),
        'label' => 'aprendices con conflictos sin resolver.',
        'url' => $conflictsManageUrl,
        'cta' => 'Gestionar',
    ];
}
if ($programaPendingRows !== []) {
    $actionItems[] = [
        'icon' => 'link',
        'iconBg' => 'bg-violet-50 text-violet-600',
        'count' => (int) ($pendientesEnlaceCount ?? count($programaPendingRows)),
        'label' => 'aprendices con programas pendientes de enlazar.',
        'url' => APP_BASE_PATH . '/catalogo/programas/pendientes' . import_nav_query_suffix($importId),
        'cta' => 'Gestionar',
    ];
}
if ($pendingCount > 0) {
    $actionItems[] = [
        'icon' => 'circle-alert',
        'iconBg' => 'bg-amber-50 text-amber-600',
        'count' => $pendingCount,
        'label' => 'aprendices con datos pendientes por completar.',
        'url' => $pendingAprendicesUrl,
        'cta' => 'Ver aprendices',
    ];
}
?>
<?php if ($actionItems !== []): ?>
<section class="mt-4 rounded-xl border border-gray-200 bg-white p-6">
    <h3 class="m-0 mb-4 text-sm font-semibold text-gray-900">Acciones pendientes de la importación</h3>
    <ul class="m-0 list-none divide-y divide-gray-100 p-0">
        <?php foreach ($actionItems as $item): ?>
            <li class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg <?= e($item['iconBg']) ?> [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon($item['icon']) ?></span>
                    <p class="m-0 text-sm text-gray-700">
                        <strong class="font-semibold text-gray-900"><?= e((string) $item['count']) ?></strong>
                        <?= e($item['label']) ?>
                    </p>
                </div>
                <a class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 no-underline transition-colors hover:bg-gray-50 hover:text-gray-900"
                   href="<?= e($item['url']) ?>">
                    <?= e($item['cta']) ?>
                    <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('chevron-right') ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<!-- Tablas de importación -->
<section class="mt-4 <?= e(ui_card_classes()) ?>">
    <div class="mb-5 inline-flex rounded-md border border-app-border bg-app-panelSubtle p-1 text-sm">
        <?php foreach ($tabs as $key => $tab): ?>
            <?php $isActive = $activeTab === $key; ?>
            <a
                class="<?= e('inline-flex items-center gap-1.5 rounded px-3 py-1.5 font-medium no-underline transition-colors duration-200 ' . ($isActive ? $tab['active'] : 'text-app-muted hover:bg-app-panel hover:text-app-text')) ?>"
                href="<?= e($detailBaseUrl . '&tab=' . urlencode((string) $key) . '&rows_page=1') ?>"
            >
                <span class="<?= e('inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4 ' . ($isActive ? '' : $tab['iconColor'])) ?>"><?= ui_icon((string) $tab['icon']) ?></span>
                <?= e((string) $tab['label']) ?> (<?= e((string) $tab['count']) ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <div id="tabla-importacion-<?= e($activeTab) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>"><?= e((string) $tabs[$activeTab]['label']) ?></h3>
        <table class="<?= e(ui_table_classes()) ?> table-fixed">
            <colgroup>
                <col class="w-[38%]">
                <col class="w-[16%]">
                <col class="w-[14%]">
                <col class="w-[32%]">
            </colgroup>
            <thead>
            <tr>
                <th class="<?= e(ui_th_classes()) ?> px-1">Nombre</th>
                <th class="<?= e(ui_th_classes()) ?> px-1">Identificación</th>
                <th class="<?= e(ui_th_classes()) ?> px-1">Grupo</th>
                <th class="<?= e(ui_th_classes()) ?> px-1">Programa</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($activeRowsPageItems !== []): ?>
                <?php foreach ($activeRowsPageItems as $row): ?>
                    <tr>
                        <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($row['nombre'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($row['identificacion'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($row['ficha'] ?? '')) ?></td>
                        <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($row['programa'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td class="<?= e(ui_td_classes()) ?> px-4" colspan="4"><?= e((string) $tabs[$activeTab]['empty']) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
        ui_render_pagination(
            $activeRowsPage,
            $activeRowsTotalPages,
            $detailBaseUrl . '&tab=' . urlencode((string) $activeTab) . '&rows_page=%d',
            'Paginación de tabla de importación',
            'tabla-importacion-' . $activeTab
        );
        ?>
    </div>
</section>

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
