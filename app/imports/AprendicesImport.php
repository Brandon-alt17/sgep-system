<?php

declare(strict_types=1);

namespace App\Imports;

use App\Helpers\Database;
use App\Helpers\Normalizer;
use App\Models\EmpresaJefe;
use App\Models\ProgramaEnlacePendiente;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AprendicesImport
{
    public function import(string $path): array
    {
        $mapping = require base_path('config/import_mapping.php');
        $sheet = IOFactory::load($path)->getActiveSheet();
        $highestDataRow = $sheet->getHighestDataRow();
        $highestDataColumn = $sheet->getHighestDataColumn();
        $rows = $sheet->rangeToArray('A1:' . $highestDataColumn . $highestDataRow, null, true, true, false);
        $results = [
            'inserted' => 0,
            'updated' => 0,
            'duplicates' => 0,
            'conflicts' => 0,
            'skipped' => 0,
            'warnings' => [],
            'errors' => [],
            'inserted_rows' => [],
            'updated_rows' => [],
            'duplicate_rows' => [],
            'pending_rows' => [],
            'programa_pending_rows' => [],
            'conflict_rows' => [],
        ];

        foreach (array_slice($rows, 1) as $index => $row) {
            if ($this->isRowCompletelyEmpty($row)) {
                // Optimización: ignora filas completamente vacías sin generar advertencias.
                continue;
            }
            try {
                $assoc = [];
                foreach ($mapping as $i => $field) {
                    $assoc[$field] = $row[$i] ?? null;
                }
                $assoc = Normalizer::normalizeRow($assoc);

                $doc = $this->normalizeDocumento(
                    $assoc['documento_identidad_vigente']
                    ?? $assoc['documento_identidad']
                    ?? null
                );
                $nombre = isset($assoc['nombre_completo']) ? trim((string) $assoc['nombre_completo']) : '';
                $usuarioLabel = $this->usuarioLabel($nombre, $doc);
                if ($doc === '' || $nombre === '') {
                    $results['skipped']++;
                    $faltantes = [];
                    if ($doc === '') {
                        $faltantes[] = 'documento_identidad_vigente';
                    }
                    if ($nombre === '') {
                        $faltantes[] = 'nombre_completo';
                    }
                    $results['warnings'][] = $usuarioLabel . ': omitido por campos requeridos vacíos (' . implode(', ', $faltantes) . ').';
                    continue;
                }

                $programaResolution = $this->findProgramaStrict(
                    $assoc['programa_formacion'] ?? null,
                    $assoc['modalidad_formacion'] ?? null
                );
                $programaId = $programaResolution['id'];
                $empresaId = $this->findOrCreateEmpresa($assoc);

                $jefeId = null;
                if ($empresaId !== null && $empresaId > 0) {
                    $jefeId = EmpresaJefe::findOrCreate($empresaId, [
                        'nombre' => $assoc['nombre_jefe'] ?? '',
                        'cargo' => $assoc['cargo_jefe'] ?? '',
                        'correo' => $assoc['correo_jefe'] ?? '',
                        'telefono' => $assoc['telefono_jefe'] ?? '',
                        'nombre_contacto2' => $assoc['nombre_contacto_2'] ?? '',
                        'correo_contacto2' => $assoc['correo_contacto_2'] ?? '',
                    ]);
                }

                $payload = $this->buildAprendizPayload($assoc, $doc, $programaId, $empresaId, $jefeId);
                $rowSummary = $this->rowSummary($assoc, $doc);

                if (($programaResolution['ambiguous'] ?? false) === true) {
                    $results['warnings'][] = $usuarioLabel . ': programa ambiguo sin nivel (' . ($programaResolution['normalized_name'] ?? 'N/D') . ').';
                    $results['programa_pending_rows'][] = [
                        'nombre' => $rowSummary['nombre'],
                        'identificacion' => $rowSummary['identificacion'],
                        'programa' => (string) ($programaResolution['normalized_name'] ?? ''),
                        'nivel_detectado' => (string) ($programaResolution['detected_level'] ?? ''),
                        'candidatos' => (array) ($programaResolution['candidates'] ?? []),
                        'motivo' => 'ambiguous',
                    ];
                    $this->registerProgramaPendingLink($assoc, $doc, $nombre, $programaResolution, 'ambiguous');
                }

                if ($programaId === null && ($programaResolution['ambiguous'] ?? false) !== true) {
                    $results['warnings'][] = $usuarioLabel . ': programa no encontrado en catálogo (' . ($programaResolution['normalized_name'] ?? 'N/D') . ').';
                    $results['programa_pending_rows'][] = [
                        'nombre' => $rowSummary['nombre'],
                        'identificacion' => $rowSummary['identificacion'],
                        'programa' => (string) ($programaResolution['normalized_name'] ?? ''),
                        'nivel_detectado' => (string) ($programaResolution['detected_level'] ?? ''),
                        'candidatos' => [],
                        'motivo' => 'not_found',
                    ];
                    $this->registerProgramaPendingLink($assoc, $doc, $nombre, $programaResolution, 'not_found');
                }

                $existing = $this->findByDocumento($doc);
                if ($existing) {
                    $aprendizId = (int) $existing['id'];
                    $comparison = $this->compareExistingWithPayload($existing, $payload);
                    $results['duplicates']++;
                    $results['duplicate_rows'][] = $rowSummary;
                    if ($comparison['conflicts'] !== []) {
                        $results['conflicts']++;
                        $results['conflict_rows'][] = [
                            'aprendiz_id' => $aprendizId,
                            'nombre' => $rowSummary['nombre'],
                            'identificacion' => $rowSummary['identificacion'],
                            'conflicts' => $comparison['conflicts'],
                        ];
                    } elseif ($comparison['fillable_payload'] !== []) {
                        $this->updateAprendizPartial($aprendizId, $comparison['fillable_payload']);
                        $results['updated']++;
                        $results['updated_rows'][] = $rowSummary;
                    }
                } else {
                    if ($this->isEmptyValue($payload['tipo_documento'] ?? null)) {
                        $payload['tipo_documento'] = 'CC';
                    }
                    $aprendizId = $this->insertAprendiz($payload);
                    $results['inserted']++;
                    $results['inserted_rows'][] = $rowSummary;
                }

                $pendingFields = $this->missingImportColumns($assoc);
                if ($pendingFields !== []) {
                    $results['pending_rows'][] = [
                        'aprendiz_id' => $aprendizId,
                        'nombre' => trim((string) ($assoc['nombre_completo'] ?? '')),
                        'identificacion' => $doc,
                        'faltantes' => $pendingFields,
                    ];
                }
            } catch (\Throwable $e) {
                $results['errors'][] = 'Fila ' . ($index + 2) . ': ' . $e->getMessage();
            }
        }

        return $results;
    }

    private function isRowCompletelyEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value === null) {
                continue;
            }
            if (is_string($value) && trim($value) === '') {
                continue;
            }
            if (is_numeric($value) && (string) $value === '0') {
                // "0" se considera dato válido.
                return false;
            }
            if ((string) $value !== '') {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, string> */
    private function rowSummary(array $assoc, string $doc): array
    {
        return [
            'nombre' => trim((string) ($assoc['nombre_completo'] ?? '')),
            'identificacion' => $doc,
            'ficha' => trim((string) ($assoc['numero_grupo'] ?? $assoc['numero_ficha'] ?? '')),
            'programa' => trim((string) ($assoc['programa_formacion'] ?? '')),
        ];
    }

    /** @return array<int, string> */
    private function missingImportColumns(array $assoc): array
    {
        $check = require base_path('config/import_mapping.php');
        $missing = [];
        foreach ($check as $field) {
            $label = (string) $field;
            if (
                $label === 'documento_identidad'
                || $label === 'documento_identidad_vigente'
                || $label === 'nombre_completo'
            ) {
                continue;
            }
            $value = $assoc[$label] ?? null;
            if ($this->isEmptyValue($value)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /** @return array{fillable_payload: array<string, mixed>, conflicts: array<int, array<string, string>>} */
    private function compareExistingWithPayload(array $existing, array $payload): array
    {
        $fields = [
            'nombre_completo',
            'tipo_documento',
            'telefono',
            'correo_personal',
            'correo_institucional',
            'ficha',
            'programa_id',
            'empresa_id',
            'jefe_id',
            'fecha_hora_formulario',
            'direccion_domicilio',
            'ciudad_domicilio',
            'alternativa_ep',
            'nombre_instructor_seguimiento',
            'telefono_instructor_seguimiento',
            'tipo_asistencia',
            'sugerencias_comentarios',
            'jefe_grupo',
            'coordinacion',
        ];

        $fillable = [];
        $conflicts = [];

        foreach ($fields as $field) {
            $incoming = $payload[$field] ?? null;
            if ($field === 'jefe_id') {
                if ($incoming === null || $incoming === '') {
                    continue;
                }
                $incoming = (int) $incoming;
                if ($incoming <= 0) {
                    continue;
                }
            } elseif ($this->isEmptyValue($incoming)) {
                continue;
            }

            $current = $existing[$field] ?? null;
            if ($field === 'jefe_id') {
                $currentInt = ($current === null || $current === '') ? 0 : (int) $current;
                if ($currentInt <= 0) {
                    $fillable[$field] = $incoming;
                    continue;
                }
                if ($currentInt === $incoming) {
                    continue;
                }
                $conflicts[] = [
                    'field' => $field,
                    'actual' => $this->stringifyValue($current),
                    'nuevo' => $this->stringifyValue($incoming),
                ];
                continue;
            }

            if ($this->isEmptyValue($current)) {
                $fillable[$field] = $incoming;
                continue;
            }

            if ($this->areEquivalentValues($current, $incoming, $field)) {
                continue;
            }

            $conflicts[] = [
                'field' => $field,
                'actual' => $this->stringifyValue($current),
                'nuevo' => $this->stringifyValue($incoming),
            ];
        }

        return [
            'fillable_payload' => $fillable,
            'conflicts' => $conflicts,
        ];
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return false;
    }

    private function areEquivalentValues(mixed $current, mixed $incoming, string $field = ''): bool
    {
        if ((is_numeric($current) || is_string($current)) && (is_numeric($incoming) || is_string($incoming))) {
            $left = $this->normalizeComparableValue((string) $current, $field);
            $right = $this->normalizeComparableValue((string) $incoming, $field);
            return $left === $right;
        }

        return $current === $incoming;
    }

    private function normalizeComparableValue(string $value, string $field): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        $normalized = mb_strtolower($normalized);
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'u'],
            $normalized
        );

        if ($field === 'tipo_documento') {
            if (str_starts_with($normalized, 'cedula de')) {
                return 'cedula de';
            }
            if (str_starts_with($normalized, 'tarjeta de')) {
                return 'tarjeta de';
            }
            if ($normalized === 'cc') {
                return 'cedula de';
            }
            if ($normalized === 'ti') {
                return 'tarjeta de';
            }
        }

        return $normalized;
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function normalizeDocumento(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $s = is_string($value) ? trim($value) : (string) $value;
        $s = preg_replace('/\s+/', '', $s) ?? $s;

        return $s;
    }

    private function usuarioLabel(string $nombre, string $doc): string
    {
        $nombreLimpio = trim($nombre);
        if ($nombreLimpio === '') {
            $nombreLimpio = 'Usuario sin nombre';
        }

        $docLimpio = trim($doc);
        if ($docLimpio === '') {
            return $nombreLimpio . ' (documento sin registrar)';
        }

        return $nombreLimpio . ' (documento ' . $docLimpio . ')';
    }

    private function buildAprendizPayload(array $assoc, string $doc, ?int $programaId, ?int $empresaId, ?int $jefeId = null): array
    {
        $ficha = $this->stringOrNull($assoc['numero_grupo'] ?? $assoc['numero_ficha'] ?? null);

        return [
            'nombre_completo' => trim((string) ($assoc['nombre_completo'] ?? '')),
            'tipo_documento' => $this->stringOrNull($assoc['tipo_documento'] ?? null),
            'numero_documento' => $doc,
            'telefono' => $this->stringOrNull($assoc['numero_celular'] ?? null),
            'correo_personal' => $this->stringOrNull($assoc['correo_electronico_personal'] ?? null),
            'correo_institucional' => $this->stringOrNull($assoc['correo_electronico_institucional'] ?? null),
            'ficha' => $ficha,
            'programa_id' => $programaId,
            'empresa_id' => $empresaId,
            'jefe_id' => $jefeId,
            'estado' => 'Pendiente por iniciar',
            'fecha_hora_formulario' => $this->toMysqlDateTime($assoc['fecha_hora_formulario'] ?? null),
            'direccion_domicilio' => $this->stringOrNull($assoc['direccion_domicilio_aprendiz'] ?? null),
            'ciudad_domicilio' => $this->stringOrNull($assoc['ciudad_domicilio_aprendiz'] ?? null),
            'alternativa_ep' => $this->stringOrNull($assoc['alternativa_ep'] ?? null),
            'nombre_instructor_seguimiento' => $this->stringOrNull($assoc['nombre_instructor_seguimiento'] ?? null),
            'telefono_instructor_seguimiento' => $this->stringOrNull($assoc['telefono_instructor_seguimiento'] ?? null),
            'tipo_asistencia' => $this->stringOrNull($assoc['tipo_asistencia'] ?? null),
            'sugerencias_comentarios' => $this->stringOrNull($assoc['sugerencias_comentarios'] ?? null),
            'jefe_grupo' => $this->stringOrNull($assoc['jefe_grupo'] ?? null),
            'coordinacion' => $this->stringOrNull($assoc['coordinacion'] ?? null),
        ];
    }

    private function stringOrNull(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_string($v)) {
            $s = trim($v);

            return $s === '' ? null : $s;
        }
        if (is_numeric($v)) {
            return trim((string) $v);
        }

        return trim((string) $v);
    }

    private function toMysqlDateTime(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d H:i:s');
        }
        if (is_numeric($v)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $v)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }
        if (is_string($v)) {
            $ts = strtotime($v);

            return $ts ? date('Y-m-d H:i:s', $ts) : null;
        }

        return null;
    }

    private function findByDocumento(string $documento): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM aprendices WHERE numero_documento = :doc');
        $stmt->execute(['doc' => $documento]);

        return $stmt->fetch() ?: null;
    }

    /** @return array{id: ?int, ambiguous: bool, normalized_name: string, detected_level: ?string, candidates: array<int, string>} */
    private function findProgramaStrict(mixed $nombrePrograma, mixed $modalidad): array
    {
        $nombreRaw = $this->stringOrNull($nombrePrograma);
        if ($nombreRaw === null) {
            return [
                'id' => null,
                'ambiguous' => false,
                'normalized_name' => '',
                'detected_level' => null,
                'candidates' => [],
            ];
        }
        $nombre = Normalizer::normalizeProgramaNombre($nombreRaw);
        $nivel = Normalizer::extractProgramaNivel($nombreRaw);
        $nombreKey = Normalizer::normalizeProgramComparableKey($nombre);
        $nivelKey = Normalizer::normalizeProgramComparableKey((string) ($nivel ?? ''));

        $pdo = Database::connection();
        $rows = $pdo->query('SELECT id, nombre, nivel, modalidad FROM programas')->fetchAll();
        $matched = [];
        foreach ($rows as $row) {
            $rowNombre = Normalizer::normalizeProgramaNombre((string) ($row['nombre'] ?? ''));
            if (Normalizer::normalizeProgramComparableKey($rowNombre) !== $nombreKey) {
                continue;
            }
            $matched[] = $row;
        }

        $selected = null;
        if ($nivelKey !== '') {
            foreach ($matched as $row) {
                $rowNivelKey = Normalizer::normalizeProgramComparableKey((string) ($row['nivel'] ?? ''));
                if ($rowNivelKey === $nivelKey || $rowNivelKey === '') {
                    $selected = $row;
                    break;
                }
            }
        } elseif (count($matched) === 1) {
            $selected = $matched[0];
        }

        if ($selected !== null) {
            return [
                'id' => (int) $selected['id'],
                'ambiguous' => false,
                'normalized_name' => $nombre,
                'detected_level' => $nivel,
                'candidates' => [],
            ];
        }

        if ($nivelKey === '' && count($matched) > 1) {
            $candidates = [];
            foreach ($matched as $m) {
                $candidateNivel = trim((string) ($m['nivel'] ?? ''));
                $candidates[] = $candidateNivel !== '' ? $candidateNivel : 'Sin nivel';
            }
            return [
                'id' => null,
                'ambiguous' => true,
                'normalized_name' => $nombre,
                'detected_level' => null,
                'candidates' => array_values(array_unique($candidates)),
            ];
        }

        return [
            'id' => null,
            'ambiguous' => false,
            'normalized_name' => $nombre,
            'detected_level' => $nivel,
            'candidates' => [],
        ];
    }

    /** @param array<string,mixed> $assoc @param array{id:?int, ambiguous:bool, normalized_name:string, detected_level:?string, candidates:array<int,string>} $programaResolution */
    private function registerProgramaPendingLink(array $assoc, string $doc, string $nombre, array $programaResolution, string $reason): void
    {
        $programaFuente = trim((string) ($assoc['programa_formacion'] ?? ''));
        if ($programaFuente === '') {
            $programaFuente = (string) ($programaResolution['normalized_name'] ?? '');
        }
        if ($programaFuente === '') {
            return;
        }

        ProgramaEnlacePendiente::createOrIgnorePending([
            'numero_documento' => $doc,
            'nombre_aprendiz' => $nombre,
            'programa_fuente' => $programaFuente,
            'nivel_fuente' => (string) ($programaResolution['detected_level'] ?? ''),
            'modalidad_fuente' => trim((string) ($assoc['modalidad_formacion'] ?? '')),
            'candidatos_json' => json_encode((array) ($programaResolution['candidates'] ?? []), JSON_UNESCAPED_UNICODE),
            'motivo' => $reason,
        ]);
    }

    private function findOrCreateEmpresa(array $assoc): ?int
    {
        $nombre = $this->stringOrNull($assoc['empresa_entidad_coformadora'] ?? null);
        if ($nombre === null) {
            return null;
        }

        $nit = $this->stringOrNull($assoc['nit_empresa'] ?? null);
        $pdo = Database::connection();

        if ($nit !== null) {
            $stmt = $pdo->prepare('SELECT id FROM empresas WHERE nit = :nit LIMIT 1');
            $stmt->execute(['nit' => $nit]);
            $row = $stmt->fetch();
            if ($row) {
                $this->mergeEmpresa((int) $row['id'], $assoc);

                return (int) $row['id'];
            }
        }

        $stmt = $pdo->prepare('SELECT id FROM empresas WHERE nombre = :n LIMIT 1');
        $stmt->execute(['n' => $nombre]);
        $row = $stmt->fetch();
        if ($row) {
            $this->mergeEmpresa((int) $row['id'], $assoc);

            return (int) $row['id'];
        }

        $sql = 'INSERT INTO empresas (nombre, nit, direccion, correo_org, nombre_contacto2, correo_contacto2, direccion_practica, created_at, updated_at)
                VALUES (:nombre, :nit, :direccion, :correo_org, :nombre_contacto2, :correo_contacto2, :direccion_practica, NOW(), NOW())';
        $params = $this->empresaParams($assoc);
        $pdo->prepare($sql)->execute($params);

        return (int) $pdo->lastInsertId();
    }

    /** @return array<string, mixed> */
    private function empresaParams(array $assoc): array
    {
        return [
            'nombre' => $this->stringOrNull($assoc['empresa_entidad_coformadora'] ?? null) ?? '',
            'nit' => $this->stringOrNull($assoc['nit_empresa'] ?? null),
            'direccion' => $this->stringOrNull($assoc['direccion_empresa'] ?? null),
            'correo_org' => $this->stringOrNull($assoc['correo_organizacional'] ?? null),
            'nombre_contacto2' => $this->stringOrNull($assoc['nombre_contacto_2'] ?? null),
            'correo_contacto2' => $this->stringOrNull($assoc['correo_contacto_2'] ?? null),
            'direccion_practica' => $this->stringOrNull($assoc['direccion_realiza_practica'] ?? null),
        ];
    }

    private function mergeEmpresa(int $id, array $assoc): void
    {
        $sql = 'UPDATE empresas SET
                    nit = COALESCE(NULLIF(:nit, ""), nit),
                    direccion = COALESCE(NULLIF(:direccion, ""), direccion),
                    correo_org = COALESCE(NULLIF(:correo_org, ""), correo_org),
                    nombre_contacto2 = COALESCE(NULLIF(:nombre_contacto2, ""), nombre_contacto2),
                    correo_contacto2 = COALESCE(NULLIF(:correo_contacto2, ""), correo_contacto2),
                    direccion_practica = COALESCE(NULLIF(:direccion_practica, ""), direccion_practica),
                    updated_at = NOW()
                WHERE id = :id';
        $p = $this->empresaParamsForUpdate($assoc);
        $p['id'] = $id;
        Database::connection()->prepare($sql)->execute($p);
    }

    /** @return array<string, mixed> */
    private function empresaParamsForUpdate(array $assoc): array
    {
        $full = $this->empresaParams($assoc);
        unset($full['nombre']);

        return $full;
    }

    /** @param array<string, mixed> $data */
    private function insertAprendiz(array $data): int
    {
        $sql = 'INSERT INTO aprendices (
                    nombre_completo, tipo_documento, numero_documento, telefono, correo_personal, correo_institucional,
                    ficha, programa_id, empresa_id, jefe_id, estado,
                    fecha_hora_formulario, direccion_domicilio, ciudad_domicilio, alternativa_ep,
                    nombre_instructor_seguimiento, telefono_instructor_seguimiento, tipo_asistencia, sugerencias_comentarios,
                    jefe_grupo, coordinacion,
                    created_at, updated_at
                ) VALUES (
                    :nombre_completo, :tipo_documento, :numero_documento, :telefono, :correo_personal, :correo_institucional,
                    :ficha, :programa_id, :empresa_id, :jefe_id, :estado,
                    :fecha_hora_formulario, :direccion_domicilio, :ciudad_domicilio, :alternativa_ep,
                    :nombre_instructor_seguimiento, :telefono_instructor_seguimiento, :tipo_asistencia, :sugerencias_comentarios,
                    :jefe_grupo, :coordinacion,
                    NOW(), NOW()
                )';
        $pdo = Database::connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre_completo' => $data['nombre_completo'],
            'tipo_documento' => $data['tipo_documento'],
            'numero_documento' => $data['numero_documento'],
            'telefono' => $data['telefono'],
            'correo_personal' => $data['correo_personal'],
            'correo_institucional' => $data['correo_institucional'],
            'ficha' => $data['ficha'],
            'programa_id' => $data['programa_id'],
            'empresa_id' => $data['empresa_id'],
            'jefe_id' => $data['jefe_id'] ?? null,
            'estado' => $data['estado'],
            'fecha_hora_formulario' => $data['fecha_hora_formulario'],
            'direccion_domicilio' => $data['direccion_domicilio'],
            'ciudad_domicilio' => $data['ciudad_domicilio'],
            'alternativa_ep' => $data['alternativa_ep'],
            'nombre_instructor_seguimiento' => $data['nombre_instructor_seguimiento'],
            'telefono_instructor_seguimiento' => $data['telefono_instructor_seguimiento'],
            'tipo_asistencia' => $data['tipo_asistencia'],
            'sugerencias_comentarios' => $data['sugerencias_comentarios'],
            'jefe_grupo' => $data['jefe_grupo'],
            'coordinacion' => $data['coordinacion'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    private function updateAprendiz(int $id, array $data): void
    {
        $sql = 'UPDATE aprendices SET
            nombre_completo = :nombre_completo,
            tipo_documento = COALESCE(NULLIF(:tipo_documento, ""), tipo_documento),
            telefono = COALESCE(NULLIF(:telefono, ""), telefono),
            correo_personal = COALESCE(NULLIF(:correo_personal, ""), correo_personal),
            correo_institucional = COALESCE(NULLIF(:correo_institucional, ""), correo_institucional),
            ficha = COALESCE(NULLIF(:ficha, ""), ficha),
            programa_id = COALESCE(:programa_id, programa_id),
            empresa_id = COALESCE(:empresa_id, empresa_id),
            jefe_id = COALESCE(:jefe_id, jefe_id),
            fecha_hora_formulario = COALESCE(:fecha_hora_formulario, fecha_hora_formulario),
            direccion_domicilio = COALESCE(NULLIF(:direccion_domicilio, ""), direccion_domicilio),
            ciudad_domicilio = COALESCE(NULLIF(:ciudad_domicilio, ""), ciudad_domicilio),
            alternativa_ep = COALESCE(NULLIF(:alternativa_ep, ""), alternativa_ep),
            nombre_instructor_seguimiento = COALESCE(NULLIF(:nombre_instructor_seguimiento, ""), nombre_instructor_seguimiento),
            telefono_instructor_seguimiento = COALESCE(NULLIF(:telefono_instructor_seguimiento, ""), telefono_instructor_seguimiento),
            tipo_asistencia = COALESCE(NULLIF(:tipo_asistencia, ""), tipo_asistencia),
            sugerencias_comentarios = COALESCE(NULLIF(:sugerencias_comentarios, ""), sugerencias_comentarios),
            jefe_grupo = COALESCE(NULLIF(:jefe_grupo, ""), jefe_grupo),
            coordinacion = COALESCE(NULLIF(:coordinacion, ""), coordinacion),
            updated_at = NOW()
            WHERE id = :id';

        $bind = [
            'id' => $id,
            'nombre_completo' => $data['nombre_completo'] ?? '',
            'tipo_documento' => $data['tipo_documento'] ?? '',
            'telefono' => $data['telefono'] ?? '',
            'correo_personal' => $data['correo_personal'] ?? '',
            'correo_institucional' => $data['correo_institucional'] ?? '',
            'ficha' => $data['ficha'] ?? '',
            'programa_id' => $data['programa_id'] ?? null,
            'empresa_id' => $data['empresa_id'] ?? null,
            'jefe_id' => $data['jefe_id'] ?? null,
            'fecha_hora_formulario' => $data['fecha_hora_formulario'] ?? null,
            'direccion_domicilio' => $data['direccion_domicilio'] ?? '',
            'ciudad_domicilio' => $data['ciudad_domicilio'] ?? '',
            'alternativa_ep' => $data['alternativa_ep'] ?? '',
            'nombre_instructor_seguimiento' => $data['nombre_instructor_seguimiento'] ?? '',
            'telefono_instructor_seguimiento' => $data['telefono_instructor_seguimiento'] ?? '',
            'tipo_asistencia' => $data['tipo_asistencia'] ?? '',
            'sugerencias_comentarios' => $data['sugerencias_comentarios'] ?? '',
            'jefe_grupo' => $data['jefe_grupo'] ?? '',
            'coordinacion' => $data['coordinacion'] ?? '',
        ];
        Database::connection()->prepare($sql)->execute($bind);
    }

    /** @param array<string, mixed> $fillableData */
    private function updateAprendizPartial(int $id, array $fillableData): void
    {
        if ($fillableData === []) {
            return;
        }

        $allowed = [
            'nombre_completo',
            'tipo_documento',
            'telefono',
            'correo_personal',
            'correo_institucional',
            'ficha',
            'programa_id',
            'empresa_id',
            'jefe_id',
            'fecha_hora_formulario',
            'direccion_domicilio',
            'ciudad_domicilio',
            'alternativa_ep',
            'nombre_instructor_seguimiento',
            'telefono_instructor_seguimiento',
            'tipo_asistencia',
            'sugerencias_comentarios',
            'jefe_grupo',
            'coordinacion',
        ];

        $setParts = [];
        $params = ['id' => $id];
        foreach ($fillableData as $field => $value) {
            if (!in_array($field, $allowed, true)) {
                continue;
            }
            $setParts[] = $field . ' = :' . $field;
            $params[$field] = $field === 'jefe_id' ? (int) $value : $value;
        }

        if ($setParts === []) {
            return;
        }

        $sql = 'UPDATE aprendices SET ' . implode(', ', $setParts) . ', updated_at = NOW() WHERE id = :id';
        Database::connection()->prepare($sql)->execute($params);
    }
}
