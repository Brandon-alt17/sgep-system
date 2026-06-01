<?php
declare(strict_types=1);

$conflictRows = (array) ($conflictRows ?? []);
$pendingFieldLabels = (array) ($pendingFieldLabels ?? []);
?>
<?php foreach ($conflictRows as $conflictRow): ?>
    <?php
    $conflictAprendizId = (int) ($conflictRow['aprendiz_id'] ?? 0);
    if ($conflictAprendizId <= 0) {
        continue;
    }
    $conflicts = (array) ($conflictRow['conflicts'] ?? []);
    $conflictName = trim((string) ($conflictRow['nombre'] ?? ''));
    $conflictDoc = trim((string) ($conflictRow['identificacion'] ?? ''));
    $conflictIncomingJefeId = (int) ($conflictRow['incoming_jefe_id'] ?? 0);
    $conflictEmpresaId = (int) ($conflictRow['empresa_id'] ?? 0);
    ?>
    <div id="modal-conflict-<?= e((string) $conflictAprendizId) ?>" class="modal-overlay hidden" data-modal-overlay="conflict-<?= e((string) $conflictAprendizId) ?>" data-incoming-jefe-id="<?= e((string) $conflictIncomingJefeId) ?>" data-empresa-id="<?= e((string) $conflictEmpresaId) ?>">
        <div class="modal-panel modal-panel--conflict bg-app-panel" role="dialog" aria-modal="true" aria-labelledby="conflict-modal-title-<?= e((string) $conflictAprendizId) ?>">
            <div class="mb-3 flex shrink-0 items-center justify-between gap-3">
                <div>
                    <h3 id="conflict-modal-title-<?= e((string) $conflictAprendizId) ?>" class="m-0 cursor-default select-none text-lg font-semibold text-app-text">Resolución de conflictos</h3>
                    <p class="mt-1 inline-flex cursor-default select-none items-center gap-1.5 text-sm text-app-muted">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center [&_svg]:h-5 [&_svg]:w-5"><?= ui_icon('user') ?></span>
                        <span><?= e($conflictName) ?> &mdash; <?= e($conflictDoc) ?></span>
                    </p>
                </div>
                <button type="button" class="<?= e(ui_button_icon_classes()) ?>" data-modal-close="conflict-<?= e((string) $conflictAprendizId) ?>" aria-label="Cerrar modal de conflictos">
                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                </button>
            </div>
            <p class="mb-3 mt-0 shrink-0 cursor-default select-none text-sm text-app-muted">Estos cambios están en conflicto y no se actualizaron automáticamente. Selecciona si conservar el valor actual o tomar el nuevo por cada campo.</p>
            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-app-border bg-white/50">
                <div class="conflict-table">
                    <div class="conflict-table__header">
                        <div>Campo</div>
                        <div>Actual</div>
                        <div>Nuevo archivo</div>
                        <div class="text-right">Acción</div>
                    </div>
                    <?php foreach ($conflicts as $conflict): ?>
                        <?php $fieldKey = (string) ($conflict['field'] ?? ''); ?>
                        <?php
                        $actualConflictValue = (string) ($conflict['actual'] ?? '');
                        $newConflictValue = (string) ($conflict['nuevo'] ?? '');
                        $newConflictRaw = (string) ($conflict['nuevo_raw'] ?? $conflict['nuevo'] ?? '');
                        $newConflictSub = trim((string) ($conflict['nuevo_sub'] ?? ''));
                        if ($fieldKey === 'tipo_documento') {
                            $actualConflictValue = ui_document_type_label($actualConflictValue);
                            $newConflictValue = ui_document_type_label($newConflictValue);
                            $newConflictRaw = (string) ($conflict['nuevo'] ?? '');
                            $newConflictSub = '';
                        }
                        $conflictField = (string) ($conflict['field'] ?? '');
                        $encodedNewValue = rawurlencode($newConflictRaw);
                        $fieldLabel = (string) ($pendingFieldLabels[$fieldKey] ?? $fieldKey);
                        ?>
                        <div
                            class="conflict-table__row"
                            data-conflict-row
                            data-conflict-modal="conflict-<?= e((string) $conflictAprendizId) ?>"
                            data-aprendiz-id="<?= e((string) $conflictAprendizId) ?>"
                            data-conflict-field="<?= e($conflictField) ?>"
                            data-conflict-value="<?= e($encodedNewValue) ?>"
                        >
                            <div class="conflict-table__field select-none"><?= e($fieldLabel) ?></div>
                            <div class="conflict-value conflict-value--current">
                                <p class="conflict-value__text select-none" data-conflict-current-text><?= e($actualConflictValue) ?></p>
                            </div>
                            <div class="conflict-value conflict-value--new">
                                <p class="conflict-value__text select-none" data-conflict-new-text><?= e($newConflictValue) ?></p>
                                <?php if ($newConflictSub !== ''): ?>
                                    <p class="conflict-value__sub select-none"><?= e($newConflictSub) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="conflict-table__actions">
                                <div class="conflict-table__actions-inner">
                                    <div class="flex flex-col items-end gap-2">
                                        <button type="button" class="inline-flex w-full cursor-pointer select-none justify-center rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 sm:w-auto sm:min-w-[9.5rem]" data-conflict-action-button="current">Mantener actual</button>
                                        <button type="button" class="inline-flex w-full cursor-pointer select-none justify-center rounded-md border border-app-accent bg-app-accent px-3 py-1.5 text-xs font-medium text-app-textOnBrand hover:bg-app-accentHover sm:w-auto sm:min-w-[9.5rem]" data-conflict-action-button="new">Aceptar nuevo</button>
                                    </div>
                                    <span class="text-right text-xs text-app-muted" data-conflict-status></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mt-5 flex shrink-0 flex-wrap items-center justify-end gap-3 border-t border-app-borderSoft pt-4">
                <p class="mr-auto mb-0 hidden cursor-default select-none text-xs text-app-muted sm:block">Aplica la misma decisión a todos los campos en conflicto de este aprendiz.</p>
                <button type="button" class="inline-flex cursor-pointer select-none rounded-md border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100" data-conflict-bulk="current" data-conflict-modal="conflict-<?= e((string) $conflictAprendizId) ?>">Mantener todos los actuales</button>
                <button type="button" class="inline-flex cursor-pointer select-none rounded-md border border-app-accent bg-app-accent px-4 py-2 text-sm font-medium text-app-textOnBrand hover:bg-app-accentHover" data-conflict-bulk="new" data-conflict-modal="conflict-<?= e((string) $conflictAprendizId) ?>">Aceptar todos los nuevos</button>
            </div>
        </div>
    </div>
<?php endforeach; ?>
