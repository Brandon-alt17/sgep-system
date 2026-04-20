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

if (!function_exists('ui_card_mt_classes')) {
    function ui_card_mt_classes(): string
    {
        return 'mt-4 ' . ui_card_classes();
    }
}

if (!function_exists('ui_label_classes')) {
    function ui_label_classes(): string
    {
        return 'block text-sm font-medium text-app-textSubtle';
    }
}

if (!function_exists('ui_input_classes')) {
    function ui_input_classes(): string
    {
        return 'mt-1 w-full rounded-lg border border-app-borderControlStrong px-3 py-2 text-sm';
    }
}

if (!function_exists('ui_button_primary_classes')) {
    function ui_button_primary_classes(): string
    {
        return 'font-app inline-flex rounded-md border border-app-accent bg-app-accent px-4 py-2 text-sm font-medium text-app-text no-underline hover:bg-app-accentHover';
    }
}

if (!function_exists('ui_button_small_classes')) {
    function ui_button_small_classes(): string
    {
        return 'font-app inline-flex rounded-md border border-app-borderControl bg-app-panelSubtle px-[18px] py-2 text-sm font-medium text-app-muted no-underline hover:bg-app-accentSoft hover:text-app-accent';
    }
}

if (!function_exists('ui_button_small_primary_classes')) {
    function ui_button_small_primary_classes(): string
    {
        return 'font-app cursor-pointer rounded-md border border-app-accent bg-app-accent px-2.5 py-1.5 text-xs text-app-textOnBrand hover:bg-app-accentHover';
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

if (!function_exists('ui_heading_sm_classes')) {
    function ui_heading_sm_classes(): string
    {
        return 'font-app mb-2 mt-0 text-[18px] font-semibold';
    }
}

if (!function_exists('ui_text_muted_classes')) {
    function ui_text_muted_classes(): string
    {
        return 'mb-2 mt-0 text-sm text-app-muted';
    }
}

if (!function_exists('ui_badge_success_classes')) {
    function ui_badge_success_classes(): string
    {
        return 'rounded-full border border-app-borderSuccess bg-app-successBg px-2 py-0.5 text-[11px] font-semibold text-app-successText';
    }
}

if (!function_exists('ui_badge_warning_classes')) {
    function ui_badge_warning_classes(): string
    {
        return 'rounded-full border border-app-borderWarning bg-app-warningBg px-2 py-0.5 text-[11px] font-semibold text-app-warningText';
    }
}

if (!function_exists('ui_badge_error_classes')) {
    function ui_badge_error_classes(): string
    {
        return 'rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700';
    }
}

if (!function_exists('ui_warning_card_classes')) {
    function ui_warning_card_classes(): string
    {
        return 'rounded-[10px] border border-app-borderWarning bg-app-warningBg p-4 text-sm text-app-warningText';
    }
}
