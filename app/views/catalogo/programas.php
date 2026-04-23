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
    <form method="get" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas" class="flex w-full items-center gap-3">
        <input
            type="text"
            name="q"
            value="<?= e($q) ?>"
            placeholder="Buscar por código o nombre"
            class="<?= e(ui_input_classes()) ?> mt-0 flex-1 min-w-0"
        >

        <div class="relative">
            <select name="nivel" class="<?= e(ui_select_classes()) ?>">
                <option value="">Todos los niveles</option>
                <option value="Técnico" <?= $nivel === 'Técnico' ? 'selected' : '' ?>>Técnico</option>
                <option value="Tecnólogo" <?= $nivel === 'Tecnólogo' ? 'selected' : '' ?>>Tecnólogo</option>
                <option value="Auxiliar" <?= $nivel === 'Auxiliar' ? 'selected' : '' ?>>Auxiliar</option>
                <option value="Operario" <?= $nivel === 'Operario' ? 'selected' : '' ?>>Operario</option>
            </select>
        </div>

        <div class="relative">
            <select name="modalidad" class="<?= e(ui_select_classes()) ?>">
                <option value="">Todas las modalidades</option>
                <option value="Presencial" <?= $modalidad === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                <option value="Virtual" <?= $modalidad === 'Virtual' ? 'selected' : '' ?>>Virtual</option>
            </select>
        </div>

        <button type="submit" class="<?= e(ui_button_small_primary_classes()) ?>">Filtrar</button>
    </form>
</section>

<section class="<?= e(ui_card_classes()) ?>">
    <table id="tabla-catalogo-programas" class="<?= e(ui_table_classes()) ?> table-fixed">
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
                <td class="<?= e($tdClasses) ?> py-3" colspan="5">No hay programas registrados.</td>
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
