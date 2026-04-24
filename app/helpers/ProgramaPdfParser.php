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
        $pendingRaCode = null;
        $competenciaAutoIndex = 1;
        foreach ($lines as $line) {
            if (preg_match('/^4\.\s*&?\s*contenidos\s+curriculares\s+de\s+la\s+competencia/iu', $line)) {
                if ($current !== null) {
                    $competencias[] = $current;
                }
                $current = [
                    'codigo' => '',
                    'nombre' => 'Competencia ' . $competenciaAutoIndex++,
                    'horas' => '',
                    'resultados' => [],
                ];
                $inCompetenciaBlock = true;
                $inResultados = false;
                $expectNombreCompetencia = false;
                continue;
            }

            if (!$inCompetenciaBlock || $current === null) {
                continue;
            }

            if (preg_match('/^4\.3\b/i', $line)) {
                $expectNombreCompetencia = true;
                $inResultados = false;
                continue;
            }

            if (preg_match('/^4\.2\b/i', $line)) {
                $inResultados = false;
                $expectNombreCompetencia = false;
                continue;
            }

            if (preg_match('/^4\.4\b/i', $line)) {
                $expectNombreCompetencia = false;
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
                $pendingRaCode = null;
                continue;
            }

            if (preg_match('/^4\.[124]\b/iu', $line)) {
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

                    $existingNombre = trim((string) ($current['nombre'] ?? ''));
                    if ($existingNombre === '' || preg_match('/^Competencia\s+\d+$/i', $existingNombre)) {
                        $current['nombre'] = $candidateNombre;
                    } else {
                        $combined = trim($existingNombre . ' ' . $candidateNombre);
                        if (mb_strlen($combined) <= 240) {
                            $current['nombre'] = $combined;
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

                if ($current['resultados'] !== [] && !preg_match('/^4\./', $segment)) {
                    $lastIdx = count($current['resultados']) - 1;
                    $current['resultados'][$lastIdx]['descripcion'] .= ' ' . self::cleanResultadoDescripcion($segment);
                }
            }
        }
        if ($current !== null) {
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
                if ($candidate === '' || preg_match('/^(del\s+programa|programa:?)$/i', $candidate)) {
                    continue;
                }
                if (preg_match('/programa\s+a[uú]n\s+se\s+encuentra\s+vigente/i', $candidate)) {
                    continue;
                }
                if (preg_match('/^\d+$/', $candidate)) {
                    continue;
                }
                if (preg_match('/^1\.[2-9]/', $candidate)) {
                    break;
                }
                return self::normalizeSentence($candidate);
            }
        }

        if (preg_match('/(?:^|\n)([A-ZÁÉÍÓÚÑ0-9][A-ZÁÉÍÓÚÑ0-9\-\s]{8,180})\n1\.\s*INFORMACION B[ÁA]SICA/iu', $text, $m)) {
            return self::normalizeSentence((string) ($m[1] ?? ''));
        }
        return '';
    }

    private static function detectNivel(string $text): string
    {
        $value = self::firstMatch('/t[ií]tulo\s+o\s+certificado\s+que\s+obtendr[aá][^\n]*\n?([A-ZÁÉÍÓÚÑ ]{4,40})/iu', $text, 1);
        if ($value !== '') {
            return mb_convert_case(trim($value), MB_CASE_TITLE);
        }
        return '';
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
