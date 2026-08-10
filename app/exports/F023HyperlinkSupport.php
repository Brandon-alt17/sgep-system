<?php

declare(strict_types=1);

namespace App\Exports;

use ZipArchive;

/**
 * Convierte el texto plano ya sustituido (por PhpWord TemplateProcessor::setValue) de un
 * enlace de grabación en un hipervínculo real de Word (<w:hyperlink> + relación en
 * word/_rels/document.xml.rels), operando sobre el .docx ya guardado (el valor real solo
 * se conoce después de la sustitución de macros).
 */
final class F023HyperlinkSupport
{
    private const DOCUMENT_XML = 'word/document.xml';
    private const RELS_PATH = 'word/_rels/document.xml.rels';

    /**
     * Busca el primer texto con forma de URL tras $anchorText y lo envuelve en un
     * hipervínculo real. No hace nada (ni lanza error) si no encuentra el ancla o el
     * texto no parece una URL — el enlace simplemente queda como texto plano, igual que hoy.
     */
    public static function applyToDocx(string $docxPath, string $anchorText): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();

            return;
        }

        $result = self::linkAfterAnchor($xml, $anchorText);
        if ($result === null) {
            $zip->close();

            return;
        }

        [$newXml, $relationshipId, $url] = $result;

        $relsXml = $zip->getFromName(self::RELS_PATH);
        if ($relsXml === false || $relsXml === '') {
            $zip->close();
            log_error('F023 hipervínculo: no se encontró ' . self::RELS_PATH . ' en ' . $docxPath);

            return;
        }

        $newRelsXml = self::addRelationship($relsXml, $relationshipId, $url);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $newXml)) {
            $zip->close();
            log_error('F023 hipervínculo: no se pudo escribir ' . self::DOCUMENT_XML . ' en ' . $docxPath);

            return;
        }

        if ($zip->locateName(self::RELS_PATH) !== false) {
            $zip->deleteName(self::RELS_PATH);
        }
        if (!$zip->addFromString(self::RELS_PATH, $newRelsXml)) {
            $zip->close();
            log_error('F023 hipervínculo: no se pudo escribir ' . self::RELS_PATH . ' en ' . $docxPath);

            return;
        }

        if (!$zip->close()) {
            log_error('F023 hipervínculo: no se pudo finalizar el zip de ' . $docxPath);
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null [xml, relationshipId, url]
     */
    private static function linkAfterAnchor(string $xml, string $anchorText): ?array
    {
        $anchorPos = strpos($xml, $anchorText);
        if ($anchorPos === false) {
            log_error('F023 hipervínculo: ancla no encontrada (' . $anchorText . ')');

            return null;
        }

        // Ventana acotada tras el ancla: el valor real está en la celda de datos inmediatamente
        // siguiente, no en cualquier parte del resto del documento.
        $tail = substr($xml, $anchorPos, 3000);
        if (!preg_match('/<w:t[^>]*>\s*(https?:\/\/[^<]+?)\s*<\/w:t>/u', $tail, $m, PREG_OFFSET_CAPTURE)) {
            // Sin URL válida tras el ancla (campo vacío o texto no reconocido como link): no hacer nada.
            return null;
        }

        $url = trim(html_entity_decode((string) $m[1][0], ENT_XML1 | ENT_QUOTES));
        $runMatchPos = $anchorPos + (int) $m[0][1];
        $runMatchXml = (string) $m[0][0];

        $runStart = strrpos(substr($xml, 0, $runMatchPos), '<w:r>');
        if ($runStart === false) {
            $runStart = strrpos(substr($xml, 0, $runMatchPos), '<w:r ');
        }
        if ($runStart === false) {
            return null;
        }
        $runEnd = strpos($xml, '</w:r>', $runMatchPos);
        if ($runEnd === false) {
            return null;
        }
        $runEnd += strlen('</w:r>');

        $run = substr($xml, $runStart, $runEnd - $runStart);
        if (str_contains($run, $runMatchXml) === false) {
            return null;
        }

        $relationshipId = 'rIdF023Link' . substr(md5($url . $runStart), 0, 8);

        $hyperlinkRun = self::styleRunAsHyperlink($run);
        $hyperlink = '<w:hyperlink r:id="' . $relationshipId . '" w:history="1">' . $hyperlinkRun . '</w:hyperlink>';

        $newXml = substr($xml, 0, $runStart) . $hyperlink . substr($xml, $runEnd);

        return [$newXml, $relationshipId, $url];
    }

    /** Agrega color de enlace + subrayado a la rPr del run, sin perder el resto de su formato. */
    private static function styleRunAsHyperlink(string $runXml): string
    {
        $linkFormatting = '<w:color w:val="0563C1"/><w:u w:val="single"/>';

        if (preg_match('/<w:rPr>/', $runXml)) {
            $updated = preg_replace('/<w:rPr>/', '<w:rPr>' . $linkFormatting, $runXml, 1);

            return is_string($updated) ? $updated : $runXml;
        }

        $updated = preg_replace(
            '/^(<w:r\b[^>]*>)/',
            '$1<w:rPr>' . $linkFormatting . '</w:rPr>',
            $runXml,
            1
        );

        return is_string($updated) ? $updated : $runXml;
    }

    private static function addRelationship(string $relsXml, string $id, string $url): string
    {
        if (str_contains($relsXml, 'Id="' . $id . '"')) {
            return $relsXml;
        }

        $safeUrl = htmlspecialchars($url, ENT_XML1 | ENT_QUOTES);
        $relationship = '<Relationship Id="' . $id . '" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" '
            . 'Target="' . $safeUrl . '" TargetMode="External"/>';

        $updated = preg_replace('/<\/Relationships>/', $relationship . '</Relationships>', $relsXml, 1);

        return is_string($updated) ? $updated : $relsXml;
    }
}
