<?php partial('components/page_header', [
    'title' => 'Revisión de importación de programa',
    'subtitle' => 'Revise extracción automática y confirme persistencia.',
]); ?>

<?php
$parsed = (array) ($parsed ?? []);
$meta = (array) ($parsed['meta'] ?? []);
$competencias = (array) ($parsed['competencias'] ?? []);
$warnings = (array) ($parsed['warnings'] ?? []);
$totalResultados = 0;
foreach ($competencias as $comp) {
    $totalResultados += count((array) ($comp['resultados'] ?? []));
}
?>

<?php if ($warnings !== []): ?>
    <section class="<?= e(ui_warning_card_classes()) ?> mb-4">
        <ul class="m-0 list-disc space-y-1 pl-4">
            <?php foreach ($warnings as $warning): ?>
                <?php
                $warningText = is_array($warning) ? (string) ($warning['message'] ?? '') : (string) $warning;
                $warningSeverity = is_array($warning) ? (string) ($warning['severity'] ?? 'warning') : 'warning';
                ?>
                <li>
                    <span class="font-semibold"><?= e(strtoupper($warningSeverity)) ?>:</span>
                    <?= e($warningText) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="<?= e(ui_card_classes()) ?> mb-4">
    <p class="m-0 text-sm text-app-muted">Archivo: <?= e((string) ($fileName ?? '')) ?></p>
    <div class="mt-3 grid gap-2 md:grid-cols-2">
        <p class="m-0 text-sm"><strong>Código:</strong> <?= e((string) ($meta['codigo'] ?? '')) ?></p>
        <p class="m-0 text-sm"><strong>Nombre:</strong> <?= e((string) ($meta['nombre'] ?? '')) ?></p>
        <p class="m-0 text-sm"><strong>Nivel:</strong> <?= e((string) ($meta['nivel'] ?? '')) ?></p>
        <p class="m-0 text-sm"><strong>Modalidad:</strong> <?= e((string) ($meta['modalidad'] ?? '')) ?></p>
        <p class="m-0 text-sm"><strong>Horas lectiva:</strong> <?= e((string) ($meta['horas_lectiva'] ?? '')) ?></p>
        <p class="m-0 text-sm"><strong>Horas productiva:</strong> <?= e((string) ($meta['horas_productiva'] ?? '')) ?></p>
        <p class="m-0 text-sm"><strong>Horas total:</strong> <?= e((string) ($meta['horas_total'] ?? '')) ?></p>
        <p class="m-0 text-sm"><strong>Competencias:</strong> <?= e((string) count($competencias)) ?> | <strong>Resultados:</strong> <?= e((string) $totalResultados) ?></p>
    </div>
</section>

<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden mb-4">
    <table class="<?= e(ui_table_in_card_classes()) ?>">
        <thead>
        <tr>
            <th class="<?= e(ui_th_classes()) ?>">Competencia</th>
            <th class="<?= e(ui_th_classes()) ?>">Resultados de aprendizaje</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($competencias === []): ?>
            <tr><td class="<?= e(ui_td_classes()) ?>" colspan="2">No se detectaron competencias automáticamente.</td></tr>
        <?php else: ?>
            <?php foreach ($competencias as $comp): ?>
                <tr>
                    <td class="<?= e(ui_td_classes()) ?> align-top"><?= e((string) ($comp['nombre'] ?? '')) ?></td>
                    <td class="<?= e(ui_td_classes()) ?>">
                        <ul class="m-0 list-disc pl-4">
                            <?php foreach ((array) ($comp['resultados'] ?? []) as $resultado): ?>
                                <li><?= e((string) ($resultado['descripcion'] ?? '')) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/importar/guardar">
    <input type="hidden" name="parsed_payload" value="<?= e((string) ($encoded ?? '')) ?>">
    <input type="hidden" name="file_name" value="<?= e((string) ($fileName ?? '')) ?>">
    <?php if ($warnings !== []): ?>
        <p class="mb-2 mt-0 text-sm text-app-muted">Se guardará como <strong>confirmado parcial</strong> por advertencias detectadas.</p>
    <?php endif; ?>
    <button class="<?= e(ui_button_primary_classes()) ?>" type="submit">Confirmar y guardar</button>
</form>
