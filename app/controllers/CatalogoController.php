<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ProgramaPdfParser;
use App\Helpers\ProgramaPdfTextExtractor;
use App\Helpers\Validator;
use App\Models\ProgramaContenido;
use App\Models\ProgramaEnlacePendiente;
use App\Models\ProgramaImportacionPdf;
use App\Models\Empresa;
use App\Models\EmpresaJefe;
use App\Models\Grupo;
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
            partial('components/ui');
            $tdClasses = str_replace('h-[50px] ', '', ui_td_classes());
            ob_start();
            partial('catalogo/programas/_rows', [
                'programas' => $programas,
                'tdClasses' => $tdClasses,
            ]);
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
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'programa_id' => (int) ($_GET['programa_id'] ?? 0),
        ];
        $grupos = Grupo::catalogo($filters);
        $programas = Programa::all();

        if ($this->isAjaxFilterRequest()) {
            partial('components/ui');
            $tdClasses = str_replace('h-[50px] ', '', ui_td_classes());
            ob_start();
            partial('catalogo/grupos/_rows', ['grupos' => $grupos, 'tdClasses' => $tdClasses]);
            $rowsHtml = (string) ob_get_clean();

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'rowsHtml' => $rowsHtml,
                'total' => count($grupos),
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        view('catalogo/grupos/index', [
            'grupos' => $grupos,
            'filters' => $filters,
            'programas' => $programas,
        ]);
    }

    public function empresas(): void
    {
        $filters = ['q' => trim((string) ($_GET['q'] ?? ''))];
        $empresas = Empresa::catalogo($filters);

        if ($this->isAjaxFilterRequest()) {
            partial('components/ui');
            $tdClasses = str_replace('h-[50px] ', '', ui_td_classes());
            ob_start();
            partial('catalogo/empresas/_rows', ['empresas' => $empresas, 'tdClasses' => $tdClasses]);
            $rowsHtml = (string) ob_get_clean();

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'rowsHtml' => $rowsHtml,
                'total' => count($empresas),
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        view('catalogo/empresas/index', [
            'empresas' => $empresas,
            'filters' => $filters,
        ]);
    }

    public function empresasNuevo(): void
    {
        view('catalogo/empresas/form', [
            'empresa' => null,
            'errors' => [],
        ]);
    }

    public function empresasCrear(): void
    {
        $data = $this->postedEmpresaPayload();
        $errors = Validator::required($_POST, ['nombre']);
        if ($errors !== []) {
            view('catalogo/empresas/form', ['empresa' => $data, 'errors' => array_values($errors)]);
            return;
        }
        Empresa::create($data);
        redirect(APP_BASE_PATH . '/catalogo/empresas?toast=empresa_creada');
    }

    public function empresasVer(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $empresa = Empresa::findById($id);
        if ($empresa === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/catalogo/empresas/ver?id=' . $id]);
            return;
        }
        $editing = ((string) ($_GET['edit'] ?? '')) === '1';
        $jefes = EmpresaJefe::listByEmpresa($id);
        // Compatibilidad: si aún no se han migrado/seeded los jefes,
        // mostramos el contacto legado de empresa como "Jefe 1".
        if ($jefes === [] && trim((string) ($empresa['nombre_jefe'] ?? '')) !== '') {
            $jefes[] = [
                'nombre' => (string) ($empresa['nombre_jefe'] ?? ''),
                'cargo' => (string) ($empresa['cargo_jefe'] ?? ''),
                'correo' => (string) ($empresa['correo_jefe'] ?? ''),
                'telefono' => (string) ($empresa['telefono_jefe'] ?? ''),
                'nombre_contacto2' => (string) ($empresa['nombre_contacto2'] ?? ''),
                'correo_contacto2' => (string) ($empresa['correo_contacto2'] ?? ''),
            ];
        }
        view('catalogo/empresas/form', [
            'empresa' => $empresa,
            'errors' => [],
            'aprendicesCount' => Empresa::countAprendices($id),
            'editing' => $editing,
            'jefes' => $jefes,
        ]);
    }

    /** @deprecated Prefer /catalogo/empresas/ver */
    public function empresasEditar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        redirect(APP_BASE_PATH . '/catalogo/empresas/ver?id=' . $id);
    }

    public function empresasActualizar(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $empresa = Empresa::findById($id);
        if ($empresa === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/catalogo/empresas/ver']);
            return;
        }
        $data = $this->postedEmpresaPayload();
        $jefesPosted = $this->postedJefesForView($id);
        $errors = Validator::required($_POST, ['nombre']);
        if ($errors === []
            && !$this->hasEmpresaMainDataChanges($empresa, $data)
            && !$this->hasJefesChanges($id, $jefesPosted)
        ) {
            redirect(APP_BASE_PATH . '/catalogo/empresas/ver?id=' . $id . '&edit=1&toast=empresa_sin_cambios');
        }
        if ($errors !== []) {
            $data['id'] = $id;
            view('catalogo/empresas/form', [
                'empresa' => array_merge($empresa, $data),
                'errors' => array_values($errors),
                'aprendicesCount' => Empresa::countAprendices($id),
                'editing' => true,
                'jefes' => $jefesPosted,
            ]);
            return;
        }
        Empresa::update($id, $data);
        $this->savePostedJefes($id);
        redirect(APP_BASE_PATH . '/catalogo/empresas/ver?id=' . $id . '&toast=empresa_actualizada');
    }

    public function empresasEliminar(): void
    {
        $id = (int) ($_POST['empresa_id'] ?? 0);
        if ($id <= 0) {
            redirect(APP_BASE_PATH . '/catalogo/empresas');
            return;
        }
        if (Empresa::countAprendices($id) > 0) {
            redirect(APP_BASE_PATH . '/catalogo/empresas?toast=empresa_no_eliminada_aprendices');
            return;
        }
        if (Empresa::deleteById($id)) {
            redirect(APP_BASE_PATH . '/catalogo/empresas?toast=empresa_eliminada');
            return;
        }
        redirect(APP_BASE_PATH . '/catalogo/empresas?toast=empresa_no_encontrada');
    }

    /** @return array<string, string> */
    private function postedEmpresaPayload(): array
    {
        return [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'nit' => trim((string) ($_POST['nit'] ?? '')),
            'direccion' => trim((string) ($_POST['direccion'] ?? '')),
            'ciudad' => trim((string) ($_POST['ciudad'] ?? '')),
            'correo_org' => trim((string) ($_POST['correo_org'] ?? '')),
            'nombre_contacto2' => trim((string) ($_POST['nombre_contacto2'] ?? '')),
            'correo_contacto2' => trim((string) ($_POST['correo_contacto2'] ?? '')),
            'direccion_practica' => trim((string) ($_POST['direccion_practica'] ?? '')),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function postedJefesForView(int $empresaId): array
    {
        $rows = (array) ($_POST['jefes'] ?? []);
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'empresa_id' => $empresaId,
                'nombre' => trim((string) ($row['nombre'] ?? '')),
                'cargo' => trim((string) ($row['cargo'] ?? '')),
                'correo' => trim((string) ($row['correo'] ?? '')),
                'telefono' => trim((string) ($row['telefono'] ?? '')),
                'nombre_contacto2' => trim((string) ($row['nombre_contacto2'] ?? '')),
                'correo_contacto2' => trim((string) ($row['correo_contacto2'] ?? '')),
            ];
        }

        if ($out === []) {
            return EmpresaJefe::listByEmpresa($empresaId);
        }

        return $out;
    }

    private function savePostedJefes(int $empresaId): void
    {
        $rows = (array) ($_POST['jefes'] ?? []);
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            EmpresaJefe::updateByIdForEmpresa($empresaId, (int) ($row['id'] ?? 0), [
                'nombre' => trim((string) ($row['nombre'] ?? '')),
                'cargo' => trim((string) ($row['cargo'] ?? '')),
                'correo' => trim((string) ($row['correo'] ?? '')),
                'telefono' => trim((string) ($row['telefono'] ?? '')),
                'nombre_contacto2' => trim((string) ($row['nombre_contacto2'] ?? '')),
                'correo_contacto2' => trim((string) ($row['correo_contacto2'] ?? '')),
            ]);
        }
    }

    /** @param array<string, mixed> $current @param array<string, string> $incoming */
    private function hasEmpresaMainDataChanges(array $current, array $incoming): bool
    {
        $fields = [
            'nombre',
            'nit',
            'direccion',
            'ciudad',
            'correo_org',
            'nombre_contacto2',
            'correo_contacto2',
            'direccion_practica',
        ];
        foreach ($fields as $field) {
            $currentValue = trim((string) ($current[$field] ?? ''));
            $incomingValue = trim((string) ($incoming[$field] ?? ''));
            if ($currentValue !== $incomingValue) {
                return true;
            }
        }

        return false;
    }

    /** @param list<array<string, mixed>> $postedRows */
    private function hasJefesChanges(int $empresaId, array $postedRows): bool
    {
        $currentRows = EmpresaJefe::listByEmpresa($empresaId);
        $currentById = [];
        foreach ($currentRows as $row) {
            $rid = (int) ($row['id'] ?? 0);
            if ($rid > 0) {
                $currentById[$rid] = $row;
            }
        }

        $postedById = [];
        foreach ($postedRows as $row) {
            $rid = (int) ($row['id'] ?? 0);
            if ($rid > 0) {
                $postedById[$rid] = $row;
            }
        }

        if (count($currentById) !== count($postedById)) {
            return true;
        }

        $fields = ['nombre', 'cargo', 'correo', 'telefono', 'nombre_contacto2', 'correo_contacto2'];
        foreach ($currentById as $rid => $current) {
            if (!isset($postedById[$rid])) {
                return true;
            }
            $incoming = $postedById[$rid];
            foreach ($fields as $field) {
                $currentValue = trim((string) ($current[$field] ?? ''));
                $incomingValue = trim((string) ($incoming[$field] ?? ''));
                if ($currentValue !== $incomingValue) {
                    return true;
                }
            }
        }

        return false;
    }

    public function pendientesPrograma(): void
    {
        ProgramaEnlacePendiente::syncAprendicesSinVinculoValido();
        $filters = ['q' => trim((string) ($_GET['q'] ?? ''))];
        $pendientes = ProgramaEnlacePendiente::pendingList($filters);
        $programas = Programa::all();
        $pendientesCount = Programa::countPendientesEnlace();

        if ($this->isAjaxFilterRequest()) {
            partial('components/ui');
            $tdClasses = str_replace('h-[50px] ', '', ui_td_classes());
            ob_start();
            partial('catalogo/programas/_pendientes_rows', [
                'pendientes' => $pendientes,
                'programas' => $programas,
                'tdClasses' => $tdClasses,
            ]);
            $rowsHtml = (string) ob_get_clean();

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'rowsHtml' => $rowsHtml,
                'total' => count($pendientes),
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        view('catalogo/programas/pendientes', [
            'pendientes' => $pendientes,
            'filters' => $filters,
            'programas' => $programas,
            'pendientesCount' => $pendientesCount,
        ]);
    }

    public function resolverPendientePrograma(): void
    {
        $pendingId = (int) ($_POST['pending_id'] ?? 0);
        $programaId = (int) ($_POST['programa_id'] ?? 0);
        $resolved = false;
        if ($pendingId > 0 && $programaId > 0) {
            ProgramaEnlacePendiente::resolve($pendingId, $programaId);
            $resolved = true;
        }
        $suffix = $resolved ? '?toast=pendiente_resuelto' : '';
        redirect(APP_BASE_PATH . '/catalogo/programas/pendientes' . $suffix);
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

        redirect(APP_BASE_PATH . '/catalogo/programas?toast=programa_importado');
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

        view('catalogo/programas/nuevo_manual', [
            'parsed' => $parsed,
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
            'horas_lectiva' => '',
            'horas_productiva' => '',
        ];
        $competencias = $this->normalizePostedCompetencias((array) ($_POST['competencias'] ?? []));
        $meta['horas_total'] = $this->sumHorasFromCompetencias($competencias);

        $errors = $this->validateProgramaFormacionPayload($meta, $competencias, $programaId);

        if ($errors !== []) {
            if ($programaId > 0) {
                view('catalogo/programas/show', [
                    'programa' => Programa::findById($programaId),
                    'fileName' => 'Registro manual',
                    'parsed' => [
                        'meta' => $meta,
                        'competencias' => $competencias,
                        'warnings' => [],
                    ],
                    'errors' => $errors,
                ]);
            } else {
                view('catalogo/programas/nuevo_manual', [
                    'parsed' => [
                        'meta' => $meta,
                        'competencias' => $competencias,
                        'warnings' => [],
                    ],
                    'errors' => $errors,
                ]);
            }
            return;
        }

        $isUpdate = $programaId > 0;
        try {
            if ($isUpdate) {
                Programa::update($programaId, $meta);
            } else {
                $programaId = Programa::create($meta);
            }
        } catch (\PDOException $e) {
            $msgLower = strtolower($e->getMessage());
            $isLikelyUniqueViolation = str_contains($msgLower, 'duplicate')
                || str_contains($msgLower, 'unique')
                || str_contains($msgLower, 'uq_programas');
            if (!$isLikelyUniqueViolation) {
                throw $e;
            }
            if ($programaId <= 0) {
                view('catalogo/programas/nuevo_manual', [
                    'parsed' => [
                        'meta' => $meta,
                        'competencias' => $competencias,
                        'warnings' => [],
                    ],
                    'errors' => ['No se pudo guardar: ya existe un programa con el mismo nombre y nivel (restricción en base de datos).'],
                ]);
            } else {
                view('catalogo/programas/show', [
                    'programa' => Programa::findById($programaId),
                    'fileName' => 'Registro manual',
                    'parsed' => [
                        'meta' => $meta,
                        'competencias' => $competencias,
                        'warnings' => [],
                    ],
                    'errors' => ['No se pudo guardar: conflicto con otro programa (nombre y nivel únicos).'],
                ]);
            }
            return;
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

        $toast = $isUpdate ? 'programa_actualizado' : 'programa_creado';
        redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&saved=1&toast=' . $toast);
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
                $decodedCompetencias = (array) ($decoded['competencias'] ?? []);
                if ($decodedCompetencias !== [] && $parsed['competencias'] === []) {
                    $parsed['competencias'] = $decodedCompetencias;
                } elseif ($decodedCompetencias !== [] && $parsed['competencias'] !== []) {
                    $hoursByKey = [];
                    foreach ($decodedCompetencias as $decodedComp) {
                        if (!is_array($decodedComp)) {
                            continue;
                        }
                        $decodedId = (int) ($decodedComp['id'] ?? 0);
                        if ($decodedId > 0) {
                            $hoursByKey['id:' . $decodedId] = trim((string) ($decodedComp['horas'] ?? ''));
                        }
                        $keyByCode = trim((string) ($decodedComp['codigo'] ?? ''));
                        if ($keyByCode !== '') {
                            $hoursByKey['code:' . $keyByCode] = trim((string) ($decodedComp['horas'] ?? ''));
                        }
                        $keyByName = trim((string) ($decodedComp['nombre'] ?? ''));
                        if ($keyByName !== '') {
                            $hoursByKey['name:' . mb_strtolower($keyByName)] = trim((string) ($decodedComp['horas'] ?? ''));
                        }
                    }

                    foreach ($parsed['competencias'] as $idx => $comp) {
                        if (!is_array($comp)) {
                            continue;
                        }
                        $code = trim((string) ($comp['codigo'] ?? ''));
                        $name = trim((string) ($comp['nombre'] ?? ''));
                        $compId = (int) ($comp['id'] ?? 0);
                        $hours = '';
                        if ($compId > 0 && isset($hoursByKey['id:' . $compId])) {
                            $hours = (string) $hoursByKey['id:' . $compId];
                        } elseif ($code !== '' && isset($hoursByKey['code:' . $code])) {
                            $hours = (string) $hoursByKey['code:' . $code];
                        } elseif ($name !== '' && isset($hoursByKey['name:' . mb_strtolower($name)])) {
                            $hours = (string) $hoursByKey['name:' . mb_strtolower($name)];
                        }
                        if ($hours !== '') {
                            $parsed['competencias'][$idx]['horas'] = $hours;
                        }
                    }
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
        $latestSummary = $this->latestSummaryDecoded($programaId);
        $currentCompetencias = $this->mergeHorasIntoCompetencias($currentCompetencias, (array) ($latestSummary['competencias'] ?? []));
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

        redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&saved=1&toast=programa_actualizado');
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
        $horas = trim((string) ($_POST['horas'] ?? ''));
        $resultadosInput = (array) ($_POST['resultados'] ?? []);
        $resultados = [];
        $hasPartialResultado = false;
        $hasDuplicateCodigoRae = false;
        $seenRaeCodes = [];
        foreach ($resultadosInput as $resultado) {
            if (!is_array($resultado)) {
                continue;
            }
            $raCodigo = trim((string) ($resultado['codigo'] ?? ''));
            $raDescripcion = trim((string) ($resultado['descripcion'] ?? ''));
            if ($raCodigo === '' && $raDescripcion === '') {
                continue;
            }
            if ($raCodigo === '' || $raDescripcion === '') {
                $hasPartialResultado = true;
            }
            $raCodigoKey = mb_strtolower($raCodigo);
            if ($raCodigoKey !== '' && isset($seenRaeCodes[$raCodigoKey])) {
                $hasDuplicateCodigoRae = true;
            }
            if ($raCodigoKey !== '') {
                $seenRaeCodes[$raCodigoKey] = true;
            }
            $resultados[] = ['codigo' => $raCodigo, 'descripcion' => $raDescripcion];
        }
        if ($hasDuplicateCodigoRae) {
            redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&edit_competencia=' . $competenciaId . '&toast=competencia_codigo_duplicado');
        }
        if (ProgramaContenido::existeCodigoCompetenciaEnPrograma($programaId, $codigo, $competenciaId)) {
            redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&edit_competencia=' . $competenciaId . '&toast=competencia_codigo_competencia_duplicado');
        }
        if ($codigo === '' || $nombre === '' || $horas === '' || $resultados === [] || $hasPartialResultado) {
            redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&edit_competencia=' . $competenciaId . '&toast=competencia_invalidada');
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
        $latestSummary = $this->latestSummaryDecoded($programaId);
        $competencias = ProgramaContenido::competenciasConResultados($programaId);
        $competencias = $this->mergeHorasIntoCompetencias($competencias, (array) ($latestSummary['competencias'] ?? []));
        foreach ($competencias as $idx => $competencia) {
            if ((int) ($competencia['id'] ?? 0) === $competenciaId) {
                $competencias[$idx]['horas'] = $horas;
                break;
            }
        }
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

        redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&saved=1&toast=competencia_actualizada');
    }

    public function agregarProgramaCompetencia(): void
    {
        $programaId = (int) ($_POST['programa_id'] ?? 0);
        if ($programaId <= 0) {
            redirect(APP_BASE_PATH . '/catalogo/programas');
        }
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $horas = trim((string) ($_POST['horas'] ?? ''));
        $resultadosInput = (array) ($_POST['resultados'] ?? []);
        $resultados = [];
        $hasPartialResultado = false;
        $hasDuplicateCodigoRae = false;
        $seenRaeCodes = [];
        foreach ($resultadosInput as $resultado) {
            if (!is_array($resultado)) {
                continue;
            }
            $raCodigo = trim((string) ($resultado['codigo'] ?? ''));
            $raDescripcion = trim((string) ($resultado['descripcion'] ?? ''));
            if ($raCodigo === '' && $raDescripcion === '') {
                continue;
            }
            if ($raCodigo === '' || $raDescripcion === '') {
                $hasPartialResultado = true;
            }
            $raCodigoKey = mb_strtolower($raCodigo);
            if ($raCodigoKey !== '' && isset($seenRaeCodes[$raCodigoKey])) {
                $hasDuplicateCodigoRae = true;
            }
            if ($raCodigoKey !== '') {
                $seenRaeCodes[$raCodigoKey] = true;
            }
            $resultados[] = ['codigo' => $raCodigo, 'descripcion' => $raDescripcion];
        }
        if ($codigo === '' || $nombre === '' || $horas === '' || $resultados === [] || $hasPartialResultado || $hasDuplicateCodigoRae) {
            $toast = $hasDuplicateCodigoRae ? 'competencia_codigo_duplicado' : 'competencia_invalidada';
            redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&new_competencia=1&toast=' . $toast);
        }
        if (ProgramaContenido::existeCodigoCompetenciaEnPrograma($programaId, $codigo)) {
            redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&new_competencia=1&toast=competencia_codigo_competencia_duplicado');
        }

        $competenciaId = ProgramaContenido::createCompetenciaConResultados($programaId, $codigo, $nombre, $resultados);
        $programa = Programa::findById($programaId) ?? [];
        $latestSummary = $this->latestSummaryDecoded($programaId);
        $meta = array_merge([
            'codigo' => (string) ($programa['codigo'] ?? ''),
            'nombre' => (string) ($programa['nombre'] ?? ''),
            'nivel' => (string) ($programa['nivel'] ?? ''),
            'modalidad' => (string) ($programa['modalidad'] ?? ''),
        ], (array) ($latestSummary['meta'] ?? []));

        $competencias = ProgramaContenido::competenciasConResultados($programaId);
        $competencias = $this->mergeHorasIntoCompetencias($competencias, (array) ($latestSummary['competencias'] ?? []));
        usort($competencias, static function (array $a, array $b) use ($competenciaId): int {
            if ((int) ($a['id'] ?? 0) === $competenciaId) {
                return -1;
            }
            if ((int) ($b['id'] ?? 0) === $competenciaId) {
                return 1;
            }
            return 0;
        });
        foreach ($competencias as $idx => $competencia) {
            if ((int) ($competencia['id'] ?? 0) === $competenciaId) {
                $competencias[$idx]['horas'] = $horas;
                break;
            }
        }

        ProgramaImportacionPdf::create([
            'programa_id' => $programaId,
            'nombre_archivo' => 'agregar_competencia',
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

        redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&saved=1&toast=competencia_creada');
    }

    public function eliminarProgramaCompetencia(): void
    {
        $programaId = (int) ($_POST['programa_id'] ?? 0);
        $competenciaId = (int) ($_POST['competencia_id'] ?? 0);
        if ($programaId <= 0 || $competenciaId <= 0) {
            redirect(APP_BASE_PATH . '/catalogo/programas');
        }

        ProgramaContenido::deleteCompetencia($programaId, $competenciaId);
        $programa = Programa::findById($programaId) ?? [];
        $latestSummary = $this->latestSummaryDecoded($programaId);
        $meta = array_merge([
            'codigo' => (string) ($programa['codigo'] ?? ''),
            'nombre' => (string) ($programa['nombre'] ?? ''),
            'nivel' => (string) ($programa['nivel'] ?? ''),
            'modalidad' => (string) ($programa['modalidad'] ?? ''),
        ], (array) ($latestSummary['meta'] ?? []));
        $competencias = ProgramaContenido::competenciasConResultados($programaId);
        $competencias = $this->mergeHorasIntoCompetencias($competencias, (array) ($latestSummary['competencias'] ?? []));

        ProgramaImportacionPdf::create([
            'programa_id' => $programaId,
            'nombre_archivo' => 'eliminar_competencia',
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

        redirect(APP_BASE_PATH . '/catalogo/programas/ver?id=' . $programaId . '&saved=1&toast=competencia_eliminada');
    }

    public function eliminarPrograma(): void
    {
        $programaId = (int) ($_POST['programa_id'] ?? 0);
        if ($programaId > 0) {
            Programa::deleteById($programaId);
        }
        redirect(APP_BASE_PATH . '/catalogo/programas?toast=programa_eliminado');
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

        // Algunos entornos no propagan HTTP_X_REQUESTED_WITH de forma confiable.
        // Con ajax=1 permitimos respuesta parcial sin depender del header.
        return $isAjaxQueryFlag || $isXmlHttpRequest;
    }

    /**
     * @param array<string,mixed> $meta
     * @param array<int,array<string,mixed>> $competencias
     * @return list<string>
     */
    private function validateProgramaFormacionPayload(array $meta, array $competencias, int $programaId): array
    {
        $errors = [];

        if (trim((string) ($meta['nombre'] ?? '')) === '') {
            $errors[] = 'El nombre del programa es obligatorio.';
        }

        if (trim((string) ($meta['codigo'] ?? '')) === '') {
            $errors[] = 'El código del programa es obligatorio.';
        }

        $modalidad = trim((string) ($meta['modalidad'] ?? ''));
        if ($modalidad !== '' && !in_array($modalidad, ['Presencial', 'Virtual', 'Mixta'], true)) {
            $errors[] = 'La modalidad no es válida.';
        }

        $nivel = trim((string) ($meta['nivel'] ?? ''));
        if ($nivel === '') {
            $errors[] = 'El nivel del programa es obligatorio.';
        } elseif (!in_array($nivel, ['Técnico', 'Tecnólogo', 'Auxiliar'], true)) {
            $errors[] = 'El nivel debe ser Técnico, Tecnólogo o Auxiliar.';
        }

        if ($competencias === []) {
            $errors[] = 'Debes agregar al menos una competencia con sus resultados de aprendizaje.';
        }

        $seenCompCodes = [];
        $duplicateCompCode = false;
        foreach ($competencias as $idx => $comp) {
            $n = $idx + 1;
            $codigo = trim((string) ($comp['codigo'] ?? ''));
            $nombre = trim((string) ($comp['nombre'] ?? ''));
            $horasComp = trim((string) ($comp['horas'] ?? ''));
            if ($codigo === '' || $nombre === '') {
                $errors[] = 'En la competencia ' . $n . ' el código y el nombre son obligatorios.';
            }
            if ($horasComp === '' || !ctype_digit($horasComp)) {
                $errors[] = 'En la competencia ' . $n . ' las horas son obligatorias y deben ser un número entero mayor o igual que 0.';
            }
            if ($codigo !== '') {
                $key = mb_strtolower($codigo);
                if (isset($seenCompCodes[$key])) {
                    $duplicateCompCode = true;
                }
                $seenCompCodes[$key] = true;
            }

            $resultados = (array) ($comp['resultados'] ?? []);
            if ($resultados === []) {
                $errors[] = 'La competencia ' . $n . ' debe tener al menos un resultado de aprendizaje con código y descripción.';
                continue;
            }

            $seenRae = [];
            $duplicateRae = false;
            $incompleteRae = false;
            foreach ($resultados as $res) {
                $raC = trim((string) ($res['codigo'] ?? ''));
                $raD = trim((string) ($res['descripcion'] ?? ''));
                if ($raC === '' || $raD === '') {
                    $incompleteRae = true;
                    continue;
                }
                $rk = mb_strtolower($raC);
                if (isset($seenRae[$rk])) {
                    $duplicateRae = true;
                }
                $seenRae[$rk] = true;
            }
            if ($incompleteRae) {
                $errors[] = 'En la competencia ' . $n . ' cada resultado de aprendizaje debe tener código y descripción.';
            }
            if ($duplicateRae) {
                $errors[] = 'En la competencia ' . $n . ' no puede haber dos RAE con el mismo código.';
            }
        }

        if ($duplicateCompCode) {
            $errors[] = 'No puede haber dos competencias con el mismo código.';
        }

        $nombreProg = trim((string) ($meta['nombre'] ?? ''));
        if ($nombreProg !== '' && $nivel !== '' && in_array($nivel, ['Técnico', 'Tecnólogo', 'Auxiliar'], true)) {
            $dupId = Programa::findIdByNombreNivel($nombreProg, $nivel, $programaId);
            if ($dupId !== null) {
                $errors[] = 'Ya existe un programa con el mismo nombre y nivel. Cambia el nombre o el nivel, o edita el programa existente desde el catálogo.';
            }
        }

        return $errors;
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
            $horas = trim((string) ($competenciaRaw['horas'] ?? ''));
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
                'horas' => $horas,
                'resultados' => $resultados,
            ];
        }

        return $normalized;
    }

    /** Suma horas declaradas por competencia (formación manual / resumen). */
    private function sumHorasFromCompetencias(array $competencias): string
    {
        $sum = 0;
        foreach ($competencias as $comp) {
            $h = trim((string) ($comp['horas'] ?? ''));
            if ($h !== '' && ctype_digit($h)) {
                $sum += (int) $h;
            }
        }

        return (string) $sum;
    }

    private function sumHoras(string $lectiva, string $productiva): string
    {
        $lectivaNum = ctype_digit($lectiva) ? (int) $lectiva : 0;
        $productivaNum = ctype_digit($productiva) ? (int) $productiva : 0;
        $total = $lectivaNum + $productivaNum;
        return $total > 0 ? (string) $total : '';
    }

    /** @return array<string,mixed> */
    private function latestSummaryDecoded(int $programaId): array
    {
        $latestImport = ProgramaImportacionPdf::latestByProgramaId($programaId);
        if ($latestImport === null) {
            return [];
        }
        $decoded = json_decode((string) ($latestImport['resumen_json'] ?? '{}'), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int,array<string,mixed>> $current
     * @param array<int,mixed> $fromSummary
     * @return array<int,array<string,mixed>>
     */
    private function mergeHorasIntoCompetencias(array $current, array $fromSummary): array
    {
        $hoursByKey = [];
        foreach ($fromSummary as $summaryComp) {
            if (!is_array($summaryComp)) {
                continue;
            }
            $summaryHours = trim((string) ($summaryComp['horas'] ?? ''));
            $summaryId = (int) ($summaryComp['id'] ?? 0);
            if ($summaryId > 0) {
                $hoursByKey['id:' . $summaryId] = $summaryHours;
            }
            $summaryCode = trim((string) ($summaryComp['codigo'] ?? ''));
            if ($summaryCode !== '') {
                $hoursByKey['code:' . $summaryCode] = $summaryHours;
            }
            $summaryName = trim((string) ($summaryComp['nombre'] ?? ''));
            if ($summaryName !== '') {
                $hoursByKey['name:' . mb_strtolower($summaryName)] = $summaryHours;
            }
        }

        foreach ($current as $idx => $comp) {
            $compId = (int) ($comp['id'] ?? 0);
            $compCode = trim((string) ($comp['codigo'] ?? ''));
            $compName = trim((string) ($comp['nombre'] ?? ''));
            $hours = '';
            if ($compId > 0 && isset($hoursByKey['id:' . $compId])) {
                $hours = (string) $hoursByKey['id:' . $compId];
            } elseif ($compCode !== '' && isset($hoursByKey['code:' . $compCode])) {
                $hours = (string) $hoursByKey['code:' . $compCode];
            } elseif ($compName !== '' && isset($hoursByKey['name:' . mb_strtolower($compName)])) {
                $hours = (string) $hoursByKey['name:' . mb_strtolower($compName)];
            }
            if ($hours !== '') {
                $current[$idx]['horas'] = $hours;
            }
        }

        return $current;
    }

}
