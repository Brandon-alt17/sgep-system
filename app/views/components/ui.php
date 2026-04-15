<?php

declare(strict_types=1);

/**
 * Utilidades visuales centralizadas para reducir repeticion de clases Tailwind
 * y mantener consistencia entre todos los modulos.
 */
if (!function_exists('ui_card_classes')) {
    function ui_card_classes(): string
    {
        return 'rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft';
    }
}

if (!function_exists('ui_label_classes')) {
    function ui_label_classes(): string
    {
        return 'block text-sm font-medium text-gray-700';
    }
}

if (!function_exists('ui_input_classes')) {
    function ui_input_classes(): string
    {
        return 'mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm';
    }
}

if (!function_exists('ui_button_primary_classes')) {
    function ui_button_primary_classes(): string
    {
        return 'inline-flex rounded-md border border-app-accent bg-app-accent px-4 py-2 text-sm font-medium text-white no-underline hover:bg-[#0e8f82]';
    }
}

if (!function_exists('ui_table_classes')) {
    function ui_table_classes(): string
    {
        return 'mt-2.5 w-full border-collapse';
    }
}

if (!function_exists('ui_th_classes')) {
    function ui_th_classes(): string
    {
        return 'border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted';
    }
}

if (!function_exists('ui_td_classes')) {
    function ui_td_classes(): string
    {
        return 'border-b border-app-border px-2 py-2.5 text-left text-xs';
    }
}
