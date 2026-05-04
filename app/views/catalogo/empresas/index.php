<?php
$empresas = (array) ($empresas ?? []);
$filters = (array) ($filters ?? []);
$q = trim((string) ($filters['q'] ?? ''));
$toastKey = trim((string) ($_GET['toast'] ?? ''));
$toastMessage = match ($toastKey) {
    'empresa_creada' => 'Empresa creada correctamente.',
    'empresa_actualizada' => 'Empresa actualizada correctamente.',
    'empresa_eliminada' => 'Empresa eliminada correctamente.',
    'empresa_no_eliminada_aprendices' => 'No se puede eliminar: hay aprendices vinculados a esta empresa.',
    'empresa_no_encontrada' => 'No se pudo eliminar la empresa.',
    default => '',
};

partial('components/ui');
$tdClasses = str_replace('h-[50px] ', '', ui_td_classes());

ob_start();
?>
<a class="<?= e(ui_button_primary_classes()) ?> inline-flex items-center gap-2 text-app-textOnBrand" href="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/nuevo">
    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('plus') ?></span>
    Nueva empresa
</a>
<?php
$headerActions = (string) ob_get_clean();

partial('components/page_header', [
    'title' => 'Catálogo - Empresas',
    'subtitle' => 'Entidades coformadoras y datos de contacto para prácticas y visitas.',
    'actions' => $headerActions,
]);
?>

<section class="<?= e(ui_card_classes()) ?> mb-4">
    <form method="get" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas" class="flex w-full items-center gap-3" data-auto-filter-form data-auto-filter-ajax="true" data-auto-filter-target="#tabla-catalogo-empresas tbody">
        <div class="relative min-w-0 flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                <?= ui_icon('search') ?>
            </span>
            <input
                type="text"
                name="q"
                value="<?= e($q) ?>"
                placeholder="Buscar por razón social, NIT o ciudad"
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
    </form>
</section>

<?php partial('components/toast', ['message' => $toastMessage]); ?>

<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden">
    <table id="tabla-catalogo-empresas" class="<?= e(ui_table_in_card_classes()) ?> !m-0 !p-0 table-fixed">
        <colgroup>
            <col class="w-[32%]">
            <col class="w-[16%]">
            <col class="w-[16%]">
            <col class="w-[10%]">
            <col class="w-[26%]">
        </colgroup>
        <thead>
        <tr class="bg-app-panelSubtle">
            <th class="<?= e(ui_th_classes()) ?> px-4 pl-6">Razón social</th>
            <th class="<?= e(ui_th_classes()) ?> px-4">NIT</th>
            <th class="<?= e(ui_th_classes()) ?> px-4">Ciudad</th>
            <th class="<?= e(ui_th_classes()) ?> px-4">Aprendices</th>
            <th class="<?= e(ui_th_classes()) ?> px-4 pr-6">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php partial('catalogo/empresas/_rows', ['empresas' => $empresas, 'tdClasses' => $tdClasses]); ?>
        </tbody>
    </table>
</section>
