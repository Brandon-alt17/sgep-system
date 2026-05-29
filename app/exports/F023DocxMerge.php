<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Concatena el cuerpo principal (word/document.xml) de varios .docx en uno,
 * conservando el sectPr del primer archivo. Útil cuando cada bloque F-023 es una plantilla separada.
 */
final class F023DocxMerge
{
    /**
     * @param list<string> $docxPaths       rutas absolutas a .docx ya generados
     * @param bool              $m3CompactLayout aplicar compactación tras fusionar si hay segmentos M3
     * @param list<bool>|null   $m3SegmentMask   por índice: true = plantilla m3_p1 / m3_p2
     */
    public static function mergeInto(
        string $targetPath,
        array $docxPaths,
        bool $m3CompactLayout = false,
        ?array $m3SegmentMask = null
    ): void {
        if ($docxPaths === []) {
            throw new \InvalidArgumentException('No hay documentos para fusionar.');
        }

        if (count($docxPaths) === 1) {
            if (!copy($docxPaths[0], $targetPath)) {
                throw new \RuntimeException('No se pudo copiar el documento generado.');
            }

            return;
        }

        $mask = $m3SegmentMask ?? array_fill(0, count($docxPaths), false);
        $m3StartOffset = $mask[0] ? 0 : null;

        $firstXml = self::readZipEntry($docxPaths[0], 'word/document.xml');
        $firstParts = self::splitBody($firstXml);
        $accum = self::normalizeSegmentForMerge($firstParts['content'], (bool) $mask[0]);
        $sectPr = $firstParts['sectPr'];

        for ($i = 1, $n = count($docxPaths); $i < $n; $i++) {
            $xml = self::readZipEntry($docxPaths[$i], 'word/document.xml');
            $parts = self::splitBody($xml);
            $segment = self::normalizeSegmentForMerge($parts['content'], (bool) $mask[$i]);

            if ($mask[$i] && $m3StartOffset === null) {
                $m3StartOffset = strlen($accum);
            }

            if (self::needsPageBreakBeforeSegment($mask, $i)) {
                $segment = self::withPageBreakBefore($segment);
            }

            $accum .= $segment;
        }

        if ($m3CompactLayout && $m3StartOffset !== null) {
            $prefix = substr($accum, 0, $m3StartOffset);
            $m3Body = substr($accum, $m3StartOffset);
            $m3Body = self::applyM3CompactLayout($m3Body);
            $accum = $prefix . $m3Body;
        }

        $newInner = $accum . $sectPr;
        $bodyOpen = strpos($firstXml, '<w:body>');
        if ($bodyOpen === false) {
            throw new \RuntimeException('document.xml del primer archivo no contiene w:body.');
        }
        $bodyClose = strrpos($firstXml, '</w:body>');
        if ($bodyClose === false) {
            throw new \RuntimeException('document.xml del primer archivo no contiene cierre w:body.');
        }

        $mergedXml = substr($firstXml, 0, $bodyOpen + strlen('<w:body>'))
            . $newInner
            . substr($firstXml, $bodyClose);

        if (!copy($docxPaths[0], $targetPath)) {
            throw new \RuntimeException('No se pudo preparar el archivo de salida.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($targetPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el zip de salida.');
        }
        $zip->addFromString('word/document.xml', $mergedXml);
        $zip->close();
    }

    private static function applyM3CompactLayout(string $content): string
    {
        $content = self::normalizeImplicitSpacing($content);
        $content = self::stripEmptyParagraphsBetweenTables($content, true);
        $content = self::trimTrailingEmptyParagraphsAfterLastTable($content);
        $content = self::compactOuterParagraphsSpacing($content);

        return self::stripTrailingEmptyParagraphsBeforeSectPr($content);
    }

    /**
     * @param list<bool> $mask
     */
    private static function needsPageBreakBeforeSegment(array $mask, int $index): bool
    {
        if ($index <= 0) {
            return false;
        }

        // m3_p1 → m3_p2 continúan en la misma secuencia; info/m1/m2 sí saltan página.
        return !($mask[$index - 1] && $mask[$index]);
    }

    private static function withPageBreakBefore(string $content): string
    {
        if ($content === '') {
            return '';
        }

        // Segmentos M3 normalizados empiezan en <w:tbl>; el salto va antes del bloque, no dentro de una celda.
        if (str_starts_with(ltrim($content), '<w:tbl')) {
            return '<w:p><w:pPr><w:pageBreakBefore/></w:pPr></w:p>' . $content;
        }

        $pStart = self::findNextParagraphOpen($content, 0);
        if ($pStart === false) {
            return '<w:p><w:pPr><w:pageBreakBefore/></w:pPr></w:p>' . $content;
        }

        $pEnd = self::findParagraphEnd($content, $pStart);
        if ($pEnd === false) {
            return $content;
        }

        $para = substr($content, $pStart, $pEnd - $pStart);
        if (str_contains($para, '<w:pageBreakBefore')) {
            return $content;
        }

        if (preg_match('/<w:pPr\b[^>]*>/', $para, $m, PREG_OFFSET_CAPTURE)) {
            $insertAt = $pStart + $m[0][1] + strlen($m[0][0]);

            return substr($content, 0, $insertAt)
                . '<w:pageBreakBefore/>'
                . substr($content, $insertAt);
        }

        $tagEnd = strpos($content, '>', $pStart);
        if ($tagEnd === false) {
            return $content;
        }

        return substr($content, 0, $tagEnd + 1)
            . '<w:pPr><w:pageBreakBefore/></w:pPr>'
            . substr($content, $tagEnd + 1);
    }

    private static function normalizeSegmentForMerge(string $content, bool $isM3Segment): string
    {
        return $isM3Segment
            ? self::normalizeM3SegmentContent($content)
            : self::normalizeSegmentContentLight($content);
    }

    /** Normalización mínima para info / M1 / M2 y fusiones mixtas (comportamiento previo al fix M3). */
    private static function normalizeSegmentContentLight(string $content): string
    {
        return self::trimLeadingEmptyParagraphsBeforeFirstTable(
            self::stripEmbeddedSectionProperties($content)
        );
    }

    /** Normalización completa solo para segmentos M3 (m3_p1 + m3_p2). */
    private static function normalizeM3SegmentContent(string $content): string
    {
        return self::contentFromFirstTable(
            self::trimLeadingEmptyParagraphsBeforeFirstTable(
                self::stripEmptyParagraphsBetweenTables(
                    self::normalizeImplicitSpacing(
                        self::stripEmbeddedSectionProperties($content)
                    ),
                    true
                )
            )
        );
    }

    /**
     * Elimina la herencia del espaciado implícito del documento que infla la altura de los párrafos.
     *
     * 1. <w:spacing /> (sin atributos) hereda el valor predeterminado del documento: after=160 / line=259.
     *    Cada párrafo exterior (fuera de tablas) así definido añade ~8pt de espacio vacío al flujo.
     *    Lo reemplazamos con ceros explícitos.
     *
     * 2. Runs con SOLO espacios en blanco a un tamaño de fuente > 11pt (sz > 22) elevan la altura
     *    de línea del párrafo contenedor sin aportar contenido visible. Los normalizamos a sz=22.
     */
    private static function normalizeImplicitSpacing(string $content): string
    {
        return self::mapOutsideTables($content, static function (string $outer): string {
            $outer = str_replace('<w:spacing />', '<w:spacing w:after="0" w:before="0"/>', $outer);

            // Whitespace-only runs with oversized font inflate the line height without visible text.
            $outer = preg_replace_callback(
                '/<w:r\b[^>]*>(.*?)<w:t\b[^>]*>(\s+)<\/w:t>\s*<\/w:r>/s',
                static function (array $m): string {
                    if (!preg_match('/<w:sz\b[^>]*w:val="([3-9]\d|\d{3,})"/', $m[1])) {
                        return $m[0];
                    }

                    $rpr = preg_replace('/<w:sz\b[^>]*w:val="([3-9]\d|\d{3,})"/', '<w:sz w:val="22"', $m[1]);
                    $rpr = preg_replace('/<w:szCs\b[^>]*w:val="([3-9]\d|\d{3,})"/', '<w:szCs w:val="22"', $rpr);

                    return '<w:r>' . $rpr . '<w:t xml:space="preserve">' . $m[2] . '</w:t></w:r>';
                },
                $outer
            );

            return is_string($outer) ? $outer : $outer;
        });
    }

    /**
     * @param callable(string): string $transform
     */
    private static function mapOutsideTables(string $content, callable $transform): string
    {
        $result = '';
        foreach (self::splitTopLevelSegments($content) as $segment) {
            $result .= $segment['type'] === 'outer'
                ? $transform($segment['content'])
                : $segment['content'];
        }

        return $result;
    }

    /**
     * Divide el cuerpo en tramos alternos: contenido exterior y tablas de primer nivel.
     * No usar regex no codicioso con </w:tbl>: las plantillas M3 tienen tablas anidadas.
     *
     * @return list<array{type: 'outer'|'table', content: string}>
     */
    private static function splitTopLevelSegments(string $content): array
    {
        $segments = [];
        $pos = 0;
        $len = strlen($content);

        while ($pos < $len) {
            $tblStart = self::findNextTableOpen($content, $pos);
            if ($tblStart === false) {
                $tail = substr($content, $pos);
                if ($tail !== '') {
                    $segments[] = ['type' => 'outer', 'content' => $tail];
                }
                break;
            }

            if ($tblStart > $pos) {
                $segments[] = ['type' => 'outer', 'content' => substr($content, $pos, $tblStart - $pos)];
            }

            $tblEnd = self::findTopLevelTableEnd($content, $tblStart);
            if ($tblEnd === false) {
                $segments[] = ['type' => 'outer', 'content' => substr($content, $tblStart)];
                break;
            }

            $segments[] = ['type' => 'table', 'content' => substr($content, $tblStart, $tblEnd - $tblStart)];
            $pos = $tblEnd;
        }

        if ($segments === []) {
            $segments[] = ['type' => 'outer', 'content' => $content];
        }

        return $segments;
    }

    private static function findTopLevelTableEnd(string $content, int $tblStart): int|false
    {
        $tagEnd = strpos($content, '>', $tblStart);
        if ($tagEnd === false) {
            return false;
        }

        $depth = 1;
        $search = $tagEnd + 1;

        while ($depth > 0) {
            $nextOpen = self::findNextTableOpen($content, $search);
            $nextClose = strpos($content, '</w:tbl>', $search);
            if ($nextClose === false) {
                return false;
            }

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $tagEnd = strpos($content, '>', $nextOpen);
                $search = $tagEnd !== false ? $tagEnd + 1 : $nextOpen + 6;
            } else {
                $depth--;
                $search = $nextClose + 8;
            }
        }

        return $search;
    }

    private static function findNextTableOpen(string $content, int $offset): int|false
    {
        $pos = $offset;
        while (($pos = strpos($content, '<w:tbl', $pos)) !== false) {
            $after = $content[$pos + 6] ?? '';
            if ($after === '>' || $after === ' ' || $after === "\t" || $after === "\n" || $after === "\r") {
                return $pos;
            }
            $pos += 6;
        }

        return false;
    }

    /**
     * Aplica una altura de línea compacta (exact=190 twips, ~9.5pt) a los párrafos exteriores
     * (fuera de tablas) que aún conservan el espaciado after=0/before=0 sin override de línea.
     *
     * Contexto: en el documento M3 fusionado las tablas consumen ~13 775 twips de una página de
     * 14 400 twips, dejando solo ~625 twips para los dos párrafos exteriores. Con el espaciado
     * "auto" heredado (line=259/240 ≈ 1.08× la fuente), p0 ocupa ~237 twips y p1 (2 líneas) ~432,
     * total ~669 twips → desborde de 44 twips → página en blanco.
     * Con exact=190 (9.5 pt): p0 → 190 twips, p1 (2 líneas) → 380 twips, total → 570 < 625. ✓
     *
     * Solo actúa sobre el contenido exterior (fuera de w:tbl), de modo que las celdas de tabla
     * no se ven afectadas.
     */
    private static function compactOuterParagraphsSpacing(string $content): string
    {
        return self::mapOutsideTables($content, static function (string $outer): string {
            return self::mapOuterParagraphs($outer, static function (string $p): string {
                $hasFloating = str_contains($p, '<w:drawing>')
                    || str_contains($p, '<mc:AlternateContent>')
                    || str_contains($p, '<w:pict>');

                $p = str_replace('<w:spacing />', '<w:spacing w:after="0" w:before="0"/>', $p);

                if ($hasFloating) {
                    return $p;
                }

                return self::replaceNormalizedZeroSpacingWithExactLine($p);
            });
        });
    }

    /**
     * Recorre párrafos exteriores respetando </w:p> anidados (p. ej. w:txbxContent).
     *
     * @param callable(string): string $transform
     */
    private static function mapOuterParagraphs(string $outer, callable $transform): string
    {
        $result = '';
        $pos = 0;
        $len = strlen($outer);

        while ($pos < $len) {
            $pStart = self::findNextParagraphOpen($outer, $pos);
            if ($pStart === false) {
                $result .= substr($outer, $pos);

                break;
            }

            $result .= substr($outer, $pos, $pStart - $pos);
            $pEnd = self::findParagraphEnd($outer, $pStart);
            if ($pEnd === false) {
                $result .= substr($outer, $pStart);

                break;
            }

            $result .= $transform(substr($outer, $pStart, $pEnd - $pStart));
            $pos = $pEnd;
        }

        return $result;
    }

    private static function findParagraphEnd(string $content, int $pStart): int|false
    {
        $tagEnd = strpos($content, '>', $pStart);
        if ($tagEnd === false) {
            return false;
        }

        $depth = 1;
        $search = $tagEnd + 1;

        while ($depth > 0) {
            $nextOpen = self::findNextParagraphOpen($content, $search);
            $nextClose = strpos($content, '</w:p>', $search);
            if ($nextClose === false) {
                return false;
            }

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $tagEnd = strpos($content, '>', $nextOpen);
                $search = $tagEnd !== false ? $tagEnd + 1 : $nextOpen + 4;
            } else {
                $depth--;
                $search = $nextClose + 6;
            }
        }

        return $search;
    }

    private static function findNextParagraphOpen(string $content, int $offset): int|false
    {
        $pos = $offset;
        while (($pos = strpos($content, '<w:p', $pos)) !== false) {
            $after = $content[$pos + 4] ?? '';
            if ($after === '>' || $after === ' ' || $after === "\t" || $after === "\n" || $after === "\r") {
                return $pos;
            }
            $pos += 4;
        }

        return false;
    }

    private static function replaceNormalizedZeroSpacingWithExactLine(string $content): string
    {
        $variants = [
            '<w:spacing w:after="0" w:before="0"/>',
            '<w:spacing w:after="0" w:before="0" />',
            '<w:spacing w:before="0" w:after="0"/>',
            '<w:spacing w:before="0" w:after="0" />',
        ];
        $exact = '<w:spacing w:after="0" w:before="0" w:line="190" w:lineRule="exact"/>';
        foreach ($variants as $from) {
            $content = str_replace($from, $exact, $content);
        }

        return $content;
    }

    private static function readZipEntry(string $docxPath, string $entry): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('No se pudo abrir: ' . $docxPath);
        }
        $data = $zip->getFromName($entry);
        $zip->close();
        if ($data === false) {
            throw new \RuntimeException('Entrada no encontrada en docx: ' . $entry);
        }

        return $data;
    }

    /**
     * Las plantillas de momentos incluyen w:sectPr dentro del cuerpo (p. ej. type nextPage).
     * Al concatenar bloques solo debe quedar el sectPr final del primer documento.
     */
    private static function stripEmbeddedSectionProperties(string $content): string
    {
        $stripped = preg_replace('/<w:sectPr\b[^>]*>.*?<\/w:sectPr>/s', '', $content);

        return trim(is_string($stripped) ? $stripped : $content);
    }

    /**
     * Elimina párrafos vacíos entre tablas consecutivas (restos de saltos de sección en plantillas M3).
     * Maneja correctamente párrafos con elementos flotantes (dibujos con w:txbxContent anidado).
     */
    private static function stripEmptyParagraphsBetweenTables(string $content, bool $stripMiddleEmpty = false): string
    {
        $segments = self::splitTopLevelSegments($content);
        $result = '';
        $count = count($segments);

        for ($i = 0; $i < $count; $i++) {
            $segment = $segments[$i];
            if ($segment['type'] === 'table') {
                $result .= $segment['content'];
                continue;
            }

            $betweenTables = $i > 0
                && $i < $count - 1
                && $segments[$i - 1]['type'] === 'table'
                && $segments[$i + 1]['type'] === 'table';

            $result .= $betweenTables
                ? self::processGapBetweenTopLevelTables($segment['content'], $stripMiddleEmpty)
                : $segment['content'];
        }

        return $result;
    }

    private static function processGapBetweenTopLevelTables(string $gap, bool $stripMiddleEmpty): string
    {
        if (trim($gap) === '') {
            return '';
        }

        $hasDrawing = str_contains($gap, '<w:drawing>')
            || str_contains($gap, '<mc:AlternateContent>')
            || str_contains($gap, '<w:pict>');
        $hasText = str_contains($gap, '${')
            || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $gap);

        if (!$hasDrawing && !$hasText && !self::gapHasStructuralSpacing($gap)) {
            return '';
        }

        if ($hasDrawing) {
            $gap = self::stripLeadingEmptyOuterParagraphs($gap);

            return self::stripTrailingEmptyOuterParagraphsAfterDrawing($gap);
        }

        if ($stripMiddleEmpty) {
            return self::stripAllEmptySimpleParagraphs($gap);
        }

        $gap = self::stripLeadingEmptyOuterParagraphs($gap);

        return self::stripTrailingEmptyOuterParagraphs($gap);
    }

    /** Párrafos vacíos con espaciado explícito (p. ej. w:before entre tablas de info). */
    private static function gapHasStructuralSpacing(string $gap): bool
    {
        return (bool) preg_match(
            '/<w:spacing\b[^>]*\bw:(before|after)="([1-9]\d*|\d{3,})"/',
            $gap
        );
    }

    /**
     * Elimina todos los párrafos simples vacíos de un bloque de contenido.
     * "Simple" = sin </w:p> anidado (sin dibujos ni cuadros de texto), sin texto visible,
     * sin variables de plantilla, sin saltos de página explícitos.
     * Usado para limpiar párrafos vacíos intermedios (no solo los del borde) en gaps entre tablas.
     */
    private static function stripAllEmptySimpleParagraphs(string $content): string
    {
        return self::mapOuterParagraphs($content, static function (string $p): string {
            if (str_contains($p, '${')
                || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $p)
                || str_contains($p, '<w:drawing>')
                || str_contains($p, '<mc:AlternateContent>')
                || str_contains($p, '<w:pict>')
                || str_contains($p, '<w:pageBreakBefore')) {
                return $p;
            }

            return '';
        });
    }

    private static function stripLeadingEmptyOuterParagraphs(string $content): string
    {
        while (preg_match('/^\s*(<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>)/s', $content, $m)) {
            $para = $m[1];
            if (
                str_contains($para, '${')
                || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $para)
                || str_contains($para, '<w:drawing>')
                || str_contains($para, '<mc:AlternateContent>')
                || str_contains($para, '<w:pict>')
            ) {
                break;
            }

            $content = substr($content, strlen($m[0]));
        }

        return $content;
    }

    private static function stripTrailingEmptyOuterParagraphs(string $content): string
    {
        while (preg_match('/(<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>)\s*$/s', $content, $m)) {
            $para = $m[1];
            if (
                str_contains($para, '${')
                || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $para)
                || str_contains($para, '<w:drawing>')
                || str_contains($para, '<mc:AlternateContent>')
                || str_contains($para, '<w:pict>')
            ) {
                break;
            }

            $content = substr($content, 0, -strlen($m[0]));
        }

        return $content;
    }

    /**
     * Para gaps que contienen un dibujo flotante (mc:AlternateContent / w:drawing / w:pict),
     * el </w:p> que cierra el párrafo contenedor del dibujo puede tener </w:p> anidados
     * dentro del w:txbxContent. Se localiza el cierre real del párrafo contenedor mediante
     * el último tag de cierre del elemento flotante, y se eliminan párrafos vacíos simples
     * que aparezcan después de él.
     */
    private static function stripTrailingEmptyOuterParagraphsAfterDrawing(string $content): string
    {
        // Find the last drawing container close to locate the outer </w:p> that wraps the drawing.
        $lastDrawingEnd = 0;
        foreach (['</mc:AlternateContent>', '</w:drawing>', '</w:pict>'] as $closer) {
            $pos = strrpos($content, $closer);
            if ($pos !== false) {
                $pos += strlen($closer);
                if ($pos > $lastDrawingEnd) {
                    $lastDrawingEnd = $pos;
                }
            }
        }

        if ($lastDrawingEnd === 0) {
            return self::stripTrailingEmptyOuterParagraphs($content);
        }

        // The next </w:p> after the drawing close is the outer close of the drawing paragraph.
        $outerClose = strpos($content, '</w:p>', $lastDrawingEnd);
        if ($outerClose === false) {
            return $content;
        }

        $drawingParaEnd = $outerClose + 6;
        $trailing = substr($content, $drawingParaEnd);

        // Strip simple empty paragraphs from $trailing.
        $trailing = ltrim(self::stripLeadingEmptyOuterParagraphs($trailing));

        return substr($content, 0, $drawingParaEnd) . $trailing;
    }

    /**
     * Las plantillas de momentos suelen traer párrafos vacíos antes de la tabla principal
     * (restos del sectPr nextPage de la plantilla original).
     */
    private static function trimLeadingEmptyParagraphsBeforeFirstTable(string $content): string
    {
        $firstTable = strpos($content, '<w:tbl>');
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
            ) {
                break;
            }

            $leading = substr($leading, strlen($match[0]));
        }

        while (preg_match('/(<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>)\s*$/s', $leading, $match)) {
            $paragraph = $match[1];
            if (
                str_contains($paragraph, '${')
                || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $paragraph)
            ) {
                break;
            }

            $leading = substr($leading, 0, -strlen($match[0]));
        }

        return ltrim($leading) . $body;
    }

    /**
     * Elimina párrafos vacíos finales del cuerpo (antes del sectPr) que generan una hoja extra.
     */
    private static function stripTrailingEmptyParagraphsBeforeSectPr(string $content): string
    {
        while (preg_match('/(<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>)\s*$/s', $content, $m)) {
            $para = $m[1];
            if (
                str_contains($para, '${')
                || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $para)
                || str_contains($para, '<w:drawing>')
                || str_contains($para, '<mc:AlternateContent>')
                || str_contains($para, '<w:pict>')
            ) {
                break;
            }

            $content = substr($content, 0, -strlen($m[0]));
        }

        return rtrim($content);
    }

    /**
     * Quita párrafos vacíos tras la última tabla (p. ej. restos de sectPr en m3_p2).
     */
    private static function trimTrailingEmptyParagraphsAfterLastTable(string $content): string
    {
        $lastTableEnd = self::findLastTopLevelTableEnd($content);
        if ($lastTableEnd === false) {
            return $content;
        }

        $prefix = substr($content, 0, $lastTableEnd);
        $suffix = substr($content, $lastTableEnd);

        while (preg_match('/^\s*(<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>)/s', $suffix, $match)) {
            $paragraph = $match[1];
            if (
                str_contains($paragraph, '${')
                || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $paragraph)
            ) {
                break;
            }

            $suffix = substr($suffix, strlen($match[0]));
        }

        while (preg_match('/(<w:p\b[^>]*>(?:(?!<\/w:p>).)*<\/w:p>)\s*$/s', $suffix, $match)) {
            $paragraph = $match[1];
            if (
                str_contains($paragraph, '${')
                || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $paragraph)
            ) {
                break;
            }

            $suffix = substr($suffix, 0, -strlen($match[0]));
        }

        return $prefix . trim($suffix);
    }

    /**
     * Al fusionar, el cuerpo del segmento debe empezar en su tabla principal (sin preámbulo suelto).
     */
    private static function contentFromFirstTable(string $content): string
    {
        $firstTable = self::findNextTableOpen($content, 0);
        if ($firstTable === false) {
            return $content;
        }

        return substr($content, $firstTable);
    }

    private static function findLastTopLevelTableEnd(string $content): int|false
    {
        $lastEnd = false;
        $pos = 0;
        $len = strlen($content);

        while ($pos < $len) {
            $tblStart = self::findNextTableOpen($content, $pos);
            if ($tblStart === false) {
                break;
            }

            $tblEnd = self::findTopLevelTableEnd($content, $tblStart);
            if ($tblEnd === false) {
                break;
            }

            $lastEnd = $tblEnd;
            $pos = $tblEnd;
        }

        return $lastEnd;
    }

    /**
     * @return array{content: string, sectPr: string}
     */
    private static function splitBody(string $documentXml): array
    {
        if (!preg_match('#<w:body>\s*(.*?)\s*</w:body>#s', $documentXml, $m)) {
            throw new \RuntimeException('XML sin w:body.');
        }
        $inner = $m[1];
        $pos = strrpos($inner, '<w:sectPr');
        if ($pos === false) {
            return ['content' => trim($inner), 'sectPr' => ''];
        }

        return [
            'content' => trim(substr($inner, 0, $pos)),
            'sectPr' => trim(substr($inner, $pos)),
        ];
    }
}
