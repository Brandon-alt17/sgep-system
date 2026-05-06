<?php
declare(strict_types=1);

/**
 * Buscador en vivo (cliente): mismo patrón que competencias en ficha de programa.
 *
 * @param string      $placeholder      Texto del placeholder del input
 * @param string      $items_target     Selector CSS del contenedor de ítems (p. ej. tbody#id).
 *                                       Si está vacío, se usa el hermano/padre con [data-live-filter-items] (compatibilidad).
 * @param string      $aria_label       Etiqueta accesible; por defecto el placeholder
 * @param string      $empty_message    Mensaje cuando no hay coincidencias
 * @param 'card'|'plain' $variant       'card' = sección con tarjeta UI; 'plain' = bloque para incrustar en otro contenedor
 * @param string      $class            Clases extra en el nodo raíz (espaciado, flex, etc.)
 * @param bool        $show_empty_state Si mostrar el aviso de “sin coincidencias”
 */
$placeholder = (string) ($placeholder ?? 'Buscar');
$itemsTarget = trim((string) ($items_target ?? ''));
$ariaLabel = trim((string) ($aria_label ?? ''));
if ($ariaLabel === '') {
    $ariaLabel = $placeholder;
}
$emptyMessage = (string) ($empty_message ?? 'No hay coincidencias para la búsqueda.');
$variant = strtolower(trim((string) ($variant ?? 'card')));
if ($variant !== 'plain') {
    $variant = 'card';
}
$extraClass = trim((string) ($class ?? ''));
$showEmpty = !isset($show_empty_state) || (bool) $show_empty_state;

$itemsTargetAttr = $itemsTarget !== '' ? ' data-live-filter-items-target="' . e($itemsTarget) . '"' : '';

if ($variant === 'plain') {
    $rootTag = 'div';
    $baseClasses = trim('relative min-w-0 ' . $extraClass);
} else {
    $rootTag = 'section';
    $baseClasses = trim(ui_card_classes() . ' mt-6 ' . $extraClass);
}
?>
<<?= $rootTag ?> class="<?= e($baseClasses) ?>" data-live-filter-root<?= $itemsTargetAttr ?>>
    <div class="relative">
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
            <?= ui_icon('search') ?>
        </span>
        <input
            type="text"
            class="<?= e(ui_input_classes()) ?> pl-10 pr-10"
            placeholder="<?= e($placeholder) ?>"
            aria-label="<?= e($ariaLabel) ?>"
            data-live-filter-input
            autocomplete="off"
        >
        <button
            type="button"
            class="hidden absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-sm text-app-muted hover:bg-app-panelSubtle hover:text-app-text"
            aria-label="Limpiar búsqueda"
            data-live-filter-clear
        >&times;</button>
    </div>
    <?php if ($showEmpty): ?>
        <p class="mt-2 hidden rounded-md border border-app-border bg-app-panelSubtle p-3 text-sm text-app-muted" data-live-filter-empty>
            <?= e($emptyMessage) ?>
        </p>
    <?php endif; ?>
</<?= $rootTag ?>>
