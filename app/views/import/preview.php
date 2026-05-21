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

$pendingTotalPages = max(1, (int) ceil(count($pendingRows) / $perPage));
$pendingPage = (int) ($_GET['pending_page'] ?? 1);
$pendingPage = min(max(1, $pendingPage), $pendingTotalPages);
$pendingOffset = ($pendingPage - 1) * $perPage;
$pendingPageItems = array_slice($pendingRows, $pendingOffset, $perPage);

$pendingFieldLabels = (array) require base_path('config/import_field_labels.php');
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

<?php if ($conflictRows !== []): ?>
    <?php partial('components/alerts/pending_link', [
        'count' => count($conflictRows),
        'manageUrl' => $conflictsManageUrl,
        'subjectPlural' => 'aprendices con conflictos',
        'messageTail' => 'sin resolver en esta importación.',
        'ctaText' => 'Gestionar ahora',
        'extraClasses' => 'mt-4',
    ]); ?>
<?php endif; ?>

<?php if ($programaPendingRows !== []): ?>
    <?php partial('components/alerts/pending_link', [
        'count' => (int) ($pendientesEnlaceCount ?? count($programaPendingRows)),
        'manageUrl' => APP_BASE_PATH . '/catalogo/programas/pendientes' . import_nav_query_suffix($importId),
        'subjectPlural' => 'aprendices con programas',
        'messageTail' => 'pendientes de enlazar.',
        'ctaText' => 'Gestionar ahora',
        'extraClasses' => 'mt-4',
    ]); ?>
<?php endif; ?>

<!-- Tablas de importación -->
<section class="mt-4 <?= e(ui_card_classes()) ?>">
    <div class="mb-5 inline-flex rounded-md border border-app-border bg-app-panelSubtle p-1 text-sm">
        <?php foreach ($tabs as $key => $tab): ?>
            <?php $isActive = $activeTab === $key; ?>
            <a
                class="<?= e('inline-flex items-center gap-1.5 rounded px-3 py-1.5 font-medium no-underline transition-colors duration-200 ' . ($isActive ? $tab['active'] : 'text-app-muted hover:bg-app-panel hover:text-app-text')) ?>"
                href="<?= e($detailBaseUrl . '&tab=' . urlencode((string) $key) . '&rows_page=1&pending_page=' . urlencode((string) $pendingPage)) ?>"
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
            $detailBaseUrl . '&tab=' . urlencode((string) $activeTab) . '&rows_page=%d&pending_page=' . urlencode((string) $pendingPage),
            'Paginación de tabla de importación',
            'tabla-importacion-' . $activeTab
        );
        ?>
    </div>
</section>

<!-- Pendientes -->
<?php if ($pendingRows !== []): ?>
    <section id="tabla-pendientes" class="mt-4 <?= e(ui_card_classes()) ?>">
        <div class="mb-2 flex items-center gap-2">
            <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center text-amber-600 leading-none [&_svg]:h-4 [&_svg]:w-4" style="transform: translateY(-1px);"><?= ui_icon('clock') ?></span>
            <h3 class="<?= e(ui_heading_sm_classes()) ?> m-0 text-amber-600">Pendientes</h3>
        </div>
        <p class="mb-3 mt-0 text-sm text-app-muted">Aprendices con datos faltantes para completar el perfil en esta importación.</p>
        <table class="<?= e(ui_table_classes()) ?> table-fixed">
            <colgroup>
                <col class="w-[34%]">
                <col class="w-[18%]">
                <col class="w-[48%]">
            </colgroup>
            <thead>
            <tr>
                <th class="<?= e(ui_th_classes()) ?> px-1">Nombre</th>
                <th class="<?= e(ui_th_classes()) ?> px-1">Identificación</th>
                <th class="<?= e(ui_th_classes()) ?> px-1">Campos faltantes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingPageItems as $pending): ?>
                <?php
                $aprendizId = (int) ($pending['aprendiz_id'] ?? 0);
                $faltantes = (array) ($pending['faltantes'] ?? []);
                $pendingCount = count($faltantes);
                $badgeClasses = 'bg-amber-100 text-amber-700 border-amber-200';
                ?>
                <tr
                    class="cursor-pointer hover:bg-app-panelSubtle"
                    data-modal-open="<?= e((string) $aprendizId) ?>"
                    tabindex="0"
                    role="button"
                    onkeydown="if(event.key==='Enter' || event.key===' '){event.preventDefault();openModal('<?= e((string) $aprendizId) ?>');}"
                >
                    <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($pending['nombre'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($pending['identificacion'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?> px-4">
                        <div class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold <?= e($badgeClasses) ?>">
                                <?= e((string) $pendingCount) ?> pendientes
                            </span>
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-app-border text-xs font-bold text-app-muted">
                                <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('circle-alert') ?></span>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        ui_render_pagination(
            $pendingPage,
            $pendingTotalPages,
            $detailBaseUrl . '&tab=' . urlencode((string) $activeTab) . '&rows_page=' . urlencode((string) $activeRowsPage) . '&pending_page=%d',
            'Paginación de pendientes',
            'tabla-pendientes'
        );
        ?>
    </section>

    <?php foreach ($pendingPageItems as $pending): ?>
        <?php
        $aprendizId = (int) ($pending['aprendiz_id'] ?? 0);
        $faltantes = (array) ($pending['faltantes'] ?? []);
        $editUrl = APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId
            . ($importId !== '' ? '&from=import&import_id=' . urlencode($importId) : '');
        ?>
        <div id="modal-<?= e((string) $aprendizId) ?>" class="modal-overlay hidden" data-modal-overlay="<?= e((string) $aprendizId) ?>">
            <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="pending-modal-title-<?= e((string) $aprendizId) ?>">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 id="pending-modal-title-<?= e((string) $aprendizId) ?>" class="m-0 text-lg font-semibold text-app-text">Campos pendientes — <?= e((string) ($pending['nombre'] ?? 'Aprendiz')) ?></h3>
                    <button type="button" class="<?= e(ui_button_icon_classes()) ?>" data-modal-close="<?= e((string) $aprendizId) ?>" aria-label="Cerrar modal">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                    </button>
                </div>
                <div class="max-h-[55vh] overflow-auto rounded-md border border-app-border">
                    <ul class="m-0 divide-y divide-app-border p-0">
                        <?php foreach ($faltantes as $campo): ?>
                            <?php $campoKey = (string) $campo; ?>
                            <li class="flex items-center gap-2 px-4 py-2 text-sm text-app-text">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-[11px] font-bold text-amber-700">!</span>
                                <span><?= e((string) ($pendingFieldLabels[$campoKey] ?? $campoKey)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <a class="<?= e(ui_button_small_classes()) ?>" href="<?= e($editUrl) ?>">Completar campos</a>
                    <button type="button" class="<?= e(ui_button_small_classes()) ?>" data-modal-close="<?= e((string) $aprendizId) ?>">Cerrar</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
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
