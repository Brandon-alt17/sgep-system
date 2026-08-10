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
            foreach (self::p1RetroalimentacionSpecs() as $spec) {
                $xml = self::injectRetroMacroInTableRow(
                    $xml,
                    (string) $spec['tableAnchor'],
                    (int) $spec['row'],
                    (string) $spec['macro']
                );
            }
            $xml = self::ensureFactorTableBorders($xml);
            $xml = F023FactorValoracionMacroSupport::injectIntoDocumentXml($xml);
        } else {
            $xml = self::injectP2ContentFields($xml);
            $xml = F023SignatureNameSupport::injectIntoDocumentXml($xml, 'left');
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
        if (!$zip->close()) {
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo finalizar la plantilla temporal M3.');
        }

        return $tmpDocx;
    }

    /** @return array<string, string> posOffset del cuadro anclado → macro de marca */
    private static function p2JuicioCheckboxOffsets(): array
    {
        return [
            '4938395' => 'm3_juicio_marca_aprobado',
            '6029325' => 'm3_juicio_marca_no_aprobado',
        ];
    }

    /** @return array<string, string> paraId del cuadro anclado → macro de marca */
    private static function p2JuicioCheckboxParaIds(): array
    {
        return [
            '6C2D484F' => 'm3_juicio_marca_aprobado',
            '469EB6AF' => 'm3_juicio_marca_no_aprobado',
        ];
    }

    /**
     * Tras TemplateProcessor::saveAs(), reduce progresivamente el tamaño de letra de los 6 campos
     * de retroalimentación si el texto ya sustituido supera el umbral "recomendado", y convierte
     * el enlace de grabación en hipervínculo real. Este método es compartido por m3_p1 y m3_p2 (el
     * $docxPath temporal no conserva el nombre de la plantilla de origen), así que cada paso busca
     * sus propias anclas y no hace nada si no las encuentra en esta parte del documento — misma
     * lógica "best effort por ancla" ya usada en el resto de esta clase.
     */
    public static function applyLayoutToSavedDocx(string $docxPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el segmento M3 para layout.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            throw new \RuntimeException('document.xml ilegible en segmento M3 guardado.');
        }

        $xml = F023FactorValoracionMacroSupport::shrinkObservationCellsBySavedLength($xml);
        $xml = self::shrinkRetroFieldsByLength($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            throw new \RuntimeException('No se pudo escribir layout en segmento M3.');
        }
        if (!$zip->close()) {
            throw new \RuntimeException('No se pudo finalizar el layout del segmento M3.');
        }

        F023HyperlinkSupport::applyToDocx($docxPath, 'Enlace de grabación del momento 3: ');
    }

    private static function shrinkRetroFieldsByLength(string $xml): string
    {
        $specs = [...self::p1RetroalimentacionSpecs(), ...self::p2RetroalimentacionSpecs()];
        foreach ($specs as $spec) {
            $xml = self::shrinkRetroCellFontByLength($xml, (string) $spec['tableAnchor'], (int) $spec['row']);
        }

        return $xml;
    }

    /** Misma navegación tabla/fila/celda que injectRetroMacroInTableRow(), sobre texto ya sustituido. */
    private static function shrinkRetroCellFontByLength(string $xml, string $tableAnchor, int $rowIndex): string
    {
        $anchorPos = strpos($xml, $tableAnchor);
        if ($anchorPos === false) {
            return $xml;
        }

        $tblStart = strrpos(substr($xml, 0, $anchorPos), '<w:tbl');
        $tblEnd = strpos($xml, '</w:tbl>', $anchorPos);
        if ($tblStart === false || $tblEnd === false) {
            return $xml;
        }
        $tblEnd += strlen('</w:tbl>');

        $table = substr($xml, $tblStart, $tblEnd - $tblStart);
        if (!preg_match_all('/<w:tr\b[^>]*>.*?<\/w:tr>/s', $table, $rows) || !isset($rows[0][$rowIndex])) {
            return $xml;
        }

        $row = (string) $rows[0][$rowIndex];
        if (!preg_match_all('/<w:tc\b[^>]*>.*?<\/w:tc>/s', $row, $cells) || !isset($cells[0][1])) {
            return $xml;
        }

        $dataCell = (string) $cells[0][1];
        $newDataCell = F023DynamicFontScaleSupport::shrinkFragmentByLength($dataCell, 'retro_m3');
        if ($newDataCell === $dataCell) {
            return $xml;
        }

        $patchedRow = substr_replace($row, $newDataCell, strpos($row, $dataCell), strlen($dataCell));
        $patchedTable = substr_replace($table, $patchedRow, strpos($table, $row), strlen($row));

        return substr($xml, 0, $tblStart) . $patchedTable . substr($xml, $tblEnd);
    }

    /**
     * Tras TemplateProcessor::saveAs(), fija la X dentro de los cuadros de juicio y ajusta
     * márgenes/centrado para que LibreOffice/PDF la dibuje dentro del recuadro.
     *
     * @param array<string, string> $marcaValues
     */
    public static function applyJuicioMarcasToSavedDocx(string $docxPath, array $marcaValues): void
    {
        if (!is_file($docxPath)) {
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el segmento M3 p2 para marcas de juicio.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            throw new \RuntimeException('document.xml ilegible en segmento M3 p2 guardado.');
        }

        foreach (self::p2JuicioCheckboxOffsets() as $offset => $macro) {
            $value = (string) ($marcaValues[$macro] ?? '');
            $xml = self::setJuicioMarcaInAnchor($xml, (string) $offset, $value);
            $xml = self::tuneJuicioCheckboxAnchorInXml($xml, (string) $offset);
        }

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            throw new \RuntimeException('No se pudo escribir marcas de juicio en segmento M3 p2.');
        }
        if (!$zip->close()) {
            throw new \RuntimeException('No se pudo finalizar las marcas de juicio del segmento M3 p2.');
        }
    }

    /**
     * Retroalimentación ente co-formador en m3_p1 (página 1).
     *
     * @return list<array{tableAnchor: string, row: int, macro: string}>
     */
    private static function p1RetroalimentacionSpecs(): array
    {
        return [
            ['tableAnchor' => 'Retroalimentación ente', 'row' => 1, 'macro' => 'm3_retro_coformador_proceso'],
            ['tableAnchor' => 'Retroalimentación ente', 'row' => 2, 'macro' => 'm3_retro_coformador_desempeno'],
        ];
    }

    /**
     * @return list<array{tableAnchor: string, row: int, macro: string}>
     */
    private static function p2RetroalimentacionSpecs(): array
    {
        return [
            ['tableAnchor' => 'Retroalimentación instructor', 'row' => 1, 'macro' => 'm3_retro_instructor_proceso'],
            ['tableAnchor' => 'Retroalimentación instructor', 'row' => 2, 'macro' => 'm3_retro_instructor_desempeno'],
            ['tableAnchor' => 'Retroalimentación del aprendiz', 'row' => 1, 'macro' => 'm3_retro_aprendiz_proceso'],
            ['tableAnchor' => 'Retroalimentación del aprendiz', 'row' => 2, 'macro' => 'm3_retro_aprendiz_desempeno'],
        ];
    }

    private static function injectP2ContentFields(string $xml): string
    {
        foreach (self::p2RetroalimentacionSpecs() as $spec) {
            $xml = self::injectRetroMacroInTableRow(
                $xml,
                (string) $spec['tableAnchor'],
                (int) $spec['row'],
                (string) $spec['macro']
            );
        }

        return self::tuneJuicioCheckboxAnchorsInXml(
            self::injectJuicioMarcasInCheckboxDrawings($xml)
        );
    }

    private static function tuneJuicioCheckboxAnchorsInXml(string $xml): string
    {
        foreach (self::p2JuicioCheckboxOffsets() as $offset => $macro) {
            $xml = self::tuneJuicioCheckboxAnchorInXml($xml, (string) $offset);
        }

        return $xml;
    }

    private static function setJuicioMarcaInAnchor(string $xml, string $posOffset, string $value): string
    {
        $block = self::extractAnchorBlockContaining($xml, $posOffset);
        if ($block === null) {
            return $xml;
        }

        $patched = self::setJuicioMarcaInAnchorBlock($block, $value);
        if ($patched === $block) {
            return $xml;
        }

        $start = strpos($xml, $block);
        if ($start === false) {
            return $xml;
        }

        return substr_replace($xml, $patched, $start, strlen($block));
    }

    private static function setJuicioMarcaInAnchorBlock(string $block, string $value): string
    {
        $txbx = self::txbxContentWithValue($value);
        $replaced = preg_replace('/<w:txbxContent>.*?<\/w:txbxContent>/s', $txbx, $block);

        return is_string($replaced) ? $replaced : $block;
    }

    private static function tuneJuicioCheckboxAnchorInXml(string $xml, string $posOffset): string
    {
        $block = self::extractAnchorBlockContaining($xml, $posOffset);
        if ($block === null) {
            return $xml;
        }

        $patched = self::tuneJuicioCheckboxAnchorBlock($block);
        if ($patched === $block) {
            return $xml;
        }

        $start = strpos($xml, $block);
        if ($start === false) {
            return $xml;
        }

        return substr_replace($xml, $patched, $start, strlen($block));
    }

    private static function extractAnchorBlockContaining(string $xml, string $posOffset): ?string
    {
        $needle = 'wp:posOffset>' . $posOffset;
        $pos = strpos($xml, $needle);
        if ($pos === false) {
            return null;
        }

        $before = substr($xml, 0, $pos);
        $start = strrpos($before, '<wp:anchor');
        if ($start === false) {
            return null;
        }

        $end = strpos($xml, '</wp:anchor>', $pos);
        if ($end === false) {
            return null;
        }
        $end += strlen('</wp:anchor>');

        return substr($xml, $start, $end - $start);
    }

    private static function tuneJuicioCheckboxAnchorBlock(string $block): string
    {
        $tuned = $block;
        foreach (['lIns', 'tIns', 'rIns', 'bIns'] as $attr) {
            $replaced = preg_replace('/\b' . $attr . '="\d+"/', $attr . '="0"', $tuned);
            if (is_string($replaced)) {
                $tuned = $replaced;
            }
        }

        $tuned = str_replace('anchor="t"', 'anchor="ctr"', $tuned);
        $tuned = str_replace('anchorCtr="0"', 'anchorCtr="1"', $tuned);
        $tuned = str_replace('v-text-anchor:top', 'v-text-anchor:middle', $tuned);

        return $tuned;
    }

    private static function injectRetroMacroInTableRow(
        string $xml,
        string $tableAnchor,
        int $rowIndex,
        string $macro
    ): string {
        $anchorPos = strpos($xml, $tableAnchor);
        if ($anchorPos === false) {
            return $xml;
        }

        $tblStart = strrpos(substr($xml, 0, $anchorPos), '<w:tbl');
        $tblEnd = strpos($xml, '</w:tbl>', $anchorPos);
        if ($tblStart === false || $tblEnd === false) {
            return $xml;
        }
        $tblEnd += strlen('</w:tbl>');

        $table = substr($xml, $tblStart, $tblEnd - $tblStart);
        if (!preg_match_all('/<w:tr\b[^>]*>.*?<\/w:tr>/s', $table, $rows) || !isset($rows[0][$rowIndex])) {
            return $xml;
        }

        $row = (string) $rows[0][$rowIndex];
        if (!preg_match_all('/<w:tc\b[^>]*>.*?<\/w:tc>/s', $row, $cells) || !isset($cells[0][1])) {
            return $xml;
        }

        $dataCell = (string) $cells[0][1];
        $patchedCell = self::injectMacroInRetroCell($dataCell, $macro);
        if ($patchedCell === $dataCell) {
            return $xml;
        }

        $patchedRow = substr_replace($row, $patchedCell, strpos($row, $dataCell), strlen($dataCell));
        $patchedTable = substr_replace($table, $patchedRow, strpos($table, $row), strlen($row));

        return substr($xml, 0, $tblStart) . $patchedTable . substr($xml, $tblEnd);
    }

    private static function injectMacroInRetroCell(string $cellXml, string $macro): string
    {
        if (!preg_match('/^(<w:tc\b[^>]*>)(.*?)(<\/w:tc>)$/s', $cellXml, $parts)) {
            return $cellXml;
        }

        $inner = $parts[2];
        if (!preg_match('/<w:p\b[^>]*>.*?<\/w:p>/s', $inner, $paragraphMatch, PREG_OFFSET_CAPTURE)) {
            $inner .= '<w:p><w:pPr><w:jc w:val="left"/></w:pPr>' . self::macroRunXml($macro) . '</w:p>';

            return $parts[1] . $inner . $parts[3];
        }

        $originalParagraph = (string) $paragraphMatch[0][0];
        $paragraphPos = (int) $paragraphMatch[0][1];
        $paragraph = self::ensureParagraphLeftAlign($originalParagraph);
        $newParagraph = preg_replace(
            '/<\/w:p>$/',
            self::macroRunXml($macro) . '</w:p>',
            $paragraph,
            1
        );
        if (!is_string($newParagraph)) {
            return $cellXml;
        }

        $inner = substr_replace($inner, $newParagraph, $paragraphPos, strlen($originalParagraph));

        return $parts[1] . $inner . $parts[3];
    }

    private static function injectJuicioMarcasInCheckboxDrawings(string $xml): string
    {
        $anchor = 'Juicio de evaluación';
        $pos = strpos($xml, $anchor);
        if ($pos === false) {
            return $xml;
        }

        $pStart = self::findParagraphStartOutsideTxbx($xml, $pos);
        if ($pStart === null) {
            return $xml;
        }

        $paragraph = self::extractParagraphAt($xml, $pStart);
        if ($paragraph === null) {
            return $xml;
        }

        $patched = self::patchJuicioDrawingCheckboxes($paragraph);
        if ($patched === $paragraph) {
            return $xml;
        }

        return substr_replace($xml, $patched, $pStart, strlen($paragraph));
    }

    private static function isInsideTxbxContent(string $xml, int $pos): bool
    {
        $before = substr($xml, 0, $pos);

        return substr_count($before, '<w:txbxContent>') > substr_count($before, '</w:txbxContent>');
    }

    private static function findParagraphStartOutsideTxbx(string $xml, int $anchorPos): ?int
    {
        $searchPos = $anchorPos;
        while ($searchPos > 0) {
            $before = substr($xml, 0, $searchPos);
            $pStart = max(
                (int) strrpos($before, '<w:p '),
                (int) strrpos($before, '<w:p>')
            );
            if ($pStart < 0) {
                return null;
            }
            if (!self::isInsideTxbxContent($xml, $pStart)) {
                return $pStart;
            }
            $searchPos = $pStart - 1;
        }

        return null;
    }

    private static function extractParagraphAt(string $xml, int $pStart): ?string
    {
        $depth = 0;
        $len = strlen($xml);
        for ($i = $pStart; $i < $len; $i++) {
            if ($xml[$i] !== '<') {
                continue;
            }
            if ($i + 4 <= $len && substr($xml, $i, 4) === '<w:p') {
                $next = $xml[$i + 4];
                if ($next === '>' || $next === ' ') {
                    $depth++;
                    continue;
                }
            }
            if ($i + 6 <= $len && substr($xml, $i, 6) === '</w:p>') {
                $depth--;
                if ($depth === 0) {
                    return substr($xml, $pStart, $i + 6 - $pStart);
                }
            }
        }

        return null;
    }

    private static function extractParagraphContaining(string $xml, int $anchorPos): ?string
    {
        $pStart = self::findParagraphStartOutsideTxbx($xml, $anchorPos);
        if ($pStart === null) {
            return null;
        }

        return self::extractParagraphAt($xml, $pStart);
    }

    private static function patchJuicioDrawingCheckboxes(string $cell): string
    {
        $patched = $cell;
        foreach (self::p2JuicioCheckboxParaIds() as $paraId => $macro) {
            if (str_contains($patched, '${' . $macro)) {
                continue;
            }

            $pattern = '/<w:txbxContent><w:p[^>]*w14:paraId="' . preg_quote($paraId, '/')
                . '"[^>]*>.*?<\/w:txbxContent>/s';
            $replaced = preg_replace(
                $pattern,
                self::txbxContentWithMacro($macro),
                $patched
            );
            if (is_string($replaced)) {
                $patched = $replaced;
            }
        }

        return $patched;
    }

    private static function txbxContentWithMacro(string $macro): string
    {
        return '<w:txbxContent>' . self::juicioCheckboxParagraphXml(self::juicioCheckboxMacroRun($macro)) . '</w:txbxContent>';
    }

    private static function txbxContentWithValue(string $value): string
    {
        return '<w:txbxContent>' . self::juicioCheckboxParagraphXml(self::juicioCheckboxValueRun($value)) . '</w:txbxContent>';
    }

    private static function juicioCheckboxParagraphXml(string $innerRun): string
    {
        return '<w:p>'
            . '<w:pPr><w:jc w:val="center"/>'
            . '<w:spacing w:before="0" w:after="0" w:line="200" w:lineRule="exact"/>'
            . '<w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:b/><w:sz w:val="16"/><w:szCs w:val="16"/></w:rPr></w:pPr>'
            . $innerRun
            . '</w:p>';
    }

    private static function juicioCheckboxValueRun(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:b/><w:bCs/><w:color w:val="000000"/><w:sz w:val="16"/><w:szCs w:val="16"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">' . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</w:t></w:r>';
    }

    private static function juicioCheckboxMacroRun(string $macro): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:b/><w:bCs/><w:color w:val="000000"/><w:sz w:val="16"/><w:szCs w:val="16"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">${' . $safe . '}</w:t></w:r>';
    }

    private static function injectMacroRunAfterAnchor(
        string $xml,
        string $anchor,
        int $occurrence,
        string $macro
    ): string {
        $pos = self::nthIndexOf($xml, $anchor, $occurrence);
        if ($pos === null) {
            return $xml;
        }

        $insertAt = $pos + strlen($anchor);

        return substr($xml, 0, $insertAt) . self::macroRunXml($macro) . substr($xml, $insertAt);
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

    /**
     * @return list<array{anchor: string, occ: int, macro: string}>
     */
    private static function p1InjectionSpecs(): array
    {
        return [
            ['anchor' => '(DD/MM/AA)</w:t>', 'occ' => 0, 'macro' => 'fecha_inicio_etapa'],
            ['anchor' => 'DD/MM/AA</w:t>', 'occ' => 0, 'macro' => 'fecha_fin_etapa'],
            ['anchor' => 'Número de visitas realizadas en toda la etapa productiva:</w:t>', 'occ' => 0, 'macro' => 'numero_visitas_realizadas'],
            ['anchor' => 'La evaluación se realizó en forma </w:t>', 'occ' => 0, 'macro' => 'modalidad'],
            ['anchor' => 'Enlace de grabación del momento 3: </w:t>', 'occ' => 0, 'macro' => 'enlace_grabacion'],
        ];
    }

    private static function ensureFactorTableBorders(string $xml): string
    {
        $pos = strpos($xml, 'Factores Técnicos');
        if ($pos === false) {
            return $xml;
        }

        $tblStart = strrpos(substr($xml, 0, $pos), '<w:tbl');
        if ($tblStart === false) {
            return $xml;
        }

        $actitudinalPos = strpos($xml, 'Factores Actitudinales', $pos);
        $searchFrom = $actitudinalPos !== false ? $actitudinalPos : $pos;
        $tblEnd = strpos($xml, '</w:tbl>', $searchFrom);
        if ($tblEnd === false) {
            return $xml;
        }
        $tblEnd += strlen('</w:tbl>');

        $chunk = substr($xml, $tblStart, $tblEnd - $tblStart);
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

        return substr($xml, 0, $tblStart) . $bordered . substr($xml, $tblEnd);
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
        $macroRun = self::macroRunXml($macro);
        $pattern = '/^(' . $quoted . '(?s).*?<\/w:p><\/w:tc>)(<w:tc\b[^>]*>.*?<\/w:tc>)/u';
        if (!preg_match($pattern, $tail, $m)) {
            return $xml;
        }

        $normalizedCell = F023HeaderValueCellSupport::normalize((string) $m[2], $macroRun);
        $newTail = (string) $m[1] . $normalizedCell . substr($tail, strlen($m[0]));

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
