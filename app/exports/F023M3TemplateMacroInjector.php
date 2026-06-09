<?php

declare(strict_types=1);

namespace App\Exports;

use ZipArchive;

/**
 * Inserta marcadores ${variable} en m3_p1.docx / m3_p2.docx y ajusta layout para LibreOffice/PDF.
 */
final class F023M3TemplateMacroInjector
{
    private const DOCUMENT_XML = 'word/document.xml';

    private const CELL_BORDERS = '<w:tcBorders>'
        . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
        . '</w:tcBorders>';

    /**
     * @throws \RuntimeException
     */
    public static function patchToTemp(string $sourceDocx): string
    {
        if (!is_file($sourceDocx)) {
            throw new \RuntimeException('Plantilla M3 no encontrada.');
        }

        $base = strtolower(basename($sourceDocx));
        $part = str_contains($base, 'm3_p2') ? 'p2' : 'p1';

        $tmp = tempnam(sys_get_temp_dir(), 'f023m3_');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal.');
        }
        $tmpDocx = $tmp . '.docx';
        if (!@copy($sourceDocx, $tmpDocx)) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo copiar la plantilla M3.');
        }
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmpDocx) !== true) {
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo abrir la copia temporal de M3.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('document.xml no legible en plantilla M3.');
        }

        if ($part === 'p1') {
            foreach (self::p1InjectionSpecs() as $spec) {
                $xml = self::injectAfterAnchor(
                    $xml,
                    (string) $spec['anchor'],
                    (int) $spec['occ'],
                    (string) $spec['macro']
                );
            }
            $xml = self::ensureFactorTableBorders($xml);
            $xml = F023FactorValoracionMacroSupport::injectIntoDocumentXml($xml);
        } else {
            $xml = self::normalizeFooterParagraphs($xml);
            $xml = self::insertSpacerParagraphsBeforeFooter($xml, 3);
            $xml = self::shrinkSignatureRowHeights($xml);
            $xml = self::removeTrailingEmptyParagraphsAfterFooter($xml);
        }

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo escribir document.xml en la plantilla temporal M3.');
        }
        $zip->close();

        return $tmpDocx;
    }

    /**
     * @return list<array{anchor: string, occ: int, macro: string}>
     */
    private static function p1InjectionSpecs(): array
    {
        return [
            ['anchor' => '(DD/MM/AA)</w:t>', 'occ' => 0, 'macro' => 'fecha_inicio_etapa'],
            ['anchor' => 'DD/MM/AA</w:t>', 'occ' => 0, 'macro' => 'fecha_fin_etapa'],
            ['anchor' => 'Número de visitas realizadas en toda la etapa productiva:</w:t>', 'occ' => 0, 'macro' => 'numero_visitas_realizadas'],
            ['anchor' => 'Enlace de grabación del momento 3: </w:t>', 'occ' => 0, 'macro' => 'enlace_grabacion'],
        ];
    }

    private static function ensureFactorTableBorders(string $xml): string
    {
        $start = strpos($xml, 'Factores Técnicos');
        if ($start === false) {
            return $xml;
        }

        $end = strrpos($xml, '</w:tbl>');
        if ($end === false || $end <= $start) {
            return $xml;
        }

        $chunk = substr($xml, $start, $end - $start);
        $bordered = str_replace('<w:tcBorders></w:tcBorders>', self::CELL_BORDERS, $chunk);
        $bordered = str_replace(
            '<w:tblBorders></w:tblBorders>',
            '<w:tblBorders>'
            . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '</w:tblBorders>',
            $bordered
        );

        if ($bordered === $chunk) {
            return $xml;
        }

        return substr($xml, 0, $start) . $bordered . substr($xml, $end);
    }

    private static function normalizeFooterParagraphs(string $xml): string
    {
        $anchorPos = strpos($xml, 'El momento 3');
        if ($anchorPos === false) {
            $anchorPos = strpos($xml, '${ciudad_diligenciamiento}');
        }
        if ($anchorPos === false) {
            return $xml;
        }

        $before = substr($xml, 0, $anchorPos);
        $pStart = max(
            (int) strrpos($before, '<w:p '),
            (int) strrpos($before, '<w:p>')
        );
        if ($pStart < 0) {
            return $xml;
        }

        $afterAnchor = substr($xml, $anchorPos);
        if (!preg_match('/(<w:p\b[^>]*>.*?<\/w:p>\s*){1,2}/s', $afterAnchor, $m, PREG_OFFSET_CAPTURE)) {
            return $xml;
        }
        $blockEnd = $anchorPos + $m[0][1] + strlen($m[0][0]);

        $pTagEnd = strpos($xml, '>', $pStart);
        if ($pTagEnd === false) {
            return $xml;
        }
        $pTag = substr($xml, $pStart, $pTagEnd - $pStart + 1);

        $pPr = '<w:pPr><w:pBdr></w:pBdr><w:spacing w:after="0" w:before="0" w:line="240" w:lineRule="auto"/>'
            . '<w:ind /><w:jc w:val="center" />'
            . '<w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:b/><w:bCs/><w:color w:val="000000"/><w:sz w:val="20"/><w:szCs w:val="20"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr></w:pPr>';

        $line1 = $pTag . $pPr
            . self::footerBoldRun('El momento 3 – Evaluación se llevó a cabo en la ')
            . self::footerMacroRun('ciudad_diligenciamiento', false)
            . self::footerBoldRun(' con fecha de diligenciamiento: ')
            . self::footerMacroRun('fecha_diligenciamiento', true)
            . self::footerBoldRun(' de forma')
            . '</w:p>';

        $line2 = $pTag . $pPr
            . self::footerBoldRun('presencial ')
            . self::footerMacroRun('m3_marca_presencial', false)
            . self::footerBoldRun(' o virtual ')
            . self::footerMacroRun('m3_marca_virtual', false)
            . '</w:p>';

        return substr($xml, 0, $pStart) . $line1 . $line2 . substr($xml, $blockEnd);
    }

    private static function insertSpacerParagraphsBeforeFooter(string $xml, int $count): string
    {
        if ($count <= 0) {
            return $xml;
        }

        $footerPos = strpos($xml, '${ciudad_diligenciamiento}');
        if ($footerPos === false) {
            $footerPos = strpos($xml, 'El momento 3');
        }
        if ($footerPos === false) {
            return $xml;
        }

        $pStart = max(
            (int) strrpos(substr($xml, 0, $footerPos), '<w:p '),
            (int) strrpos(substr($xml, 0, $footerPos), '<w:p>')
        );
        if ($pStart < 0) {
            return $xml;
        }

        $lastTableEnd = strrpos(substr($xml, 0, $pStart), '</w:tbl>');
        if ($lastTableEnd === false) {
            return $xml;
        }
        $lastTableEnd += strlen('</w:tbl>');

        $offset = $lastTableEnd;
        while (preg_match('/^\s*(<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>)/s', substr($xml, $offset), $match)) {
            if (!self::isLayoutSpacerParagraph($match[1])) {
                break;
            }
            $offset += strlen($match[0]);
        }

        $spacer = '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="exact"/></w:pPr></w:p>';

        return substr($xml, 0, $lastTableEnd)
            . str_repeat($spacer, $count)
            . substr($xml, $offset);
    }

    private static function shrinkSignatureRowHeights(string $xml): string
    {
        return self::capRowHeightNearAnchor($xml, 'Firma del ', 320);
    }

    private static function removeTrailingEmptyParagraphsAfterFooter(string $xml): string
    {
        $marker = strpos($xml, '${m3_marca_virtual}');
        if ($marker === false) {
            return $xml;
        }

        $footerEnd = strpos($xml, '</w:p>', $marker);
        if ($footerEnd === false) {
            return $xml;
        }
        $footerEnd += strlen('</w:p>');

        $offset = $footerEnd;
        while (preg_match('#^(\s*<w:p\b[^>]*>(?:(?!</w:p>).)*</w:p>)#s', substr($xml, $offset), $match)) {
            if (!self::isLayoutSpacerParagraph($match[1])) {
                break;
            }
            $offset += strlen($match[0]);
        }

        return substr($xml, 0, $footerEnd) . substr($xml, $offset);
    }

    private static function isLayoutSpacerParagraph(string $paragraph): bool
    {
        if (str_contains($paragraph, '${')) {
            return false;
        }

        return !preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $paragraph);
    }

    private static function capRowHeightNearAnchor(string $xml, string $anchor, int $maxTwips): string
    {
        $pos = strpos($xml, $anchor);
        if ($pos === false) {
            return $xml;
        }

        $rowStart = strrpos(substr($xml, 0, $pos), '<w:tr');
        if ($rowStart === false) {
            return $xml;
        }

        $rowEnd = strpos($xml, '</w:tr>', $pos);
        if ($rowEnd === false) {
            return $xml;
        }
        $rowEnd += strlen('</w:tr>');

        $row = substr($xml, $rowStart, $rowEnd - $rowStart);
        $newRow = preg_replace(
            '/<w:trHeight w:val="\d+"\s*\/>/',
            '<w:trHeight w:val="' . $maxTwips . '" w:hRule="atLeast"/>',
            $row,
            1
        );
        if (!is_string($newRow) || $newRow === $row) {
            return $xml;
        }

        return substr($xml, 0, $rowStart) . $newRow . substr($xml, $rowEnd);
    }

    private static function footerBoldRun(string $text): string
    {
        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:b/><w:bCs/><w:color w:val="000000"/><w:sz w:val="20"/><w:szCs w:val="20"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $text . '</w:t></w:r>';
    }

    private static function footerMacroRun(string $macro, bool $bold): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';
        $boldPr = $bold ? '<w:b/><w:bCs/>' : '';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . $boldPr . '<w:color w:val="000000"/><w:sz w:val="20"/><w:szCs w:val="20"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">${' . $safe . '}</w:t></w:r>';
    }

    private static function macroRunXml(string $macro): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:sz w:val="18"/><w:szCs w:val="18"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">${' . $safe . '}</w:t></w:r>';
    }

    private static function injectAfterAnchor(string $xml, string $anchor, int $occurrence, string $macro): string
    {
        $pos = self::nthIndexOf($xml, $anchor, $occurrence);
        if ($pos === null) {
            return $xml;
        }

        $tail = substr($xml, $pos);
        $quoted = preg_quote($anchor, '/');
        $pattern = '/^(' . $quoted . '(?s).*?<\/w:p><\/w:tc><w:tc\b[^>]*>(?s).*?<w:p\b[^>]*>(?s)(?:<w:pPr>.*?<\/w:pPr>)?)(.*?)(<\/w:p>)/u';
        if (!preg_match($pattern, $tail, $m)) {
            return $xml;
        }

        $replacement = $m[1] . self::macroRunXml($macro) . $m[3];
        $newTail = $replacement . substr($tail, strlen($m[0]));

        return substr($xml, 0, $pos) . $newTail;
    }

    private static function nthIndexOf(string $haystack, string $needle, int $occurrence): ?int
    {
        $offset = 0;
        for ($i = 0; $i <= $occurrence; $i++) {
            $p = strpos($haystack, $needle, $offset);
            if ($p === false) {
                return null;
            }
            if ($i < $occurrence) {
                $offset = $p + strlen($needle);
            } else {
                return $p;
            }
        }

        return null;
    }
}
