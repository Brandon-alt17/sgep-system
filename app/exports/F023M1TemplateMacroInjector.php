<?php

declare(strict_types=1);

namespace App\Exports;

use ZipArchive;

/**
 * Inserta marcadores ${variable} en m1.docx (la plantilla oficial no trae campos de combinación).
 */
final class F023M1TemplateMacroInjector
{
    private const DOCUMENT_XML = 'word/document.xml';

    /**
     * @return list<array{anchor: string, occ: int, macro: string}>
     */
    private static function injectionSpecs(): array
    {
        return [
            ['anchor' => '(DD/MM/AA)</w:t>', 'occ' => 0, 'macro' => 'fecha_inicio_etapa'],
            ['anchor' => '(DD/MM/AA)</w:t>', 'occ' => 1, 'macro' => 'fecha_fin_etapa'],
            ['anchor' => 'Fecha de afiliación a la ARL:</w:t>', 'occ' => 0, 'macro' => 'fecha_arl'],
            ['anchor' => 'póliza ARL:</w:t>', 'occ' => 0, 'macro' => 'numero_poliza_arl'],
            ['anchor' => 'Indicar si es diurno', 'occ' => 0, 'macro' => 'horario'],
            ['anchor' => 'grabación del momento 1: </w:t>', 'occ' => 0, 'macro' => 'enlace_grabacion'],
            ['anchor' => 'Competencias a </w:t>', 'occ' => 0, 'macro' => 'm1_competencias'],
            ['anchor' => 'Resultados de aprendizaje</w:t>', 'occ' => 0, 'macro' => 'm1_resultados'],
            ['anchor' => 'Actividades a desarrollar </w:t>', 'occ' => 0, 'macro' => 'm1_actividades'],
            ['anchor' => 'Evidencias de aprendizaje</w:t>', 'occ' => 0, 'macro' => 'm1_evidencias'],
            ['anchor' => 'Observaciones adicionales</w:t>', 'occ' => 0, 'macro' => 'm1_observaciones_adicionales'],
        ];
    }

    private static function macroRunXml(string $macro): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:sz w:val="18"/><w:szCs w:val="18"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">${' . $safe . '}</w:t></w:r>';
    }

    /**
     * @throws \RuntimeException
     */
    public static function patchToTemp(string $sourceDocx): string
    {
        if (!is_file($sourceDocx)) {
            throw new \RuntimeException('Plantilla M1 no encontrada.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'f023m1_');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal.');
        }
        $tmpDocx = $tmp . '.docx';
        if (!@copy($sourceDocx, $tmpDocx)) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo copiar la plantilla M1.');
        }
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmpDocx) !== true) {
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo abrir la copia temporal de m1.docx.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('document.xml no legible en m1.docx.');
        }

        foreach (self::injectionSpecs() as $spec) {
            $xml = self::injectAfterAnchor(
                $xml,
                (string) $spec['anchor'],
                (int) $spec['occ'],
                (string) $spec['macro']
            );
        }

        $xml = self::normalizeFooterParagraph($xml);
        $xml = self::stripExtraEmptyParasFromConcertacionCells($xml);
        $xml = self::shrinkConcertacionEmptyRowHeights($xml);
        $xml = self::shrinkBottomSectionRowHeights($xml);
        $xml = self::removeTrailingEmptyParagraphAfterFooter($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo escribir document.xml en la plantilla temporal M1.');
        }
        $zip->close();

        return $tmpDocx;
    }

    private static function normalizeFooterParagraph(string $xml): string
    {
        $anchorPos = strpos($xml, 'Ciudad____________');
        if ($anchorPos === false) {
            $anchorPos = strpos($xml, 'Ciudad ${ciudad_diligenciamiento}');
        }
        if ($anchorPos === false) {
            $anchorPos = strpos($xml, 'de diligenciamiento');
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

        $pEnd = strpos($xml, '</w:p>', $anchorPos);
        if ($pEnd === false) {
            return $xml;
        }
        $pEnd += strlen('</w:p>');

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

        $runs = self::footerBoldRun('Ciudad ')
            . self::footerMacroRun('ciudad_diligenciamiento', false)
            . self::footerBoldRun(' y fecha de diligenciamiento: ')
            . self::footerMacroRun('fecha_diligenciamiento', true)
            . self::footerBoldRun(' de forma presencial ')
            . self::footerMacroRun('m1_marca_presencial', false)
            . self::footerBoldRun(' o virtual ')
            . self::footerMacroRun('m1_marca_virtual', false);

        $newPara = $pTag . $pPr . $runs . '</w:p>';

        return substr($xml, 0, $pStart) . $newPara . substr($xml, $pEnd);
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

    private static function shrinkBottomSectionRowHeights(string $xml): string
    {
        $xml = self::capRowHeightNearAnchor($xml, 'Firma delaprendiz', 320);
        $next = preg_replace(
            '/<w:trHeight w:val="765"\s*\/>/',
            '<w:trHeight w:val="360" w:hRule="atLeast"/>',
            $xml,
            1
        );

        return is_string($next) ? $next : $xml;
    }

    private static function removeTrailingEmptyParagraphAfterFooter(string $xml): string
    {
        $marker = strpos($xml, '${m1_marca_virtual}');
        if ($marker === false) {
            return $xml;
        }

        $footerEnd = strpos($xml, '</w:p>', $marker);
        if ($footerEnd === false) {
            return $xml;
        }
        $footerEnd += strlen('</w:p>');

        if (!preg_match('#^(\s*<w:p\b[^>]*>(?:(?!</w:p>).)*</w:p>)#s', substr($xml, $footerEnd), $match)) {
            return $xml;
        }

        $nextPara = $match[1];
        if (preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $nextPara)) {
            return $xml;
        }

        return substr($xml, 0, $footerEnd) . substr($xml, $footerEnd + strlen($nextPara));
    }

    private static function stripExtraEmptyParasFromConcertacionCells(string $xml): string
    {
        foreach ([
            'Competencias a ',
            'Resultados de aprendizaje',
            'Actividades a desarrollar ',
            'Evidencias de aprendizaje',
            'Observaciones adicionales',
        ] as $anchor) {
            $xml = self::stripExtraParasFromContentCell($xml, $anchor);
        }

        return $xml;
    }

    private static function stripExtraParasFromContentCell(string $xml, string $anchor): string
    {
        $anchorPos = strpos($xml, $anchor);
        if ($anchorPos === false) {
            return $xml;
        }

        $labelCellEnd = strpos($xml, '</w:tc>', $anchorPos);
        if ($labelCellEnd === false) {
            return $xml;
        }
        $contentCellOffset = $labelCellEnd + strlen('</w:tc>');

        $contentCellEnd = strpos($xml, '</w:tc>', $contentCellOffset);
        if ($contentCellEnd === false) {
            return $xml;
        }

        $contentCell = substr($xml, $contentCellOffset, $contentCellEnd - $contentCellOffset);

        $firstParaClose = strpos($contentCell, '</w:p>');
        if ($firstParaClose === false) {
            return $xml;
        }
        $firstParaClose += strlen('</w:p>');

        $removed = substr($contentCell, $firstParaClose);
        if ($removed === '') {
            return $xml;
        }

        // Only strip if what follows is truly empty paragraphs (no macros, no visible text).
        if (
            str_contains($removed, '${')
            || preg_match('/<w:t[^>]*>[^<]+<\/w:t>/', $removed)
        ) {
            return $xml;
        }

        return substr($xml, 0, $contentCellOffset)
            . substr($contentCell, 0, $firstParaClose)
            . '</w:tc>'
            . substr($xml, $contentCellEnd + strlen('</w:tc>'));
    }

    private static function shrinkConcertacionEmptyRowHeights(string $xml): string
    {
        $targets = [
            ['anchor' => 'Competencias a ', 'maxTwips' => 360],
            ['anchor' => 'Resultados de aprendizaje', 'maxTwips' => 360],
            ['anchor' => 'Actividades a desarrollar ', 'maxTwips' => 240],
            ['anchor' => 'Evidencias de aprendizaje', 'maxTwips' => 240],
            ['anchor' => 'Observaciones adicionales', 'maxTwips' => 240],
        ];

        foreach ($targets as $target) {
            $xml = self::capRowHeightNearAnchor($xml, (string) $target['anchor'], (int) $target['maxTwips']);
        }

        return $xml;
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
