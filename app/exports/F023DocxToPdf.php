<?php

declare(strict_types=1);

namespace App\Exports;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

/**
 * Convierte un .docx ya generado a PDF.
 *
 * Orden de intentos:
 * 1. LibreOffice / soffice en modo headless (fidelidad muy cercana al Word).
 * 2. PhpWord + Dompdf (HTML intermedio), con CSS y fuente reforzados para reducir diferencias.
 */
final class F023DocxToPdf
{
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

        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        if ($pdfPath === null || $pdfPath === '') {
            $pdfPath = $docxPath . '.pdf';
        }

        $dir = dirname($docxPath);
        $lo = self::libreOfficeBinary();
        if ($lo !== null) {
            if (is_file($pdfPath)) {
                @unlink($pdfPath);
            }
            $cmd = sprintf(
                '%s --headless --invisible --nologo --nodefault --norestore --convert-to pdf --outdir %s %s',
                escapeshellarg($lo),
                escapeshellarg($dir),
                escapeshellarg($docxPath)
            );
            $code = 1;
            @exec($cmd . ' 2>&1', $_, $code);
            if ($code === 0 && is_file($pdfPath) && filesize($pdfPath) > 0) {
                return $pdfPath;
            }
            if (is_file($pdfPath)) {
                @unlink($pdfPath);
            }
        }

        self::convertWithPhpWordDompdf($docxPath, $pdfPath);

        return $pdfPath;
    }

    private static function libreOfficeBinary(): ?string
    {
        foreach (['libreoffice', 'soffice'] as $name) {
            $which = @shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null');
            $path = $which !== null ? trim($which) : '';
            if ($path !== '' && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @throws \RuntimeException
     */
    private static function convertWithPhpWordDompdf(string $docxPath, string $pdfPath): void
    {
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

        if (!is_file($pdfPath) || filesize($pdfPath) === 0) {
            throw new \RuntimeException('La exportación PDF no produjo un archivo válido.');
        }
    }

    /**
     * Refuerza tablas, bordes y tipografía para Dompdf (PhpWord emite HTML con estilos incompletos).
     */
    public static function injectDompdfCss(string $html): string
    {
        $css = <<<'CSS'
<style type="text/css">
/* F023 — reducir diferencias frente al .docx en Dompdf */
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
