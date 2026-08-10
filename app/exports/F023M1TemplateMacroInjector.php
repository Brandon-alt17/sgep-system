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
     * Techo de líneas físicas clonadas para "Observaciones adicionales". El límite de
     * config/f023_limites.php['m1_observaciones_adicionales'] es solo indicativo (no trunca el
     * texto ingresado) — este valor es lo único que realmente acota cuánto texto entra en el
     * documento, así que se fija generoso para no perder contenido en la práctica.
     */
    private const OBS_ADICIONALES_MAX_LINES = 16;

    /**
     * @return list<array{anchor: string, occ: int, macro: string}>
     */
    private static function injectionSpecs(): array
    {
        return [
            ['anchor' => '(DD/MM/AA)</w:t>', 'occ' => 0, 'macro' => 'fecha_inicio_etapa', 'header' => true],
            ['anchor' => '(DD/MM/AA)</w:t>', 'occ' => 1, 'macro' => 'fecha_fin_etapa', 'header' => true],
            ['anchor' => 'Fecha de afiliación a la ARL:</w:t>', 'occ' => 0, 'macro' => 'fecha_arl', 'header' => true],
            ['anchor' => 'póliza ARL:</w:t>', 'occ' => 0, 'macro' => 'numero_poliza_arl', 'header' => true],
            ['anchor' => 'Indicar si es diurno', 'occ' => 0, 'macro' => 'horario', 'header' => true],
            ['anchor' => 'grabación del momento 1: </w:t>', 'occ' => 0, 'macro' => 'enlace_grabacion', 'header' => true],
            ['anchor' => 'Competencias a </w:t>', 'occ' => 0, 'macro' => 'm1_competencias'],
            ['anchor' => 'Resultados de aprendizaje</w:t>', 'occ' => 0, 'macro' => 'm1_resultados'],
            ['anchor' => 'Actividades a desarrollar </w:t>', 'occ' => 0, 'macro' => 'm1_actividades'],
            ['anchor' => 'Evidencias de aprendizaje</w:t>', 'occ' => 0, 'macro' => 'm1_evidencias'],
            ['anchor' => 'Observaciones adicionales</w:t>', 'occ' => 0, 'macro' => 'm1_observaciones_adicionales', 'bold' => true],
        ];
    }

    private static function macroRunXml(string $macro, bool $bold = false): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';
        $boldPr = $bold ? '<w:b/><w:bCs/><w:color w:val="000000"/>' : '';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . $boldPr
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
                (string) $spec['macro'],
                (bool) ($spec['bold'] ?? false),
                (bool) ($spec['header'] ?? false)
            );
        }

        $xml = self::injectM1ObservacionesAdicionalesExtraLines($xml, self::OBS_ADICIONALES_MAX_LINES);

        $xml = F023SignatureNameSupport::injectIntoDocumentXml($xml, 'left');
        $xml = self::normalizeFooterParagraph($xml);
        $xml = self::insertSpacerParagraphsBeforeFooter($xml, 1);
        $xml = self::stripExtraEmptyParasFromConcertacionCells($xml);
        $xml = self::tightenConcertacionContentSpacing($xml);
        $xml = self::shrinkConcertacionEmptyRowHeights($xml);
        $xml = self::shrinkBottomSectionRowHeights($xml);
        $xml = self::removeTrailingEmptyParagraphsAfterFooter($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo escribir document.xml en la plantilla temporal M1.');
        }
        if (!$zip->close()) {
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo finalizar la plantilla temporal M1.');
        }

        return $tmpDocx;
    }

    /**
     * Aplica layout final tras PhpWord TemplateProcessor::saveAs() (el XML definitivo del segmento).
     */
    public static function applyLayoutToSavedDocx(string $docxPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el segmento M1 para layout.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            throw new \RuntimeException('document.xml ilegible en segmento M1 guardado.');
        }

        $xml = self::shrinkConcertacionFreeTextByLength($xml);
        $xml = self::shrinkObservacionesAdicionalesByLength($xml);
        $xml = self::pruneUnusedObservacionesAdicionalesLines($xml);
        $xml = self::compactLayoutForSinglePage($xml);
        $xml = self::removeTrailingEmptyParagraphsAfterFooter($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            throw new \RuntimeException('No se pudo escribir layout en segmento M1.');
        }
        if (!$zip->close()) {
            throw new \RuntimeException('No se pudo finalizar el layout del segmento M1.');
        }

        F023HyperlinkSupport::applyToDocx($docxPath, 'grabación del momento 1: ');
    }

    /**
     * Exportación M1 sola: una página, cabecera PROCESO centrada, sin saltos de sección extra.
     */
    private static function compactLayoutForSinglePage(string $xml): string
    {
        if (!preg_match('#^(.*?<w:body>\s*)(.*?)(\s*</w:body>.*)$#s', $xml, $m)) {
            return $xml;
        }

        $inner = $m[2];
        $sectPos = strrpos($inner, '<w:sectPr');
        if ($sectPos === false) {
            return $xml;
        }

        $content = substr($inner, 0, $sectPos);
        $sectPr = substr($inner, $sectPos);

        $stripped = preg_replace('/<w:sectPr\b[^>]*>.*?<\/w:sectPr>/s', '', $content);
        $content = is_string($stripped) ? trim($stripped) : trim($content);

        $content = self::stripPageBreakBeforeFromLeadingParagraphs($content);
        $content = self::centerFirstTopLevelTable($content);
        $content = self::trimLeadingEmptyParagraphsBeforeFirstTable($content);
        $content = self::removeTrailingEmptyParagraphAfterLastTable($content);

        $sectPr = preg_replace('/<w:type\s+w:val="nextPage"\s*\/>/', '<w:type w:val="continuous" />', $sectPr) ?? $sectPr;
        $sectPr = F023SectionHeaderSupport::normalizeSectPrForPdfExport($sectPr, $xml);

        $result = $m[1] . $content . $sectPr . $m[3];

        return $result;
    }

    private static function centerFirstTopLevelTable(string $content): string
    {
        $tblStart = strpos($content, '<w:tbl');
        if ($tblStart === false) {
            return $content;
        }

        $tblPrStart = strpos($content, '<w:tblPr>', $tblStart);
        $tblPrEnd = strpos($content, '</w:tblPr>', $tblPrStart !== false ? $tblPrStart : $tblStart);
        if ($tblPrStart === false || $tblPrEnd === false) {
            return $content;
        }

        $tblPrEnd += strlen('</w:tblPr>');
        $tblPr = substr($content, $tblPrStart, $tblPrEnd - $tblPrStart);
        if (str_contains($tblPr, '<w:jc')) {
            return $content;
        }

        $centered = preg_replace('/(<w:tblPr>)/', '$1<w:jc w:val="center"/>', $tblPr, 1);
        if (!is_string($centered) || $centered === $tblPr) {
            return $content;
        }

        return substr($content, 0, $tblPrStart) . $centered . substr($content, $tblPrEnd);
    }

    private static function stripPageBreakBeforeFromLeadingParagraphs(string $content): string
    {
        $firstTable = strpos($content, '<w:tbl');
        if ($firstTable === false || $firstTable === 0) {
            return $content;
        }

        $leading = substr($content, 0, $firstTable);
        $body = substr($content, $firstTable);
        $leading = preg_replace('/<w:pageBreakBefore\s*\/>/', '', $leading) ?? $leading;

        return $leading . $body;
    }

    private static function trimLeadingEmptyParagraphsBeforeFirstTable(string $content): string
    {
        $firstTable = strpos($content, '<w:tbl');
        if ($firstTable === false || $firstTable === 0) {
            return $content;
        }

        $leading = substr($content, 0, $firstTable);
        $body = substr($content, $firstTable);

        while (preg_match('/^\s*(<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>)/s', $leading, $match)) {
            $paragraph = $match[1];
            if (
                str_contains($paragraph, '${')
                || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $paragraph)
                || str_contains($paragraph, '<w:pageBreakBefore')
            ) {
                break;
            }

            $leading = substr($leading, strlen($match[0]));
        }

        return ltrim($leading) . $body;
    }

    private static function removeTrailingEmptyParagraphAfterLastTable(string $content): string
    {
        $lastTableEnd = strrpos($content, '</w:tbl>');
        if ($lastTableEnd === false) {
            return $content;
        }
        $lastTableEnd += strlen('</w:tbl>');

        $footerPos = strpos($content, '${ciudad_diligenciamiento}');
        if ($footerPos === false) {
            $footerPos = strpos($content, 'Ciudad ');
        }
        if ($footerPos === false || $footerPos <= $lastTableEnd) {
            return $content;
        }

        $between = substr($content, $lastTableEnd, $footerPos - $lastTableEnd);
        if (str_contains($between, '${') || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $between)) {
            return $content;
        }

        $collapsed = preg_replace('/<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>/s', '', $between) ?? $between;
        if ($collapsed === $between) {
            return $content;
        }

        return substr($content, 0, $lastTableEnd) . $collapsed . substr($content, $footerPos);
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

    private static function insertSpacerParagraphsBeforeFooter(string $xml, int $count = 3): string
    {
        if ($count <= 0) {
            return $xml;
        }

        $footerPos = strpos($xml, '${ciudad_diligenciamiento}');
        if ($footerPos === false) {
            $footerPos = strpos($xml, 'Ciudad ');
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

    private static function stripEmptyParagraphsBeforeFooter(string $xml): string
    {
        $footerPos = strpos($xml, '${ciudad_diligenciamiento}');
        if ($footerPos === false) {
            $footerPos = strpos($xml, 'Ciudad ');
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

        return substr($xml, 0, $lastTableEnd) . substr($xml, $offset);
    }

    private static function removeTrailingEmptyParagraphsAfterFooter(string $xml): string
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

    /**
     * Reduce progresivamente el tamaño de letra de los 4 campos de flujo libre (competencias,
     * resultados, actividades, evidencias) cuando el texto ya sustituido supera su umbral
     * "recomendado" de config/f023_limites.php — garantía real de 1 sola hoja, más allá del
     * aviso no bloqueante del contador del formulario.
     */
    private static function shrinkConcertacionFreeTextByLength(string $xml): string
    {
        $targets = [
            ['anchor' => 'Competencias a ', 'key' => 'm1_competencias'],
            ['anchor' => 'Resultados de aprendizaje', 'key' => 'm1_resultados'],
            ['anchor' => 'Actividades a desarrollar ', 'key' => 'm1_actividades'],
            ['anchor' => 'Evidencias de aprendizaje', 'key' => 'm1_evidencias'],
        ];

        foreach ($targets as $target) {
            $xml = F023DynamicFontScaleSupport::shrinkContentCellFontByLength(
                $xml,
                (string) $target['anchor'],
                (string) $target['key']
            );
        }

        return $xml;
    }

    /**
     * "Observaciones adicionales" clona hasta OBS_ADICIONALES_MAX_LINES párrafos (ver
     * injectM1ObservacionesAdicionalesExtraLines) para poder mostrar texto largo; cuando el texto
     * es corto, la mayoría de esos párrafos quedan sustituidos con texto vacío pero SIGUEN
     * ocupando su propia línea en la celda (a diferencia de M2/EX, este campo no tiene subrayado
     * que preservar, así que no hace falta dejar ninguna línea "en blanco" de más). Este método
     * elimina esos párrafos realmente vacíos tras la sustitución, dejando solo las líneas que
     * el aprendiz efectivamente escribió — evita el hueco de página en blanco que dejaba cada
     * línea sobrante sin usar.
     */
    private static function pruneUnusedObservacionesAdicionalesLines(string $xml): string
    {
        $anchor = 'Observaciones adicionales';
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
        $newContentCell = self::removeEmptyTrailingLines($contentCell);
        if ($newContentCell === $contentCell) {
            return $xml;
        }

        return substr($xml, 0, $contentCellOffset) . $newContentCell . substr($xml, $contentCellEnd);
    }

    /**
     * Conserva siempre el primer párrafo de la celda (línea base, aunque esté vacío); desde el
     * segundo en adelante, elimina los que no tengan ningún texto visible (ni siquiera el
     * marcador NBSP de línea "en blanco pero usada" que sí necesitan M2/EX).
     */
    private static function removeEmptyTrailingLines(string $cellXml): string
    {
        if (!preg_match_all('/<w:p\b[^>]*>.*?<\/w:p>/s', $cellXml, $matches, PREG_OFFSET_CAPTURE)) {
            return $cellXml;
        }

        /** @var list<array{start: int, length: int}> $removals */
        $removals = [];
        foreach ($matches[0] as $index => $match) {
            if ($index === 0) {
                continue;
            }
            $paragraph = (string) $match[0];
            if (!self::isBlankParagraph($paragraph)) {
                continue;
            }
            $removals[] = ['start' => (int) $match[1], 'length' => strlen($paragraph)];
        }

        usort($removals, static fn (array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($removals as $removal) {
            $cellXml = substr_replace($cellXml, '', $removal['start'], $removal['length']);
        }

        return $cellXml;
    }

    private static function isBlankParagraph(string $paragraphXml): bool
    {
        if (!preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $paragraphXml, $m)) {
            return true;
        }

        $text = implode('', $m[1]);
        $text = html_entity_decode($text, ENT_XML1 | ENT_QUOTES);
        $text = str_replace("\u{00A0}", '', $text);

        return trim($text) === '';
    }

    /**
     * Igual que shrinkConcertacionFreeTextByLength() pero para "Observaciones adicionales"
     * (campo de slots fijos): mide el texto combinado de las hasta 4 líneas físicas de la
     * plantilla y aplica un único tamaño uniforme a todas, para no tener saltos de tamaño entre
     * renglones del mismo campo.
     */
    private static function shrinkObservacionesAdicionalesByLength(string $xml): string
    {
        return F023DynamicFontScaleSupport::shrinkContentCellFontByLength(
            $xml,
            'Observaciones adicionales',
            'm1_observaciones_adicionales'
        );
    }

    /**
     * Ajusta el interlineado del único párrafo de contenido de cada campo de concertación
     * (competencias, resultados, actividades, evidencias, observaciones adicionales) a
     * espaciado exacto sin espacio antes/después, para recuperar presupuesto vertical de
     * página cuando el texto es largo.
     */
    private static function tightenConcertacionContentSpacing(string $xml): string
    {
        foreach ([
            'Competencias a ',
            'Resultados de aprendizaje',
            'Actividades a desarrollar ',
            'Evidencias de aprendizaje',
        ] as $anchor) {
            $xml = self::tightenContentCellSpacing($xml, $anchor);
        }

        return $xml;
    }

    private static function tightenContentCellSpacing(string $xml, string $anchor): string
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
        $firstParaEnd = strpos($contentCell, '</w:p>');
        if ($firstParaEnd === false) {
            return $xml;
        }
        $firstParaEnd += strlen('</w:p>');

        $paragraph = substr($contentCell, 0, $firstParaEnd);
        $newParagraph = self::applyTightLineSpacing($paragraph);
        if ($newParagraph === $paragraph) {
            return $xml;
        }

        $newContentCell = $newParagraph . substr($contentCell, $firstParaEnd);

        return substr($xml, 0, $contentCellOffset) . $newContentCell . substr($xml, $contentCellEnd);
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

    /**
     * La celda de datos de "Observaciones adicionales" en m1.docx trae un único párrafo
     * (recibe ${m1_observaciones_adicionales} vía injectAfterAnchor()). Para poder mostrar
     * más de 1 línea cuando el texto es largo, se clona ese párrafo (ajustado a interlineado
     * compacto) tantas veces como líneas adicionales se necesiten, cada una con su propio
     * macro ${..._lN}, insertadas justo después del párrafo original.
     */
    private static function injectM1ObservacionesAdicionalesExtraLines(string $xml, int $totalLines): string
    {
        if ($totalLines < 2) {
            return $xml;
        }

        $macroToken = '${m1_observaciones_adicionales}';
        $macroPos = strpos($xml, $macroToken);
        if ($macroPos === false) {
            return $xml;
        }

        $before = substr($xml, 0, $macroPos);
        if (!preg_match_all('/<w:p(?:\s[^>]*)?>/', $before, $pMatches, PREG_OFFSET_CAPTURE)) {
            return $xml;
        }
        $lastP = end($pMatches[0]);
        $paraStart = (int) $lastP[1];

        $paraEnd = strpos($xml, '</w:p>', $macroPos);
        if ($paraEnd === false) {
            return $xml;
        }
        $paraEnd += strlen('</w:p>');

        $paragraph = substr($xml, $paraStart, $paraEnd - $paraStart);
        $tightParagraph = self::applyTightLineSpacing($paragraph);

        $extraParagraphs = '';
        for ($line = 2; $line <= $totalLines; $line++) {
            $lineToken = '${m1_observaciones_adicionales_l' . $line . '}';
            $extraParagraphs .= str_replace($macroToken, $lineToken, $tightParagraph);
        }

        return substr($xml, 0, $paraStart) . $tightParagraph . $extraParagraphs . substr($xml, $paraEnd);
    }

    private static function applyTightLineSpacing(string $paragraphXml): string
    {
        $spacing = '<w:spacing w:after="0" w:before="0" w:line="240" w:lineRule="exact"/>';
        if (preg_match('/<w:spacing\b[^>]*\/>/', $paragraphXml)) {
            $updated = preg_replace('/<w:spacing\b[^>]*\/>/', $spacing, $paragraphXml, 1);

            return is_string($updated) ? $updated : $paragraphXml;
        }
        if (str_contains($paragraphXml, '<w:pPr>')) {
            $updated = preg_replace('/<w:pPr>/', '<w:pPr>' . $spacing, $paragraphXml, 1);

            return is_string($updated) ? $updated : $paragraphXml;
        }

        $updated = preg_replace('/<w:p>/', '<w:p><w:pPr>' . $spacing . '</w:pPr>', $paragraphXml, 1);

        return is_string($updated) ? $updated : $paragraphXml;
    }

    private static function injectAfterAnchor(
        string $xml,
        string $anchor,
        int $occurrence,
        string $macro,
        bool $bold = false,
        bool $headerValueCell = false
    ): string {
        $pos = self::nthIndexOf($xml, $anchor, $occurrence);
        if ($pos === null) {
            return $xml;
        }

        $tail = substr($xml, $pos);
        $quoted = preg_quote($anchor, '/');
        $macroRun = self::macroRunXml($macro, $bold);

        if ($headerValueCell) {
            $pattern = '/^(' . $quoted . '(?s).*?<\/w:p><\/w:tc>)(<w:tc\b[^>]*>.*?<\/w:tc>)/u';
            if (!preg_match($pattern, $tail, $m)) {
                return $xml;
            }

            $normalizedCell = F023HeaderValueCellSupport::normalize((string) $m[2], $macroRun);
            $newTail = (string) $m[1] . $normalizedCell . substr($tail, strlen($m[0]));

            return substr($xml, 0, $pos) . $newTail;
        }

        $pattern = '/^(' . $quoted . '(?s).*?<\/w:p><\/w:tc><w:tc\b[^>]*>(?s).*?<w:p\b[^>]*>(?s)(?:<w:pPr>.*?<\/w:pPr>)?)(.*?)(<\/w:p>)/u';
        if (!preg_match($pattern, $tail, $m)) {
            return $xml;
        }

        $replacement = $m[1] . $macroRun . $m[3];
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
