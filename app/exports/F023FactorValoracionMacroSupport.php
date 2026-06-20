<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Marcadores de valoración (X) y observaciones en tablas de factores M2 / M3 p1.
 */
final class F023FactorValoracionMacroSupport
{
    public static function injectIntoDocumentXml(string $xml): string
    {
        $xml = self::injectFactorValoracionMacros($xml);
        $xml = self::stripPostTableSectionBreakParagraph($xml);
        $xml = self::collapseRedundantParagraphsBeforeFooter($xml);
        $xml = self::normalizeFooterSpacing($xml);

        return $xml;
    }

    private static function injectFactorValoracionMacros(string $xml): string
    {
        $cfg = require base_path('config/factores.php');
        /** @var list<string> $names */
        $names = array_merge($cfg['tecnicos'], $cfg['actitudinales']);

        if (!preg_match_all('/<w:tr\b[^>]*>.*?<\/w:tr>/s', $xml, $matches, PREG_OFFSET_CAPTURE)) {
            return $xml;
        }

        /** @var list<array{offset: int, length: int, new: string}> $replacements */
        $replacements = [];
        $searchFrom = 0;

        foreach ($names as $index => $name) {
            $rowMatches = $matches[0];
            for ($r = $searchFrom, $n = count($rowMatches); $r < $n; $r++) {
                $rowXml = (string) $rowMatches[$r][0];
                $offset = (int) $rowMatches[$r][1];
                $plain = preg_replace('/\s+/u', ' ', strip_tags($rowXml)) ?? '';
                if (!is_string($plain) || stripos($plain, $name) === false) {
                    continue;
                }

                $replacements[] = [
                    'offset' => $offset,
                    'length' => strlen($rowXml),
                    'new' => self::patchFactorRow($rowXml, $index),
                ];
                $searchFrom = $r + 1;

                break;
            }
        }

        usort($replacements, static fn (array $a, array $b): int => $b['offset'] <=> $a['offset']);
        foreach ($replacements as $rep) {
            $xml = substr_replace($xml, $rep['new'], $rep['offset'], $rep['length']);
        }

        return $xml;
    }

    private static function patchFactorRow(string $rowXml, int $factorIndex): string
    {
        $cellIndex = 0;

        $patched = preg_replace_callback(
            '/<w:tc\b[^>]*>.*?<\/w:tc>/s',
            static function (array $match) use ($factorIndex, &$cellIndex): string {
                $cell = $match[0];
                ++$cellIndex;

                return match ($cellIndex) {
                    2 => self::injectMacroInCell($cell, 'factor_' . $factorIndex . '_valoracion_s'),
                    3 => self::injectMacroInCell($cell, 'factor_' . $factorIndex . '_valoracion_pm'),
                    4 => self::normalizeObservationCellLeft(
                        self::injectMacroInCell($cell, 'factor_' . $factorIndex . '_observacion', true)
                    ),
                    default => $cell,
                };
            },
            $rowXml
        );

        return is_string($patched) ? $patched : $rowXml;
    }

    private static function injectMacroInCell(string $cellXml, string $macro, bool $stripEmptyBorders = false): string
    {
        if (!preg_match('/^(<w:tc\b[^>]*>)(.*?)(<\/w:tc>)$/s', $cellXml, $parts)) {
            return $cellXml;
        }

        $inner = $parts[2];
        if (preg_match('/<w:p\b[^>]*>.*?<\/w:p>/s', $inner, $paragraphMatch, PREG_OFFSET_CAPTURE)) {
            $originalParagraph = (string) $paragraphMatch[0][0];
            $paragraphPos = (int) $paragraphMatch[0][1];
            $paragraph = $originalParagraph;
            if ($stripEmptyBorders) {
                $paragraph = self::stripEmptyParagraphBorders($paragraph);
                $paragraph = self::ensureParagraphLeftAlign($paragraph);
            }
            $newParagraph = preg_replace(
                '/<\/w:p>$/',
                self::macroRunXml($macro) . '</w:p>',
                $paragraph,
                1
            );
            if (is_string($newParagraph)) {
                $inner = substr_replace($inner, $newParagraph, $paragraphPos, strlen($originalParagraph));
            }
        } else {
            $jc = $stripEmptyBorders ? 'left' : 'center';
            $inner .= '<w:p><w:pPr><w:spacing w:after="0" w:before="0"/>'
                . '<w:jc w:val="' . $jc . '"/></w:pPr>'
                . self::macroRunXml($macro)
                . '</w:p>';
        }

        return $parts[1] . $inner . $parts[3];
    }

    private static function stripEmptyParagraphBorders(string $paragraphXml): string
    {
        $stripped = preg_replace('/<w:pBdr>\s*<\/w:pBdr>/', '', $paragraphXml);

        return is_string($stripped) ? $stripped : $paragraphXml;
    }

    private static function normalizeObservationCellLeft(string $cellXml): string
    {
        $normalized = preg_replace_callback(
            '/<w:p\b[^>]*>.*?<\/w:p>/s',
            static fn (array $match): string => self::ensureParagraphLeftAlign((string) $match[0]),
            $cellXml
        );

        return is_string($normalized) ? $normalized : $cellXml;
    }

    private static function ensureParagraphLeftAlign(string $paragraphXml): string
    {
        if (preg_match('/<w:jc\b[^>]*>/', $paragraphXml)) {
            $aligned = preg_replace(
                '/<w:jc\b[^>]*\/?>/',
                '<w:jc w:val="left"/>',
                $paragraphXml,
                1
            );

            return is_string($aligned) ? $aligned : $paragraphXml;
        }

        if (preg_match('/<w:pPr>/', $paragraphXml)) {
            $aligned = preg_replace('/<w:pPr>/', '<w:pPr><w:jc w:val="left"/>', $paragraphXml, 1);

            return is_string($aligned) ? $aligned : $paragraphXml;
        }

        $aligned = preg_replace('/<w:p>/', '<w:p><w:pPr><w:jc w:val="left"/></w:pPr>', $paragraphXml, 1);

        return is_string($aligned) ? $aligned : $paragraphXml;
    }

    private static function macroRunXml(string $macro): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:b/><w:bCs/><w:color w:val="000000"/>'
            . '<w:sz w:val="18"/><w:szCs w:val="18"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">${' . $safe . '}</w:t></w:r>';
    }

    private static function stripPostTableSectionBreakParagraph(string $xml): string
    {
        $lastTableEnd = strrpos($xml, '</w:tbl>');
        if ($lastTableEnd === false) {
            return $xml;
        }
        $lastTableEnd += strlen('</w:tbl>');

        if (!preg_match('/^\s*(<w:p\b[^>]*>.*?<\/w:p>)/s', substr($xml, $lastTableEnd), $match)) {
            return $xml;
        }

        $paragraph = $match[1];
        if (!str_contains($paragraph, '<w:sectPr')) {
            return $xml;
        }

        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($paragraph)) ?? '');
        if ($plain !== '') {
            return $xml;
        }

        return substr($xml, 0, $lastTableEnd) . substr($xml, $lastTableEnd + strlen($match[0]));
    }

    private static function normalizeFooterSpacing(string $xml): string
    {
        $lastTableEnd = strrpos($xml, '</w:tbl>');
        if ($lastTableEnd === false) {
            return $xml;
        }

        $tail = substr($xml, $lastTableEnd);
        $normalized = str_replace('<w:spacing />', '<w:spacing w:after="0" w:before="0"/>', $tail);

        return substr($xml, 0, $lastTableEnd) . $normalized;
    }

    private static function collapseRedundantParagraphsBeforeFooter(string $xml): string
    {
        $lastTableEnd = strrpos($xml, '</w:tbl>');
        if ($lastTableEnd === false) {
            return $xml;
        }
        $lastTableEnd += strlen('</w:tbl>');

        foreach (['Ciudad______', 'Ciudad ', 'de diligenciamiento', 'El momento 3'] as $anchor) {
            $pos = strpos($xml, $anchor, $lastTableEnd);
            if ($pos === false) {
                continue;
            }

            $pStart = max(
                (int) strrpos(substr($xml, $lastTableEnd, $pos - $lastTableEnd), '<w:p '),
                (int) strrpos(substr($xml, $lastTableEnd, $pos - $lastTableEnd), '<w:p>')
            );
            if ($pStart < 0) {
                continue;
            }
            $pStart += $lastTableEnd;

            $between = substr($xml, $lastTableEnd, $pStart - $lastTableEnd);
            $collapsed = preg_replace('/<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>/s', '', $between) ?? $between;
            $xml = substr($xml, 0, $lastTableEnd) . $collapsed . substr($xml, $pStart);

            break;
        }

        return $xml;
    }
}
