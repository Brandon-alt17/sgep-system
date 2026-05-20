<?php
declare(strict_types=1);

$pendientes = (array) ($pendientes ?? []);
$programas = (array) ($programas ?? []);
$filters = (array) ($filters ?? []);
$q = trim((string) ($filters['q'] ?? ''));
$pendientesCount = (int) ($pendientesCount ?? 0);
$toastKey = trim((string) ($_GET['toast'] ?? ''));
$toastMessage = match ($toastKey) {
    'pendiente_resuelto' => 'Vínculo con el programa guardado correctamente.',
    default => '',
};
$tdClasses = str_replace('h-[50px] ', '', ui_td_classes());
$pendientesAlertUrgente = $pendientesCount > 5;
$pendientesAlertSection = trim('mt-4 ' . ui_pending_enlace_alert_section_classes($pendientesCount));
$pendientesAlertFg = $pendientesAlertUrgente ? 'text-rose-700' : 'text-app-warningText';
$pendientesAlertBadge = $pendientesAlertUrgente
    ? 'rounded-full bg-rose-200 px-2 py-0.5 text-xs font-bold text-rose-900'
    : 'rounded-full border border-app-borderWarning bg-amber-100 px-2 py-0.5 text-xs font-bold text-app-warningText';
?>

<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 shrink-0 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/programas" aria-label="Volver al catálogo de programas">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex min-w-0 flex-1 flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Pendientes por enlazar</h2>
        <p class="m-0 text-sm text-app-muted">Aprendices importados sin vínculo a un programa del catálogo. Asigna un programa existente en cada fila para resolver el pendiente.</p>
    </div>
</section>

<section class="<?= e($pendientesAlertSection) ?>">
    <p class="m-0 flex items-center gap-2 text-sm font-semibold <?= e($pendientesAlertFg) ?>">
        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4 <?= e($pendientesAlertFg) ?>"><?= ui_icon('circle-alert') ?></span>
        Hay <span class="<?= e($pendientesAlertBadge) ?>"><?= e((string) $pendientesCount) ?></span> aprendices pendientes por enlazar con un programa.
    </p>
</section>

<section class="<?= e(ui_card_classes()) ?> mb-4 mt-4 p-6">
    <form
        method="get"
        action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/pendientes"
        class="w-full"
        data-remote-table-filter-form
        data-remote-table-filter-target="#tabla-pendientes-tbody"
        data-remote-table-filter-debounce="350"
    >
        <div class="relative min-w-0 w-full">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                <?= ui_icon('search') ?>
            </span>
            <input
                type="text"
                name="q"
                value="<?= e($q) ?>"
                placeholder="Buscar por documento, aprendiz o programa"
                aria-label="Buscar por documento, aprendiz o programa"
                autocomplete="off"
                class="<?= e(ui_input_classes()) ?> mt-0 w-full bg-white pl-10 pr-10"
                data-remote-table-filter-q
            >
            <button
                type="button"
                class="<?= $q !== '' ? '' : 'hidden' ?> absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-sm text-app-muted hover:bg-app-panelSubtle hover:text-app-text"
                aria-label="Limpiar búsqueda"
                data-remote-table-filter-clear
            >&times;</button>
        </div>
    </form>
</section>

<?php partial('components/toast', ['message' => $toastMessage]); ?>

<section class="<?= e(ui_card_classes()) ?> mb-6 !p-0">
    <div class="overflow-x-auto rounded-[10px]">
        <table class="<?= e(ui_table_in_card_classes()) ?> table-fixed min-w-[900px] !m-0 !p-0">
            <colgroup>
                <col class="w-[28%] min-w-[180px]">
                <col class="w-[12%]">
                <col class="w-[27.5%]">
                <col class="w-[32.5%]">
            </colgroup>
            <thead>
            <tr class="bg-app-panelSubtle">
                <th class="<?= e(ui_th_classes()) ?> px-5 pl-6">Aprendiz</th>
                <th class="<?= e(ui_th_classes()) ?> px-5">Documento</th>
                <th class="<?= e(ui_th_classes()) ?> px-5">Programa fuente</th>
                <th class="<?= e(ui_th_classes()) ?> px-5 pr-6 text-left">Resolver</th>
            </tr>
            </thead>
            <tbody id="tabla-pendientes-tbody">
                <?php partial('catalogo/programas/_pendientes_rows', [
                    'pendientes' => $pendientes,
                    'programas' => $programas,
                    'tdClasses' => $tdClasses,
                ]); ?>
            </tbody>
        </table>
    </div>
</section>
