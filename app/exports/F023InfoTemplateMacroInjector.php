<?php

declare(strict_types=1);

namespace App\Exports;

use ZipArchive;

/**
 * Inserta marcadores ${variable} en la primera celda de datos tras cada etiqueta de info.docx,
 * porque la plantilla oficial no trae campos de combinación de Word.
 */
final class F023InfoTemplateMacroInjector
{
    private const DOCUMENT_XML = 'word/document.xml';

    /**
     * @return list<array{anchor: string, occ: int, macro: string}>
     */
    private static function injectionSpecs(): array
    {
        return [
            ['anchor' => 'Regional:</w:t>', 'occ' => 0, 'macro' => 'regional'],
            ['anchor' => 'Centro de </w:t>', 'occ' => 0, 'macro' => 'centro_formacion'],
            ['anchor' => 'ormativo:</w:t>', 'occ' => 0, 'macro' => 'nivel_formativo'],
            ['anchor' => 'Programa de formación:</w:t>', 'occ' => 0, 'macro' => 'programa_formacion'],
            ['anchor' => 'No. Grupo:</w:t>', 'occ' => 0, 'macro' => 'numero_grupo'],
            ['anchor' => 'Presencial</w:t>', 'occ' => 0, 'macro' => 'modalidad_presencial'],
            ['anchor' => 'Virtual</w:t>', 'occ' => 0, 'macro' => 'modalidad_virtual'],
            ['anchor' => 'A Distancia</w:t>', 'occ' => 0, 'macro' => 'modalidad_distancia'],
            ['anchor' => 'ormativa:</w:t>', 'occ' => 0, 'macro' => 'estrategia_formativa'],
            ['anchor' => 'Fecha fin de la etapa lectiva:</w:t>', 'occ' => 0, 'macro' => 'fecha_fin_etapa_lectiva'],
            ['anchor' => 'ompleto:</w:t>', 'occ' => 0, 'macro' => 'nombre_completo'],
            ['anchor' => 'Tipo de documento:</w:t>', 'occ' => 0, 'macro' => 'tipo_documento'],
            ['anchor' => 'dentificación:</w:t>', 'occ' => 0, 'macro' => 'numero_documento'],
            ['anchor' => 'Contacto telefónico:</w:t>', 'occ' => 0, 'macro' => 'telefono'],
            ['anchor' => 'Dirección:</w:t>', 'occ' => 0, 'macro' => 'direccion_domicilio'],
            ['anchor' => 'Correo electrónico personal:</w:t>', 'occ' => 0, 'macro' => 'correo_personal'],
            ['anchor' => 'Correo electrónico institucional:</w:t>', 'occ' => 0, 'macro' => 'correo_institucional'],
            ['anchor' => 'Alternativa de etapa productiva registrada</w:t>', 'occ' => 0, 'macro' => 'alternativa_ep'],
            ['anchor' => 'SofiaPlus</w:t>', 'occ' => 0, 'macro' => 'fecha_registro_sofiaplus'],
            // Fila instructor: no usar el texto de la celda fusionada vertical («…instructor de seguimiento»),
            // porque el primer </w:tc><w:tc> abre la columna de etiquetas «Nombre:», no la de datos.
            ['anchor' => 'Nombre:</w:t>', 'occ' => 0, 'macro' => 'nombre_instructor_seguimiento'],
            ['anchor' => 'Contacto telefónico:</w:t>', 'occ' => 1, 'macro' => 'telefono_instructor_seguimiento'],
            ['anchor' => 'Correo electrónico institucional:</w:t>', 'occ' => 1, 'macro' => 'correo_instructor_seguimiento'],
            ['anchor' => '-formadora:</w:t>', 'occ' => 0, 'macro' => 'empresa_nombre'],
            ['anchor' => 'Dirección:</w:t>', 'occ' => 1, 'macro' => 'empresa_direccion'],
            ['anchor' => '>Nit</w:t>', 'occ' => 0, 'macro' => 'empresa_nit'],
            ['anchor' => 'Correo electrónico:</w:t>', 'occ' => 0, 'macro' => 'empresa_correo'],
            ['anchor' => '-formador del aprendiz/tutor:</w:t>', 'occ' => 0, 'macro' => 'jefe_nombre'],
            ['anchor' => 'Cargo:</w:t>', 'occ' => 0, 'macro' => 'jefe_cargo'],
            ['anchor' => 'Contacto telefónico:</w:t>', 'occ' => 2, 'macro' => 'jefe_telefono'],
            ['anchor' => 'Nombre otro contacto:</w:t>', 'occ' => 0, 'macro' => 'contacto2_nombre'],
            ['anchor' => 'nstitucional (fijo/móvil):</w:t>', 'occ' => 0, 'macro' => 'contacto2_telefono'],
            ['anchor' => 'Nombre de la persona que asiste al aprendiz:</w:t>', 'occ' => 0, 'macro' => 'asistencia_nombre'],
            ['anchor' => ' otros)</w:t>', 'occ' => 0, 'macro' => 'asistencia_tipo'],
            ['anchor' => 'Contacto telefónico:</w:t>', 'occ' => 3, 'macro' => 'asistencia_contacto'],
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
     * Copia la plantilla info.docx e inserta ${...} en las celdas de datos.
     *
     * @throws \RuntimeException
     */
    public static function patchToTemp(string $sourceDocx): string
    {
        if (!is_file($sourceDocx)) {
            throw new \RuntimeException('Plantilla info no encontrada.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'f023info_');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear archivo temporal.');
        }
        $tmpDocx = $tmp . '.docx';
        if (!@copy($sourceDocx, $tmpDocx)) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo copiar la plantilla info.');
        }
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($tmpDocx) !== true) {
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo abrir la copia temporal de info.docx.');
        }

        $xml = $zip->getFromName(self::DOCUMENT_XML);
        if ($xml === false || $xml === '') {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('document.xml no legible en info.docx.');
        }

        foreach (self::injectionSpecs() as $spec) {
            $xml = self::injectAfterAnchor(
                $xml,
                (string) $spec['anchor'],
                (int) $spec['occ'],
                (string) $spec['macro']
            );
        }

        $xml = self::compactLayoutForSinglePage($xml);

        if ($zip->locateName(self::DOCUMENT_XML) !== false) {
            $zip->deleteName(self::DOCUMENT_XML);
        }
        if (!$zip->addFromString(self::DOCUMENT_XML, $xml)) {
            $zip->close();
            @unlink($tmpDocx);
            throw new \RuntimeException('No se pudo escribir document.xml en la plantilla temporal.');
        }
        $zip->close();

        return $tmpDocx;
    }

    /**
     * La plantilla oficial deja un párrafo vacío con w:before="1540" entre la tabla de encabezado
     * y la de «Información general», lo que fuerza una segunda hoja al exportar solo info.
     */
    private static function compactLayoutForSinglePage(string $xml): string
    {
        $next = preg_replace(
            '/(<w:spacing w:after="240" w:before=")1540(")/',
            '${1}0${2}',
            $xml,
            1
        );
        if (is_string($next)) {
            $xml = $next;
        }

        $xml = self::stripEmptyParagraphsBetweenFirstTwoTables($xml);

        return self::removeTrailingEmptyParagraphAfterLastTable($xml);
    }

    private static function stripEmptyParagraphsBetweenFirstTwoTables(string $xml): string
    {
        $firstTableEnd = strpos($xml, '</w:tbl>');
        if ($firstTableEnd === false) {
            return $xml;
        }
        $firstTableEnd += strlen('</w:tbl>');
        $secondTableStart = strpos($xml, '<w:tbl>', $firstTableEnd);
        if ($secondTableStart === false) {
            return $xml;
        }

        $between = substr($xml, $firstTableEnd, $secondTableStart - $firstTableEnd);
        if (
            str_contains($between, '${')
            || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $between)
        ) {
            return $xml;
        }

        return substr($xml, 0, $firstTableEnd) . substr($xml, $secondTableStart);
    }

    private static function removeTrailingEmptyParagraphAfterLastTable(string $xml): string
    {
        $lastTableEnd = strrpos($xml, '</w:tbl>');
        if ($lastTableEnd === false) {
            return $xml;
        }
        $lastTableEnd += strlen('</w:tbl>');

        $sectPos = strrpos($xml, '<w:sectPr');
        if ($sectPos === false || $sectPos <= $lastTableEnd) {
            return $xml;
        }

        $between = substr($xml, $lastTableEnd, $sectPos - $lastTableEnd);
        if (
            str_contains($between, '${')
            || preg_match('/<w:t[^>]*>[^<\s][^<]*<\/w:t>/', $between)
        ) {
            return $xml;
        }

        return substr($xml, 0, $lastTableEnd) . substr($xml, $sectPos);
    }

    private static function injectAfterAnchor(string $xml, string $anchor, int $occurrence, string $macro): string
    {
        $pos = self::nthIndexOf($xml, $anchor, $occurrence);
        if ($pos === null) {
            return $xml;
        }

        $tail = substr($xml, $pos);
        $quoted = preg_quote($anchor, '/');
        // Grupo 1: desde el ancla hasta (incl.) el <w:pPr> opcional del párrafo de la celda de valor.
        // Grupo 2: contenido antiguo del párrafo (se sustituye por el marcador).
        // Grupo 3: cierre </w:p> del primer párrafo de la celda de valor.
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
