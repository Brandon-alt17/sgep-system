<?php
declare(strict_types=1);

$pendientes = (array) ($pendientes ?? []);
$programas = (array) ($programas ?? []);
$defaultTd = function_exists('ui_td_classes') ? ui_td_classes() : 'border-b border-app-borderSoft px-2 text-left text-sm align-middle transition-colors duration-200 ease-in-out';
$tdClasses = isset($tdClasses) && (string) $tdClasses !== ''
    ? (string) $tdClasses
    : str_replace('h-[50px] ', '', $defaultTd);
?>
<?php if ($pendientes === []): ?>
    <tr class="h-[4.5rem]">
        <td class="<?= e($tdClasses) ?> px-5 pl-6 text-center align-middle text-app-muted" colspan="4"><?= e((string) ($emptyMessage ?? 'No hay pendientes por enlazar.')) ?></td>
    </tr>
<?php else: ?>
    <?php foreach ($pendientes as $row): ?>
        <tr class="border-b border-app-borderSoft">
            <td class="<?= e($tdClasses) ?> px-5 py-4 pl-6 align-top whitespace-normal break-words text-app-text"><?= e((string) ($row['nombre_aprendiz'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> px-5 py-4 align-top font-mono text-sm text-app-textSubtle"><?= e((string) ($row['numero_documento'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> px-5 py-4 align-top text-sm leading-snug text-app-text"><?= e((string) ($row['programa_fuente'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> relative z-0 overflow-visible px-5 py-4 pr-6 align-middle focus-within:z-30">
                <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/pendientes/resolver" class="flex flex-row flex-nowrap items-center justify-start gap-2">
                    <input type="hidden" name="pending_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                    <?php if (!empty($preserveImportNav) && trim((string) ($importId ?? '')) !== ''): ?>
                        <input type="hidden" name="from" value="import">
                        <input type="hidden" name="import_id" value="<?= e((string) $importId) ?>">
                    <?php endif; ?>
                    <?php
                    $comboboxOptions = [];
                    foreach ($programas as $programa) {
                        $pid = (int) ($programa['id'] ?? 0);
                        if ($pid <= 0) {
                            continue;
                        }
                        $pcod = trim((string) ($programa['codigo'] ?? ''));
                        $pnom = trim((string) ($programa['nombre'] ?? ''));
                        $label = $pcod !== '' ? ($pcod . ' — ' . $pnom) : ($pnom !== '' ? $pnom : 'Programa #' . $pid);
                        $comboboxOptions[] = [
                            'value' => (string) $pid,
                            'label' => $label,
                            'search' => trim($pcod . ' ' . $pnom . ' ' . $label),
                        ];
                    }
                    partial('components/combobox', [
                        'name' => 'programa_id',
                        'required' => true,
                        'placeholder' => 'Seleccionar programa…',
                        'options' => $comboboxOptions,
                    ]);
                    ?>
                    <button class="<?= e(ui_button_small_classes()) ?> inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap" type="submit">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                        Resolver
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
