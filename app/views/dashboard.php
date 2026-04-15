<?php partial('components/page_header', [
    'title' => 'Dashboard',
    'subtitle' => 'Vista general del estado de aprendices y próximas actividades.',
]); ?>

<section class="grid gap-4 lg:grid-cols-[2fr_1fr]">
    <article class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Conteo por estado</h3>
        <table class="<?= e(ui_table_classes()) ?>">
            <thead>
            <tr>
                <th class="<?= e(ui_th_classes()) ?>">Estado</th>
                <th class="<?= e(ui_th_classes()) ?>">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($counts ?? []) as $row): ?>
                <tr>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $row['estado']) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $row['total']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>

    <article class="<?= e(ui_card_classes()) ?>">
        <h3 class="<?= e(ui_heading_sm_classes()) ?>">Próximas visitas (30 días)</h3>
        <table class="<?= e(ui_table_classes()) ?>">
            <thead>
            <tr>
                <th class="<?= e(ui_th_classes()) ?>">Aprendiz</th>
                <th class="<?= e(ui_th_classes()) ?>">Fecha</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($alerts ?? []) as $alert): ?>
                <tr>
                    <td class="<?= e(ui_td_classes()) ?>"><a href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int) $alert['id'] ?>"><?= e((string) $alert['nombre_completo']) ?></a></td>
                    <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $alert['proxima_visita']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>
