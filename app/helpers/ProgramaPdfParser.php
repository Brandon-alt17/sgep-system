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
        $sectionStarted = false;
        $captureResultadoFromDenominacion = false;
        $competenciaAutoIndex = 1;
        foreach ($lines as $line) {
            if (preg_match('/^4\.\s*contenidos\s+curriculares\s+de\s+la\s+competencia/iu', $line)) {
                if ($current !== null) {
                    $competencias[] = $current;
                }
                $current = [
                    'codigo' => '',
                    'nombre' => 'Competencia ' . $competenciaAutoIndex++,
                    'resultados' => [],
                ];
                $sectionStarted = true;
                $captureResultadoFromDenominacion = false;
                continue;
            }

            if (preg_match('/competencias?\s+espec[ií]ficas?|resultados?\s+de\s+aprendizaje/iu', $line)) {
                $sectionStarted = true;
            }
            if (!$sectionStarted) {
                continue;
            }

            if ($current !== null && preg_match('/^4\.3\s+nombre\s+de\s+la\s+competencia/iu', $line)) {
                $captureResultadoFromDenominacion = false;
                continue;
            }

            if ($current !== null && preg_match('/^4\.[678]\b/iu', $line)) {
                $captureResultadoFromDenominacion = false;
                continue;
            }

            if (preg_match('/^denominaci[oó]n$/iu', $line)) {
                $captureResultadoFromDenominacion = true;
                continue;
            }

            if ($captureResultadoFromDenominacion && $current !== null && !preg_match('/^4\./', $line)) {
                $current['resultados'][] = [
                    'codigo' => '',
                    'descripcion' => trim($line),
                ];
                $captureResultadoFromDenominacion = false;
                continue;
            }

            if (preg_match('/^(competencia)\b[:\-]?\s*(.*)$/iu', $line, $m)) {
                if ($current !== null) {
                    $competencias[] = $current;
                }
                $current = [
                    'codigo' => '',
                    'nombre' => trim((string) ($m[2] ?? '')),
                    'resultados' => [],
                ];
                continue;
            }

            if (preg_match('/^(resultado(?:s)?(?:\s+de\s+aprendizaje)?|ra[\.\-\s]*\d+)\b[:\-]?\s*(.*)$/iu', $line, $m)
                || preg_match('/^\d+(\.\d+)?\s+(.{12,})$/u', $line, $mNum)
            ) {
                if ($current === null) {
                    $warnings[] = ['severity' => 'warning', 'message' => 'Se detectaron resultados sin competencia activa; se agrupan en bloque temporal.'];
                    $current = ['codigo' => '', 'nombre' => 'Competencia por clasificar', 'resultados' => []];
                }
                $current['resultados'][] = [
                    'codigo' => '',
                    'descripcion' => trim((string) ($m[2] ?? $mNum[2] ?? '')),
                ];
                continue;
            }

            if ($current !== null && mb_strlen($line) > 16 && mb_strlen($line) < 280) {
                $lastIdx = count($current['resultados']) - 1;
                if ($lastIdx >= 0) {
                    $current['resultados'][$lastIdx]['descripcion'] .= ' ' . $line;
                } elseif (!preg_match('/^4\.[0-9]/', $line)) {
                    $current['nombre'] = trim(($current['nombre'] ?? 'Competencia') . ' ' . $line);
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
        if (preg_match('/denominaci[oó]n\s+del\s+programa[:\s]*\n?([^\n]{5,220})/iu', $text, $m)) {
            $candidate = trim((string) ($m[1] ?? ''));
            if ($candidate !== '' && !preg_match('/programa\s+a[uú]n/i', $candidate)) {
                return $candidate;
            }
        }
        if (preg_match('/\n([A-ZÁÉÍÓÚÑ0-9][A-ZÁÉÍÓÚÑ0-9\-\s]{8,180})\n1\.\s*INFORMACION B[ÁA]SICA/iu', $text, $m)) {
            return trim((string) ($m[1] ?? ''));
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
}
