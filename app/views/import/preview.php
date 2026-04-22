<?php
$insertedRows = (array) ($resultado['inserted_rows'] ?? []);
$updatedRows = (array) ($resultado['updated_rows'] ?? []);
$duplicateRows = (array) ($resultado['duplicate_rows'] ?? []);
$pendingRows = (array) ($resultado['pending_rows'] ?? []);
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

<?php if ($conflictRows !== []): ?>
    <section class="mt-4 <?= e(ui_card_classes()) ?>">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="<?= e(ui_heading_sm_classes()) ?> mb-1">Conflictos detectados</h3>
                <p class="m-0 text-sm text-app-muted">Se detectaron <?= e((string) count($conflictRows)) ?> registros con cambios sobre datos ya existentes. No se actualizaron automáticamente.</p>
            </div>
            <button
                type="button"
                class="<?= e(ui_button_small_classes()) ?>"
                data-open-conflicts-modal
                aria-haspopup="dialog"
                aria-controls="conflicts-modal"
            >
                Ver conflictos
            </button>
        </div>
    </section>
<?php endif; ?>

<?php if ($conflictRows !== []): ?>
    <div id="conflicts-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="conflicts-modal-title">
        <div class="w-full max-w-4xl rounded-lg bg-app-panel p-4 shadow-xl">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 id="conflicts-modal-title" class="m-0 text-lg font-semibold text-app-text">Conflictos de importación</h3>
                <button type="button" class="<?= e(ui_button_small_classes()) ?>" data-close-conflicts-modal aria-label="Cerrar modal de conflictos">Cerrar</button>
            </div>

            <div class="max-h-[70vh] overflow-auto rounded border border-app-border">
                <table class="<?= e(ui_table_classes()) ?> table-fixed">
                    <colgroup>
                        <col class="w-[25%]">
                        <col class="w-[20%]">
                        <col class="w-[20%]">
                        <col class="w-[35%]">
                    </colgroup>
                    <thead>
                    <tr>
                        <th class="<?= e(ui_th_classes()) ?> px-1">Aprendiz</th>
                        <th class="<?= e(ui_th_classes()) ?> px-1">Campo</th>
                        <th class="<?= e(ui_th_classes()) ?> px-1">Actual</th>
                        <th class="<?= e(ui_th_classes()) ?> px-1">Nuevo archivo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($conflictRows as $conflictRow): ?>
                        <?php
                        $conflicts = (array) ($conflictRow['conflicts'] ?? []);
                        $aprendizLabel = trim((string) ($conflictRow['nombre'] ?? '')) . ' - ' . trim((string) ($conflictRow['identificacion'] ?? ''));
                        ?>
                        <?php foreach ($conflicts as $conflict): ?>
                            <tr>
                                <td class="<?= e(ui_td_classes()) ?> px-4"><?= e($aprendizLabel) ?></td>
                                <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($conflict['field'] ?? '')) ?></td>
                                <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($conflict['actual'] ?? '')) ?></td>
                                <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($conflict['nuevo'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById("conflicts-modal");
            if (!modal) return;
            var openButton = document.querySelector("[data-open-conflicts-modal]");
            var closeButton = modal.querySelector("[data-close-conflicts-modal]");
            var open = function () {
                modal.classList.remove("hidden");
                modal.classList.add("flex");
            };
            var close = function () {
                modal.classList.add("hidden");
                modal.classList.remove("flex");
            };
            if (openButton) openButton.addEventListener("click", open);
            if (closeButton) closeButton.addEventListener("click", close);
            modal.addEventListener("click", function (event) {
                if (event.target === modal) close();
            });
            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape") close();
            });
        })();
    </script>
<?php endif; ?>

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

    <div id="<?= e($activeTab) ?>">
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
                <th class="<?= e(ui_th_classes()) ?> px-1">Ficha</th>
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
        <?php if ($activeRowsTotalPages > 1): ?>
            <nav class="mt-4 flex items-center justify-center gap-2 text-sm" aria-label="Paginación de tabla de importación">
                <?php if ($activeRowsPage > 1): ?>
                    <a class="<?= e(ui_button_icon_classes()) ?>" href="<?= e($detailBaseUrl . '&tab=' . urlencode((string) $activeTab) . '&rows_page=' . urlencode((string) ($activeRowsPage - 1)) . '&pending_page=' . urlencode((string) $pendingPage)) ?>" aria-label="Página anterior">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('arrow') ?></span>
                    </a>
                <?php endif; ?>
                <span class="text-app-muted">Página <?= e((string) $activeRowsPage) ?> de <?= e((string) $activeRowsTotalPages) ?></span>
                <?php if ($activeRowsPage < $activeRowsTotalPages): ?>
                    <a class="<?= e(ui_button_icon_classes()) ?>" href="<?= e($detailBaseUrl . '&tab=' . urlencode((string) $activeTab) . '&rows_page=' . urlencode((string) ($activeRowsPage + 1)) . '&pending_page=' . urlencode((string) $pendingPage)) ?>" aria-label="Página siguiente">
                        <span class="inline-flex h-4 w-4 -scale-x-100 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('arrow') ?></span>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>

<?php if ($pendingRows !== []): ?>
    <section class="mt-4 <?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Pendientes</h3>
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
                $detailUrl = APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId;
                $faltantes = (array) ($pending['faltantes'] ?? []);
                ?>
                <tr class="cursor-pointer" role="link" tabindex="0" onclick="window.location.href='<?= e($detailUrl) ?>'" onkeydown="if(event.key==='Enter' || event.key===' '){event.preventDefault();window.location.href='<?= e($detailUrl) ?>';}">
                    <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($pending['nombre'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?> px-4"><?= e((string) ($pending['identificacion'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?> px-4"><?= e(implode(', ', array_map(static fn ($field): string => (string) $field, $faltantes))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($pendingTotalPages > 1): ?>
            <nav class="mt-4 flex items-center justify-center gap-2 text-sm" aria-label="Paginación de pendientes">
                <?php if ($pendingPage > 1): ?>
                    <a class="<?= e(ui_button_icon_classes()) ?>" href="<?= e($detailBaseUrl . '&tab=' . urlencode((string) $activeTab) . '&rows_page=' . urlencode((string) $activeRowsPage) . '&pending_page=' . urlencode((string) ($pendingPage - 1))) ?>" aria-label="Página anterior">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('arrow') ?></span>
                    </a>
                <?php endif; ?>
                <span class="text-app-muted">Página <?= e((string) $pendingPage) ?> de <?= e((string) $pendingTotalPages) ?></span>
                <?php if ($pendingPage < $pendingTotalPages): ?>
                    <a class="<?= e(ui_button_icon_classes()) ?>" href="<?= e($detailBaseUrl . '&tab=' . urlencode((string) $activeTab) . '&rows_page=' . urlencode((string) $activeRowsPage) . '&pending_page=' . urlencode((string) ($pendingPage + 1))) ?>" aria-label="Página siguiente">
                        <span class="inline-flex h-4 w-4 -scale-x-100 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('arrow') ?></span>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
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
