<?php partial('components/page_header', [
    'title' => 'Reporte maestro',
    'subtitle' => 'Consulte el consolidado y actualice campos libres de cada aprendiz.',
]); ?>

<p class="mb-3">
    <a class="inline-flex rounded-md border border-app-accent bg-app-accent px-4 py-2 text-sm font-medium text-white no-underline hover:bg-[#0e8f82]" href="<?= e(APP_BASE_PATH) ?>/reportes/exportar">Exportar a Excel</a>
</p>

<section class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
    <table class="<?= e(ui_table_classes()) ?>">
        <thead>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Documento</th>
            <th class="<?= e(ui_th_classes()) ?>">Nombre</th>
            <th class="<?= e(ui_th_classes()) ?>">Estado</th>
            <th class="<?= e(ui_th_classes()) ?>">Últ. edición reporte</th>
            <th class="<?= e(ui_th_classes()) ?>">Editar campo libre</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($rows ?? []) as $row): ?>
            <tr>
                <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $row['numero_documento']) ?></td>
                <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $row['nombre_completo']) ?></td>
                <td class="<?= e(ui_td_classes()) ?>"><?= e((string) $row['estado']) ?></td>
                <td class="<?= e(ui_td_classes()) ?>"><?= e((string) ($row['reporte_actualizado'] ?? '')) ?></td>
                <td class="<?= e(ui_td_classes()) ?>">
                    <form method="post" action="<?= e(APP_BASE_PATH) ?>/reportes/update" class="grid gap-2 md:grid-cols-[1fr_1fr_auto] md:items-end">
                        <input type="hidden" name="aprendiz_id" value="<?= (int) $row['id'] ?>">
                        <input type="text" name="campo" placeholder="campo" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
                        <input type="text" name="valor" placeholder="valor" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs">
                        <button type="submit" class="<?= e(ui_button_primary_classes()) ?> px-3 py-2 text-xs">Guardar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
