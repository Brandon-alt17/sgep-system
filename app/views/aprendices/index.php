<?php
declare(strict_types=1);

$aprendices = (array) ($aprendices ?? []);
$fichasOptions = (array) ($fichasOptions ?? []);
$estadosOptions = (array) ($estadosOptions ?? []);
$programasOptions = (array) ($programasOptions ?? []);
$empresasOptions = (array) ($empresasOptions ?? []);
$activeFilters = (array) ($activeFilters ?? []);

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
$returnToCatalogUrl = $source === 'grupos'
    ? APP_BASE_PATH . '/catalogo/grupos'
    : ($source === 'empresas' ? APP_BASE_PATH . '/catalogo/empresas' : '');

partial('components/page_header', [
    'title' => 'Aprendices',
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
            ← <?= $source === 'grupos' ? 'Volver a grupos' : 'Volver a empresas' ?>
        </a>
    <?php endif; ?>
<?php endif; ?>

<section class="<?= e(ui_card_classes()) ?> mb-4">
    <form method="get" action="<?= e(APP_BASE_PATH) ?>/aprendices" class="flex w-full flex-wrap items-center gap-3" data-auto-filter-form data-auto-filter-ajax="true" data-auto-filter-target="#aprendices-table tbody">
        <?php if ($source !== ''): ?>
            <input type="hidden" name="from" value="<?= e($source) ?>">
        <?php endif; ?>

        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                <?= ui_icon('search') ?>
            </span>
            <input
                type="text"
                name="q"
                value="<?= e($initialQ) ?>"
                placeholder="Buscar por nombre o documento"
                class="<?= e(ui_input_classes()) ?> mt-0 min-w-0 flex-1 pl-10 pr-10"
                autocomplete="off"
                data-auto-filter-input
                data-auto-filter-main-input
            >
            <button type="button" class="<?= $initialQ !== '' ? '' : 'hidden' ?> absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-sm text-app-muted hover:bg-app-panelSubtle hover:text-app-text" aria-label="Limpiar búsqueda" data-auto-filter-clear>&times;</button>
        </div>
        <div class="relative w-full min-w-[260px] md:w-80 ">
                <button type="button" id="open-filters-popover" class="<?= e(ui_button_small_classes()) ?> inline-flex w-full items-center justify-center gap-2 bg-white border-app-borderControlStrong text-app-text hover:bg-white hover:text-app-accent" aria-expanded="false" aria-controls="aprendices-filters-popover" data-filter-popover-trigger>
                    <span class="inline-flex h-4 w-4 items-center justify-center" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                            <path d="M3 5h18l-7 8v6l-4-2v-4L3 5z"></path>
                        </svg>
                    </span>
                    Filtros
                </button>

                <div id="aprendices-filters-popover" class="absolute right-0 top-full z-40 mt-2 hidden w-full rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
                    <div class="space-y-3">
                        <label class="<?= e(ui_label_classes()) ?>">
                            Ficha
                            <span class="<?= e(ui_select_wrapper_classes()) ?>">
                                <select name="ficha" class="<?= e(ui_select_classes()) ?>">
                                    <option value="">Todas las fichas</option>
                                    <?php foreach ($fichasOptions as $f): ?>
                                        <?php if ($f === null || $f === '') { continue; } ?>
                                        <option value="<?= e((string) $f) ?>" <?= (string) $f === $initialFicha ? 'selected' : '' ?>><?= e((string) $f) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                                </span>
                            </span>
                        </label>

                        <label class="<?= e(ui_label_classes()) ?>">
                            Empresa
                            <span class="<?= e(ui_select_wrapper_classes()) ?>">
                                <select name="empresa_id" class="<?= e(ui_select_classes()) ?>">
                                    <option value="">Todas las empresas</option>
                                    <?php foreach ($empresasOptions as $empresa): ?>
                                        <?php $eid = (int) ($empresa['id'] ?? 0); if ($eid <= 0) { continue; } ?>
                                        <option value="<?= $eid ?>" <?= $initialEmpresaId === $eid ? 'selected' : '' ?>><?= e((string) ($empresa['nombre'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                                </span>
                            </span>
                        </label>

                        <label class="<?= e(ui_label_classes()) ?>">
                            Programa
                            <span class="<?= e(ui_select_wrapper_classes()) ?>">
                                <select name="programa_id" class="<?= e(ui_select_classes()) ?>">
                                    <option value="">Todos los programas</option>
                                    <?php foreach ($programasOptions as $programa): ?>
                                        <?php $pid = (int) ($programa['id'] ?? 0); if ($pid <= 0) { continue; } ?>
                                        <option value="<?= $pid ?>" <?= $initialProgramaId === $pid ? 'selected' : '' ?>><?= e((string) ($programa['nombre'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                                </span>
                            </span>
                        </label>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button type="submit" class="<?= e(ui_button_primary_classes()) ?> text-white">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>
        <div class="<?= e(ui_select_wrapper_classes()) ?> w-full min-w-[220px] shrink-0 md:w-64">
            <select name="estado" class="<?= e(ui_select_classes()) ?>" data-auto-filter-change>
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

<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="aprendices-table" class="<?= e(ui_table_classes()) ?>">
            <thead class="border-b bg-app-panelSubtle">
                <tr>
                    <th class="<?= e(ui_th_classes()) ?>">Nombre completo</th>
                    <th class="<?= e(ui_th_classes()) ?>">Documento</th>
                    <th class="<?= e(ui_th_classes()) ?>">Empresa</th>
                    <th class="<?= e(ui_th_classes()) ?>">Grupo</th>
                    <th class="<?= e(ui_th_classes()) ?>">Estado</th>
                    <th class="<?= e(ui_th_classes()) ?>">Última visita</th>
                    <th class="<?= e(ui_th_classes()) ?>"></th>
                </tr>
            </thead>
            <tbody id="table-body">
            <?php partial('aprendices/_rows', ['aprendices' => $aprendices, 'activeFilters' => $activeFilters]); ?>
            </tbody>
        </table>
    </div>
</section>

<?php partial('components/filter_popover_script'); ?>
