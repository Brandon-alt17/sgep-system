<?php

declare(strict_types=1);

namespace App\Helpers;

class ProgramaPdfParser
{
    /**
     * @param array<int,string> $pages
     * @return array{meta: array<string,string>, competencias: array<int,array<string,mixed>>, warnings: array<int,array{severity:string,message:string}>}
     */
    public static function parsePages(array $pages): array
    {
        $cleanPages = [];
        foreach ($pages as $page) {
            $clean = self::cleanPageNoise((string) $page);
            if ($clean !== '') {
                $cleanPages[] = $clean;
            }
        }
        $flat = implode("\n", $cleanPages);
        $meta = [
            'codigo' => self::detectCodigo($flat),
            'nombre' => self::detectNombre($flat),
            'nivel' => self::detectNivel($flat),
            'modalidad' => self::detectModalidad($flat),
            'horas_lectiva' => self::firstMatch('/etapa\s+lectiva\s*[:\-]?\s*(\d{2,5})\s*horas/iu', $flat, 1),
            'horas_productiva' => self::firstMatch('/etapa\s+productiva\s*[:\-]?\s*(\d{2,5})\s*horas/iu', $flat, 1),
            'horas_total' => self::firstMatch('/total\s*[:\-]?\s*(\d{2,5})\s*horas/iu', $flat, 1),
        ];

        $warnings = [];
        if ($meta['nombre'] === '') {
            $warnings[] = ['severity' => 'warning', 'message' => 'No se detecto nombre de programa automaticamente.'];
        }

        $lines = array_values(array_filter(array_map(
            static fn ($line): string => trim((string) $line),
            explode("\n", $flat)
        ), static fn (string $line): bool => $line !== ''));

        $competencias = [];
        $current = null;
        $inCompetenciaBlock = false;
        $inResultados = false;
        $expectNombreCompetencia = false;
        $expectUnidadCompetencia = false;
        $pendingRaCode = null;
        $competenciaAutoIndex = 1;
        foreach ($lines as $line) {
            if (preg_match('/^4\.\s*&?\s*contenidos\s+curriculares\s+de\s+la\s+competencia/iu', $line)) {
                if ($current !== null) {
                    self::finalizeCompetenciaNombre($current);
                    $competencias[] = $current;
                }
                $current = [
                    'codigo' => '',
                    'nombre' => 'Competencia ' . $competenciaAutoIndex++,
                    'horas' => '',
                    'resultados' => [],
                    '_unidad_norma' => '',
                ];
                $inCompetenciaBlock = true;
                $inResultados = false;
                $expectNombreCompetencia = false;
                $expectUnidadCompetencia = false;
                continue;
            }

            if (!$inCompetenciaBlock || $current === null) {
                continue;
            }

            if (preg_match('/^4\.1\b/i', $line)) {
                $expectUnidadCompetencia = true;
                $expectNombreCompetencia = false;
                $inResultados = false;
                if (preg_match('/^4\.1\b.*?(?:norma|unidad)[^\n:]*[:\-]\s*(.+)$/iu', $line, $mUnidadInline)) {
                    $restUnidad = trim((string) ($mUnidadInline[1] ?? ''));
                    if ($restUnidad !== '' && !preg_match('/^4\./', $restUnidad)) {
                        $current['_unidad_norma'] = self::normalizeSentence($restUnidad);
                        $expectUnidadCompetencia = false;
                    }
                }
                continue;
            }

            if ($expectUnidadCompetencia) {
                if (preg_match('/^4\.2\b/i', $line)) {
                    $expectUnidadCompetencia = false;
                } elseif ($line !== '' && preg_match('/^\d{6,12}$/', $line)) {
                    if (trim((string) ($current['codigo'] ?? '')) === '') {
                        $current['codigo'] = trim($line);
                    }
                    continue;
                } elseif ($line !== '' && !preg_match('/^4\./', $line)) {
                    $piece = self::normalizeSentence($line);
                    $prevUnidad = trim((string) ($current['_unidad_norma'] ?? ''));
                    $current['_unidad_norma'] = $prevUnidad === '' ? $piece : trim($prevUnidad . ' ' . $piece);
                    continue;
                } else {
                    $expectUnidadCompetencia = false;
                }
            }

            if (preg_match('/^4\.3\b/i', $line)) {
                $expectNombreCompetencia = true;
                $inResultados = false;
                continue;
            }

            if (preg_match('/^4\.2\b/i', $line)) {
                $inResultados = false;
                $expectNombreCompetencia = false;
                $expectUnidadCompetencia = false;
                continue;
            }

            if (preg_match('/^4\.4\b/i', $line)) {
                $expectNombreCompetencia = false;
                $expectUnidadCompetencia = false;
                continue;
            }

            if (preg_match('/^4\.5\s+resultados?\s+de\s+aprendizaje/iu', $line)) {
                $inResultados = true;
                $expectNombreCompetencia = false;
                $pendingRaCode = null;
                continue;
            }

            if (preg_match('/^4\.(6|7|8|9|10)\b/iu', $line)) {
                $inResultados = false;
                $expectNombreCompetencia = false;
                $expectUnidadCompetencia = false;
                $pendingRaCode = null;
                continue;
            }

            if (preg_match('/^4\.[24]\b/iu', $line)) {
                continue;
            }

            if ($current['codigo'] === '' && preg_match('/^\d{6,12}$/', $line)) {
                $current['codigo'] = trim($line);
                continue;
            }

            if ($current['horas'] === '' && preg_match('/(\d{1,4})\s*horas?/iu', $line, $mHorasComp)) {
                $current['horas'] = (string) ($mHorasComp[1] ?? '');
                continue;
            }

            if ($expectNombreCompetencia) {
                if ($line !== '' && !preg_match('/^4\./', $line)) {
                    $candidateNombre = self::normalizeSentence($line);
                    if (preg_match('/^competencia$/iu', $candidateNombre)) {
                        continue;
                    }
                    if (self::isJunkCompetenciaNombre($candidateNombre)) {
                        continue;
                    }

                    $existingNombre = trim((string) ($current['nombre'] ?? ''));
                    if ($existingNombre === '' || preg_match('/^Competencia\s+\d+$/i', $existingNombre)) {
                        $current['nombre'] = $candidateNombre;
                    } else {
                        if (self::isJunkCompetenciaNombre($existingNombre)) {
                            $current['nombre'] = $candidateNombre;
                        } else {
                            $combined = trim($existingNombre . ' ' . $candidateNombre);
                            if (mb_strlen($combined) <= 240 && !self::isJunkCompetenciaNombre($combined)) {
                                $current['nombre'] = $combined;
                            }
                        }
                    }
                }
                continue;
            }

            if (!$inResultados) {
                continue;
            }

            if (preg_match('/^denominaci[oó]n$/iu', $line)) {
                continue;
            }

            foreach (self::splitPotentialRaSegments($line) as $segment) {
                if (preg_match('/^(RA\s*\d+)\s*[:\-]?$/iu', $segment, $mRaOnly)) {
                    $pendingRaCode = strtoupper(str_replace(' ', '', trim((string) $mRaOnly[1])));
                    continue;
                }

                if ($pendingRaCode !== null && !preg_match('/^4\./', $segment)) {
                    $current['resultados'][] = [
                        'codigo' => $pendingRaCode,
                        'descripcion' => self::cleanResultadoDescripcion($segment),
                    ];
                    $pendingRaCode = null;
                    continue;
                }

                if (preg_match('/^(RA\s*\d+)\s*[:\-]\s*(.+)$/iu', $segment, $mRa)) {
                    $current['resultados'][] = [
                        'codigo' => strtoupper(str_replace(' ', '', trim((string) $mRa[1]))),
                        'descripcion' => self::cleanResultadoDescripcion((string) $mRa[2]),
                    ];
                    continue;
                }

                if (preg_match('/^(RA\s*\d+)\s+(.+)$/iu', $segment, $mRaNoColon)) {
                    $current['resultados'][] = [
                        'codigo' => strtoupper(str_replace(' ', '', trim((string) $mRaNoColon[1]))),
                        'descripcion' => self::cleanResultadoDescripcion((string) $mRaNoColon[2]),
                    ];
                    continue;
                }

                if (self::isLikelyResultadoLine($segment)) {
                    $lastIdx = count($current['resultados']) - 1;
                    $lastDescripcion = $lastIdx >= 0 ? (string) ($current['resultados'][$lastIdx]['descripcion'] ?? '') : '';
                    if ($lastIdx >= 0 && self::shouldAppendToPreviousResultado($lastDescripcion, $segment)) {
                        $current['resultados'][$lastIdx]['descripcion'] .= ' ' . self::cleanResultadoDescripcion($segment);
                        continue;
                    }

                    $current['resultados'][] = [
                        'codigo' => 'RA' . (count($current['resultados']) + 1),
                        'descripcion' => self::cleanResultadoDescripcion($segment),
                    ];
                    continue;
                }

                if ($current['resultados'] !== [] && !preg_match('/^4\./', $segment)) {
                    $lastIdx = count($current['resultados']) - 1;
                    $current['resultados'][$lastIdx]['descripcion'] .= ' ' . self::cleanResultadoDescripcion($segment);
                }
            }
        }
        if ($current !== null) {
            self::finalizeCompetenciaNombre($current);
            $competencias[] = $current;
        }

        $competencias = array_values(array_filter($competencias, static function (array $comp): bool {
            return trim((string) ($comp['nombre'] ?? '')) !== '' || (array) ($comp['resultados'] ?? []) !== [];
        }));

        if ($competencias === []) {
            $warnings[] = ['severity' => 'error', 'message' => 'No se detectaron competencias/resultados con reglas actuales. Revise manualmente.'];
        }

        return [
            'meta' => $meta,
            'competencias' => $competencias,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array{meta: array<string,string>, competencias: array<int,array<string,mixed>>, warnings: array<int,array{severity:string,message:string}>}
     */
    public static function parseText(string $text): array
    {
        return self::parsePages([$text]);
    }

    private static function cleanPageNoise(string $page): string
    {
        $page = preg_replace('/\r\n?/', "\n", $page) ?? $page;
        $lines = explode("\n", $page);
        $filtered = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^l[ií]nea tecnol[oó]gica:/iu', $line)) {
                continue;
            }
            if (preg_match('/^red tecnol[oó]gica:/iu', $line)) {
                continue;
            }
            if (preg_match('/^red de conocimiento:/iu', $line)) {
                continue;
            }
            if (preg_match('/^p[aá]gina\s+\d+\s+de\s+\d+/iu', $line)) {
                continue;
            }
            if (preg_match('/^--\s*\d+\s+of\s+\d+\s*--$/i', $line)) {
                continue;
            }
            $filtered[] = $line;
        }
        return implode("\n", $filtered);
    }

    private static function detectCodigo(string $text): string
    {
        $code = self::firstMatch('/c[oó]digo\s+programa[^\d]*([0-9]{5,8})/iu', $text, 1);
        if ($code !== '') {
            return $code;
        }
        return self::firstMatch('/\b([0-9]{5,8})\b/u', $text, 1);
    }

    private static function detectNombre(string $text): string
    {
        if (preg_match('/1[\.\s]*1\b[^\n]*denominaci[oó]n[^\n]*\n?(.*?)\n1[\.\s]*2\b/isu', $text, $mBlock)) {
            $block = trim((string) ($mBlock[1] ?? ''));
            if ($block !== '') {
                $candidates = array_values(array_filter(array_map(
                    static fn ($line): string => trim((string) $line),
                    preg_split('/\R/u', $block) ?: []
                ), static fn (string $line): bool => $line !== ''));

                foreach ($candidates as $candidate) {
                    $invalid = self::isInvalidProgramNameCandidate($candidate);
                    if (!$invalid) {
                        return self::normalizeSentence($candidate);
                    }
                }
            }
        }

        if (preg_match('/1[\.\s]*1\b.*denominaci[oó]n(?:\s+del\s+programa)?\s*[:\-]?\s*(.+)$/imu', $text, $inline)) {
            $candidateInline = trim((string) ($inline[1] ?? ''));
            $invalidInline = self::isInvalidProgramNameCandidate($candidateInline);
            if (!$invalidInline) {
                return self::normalizeSentence($candidateInline);
            }
        }

        $lines = array_values(array_filter(array_map(
            static fn ($line): string => trim((string) $line),
            explode("\n", $text)
        ), static fn (string $line): bool => $line !== ''));

        $total = count($lines);
        for ($i = 0; $i < $total; $i++) {
            if (!preg_match('/^1[\.\s]*1\b.*denominaci[oó]n/i', $lines[$i])) {
                continue;
            }
            for ($j = $i + 1; $j < min($total, $i + 8); $j++) {
                $candidate = trim($lines[$j]);
                $invalidFallback = self::isInvalidProgramNameCandidate($candidate);
                if ($invalidFallback) {
                    continue;
                }
                if (preg_match('/^1\.[2-9]/', $candidate)) {
                    break;
                }
                return self::normalizeSentence($candidate);
            }
        }
        return '';
    }

    private static function detectNivel(string $text): string
    {
        // Caso ideal: encabezado seguido del nivel en la linea siguiente.
        $value = self::firstMatch('/t[ií]tulo\s+o\s+certificado\s+que\s+obtendr[aá][^\n]*\n?([A-ZÁÉÍÓÚÑ ]{4,60})/iu', $text, 1);
        if ($value !== '') {
            $normalized = self::normalizeNivel($value);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        // Fallback: a veces el nivel viene en la misma linea del encabezado.
        $valueInline = self::firstMatch('/t[ií]tulo\s+o\s+certificado\s+que\s+obtendr[aá][^:\n]*[:\-]?\s*([^\n]+)/iu', $text, 1);
        if ($valueInline !== '') {
            $normalizedInline = self::normalizeNivel($valueInline);
            if ($normalizedInline !== '') {
                return $normalizedInline;
            }
        }

        // Fallback general: buscar nivel de formacion.
        $valueNearNivel = self::firstMatch('/nivel\s+de\s+formaci[oó]n[^:\n]*[:\-]?\s*([^\n]+)/iu', $text, 1);
        if ($valueNearNivel !== '') {
            $normalizedNearNivel = self::normalizeNivel($valueNearNivel);
            if ($normalizedNearNivel !== '') {
                return $normalizedNearNivel;
            }
        }

        // Ultimo recurso: detectar keywords de nivel en el documento.
        $valueGlobal = self::firstMatch('/\b(tecn[oó]logo|t[eé]cnico(?:\s+laboral)?|auxiliar|operario|especializaci[oó]n)\b/iu', $text, 1);
        return self::normalizeNivel($valueGlobal);
    }

    private static function detectModalidad(string $text): string
    {
        $mode = self::firstMatch('/modalidad\s*[:\-]?\s*(presencial|virtual|mixta)/iu', $text, 1);
        return $mode !== '' ? mb_convert_case(trim($mode), MB_CASE_TITLE) : '';
    }

    private static function firstMatch(string $pattern, string $subject, int $group): string
    {
        if (preg_match($pattern, $subject, $m)) {
            return trim((string) ($m[$group] ?? ''));
        }
        return '';
    }

    private static function cleanResultadoDescripcion(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\(\s*\d+\s*horas?\s*\)\.?$/iu', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return trim($value);
    }

    private static function normalizeSentence(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        return $value;
    }

    private static function normalizeNivel(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return '';
        }
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        $v = mb_strtolower($v);

        if (preg_match('/\btecn[oó]logo\b/u', $v)) {
            return 'Tecnólogo';
        }
        if (preg_match('/\bt[eé]cnico\s+laboral\b/u', $v)) {
            return 'Técnico Laboral';
        }
        if (preg_match('/\bt[eé]cnico\b/u', $v)) {
            return 'Técnico';
        }
        if (preg_match('/\bauxiliar\b/u', $v)) {
            return 'Auxiliar';
        }
        if (preg_match('/\boperario\b/u', $v)) {
            return 'Operario';
        }
        if (preg_match('/\bespecializaci[oó]n\b/u', $v)) {
            return 'Especialización';
        }

        return '';
    }

    /**
     * Si no hubo nombre en 4.3 (sigue el placeholder "Competencia N"), usar 4.1 norma/unidad de competencia (p. ej. etapa práctica).
     *
     * @param array<string,mixed> $comp
     */
    private static function finalizeCompetenciaNombre(array &$comp): void
    {
        $nombre = trim((string) ($comp['nombre'] ?? ''));
        $unidad = trim((string) ($comp['_unidad_norma'] ?? ''));
        $codigo = trim((string) ($comp['codigo'] ?? ''));
        $isGenericPlaceholder = $nombre === '' || preg_match('/^Competencia\s+\d+$/i', $nombre) === 1;
        $junkNombre = self::isJunkCompetenciaNombre($nombre);
        $needsFallback = $isGenericPlaceholder || $junkNombre;
        $unidadTitulo = self::cleanUnidadNormaParaTitulo($unidad);
        if ($needsFallback && $unidadTitulo !== '') {
            $comp['nombre'] = self::normalizeSentence($unidadTitulo);
        } elseif ($needsFallback && $codigo === '999999999') {
            $comp['nombre'] = 'RESULTADOS DE APRENDIZAJE ETAPA PRÁCTICA';
        }
        unset($comp['_unidad_norma']);
    }

    private static function isJunkCompetenciaNombre(string $nombre): bool
    {
        $n = trim($nombre);
        if ($n === '') {
            return true;
        }
        if (preg_match('/p[aá]gina\s+\d+\s+de\s*\d*/iu', $n)) {
            return true;
        }
        if (preg_match('/\b\d{1,2}\/\d{1,2}\/\d{2,4}\b/u', $n) && preg_match('/\b(de|del)\s*\d{3,5}\s*\/\s*\d{1,2}\s*\/\s*\d{1,2}/iu', $n)) {
            return true;
        }
        if (preg_match('/^\d{5,12}$/', $n)) {
            return true;
        }
        return false;
    }

    private static function cleanUnidadNormaParaTitulo(string $raw): string
    {
        $u = trim($raw);
        if ($u === '') {
            return '';
        }
        $u = preg_replace('/^\s*COMPETENCIA\s+/iu', '', $u) ?? $u;
        $u = preg_replace('/\s+\d{6,12}\s*$/u', '', $u) ?? $u;
        $u = trim($u);
        if ($u === '' || preg_match('/^COMPETENCIA$/iu', $u)) {
            return '';
        }
        if (preg_match('/^\d{5,12}$/', $u)) {
            return '';
        }
        return self::normalizeSentence($u);
    }

    private static function isInvalidProgramNameCandidate(string $candidate): bool
    {
        $value = trim($candidate);
        if ($value === '') {
            return true;
        }
        if (preg_match('/^(del\s+programa:?|programa:?)$/iu', $value)) {
            return true;
        }
        if (preg_match('/^\d+$/', $value)) {
            return true;
        }
        if (preg_match('/\bprograma\b[\s\S]{0,60}\bvigente\b/iu', $value)) {
            return true;
        }
        if (preg_match('/software\s+de\s+software/iu', $value)) {
            return true;
        }
        return false;
    }

    private static function isLikelyResultadoLine(string $line): bool
    {
        $normalized = trim($line);
        if ($normalized === '') {
            return false;
        }
        if (preg_match('/^4\./i', $normalized)) {
            return false;
        }
        if (preg_match('/^(denominaci[oó]n|c[oó]digo\s*ra|resultado[s]?\s+de\s+aprendizaje)$/iu', $normalized)) {
            return false;
        }
        if (preg_match('/^aprendizaje\s*\(?\s*horas?\s*\)?$/iu', $normalized)) {
            return false;
        }
        if (preg_match('/\b(aprendizaje|denominaci[oó]n)\b.*\bhoras?\b/iu', $normalized)) {
            return false;
        }
        if (preg_match('/^\(?\s*horas?\s*\)?$/iu', $normalized)) {
            return false;
        }
        return mb_strlen($normalized) >= 12 && preg_match('/[a-záéíóúñ]/iu', $normalized) === 1;
    }

    private static function shouldAppendToPreviousResultado(string $previous, string $current): bool
    {
        $prev = trim($previous);
        $curr = trim($current);
        if ($prev === '' || $curr === '') {
            return false;
        }
        if (preg_match('/[.!?)]$/u', $prev)) {
            return false;
        }
        return true;
    }

    /** @return array<int,string> */
    private static function splitPotentialRaSegments(string $line): array
    {
        $normalized = preg_replace('/\s+/', ' ', trim($line)) ?? trim($line);
        if ($normalized === '') {
            return [];
        }

        $normalized = preg_replace('/([.;)])\s+(RA\s*\d+\s*[:\-])/iu', '$1' . "\n" . '$2', $normalized) ?? $normalized;
        $parts = array_values(array_filter(array_map(
            static fn ($part): string => trim((string) $part),
            explode("\n", $normalized)
        ), static fn (string $part): bool => $part !== ''));

        return $parts === [] ? [$normalized] : $parts;
    }
}
