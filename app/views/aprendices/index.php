<?php partial('components/page_header', [
    'title' => 'Aprendices',
    'subtitle' => 'Módulo listo para agregar diseño específico en próximas iteraciones.',
]); ?>

<section class="<?= e(ui_card_classes()) ?>">
    <p><a class="<?= e(ui_button_primary_classes()) ?> px-2.5 py-1.5 text-xs" href="<?= e(APP_BASE_PATH) ?>/aprendices/create">Nuevo aprendiz</a></p>
    <table class="<?= e(ui_table_classes()) ?>">
        <thead>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Documento</th>
            <th class="<?= e(ui_th_classes()) ?>">Nombre</th>
            <th class="<?= e(ui_th_classes()) ?>">Estado</th>
            <th class="<?= e(ui_th_classes()) ?>">Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($aprendices ?? []) as $aprendiz): ?>
            <tr>
                <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $aprendiz['numero_documento']) ?></td>
                <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $aprendiz['nombre_completo']) ?></td>
                <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $aprendiz['estado']) ?></td>
                <td class="<?= e(ui_td_classes()) ?>"><a href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int) $aprendiz['id'] ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
