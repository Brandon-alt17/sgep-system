<?php

declare(strict_types=1);

namespace App\Exports;

use ZipArchive;

/**
 * Inserta marcadores ${variable} en extra.docx (anexo seguimiento extraordinario).
 */
final class F023ExTemplateMacroInjector
{
    private const DOCUMENT_XML = 'word/document.xml';

    private const OBS_LINE_BORDER = '<w:pBdr>'
        . '<w:bottom w:val="single" w:color="000000" w:sz="4" w:space="1"/>'
        . '<w:between w:val="single" w:color="000000" w:sz="4" w:space="1"/>'
        . '</w:pBdr>';

    private const OBS_LINE_SPACING = '<w:spacing w:after="60" w:before="0" w:line="276" w:lineRule="exact"/>';

    /**
     * @throws \RuntimeException
     */
    public static function patchToTemp(string $sourceDocx): string
    {
        if (!is_file($sourceDocx)) {
            throw new \RuntimeException('Plantilla EX no encontrada.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'f023ex_');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal.');
        }
        $tmpDocx = $tmp . '.docx';
        if (!@copy($sourceDocx, $tmpDocx)) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo copiar la plantilla EX.');
        }
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmpDocx) !== true) {
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo abrir la copia temporal de extra.docx.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('document.xml no legible en extra.docx.');
        }

        $xml = F023FactorValoracionMacroSupport::injectIntoDocumentXml($xml);
        $xml = self::injectNumeroVisita($xml);

        foreach (self::injectionSpecs() as $spec) {
            $xml = self::injectAfterAnchor(
                $xml,
                (string) $spec['anchor'],
                (int) $spec['occ'],
                (string) $spec['macro']
            );
        }

        $xml = self::injectCompromisosFields($xml);
        $xml = self::injectSignatures($xml);
        $xml = self::normalizeFooterParagraph($xml);
        $xml = self::stripDecorativeTableColors($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo escribir document.xml en la plantilla temporal EX.');
        }
        $zip->close();

        return $tmpDocx;
    }

    public static function applyLayoutToSavedDocx(string $docxPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el segmento EX para layout.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            throw new \RuntimeException('document.xml ilegible en segmento EX guardado.');
        }

        $xml = self::compactLayoutForSinglePage($xml);
        $xml = self::reinforceCompromisosUnderlines($xml);
        $xml = self::pruneUnusedCompromisoLineParagraphs($xml);
        $xml = self::trimEmptyParagraphsBeforeSignatureTable($xml);
        $xml = self::stripDecorativeTableColors($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            throw new \RuntimeException('No se pudo escribir layout en segmento EX.');
        }
        $zip->close();
    }

    /**
     * @return list<array{anchor: string, occ: int, macro: string}>
     */
    private static function injectionSpecs(): array
    {
        return [
            ['anchor' => 'Fecha del momento de seguimiento anterior:</w:t>', 'occ' => 0, 'macro' => 'fecha_seguimiento_anterior'],
            ['anchor' => 'Fecha del seguimiento extraordinario:</w:t>', 'occ' => 0, 'macro' => 'fecha_visita'],
            ['anchor' => 'Modalidad del seguimiento: </w:t>', 'occ' => 0, 'macro' => 'modalidad'],
            ['anchor' => 'Enlace de grabación del seguimiento extraordinario: </w:t>', 'occ' => 0, 'macro' => 'enlace_grabacion'],
            ['anchor' => 'Motivo del seguimiento extraordinario:</w:t>', 'occ' => 0, 'macro' => 'motivo_seguimiento_extraordinario'],
        ];
    }

    private static function injectNumeroVisita(string $xml): string
    {
        $updated = preg_replace(
            '/Momento -N°___/',
            'Momento -N°${numero_visita}',
            $xml,
            1
        );

        return is_string($updated) ? $updated : $xml;
    }

    private static function injectCompromisosFields(string $xml): string
    {
        $specs = [
            ['anchor' => 'Compromisos por parte del instructor de seguimiento:', 'macro' => 'obs_instructor'],
            ['anchor' => 'Compromisos por parte del aprendiz:', 'macro' => 'obs_aprendiz'],
            ['anchor' => 'Compromisos por parte del responsable ente co-formador:', 'macro' => 'obs_coformador'],
        ];

        foreach ($specs as $spec) {
            $xml = self::injectCompromisoBlock($xml, (string) $spec['anchor'], (string) $spec['macro']);
        }

        return $xml;
    }

    private static function injectCompromisoBlock(string $xml, string $labelAnchor, string $macro): string
    {
        $pos = strpos($xml, $labelAnchor);
        if ($pos === false) {
            return $xml;
        }

        $pStart = strrpos(substr($xml, 0, $pos), '<w:p ');
        if ($pStart === false) {
            $pStart = strrpos(substr($xml, 0, $pos), '<w:p>');
        }
        if ($pStart === false) {
            return $xml;
        }

        $pEnd = strpos($xml, '</w:p>', $pos);
        if ($pEnd === false) {
            return $xml;
        }
        $pEnd += strlen('</w:p>');

        $paragraph = substr($xml, $pStart, $pEnd - $pStart);
        $replacement = self::buildCompromisoBlock($paragraph, $labelAnchor, $macro);
        if ($replacement === '') {
            return $xml;
        }

        return substr($xml, 0, $pStart) . $replacement . substr($xml, $pEnd);
    }

    private static function buildCompromisoBlock(string $paragraphXml, string $labelAnchor, string $macro): string
    {
        $labelParagraph = self::buildCompromisoLabelParagraph($paragraphXml, $labelAnchor);
        if ($labelParagraph === '') {
            return '';
        }

        $block = $labelParagraph . self::buildCompromisoContentParagraph($macro);
        for ($lineIndex = 2; $lineIndex <= F023ObservationLines::TEMPLATE_SLOT_COUNT; $lineIndex++) {
            $block .= self::buildCompromisoContentParagraph($macro . '_l' . $lineIndex);
        }

        return $block;
    }

    private static function buildCompromisoLabelParagraph(string $paragraphXml, string $labelAnchor): string
    {
        if (!preg_match('/^(<w:p\b[^>]*>)(.*?)(<\/w:p>)$/s', $paragraphXml, $parts)) {
            return '';
        }

        $spacing = '<w:spacing w:after="0" w:before="0"/>';
        $pPr = '';
        if (preg_match('/<w:pPr>.*?<\/w:pPr>/s', $parts[2], $pPrMatch)) {
            $pPr = (string) $pPrMatch[0];
            $pPr = preg_replace('/<w:pBdr>.*?<\/w:pBdr>/s', '', $pPr) ?? $pPr;
            if (preg_match('/<w:spacing\b[^>]*\/>/', $pPr)) {
                $pPr = preg_replace('/<w:spacing\b[^>]*\/>/', $spacing, $pPr, 1) ?? $pPr;
            } else {
                $pPr = preg_replace('/<w:pPr>/', '<w:pPr>' . $spacing, $pPr, 1) ?? $pPr;
            }
        } else {
            $pPr = '<w:pPr>' . $spacing . '<w:jc w:val="left"/></w:pPr>';
        }

        $labelRun = '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:b/><w:bCs/><w:sz w:val="18"/><w:szCs w:val="18"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $labelAnchor . '</w:t></w:r>';

        return $parts[1] . $pPr . $labelRun . $parts[3];
    }

    private static function buildCompromisoContentParagraph(string $macro): string
    {
        return '<w:p><w:pPr>'
            . self::OBS_LINE_SPACING
            . '<w:ind w:left="0" w:right="0"/><w:jc w:val="left"/>'
            . self::OBS_LINE_BORDER
            . '</w:pPr>'
            . self::macroRunXmlForLine($macro)
            . '</w:p>';
    }

    private static function injectSignatures(string $xml): string
    {
        if (!preg_match_all('/<w:tbl\b[^>]*>.*?<\/w:tbl>/s', $xml, $tables, PREG_OFFSET_CAPTURE)) {
            return $xml;
        }

        foreach ($tables[0] as $tableMatch) {
            $table = (string) $tableMatch[0];
            $tblStart = (int) $tableMatch[1];
            if (!str_contains($table, 'Nombre y Firma del aprendiz')
                || !str_contains($table, 'ente co-formador')) {
                continue;
            }

            $patched = self::patchExSignatureTable($table);
            if ($patched === $table) {
                continue;
            }

            return substr($xml, 0, $tblStart) . $patched . substr($xml, $tblStart + strlen($table));
        }

        return $xml;
    }

    private static function patchExSignatureTable(string $table): string
    {
        if (!preg_match_all('/<w:tr\b[^>]*>.*?<\/w:tr>/s', $table, $rows, PREG_OFFSET_CAPTURE)) {
            return $table;
        }

        $labelRowIndex = null;
        foreach ($rows[0] as $index => $rowMatch) {
            if (str_contains((string) $rowMatch[0], 'Nombre y Firma del aprendiz')) {
                $labelRowIndex = $index;
                break;
            }
        }

        if ($labelRowIndex === null || $labelRowIndex === 0) {
            return $table;
        }

        $lineRow = (string) $rows[0][$labelRowIndex - 1][0];
        $lineRowStart = (int) $rows[0][$labelRowIndex - 1][1];
        $macros = [
            0 => 'nombre_aprendiz',
            2 => 'nombre_instructor_seguimiento',
            4 => 'nombre_coformador',
        ];

        if (!preg_match_all('/<w:tc\b[^>]*>.*?<\/w:tc>/s', $lineRow, $cells, PREG_OFFSET_CAPTURE)) {
            return $table;
        }

        /** @var list<array{start: int, length: int, new: string}> $replacements */
        $replacements = [];
        foreach ($macros as $cellIndex => $macro) {
            if (!isset($cells[0][$cellIndex])) {
                continue;
            }
            $cell = (string) $cells[0][$cellIndex][0];
            if (str_contains($cell, '${' . $macro . '}')) {
                continue;
            }
            $newCell = self::injectMacroInSignatureCell($cell, $macro);
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
            return $table;
        }

        usort($replacements, static fn (array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($replacements as $rep) {
            $lineRow = substr_replace($lineRow, $rep['new'], $rep['start'], $rep['length']);
        }

        return substr_replace($table, $lineRow, $lineRowStart, strlen((string) $rows[0][$labelRowIndex - 1][0]));
    }

    private static function injectMacroInSignatureCell(string $cellXml, string $macro): string
    {
        if (!preg_match('/^(<w:tc\b[^>]*>)(.*?)(<\/w:tc>)$/s', $cellXml, $parts)) {
            return $cellXml;
        }

        $inner = $parts[2];
        $tcPr = '';
        if (preg_match('/<w:tcPr>.*?<\/w:tcPr>/s', $inner, $match)) {
            $tcPr = (string) $match[0];
            if (!preg_match('/<w:vAlign\b/', $tcPr)) {
                $tcPr = preg_replace('/<\/w:tcPr>/', '<w:vAlign w:val="center"/></w:tcPr>', $tcPr, 1) ?? $tcPr;
            }
        } else {
            $tcPr = '<w:tcPr><w:vAlign w:val="center"/></w:tcPr>';
        }

        $paragraph = '<w:p><w:pPr><w:spacing w:after="0" w:before="0"/><w:jc w:val="left"/></w:pPr>'
            . self::macroRunXml($macro, true)
            . '</w:p>';

        return $parts[1] . $tcPr . $paragraph . $parts[3];
    }

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

        $lastTableEnd = strrpos($content, '</w:tbl>');
        if ($lastTableEnd !== false) {
            $lastTableEnd += strlen('</w:tbl>');
            $tail = substr($content, $lastTableEnd);
            $tail = str_replace('<w:spacing />', '<w:spacing w:after="0" w:before="0"/>', $tail);
            $content = substr($content, 0, $lastTableEnd) . $tail;
        }

        $sectPr = preg_replace('/<w:type\s+w:val="nextPage"\s*\/>/', '<w:type w:val="continuous" />', $sectPr) ?? $sectPr;
        $sectPr = F023SectionHeaderSupport::normalizeSectPrForPdfExport($sectPr, $xml);

        return $m[1] . $content . $sectPr . $m[3];
    }

    /**
     * Fin de la zona de compromisos (antes de la tabla de firmas).
     */
    private static function compromisosSectionEnd(string $xml, int $sectionStart): int
    {
        $labelPos = strpos($xml, 'Nombre y Firma del aprendiz', $sectionStart);
        if ($labelPos === false) {
            return strlen($xml);
        }

        $tblStart = strrpos(substr($xml, 0, $labelPos), '<w:tbl');
        if ($tblStart !== false && $tblStart >= $sectionStart) {
            return $tblStart;
        }

        return $labelPos;
    }

    /**
     * Quita bandas de color del tema (verde/azul) y resaltado amarillo en tablas.
     */
    private static function stripDecorativeTableColors(string $xml): string
    {
        $xml = preg_replace('/<w:tblStyle w:val="[^"]*"\s*\/>/', '', $xml) ?? $xml;
        $xml = preg_replace('/w:noHBand="0"/', 'w:noHBand="1"', $xml) ?? $xml;
        $xml = preg_replace('/w:noVBand="0"/', 'w:noVBand="1"', $xml) ?? $xml;
        $xml = preg_replace('/<w:highlight w:val="[^"]*"\s*\/>/', '', $xml) ?? $xml;

        return preg_replace_callback(
            '/<w:shd w:val="clear" w:color="auto" w:fill="([^"]+)"\s*\/>/',
            static function (array $match): string {
                return $match[1] === '262626'
                    ? $match[0]
                    : '<w:shd w:val="clear" w:color="auto" w:fill="auto"/>';
            },
            $xml
        ) ?? $xml;
    }

    private static function pruneUnusedCompromisoLineParagraphs(string $xml): string
    {
        $sectionStart = strpos($xml, 'Compromisos por parte del instructor');
        if ($sectionStart === false) {
            return $xml;
        }

        $tblPos = self::compromisosSectionEnd($xml, $sectionStart);
        $sectionEnd = $tblPos;

        /** @var list<array{start: int, length: int}> $removals */
        $removals = [];
        $searchFrom = 0;

        while (preg_match('/<w:p\b[^>]*>.*?<\/w:p>/s', $xml, $match, PREG_OFFSET_CAPTURE, $searchFrom)) {
            $paragraph = $match[0][0];
            $paraStart = $match[0][1];
            $searchFrom = $paraStart + strlen($paragraph);

            if ($paraStart < $sectionStart || $paraStart >= $sectionEnd) {
                continue;
            }

            if (!self::isRemovableUnusedCompromisoLine($paragraph)) {
                continue;
            }

            $removals[] = ['start' => $paraStart, 'length' => strlen($paragraph)];
        }

        usort($removals, static fn (array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($removals as $removal) {
            $xml = substr_replace($xml, '', $removal['start'], $removal['length']);
        }

        return $xml;
    }

    private static function isRemovableUnusedCompromisoLine(string $paragraph): bool
    {
        if (str_contains($paragraph, '${nombre_')) {
            return false;
        }

        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($paragraph)) ?? '');
        if ($plain !== '' && str_contains($plain, 'Compromisos por parte')) {
            return false;
        }

        if (preg_match('/<w:t[^>]*>[^<\s_\x{C2}\x{A0}][^<]*<\/w:t>/u', $paragraph)) {
            return false;
        }
        if (preg_match('/<w:t[^>]*>_{3,}<\/w:t>/', $paragraph)) {
            return false;
        }
        if (str_contains($paragraph, '&#160;') || str_contains($paragraph, "\xC2\xA0")) {
            return false;
        }

        return str_contains($paragraph, 'w:bottom w:val="single"');
    }

    private static function reinforceCompromisosUnderlines(string $xml): string
    {
        $sectionStart = strpos($xml, 'Compromisos por parte del instructor');
        if ($sectionStart === false) {
            return $xml;
        }

        $sectionEnd = self::compromisosSectionEnd($xml, $sectionStart);

        /** @var list<array{start: int, length: int, new: string}> $replacements */
        $replacements = [];
        $searchFrom = 0;

        while (preg_match('/<w:p\b[^>]*>.*?<\/w:p>/s', $xml, $match, PREG_OFFSET_CAPTURE, $searchFrom)) {
            $paragraph = $match[0][0];
            $paraStart = $match[0][1];
            $searchFrom = $paraStart + strlen($paragraph);

            if ($paraStart < $sectionStart || $paraStart >= $sectionEnd) {
                continue;
            }

            if (str_contains($paragraph, '${nombre_')) {
                continue;
            }

            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($paragraph)) ?? '');
            if ($plain !== '' && str_contains($plain, 'Compromisos por parte')) {
                continue;
            }

            $newParagraph = self::ensureBottomBorderLine($paragraph);
            if ($newParagraph !== $paragraph) {
                $replacements[] = [
                    'start' => $paraStart,
                    'length' => strlen($paragraph),
                    'new' => $newParagraph,
                ];
            }
        }

        usort($replacements, static fn (array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($replacements as $rep) {
            $xml = substr_replace($xml, $rep['new'], $rep['start'], $rep['length']);
        }

        return $xml;
    }

    private static function ensureBottomBorderLine(string $paragraphXml): string
    {
        if (preg_match('/<w:pBdr>.*?<\/w:pBdr>/s', $paragraphXml)) {
            $updated = preg_replace('/<w:pBdr>.*?<\/w:pBdr>/s', self::OBS_LINE_BORDER, $paragraphXml, 1);

            return is_string($updated) ? $updated : $paragraphXml;
        }
        if (str_contains($paragraphXml, '<w:pPr>')) {
            $updated = preg_replace('/<w:pPr>/', '<w:pPr>' . self::OBS_LINE_BORDER, $paragraphXml, 1);

            return is_string($updated) ? $updated : $paragraphXml;
        }

        $updated = preg_replace('/<w:p>/', '<w:p><w:pPr>' . self::OBS_LINE_BORDER, $paragraphXml, 1);

        return is_string($updated) ? $updated : $paragraphXml;
    }

    private static function trimEmptyParagraphsBeforeSignatureTable(string $xml): string
    {
        $pos = strpos($xml, 'Compromisos por parte del responsable ente co-formador');
        if ($pos === false) {
            return $xml;
        }

        $tblPos = strpos($xml, 'Nombre y Firma del aprendiz', $pos);
        if ($tblPos === false) {
            return $xml;
        }

        $beforeTbl = substr($xml, 0, $tblPos);
        $afterTbl = substr($xml, $tblPos);

        while (preg_match('/<w:p\b[^>]*>.*?<\/w:p>\s*$/s', $beforeTbl, $match)) {
            $paragraph = $match[0];
            $plain = trim(preg_replace('/\s+/u', '', strip_tags($paragraph)) ?? '');
            if ($plain !== '' || str_contains($paragraph, '${')) {
                break;
            }
            $beforeTbl = substr($beforeTbl, 0, -strlen($match[0]));
        }

        return $beforeTbl . $afterTbl;
    }

    private static function macroRunXmlForLine(string $macro): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:sz w:val="18"/><w:szCs w:val="18"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve">${' . $safe . '}</w:t></w:r>';
    }

    private static function macroRunXml(string $macro, bool $italic = false): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro) ?? '';
        $italicPr = $italic ? '<w:i/><w:iCs/>' : '';
        $boldPr = $italic ? '' : '<w:b/><w:bCs/>';

        return '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . $boldPr . $italicPr
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

    private static function normalizeFooterParagraph(string $xml): string
    {
        $anchorPos = strpos($xml, 'Ciudad____________');
        if ($anchorPos === false) {
            $anchorPos = strpos($xml, 'de diligenciamiento');
        }
        if ($anchorPos === false) {
            return $xml;
        }

        $before = substr($xml, 0, $anchorPos);
        $pStart = max((int) strrpos($before, '<w:p '), (int) strrpos($before, '<w:p>'));
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
            . self::footerMacroRun('ex_marca_presencial', false)
            . self::footerBoldRun(' o virtual ')
            . self::footerMacroRun('ex_marca_virtual', false);

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
}
