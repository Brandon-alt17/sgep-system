<?php

declare(strict_types=1);

if (!function_exists('ui_icon')) {
    /**
     * Carga iconos SVG desde app/views/components/Icons/<nombre>.svg
     * con cache en memoria por request.
     */
    function ui_icon(string $name): string
    {
        static $cache = [];

        if (isset($cache[$name])) {
            return $cache[$name];
        }

        $path = base_path('app/views/components/Icons/' . $name . '.svg');
        if (!is_file($path)) {
            $cache[$name] = '';
            return '';
        }

        $content = file_get_contents($path);
        $cache[$name] = $content === false ? '' : trim($content);

        return $cache[$name];
    }
}
