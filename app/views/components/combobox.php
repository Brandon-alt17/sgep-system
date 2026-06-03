<?php
declare(strict_types=1);

$name = (string) ($name ?? 'combobox_value');
$placeholder = (string) ($placeholder ?? 'Buscar...');
$required = (bool) ($required ?? false);
$value = trim((string) ($value ?? ''));
$options = is_array($options ?? null) ? (array) $options : [];
$valueInputAttrs = is_array($valueInputAttrs ?? null) ? (array) $valueInputAttrs : [];
$comboboxDropUp = (bool) ($comboboxDropUp ?? false);
$comboboxPreserveValueOnSearch = (bool) ($comboboxPreserveValueOnSearch ?? false);
// Misma apariencia que el select del catálogo (ui_select_classes: altura, texto, borde).
$fieldClasses = trim(ui_select_classes() . ' cursor-text');

$selectedLabel = '';
foreach ($options as $option) {
    if (!is_array($option)) {
        continue;
    }
    $optValue = trim((string) ($option['value'] ?? ''));
    if ($optValue !== '' && $optValue === $value) {
        $selectedLabel = trim((string) ($option['label'] ?? ''));
        break;
    }
}
$hasDisplayText = $selectedLabel !== '';

$valueInputAttrHtml = '';
foreach ($valueInputAttrs as $attrName => $attrValue) {
    $inputAttrName = trim((string) $attrName);
    if ($inputAttrName === '') {
        continue;
    }
    if (is_bool($attrValue)) {
        if ($attrValue) {
            $valueInputAttrHtml .= ' ' . e($inputAttrName);
        }
        continue;
    }
    $valueInputAttrHtml .= ' ' . e($inputAttrName) . '="' . e((string) $attrValue) . '"';
}
?>
<div class="relative min-w-0 block w-full" data-combobox-root<?= $comboboxDropUp ? ' data-combobox-drop-up="1"' : '' ?><?= $comboboxPreserveValueOnSearch ? ' data-combobox-preserve-value-on-search="1"' : '' ?>>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>" data-combobox-value <?= $required ? 'data-combobox-required="1"' : '' ?><?= $valueInputAttrHtml ?>>
    <div class="relative">
        <input
            type="text"
            value="<?= e($selectedLabel) ?>"
            placeholder="<?= e($placeholder) ?>"
            class="<?= e($fieldClasses) ?> pr-10"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
            data-combobox-input
        >
        <button
            type="button"
            class="<?= $hasDisplayText ? '' : 'hidden' ?> absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-sm text-app-muted hover:bg-app-panelSubtle hover:text-app-text"
            aria-label="Limpiar búsqueda"
            data-combobox-clear
        >&times;</button>
        <span
            class="<?= e(ui_select_chevron_classes()) ?> <?= $hasDisplayText ? 'hidden' : '' ?>"
            data-combobox-chevron
            aria-hidden="true"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>
        </span>
    </div>
    <div class="z-[100] mt-1 hidden overflow-hidden rounded-lg border border-app-borderControlStrong bg-white shadow-xsSoft" data-combobox-menu>
        <ul class="max-h-[210px] overflow-auto py-1" role="listbox">
            <?php foreach ($options as $option): ?>
                <?php
                if (!is_array($option)) {
                    continue;
                }
                $optValue = trim((string) ($option['value'] ?? ''));
                $optLabel = trim((string) ($option['label'] ?? ''));
                if ($optValue === '' || $optLabel === '') {
                    continue;
                }
                $searchText = trim((string) ($option['search'] ?? $optLabel));
                ?>
                <li>
                    <button
                        type="button"
                        class="w-full px-3 py-2 text-left text-sm text-app-muted transition-colors duration-150 hover:bg-app-accentSoft hover:text-app-text"
                        data-combobox-option
                        data-value="<?= e($optValue) ?>"
                        data-label="<?= e($optLabel) ?>"
                        data-search="<?= e($searchText) ?>"
                        role="option"
                    ><?= e($optLabel) ?></button>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="hidden px-3 py-2 text-sm text-app-muted" data-combobox-empty>No hay resultados.</p>
    </div>
</div>
