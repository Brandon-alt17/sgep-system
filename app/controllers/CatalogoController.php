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
        ];
        $programas = Programa::catalogo($filters);
        $pendientesCount = Programa::countPendientesEnlace();

        if ($this->isAjaxFilterRequest()) {
            ob_start();
            partial('catalogo/programas/_rows', ['programas' => $programas]);
            $rowsHtml = (string) ob_get_clean();

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'rowsHtml' => $rowsHtml,
                'total' => count($programas),
                'pendientesCount' => $pendientesCount,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        view('catalogo/programas/index', [
            'programas' => $programas,
            'filters' => $filters,
            'pendientesCount' => $pendientesCount,
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

    public function nuevoProgramaForm(): void
    {
        $parsed = [
            'meta' => [
                'codigo' => '',
                'nombre' => '',
                'nivel' => '',
                'modalidad' => '',
                'horas_lectiva' => '',
                'horas_productiva' => '',
                'horas_total' => '',
            ],
            'competencias' => [],
            'warnings' => [],
        ];

        view('catalogo/programas/show', [
            'programa' => null,
            'fileName' => 'Registro manual',
            'parsed' => $parsed,
            'isNew' => true,
            'errors' => [],
        ]);
    }

    public function guardarProgramaFormacion(): void
    {
        $programaId = (int) ($_POST['programa_id'] ?? 0);
        $meta = [
            'codigo' => trim((string) ($_POST['codigo'] ?? '')),
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'nivel' => trim((string) ($_POST['nivel'] ?? '')),
            'modalidad' => trim((string) ($_POST['modalidad'] ?? '')),
            'horas_lectiva' => trim((string) ($_POST['horas_lectiva'] ?? '')),
            'horas_productiva' => trim((string) ($_POST['horas_productiva'] ?? '')),
        ];
        $meta['horas_total'] = $this->sumHoras($meta['horas_lectiva'], $meta['horas_productiva']);
        $competencias = $this->normalizePostedCompetencias((array) ($_POST['competencias'] ?? []));

        $errors = [];
        if ($meta['nombre'] === '') {
            $errors[] = 'El nombre del programa es obligatorio.';
        }

        if ($errors !== []) {
            view('catalogo/programas/show', [
                'programa' => $programaId > 0 ? Programa::findById($programaId) : null,
                'fileName' => 'Registro manual',
                'parsed' => [
                    'meta' => $meta,
                    'competencias' => $competencias,
                    'warnings' => [],
                ],
                'isNew' => $programaId <= 0,
                'errors' => $errors,
            ]);
            return;
        }

        if ($programaId > 0) {
            Programa::update($programaId, $meta);
        } else {
            $programaId = Programa::create($meta);
        }

        ProgramaContenido::replaceProgramaContenido($programaId, $competencias);
        $summary = [
            'meta' => $meta,
            'competencias' => $competencias,
            'warnings' => [],
        ];
        ProgramaImportacionPdf::create([
            'programa_id' => $programaId,
            'nombre_archivo' => 'edicion_manual',
            'codigo_programa' => $meta['codigo'],
            'nombre_programa' => $meta['nombre'],
            'estado' => 'manual',
            'advertencias_json' => '[]',
            'resumen_json' => json_encode($summary, JSON_UNESCAPED_UNICODE),
        ]);

        redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&saved=1');
    }

    public function verPrograma(): void
    {
        $programaId = (int) ($_GET['id'] ?? 0);
        if ($programaId <= 0) {
            http_response_code(404);
            view('errors/404', ['uri' => '/catalogo/programas/ver']);
            return;
        }

        $programa = Programa::findById($programaId);
        if ($programa === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/catalogo/programas/ver']);
            return;
        }

        $latestImport = ProgramaImportacionPdf::latestByProgramaId($programaId);
        $parsed = [
            'meta' => [
                'codigo' => (string) ($programa['codigo'] ?? ''),
                'nombre' => (string) ($programa['nombre'] ?? ''),
                'nivel' => (string) ($programa['nivel'] ?? ''),
                'horas_total' => '',
            ],
            'competencias' => ProgramaContenido::competenciasConResultados($programaId),
            'warnings' => [],
        ];

        if ($latestImport !== null) {
            $decoded = json_decode((string) ($latestImport['resumen_json'] ?? '{}'), true);
            if (is_array($decoded)) {
                $parsed['meta'] = array_merge($parsed['meta'], (array) ($decoded['meta'] ?? []));
                if ((array) ($decoded['competencias'] ?? []) !== [] && $parsed['competencias'] === []) {
                    $parsed['competencias'] = (array) $decoded['competencias'];
                }
                $parsed['warnings'] = (array) ($decoded['warnings'] ?? []);
            }
        }

        view('catalogo/programas/show', [
            'programa' => $programa,
            'fileName' => (string) ($latestImport['nombre_archivo'] ?? 'Registro guardado'),
            'parsed' => $parsed,
            'isNew' => false,
            'errors' => [],
        ]);
    }

    public function actualizarProgramaDatos(): void
    {
        $programaId = (int) ($_POST['programa_id'] ?? 0);
        if ($programaId <= 0) {
            redirect(APP_BASE_PATH . '/catalogo/programas');
        }

        $meta = [
            'codigo' => trim((string) ($_POST['codigo'] ?? '')),
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'nivel' => trim((string) ($_POST['nivel'] ?? '')),
            'modalidad' => trim((string) ($_POST['modalidad'] ?? '')),
            'horas_lectiva' => trim((string) ($_POST['horas_lectiva'] ?? '')),
            'horas_productiva' => trim((string) ($_POST['horas_productiva'] ?? '')),
        ];
        if ($meta['nombre'] === '') {
            redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId);
        }
        $meta['horas_total'] = $this->sumHoras($meta['horas_lectiva'], $meta['horas_productiva']);

        Programa::update($programaId, $meta);
        $currentCompetencias = ProgramaContenido::competenciasConResultados($programaId);
        ProgramaImportacionPdf::create([
            'programa_id' => $programaId,
            'nombre_archivo' => 'edicion_datos_programa',
            'codigo_programa' => $meta['codigo'],
            'nombre_programa' => $meta['nombre'],
            'estado' => 'manual',
            'advertencias_json' => '[]',
            'resumen_json' => json_encode([
                'meta' => $meta,
                'competencias' => $currentCompetencias,
                'warnings' => [],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&saved=1');
    }

    public function actualizarProgramaCompetencia(): void
    {
        $programaId = (int) ($_POST['programa_id'] ?? 0);
        $competenciaId = (int) ($_POST['competencia_id'] ?? 0);
        if ($programaId <= 0 || $competenciaId <= 0) {
            redirect(APP_BASE_PATH . '/catalogo/programas');
        }

        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $resultadosInput = (array) ($_POST['resultados'] ?? []);
        $resultados = [];
        foreach ($resultadosInput as $resultado) {
            if (!is_array($resultado)) {
                continue;
            }
            $raCodigo = trim((string) ($resultado['codigo'] ?? ''));
            $raDescripcion = trim((string) ($resultado['descripcion'] ?? ''));
            if ($raCodigo === '' && $raDescripcion === '') {
                continue;
            }
            $resultados[] = ['codigo' => $raCodigo, 'descripcion' => $raDescripcion];
        }

        ProgramaContenido::updateCompetenciaConResultados($programaId, $competenciaId, $codigo, $nombre, $resultados);

        $programa = Programa::findById($programaId) ?? [];
        $latestImport = ProgramaImportacionPdf::latestByProgramaId($programaId);
        $meta = [
            'codigo' => (string) ($programa['codigo'] ?? ''),
            'nombre' => (string) ($programa['nombre'] ?? ''),
            'nivel' => (string) ($programa['nivel'] ?? ''),
            'modalidad' => (string) ($programa['modalidad'] ?? ''),
        ];
        if ($latestImport !== null) {
            $decodedLatest = json_decode((string) ($latestImport['resumen_json'] ?? '{}'), true);
            if (is_array($decodedLatest)) {
                $meta = array_merge($meta, (array) ($decodedLatest['meta'] ?? []));
            }
        }
        $competencias = ProgramaContenido::competenciasConResultados($programaId);
        ProgramaImportacionPdf::create([
            'programa_id' => $programaId,
            'nombre_archivo' => 'edicion_competencia',
            'codigo_programa' => (string) ($programa['codigo'] ?? ''),
            'nombre_programa' => (string) ($programa['nombre'] ?? ''),
            'estado' => 'manual',
            'advertencias_json' => '[]',
            'resumen_json' => json_encode([
                'meta' => $meta,
                'competencias' => $competencias,
                'warnings' => [],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&saved=1');
    }

    private function guessProgramNameFromFileName(string $fileName): string
    {
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $base = preg_replace('/^[\d_\-\s]*programa\s+de\s+formaci[oó]n\s*-\s*/iu', '', $base) ?? $base;
        $base = preg_replace('/[_\-]+/', ' ', $base) ?? $base;
        $base = preg_replace('/\s+/', ' ', trim($base)) ?? trim($base);
        return $base;
    }

    private function isAjaxFilterRequest(): bool
    {
        $isXmlHttpRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $isAjaxQueryFlag = ((string) ($_GET['ajax'] ?? '')) === '1';
        return $isXmlHttpRequest && $isAjaxQueryFlag;
    }

    /** @param array<int,mixed> $competenciasInput
     *  @return array<int,array<string,mixed>>
     */
    private function normalizePostedCompetencias(array $competenciasInput): array
    {
        $normalized = [];
        foreach ($competenciasInput as $competenciaRaw) {
            if (!is_array($competenciaRaw)) {
                continue;
            }
            $codigo = trim((string) ($competenciaRaw['codigo'] ?? ''));
            $nombre = trim((string) ($competenciaRaw['nombre'] ?? ''));
            $resultadosRaw = (array) ($competenciaRaw['resultados'] ?? []);
            $resultados = [];
            foreach ($resultadosRaw as $resultadoRaw) {
                if (!is_array($resultadoRaw)) {
                    continue;
                }
                $raCodigo = trim((string) ($resultadoRaw['codigo'] ?? ''));
                $raDescripcion = trim((string) ($resultadoRaw['descripcion'] ?? ''));
                if ($raCodigo === '' && $raDescripcion === '') {
                    continue;
                }
                $resultados[] = [
                    'codigo' => $raCodigo,
                    'descripcion' => $raDescripcion,
                ];
            }
            if ($codigo === '' && $nombre === '' && $resultados === []) {
                continue;
            }
            $normalized[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'resultados' => $resultados,
            ];
        }

        return $normalized;
    }

    private function sumHoras(string $lectiva, string $productiva): string
    {
        $lectivaNum = ctype_digit($lectiva) ? (int) $lectiva : 0;
        $productivaNum = ctype_digit($productiva) ? (int) $productiva : 0;
        $total = $lectivaNum + $productivaNum;
        return $total > 0 ? (string) $total : '';
    }

}
