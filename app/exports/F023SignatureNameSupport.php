<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Inserta ${nombre_aprendiz} y ${nombre_instructor_seguimiento} sobre la línea de firma.
 */
final class F023SignatureNameSupport
{
    public static function injectIntoDocumentXml(string $xml, string $nameAlignment = 'center'): string
    {
        $nameAlignment = $nameAlignment === 'left' ? 'left' : 'center';
        $offset = 0;
        while (preg_match('/<w:tbl\b/', $xml, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $tblStart = (int) $match[0][1];
            $tblEnd = strpos($xml, '</w:tbl>', $tblStart);
            if ($tblEnd === false) {
                break;
            }
            $tblEnd += strlen('</w:tbl>');
            $table = substr($xml, $tblStart, $tblEnd - $tblStart);
            $patched = self::injectInTable($table, $nameAlignment);
            if ($patched !== $table) {
                $xml = substr($xml, 0, $tblStart) . $patched . substr($xml, $tblEnd);
            }
            $offset = $tblStart + strlen($patched);
        }

        return $xml;
    }

    private static function injectInTable(string $table, string $nameAlignment): string
    {
        if (!preg_match_all('/<w:tr\b[^>]*>.*?<\/w:tr>/s', $table, $rows, PREG_OFFSET_CAPTURE)) {
            return $table;
        }

        $labelRowIndex = null;
        foreach ($rows[0] as $index => $rowMatch) {
            if (self::isSignatureLabelRow((string) $rowMatch[0])) {
                $labelRowIndex = $index;
                break;
            }
        }

        if ($labelRowIndex === null || $labelRowIndex === 0) {
            return $table;
        }

        $lineRow = (string) $rows[0][$labelRowIndex - 1][0];
        $lineRowStart = (int) $rows[0][$labelRowIndex - 1][1];
        $newLineRow = self::patchSignatureLineRow($lineRow, $nameAlignment);
        if ($newLineRow === $lineRow) {
            return $table;
        }

        return substr_replace($table, $newLineRow, $lineRowStart, strlen($lineRow));
    }

    private static function isSignatureLabelRow(string $rowXml): bool
    {
        $plain = self::normalizePlainText($rowXml);

        return str_contains($plain, 'Firma del aprendiz')
            && stripos($plain, 'instructor') !== false
            && stripos($plain, 'seguimiento') !== false;
    }

    private static function patchSignatureLineRow(string $rowXml, string $nameAlignment): string
    {
        if (!preg_match_all('/<w:tc\b[^>]*>.*?<\/w:tc>/s', $rowXml, $cells, PREG_OFFSET_CAPTURE)) {
            return $rowXml;
        }

        /** @var list<array{start: int, length: int, new: string}> $replacements */
        $replacements = [];
        $macros = [
            0 => 'nombre_aprendiz',
            2 => 'nombre_instructor_seguimiento',
            4 => 'nombre_coformador',
        ];

        foreach ($macros as $cellIndex => $macro) {
            if (!isset($cells[0][$cellIndex])) {
                continue;
            }

            $cell = (string) $cells[0][$cellIndex][0];
            if (str_contains($cell, '${' . $macro . '}')) {
                continue;
            }

            $newCell = self::injectMacroInSignatureLineCell($cell, $macro, $nameAlignment);
            if ($newCell === $cell) {
                continue;
            }

            $replacements[] = [
                'start' => (int) $cells[0][$cellIndex][1],
                'length' => strlen($cell),
                'new' => $newCell,
            ];
        }

        if ($replacements === []) {
            return $rowXml;
        }

        usort($replacements, static fn (array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($replacements as $rep) {
            $rowXml = substr_replace($rowXml, $rep['new'], $rep['start'], $rep['length']);
        }

        return $rowXml;
    }

    private static function injectMacroInSignatureLineCell(string $cellXml, string $macro, string $nameAlignment): string
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
            . '<w:jc w:val="' . $nameAlignment . '"/></w:pPr>'
            . self::macroRunXml($macro, $nameAlignment === 'left')
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

    private static function macroRunXml(string $macro, bool $italic = false): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';
        $italicPr = $italic ? '<w:i/><w:iCs/>' : '';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . $italicPr
            . '<w:sz w:val="18"/><w:szCs w:val="18"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">${' . $safe . '}</w:t></w:r>';
    }

    private static function normalizePlainText(string $xml): string
    {
        return preg_replace('/\s+/u', ' ', trim(strip_tags($xml)) ?? '') ?? '';
    }
}
