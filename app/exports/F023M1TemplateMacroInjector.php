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

        $xml = self::applyInlineReplacements($xml);

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

    private static function applyInlineReplacements(string $xml): string
    {
        $xml = str_replace('Ciudad____________', '${ciudad_diligenciamiento}', $xml);
        $xml = str_replace('resencial ___ o ', 'resencial ${m1_marca_presencial} o ', $xml);
        $xml = str_replace('irtual _</w:t>', 'irtual ${m1_marca_virtual}</w:t>', $xml);

        $pattern = '/de diligenciamiento<\/w:t><\/w:r>(?s).*?\/____ de forma <\/w:t>/u';
        $replacement = 'de diligenciamiento</w:t></w:r>' . self::macroRunXml('fecha_diligenciamiento')
            . '<w:r><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>'
            . '<w:b/><w:bCs/><w:color w:val="000000"/><w:sz w:val="20"/><w:szCs w:val="20"/>'
            . '<w:lang w:val="es-CO" w:eastAsia="es-CO"/></w:rPr>'
            . '<w:t xml:space="preserve"> de forma </w:t></w:r>';

        $next = preg_replace($pattern, $replacement, $xml, 1);
        if (is_string($next)) {
            $xml = $next;
        }

        return $xml;
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
