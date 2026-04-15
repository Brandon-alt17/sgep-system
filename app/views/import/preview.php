<?php partial('components/page_header', [
    'title' => 'Resultado de importación',
    'subtitle' => 'Resumen del proceso de carga y validación del archivo.',
]); ?>

<section class="<?= e(ui_card_classes()) ?>">
    <table class="<?= e(ui_table_classes()) ?>">
        <tbody>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Insertados</th>
            <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($resultado['inserted'] ?? 0)) ?></td>
        </tr>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Actualizados</th>
            <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($resultado['updated'] ?? 0)) ?></td>
        </tr>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Duplicados detectados</th>
            <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($resultado['duplicates'] ?? 0)) ?></td>
        </tr>
        </tbody>
    </table>
</section>

<?php if (!empty($resultado['errors'])): ?>
    <section class="mt-4 <?= e(ui_card_classes()) ?>">
        <h3 class="mb-2 mt-0 text-[15px] font-semibold">Errores</h3>
        <ul class="m-0 list-disc space-y-1 pl-5 text-sm text-gray-700">
            <?php foreach ($resultado['errors'] as $error): ?>
                <li><?= e((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
