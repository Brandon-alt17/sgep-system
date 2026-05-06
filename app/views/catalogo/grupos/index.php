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
$programaComboboxOptions = [];
foreach ($programas as $p) {
    $pid = (int) ($p['id'] ?? 0);
    if ($pid <= 0) {
        continue;
    }
    $nombre = trim((string) ($p['nombre'] ?? 'Programa #' . $pid));
    $programaComboboxOptions[] = [
        'value' => (string) $pid,
        'label' => $nombre,
        'search' => $nombre,
    ];
}

partial('components/ui');
$tdClasses = str_replace('h-[50px] ', '', ui_td_classes());

partial('components/page_header', [
    'title' => 'Catálogo - Grupos',
    'subtitle' => 'Fichas de formación agrupadas con el programa asociado y número de aprendices.',
]);
?>

<section class="<?= e(ui_card_classes()) ?> mb-4">
    <form
        method="get"
        action="<?= e(APP_BASE_PATH) ?>/catalogo/grupos"
        class="flex w-full flex-wrap items-center gap-3"
        data-remote-table-filter-form
        data-remote-table-filter-target="#tabla-catalogo-grupos tbody"
        data-remote-table-filter-debounce="350"
    >
        <div class="relative w-full min-w-0 md:flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                <?= ui_icon('search') ?>
            </span>
            <input
                type="text"
                name="q"
                value="<?= e($q) ?>"
                placeholder="Buscar por ficha o programa"
                class="<?= e(ui_input_classes()) ?> mt-0 block w-full min-w-0 pl-10 pr-10"
                data-remote-table-filter-q
            >
            <button
                type="button"
                class="<?= $q !== '' ? '' : 'hidden' ?> absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-sm text-app-muted hover:bg-app-panelSubtle hover:text-app-text"
                aria-label="Limpiar búsqueda"
                data-remote-table-filter-clear
            >&times;</button>
        </div>

        <div class="w-full min-w-[200px] shrink-0 md:w-72">
            <?php partial('components/combobox', [
                'name' => 'programa_id',
                'value' => $programaId > 0 ? (string) $programaId : '',
                'placeholder' => 'Todos los programas',
                'options' => $programaComboboxOptions,
                'valueInputAttrs' => ['data-remote-table-filter-change' => true],
            ]); ?>
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
