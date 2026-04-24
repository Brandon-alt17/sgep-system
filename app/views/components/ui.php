<?php

declare(strict_types=1);

/**
 * Utilidades visuales centralizadas para reducir repeticion de clases Tailwind
 * y mantener consistencia entre todos los modulos.
 */
if (!function_exists('ui_card_classes')) {
    function ui_card_classes(): string
    {
        return 'rounded-[10px] border border-app-border bg-app-panel p-4 mp-4 shadow-xsSoft';
    }
}

if (!function_exists('ui_select_classes')) {
    function ui_select_classes(): string
    {
        return 'w-full rounded-lg border border-app-borderControlStrong bg-white bg-none px-3 py-2 pr-10 text-sm text-app-muted appearance-none outline-none transition-colors duration-200 hover:border-app-accent focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft [&>option]:bg-white [&>option]:text-app-muted';
    }
}

if (!function_exists('ui_select_wrapper_classes')) {
    function ui_select_wrapper_classes(): string
    {
        return 'relative block js-custom-select';
    }
}

if (!function_exists('ui_select_chevron_classes')) {
    function ui_select_chevron_classes(): string
    {
        return 'pointer-events-none absolute right-3 top-1/2 inline-flex h-4 w-4 -translate-y-1/2 text-app-muted transition-transform duration-200 ease-in-out';
    }
}

if (!function_exists('ui_card_mt_classes')) {
    function ui_card_mt_classes(): string
    {
        return 'mt-4' . ui_card_classes();
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
        return 'w-full rounded-lg border border-app-borderControlStrong px-3 py-2 text-sm text-app-text outline-none transition-colors duration-200 focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft';
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
        return 'w-full border-collapse [&>tbody>tr:hover>td]:bg-app-panelHover';
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

if (!function_exists('ui_render_pagination')) {
    /**
     * Renderiza paginación reutilizable con ancla opcional.
     *
     * @param int $currentPage Página actual (1-indexed)
     * @param int $totalPages Total de páginas
     * @param string $urlPattern Patrón URL con placeholder %d para el número de página
     * @param string $ariaLabel Etiqueta accesible del nav
     * @param string $anchor Id de ancla sin # (opcional)
     */
    function ui_render_pagination(
        int $currentPage,
        int $totalPages,
        string $urlPattern,
        string $ariaLabel = 'Paginación',
        string $anchor = ''
    ): void {
        if ($totalPages <= 1) {
            return;
        }

        $anchorSuffix = $anchor !== '' ? '#' . rawurlencode($anchor) : '';
        $prevUrl = sprintf($urlPattern, $currentPage - 1) . $anchorSuffix;
        $nextUrl = sprintf($urlPattern, $currentPage + 1) . $anchorSuffix;
        ?>
        <nav class="mt-4 flex items-center justify-center gap-2 text-sm" aria-label="<?= e($ariaLabel) ?>">
            <?php if ($currentPage > 1): ?>
                <a class="<?= e(ui_button_icon_classes()) ?>" href="<?= e($prevUrl) ?>" aria-label="Página anterior">
                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('arrow') ?></span>
                </a>
            <?php endif; ?>
            <span class="text-app-muted">Página <?= e((string) $currentPage) ?> de <?= e((string) $totalPages) ?></span>
            <?php if ($currentPage < $totalPages): ?>
                <a class="<?= e(ui_button_icon_classes()) ?>" href="<?= e($nextUrl) ?>" aria-label="Página siguiente">
                    <span class="inline-flex h-4 w-4 -scale-x-100 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('arrow') ?></span>
                </a>
            <?php endif; ?>
        </nav>
        <?php
    }
}

if (!function_exists('ui_document_type_label')) {
    function ui_document_type_label(?string $value): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return '';
        }

        $normalized = mb_strtolower($raw);
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'u'],
            $normalized
        );
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return match ($normalized) {
            'cc', 'cedula', 'cedula de', 'cedula de ciudadania' => 'Cédula de Ciudadanía',
            'ti', 'tarjeta', 'tarjeta de', 'tarjeta de identidad' => 'Tarjeta de Identidad',
            'ce' => 'Cédula de Extranjería',
            'pep' => 'PEP',
            'ppt' => 'PPT',
            default => $raw,
        };
    }
}
