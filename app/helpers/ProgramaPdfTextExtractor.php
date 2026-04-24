<?php

declare(strict_types=1);

namespace App\Helpers;

use Smalot\PdfParser\Parser as PdfParser;

class ProgramaPdfTextExtractor
{
    /**
     * @return array{pages: array<int,string>, warnings: array<int,string>}
     */
    public static function extract(string $path, string $extension): array
    {
        if ($extension === 'txt') {
            $txt = (string) file_get_contents($path);
            return [
                'pages' => self::splitPages($txt),
                'warnings' => [],
            ];
        }

        $warnings = [];
        $pages = self::extractWithPhpParser($path);
        if ($pages === []) {
            $text = self::extractWithPdftotext($path);
            if ($text === '') {
                $warnings[] = 'No se logro extraer texto con parser PDF ni pdftotext. Se usa modo de compatibilidad limitado.';
                $text = (string) file_get_contents($path);
            }
            $pages = self::splitPages($text);
        }
        if ($pages === []) {
            $raw = (string) file_get_contents($path);
            $pages = [self::normalize($raw)];
            $warnings[] = 'No se detecto separacion por paginas; se procesa como un bloque unico.';
        }

        return [
            'pages' => $pages,
            'warnings' => $warnings,
        ];
    }

    /** @return array<int,string> */
    private static function extractWithPhpParser(string $path): array
    {
        try {
            $doc = (new PdfParser())->parseFile($path);
            $pages = [];
            foreach ($doc->getPages() as $page) {
                $txt = self::normalize((string) $page->getText());
                if ($txt !== '') {
                    $pages[] = $txt;
                }
            }
            return $pages;
        } catch (\Throwable) {
            return [];
        }
    }

    private static function extractWithPdftotext(string $path): string
    {
        $escaped = escapeshellarg($path);
        $cmd = 'pdftotext -layout ' . $escaped . ' - 2>NUL';
        $out = @shell_exec($cmd);
        return is_string($out) ? self::normalize($out) : '';
    }

    /** @return array<int,string> */
    private static function splitPages(string $text): array
    {
        $normalized = self::normalize($text);
        $parts = preg_split('/\f+|--\s*\d+\s+of\s+\d+\s*--/i', $normalized) ?: [];
        $pages = [];
        foreach ($parts as $part) {
            $page = trim($part);
            if ($page !== '') {
                $pages[] = $page;
            }
        }
        return $pages;
    }

    private static function normalize(string $text): string
    {
        $text = preg_replace('/\r\n?/', "\n", $text) ?? $text;
        $text = preg_replace('/[^\P{C}\n\t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        return trim($text);
    }
}
