<?php

declare(strict_types=1);

namespace App\Exports;

use ZipArchive;

/**
 * Inserta marcadores ${variable} en m2.docx (valoración con X y observaciones por factor).
 */
final class F023M2TemplateMacroInjector
{
    private const DOCUMENT_XML = 'word/document.xml';

    /**
     * @throws \RuntimeException
     */
    public static function patchToTemp(string $sourceDocx): string
    {
        if (!is_file($sourceDocx)) {
            throw new \RuntimeException('Plantilla M2 no encontrada.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'f023m2_');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal.');
        }
        $tmpDocx = $tmp . '.docx';
        if (!@copy($sourceDocx, $tmpDocx)) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo copiar la plantilla M2.');
        }
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmpDocx) !== true) {
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo abrir la copia temporal de m2.docx.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('document.xml no legible en m2.docx.');
        }

        $xml = F023FactorValoracionMacroSupport::injectIntoDocumentXml($xml);

        foreach (self::injectionSpecs() as $spec) {
            $xml = self::injectAfterAnchor(
                $xml,
                (string) $spec['anchor'],
                (int) $spec['occ'],
                (string) $spec['macro']
            );
        }

        $xml = self::injectComplementaryObservationFields($xml);
        $xml = self::stripComplementaryObservationLabelBorders($xml);
        $xml = F023SignatureNameSupport::injectIntoDocumentXml($xml, 'left');
        $xml = self::normalizeFooterParagraph($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo escribir document.xml en la plantilla temporal M2.');
        }
        $zip->close();

        return $tmpDocx;
    }

    /**
     * Compacta el M2 exportado para que quepa en una sola página.
     */
    public static function applyLayoutToSavedDocx(string $docxPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el segmento M2 para layout.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            throw new \RuntimeException('document.xml ilegible en segmento M2 guardado.');
        }

        $xml = self::compactLayoutForSinglePage($xml);
        $xml = self::stripComplementaryObservationLabelBorders($xml);
        $xml = self::reinforceComplementaryObservationLines($xml);
        $xml = self::pruneUnusedObservationLineParagraphs($xml);
        $xml = self::trimEmptyParagraphsBeforeSignatureTable($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            throw new \RuntimeException('No se pudo escribir layout en segmento M2.');
        }
        $zip->close();
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
     * Tras saveAs: quita guiones residuales en renglones vacíos y asegura borde inferior en cada línea de observación.
     */
    private static function reinforceComplementaryObservationLines(string $xml): string
    {
        $sectionStart = strpos($xml, 'complementarias del instructor');
        if ($sectionStart === false) {
            return $xml;
        }

        $firmaPos = strpos($xml, 'Firma del', $sectionStart);
        $tblPos = strpos($xml, '<w:tbl>', $sectionStart);
        $sectionEnd = strlen($xml);
        if ($firmaPos !== false) {
            $sectionEnd = min($sectionEnd, self::paragraphStartBeforePosition($xml, $firmaPos));
        }
        if ($tblPos !== false) {
            $sectionEnd = min($sectionEnd, $tblPos);
        }

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

            $plain = trim(preg_replace('/\s+/u', '', strip_tags($paragraph)) ?? '');
            if ($plain !== '' && (str_contains($plain, ':') || stripos($plain, 'Firma') !== false)) {
                continue;
            }

            $newParagraph = $paragraph;
            if (preg_match('/^_{3,}$/u', $plain)) {
                $newParagraph = self::replaceParagraphPlainText($paragraph, "\u{00A0}");
                $newParagraph = self::ensureBottomBorderLine($newParagraph);
            } elseif ($plain !== '') {
                $stripped = preg_replace(
                    '/<w:r>\s*<w:rPr>.*?<\/w:rPr>\s*<w:t[^>]*>_{3,}<\/w:t><\/w:r>/s',
                    '',
                    $paragraph
                );
                $newParagraph = is_string($stripped) ? $stripped : $paragraph;
                $newParagraph = self::ensureBottomBorderLine($newParagraph);
            } else {
                $newParagraph = self::ensureBottomBorderLine($paragraph);
            }

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

    private static function paragraphStartBeforePosition(string $xml, int $textPos): int
    {
        $slice = substr($xml, 0, $textPos);
        $pStart = strrpos($slice, '<w:p ');
        if ($pStart === false) {
            $pStart = strrpos($slice, '<w:p>');
        }

        return $pStart !== false ? $pStart : $textPos;
    }

    /**
     * Quita párrafos vacíos entre las observaciones y la tabla de firmas (evita renglones extra).
     */
    private static function trimEmptyParagraphsBeforeSignatureTable(string $xml): string
    {
        $pos = strpos($xml, 'complementarias del instructor');
        if ($pos === false) {
            return $xml;
        }

        $tblPos = strpos($xml, '<w:tbl>', $pos);
        if ($tblPos === false) {
            return $xml;
        }

        $beforeTbl = substr($xml, 0, $tblPos);
        $afterTbl = substr($xml, $tblPos);

        while (preg_match('/<w:p\b[^>]*>.*?<\/w:p>\s*$/s', $beforeTbl, $match)) {
            $paragraph = $match[0];
            $plain = trim(preg_replace('/\s+/u', '', strip_tags($paragraph)) ?? '');
            if ($plain !== '' || str_contains($paragraph, 'w:bottom w:val="single"')) {
                break;
            }
            $beforeTbl = substr($beforeTbl, 0, -strlen($match[0]));
        }

        return $beforeTbl . $afterTbl;
    }

    /**
     * @return list<array{anchor: string, occ: int, macro: string}>
     */
    private static function injectionSpecs(): array
    {
        return [
            ['anchor' => '(DD/MM/AA)</w:t>', 'occ' => 0, 'macro' => 'fecha_inicio_etapa'],
            ['anchor' => 'Fecha del momento de seguimiento:</w:t>', 'occ' => 0, 'macro' => 'fecha_visita'],
            ['anchor' => 'Modalidad del seguimiento: </w:t>', 'occ' => 0, 'macro' => 'modalidad'],
            ['anchor' => 'grabación del momento 2: </w:t>', 'occ' => 0, 'macro' => 'enlace_grabacion'],
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
     * Observaciones complementarias (instructor, aprendiz, co-formador) tras las tablas de factores.
     * Cada campo usa exactamente 2 párrafos subrayados (sin espacio extra entre renglones).
     */
    private static function injectComplementaryObservationFields(string $xml): string
    {
        $sectionStart = strpos($xml, 'complementarias del instructor');
        if ($sectionStart === false) {
            return $xml;
        }

        $macros = ['obs_instructor', 'obs_aprendiz', 'obs_coformador'];
        $search = substr($xml, $sectionStart);
        $offset = 0;
        $pattern = '/<w:p\b(?:(?!<\/w:p>).)*<w:bottom w:val="single"(?:(?!<\/w:p>).)*<\/w:p>\s*'
            . '<w:p\b(?:(?!<\/w:p>).)*<\/w:p>/s';
        /** @var list<array{absStart: int, length: int, new: string}> $replacements */
        $replacements = [];

        foreach ($macros as $macro) {
            if (!preg_match($pattern, $search, $match, PREG_OFFSET_CAPTURE, $offset)) {
                break;
            }

            $block = (string) $match[0][0];
            $relStart = (int) $match[0][1];
            if (!preg_match_all('/<w:p\b[^>]*>.*?<\/w:p>/s', $block, $paragraphs) || count($paragraphs[0]) < 2) {
                $offset = $relStart + strlen($block);

                continue;
            }

            $line1 = self::normalizeComplementaryObservationParagraph((string) $paragraphs[0][0]);
            $line1 = preg_replace(
                '/<\/w:p>$/',
                self::macroRunXml($macro, false) . '</w:p>',
                $line1,
                1
            );
            if (!is_string($line1)) {
                $offset = $relStart + strlen($block);

                continue;
            }

            $line1 = self::ensureBottomBorderLine($line1);

            $line2 = self::injectComplementaryLine2Macro((string) $paragraphs[0][1], $macro);
            if ($line2 === '') {
                $offset = $relStart + strlen($block);

                continue;
            }

            $replacement = $line1 . $line2;
            for ($lineIndex = 3; $lineIndex <= F023ObservationLines::TEMPLATE_SLOT_COUNT; $lineIndex++) {
                $replacement .= self::buildExtraObservationLineParagraph($line1, $macro . '_l' . $lineIndex);
            }

            $replacements[] = [
                'absStart' => $sectionStart + $relStart,
                'length' => strlen($block),
                'new' => $replacement,
            ];
            $offset = $relStart + strlen($block);
        }

        usort($replacements, static fn (array $a, array $b): int => $b['absStart'] <=> $a['absStart']);
        foreach ($replacements as $rep) {
            $xml = substr_replace($xml, $rep['new'], $rep['absStart'], $rep['length']);
        }

        $xml = self::trimTrailingEmptyParagraphsAfterComplementaryObservations($xml);

        return $xml;
    }

    /**
     * Quita <w:pBdr></w:pBdr> vacío en títulos de observación (evita un tercer renglón visual).
     */
    private static function stripComplementaryObservationLabelBorders(string $xml): string
    {
        $textPos = strpos($xml, 'complementarias del instructor');
        if ($textPos === false) {
            return $xml;
        }

        $sectionStart = self::paragraphStartBeforePosition($xml, $textPos);

        $firmaPos = strpos($xml, 'Firma del', $sectionStart);
        $tblPos = strpos($xml, '<w:tbl>', $sectionStart);
        $sectionEnd = strlen($xml);
        if ($firmaPos !== false) {
            $sectionEnd = min($sectionEnd, self::paragraphStartBeforePosition($xml, $firmaPos));
        }
        if ($tblPos !== false) {
            $sectionEnd = min($sectionEnd, $tblPos);
        }

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

            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($paragraph)) ?? '');
            if ($plain === '' || !str_contains($plain, ':') || stripos($plain, 'Firma') !== false) {
                continue;
            }
            if (preg_match('/^_{3,}$/u', str_replace(' ', '', $plain))) {
                continue;
            }

            $newParagraph = self::stripEmptyParagraphBorder($paragraph);
            if ($newParagraph === $paragraph) {
                continue;
            }

            $replacements[] = [
                'start' => $paraStart,
                'length' => strlen($paragraph),
                'new' => $newParagraph,
            ];
        }

        usort($replacements, static fn (array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($replacements as $rep) {
            $xml = substr_replace($xml, $rep['new'], $rep['start'], $rep['length']);
        }

        return $xml;
    }

    private static function stripEmptyParagraphBorder(string $paragraphXml): string
    {
        $stripped = preg_replace('/<w:pBdr>\s*<\/w:pBdr>/', '', $paragraphXml);

        return is_string($stripped) ? $stripped : $paragraphXml;
    }

    private static function buildExtraObservationLineParagraph(string $line1Template, string $macro): string
    {
        if (!preg_match('/^(<w:p\b[^>]*>)/', $line1Template, $openTag)) {
            return '';
        }

        $paragraph = $openTag[1]
            . '<w:pPr>' . self::observationParagraphPropertiesXml() . '</w:pPr>'
            . self::macroRunXml($macro, false)
            . '</w:p>';

        return self::ensureBottomBorderLine($paragraph);
    }

    private static function injectComplementaryLine2Macro(string $paragraphXml, string $macro): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $macro . '_l2') ?? '';
        $paragraphXml = self::normalizeComplementaryObservationParagraph($paragraphXml);

        if (!preg_match('/^(<w:p\b[^>]*>)(.*?)(<\/w:p>)$/s', $paragraphXml, $parts)) {
            return '';
        }

        $pPr = '';
        if (preg_match('/<w:pPr>.*?<\/w:pPr>/s', $parts[2], $pPrMatch)) {
            $pPr = (string) $pPrMatch[0];
        }

        $rebuilt = $parts[1] . $pPr . self::macroRunXml($safe, false) . $parts[3];

        return self::ensureBottomBorderLine($rebuilt);
    }

    private static function trimTrailingEmptyParagraphsAfterComplementaryObservations(string $xml): string
    {
        $pos = strpos($xml, 'obs_coformador_l2');
        if ($pos === false) {
            return $xml;
        }

        $afterBlock = strpos($xml, '</w:p>', $pos);
        if ($afterBlock === false) {
            return $xml;
        }
        $afterBlock += strlen('</w:p>');

        $tail = substr($xml, $afterBlock);

        while (preg_match('/^\s*(<w:p\b[^>]*>.*?<\/w:p>)/s', $tail, $match)) {
            $paragraph = $match[1];
            $plain = trim(preg_replace('/\s+/u', '', strip_tags($paragraph)) ?? '');
            if ($plain !== '' || str_contains($paragraph, '${')) {
                break;
            }
            $tail = substr($tail, strlen($match[0]));
        }

        return substr($xml, 0, $afterBlock) . $tail;
    }

    /**
     * Quita renglones opcionales (l3+) que quedaron vacíos tras reemplazar macros.
     */
    private static function pruneUnusedObservationLineParagraphs(string $xml): string
    {
        $sectionStart = strpos($xml, 'complementarias del instructor');
        if ($sectionStart === false) {
            return $xml;
        }

        $firmaPos = strpos($xml, 'Firma del', $sectionStart);
        $tblPos = strpos($xml, '<w:tbl>', $sectionStart);
        $sectionEnd = strlen($xml);
        if ($firmaPos !== false) {
            $sectionEnd = min($sectionEnd, self::paragraphStartBeforePosition($xml, $firmaPos));
        }
        if ($tblPos !== false) {
            $sectionEnd = min($sectionEnd, $tblPos);
        }

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

            if (!self::isRemovableEmptyObservationLine($paragraph)) {
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

    private static function isRemovableEmptyObservationLine(string $paragraph): bool
    {
        if (preg_match('/<w:t[^>]*>[^<\s_\x{C2}\x{A0}][^<]*<\/w:t>/u', $paragraph)) {
            return false;
        }
        if (preg_match('/<w:t[^>]*>_{3,}<\/w:t>/', $paragraph)) {
            return false;
        }
        if (str_contains($paragraph, '&#160;') || str_contains($paragraph, "\xC2\xA0")) {
            return false;
        }

        return true;
    }

    private static function replaceParagraphPlainText(string $paragraphXml, string $text): string
    {
        $updated = preg_replace(
            '/(<w:t[^>]*>)[^<]*(<\/w:t>)/',
            '$1' . $text . '$2',
            $paragraphXml,
            1
        );

        return is_string($updated) ? $updated : $paragraphXml;
    }

    private static function ensureBottomBorderLine(string $paragraphXml): string
    {
        $border = self::OBS_LINE_BORDER;
        if (preg_match('/<w:pBdr>.*?<\/w:pBdr>/s', $paragraphXml)) {
            $updated = preg_replace('/<w:pBdr>.*?<\/w:pBdr>/s', $border, $paragraphXml, 1);

            return is_string($updated) ? $updated : $paragraphXml;
        }
        if (str_contains($paragraphXml, '<w:pPr>')) {
            $updated = preg_replace('/<w:pPr>/', '<w:pPr>' . $border, $paragraphXml, 1);

            return is_string($updated) ? $updated : $paragraphXml;
        }

        $updated = preg_replace('/<w:p>/', '<w:p><w:pPr>' . $border, $paragraphXml, 1);

        return is_string($updated) ? $updated : $paragraphXml;
    }

    private static function stripBottomBorderLine(string $paragraphXml): string
    {
        if (!preg_match('/<w:pBdr>.*?<\/w:pBdr>/s', $paragraphXml)) {
            return $paragraphXml;
        }
        $updated = preg_replace('/<w:pBdr>.*?<\/w:pBdr>/s', '', $paragraphXml, 1);

        return is_string($updated) ? $updated : $paragraphXml;
    }

    private static function normalizeComplementaryObservationParagraph(string $paragraphXml): string
    {
        $paragraphXml = self::applyTightLineSpacing($paragraphXml);
        $paragraphXml = self::normalizeObservationIndentation($paragraphXml);
        $stripped = preg_replace('/<w:pBdr>\s*<\/w:pBdr>/', '', $paragraphXml);
        $paragraphXml = is_string($stripped) ? $stripped : $paragraphXml;
        $stripped = preg_replace('/<w:b\/?>\s*<w:bCs\/?>/', '', $paragraphXml);

        return is_string($stripped) ? $stripped : $paragraphXml;
    }

    private static function normalizeObservationIndentation(string $paragraphXml): string
    {
        $indent = '<w:ind w:left="0" w:right="0"/>';
        if (preg_match('/<w:ind\b[^>]*\/>/', $paragraphXml)) {
            $updated = preg_replace('/<w:ind\b[^>]*\/>/', $indent, $paragraphXml, 1);

            return is_string($updated) ? $updated : $paragraphXml;
        }
        if (str_contains($paragraphXml, '<w:pPr>')) {
            $updated = preg_replace('/<w:pPr>/', '<w:pPr>' . $indent, $paragraphXml, 1);

            return is_string($updated) ? $updated : $paragraphXml;
        }

        $updated = preg_replace('/<w:p>/', '<w:p><w:pPr>' . $indent . '</w:pPr>', $paragraphXml, 1);

        return is_string($updated) ? $updated : $paragraphXml;
    }

    /** Espaciado de cada renglón de observación: alto suficiente para que el subrayado quede bajo el texto. */
    private const OBS_LINE_SPACING = '<w:spacing w:after="60" w:before="0" w:line="276" w:lineRule="exact"/>';

    /**
     * Borde de cada renglón. Word/LibreOffice fusiona párrafos contiguos con bordes idénticos en un solo
     * grupo y solo dibuja el borde inferior del último; por eso se incluye también <w:between> para que
     * cada línea de texto quede sobre su propio subrayado.
     */
    private const OBS_LINE_BORDER = '<w:pBdr>'
        . '<w:bottom w:val="single" w:color="000000" w:sz="4" w:space="1"/>'
        . '<w:between w:val="single" w:color="000000" w:sz="4" w:space="1"/>'
        . '</w:pBdr>';

    private static function observationParagraphPropertiesXml(): string
    {
        return self::OBS_LINE_SPACING
            . '<w:ind w:left="0" w:right="0"/>'
            . '<w:jc w:val="left"/>';
    }

    private static function applyTightLineSpacing(string $paragraphXml): string
    {
        $spacing = self::OBS_LINE_SPACING;
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
        $anchorPos = strpos($xml, 'Ciudad______');
        if ($anchorPos === false) {
            $anchorPos = strpos($xml, '${ciudad_diligenciamiento}');
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
            . self::footerMacroRun('m2_marca_presencial', false)
            . self::footerBoldRun(' o virtual ')
            . self::footerMacroRun('m2_marca_virtual', false);

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
