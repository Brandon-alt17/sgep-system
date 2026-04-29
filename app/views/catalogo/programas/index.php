<?php
$programas = (array) ($programas ?? []);
$filters = (array) ($filters ?? []);
$q = trim((string) ($filters['q'] ?? ''));
$nivel = trim((string) ($filters['nivel'] ?? ''));
$totalRows = count($programas);
$pendientesCount = (int) ($pendientesCount ?? 0);

// Reutiliza estilos base sin altura fija para permitir crecimiento de fila con multilinea.
$tdClasses = str_replace('h-[50px] ', '', ui_td_classes());

ob_start();
?>
<a class="<?= e(ui_button_primary_classes()) ?> inline-flex items-center gap-2 text-app-textOnBrand" href="<?= e(APP_BASE_PATH) ?>/catalogo/programas/importar">
    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('file-up') ?></span>
    Importar PDF del programa
</a>
<?php
$headerActions = (string) ob_get_clean();

partial('components/page_header', [
    'title' => 'Catálogo - Programas de formación',
    'subtitle' => 'Listado de programas disponibles para asociar aprendices y su gestión.',
    'actions' => $headerActions,
]);
?>

<?php partial('components/alerts/pending_link', [
    'count' => $pendientesCount,
    'manageUrl' => APP_BASE_PATH . '/catalogo/programas/pendientes',
    'subjectPlural' => 'aprendices con programas',
    'messageTail' => 'pendientes de enlazar.',
    'ctaText' => 'Gestionar ahora',
    'extraClasses' => 'mb-4',
]); ?>

<!-- Buscador y filtros -->
<section class="<?= e(ui_card_classes()) ?> mb-4">
   

    <form method="get" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas" class="flex w-full items-center gap-3" data-auto-filter-form data-auto-filter-ajax="true" data-auto-filter-target="#tabla-catalogo-programas tbody">
        <div class="relative flex-1 min-w-0">
            <input
                type="text"
                name="q"
                value="<?= e($q) ?>"
                placeholder="Buscar por código o nombre"
                class="<?= e(ui_input_classes()) ?> mt-0 flex-1 min-w-0 pr-10"
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

        <div class="<?= e(ui_select_wrapper_classes()) ?> w-64 shrink-0">
            <select name="nivel" class="<?= e(ui_select_classes()) ?>" data-auto-filter-change>
                <option value="">Todos los niveles</option>
                <option value="Técnico" <?= $nivel === 'Técnico' ? 'selected' : '' ?>>Técnico</option>
                <option value="Tecnólogo" <?= $nivel === 'Tecnólogo' ? 'selected' : '' ?>>Tecnólogo</option>
                <option value="Auxiliar" <?= $nivel === 'Auxiliar' ? 'selected' : '' ?>>Auxiliar</option>
                <option value="Operario" <?= $nivel === 'Operario' ? 'selected' : '' ?>>Operario</option>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </div>

    </form>
</section>

<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden">
    <table id="tabla-catalogo-programas" class="<?= e(ui_table_in_card_classes()) ?> !m-0 !p-0 table-fixed">
        <colgroup>
            <col class="w-[16%]">
            <col class="w-[46%]">
            <col class="w-[18%]">
            <col class="w-[20%]">
        </colgroup>
        <thead>
        <tr class="bg-app-panelSubtle">
            <th class="<?= e(ui_th_classes()) ?> px-4 pl-6">Código</th>
            <th class="<?= e(ui_th_classes()) ?> px-4">Nombre</th>
            <th class="<?= e(ui_th_classes()) ?> px-4">Nivel</th>
            <th class="<?= e(ui_th_classes()) ?> px-4 pr-6">Duración</th>
        </tr>
        </thead>
        <tbody>
        <?php partial('catalogo/programas/_rows', ['programas' => $programas, 'tdClasses' => $tdClasses]); ?>
        </tbody>
    </table>
</section>
