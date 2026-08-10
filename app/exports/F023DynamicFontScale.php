<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Calcula y aplica una reducción progresiva del tamaño de letra (w:sz/w:szCs, en medios-puntos
 * OOXML) para los campos de texto largo de F-023, cuando el contenido ingresado se acerca al
 * límite duro de caracteres. El contador "recomendado" del formulario ya avisa al usuario mientras
 * escribe; este helper es la garantía real en el documento generado de que, aun si el usuario
 * ignora el aviso, el texto siga cabiendo en 1 sola hoja.
 *
 * Funciones puras (sin ZipArchive) para poder testear el cálculo en aislamiento del XML real.
 */
final class F023DynamicFontScale
{
    public const DEFAULT_MAX_HALF_POINTS = 18;

    /**
     * Longitud de un texto tal como la miden hoy el contador JS (`value.length` sobre
     * `[data-max]`/`[data-recommended]`, ver public/js/app.js) y el truncado del servidor
     * (`MomentoController::applyTextLimits()`, `mb_substr($value, 0, $limit)`) — ambos cuentan
     * TODOS los caracteres, incluidos espacios. Los límites de config/f023_limites.php (p. ej.
     * `m1_actividades`, `obs_instructor`) están calibrados contra esa misma medida, así que este
     * helper debe usarla también para que el punto donde empieza a reducirse la letra coincida
     * con el punto donde el contador ya avisó "recomendado" en el formulario.
     *
     * (No confundir con el conteo "sin espacios" que usa F023ObservationLines para repartir
     * texto entre líneas físicas — es un presupuesto interno distinto, de otro propósito.)
     */
    public static function plainTextLength(string $text): int
    {
        return mb_strlen($text);
    }

    /**
     * Interpola linealmente el tamaño de fuente entre $maxHalfPoints (cuando el texto está en o
     * bajo el umbral "recomendado") y $floorHalfPoints (cuando el texto alcanza o supera el
     * límite duro), redondeando a pasos de 2 medios-puntos (1pt) para evitar variaciones
     * imperceptibles entre documentos.
     */
    public static function sizeForLength(
        int $plainTextLength,
        int $recommendedThreshold,
        int $hardMax,
        int $maxHalfPoints = self::DEFAULT_MAX_HALF_POINTS,
        int $floorHalfPoints = self::DEFAULT_MAX_HALF_POINTS
    ): int {
        if ($floorHalfPoints >= $maxHalfPoints) {
            return $maxHalfPoints;
        }
        if ($recommendedThreshold <= 0 || $hardMax <= $recommendedThreshold) {
            return $maxHalfPoints;
        }
        if ($plainTextLength <= $recommendedThreshold) {
            return $maxHalfPoints;
        }
        if ($plainTextLength >= $hardMax) {
            return $floorHalfPoints;
        }

        $progress = ($plainTextLength - $recommendedThreshold) / ($hardMax - $recommendedThreshold);
        $raw = $maxHalfPoints - $progress * ($maxHalfPoints - $floorHalfPoints);
        $stepped = (int) (round($raw / 2) * 2);

        return max($floorHalfPoints, min($maxHalfPoints, $stepped));
    }

    /**
     * Reescribe w:val de w:sz/w:szCs dentro de un fragmento XML ya acotado (un run o un rango de
     * runs que el caller extrajo). No modifica nada si el fragmento no trae w:sz/w:szCs explícito
     * (mismo comportamiento conservador que F023DocxMerge::normalizeImplicitSpacing()).
     */
    public static function rewriteRunFontSize(string $xml, int $halfPoints): string
    {
        $halfPoints = max(2, $halfPoints);

        $xml = preg_replace(
            '/<w:sz\b[^>]*w:val="\d+"[^>]*\/>/',
            '<w:sz w:val="' . $halfPoints . '"/>',
            $xml
        ) ?? $xml;

        $xml = preg_replace(
            '/<w:szCs\b[^>]*w:val="\d+"[^>]*\/>/',
            '<w:szCs w:val="' . $halfPoints . '"/>',
            $xml
        ) ?? $xml;

        return $xml;
    }

    /**
     * Los renglones de observaciones/compromisos usan interlineado "exact" (una altura fija en
     * twips, calibrada para el tamaño de letra base) para que el subrayado quede pegado al texto,
     * más un espacio fijo w:after/w:before entre renglones. Si solo se reduce w:sz sin tocar esos
     * valores fijos, cada línea deja un hueco vacío proporcional a la reducción (el texto ocupa
     * menos alto que el que Word reserva para la línea, y el espacio entre renglones deja de ser
     * proporcional al tamaño de letra ya reducido). Este método reescala w:line/w:after/w:before
     * proporcionalmente al mismo factor que se aplicó al tamaño de fuente, dentro de cualquier
     * <w:spacing w:lineRule="exact" .../> presente en el fragmento — deja intactos los
     * <w:spacing> sin w:lineRule="exact" (interlineado automático, no depende de un alto fijo).
     */
    public static function scaleExactLineSpacing(string $xml, int $targetHalfPoints, int $baseHalfPoints): string
    {
        if ($baseHalfPoints <= 0 || $targetHalfPoints >= $baseHalfPoints) {
            return $xml;
        }

        $updated = preg_replace_callback(
            '/<w:spacing\b[^>]*\/>/',
            static function (array $m) use ($targetHalfPoints, $baseHalfPoints): string {
                $tag = $m[0];
                if (!str_contains($tag, 'w:lineRule="exact"')) {
                    return $tag;
                }
                if (!preg_match('/w:line="(\d+)"/', $tag)) {
                    return $tag;
                }

                foreach (['w:line', 'w:after', 'w:before'] as $attr) {
                    if (!preg_match('/' . $attr . '="(\d+)"/', $tag, $valMatch)) {
                        continue;
                    }
                    $base = (int) $valMatch[1];
                    $new = $base === 0 ? 0 : max(1, (int) round($base * $targetHalfPoints / $baseHalfPoints));
                    $tag = preg_replace('/' . $attr . '="\d+"/', $attr . '="' . $new . '"', $tag, 1) ?? $tag;
                }

                return $tag;
            },
            $xml
        );

        return is_string($updated) ? $updated : $xml;
    }
}
