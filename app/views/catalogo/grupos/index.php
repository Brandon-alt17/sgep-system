<?php
$grupos = (array) ($grupos ?? []);
$filters = (array) ($filters ?? []);
$programas = (array) ($programas ?? []);
$q = trim((string) ($filters['q'] ?? ''));
$programaId = (int) ($filters['programa_id'] ?? 0);
$toastKey = trim((string) ($_GET['toast'] ?? ''));
$toastMessage = match ($toastKey) {
    default => '',
};
$totalRows = count($grupos);

partial('components/ui');
$tdClasses = str_replace('h-[50px] ', '', ui_td_classes());

partial('components/page_header', [
    'title' => 'Catálogo - Grupos',
    'subtitle' => 'Fichas de formación agrupadas con el programa asociado y número de aprendices.',
]);
?>

<section class="<?= e(ui_card_classes()) ?> mb-4">
    <form method="get" action="<?= e(APP_BASE_PATH) ?>/catalogo/grupos" class="flex w-full flex-wrap items-center gap-3" data-auto-filter-form data-auto-filter-ajax="true" data-auto-filter-target="#tabla-catalogo-grupos tbody">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                <?= ui_icon('search') ?>
            </span>
            <input
                type="text"
                name="q"
                value="<?= e($q) ?>"
                placeholder="Buscar por ficha o programa"
                class="<?= e(ui_input_classes()) ?> mt-0 min-w-0 flex-1 pl-10 pr-10"
                data-auto-filter-input
                data-auto-filter-main-input
            >
            <button
                type="button"
                class="<?= $q !== '' ? '' : 'hidden' ?> absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-sm text-app-muted hover:bg-app-panelSubtle hover:text-app-text"
                aria-label="Limpiar búsqueda"
                data-auto-filter-clear
            >&times;</button>
        </div>

        <div class="<?= e(ui_select_wrapper_classes()) ?> w-full min-w-[200px] shrink-0 md:w-72">
            <select name="programa_id" class="<?= e(ui_select_classes()) ?>" data-auto-filter-change>
                <option value="0">Todos los programas</option>
                <?php foreach ($programas as $p): ?>
                    <?php $pid = (int) ($p['id'] ?? 0); ?>
                    <option value="<?= $pid ?>" <?= $programaId === $pid ? 'selected' : '' ?>><?= e((string) ($p['nombre'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </div>
    </form>
</section>

<?php partial('components/toast', ['message' => $toastMessage]); ?>

<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden">
    <table id="tabla-catalogo-grupos" class="<?= e(ui_table_in_card_classes()) ?> !m-0 !p-0 table-fixed">
        <colgroup>
            <col class="w-[22%]">
            <col class="w-[40%]">
            <col class="w-[14%]">
            <col class="w-[24%]">
        </colgroup>
        <thead>
        <tr class="bg-app-panelSubtle">
            <th class="<?= e(ui_th_classes()) ?> px-4 pl-6">Ficha</th>
            <th class="<?= e(ui_th_classes()) ?> px-4">Programa</th>
            <th class="<?= e(ui_th_classes()) ?> px-4">Aprendices</th>
            <th class="<?= e(ui_th_classes()) ?> px-4 pr-6">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php partial('catalogo/grupos/_rows', ['grupos' => $grupos, 'tdClasses' => $tdClasses]); ?>
        </tbody>
    </table>
</section>
