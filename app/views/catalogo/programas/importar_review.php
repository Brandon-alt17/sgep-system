<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/programas/importar" aria-label="Volver a importar programa">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5 "><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Revisión de importación de programa</h2>
        <p class="m-0 text-sm text-app-muted">
            <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('file') ?></span>
            <?= e((string) ($fileName ?? 'Archivo sin nombre')) ?>
            &mdash; Revise extracción automática y confirme persistencia.
        </p>
    </div>
</section>

<?php
$parsed = (array) ($parsed ?? []);
$meta = (array) ($parsed['meta'] ?? []);
$competencias = (array) ($parsed['competencias'] ?? []);
$warnings = (array) ($parsed['warnings'] ?? []);
$totalResultados = 0;
foreach ($competencias as $comp) {
    $totalResultados += count((array) ($comp['resultados'] ?? []));
}

$programName = trim((string) ($meta['nombre'] ?? ''));
if ($programName === '') {
    $programName = 'Programa sin nombre detectado';
}
$programCode = trim((string) ($meta['codigo'] ?? ''));

$missingMetaFields = [];
$metaFieldLabels = [
    'nivel' => 'Nivel',
    'modalidad' => 'Modalidad',
    'horas_total' => 'Horas total',
];
foreach ($metaFieldLabels as $metaKey => $metaLabel) {
    if (trim((string) ($meta[$metaKey] ?? '')) === '') {
        $missingMetaFields[] = $metaLabel;
    }
}
?>

<section class="mt-4">
    <h3 class="m-0 text-2xl font-semibold text-app-text"><?= e($programName) ?></h3>
    <p class="mt-1 text-sm text-app-muted">Código: <?= e($programCode !== '' ? $programCode : 'No detectado') ?></p>
</section>

<section class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <div class="flex items-center justify-between gap-2 h-full">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Nivel</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) (($meta['nivel'] ?? '') !== '' ? $meta['nivel'] : 'N/D')) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('book-open') ?></span>
        </div>
    </article>

    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <div class="flex items-center justify-between gap-2 h-full">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Competencias</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) count($competencias)) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('target') ?></span>
        </div>
    </article>

    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <div class="flex items-center justify-between gap-2 h-full">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Resultados de aprendizaje</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) $totalResultados) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('book') ?></span>
        </div>
    </article>

    <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6">
        <div class="flex items-center justify-between gap-2 h-full">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Horas totales</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) (($meta['horas_total'] ?? '') !== '' ? $meta['horas_total'] . 'h' : 'N/D')) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('clock') ?></span>
        </div>
    </article>
</section>

<?php if ($warnings !== [] || $missingMetaFields !== []): ?>
    <section class="mt-4 <?= e(ui_warning_card_classes()) ?> mb-6">
        <h4 class="mb-2 mt-0 text-sm font-semibold">Campos no obtenidos del PDF (<?= e((string) count($missingMetaFields)) ?>)</h4>
        <?php if ($missingMetaFields !== []): ?>
            <div class="mb-3 flex flex-wrap gap-2">
                <?php foreach ($missingMetaFields as $missingField): ?>
                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"><?= e($missingField) ?></span>
                <?php endforeach; ?>
            </div>
            <p class="m-0 mb-2 text-sm text-app-muted">Estos campos no pudieron extraerse automáticamente. Completa la información manualmente o vuelve a importar el PDF.</p>
        <?php endif; ?>
        <?php if ($warnings !== []): ?>
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
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($competencias !== []): ?>
    <?php partial('components/live_filter_search', [
        'items_target' => '#importar-review-competencias-live-items',
        'placeholder' => 'Buscar por competencia, RAE o código',
        'aria_label' => 'Buscar por competencia, RAE o código',
        'variant' => 'card',
        'class' => '',
    ]); ?>
<?php endif; ?>

<section class="<?= e(ui_card_classes()) ?> mb-6 mt-4 p-6">
    <h4 class="m-0 text-md font-semibold text-app-text">Competencias y Resultados de Aprendizaje</h4>

    <?php if ($competencias === []): ?>
        <div class="mt-6 rounded-md border border-app-border bg-app-panelSubtle p-4 text-sm text-app-muted">
            No se detectaron competencias automáticamente.
        </div>
    <?php else: ?>
        <div id="importar-review-competencias-live-items" class="mt-6 space-y-6" data-live-filter-items>
            <?php foreach ($competencias as $compIndex => $comp): ?>
                <?php
                $resultadosComp = (array) ($comp['resultados'] ?? []);
                $searchParts = [
                    (string) ($comp['codigo'] ?? ''),
                    (string) ($comp['nombre'] ?? ''),
                ];
                foreach ($resultadosComp as $raIndex => $resultado) {
                    $searchParts[] = 'RA' . ($raIndex + 1);
                    $searchParts[] = (string) ($resultado['descripcion'] ?? '');
                    $searchParts[] = (string) ($resultado['codigo'] ?? '');
                }
                $searchText = trim(implode(' ', array_filter(array_map(
                    static fn ($value): string => trim((string) $value),
                    $searchParts
                ))));
                ?>
                <article class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6" data-live-filter-item data-live-filter-text="<?= e($searchText) ?>">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                        <?php if (trim((string) ($comp['codigo'] ?? '')) !== ''): ?>
                            <p class="m-0 text-xs font-semibold tracking-wide text-app-muted ui-monospace font-mono"><?= e((string) $comp['codigo']) ?></p>
                        <?php endif; ?>
                        <p class="m-0 mt-1 text-lg font-semibold text-app-text"><?= e((string) (($comp['nombre'] ?? '') !== '' ? $comp['nombre'] : 'Competencia ' . ($compIndex + 1))) ?></p>
                        </div>
                        <?php if (trim((string) ($comp['horas'] ?? '')) !== ''): ?>
                            <span class="inline-flex items-center rounded-full bg-app-accent px-2.5 py-1 text-xs font-semibold text-app-textOnBrand"><?= e((string) $comp['horas']) ?>h</span>
                        <?php endif; ?>
                    </div>
                    <div class="overflow-hidden rounded-md">
                        <table class="<?= e(ui_table_classes()) ?>">
                            <thead>
                            <tr>
                                <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Código RA</th>
                                <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Resultado de aprendizaje</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($resultadosComp === []): ?>
                                <tr>
                                    <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>" colspan="2">Sin resultados detectados para esta competencia.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($resultadosComp as $raIndex => $resultado): ?>
                                    <tr class="<?= $raIndex < (count($resultadosComp) - 1) ? 'border-b border-app-borderSoft' : '' ?>">
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> w-32 ui-monospace font-mono"><?= e((string) (($resultado['codigo'] ?? '') !== '' ? $resultado['codigo'] : ('RA' . ($raIndex + 1)))) ?></td>
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?>"><?= e((string) ($resultado['descripcion'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="pb-20" aria-hidden="true"></div>
<?php
$footerNote = $warnings !== []
    ? 'Se guardará como <strong>confirmado parcial</strong> por advertencias detectadas.'
    : '';
partial('components/sticky_bottom_action', [
    'action' => APP_BASE_PATH . '/catalogo/programas/importar/guardar',
    'buttonText' => 'Confirmar y guardar',
    'fields' => [
        'parsed_payload' => (string) ($encoded ?? ''),
        'file_name' => (string) ($fileName ?? ''),
    ],
    'note' => $footerNote,
    'cancelText' => 'Cancelar',
    'cancelHref' => APP_BASE_PATH . '/catalogo/programas/importar',
]);
?>
