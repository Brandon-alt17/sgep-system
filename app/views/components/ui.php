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

/**
 * Card con cabecera y cuerpo separados (borde inferior en el header).
 * Uso: surface → header (+ stack opcional) → body.
 */
if (!function_exists('ui_card_surface_classes')) {
    function ui_card_surface_classes(): string
    {
        return 'overflow-hidden rounded-[10px] border border-app-border bg-app-panel shadow-xsSoft';
    }
}

if (!function_exists('ui_card_header_classes')) {
    function ui_card_header_classes(): string
    {
        return 'border-b border-app-border px-6 pt-6 pb-4';
    }
}

if (!function_exists('ui_card_header_stack_classes')) {
    function ui_card_header_stack_classes(): string
    {
        return 'flex flex-col gap-1.5';
    }
}

if (!function_exists('ui_card_body_classes')) {
    function ui_card_body_classes(): string
    {
        return 'p-6';
    }
}

/** Título dentro del header de card (sin margen inferior; el stack define el ritmo). */
if (!function_exists('ui_card_title_classes')) {
    function ui_card_title_classes(): string
    {
        return 'font-app m-0 text-[18px] font-semibold text-app-text';
    }
}

/** Subtítulo / descripción bajo el título en el header de card. */
if (!function_exists('ui_card_description_classes')) {
    function ui_card_description_classes(): string
    {
        return 'm-0 text-sm text-app-muted';
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

if (!function_exists('ui_button_icon_classes')) {
    function ui_button_icon_classes(): string
    {
        return 'font-app inline-flex h-10 w-10 items-center justify-center rounded-md border border-app-borderControl bg-app-panelSubtle text-app-muted no-underline hover:bg-app-accentSoft hover:text-app-accent';
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
        return 'mt-2.5 w-full border-collapse [&>tbody>tr:hover>td]:bg-app-panelHover';
    }
}

/** Tabla dentro del body de una card (sin margen superior; el padding del body alinea). */
if (!function_exists('ui_table_in_card_classes')) {
    function ui_table_in_card_classes(): string
    {
        return 'w-full border-collapse [&>tbody>tr:hover>td]:bg-app-panelHover';
    }
}

if (!function_exists('ui_th_classes')) {
    function ui_th_classes(): string
    {
        return 'h-[50px] border-b border-app-border px-2 text-left text-sm font-semibold text-app-muted align-middle transition-colors duration-200 ';
    }
}

if (!function_exists('ui_td_classes')) {
    function ui_td_classes(): string
    {
        return 'h-[50px] border-b border-app-borderSoft px-2 text-left text-sm align-middle transition-colors duration-200 ease-in-out';
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
