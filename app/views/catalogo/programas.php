<?php partial('components/page_header', [
    'title' => 'Catálogo - Programas de formación',
    'subtitle' => 'Listado de programas disponibles para asociar aprendices y su gestión.',
]); ?>

<?php $programas = (array) ($programas ?? []); ?>

<section class="<?= e(ui_card_classes()) ?>">
    <h3 class="<?= e(ui_heading_sm_classes()) ?>">Programas (<?= e((string) count($programas)) ?>)</h3>

    <table class="<?= e(ui_table_classes()) ?>">
        <thead>
        <tr>
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
                <td class="<?= e(ui_td_classes()) ?>" colspan="5">No hay programas registrados.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($programas as $programa): ?>
                <tr>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($programa['codigo'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($programa['nombre'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($programa['nivel'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>">-</td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($programa['modalidad'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
