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
    <tr>
        <td class="<?= e($tdClasses) ?> px-5 py-6 pl-6 text-app-muted" colspan="4">No hay pendientes por enlazar.</td>
    </tr>
<?php else: ?>
    <?php foreach ($pendientes as $row): ?>
        <tr class="border-b border-app-borderSoft">
            <td class="<?= e($tdClasses) ?> px-5 py-4 pl-6 align-top whitespace-normal break-words text-app-text"><?= e((string) ($row['nombre_aprendiz'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> px-5 py-4 align-top font-mono text-sm text-app-textSubtle"><?= e((string) ($row['numero_documento'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> px-5 py-4 align-top text-sm leading-snug text-app-text"><?= e((string) ($row['programa_fuente'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> relative z-0 px-5 py-4 pr-6 align-middle focus-within:z-30">
                <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/pendientes/resolver" class="flex flex-row flex-nowrap items-center justify-start gap-2">
                    <input type="hidden" name="pending_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                    <span class="<?= e(ui_select_wrapper_classes()) ?> min-w-0 flex-1">
                        <select name="programa_id" required class="<?= e(ui_select_classes()) ?> text-xs" autocomplete="off">
                            <option value="">Seleccionar programa…</option>
                            <?php foreach ($programas as $programa): ?>
                                <?php
                                $pid = (int) ($programa['id'] ?? 0);
                                $pcod = trim((string) ($programa['codigo'] ?? ''));
                                $pnom = trim((string) ($programa['nombre'] ?? ''));
                                $label = $pcod !== '' ? ($pcod . ' — ' . $pnom) : $pnom;
                                ?>
                                <option value="<?= $pid ?>"><?= e($label !== '' ? $label : 'Programa #' . $pid) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
                        </span>
                    </span>
                    <button class="<?= e(ui_button_small_classes()) ?> inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap" type="submit">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                        Resolver
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
