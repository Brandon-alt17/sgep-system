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
     * @param list<string> $docxPaths rutas absolutas a .docx ya generados
     */
    public static function mergeInto(string $targetPath, array $docxPaths): void
    {
        if ($docxPaths === []) {
            throw new \InvalidArgumentException('No hay documentos para fusionar.');
        }

        if (count($docxPaths) === 1) {
            if (!copy($docxPaths[0], $targetPath)) {
                throw new \RuntimeException('No se pudo copiar el documento generado.');
            }

            return;
        }

        $firstXml = self::readZipEntry($docxPaths[0], 'word/document.xml');
        $firstParts = self::splitBody($firstXml);
        $accum = self::stripEmbeddedSectionProperties($firstParts['content']);
        $sectPr = $firstParts['sectPr'];

        for ($i = 1, $n = count($docxPaths); $i < $n; $i++) {
            $xml = self::readZipEntry($docxPaths[$i], 'word/document.xml');
            $parts = self::splitBody($xml);
            $segmentContent = self::stripEmbeddedSectionProperties($parts['content']);
            $segmentContent = self::trimLeadingEmptyParagraphsBeforeFirstTable($segmentContent);
            $accum .= self::withPageBreakBefore($segmentContent);
        }

        $newInner = $accum . $sectPr;
        if (!preg_match('#(<w:body>)(.*)(</w:body>)#s', $firstXml, $m)) {
            throw new \RuntimeException('document.xml del primer archivo no contiene w:body.');
        }
        $mergedXml = $m[1] . $newInner . $m[3];

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
     * Evita una hoja en blanco cuando el bloque anterior ya terminó al pie de página:
     * pageBreakBefore en el siguiente bloque no duplica el salto como w:br al final.
     */
    private static function withPageBreakBefore(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $pos = strpos($content, '<w:p');
        if ($pos === false) {
            return '<w:p><w:pPr><w:pageBreakBefore/></w:pPr></w:p>' . $content;
        }

        $pTagEnd = strpos($content, '>', $pos);
        if ($pTagEnd === false) {
            return $content;
        }

        $afterPOpen = $pTagEnd + 1;
        if (str_starts_with(substr($content, $afterPOpen, 5), '<w:pP')) {
            $pPrEnd = strpos($content, '>', $afterPOpen);
            if ($pPrEnd === false) {
                return $content;
            }

            return substr($content, 0, $pPrEnd + 1)
                . '<w:pageBreakBefore/>'
                . substr($content, $pPrEnd + 1);
        }

        return substr($content, 0, $afterPOpen)
            . '<w:pPr><w:pageBreakBefore/></w:pPr>'
            . substr($content, $afterPOpen);
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
