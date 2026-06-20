<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Celdas de valor en la cabecera de momentos (fechas, modalidad, enlace, etc.).
 */
final class F023HeaderValueCellSupport
{
    /**
     * Reemplaza el contenido de la celda de valor por un único párrafo centrado.
     */
    public static function normalize(string $cellXml, string $macroRunXml): string
    {
        if (!preg_match('/^(<w:tc\b[^>]*>)(.*?)(<\/w:tc>)$/s', $cellXml, $parts)) {
            return $cellXml;
        }

        $inner = $parts[2];
        $tcPr = '';
        if (preg_match('/<w:tcPr>.*?<\/w:tcPr>/s', $inner, $match)) {
            $tcPr = self::ensureVerticalCenter((string) $match[0]);
        } else {
            $tcPr = '<w:tcPr><w:vAlign w:val="center"/></w:tcPr>';
        }

        $paragraph = '<w:p><w:pPr><w:spacing w:after="0" w:before="0"/>'
            . '<w:jc w:val="center"/></w:pPr>'
            . $macroRunXml
            . '</w:p>';

        return $parts[1] . $tcPr . $paragraph . $parts[3];
    }

    private static function ensureVerticalCenter(string $tcPr): string
    {
        if (preg_match('/<w:vAlign\b[^>]*\/>/', $tcPr)) {
            $updated = preg_replace('/<w:vAlign\b[^>]*\/>/', '<w:vAlign w:val="center"/>', $tcPr, 1);

            return is_string($updated) ? $updated : $tcPr;
        }

        $updated = preg_replace('/<\/w:tcPr>/', '<w:vAlign w:val="center"/></w:tcPr>', $tcPr, 1);

        return is_string($updated) ? $updated : $tcPr;
    }
}
