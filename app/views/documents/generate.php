<?php

declare(strict_types=1);

$aprendiz = isset($aprendiz) && is_array($aprendiz) ? $aprendiz : null;
$aprendizId = (int) ($aprendiz_id ?? 0);
$info = (array) ($info ?? []);
$exportMomentos = (array) ($export_momentos ?? []);
$infoFaltantes = (int) ($info_faltantes_count ?? 0);
$errorKey = (string) ($error ?? '');
$pdfAvailable = (bool) ($pdf_available ?? true);
$pdfAvailabilityMessage = (string) ($pdf_availability_message ?? '');
$errorMsg = match ($errorKey) {
    'sin_partes' => 'Seleccione al menos un bloque a incluir en el documento.',
    'id_aprendiz' => 'Indique un aprendiz válido para exportar.',
    'pdf_libreoffice' => 'La exportación PDF requiere LibreOffice en este equipo. Use Word (.docx) o pida al administrador que instale LibreOffice.',
    'pdf_convert_failed' => 'LibreOffice no pudo convertir el documento a PDF. Pruebe con Word (.docx) o contacte al administrador.',
    'export_failed' => 'No se pudo generar el archivo. Si eligió PDF, pruebe con Word o contacte al administrador.',
    default => $errorKey !== '' ? 'No se pudo completar la solicitud. Inténtelo de nuevo.' : '',
};

$card = e(ui_card_classes());
$chkClass = 'peer mt-0.5 h-4 w-4 shrink-0 rounded border-app-borderControlStrong text-app-accent';
/** Label de fila con casilla: título/estado según :checked (peer en el input); archivo y estado en tipografía más pequeña */
$docLabelClass = 'min-w-0 flex-1 cursor-pointer text-left text-sm [&_.doc-title]:text-app-muted peer-checked:[&_.doc-title]:text-app-text [&_.doc-file]:text-xs [&_.doc-file]:text-app-muted [&_.doc-status]:text-xs [&_.doc-status]:text-app-muted';
/** Fila tipo referencia: sin caja por ítem, solo espacio vertical */
$rowClass = 'flex gap-3 py-2.5';
?>

<?php $volverPerfilUrl = APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId; ?>
    <section class="bg-app-bg mb-4 flex flex-row items-start gap-2">
        <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e($volverPerfilUrl) ?>" aria-label="Volver al perfil del aprendiz">
            <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
        </a>
        <div class="mb-3 flex min-w-0 flex-1 flex-col gap-0 pl-4">
            <h2 class="m-0 text-2xl font-semibold text-app-text">Generar formato GFPI-F-023</h2>
            <p class="m-0 mt-2 max-w-[720px] text-sm text-app-muted">Elija qué bloques incluir y el formato de salida; luego descargue el documento consolidado.</p>
        </div>
    </section>

    <?php if ($errorMsg !== ''): ?>
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="alert">
            <?= e($errorMsg) ?>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="<?= e(APP_BASE_PATH) ?>/documentos/generar"
        class="flex w-full max-w-none flex-col gap-6"
        data-download-form
        data-download-label="Generando formato GFPI-F-023"
    >
        <input type="hidden" name="aprendiz_id" value="<?= $aprendizId ?>">

        <?php
        $nombreAprendiz = trim((string) ($aprendiz['nombre_completo'] ?? ''));
        $lineaDocumento = trim(trim((string) ($aprendiz['tipo_documento'] ?? '')) . ' ' . trim((string) ($aprendiz['numero_documento'] ?? '')));
        $lineaEmpresa = trim((string) ($aprendiz['empresa_nombre'] ?? '')) !== '' ? trim((string) ($aprendiz['empresa_nombre'] ?? '')) : 'Sin empresa';
        $infoEstado = $infoFaltantes > 0
            ? '(faltan ' . (int) $infoFaltantes . ' datos obligatorios — completar información)'
            : '(listo para exportar)';
        ?>

        <section class="<?= $card ?> w-full !p-6 text-left">
            <h3 class="m-0 text-left text-base font-semibold text-app-text">Datos del aprendiz</h3>
            <p class="m-0 mt-2 text-xs text-app-muted">Resumen tomado del perfil del aprendiz para esta exportación.</p>
            <div class="m-0 mt-3 space-y-2 text-left text-sm text-app-text">
                <p class="m-0"><span class="font-medium text-app-muted">Aprendiz:</span> <?= e($nombreAprendiz) ?></p>
                <p class="m-0"><span class="font-medium text-app-muted">Documento:</span> <?= e($lineaDocumento) ?></p>
                <p class="m-0"><span class="font-medium text-app-muted">Empresa:</span> <?= e($lineaEmpresa) ?></p>
            </div>
        </section>

        <section class="<?= $card ?> w-full !p-6">
            <h3 class="m-0 text-left text-base font-semibold text-app-text">Seleccionar documentos a incluir</h3>
            <p class="m-0 mt-2 text-left text-xs text-app-muted">Por defecto se incluyen todos los bloques del formato. Desmarque los que no desee exportar.</p>
            <p id="export-partes-resumen" class="m-0 mt-2 text-left text-xs font-medium text-app-text" aria-live="polite"></p>
            <ul class="m-0 mt-4 list-none divide-y divide-app-border border-t border-app-border p-0 text-left">
                <li class="<?= e($rowClass) ?>">
                    <input id="parte-info" type="checkbox" name="partes[]" value="info" checked class="<?= e($chkClass) ?>">
                    <?php
                    $infoLabelClass = $docLabelClass . ($infoFaltantes > 0
                        ? ' peer-checked:[&_.doc-status]:text-amber-800 peer-checked:[&_.doc-link]:text-app-accent peer-checked:[&_.doc-link]:hover:text-app-accentStrong'
                        : ' peer-checked:[&_.doc-status]:text-emerald-700');
                    ?>
                    <label for="parte-info" class="<?= e($infoLabelClass) ?>">
                        <span class="doc-title font-medium">Información general</span>
                        <span class="doc-file"> (info.docx)</span>
                        <?php if ($infoFaltantes > 0): ?>
                            <span class="doc-status"> <?= e($infoEstado) ?> <a class="doc-link font-medium underline text-app-muted hover:text-app-text" href="<?= e(APP_BASE_PATH . '/documentos/info?aprendiz_id=' . $aprendizId) ?>">Abrir formulario</a></span>
                        <?php else: ?>
                            <span class="doc-status"><?= e($infoEstado !== '' ? ' ' . $infoEstado : '') ?></span>
                        <?php endif; ?>
                    </label>
                </li>
                <?php foreach ($exportMomentos as $m): ?>
                    <?php
                    $parteInputId = (string) ($m['input_id'] ?? ('m' . (string) ($m['id'] ?? '')));
                    $suffix = trim((string) ($m['status_suffix'] ?? ''));
                    $isPlaceholderTipo = str_starts_with((string) ($m['checkbox_value'] ?? ''), 'momento_tipo:');
                    $isPendiente = $isPlaceholderTipo || str_contains(strtolower($suffix), 'no iniciado');
                    $momentoUncheckedHint = $isPlaceholderTipo
                        ? ' — marque la casilla para incluir este bloque en la descarga'
                        : '';
                    $lineaMomento = str_replace('—', ' - ', (string) $m['label']);
                    $momentoLabelClass = $docLabelClass . ($isPendiente
                        ? ' peer-checked:[&_.doc-status]:text-app-muted'
                        : ' peer-checked:[&_.doc-status]:text-emerald-700');
                    ?>
                    <li class="<?= e($rowClass) ?>">
                        <input id="parte-<?= e($parteInputId) ?>" type="checkbox" name="partes[]" value="<?= e((string) $m['checkbox_value']) ?>" checked class="<?= e($chkClass) ?>">
                        <label for="parte-<?= e($parteInputId) ?>" class="<?= e($momentoLabelClass) ?>">
                            <span class="doc-title font-medium"><?= e($lineaMomento) ?></span>
                            <span class="doc-status"><?= e($suffix !== '' ? ' ' . $suffix : ' (sin estado)') ?><?= e($momentoUncheckedHint) ?></span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="<?= $card ?> w-full !p-6 text-left">
            <h3 class="m-0 text-left text-base font-semibold text-app-text">Formato de exportación</h3>
            <?php if ($pdfAvailable): ?>
                <p class="m-0 mt-2 text-sm text-app-muted">El PDF se genera con <strong class="font-medium text-app-text">LibreOffice</strong> en este equipo (equivalente a abrir el Word y exportar a PDF).</p>
            <?php else: ?>
                <p class="m-0 mt-2 rounded-lg border border-amber-500/70 bg-amber-100 px-3 py-2.5 text-sm font-medium text-amber-950"><?= e($pdfAvailabilityMessage !== '' ? $pdfAvailabilityMessage : 'La exportación PDF no está disponible en este equipo.') ?></p>
            <?php endif; ?>
            <fieldset class="m-0 mt-4 space-y-2 border-0 p-0">
                <legend class="sr-only">Formato de archivo</legend>
                <?php $radioFmt = 'mt-0.5 h-4 w-4 shrink-0 border-app-borderControlStrong text-app-accent focus:outline-none focus:ring-0 focus:ring-offset-0'; ?>
                <label class="flex cursor-pointer items-start gap-3 text-left text-sm text-app-text">
                    <input type="radio" name="formato" value="docx" checked class="<?= e($radioFmt) ?>">
                    <span>Microsoft Word (.docx)</span>
                </label>
                <label class="flex items-start gap-3 text-left text-sm <?= $pdfAvailable ? 'cursor-pointer text-app-text' : 'cursor-not-allowed text-app-muted' ?>">
                    <input type="radio" name="formato" value="pdf" class="<?= e($radioFmt) ?>"<?= $pdfAvailable ? '' : ' disabled' ?>>
                    <span>PDF (.pdf)<?= $pdfAvailable ? '' : ' — no disponible sin LibreOffice' ?></span>
                </label>
            </fieldset>
        </section>

        <button type="submit" class="<?= e(ui_button_primary_classes()) ?> w-full justify-center gap-2 py-3 text-app-textOnBrand">
            <span class="inline-flex h-5 w-5 shrink-0 [&_svg]:h-5 [&_svg]:w-5" aria-hidden="true"><?= ui_icon('file-spreadsheet') ?></span>
            Generar y descargar
        </button>
    </form>
    <script>
    (function () {
        var form = document.querySelector('[data-download-form][action*="documentos/generar"]');
        if (!form) return;
        var resumen = document.getElementById('export-partes-resumen');
        function updateResumen() {
            if (!resumen) return;
            var checked = form.querySelectorAll('input[name="partes[]"]:checked');
            if (!checked.length) {
                resumen.textContent = 'Ningún bloque seleccionado.';
                return;
            }
            var labels = [];
            checked.forEach(function (input) {
                var label = form.querySelector('label[for="' + input.id + '"] .doc-title');
                labels.push(label ? label.textContent.trim() : input.value);
            });
            resumen.textContent = 'Se incluirán ' + checked.length + ' bloque(s): ' + labels.join(', ') + '.';
        }
        form.querySelectorAll('input[name="partes[]"]').forEach(function (input) {
            input.addEventListener('change', updateResumen);
        });
        updateResumen();
    })();
    </script>
