<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Une varios PDF en orden (p. ej. info + M1 + M2).
 * Evita convertir un .docx fusionado, que LibreOffice suele renderizar mal en PDF.
 */
final class F023PdfMerge
{
    private const MIN_PDF_BYTES = 512;

    /**
     * @param list<string> $pdfPaths rutas absolutas en orden
     *
     * @throws \RuntimeException
     */
    public static function merge(array $pdfPaths, string $targetPath): void
    {
        $paths = [];
        foreach ($pdfPaths as $path) {
            $path = trim($path);
            if ($path === '' || !is_file($path) || !is_readable($path)) {
                throw new \RuntimeException('PDF intermedio no encontrado para fusionar.');
            }
            if (filesize($path) < self::MIN_PDF_BYTES) {
                throw new \RuntimeException('PDF intermedio inválido o vacío.');
            }
            $paths[] = $path;
        }

        if ($paths === []) {
            throw new \RuntimeException('No hay PDF para fusionar.');
        }

        if (count($paths) === 1) {
            if (!copy($paths[0], $targetPath)) {
                throw new \RuntimeException('No se pudo copiar el PDF generado.');
            }

            return;
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio de salida del PDF.');
        }

        if (self::mergeWithQpdf($paths, $targetPath)) {
            return;
        }
        if (self::mergeWithGhostscript($paths, $targetPath)) {
            return;
        }
        if (self::mergeWithFpdi($paths, $targetPath)) {
            return;
        }

        throw new \RuntimeException(
            'No se pudo unir los PDF. Instale qpdf o ghostscript, o ejecute composer install (FPDI).'
        );
    }

    /** @param list<string> $paths */
    private static function mergeWithQpdf(array $paths, string $targetPath): bool
    {
        $binary = self::resolveBinary(['qpdf', '/usr/bin/qpdf']);
        if ($binary === null) {
            return false;
        }

        if (is_file($targetPath)) {
            @unlink($targetPath);
        }

        $pageArgs = [];
        foreach ($paths as $path) {
            $pageArgs[] = escapeshellarg($path);
            $pageArgs[] = '1-z';
        }

        $cmd = sprintf(
            '%s --warning-exit-0 --empty --pages %s -- %s',
            escapeshellarg($binary),
            implode(' ', $pageArgs),
            escapeshellarg($targetPath)
        );

        return self::runMergeCommand($cmd, $targetPath);
    }

    /** @param list<string> $paths */
    private static function mergeWithGhostscript(array $paths, string $targetPath): bool
    {
        $binary = self::resolveBinary(['gs', 'gswin64c', 'gswin32c', '/usr/bin/gs']);
        if ($binary === null) {
            return false;
        }

        if (is_file($targetPath)) {
            @unlink($targetPath);
        }

        $inputs = implode(' ', array_map(static fn (string $p): string => escapeshellarg($p), $paths));
        $cmd = sprintf(
            '%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -sOutputFile=%s %s',
            escapeshellarg($binary),
            escapeshellarg($targetPath),
            $inputs
        );

        return self::runMergeCommand($cmd, $targetPath);
    }

    /** @param list<string> $paths */
    private static function mergeWithFpdi(array $paths, string $targetPath): bool
    {
        if (!class_exists(\setasign\Fpdi\Fpdi::class)) {
            return false;
        }

        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            foreach ($paths as $path) {
                $pageCount = $pdf->setSourceFile($path);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }
            $pdf->Output($targetPath, 'F');
        } catch (\Throwable $e) {
            log_error('F023 PDF merge FPDI: ' . $e->getMessage());
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }

            return false;
        }

        return is_file($targetPath) && filesize($targetPath) >= self::MIN_PDF_BYTES;
    }

    private static function runMergeCommand(string $cmd, string $targetPath): bool
    {
        $output = [];
        $exitCode = 1;
        exec($cmd . ' 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            $detail = trim(implode("\n", array_slice($output, -5)));
            if ($detail !== '') {
                log_error('F023 PDF merge: ' . $detail);
            }
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }

            return false;
        }

        return is_file($targetPath) && filesize($targetPath) >= self::MIN_PDF_BYTES;
    }

    /**
     * @param list<string> $candidates
     */
    private static function resolveBinary(array $candidates): ?string
    {
        foreach ($candidates as $name) {
            if (str_contains($name, '/') && is_executable($name)) {
                return $name;
            }
            $which = @shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null');
            $path = $which !== null ? trim($which) : '';
            if ($path !== '' && is_executable($path)) {
                return $path;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            foreach ($candidates as $name) {
                if (!str_ends_with(strtolower($name), '.exe')) {
                    $exe = $name . '.exe';
                    $which = @shell_exec('where ' . escapeshellarg($exe) . ' 2>nul');
                    $path = $which !== null ? trim(explode("\n", $which)[0] ?? '') : '';
                    if ($path !== '' && is_file($path)) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }
}
