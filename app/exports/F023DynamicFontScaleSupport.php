<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Localiza un fragmento de texto largo F-023 ya sustituido (después de
 * TemplateProcessor::setValue()) y le aplica la reducción de tamaño de letra calculada por
 * F023DynamicFontScale, reutilizando el mismo patrón "ancla -> celda de etiqueta -> celda de
 * datos" ya usado en las 4 clases inyectoras (p. ej.
 * F023M1TemplateMacroInjector::tightenContentCellSpacing()).
 *
 * Cada clase inyectora mantiene su propia lista de anclas/campos y decide cuándo llamar aquí;
 * este soporte solo centraliza el cálculo (F023DynamicFontScale) y la localización/reescritura
 * de XML compartidas entre M1/M2/M3/EX.
 */
final class F023DynamicFontScaleSupport
{
    /**
     * Busca la celda de datos que sigue a la celda de etiqueta de $anchor (patrón
     * "<etiqueta>...</w:tc><w:tc>...</w:tc>" ya usado en los 4 injectors) y le aplica el tamaño
     * calculado para $configKey. $configKey debe tener contraparte '<configKey>' (referencia de
     * piso) y '<configKey>_recomendado' en config/f023_limites.php; si no existen, no hace nada
     * (comportamiento seguro, igual que el resto de estos métodos "best effort" por ancla).
     */
    public static function shrinkContentCellFontByLength(string $xml, string $anchor, string $configKey): string
    {
        $anchorPos = strpos($xml, $anchor);
        if ($anchorPos === false) {
            return $xml;
        }

        $labelCellEnd = strpos($xml, '</w:tc>', $anchorPos);
        if ($labelCellEnd === false) {
            return $xml;
        }
        $contentCellOffset = $labelCellEnd + strlen('</w:tc>');

        $contentCellEnd = strpos($xml, '</w:tc>', $contentCellOffset);
        if ($contentCellEnd === false) {
            return $xml;
        }

        $contentCell = substr($xml, $contentCellOffset, $contentCellEnd - $contentCellOffset);
        $newContentCell = self::shrinkFragmentByLength($contentCell, $configKey);
        if ($newContentCell === $contentCell) {
            return $xml;
        }

        return substr($xml, 0, $contentCellOffset) . $newContentCell . substr($xml, $contentCellEnd);
    }

    /**
     * Igual que shrinkContentCellFontByLength(), pero sobre un fragmento XML ya acotado por el
     * caller (p. ej. una celda de tabla que el caller localizó por su cuenta, en vez de una
     * celda detectable por ancla). Además de w:sz/w:szCs, reescala proporcionalmente cualquier
     * interlineado "exact" presente (evita el hueco vacío que deja un renglón subrayado cuando
     * solo se reduce la letra sin ajustar la altura de línea).
     */
    public static function shrinkFragmentByLength(string $fragmentXml, string $configKey): string
    {
        $target = self::targetSizeForLength(
            F023DynamicFontScale::plainTextLength(self::extractPlainText($fragmentXml)),
            $configKey
        );
        if ($target === null) {
            return $fragmentXml;
        }

        $fragmentXml = F023DynamicFontScale::rewriteRunFontSize($fragmentXml, $target['size']);

        return F023DynamicFontScale::scaleExactLineSpacing($fragmentXml, $target['size'], $target['maxHalfPoints']);
    }

    /**
     * Igual que shrinkFragmentByLength(), pero para una sección con varios párrafos donde algunos
     * son subtítulos/etiquetas fijas de la plantilla (p. ej. "Compromisos por parte del
     * instructor de seguimiento:") que NO deben reducirse ni contarse como parte del texto
     * ingresado — solo el formato/tamaño de los datos realmente escritos por el usuario debe
     * cambiar. $labelPrefixes son fragmentos de texto plano (sin acentos ni mayúsculas no hace
     * falta que coincidan exacto, se compara con stripos) que identifican un párrafo como
     * etiqueta si su texto los contiene.
     *
     * @param list<string> $labelPrefixes
     */
    public static function shrinkSectionExcludingLabels(string $sectionXml, string $configKey, array $labelPrefixes): string
    {
        if (!preg_match_all('/<w:p\b[^>]*>.*?<\/w:p>/s', $sectionXml, $matches, PREG_OFFSET_CAPTURE)) {
            return $sectionXml;
        }

        $dataText = '';
        foreach ($matches[0] as $match) {
            $paragraph = (string) $match[0];
            if (self::isLabelParagraph($paragraph, $labelPrefixes)) {
                continue;
            }
            $dataText .= self::extractPlainText($paragraph);
        }

        $target = self::targetSizeForLength(F023DynamicFontScale::plainTextLength($dataText), $configKey);
        if ($target === null) {
            return $sectionXml;
        }

        /** @var list<array{start: int, length: int, new: string}> $replacements */
        $replacements = [];
        foreach ($matches[0] as $match) {
            $paragraph = (string) $match[0];
            $start = (int) $match[1];
            if (self::isLabelParagraph($paragraph, $labelPrefixes)) {
                continue;
            }

            $new = F023DynamicFontScale::rewriteRunFontSize($paragraph, $target['size']);
            $new = F023DynamicFontScale::scaleExactLineSpacing($new, $target['size'], $target['maxHalfPoints']);
            if ($new !== $paragraph) {
                $replacements[] = ['start' => $start, 'length' => strlen($paragraph), 'new' => $new];
            }
        }

        usort($replacements, static fn (array $a, array $b): int => $b['start'] <=> $a['start']);
        foreach ($replacements as $rep) {
            $sectionXml = substr_replace($sectionXml, $rep['new'], $rep['start'], $rep['length']);
        }

        return $sectionXml;
    }

    private static function isLabelParagraph(string $paragraphXml, array $labelPrefixes): bool
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($paragraphXml)) ?? '');
        if ($plain === '') {
            return false;
        }

        foreach ($labelPrefixes as $prefix) {
            if (stripos($plain, $prefix) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{size: int, maxHalfPoints: int}|null
     */
    private static function targetSizeForLength(int $length, string $configKey): ?array
    {
        $resolved = self::resolveTargetSize($length, $configKey);

        // Nada que cambiar (texto bajo el umbral recomendado): evita tocar XML innecesariamente.
        return $resolved['size'] === $resolved['maxHalfPoints'] ? null : $resolved;
    }

    /**
     * Igual que targetSizeForLength() pero siempre devuelve un resultado (size=maxHalfPoints
     * cuando no aplica reducción), para callers que necesitan el tamaño "final" de antemano —
     * p. ej. F023Generator calcula aquí, ANTES de envolver el texto en líneas físicas, a qué
     * tamaño quedará la letra para poder pasarle a F023ObservationLines un ancho de línea
     * proporcionalmente mayor (ver observationCharsPerLineOverrides()) y evitar que el texto
     * quede envuelto en más líneas de las necesarias al tamaño ya reducido.
     *
     * @return array{size: int, maxHalfPoints: int}
     */
    public static function resolveTargetSize(int $length, string $configKey): array
    {
        $limites = require base_path('config/f023_limites.php');
        $maxHalfPoints = (int) ($limites['font_scale_max_half_points'] ?? F023DynamicFontScale::DEFAULT_MAX_HALF_POINTS);
        $hardMax = (int) ($limites[$configKey] ?? 0);
        $recommended = (int) ($limites[$configKey . '_recomendado'] ?? 0);
        if ($hardMax <= 0 || $recommended <= 0) {
            return ['size' => $maxHalfPoints, 'maxHalfPoints' => $maxHalfPoints];
        }

        $floorHalfPoints = (int) ($limites['font_scale_floor_half_points'] ?? $maxHalfPoints);
        $size = F023DynamicFontScale::sizeForLength($length, $recommended, $hardMax, $maxHalfPoints, $floorHalfPoints);

        return ['size' => $size, 'maxHalfPoints' => $maxHalfPoints];
    }

    private static function extractPlainText(string $xml): string
    {
        if (!preg_match_all('/<w:t\b[^>]*>(.*?)<\/w:t>/s', $xml, $m)) {
            return '';
        }

        $text = implode('', $m[1]);
        // Marcador de línea vacía de F023ObservationLines::templateLineValue() (NBSP): no es contenido real.
        $text = str_replace("\u{00A0}", '', $text);

        return html_entity_decode($text, ENT_XML1 | ENT_QUOTES);
    }
}
