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
        $accum = $firstParts['content'];
        $sectPr = $firstParts['sectPr'];

        $pageBreak = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';

        for ($i = 1, $n = count($docxPaths); $i < $n; $i++) {
            $xml = self::readZipEntry($docxPaths[$i], 'word/document.xml');
            $parts = self::splitBody($xml);
            $accum .= $pageBreak . $parts['content'];
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
