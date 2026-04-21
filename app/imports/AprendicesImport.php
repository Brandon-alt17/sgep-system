<?php

declare(strict_types=1);

namespace App\Imports;

use App\Helpers\Database;
use App\Helpers\Normalizer;
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
            'skipped' => 0,
            'warnings' => [],
            'errors' => [],
            'inserted_rows' => [],
            'updated_rows' => [],
            'duplicate_rows' => [],
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

                $doc = $this->normalizeDocumento($assoc['documento_identidad'] ?? null);
                $nombre = isset($assoc['nombre_completo']) ? trim((string) $assoc['nombre_completo']) : '';
                if ($doc === '' || $nombre === '') {
                    $results['skipped']++;
                    $faltantes = [];
                    if ($doc === '') {
                        $faltantes[] = 'documento_identidad';
                    }
                    if ($nombre === '') {
                        $faltantes[] = 'nombre_completo';
                    }
                    $results['warnings'][] = 'Fila ' . ($index + 2) . ': omitida por campos requeridos vacíos (' . implode(', ', $faltantes) . ').';
                    continue;
                }

                $optionalMissing = $this->missingOptionalFields($assoc);
                if ($optionalMissing !== []) {
                    $results['warnings'][] = 'Fila ' . ($index + 2) . ': campos vacíos (' . implode(', ', $optionalMissing) . '). Se pueden completar luego en gestión de usuarios.';
                }

                $programaId = $this->findOrCreatePrograma(
                    $assoc['programa_formacion'] ?? null,
                    $assoc['modalidad_formacion'] ?? null
                );
                $empresaId = $this->findOrCreateEmpresa($assoc);

                $payload = $this->buildAprendizPayload($assoc, $doc, $programaId, $empresaId);

                $existing = $this->findByDocumento($doc);
                if ($existing) {
                    $this->updateAprendiz((int) $existing['id'], $payload);
                    $results['updated']++;
                    $results['duplicates']++;
                    $rowSummary = $this->rowSummary($assoc, $doc);
                    $results['updated_rows'][] = $rowSummary;
                    $results['duplicate_rows'][] = $rowSummary;
                } else {
                    $this->insertAprendiz($payload);
                    $results['inserted']++;
                    $results['inserted_rows'][] = $this->rowSummary($assoc, $doc);
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
            'ficha' => trim((string) ($assoc['numero_ficha'] ?? '')),
            'programa' => trim((string) ($assoc['programa_formacion'] ?? '')),
        ];
    }

    /** @return array<int, string> */
    private function missingOptionalFields(array $assoc): array
    {
        $check = [
            'tipo_documento',
            'numero_celular',
            'correo_electronico_personal',
            'correo_electronico_institucional',
            'programa_formacion',
            'numero_ficha',
            'empresa_entidad_coformadora',
        ];
        $missing = [];
        foreach ($check as $field) {
            $value = $assoc[$field] ?? null;
            if ($value === null || trim((string) $value) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
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

    private function buildAprendizPayload(array $assoc, string $doc, ?int $programaId, ?int $empresaId): array
    {
        $ficha = $this->stringOrNull($assoc['numero_ficha'] ?? null);
        if ($ficha === null || $ficha === '') {
            $ficha = $this->stringOrNull($assoc['ficha_curso'] ?? null);
        }

        return [
            'nombre_completo' => trim((string) ($assoc['nombre_completo'] ?? '')),
            'tipo_documento' => $this->stringOrNull($assoc['tipo_documento'] ?? null) ?? 'CC',
            'numero_documento' => $doc,
            'telefono' => $this->stringOrNull($assoc['numero_celular'] ?? null),
            'correo_personal' => $this->stringOrNull($assoc['correo_electronico_personal'] ?? null),
            'correo_institucional' => $this->stringOrNull($assoc['correo_electronico_institucional'] ?? null),
            'ficha' => $ficha,
            'programa_id' => $programaId,
            'empresa_id' => $empresaId,
            'estado' => 'Pendiente por iniciar',
            'fecha_hora_formulario' => $this->toMysqlDateTime($assoc['fecha_hora_formulario'] ?? null),
            'direccion_domicilio' => $this->stringOrNull($assoc['direccion_domicilio_aprendiz'] ?? null),
            'ciudad_domicilio' => $this->stringOrNull($assoc['ciudad_domicilio_aprendiz'] ?? null),
            'alternativa_ep' => $this->stringOrNull($assoc['alternativa_ep'] ?? null),
            'fecha_sofia' => $this->toMysqlDate($assoc['fecha_sofia'] ?? null),
            'nombre_instructor_seguimiento' => $this->stringOrNull($assoc['nombre_instructor_seguimiento'] ?? null),
            'telefono_instructor_seguimiento' => $this->stringOrNull($assoc['telefono_instructor_seguimiento'] ?? null),
            'tipo_asistencia' => $this->stringOrNull($assoc['tipo_asistencia'] ?? null),
            'sugerencias_comentarios' => $this->stringOrNull($assoc['sugerencias_comentarios'] ?? null),
            'ficha_curso' => $this->stringOrNull($assoc['ficha_curso'] ?? null),
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

    private function toMysqlDate(mixed $v): ?string
    {
        $dt = $this->toMysqlDateTime($v);
        if ($dt === null) {
            return null;
        }

        return substr($dt, 0, 10);
    }

    private function findByDocumento(string $documento): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM aprendices WHERE numero_documento = :doc');
        $stmt->execute(['doc' => $documento]);

        return $stmt->fetch() ?: null;
    }

    private function findOrCreatePrograma(mixed $nombrePrograma, mixed $modalidad): ?int
    {
        $nombre = $this->stringOrNull($nombrePrograma);
        if ($nombre === null) {
            return null;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, modalidad FROM programas WHERE nombre = :n LIMIT 1');
        $stmt->execute(['n' => $nombre]);
        $row = $stmt->fetch();
        if ($row) {
            $mod = $this->stringOrNull($modalidad);
            if ($mod !== null && ($row['modalidad'] === null || $row['modalidad'] === '')) {
                $pdo->prepare('UPDATE programas SET modalidad = :m WHERE id = :id')
                    ->execute(['m' => $mod, 'id' => (int) $row['id']]);
            }

            return (int) $row['id'];
        }

        $mod = $this->stringOrNull($modalidad);
        $pdo->prepare('INSERT INTO programas (nombre, modalidad, created_at) VALUES (:nombre, :modalidad, NOW())')
            ->execute(['nombre' => $nombre, 'modalidad' => $mod]);

        return (int) $pdo->lastInsertId();
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

        $sql = 'INSERT INTO empresas (nombre, nit, direccion, correo_org, nombre_jefe, cargo_jefe, correo_jefe, telefono_jefe, nombre_contacto2, correo_contacto2, direccion_practica, created_at, updated_at)
                VALUES (:nombre, :nit, :direccion, :correo_org, :nombre_jefe, :cargo_jefe, :correo_jefe, :telefono_jefe, :nombre_contacto2, :correo_contacto2, :direccion_practica, NOW(), NOW())';
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
            'nombre_jefe' => $this->stringOrNull($assoc['nombre_jefe'] ?? null),
            'cargo_jefe' => $this->stringOrNull($assoc['cargo_jefe'] ?? null),
            'correo_jefe' => $this->stringOrNull($assoc['correo_jefe'] ?? null),
            'telefono_jefe' => $this->stringOrNull($assoc['telefono_jefe'] ?? null),
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
                    nombre_jefe = COALESCE(NULLIF(:nombre_jefe, ""), nombre_jefe),
                    cargo_jefe = COALESCE(NULLIF(:cargo_jefe, ""), cargo_jefe),
                    correo_jefe = COALESCE(NULLIF(:correo_jefe, ""), correo_jefe),
                    telefono_jefe = COALESCE(NULLIF(:telefono_jefe, ""), telefono_jefe),
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
    private function insertAprendiz(array $data): void
    {
        $sql = 'INSERT INTO aprendices (
                    nombre_completo, tipo_documento, numero_documento, telefono, correo_personal, correo_institucional,
                    ficha, programa_id, empresa_id, estado,
                    fecha_hora_formulario, direccion_domicilio, ciudad_domicilio, alternativa_ep, fecha_sofia,
                    nombre_instructor_seguimiento, telefono_instructor_seguimiento, tipo_asistencia, sugerencias_comentarios,
                    ficha_curso, jefe_grupo, coordinacion,
                    created_at, updated_at
                ) VALUES (
                    :nombre_completo, :tipo_documento, :numero_documento, :telefono, :correo_personal, :correo_institucional,
                    :ficha, :programa_id, :empresa_id, :estado,
                    :fecha_hora_formulario, :direccion_domicilio, :ciudad_domicilio, :alternativa_ep, :fecha_sofia,
                    :nombre_instructor_seguimiento, :telefono_instructor_seguimiento, :tipo_asistencia, :sugerencias_comentarios,
                    :ficha_curso, :jefe_grupo, :coordinacion,
                    NOW(), NOW()
                )';
        $stmt = Database::connection()->prepare($sql);
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
            'estado' => $data['estado'],
            'fecha_hora_formulario' => $data['fecha_hora_formulario'],
            'direccion_domicilio' => $data['direccion_domicilio'],
            'ciudad_domicilio' => $data['ciudad_domicilio'],
            'alternativa_ep' => $data['alternativa_ep'],
            'fecha_sofia' => $data['fecha_sofia'],
            'nombre_instructor_seguimiento' => $data['nombre_instructor_seguimiento'],
            'telefono_instructor_seguimiento' => $data['telefono_instructor_seguimiento'],
            'tipo_asistencia' => $data['tipo_asistencia'],
            'sugerencias_comentarios' => $data['sugerencias_comentarios'],
            'ficha_curso' => $data['ficha_curso'],
            'jefe_grupo' => $data['jefe_grupo'],
            'coordinacion' => $data['coordinacion'],
        ]);
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
            fecha_hora_formulario = COALESCE(:fecha_hora_formulario, fecha_hora_formulario),
            direccion_domicilio = COALESCE(NULLIF(:direccion_domicilio, ""), direccion_domicilio),
            ciudad_domicilio = COALESCE(NULLIF(:ciudad_domicilio, ""), ciudad_domicilio),
            alternativa_ep = COALESCE(NULLIF(:alternativa_ep, ""), alternativa_ep),
            fecha_sofia = COALESCE(:fecha_sofia, fecha_sofia),
            nombre_instructor_seguimiento = COALESCE(NULLIF(:nombre_instructor_seguimiento, ""), nombre_instructor_seguimiento),
            telefono_instructor_seguimiento = COALESCE(NULLIF(:telefono_instructor_seguimiento, ""), telefono_instructor_seguimiento),
            tipo_asistencia = COALESCE(NULLIF(:tipo_asistencia, ""), tipo_asistencia),
            sugerencias_comentarios = COALESCE(NULLIF(:sugerencias_comentarios, ""), sugerencias_comentarios),
            ficha_curso = COALESCE(NULLIF(:ficha_curso, ""), ficha_curso),
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
            'fecha_hora_formulario' => $data['fecha_hora_formulario'] ?? null,
            'direccion_domicilio' => $data['direccion_domicilio'] ?? '',
            'ciudad_domicilio' => $data['ciudad_domicilio'] ?? '',
            'alternativa_ep' => $data['alternativa_ep'] ?? '',
            'fecha_sofia' => $data['fecha_sofia'] ?? null,
            'nombre_instructor_seguimiento' => $data['nombre_instructor_seguimiento'] ?? '',
            'telefono_instructor_seguimiento' => $data['telefono_instructor_seguimiento'] ?? '',
            'tipo_asistencia' => $data['tipo_asistencia'] ?? '',
            'sugerencias_comentarios' => $data['sugerencias_comentarios'] ?? '',
            'ficha_curso' => $data['ficha_curso'] ?? '',
            'jefe_grupo' => $data['jefe_grupo'] ?? '',
            'coordinacion' => $data['coordinacion'] ?? '',
        ];
        Database::connection()->prepare($sql)->execute($bind);
    }
}
