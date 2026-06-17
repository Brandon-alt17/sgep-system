<?php
declare(strict_types=1);

$entry = (array) ($entry ?? []);
$conflictRows = (array) ($conflictRows ?? []);
$conflictRowsAll = (array) ($conflictRowsAll ?? $conflictRows);
$pendingFieldLabels = (array) ($pendingFieldLabels ?? []);
$importReturnUrl = trim((string) ($importReturnUrl ?? ''));
$aprendicesUrl = (string) ($aprendicesUrl ?? APP_BASE_PATH . '/aprendices');
$importId = (string) ($entry['id'] ?? '');
$conflictsCount = (int) ($conflictsCount ?? count($conflictRowsAll));
$conflictsFilteredCount = (int) ($conflictsFilteredCount ?? count($conflictRows));
$searchQ = trim((string) ($searchQ ?? ''));
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$conflictsPaginationUrl = (string) ($conflictsPaginationUrl ?? '');
$conflictsTdClasses = str_replace('h-[50px] ', '', ui_td_classes());
$conflictsAlertUrgente = $conflictsCount > 5;
$conflictsAlertSection = trim('mt-4 ' . ui_pending_enlace_alert_section_classes($conflictsCount));
$conflictsAlertFg = $conflictsAlertUrgente ? 'text-rose-700' : 'text-app-warningText';
$conflictsAlertBadge = $conflictsAlertUrgente
    ? 'rounded-full bg-rose-200 px-2 py-0.5 text-xs font-bold text-rose-900'
    : 'rounded-full border border-app-borderWarning bg-amber-100 px-2 py-0.5 text-xs font-bold text-app-warningText';
?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 shrink-0 self-start" href="<?= e($aprendicesUrl) ?>" aria-label="Volver a aprendices">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex min-w-0 flex-1 flex-col gap-2 pl-4">
        <h2 class="m-0 cursor-default text-2xl font-semibold text-app-text">Conflictos de importación</h2>
        <p class="m-0 cursor-default text-sm text-app-muted">Elija por aprendiz si conserva el dato actual o el del archivo importado.</p>
        <?php if ($importReturnUrl !== ''): ?>
            <?php partial('components/back_link_text', [
                'url' => $importReturnUrl,
                'label' => 'Volver a importación',
                'extraClasses' => 'mb-0 mt-1',
            ]); ?>
        <?php endif; ?>
    </div>
</section>

<?php if ($conflictsCount > 0): ?>
    <section class="<?= e($conflictsAlertSection) ?>" id="import-conflicts-alert">
        <p class="m-0 flex cursor-default items-center gap-2 text-sm font-semibold <?= e($conflictsAlertFg) ?>">
            <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4 <?= e($conflictsAlertFg) ?>"><?= ui_icon('circle-alert') ?></span>
            Hay <span id="import-conflicts-count" class="<?= e($conflictsAlertBadge) ?>"><?= e((string) $conflictsCount) ?></span> aprendices con conflictos sin resolver.
        </p>
    </section>
<?php endif; ?>

<?php partial('components/toast', [
    'message' => '',
    'variant' => 'success',
    'toastRootId' => 'import-conflictos-toast-root',
]); ?>
<script src="<?= e(APP_BASE_PATH) ?>/js/ui-toast.js"></script>

<?php if ($conflictsCount === 0): ?>
    <section class="mt-4 <?= e(ui_card_classes()) ?> p-6">
        <p class="m-0 text-sm text-app-muted">No hay conflictos pendientes en esta importación.</p>
        <a class="<?= e(ui_button_small_classes()) ?> mt-4 inline-flex" href="<?= e($aprendicesUrl) ?>">Volver a aprendices</a>
    </section>
<?php else: ?>
    <section
        id="import-conflicts-root"
        class="mt-4"
        data-import-conflicts-root
        data-import-id="<?= e($importId) ?>"
    >
        <section class="<?= e(ui_card_classes()) ?> mb-4 p-6">
            <form
                method="get"
                action="<?= e(APP_BASE_PATH) ?>/importar/conflictos"
                class="w-full"
                data-remote-table-filter-form
                data-remote-table-filter-target="#import-conflicts-tbody"
                data-remote-table-filter-pagination="#import-conflicts-pagination"
                data-remote-table-filter-debounce="350"
            >
                <input type="hidden" name="id" value="<?= e($importId) ?>">
                <?php if ($importReturnUrl !== ''): ?>
                    <input type="hidden" name="from" value="import">
                <?php endif; ?>
                <div class="relative min-w-0 w-full">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                        <?= ui_icon('search') ?>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="<?= e($searchQ) ?>"
                        placeholder="Buscar por aprendiz o documento"
                        aria-label="Buscar conflictos en toda la importación"
                        autocomplete="off"
                        class="<?= e(ui_input_classes()) ?> mt-0 w-full bg-white pl-10 pr-10"
                        data-remote-table-filter-q
                    >
                    <button
                        type="button"
                        class="<?= $searchQ !== '' ? '' : 'hidden' ?> absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-sm text-app-muted hover:bg-app-panelSubtle hover:text-app-text"
                        aria-label="Limpiar búsqueda"
                        data-remote-table-filter-clear
                    >&times;</button>
                </div>
            </form>
        </section>

        <section class="<?= e(ui_card_classes()) ?> mb-2 !p-0" id="tabla-conflictos">
            <div class="overflow-x-auto rounded-[10px]">
                <table class="<?= e(ui_table_in_card_classes()) ?> table-fixed min-w-[720px] !m-0 !p-0">
                    <colgroup>
                        <col class="w-[32%] min-w-[180px]">
                        <col class="w-[18%]">
                        <col class="w-[22%]">
                        <col class="w-[28%]">
                    </colgroup>
                    <thead>
                    <tr class="bg-app-panelSubtle">
                        <th class="<?= e(ui_th_classes()) ?> px-5 pl-6">Aprendiz</th>
                        <th class="<?= e(ui_th_classes()) ?> px-5">Documento</th>
                        <th class="<?= e(ui_th_classes()) ?> px-5">Conflictos</th>
                        <th class="<?= e(ui_th_classes()) ?> px-5 pr-6 text-left">Acción</th>
                    </tr>
                    </thead>
                    <tbody id="import-conflicts-tbody">
                        <?php partial('import/_conflicts_rows', [
                            'conflictRows' => $conflictRows,
                            'tdClasses' => $conflictsTdClasses,
                            'emptyMessage' => $searchQ !== ''
                                ? 'Sin coincidencias para esta búsqueda.'
                                : 'No hay aprendices con conflictos pendientes.',
                        ]); ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div id="import-conflicts-pagination">
            <?php if ($conflictsPaginationUrl !== '' && $totalPages > 1): ?>
                <?php
                ui_render_pagination(
                    $currentPage,
                    $totalPages,
                    $conflictsPaginationUrl,
                    'Paginación de conflictos de importación',
                    'tabla-conflictos'
                );
                ?>
            <?php endif; ?>
        </div>

        <?php partial('import/_conflict_modals', [
            'conflictRows' => $conflictRowsAll,
            'pendingFieldLabels' => $pendingFieldLabels,
        ]); ?>

        <section id="import-conflicts-all-done" class="<?= e(ui_card_classes()) ?> mb-6 hidden p-6">
            <p class="m-0 text-sm text-app-muted">Todos los conflictos de esta importación quedaron resueltos.</p>
            <a class="<?= e(ui_button_small_classes()) ?> mt-4 inline-flex" href="<?= e($aprendicesUrl) ?>">Volver a aprendices</a>
        </section>
    </section>
<?php endif; ?>
