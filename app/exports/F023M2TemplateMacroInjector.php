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
     * @return list<array{anchor: string, occ: int, macro: string}>
     */
    private static function injectionSpecs(): array
    {
        return [
            ['anchor' => 'grabación del momento 2: </w:t>', 'occ' => 0, 'macro' => 'enlace_grabacion'],
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
