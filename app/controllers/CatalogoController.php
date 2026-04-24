<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ProgramaPdfParser;
use App\Helpers\ProgramaPdfTextExtractor;
use App\Models\ProgramaContenido;
use App\Models\ProgramaEnlacePendiente;
use App\Models\ProgramaImportacionPdf;
use App\Models\Programa;

class CatalogoController
{
    public function programas(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'nivel' => trim((string) ($_GET['nivel'] ?? '')),
            'modalidad' => trim((string) ($_GET['modalidad'] ?? '')),
        ];

        view('catalogo/programas/index', [
            'programas' => Programa::catalogo($filters),
            'filters' => $filters,
            'pendientesCount' => Programa::countPendientesEnlace(),
        ]);
    }

    public function grupos(): void
    {
        view('catalogo/grupos/index');
    }

    public function empresas(): void
    {
        view('catalogo/empresas/index');
    }

    public function pendientesPrograma(): void
    {
        $filters = ['q' => trim((string) ($_GET['q'] ?? ''))];
        view('catalogo/programas/pendientes', [
            'pendientes' => ProgramaEnlacePendiente::pendingList($filters),
            'filters' => $filters,
            'programas' => Programa::all(),
        ]);
    }

    public function resolverPendientePrograma(): void
    {
        $pendingId = (int) ($_POST['pending_id'] ?? 0);
        $programaId = (int) ($_POST['programa_id'] ?? 0);
        if ($pendingId > 0 && $programaId > 0) {
            ProgramaEnlacePendiente::resolve($pendingId, $programaId);
        }
        redirect(APP_BASE_PATH . '/catalogo/programas/pendientes');
    }

    public function importarProgramaForm(): void
    {
        view('catalogo/programas/importar');
    }

    public function importarProgramaAnalizar(): void
    {
        if (empty($_FILES['archivo_pdf']['tmp_name'])) {
            view('catalogo/programas/importar', ['error' => 'Seleccione un PDF o TXT para analizar.']);
            return;
        }

        $tmpPath = (string) $_FILES['archivo_pdf']['tmp_name'];
        $name = (string) ($_FILES['archivo_pdf']['name'] ?? 'programa.pdf');
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $extracted = ProgramaPdfTextExtractor::extract($tmpPath, $extension);
        $parsed = ProgramaPdfParser::parsePages((array) ($extracted['pages'] ?? []));
        $parsedMeta = (array) ($parsed['meta'] ?? []);
        $nameFallbackApplied = false;
        if (trim((string) ($parsedMeta['nombre'] ?? '')) === '') {
            $fallbackName = $this->guessProgramNameFromFileName($name);
            $parsed['meta']['nombre'] = $fallbackName;
            $nameFallbackApplied = $fallbackName !== '';
        }
        if ($nameFallbackApplied) {
            $parsed['warnings'] = array_values(array_filter(
                (array) ($parsed['warnings'] ?? []),
                static function ($warning): bool {
                    $message = is_array($warning) ? (string) ($warning['message'] ?? '') : (string) $warning;
                    return stripos($message, 'No se detecto nombre de programa automaticamente.') === false;
                }
            ));
        }
        $parsed['warnings'] = array_merge(
            (array) ($parsed['warnings'] ?? []),
            array_map(static fn (string $w): array => ['severity' => 'warning', 'message' => $w], (array) ($extracted['warnings'] ?? []))
        );

        view('catalogo/programas/importar_review', [
            'fileName' => $name,
            'parsed' => $parsed,
            'encoded' => base64_encode(json_encode($parsed, JSON_UNESCAPED_UNICODE) ?: '{}'),
        ]);
    }

    public function importarProgramaGuardar(): void
    {
        $encoded = (string) ($_POST['parsed_payload'] ?? '');
        $decoded = json_decode(base64_decode($encoded, true) ?: '{}', true);
        if (!is_array($decoded)) {
            redirect(APP_BASE_PATH . '/catalogo/programas/importar');
        }

        $meta = (array) ($decoded['meta'] ?? []);
        $competencias = (array) ($decoded['competencias'] ?? []);
        $warnings = (array) ($decoded['warnings'] ?? []);
        $hasWarnings = $warnings !== [];
        $programaId = Programa::findOrCreateFromImport($meta);
        ProgramaContenido::replaceProgramaContenido($programaId, $competencias);
        ProgramaImportacionPdf::create([
            'programa_id' => $programaId,
            'nombre_archivo' => (string) ($_POST['file_name'] ?? 'importacion.pdf'),
            'codigo_programa' => (string) ($meta['codigo'] ?? ''),
            'nombre_programa' => (string) ($meta['nombre'] ?? ''),
            'estado' => $hasWarnings ? 'confirmado_parcial' : 'confirmado',
            'advertencias_json' => json_encode($warnings, JSON_UNESCAPED_UNICODE),
            'resumen_json' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
        ]);

        redirect(APP_BASE_PATH . '/catalogo/programas');
    }

    private function guessProgramNameFromFileName(string $fileName): string
    {
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $base = preg_replace('/^[\d_\-\s]*programa\s+de\s+formaci[oó]n\s*-\s*/iu', '', $base) ?? $base;
        $base = preg_replace('/[_\-]+/', ' ', $base) ?? $base;
        $base = preg_replace('/\s+/', ' ', trim($base)) ?? trim($base);
        return $base;
    }

}
