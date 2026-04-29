<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/programas" aria-label="Volver al catálogo de programas">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5 "><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Detalle del programa</h2>
        <p class="m-0 text-sm text-app-muted">
            Vista de consulta, edición y administración del programa.
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
?>

<section class="mt-4">
    <h3 class="m-0 text-2xl font-semibold text-app-text"><?= e($programName) ?></h3>
    <p class="mt-1 text-sm text-app-muted">Código: <?= e($programCode !== '' ? $programCode : 'No detectado') ?></p>
</section>

<section class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="<?= e(ui_card_classes()) ?> ">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Nivel</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) (($meta['nivel'] ?? '') !== '' ? $meta['nivel'] : 'N/D')) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('book-open') ?></span>
        </div>
    </article>

    <article class="<?= e(ui_card_classes()) ?>">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Competencias</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) count($competencias)) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('target') ?></span>
        </div>
    </article>

    <article class="<?= e(ui_card_classes()) ?>">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Resultados de aprendizaje</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) $totalResultados) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('book') ?></span>
        </div>
    </article>

    <article class="<?= e(ui_card_classes()) ?>">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="m-0 text-xs uppercase tracking-wide text-app-muted">Horas totales</p>
                <p class="mt-2 text-1xl font-semibold text-app-text"><?= e((string) (($meta['horas_total'] ?? '') !== '' ? $meta['horas_total'] . 'h' : 'N/D')) ?></p>
            </div>
            <span class="inline-flex h-6 w-6 text-app-accent [&_svg]:h-6 [&_svg]:w-6"><?= ui_icon('clock') ?></span>
        </div>
    </article>
</section>

<?php if ($warnings !== []): ?>
    <section class="mt-4 <?= e(ui_warning_card_classes()) ?> mb-4">
        <h4 class="mb-2 mt-0 text-sm font-semibold">Advertencias de la importación</h4>
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

<section class="<?= e(ui_card_classes()) ?> mb-4 mt-4">
    <h4 class="m-0 text-md font-semibold text-app-text">Competencias y Resultados de Aprendizaje</h4>

    <?php if ($competencias === []): ?>
        <div class="mt-4 rounded-md border border-app-border bg-app-panelSubtle p-4 text-sm text-app-muted">
            No se detectaron competencias automáticamente.
        </div>
    <?php else: ?>
        <div class="mt-4 space-y-4">
            <?php foreach ($competencias as $compIndex => $comp): ?>
                <?php $resultadosComp = (array) ($comp['resultados'] ?? []); ?>
                <article class="rounded-lg border border-app-border bg-app-panel p-4">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                        <?php if (trim((string) ($comp['codigo'] ?? '')) !== ''): ?>
                            <p class="m-0 text-xs font-semibold tracking-wide text-app-muted"><?= e((string) $comp['codigo']) ?></p>
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
                                        <td class="<?= e(str_replace('border-b border-app-borderSoft ', '', ui_td_classes())) ?> w-32"><?= e('RA' . ($raIndex + 1)) ?></td>
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
