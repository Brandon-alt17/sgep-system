<?php

declare(strict_types=1);

namespace App\Exports;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

/**
 * Convierte un .docx ya generado a PDF con LibreOffice headless (fidelidad cercana al Word).
 */
final class F023DocxToPdf
{
    private const MIN_PDF_BYTES = 512;

    /**
     * Escribe `…/mismo_nombre.pdf` junto al .docx y devuelve la ruta absoluta al PDF.
     *
     * @throws \RuntimeException
     */
    public static function convert(string $docxPath): string
    {
        if (!is_file($docxPath) || !is_readable($docxPath)) {
            throw new \RuntimeException('Archivo Word no encontrado o ilegible para PDF.');
        }

        $binary = self::resolveLibreOfficeBinary();
        if ($binary === null) {
            if (self::allowDompdfFallback()) {
                return self::convertWithPhpWordDompdf($docxPath);
            }

            throw new \RuntimeException(
                'La exportación PDF requiere LibreOffice instalado en el servidor. '
                . 'Use Word (.docx) o instale LibreOffice y configure F023_LIBREOFFICE_PATH en .env.'
            );
        }

        return self::convertWithLibreOffice($docxPath, $binary);
    }

    /** Indica si LibreOffice (o el fallback Dompdf habilitado) está disponible. */
    public static function isAvailable(): bool
    {
        return self::resolveLibreOfficeBinary() !== null || self::allowDompdfFallback();
    }

    /** Mensaje para la UI cuando PDF no puede generarse; null si hay motor disponible. */
    public static function availabilityMessage(): ?string
    {
        if (self::isAvailable()) {
            return null;
        }

        return 'La exportación PDF requiere LibreOffice en este equipo. Puede usar Word (.docx) '
            . 'o pedir al administrador que instale LibreOffice.';
    }

    private static function convertWithLibreOffice(string $docxPath, string $binary): string
    {
        $pdfPath = self::pdfPathForDocx($docxPath);
        $outDir = dirname($docxPath);

        if (is_file($pdfPath)) {
            @unlink($pdfPath);
        }

        $profileDir = self::isolatedUserProfileDir();
        $profileUri = 'file://' . str_replace('\\', '/', $profileDir);
        if (PHP_OS_FAMILY === 'Windows' && !str_starts_with($profileUri, 'file:///')) {
            $profileUri = 'file:///' . ltrim(str_replace('\\', '/', $profileDir), '/');
        }

        $cmd = sprintf(
            '%s --headless --invisible --nologo --nodefault --norestore -env:UserInstallation=%s --convert-to pdf --outdir %s %s',
            escapeshellarg($binary),
            escapeshellarg($profileUri),
            escapeshellarg($outDir),
            escapeshellarg($docxPath)
        );

        $output = [];
        $exitCode = 1;
        exec($cmd . ' 2>&1', $output, $exitCode);

        // En Windows, soffice.exe puede retornar antes de que el proceso soffice.bin
        // termine de escribir el PDF; se espera un poco más antes de declarar fallo.
        $pdfReady = self::waitForPdfReady($pdfPath);
        self::removeDirectory($profileDir);

        if ($pdfReady) {
            return $pdfPath;
        }

        if (is_file($pdfPath)) {
            @unlink($pdfPath);
        }

        $detail = trim(implode("\n", array_slice($output, -5)));
        log_error(sprintf(
            'F023 PDF LibreOffice: exitCode=%d docx=%s detail=%s',
            $exitCode,
            $docxPath,
            $detail !== '' ? $detail : '(sin salida)'
        ));

        throw new \RuntimeException(
            'LibreOffice no pudo convertir el documento a PDF. Pruebe con Word (.docx) o contacte al administrador.'
        );
    }

    /** Espera brevemente a que el PDF de salida quede escrito por completo. */
    private static function waitForPdfReady(string $pdfPath, int $attempts = 6, int $delayMicroseconds = 350000): bool
    {
        for ($i = 0; $i < $attempts; $i++) {
            clearstatcache(true, $pdfPath);
            if (is_file($pdfPath) && filesize($pdfPath) >= self::MIN_PDF_BYTES) {
                return true;
            }
            usleep($delayMicroseconds);
        }
        clearstatcache(true, $pdfPath);

        return is_file($pdfPath) && filesize($pdfPath) >= self::MIN_PDF_BYTES;
    }

    /**
     * @throws \RuntimeException
     */
    private static function convertWithPhpWordDompdf(string $docxPath): string
    {
        $pdfPath = self::pdfPathForDocx($docxPath);
        $dompdfRoot = base_path('vendor/dompdf/dompdf');
        if (!is_dir($dompdfRoot) || !is_readable($dompdfRoot)) {
            throw new \RuntimeException('No se encontró la biblioteca Dompdf en vendor.');
        }

        Settings::setPdfRendererOptions(['font' => 'DejaVu Sans']);
        if (!Settings::setPdfRenderer(Settings::PDF_RENDERER_DOMPDF, $dompdfRoot)) {
            throw new \RuntimeException('No se pudo configurar el motor de PDF.');
        }

        try {
            $phpWord = IOFactory::load($docxPath, 'Word2007');
        } catch (\Throwable $e) {
            throw new \RuntimeException('No se pudo leer el Word generado para exportar a PDF.', 0, $e);
        }

        $writer = IOFactory::createWriter($phpWord, 'PDF');
        $writer->setEditCallback(static fn (string $html): string => self::injectDompdfCss($html));

        if (is_file($pdfPath)) {
            @unlink($pdfPath);
        }

        $writer->save($pdfPath);

        if (!is_file($pdfPath) || filesize($pdfPath) < self::MIN_PDF_BYTES) {
            throw new \RuntimeException('La exportación PDF no produjo un archivo válido.');
        }

        return $pdfPath;
    }

    private static function pdfPathForDocx(string $docxPath): string
    {
        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);

        return ($pdfPath !== null && $pdfPath !== '') ? $pdfPath : $docxPath . '.pdf';
    }

    private static function resolveLibreOfficeBinary(): ?string
    {
        $configured = trim((string) env('F023_LIBREOFFICE_PATH', ''));
        if ($configured !== '' && self::isUsableBinary($configured)) {
            return $configured;
        }

        $bundled = [
            base_path('tools/libreoffice/program/soffice'),
            base_path('tools/libreoffice/program/soffice.exe'),
        ];
        foreach ($bundled as $path) {
            if (self::isUsableBinary($path)) {
                return $path;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            foreach ([
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            ] as $path) {
                if (self::isUsableBinary($path)) {
                    return $path;
                }
            }
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            foreach ([
                '/Applications/LibreOffice.app/Contents/MacOS/soffice',
                '/Applications/LibreOffice.app/Contents/MacOS/libreoffice',
            ] as $path) {
                if (self::isUsableBinary($path)) {
                    return $path;
                }
            }
        }

        if (PHP_OS_FAMILY === 'Linux') {
            foreach ([
                '/usr/bin/libreoffice',
                '/usr/bin/soffice',
                '/usr/lib/libreoffice/program/soffice',
                '/usr/lib64/libreoffice/program/soffice',
            ] as $path) {
                if (self::isUsableBinary($path)) {
                    return $path;
                }
            }
        }

        foreach (['libreoffice', 'soffice'] as $name) {
            $which = @shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null');
            $path = $which !== null ? trim($which) : '';
            if ($path !== '' && self::isUsableBinary($path)) {
                return $path;
            }
        }

        return null;
    }

    private static function isUsableBinary(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            return true;
        }

        return is_executable($path);
    }

    private static function allowDompdfFallback(): bool
    {
        return filter_var(env('F023_PDF_ALLOW_DOMPDF_FALLBACK', 'false'), FILTER_VALIDATE_BOOL);
    }

    private static function isolatedUserProfileDir(): string
    {
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'sgep-lo-profile-'
            . getmypid()
            . '-'
            . bin2hex(random_bytes(4));
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return sys_get_temp_dir();
        }

        return $dir;
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Refuerza tablas, bordes y tipografía para Dompdf (solo si F023_PDF_ALLOW_DOMPDF_FALLBACK=true).
     */
    public static function injectDompdfCss(string $html): string
    {
        $css = <<<'CSS'
<style type="text/css">
@page { margin: 11mm 9mm; }
html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
body {
  font-family: 'DejaVu Sans', DejaVu Sans, Arial, Helvetica, sans-serif !important;
  font-size: 10pt !important;
  line-height: 1.22 !important;
  color: #000 !important;
  margin: 0 !important;
}
table {
  width: 100% !important;
  max-width: 100% !important;
  border-collapse: collapse !important;
  border-spacing: 0 !important;
  table-layout: auto !important;
}
td, th {
  border: 1px solid #000 !important;
  padding: 2px 5px !important;
  vertical-align: top !important;
  word-wrap: break-word;
  overflow-wrap: break-word;
}
p { margin: 0.15em 0; }
img { max-width: 100% !important; height: auto !important; }
</style>
CSS;

        if (preg_match('/<\/head\s*>/i', $html) === 1) {
            return preg_replace('/<\/head\s*>/i', $css . '</head>', $html, 1) ?? ($css . $html);
        }

        return $css . $html;
    }
}
