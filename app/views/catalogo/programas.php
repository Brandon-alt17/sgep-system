<?php partial('components/page_header', [
    'title' => 'Catálogo - Programas de formación',
    'subtitle' => 'Listado de programas disponibles para asociar aprendices y su gestión.',
]); ?>

<?php
$programas = (array) ($programas ?? []);
$filters = (array) ($filters ?? []);
$q = trim((string) ($filters['q'] ?? ''));
$nivel = trim((string) ($filters['nivel'] ?? ''));
$modalidad = trim((string) ($filters['modalidad'] ?? ''));
$totalRows = count($programas);

// Reutiliza estilos base sin altura fija para permitir crecimiento de fila con multilinea.
$tdClasses = str_replace('h-[50px] ', '', ui_td_classes());
?>

<!-- Buscador y filtros -->
<section class="<?= e(ui_card_classes()) ?> mb-4">
    <form method="get" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas" class="flex w-full items-center gap-3" data-auto-filter-form>
        <input
            type="text"
            name="q"
            value="<?= e($q) ?>"
            placeholder="Buscar por código o nombre"
            class="<?= e(ui_input_classes()) ?> mt-0 flex-1 min-w-0"
            data-auto-filter-input
        >

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

        <div class="<?= e(ui_select_wrapper_classes()) ?> w-64 shrink-0">
            <select name="modalidad" class="<?= e(ui_select_classes()) ?>" data-auto-filter-change>
                <option value="">Todas las modalidades</option>
                <option value="Presencial" <?= $modalidad === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                <option value="Virtual" <?= $modalidad === 'Virtual' ? 'selected' : '' ?>>Virtual</option>
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
            <col class="w-[12%]">
            <col class="w-[38%]">
            <col class="w-[18%]">
            <col class="w-[16%]">
            <col class="w-[16%]">
        </colgroup>
        <thead>
        <tr class="bg-app-panelSubtle">
            <th class="<?= e(ui_th_classes()) ?>">Código</th>
            <th class="<?= e(ui_th_classes()) ?>">Nombre</th>
            <th class="<?= e(ui_th_classes()) ?>">Nivel</th>
            <th class="<?= e(ui_th_classes()) ?>">Duración</th>
            <th class="<?= e(ui_th_classes()) ?>">Modalidad</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($programas === []): ?>
            <tr>
                <td class="<?= e($tdClasses) ?> py-4 text-center" colspan="5">No hay programas registrados.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($programas as $programa): ?>
                <tr>
                    <td class="<?= e($tdClasses) ?> py-3 align-top"><?= e((string) ($programa['codigo'] ?? '')) ?></td>
                    <td class="<?= e($tdClasses) ?> py-3 whitespace-normal break-words leading-6 align-top"><?= e((string) ($programa['nombre'] ?? '')) ?></td>
                    <td class="<?= e($tdClasses) ?> py-3 align-top"><?= e((string) ($programa['nivel'] ?? '')) ?></td>
                    <td class="<?= e($tdClasses) ?> py-3 align-top">-</td>
                    <td class="<?= e($tdClasses) ?> py-3 align-top"><?= e((string) ($programa['modalidad'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
