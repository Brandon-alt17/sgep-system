<?php
declare(strict_types=1);

$conflictRows = (array) ($conflictRows ?? []);
$tdClasses = (string) ($tdClasses ?? ui_td_classes());
$emptyMessage = trim((string) ($emptyMessage ?? 'No hay aprendices con conflictos pendientes.'));
?>
<?php if ($conflictRows === []): ?>
    <tr class="h-[4.5rem]">
        <td class="<?= e($tdClasses) ?> px-5 pl-6 text-center align-middle text-app-muted" colspan="4"><?= e($emptyMessage) ?></td>
    </tr>
<?php else: ?>
    <?php foreach ($conflictRows as $conflictRow): ?>
        <?php
        $conflictAprendizId = (int) ($conflictRow['aprendiz_id'] ?? 0);
        if ($conflictAprendizId <= 0) {
            continue;
        }
        $conflictCount = count((array) ($conflictRow['conflicts'] ?? []));
        $conflictName = trim((string) ($conflictRow['nombre'] ?? ''));
        $conflictDoc = trim((string) ($conflictRow['identificacion'] ?? ''));
        ?>
        <tr
            class="cursor-pointer hover:bg-app-panelSubtle"
            data-conflict-aprendiz-row
            data-aprendiz-id="<?= e((string) $conflictAprendizId) ?>"
            data-conflict-name="<?= e($conflictName !== '' ? $conflictName : 'Aprendiz') ?>"
            data-modal-open="conflict-<?= e((string) $conflictAprendizId) ?>"
            tabindex="0"
            role="button"
        >
            <td class="<?= e($tdClasses) ?> px-5 py-4 pl-6 align-middle select-none"><?= e($conflictName !== '' ? $conflictName : '—') ?></td>
            <td class="<?= e($tdClasses) ?> px-5 py-4 align-middle select-none"><?= e($conflictDoc !== '' ? $conflictDoc : '—') ?></td>
            <td class="<?= e($tdClasses) ?> px-5 py-4 align-middle select-none">
                <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">
                    <?= e((string) $conflictCount) ?> <?= $conflictCount === 1 ? 'conflicto' : 'conflictos' ?>
                </span>
            </td>
            <td class="<?= e($tdClasses) ?> px-5 py-4 pr-6 align-middle">
                <span class="text-sm font-medium text-app-accentStrong">Revisar conflictos</span>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
