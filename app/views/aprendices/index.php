<?php
declare(strict_types=1);

$aprendices = (array) ($aprendices ?? []);
$fichasOptions = (array) ($fichasOptions ?? []);
$estadosOptions = (array) ($estadosOptions ?? []);
$programasOptions = (array) ($programasOptions ?? []);
$empresasOptions = (array) ($empresasOptions ?? []);
$activeFilters = (array) ($activeFilters ?? []);
$currentPage = max(1, (int) ($currentPage ?? ($activeFilters['page'] ?? 1)));
$totalPages = max(1, (int) ($totalPages ?? 1));
$totalItems = max(0, (int) ($totalItems ?? count($aprendices)));

$initialFicha = trim((string) ($activeFilters['ficha'] ?? ($initialFicha ?? '')));
$initialEstado = trim((string) ($activeFilters['estado'] ?? ($initialEstado ?? '')));
$initialQ = trim((string) ($activeFilters['q'] ?? ($initialQ ?? '')));
$initialEmpresaId = (int) ($activeFilters['empresa_id'] ?? 0);
$initialProgramaId = (int) ($activeFilters['programa_id'] ?? 0);
$source = trim((string) ($activeFilters['from'] ?? ''));

$activeQuery = array_filter([
    'q' => $initialQ,
    'ficha' => $initialFicha,
    'estado' => $initialEstado,
    'empresa_id' => $initialEmpresaId > 0 ? (string) $initialEmpresaId : '',
    'programa_id' => $initialProgramaId > 0 ? (string) $initialProgramaId : '',
    'from' => in_array($source, ['grupos', 'empresas'], true) ? $source : '',
], static fn ($v): bool => (string) $v !== '');

$profileFiltersQuery = $activeQuery === [] ? '' : '&' . http_build_query($activeQuery);
$clearFiltersUrl = APP_BASE_PATH . '/aprendices';
$returnToEmpresaUrl = '';
if ($source === 'empresas' && $initialEmpresaId > 0) {
    $returnToEmpresaUrl = APP_BASE_PATH . '/catalogo/empresas/ver?id=' . $initialEmpresaId;
}
$returnToCatalogUrl = match ($source) {
    'grupos' => APP_BASE_PATH . '/catalogo/grupos',
    'empresas' => ($returnToEmpresaUrl !== '' ? $returnToEmpresaUrl : APP_BASE_PATH . '/catalogo/empresas'),
    default => '',
};

$fichaComboboxOptions = [];
foreach ($fichasOptions as $f) {
    if ($f === null || $f === '') {
        continue;
    }
    $fichaValue = (string) $f;
    $fichaComboboxOptions[] = [
        'value' => $fichaValue,
        'label' => $fichaValue,
        'search' => $fichaValue,
    ];
}

$empresaComboboxOptions = [];
foreach ($empresasOptions as $empresa) {
    $eid = (int) ($empresa['id'] ?? 0);
    if ($eid <= 0) {
        continue;
    }
    $empresaNombre = trim((string) ($empresa['nombre'] ?? 'Empresa #' . $eid));
    $empresaComboboxOptions[] = [
        'value' => (string) $eid,
        'label' => $empresaNombre,
        'search' => $empresaNombre,
    ];
}

$programaComboboxOptions = [];
foreach ($programasOptions as $programa) {
    $pid = (int) ($programa['id'] ?? 0);
    if ($pid <= 0) {
        continue;
    }
    $programaNombre = trim((string) ($programa['nombre'] ?? 'Programa #' . $pid));
    $programaComboboxOptions[] = [
        'value' => (string) $pid,
        'label' => $programaNombre,
        'search' => $programaNombre,
    ];
}

$estadoComboboxOptions = [];
foreach ($estadosOptions as $st) {
    if ($st === null || $st === '') {
        continue;
    }
    $estadoValue = (string) $st;
    $estadoComboboxOptions[] = [
        'value' => $estadoValue,
        'label' => $estadoValue,
        'search' => $estadoValue,
    ];
}

partial('components/page_header', [
    'title' => 'Aprendices',
    'subtitle' => 'Administra los aprendices registrados y consulta su estado, empresa y documentos de seguimiento.',
    'actions' => '
        <a class="' . e(ui_button_primary_classes()) . ' inline-flex items-center gap-2 text-white" href="' . e(APP_BASE_PATH) . '/aprendices/create">
            <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-4 [&_svg]:w-4" aria-hidden="true">' . ui_icon('user-plus') . '</span>
            Nuevo aprendiz
        </a>
    ',
]);
?>

<?php if ($source === 'grupos' || $source === 'empresas'): ?>
    <?php if ($returnToCatalogUrl !== ''): ?>
        <a href="<?= e($returnToCatalogUrl) ?>" class="mb-4 inline-block text-sm text-app-link hover:underline">
            ← <?= $source === 'grupos'
                ? 'Volver a grupos'
                : ($initialEmpresaId > 0 ? 'Volver a la empresa' : 'Volver a empresas') ?>
        </a>
    <?php endif; ?>
<?php endif; ?>

<section class="<?= e(ui_card_classes()) ?> mb-4">
    <form
        method="get"
        action="<?= e(APP_BASE_PATH) ?>/aprendices"
        class="flex w-full flex-wrap items-center gap-3"
        data-remote-table-filter-form
        data-remote-table-filter-target="#aprendices-table tbody"
        data-remote-table-filter-debounce="350"
    >
        <?php if ($source !== ''): ?>
            <input type="hidden" name="from" value="<?= e($source) ?>">
        <?php endif; ?>

        <div class="relative w-full min-w-0 md:flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                <?= ui_icon('search') ?>
            </span>
            <input
                type="text"
                name="q"
                value="<?= e($initialQ) ?>"
                placeholder="Buscar por nombre o documento"
                class="<?= e(ui_input_classes()) ?> mt-0 block w-full min-w-0 pl-10 pr-10"
                autocomplete="off"
                data-remote-table-filter-q
            >
            <button type="button" class="<?= $initialQ !== '' ? '' : 'hidden' ?> absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-sm text-app-muted hover:bg-app-panelSubtle hover:text-app-text" aria-label="Limpiar búsqueda" data-remote-table-filter-clear>&times;</button>
        </div>
        <div class="relative w-full min-w-[220px] md:w-72 md:flex-none">
                <button type="button" id="open-filters-popover" class="<?= e(ui_button_small_classes()) ?> inline-flex w-full items-center justify-center gap-2 bg-white border-app-borderControlStrong text-app-muted hover:bg-white hover:text-app-text" aria-expanded="false" aria-controls="aprendices-filters-popover" data-filter-popover-trigger>
                    <span class="inline-flex h-4 w-4 items-center justify-center text-app-muted" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                            <path d="M3 5h18l-7 8v6l-4-2v-4L3 5z"></path>
                        </svg>
                    </span>
                    Filtros
                </button>

                <div id="aprendices-filters-popover" class="absolute right-0 top-full z-40 mt-2 hidden w-full rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
                    <div class="space-y-3">
                        <div class="<?= e(ui_label_classes()) ?>">
                            <span>Ficha</span>
                            <?php partial('components/combobox', [
                                'name' => 'ficha',
                                'value' => $initialFicha,
                                'placeholder' => 'Todas las fichas',
                                'options' => $fichaComboboxOptions,
                                'valueInputAttrs' => ['data-remote-table-filter-change' => true],
                            ]); ?>
                        </div>

                        <div class="<?= e(ui_label_classes()) ?>">
                            <span>Empresa</span>
                            <?php partial('components/combobox', [
                                'name' => 'empresa_id',
                                'value' => $initialEmpresaId > 0 ? (string) $initialEmpresaId : '',
                                'placeholder' => 'Todas las empresas',
                                'options' => $empresaComboboxOptions,
                                'valueInputAttrs' => ['data-remote-table-filter-change' => true],
                            ]); ?>
                        </div>

                        <div class="<?= e(ui_label_classes()) ?>">
                            <span>Programa</span>
                            <?php partial('components/combobox', [
                                'name' => 'programa_id',
                                'value' => $initialProgramaId > 0 ? (string) $initialProgramaId : '',
                                'placeholder' => 'Todos los programas',
                                'options' => $programaComboboxOptions,
                                'valueInputAttrs' => ['data-remote-table-filter-change' => true],
                            ]); ?>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button type="submit" class="<?= e(ui_button_primary_classes()) ?> text-white">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>
        <div class="<?= e(ui_select_wrapper_classes()) ?> w-full min-w-[220px] shrink-0 md:w-64">
            <select name="estado" class="<?= e(ui_select_classes()) ?>" data-remote-table-filter-change aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <?php foreach ($estadosOptions as $st): ?>
                    <?php if ($st === null || $st === '') { continue; } ?>
                    <option value="<?= e((string) $st) ?>" <?= (string) $st === $initialEstado ? 'selected' : '' ?>><?= e((string) $st) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
            </span>
        </div>
        <a class="<?= e(ui_button_primary_classes()) ?> inline-flex items-center gap-2 text-white" href="<?= e($clearFiltersUrl) ?>">
                <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-4 [&_svg]:w-4" aria-hidden="true"><?= ui_icon('brush-cleaning') ?></span>
                Limpiar filtros
        </a>
    </form>
</section>

<section class="<?= e(ui_card_classes()) ?> !p-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="aprendices-table" class="<?= e(ui_table_classes()) ?>">
            <thead class="border-b bg-app-panelSubtle">
                <tr>
                    <th class="<?= e(ui_th_classes()) ?>">Nombre completo</th>
                    <th class="<?= e(ui_th_classes()) ?>">Documento</th>
                    <th class="<?= e(ui_th_classes()) ?>">Empresa</th>
                    <th class="<?= e(ui_th_classes()) ?>">Grupo</th>
                    <th class="<?= e(ui_th_classes()) ?>">Estado</th>
                    <th class="<?= e(ui_th_classes()) ?>">Proxima visita</th>
                    <th class="<?= e(ui_th_classes()) ?>"></th>
                </tr>
            </thead>
            <tbody id="table-body">
            <?php partial('aprendices/_rows', ['aprendices' => $aprendices, 'activeFilters' => $activeFilters]); ?>
            </tbody>
        </table>
    </div>
</section>

<?php
$paginationQuery = $activeQuery;
unset($paginationQuery['page']);
$paginationBase = APP_BASE_PATH . '/aprendices';
$paginationUrlPattern = $paginationBase . '?page=%d';
if ($paginationQuery !== []) {
    $paginationUrlPattern = $paginationBase . '?' . http_build_query($paginationQuery) . '&page=%d';
}
ui_render_pagination(
    $currentPage,
    $totalPages,
    $paginationUrlPattern,
    'Paginación de aprendices',
    'aprendices-table'
);
?>

<?php partial('components/filter_popover_script'); ?>
