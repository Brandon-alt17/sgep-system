<?php partial('components/page_header', [
    'title' => 'Catálogo - Pendientes por enlazar',
    'subtitle' => 'Aprendices importados sin vínculo de programa. Asigne un programa existente para resolver.',
]); ?>

<?php
$pendientes = (array) ($pendientes ?? []);
$programas = (array) ($programas ?? []);
$filters = (array) ($filters ?? []);
$q = trim((string) ($filters['q'] ?? ''));
?>

<section class="<?= e(ui_card_classes()) ?> mb-4">
    <form method="get" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/pendientes" class="flex gap-3">
        <div class="relative flex-1 min-w-0">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                <?= ui_icon('search') ?>
            </span>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por documento, aprendiz o programa" class="<?= e(ui_input_classes()) ?> pl-10">
        </div>
        <button class="<?= e(ui_button_primary_classes()) ?>" type="submit">Buscar</button>
    </form>
</section>

<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden">
    <table class="<?= e(ui_table_in_card_classes()) ?>">
        <thead>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Aprendiz</th>
            <th class="<?= e(ui_th_classes()) ?>">Documento</th>
            <th class="<?= e(ui_th_classes()) ?>">Programa fuente</th>
            <th class="<?= e(ui_th_classes()) ?>">Motivo</th>
            <th class="<?= e(ui_th_classes()) ?>">Resolver</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($pendientes === []): ?>
            <tr><td class="<?= e(ui_td_classes()) ?>" colspan="5">No hay pendientes por enlazar.</td></tr>
        <?php else: ?>
            <?php foreach ($pendientes as $row): ?>
                <tr>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['nombre_aprendiz'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['numero_documento'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['programa_fuente'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['motivo'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>">
                        <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/pendientes/resolver" class="flex items-center gap-2">
                            <input type="hidden" name="pending_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                            <span class="<?= e(ui_select_wrapper_classes()) ?> min-w-[280px]">
                                <select name="programa_id" required class="<?= e(ui_select_classes()) ?>">
                                    <option value="">Seleccionar programa</option>
                                    <?php foreach ($programas as $programa): ?>
                                        <option value="<?= (int) ($programa['id'] ?? 0) ?>">
                                            <?= e((string) ($programa['codigo'] ?? '')) ?> - <?= e((string) ($programa['nombre'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                                </span>
                            </span>
                            <button class="<?= e(ui_button_small_classes()) ?>" type="submit">Resolver</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
