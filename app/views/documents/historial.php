<?php

declare(strict_types=1);

use App\Models\DocumentoGenerado;

$aprendiz = isset($aprendiz) && is_array($aprendiz) ? $aprendiz : null;
$aprendizId = (int) ($aprendiz_id ?? 0);
$documentos = is_array($documentos ?? null) ? $documentos : [];
$errorKey = trim((string) ($_GET['error'] ?? ''));
$errorMsg = match ($errorKey) {
    'archivo_no_disponible' => 'El archivo ya no está disponible en el servidor. Genere el documento nuevamente.',
    default => '',
};

$volverPerfilUrl = APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId;
$generarUrl = APP_BASE_PATH . '/documentos/generar?aprendiz_id=' . $aprendizId;
?>

<section class="bg-app-bg mb-4 flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e($volverPerfilUrl) ?>" aria-label="Volver al perfil del aprendiz">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex min-w-0 flex-1 flex-col gap-0 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Historial de documentos F-023</h2>
        <p class="m-0 mt-2 max-w-[720px] text-sm text-app-muted">
            Documentos GFPI-F-023 generados para
            <span class="font-medium text-app-text"><?= e((string) ($aprendiz['nombre_completo'] ?? '')) ?></span>.
        </p>
    </div>
</section>

<?php if ($errorMsg !== ''): ?>
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="alert">
        <?= e($errorMsg) ?>
    </div>
<?php endif; ?>

<div class="mb-4 flex flex-wrap gap-3">
    <a href="<?= e($generarUrl) ?>" class="<?= e(ui_button_primary_classes()) ?> inline-flex items-center gap-2 text-app-textOnBrand">
        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('file-up') ?></span>
        Generar nuevo documento
    </a>
</div>

<section class="<?= e(ui_card_surface_classes()) ?>">
    <div class="<?= e(ui_card_header_classes()) ?>">
        <div class="<?= e(ui_card_header_stack_classes()) ?>">
            <h3 class="<?= e(ui_card_title_classes()) ?>">Documentos generados</h3>
            <p class="<?= e(ui_card_description_classes()) ?>">Fecha, partes incluidas y formato de cada exportación.</p>
        </div>
    </div>
    <div class="<?= e(ui_card_body_classes()) ?>">
        <?php if ($documentos === []): ?>
            <p class="m-0 text-sm text-app-muted">Aún no hay documentos generados para este aprendiz.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table id="documentos-generados-table" class="<?= e(ui_table_in_card_classes()) ?>">
                    <thead>
                    <tr>
                        <th class="<?= e(ui_th_classes()) ?>">Fecha</th>
                        <th class="<?= e(ui_th_classes()) ?>">Partes incluidas</th>
                        <th class="<?= e(ui_th_classes()) ?>">Formato</th>
                        <th class="<?= e(ui_th_classes()) ?>">Acción</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($documentos as $doc): ?>
                        <?php
                        $docId = (int) ($doc['id'] ?? 0);
                        $formato = strtoupper(trim((string) ($doc['formato'] ?? '')));
                        $createdAt = trim((string) ($doc['created_at'] ?? ''));
                        $fechaDisplay = $createdAt !== ''
                            ? date_iso_to_dmY(substr($createdAt, 0, 10)) . ' ' . substr($createdAt, 11, 5)
                            : '—';
                        $partesLabel = DocumentoGenerado::describePartes((string) ($doc['partes'] ?? ''));
                        $path = trim((string) ($doc['ruta_archivo'] ?? ''));
                        $disponible = DocumentoGenerado::isPathAllowed($path);
                        ?>
                        <tr>
                            <td class="<?= e(ui_td_classes()) ?> whitespace-nowrap"><?= e($fechaDisplay) ?></td>
                            <td class="<?= e(ui_td_classes()) ?> max-w-md"><?= e($partesLabel) ?></td>
                            <td class="<?= e(ui_td_classes()) ?> whitespace-nowrap"><?= e($formato !== '' ? $formato : '—') ?></td>
                            <td class="<?= e(ui_td_classes()) ?> whitespace-nowrap">
                                <?php if ($disponible): ?>
                                    <a href="<?= e(APP_BASE_PATH . '/documentos/descargar?id=' . $docId) ?>"
                                       class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('download') ?></span>
                                        Descargar
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-app-muted">Archivo no disponible</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $currentPage = (int) ($currentPage ?? 1);
            $totalPages = (int) ($totalPages ?? 1);
            $paginationUrl = APP_BASE_PATH . '/documentos/historial?aprendiz_id=' . $aprendizId . '&page=%d';
            ui_render_pagination(
                $currentPage,
                $totalPages,
                $paginationUrl,
                'Paginación de documentos generados',
                'documentos-generados-table'
            );
            ?>
        <?php endif; ?>
    </div>
</section>
