<?php

declare(strict_types=1);

namespace App\Imports;

use App\Helpers\Database;
use App\Helpers\Normalizer;
use App\Models\Aprendiz;
use App\Models\Empresa;
use App\Models\EmpresaJefe;
use App\Models\Programa;
use App\Models\ProgramaEnlacePendiente;
use App\Services\ProgramaCatalogMatcher;
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
        $headerRow = $rows[0] ?? [];
        $columnMap = ImportColumnResolver::resolve($headerRow, $mapping);
        $fallbackFields = ImportColumnResolver::fieldsUsingDefaultFallback($headerRow, $mapping);
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

        if ($fallbackFields !== []) {
            /** @var array<string, string> $fieldLabels */
            $fieldLabels = require base_path('config/import_field_labels.php');
            $fallbackLabels = array_map(
                static fn (string $field): string => $fieldLabels[$field] ?? $field,
                $fallbackFields
            );
            $results['warnings'][] = 'No se reconoció el encabezado de estas columnas; se usó su '
                . 'posición habitual en la plantilla — verifique que los datos correspondan: '
                . implode(', ', $fallbackLabels) . '.';
        }

        $seenThisRun = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            if ($this->isRowCompletelyEmpty($row)) {
                // Optimización: ignora filas completamente vacías sin generar advertencias.
                continue;
            }
            try {
                $assoc = [];
                foreach ($columnMap as $field => $columnIndex) {
                    $assoc[$field] = $row[$columnIndex] ?? null;
                }
                $assoc = Normalizer::normalizeRow($assoc);
                $assoc['jefe_grupo'] = ImportScalar::clean($assoc['jefe_grupo'] ?? null);
                $assoc['coordinacion'] = ImportScalar::clean($assoc['coordinacion'] ?? null);

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

                $programaResolution = ProgramaCatalogMatcher::resolve(
                    $assoc['programa_formacion'] ?? null,
                    $assoc['modalidad_formacion'] ?? null
                );
                $programaId = $programaResolution['id'];
                $existing = $this->findByDocumento($doc);
                $yaTieneProgramaVinculado = ProgramaEnlacePendiente::aprendizHasValidProgramaVinculo($doc);

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

                if ($yaTieneProgramaVinculado) {
                    ProgramaEnlacePendiente::closePendingIfAprendizVinculado($doc);
                } elseif (($programaResolution['ambiguous'] ?? false) === true) {
                    $results['warnings'][] = $usuarioLabel . ': programa ambiguo sin nivel (' . ($programaResolution['normalized_name'] ?? 'N/D') . ').';
                    $this->queueProgramaPending($results, $assoc, $doc, $nombre, $rowSummary, $programaResolution, 'ambiguous');
                } elseif ($programaId === null) {
                    $results['warnings'][] = $usuarioLabel . ': programa no encontrado en catálogo (' . ($programaResolution['normalized_name'] ?? 'N/D') . ').';
                    $this->queueProgramaPending($results, $assoc, $doc, $nombre, $rowSummary, $programaResolution, 'not_found');
                }
                if ($existing) {
                    $aprendizId = (int) $existing['id'];
                    $isIntraFileDuplicate = isset($seenThisRun[$doc]);

                    if ($isIntraFileDuplicate) {
                        $this->removePreviousConflict($results, $aprendizId);

                        $refreshed = $this->findByDocumento($doc);
                        if ($refreshed) {
                            $comparison = $this->compareExistingWithPayload($refreshed, $payload, $assoc, $jefeId, $empresaId);

                            $autoFillable = $comparison['auto_accept'] ?? [];
                            if ($autoFillable !== []) {
                                $this->updateAprendizPartial($aprendizId, $autoFillable);
                            }

                            if ($comparison['conflicts'] !== []) {
                                $results['conflicts']++;
                                $results['conflict_rows'][] = [
                                    'aprendiz_id' => $aprendizId,
                                    'nombre' => $rowSummary['nombre'],
                                    'identificacion' => $rowSummary['identificacion'],
                                    'incoming_jefe_id' => $jefeId !== null && $jefeId > 0 ? $jefeId : null,
                                    'empresa_id' => $empresaId !== null && $empresaId > 0 ? $empresaId : null,
                                    'file_supervisor' => $this->fileSupervisorSnapshot($assoc),
                                    'file_correo_org' => $this->stringOrNull($assoc['correo_organizacional'] ?? null),
                                    'conflicts' => $comparison['conflicts'],
                                ];
                            } elseif ($comparison['fillable_payload'] !== []) {
                                $this->updateAprendizPartial($aprendizId, $comparison['fillable_payload']);
                                $results['updated']++;
                                $results['updated_rows'][] = $rowSummary;
                            }
                        }
                    } else {
                        $seenThisRun[$doc] = true;
                        $comparison = $this->compareExistingWithPayload($existing, $payload, $assoc, $jefeId, $empresaId);
                        $results['duplicates']++;
                        $results['duplicate_rows'][] = $rowSummary;

                        $autoFillable = $comparison['auto_accept'] ?? [];
                        if ($autoFillable !== []) {
                            $this->updateAprendizPartial($aprendizId, $autoFillable);
                        }

                        if ($comparison['conflicts'] !== []) {
                            $results['conflicts']++;
                            $results['conflict_rows'][] = [
                                'aprendiz_id' => $aprendizId,
                                'nombre' => $rowSummary['nombre'],
                                'identificacion' => $rowSummary['identificacion'],
                                'incoming_jefe_id' => $jefeId !== null && $jefeId > 0 ? $jefeId : null,
                                'empresa_id' => $empresaId !== null && $empresaId > 0 ? $empresaId : null,
                                'file_supervisor' => $this->fileSupervisorSnapshot($assoc),
                                'file_correo_org' => $this->stringOrNull($assoc['correo_organizacional'] ?? null),
                                'conflicts' => $comparison['conflicts'],
                            ];
                        } elseif ($comparison['fillable_payload'] !== []) {
                            $this->updateAprendizPartial($aprendizId, $comparison['fillable_payload']);
                            $results['updated']++;
                            $results['updated_rows'][] = $rowSummary;
                        }
                    }
                } else {
                    if ($this->isEmptyValue($payload['tipo_documento'] ?? null)) {
                        $payload['tipo_documento'] = 'CC';
                    }
                    $aprendizId = $this->insertAprendiz($payload);
                    $seenThisRun[$doc] = true;
                    $results['inserted']++;
                    $results['inserted_rows'][] = $rowSummary;
                }

                ProgramaEnlacePendiente::saveImportFuente($doc, [
                    'modalidad_fuente' => trim((string) ($assoc['modalidad_formacion'] ?? '')),
                    'jefe_grupo_fuente' => trim((string) ($assoc['jefe_grupo'] ?? '')),
                    'coordinacion_fuente' => trim((string) ($assoc['coordinacion'] ?? '')),
                ]);

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

    /** @param array<string, mixed> $results */
    private function removePreviousConflict(array &$results, int $aprendizId): void
    {
        foreach ($results['conflict_rows'] as $i => $entry) {
            if ((int) ($entry['aprendiz_id'] ?? 0) === $aprendizId) {
                unset($results['conflict_rows'][$i]);
                $results['conflicts'] = max(0, $results['conflicts'] - 1);
                break;
            }
        }
        $results['conflict_rows'] = array_values($results['conflict_rows']);
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

    /**
     * @param array<string, mixed> $importAssoc Fila del archivo (para etiquetas legibles en conflictos).
     * @return array{fillable_payload: array<string, mixed>, auto_accept: array<string, mixed>, conflicts: array<int, array<string, string>>}
     */
    private function compareExistingWithPayload(
        array $existing,
        array $payload,
        array $importAssoc = [],
        ?int $incomingJefeId = null,
        ?int $incomingEmpresaId = null,
    ): array {
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
            'direccion_domicilio',
            'ciudad_domicilio',
            'alternativa_ep',
            'nombre_instructor_seguimiento',
            'telefono_instructor_seguimiento',
            'correo_instructor_seguimiento',
            'tipo_asistencia',
            'sugerencias_comentarios',
            'jefe_grupo',
            'coordinacion',
        ];

        $autoAcceptFields = ['fecha_hora_formulario'];

        $autoAccept = [];
        $fillable = [];
        $conflicts = [];

        foreach ($autoAcceptFields as $autoField) {
            $incoming = $payload[$autoField] ?? null;
            if (!$this->isEmptyValue($incoming)) {
                $autoAccept[$autoField] = $incoming;
            }
        }

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
            if (in_array($field, ['jefe_grupo', 'coordinacion'], true) && ImportScalar::isIgnorable($current)) {
                $current = null;
            }
            if ($field === 'jefe_id') {
                $incomingJefe = (int) $incoming;
                if ($incomingJefe <= 0) {
                    continue;
                }
                $currentInt = ($current === null || $current === '') ? 0 : (int) $current;
                if ($currentInt <= 0) {
                    $fillable[$field] = $incomingJefe;
                } elseif ($currentInt !== $incomingJefe) {
                    if ($this->areEquivalentJefesByName($currentInt, $incomingJefe)) {
                        $fillable[$field] = $incomingJefe;
                    } else {
                        $conflicts[] = $this->buildConflictEntry($field, $current, $incoming, $importAssoc);
                    }
                }
                continue;
            }

            if ($this->isEmptyValue($current)) {
                $fillable[$field] = $incoming;
                continue;
            }

            if ($field === 'empresa_id') {
                $currentEmpId = (int) $current;
                $incomingEmpId = (int) $incoming;
                if ($currentEmpId > 0 && $incomingEmpId > 0 && $currentEmpId !== $incomingEmpId) {
                    if ($this->areEquivalentEmpresasByName($currentEmpId, $incomingEmpId)) {
                        $fillable[$field] = $incomingEmpId;
                    } else {
                        $conflicts[] = $this->buildConflictEntry($field, $current, $incoming, $importAssoc);
                    }
                    continue;
                }
            }

            if ($this->areEquivalentValues($current, $incoming, $field)) {
                continue;
            }

            $conflicts[] = $this->buildConflictEntry($field, $current, $incoming, $importAssoc);
        }

        $empresaId = (int) ($incomingEmpresaId ?? 0);
        if ($empresaId <= 0) {
            $empresaId = (int) ($existing['empresa_id'] ?? 0);
        }
        $conflicts = array_merge(
            $conflicts,
            $this->expandSupervisorColumnConflicts($existing, $importAssoc, $incomingJefeId, $empresaId)
        );

        return [
            'fillable_payload' => $fillable,
            'auto_accept' => $autoAccept,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Conflictos por columna del Excel (R–U): correo org., nombre, cargo y correo del jefe.
     *
     * @return array<int, array<string, string>>
     */
    private function expandSupervisorColumnConflicts(
        array $existing,
        array $importAssoc,
        ?int $incomingJefeId,
        int $empresaId,
    ): array {
        $conflicts = [];
        $incomingJefe = (int) ($incomingJefeId ?? 0);
        $currentJefeId = (int) ($existing['jefe_id'] ?? 0);

        if ($empresaId > 0) {
            $empresa = Empresa::findById($empresaId);
            $fileCorreoOrg = $this->stringOrNull($importAssoc['correo_organizacional'] ?? null);
            if ($fileCorreoOrg !== null) {
                $currentCorreoOrg = trim((string) ($empresa['correo_org'] ?? ''));
                if (!$this->areEquivalentValues($currentCorreoOrg === '' ? null : $currentCorreoOrg, $fileCorreoOrg, 'correo_organizacional')) {
                    $conflicts[] = $this->buildScalarConflictEntry(
                        'correo_organizacional',
                        $currentCorreoOrg !== '' ? $currentCorreoOrg : 'Sin dato',
                        $fileCorreoOrg
                    );
                }
            }
        }

        if ($incomingJefe > 0 && $incomingJefe !== $currentJefeId) {
            return $conflicts;
        }

        $currentJefe = $currentJefeId > 0 ? EmpresaJefe::findById($currentJefeId) : null;
        $jefeColumns = [
            'jefe_nombre' => ['column' => 'nombre', 'file_key' => 'nombre_jefe'],
            'jefe_correo' => ['column' => 'correo', 'file_key' => 'correo_jefe'],
            // jefe_cargo y jefe_telefono excluidos: distintas filas del mismo Excel
            // pueden tener valores diferentes para el mismo supervisor (datos inconsistentes
            // en la fuente), lo que genera un ping-pong de conflictos entre aprendices.
            // El cargo y teléfono del supervisor se actualizan manualmente desde la UI.
        ];

        foreach ($jefeColumns as $fieldKey => $meta) {
            $fileValue = $this->stringOrNull($importAssoc[$meta['file_key']] ?? null);
            if ($fileValue === null) {
                continue;
            }

            $currentValue = $currentJefe !== null
                ? trim((string) ($currentJefe[$meta['column']] ?? ''))
                : '';
            if ($this->areEquivalentValues($currentValue === '' ? null : $currentValue, $fileValue, $fieldKey)) {
                continue;
            }

            $conflicts[] = $this->buildScalarConflictEntry(
                $fieldKey,
                $currentValue !== '' ? $currentValue : 'Sin dato',
                $fileValue
            );
        }

        return $conflicts;
    }

    /** @return array{field: string, actual: string, nuevo: string, nuevo_sub: string, nuevo_raw: string} */
    private function buildScalarConflictEntry(string $field, string $actual, string $nuevo): array
    {
        return [
            'field' => $field,
            'actual' => $actual,
            'nuevo' => $nuevo,
            'nuevo_sub' => '',
            'nuevo_raw' => $nuevo,
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

    private function areEquivalentEmpresasByName(int $currentId, int $incomingId): bool
    {
        $currentEmpresa = Empresa::findById($currentId);
        $incomingEmpresa = Empresa::findById($incomingId);
        $currentName = $this->normalizeComparableValue(trim((string) ($currentEmpresa['nombre'] ?? '')), 'empresa_nombre');
        $incomingName = $this->normalizeComparableValue(trim((string) ($incomingEmpresa['nombre'] ?? '')), 'empresa_nombre');
        if ($currentName === '' || $incomingName === '') {
            return false;
        }

        return $currentName === $incomingName;
    }

    private function areEquivalentJefesByName(int $currentId, int $incomingId): bool
    {
        $currentJefe = EmpresaJefe::findById($currentId);
        $incomingJefe = EmpresaJefe::findById($incomingId);
        $currentName = $this->normalizeComparableValue(trim((string) ($currentJefe['nombre'] ?? '')), 'jefe_nombre');
        $incomingName = $this->normalizeComparableValue(trim((string) ($incomingJefe['nombre'] ?? '')), 'jefe_nombre');
        if ($currentName === '' || $incomingName === '') {
            return false;
        }

        return $currentName === $incomingName;
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
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $normalized
        );

        if (in_array($field, ['jefe_nombre', 'jefe_cargo', 'nombre_completo', 'jefe_grupo', 'coordinacion'], true)) {
            $normalized = preg_replace('/[^a-z0-9\s]/u', '', $normalized) ?? $normalized;
            $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        }

        if ($field === 'jefe_cargo') {
            $parts = explode(' ', $normalized);
            if ($parts !== []) {
                $last = (string) end($parts);
                if (strlen($last) > 3 && str_ends_with($last, 's')) {
                    $parts[count($parts) - 1] = substr($last, 0, -1);
                    $normalized = implode(' ', $parts);
                }
            }
        }

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

        if (in_array($field, ['telefono', 'jefe_telefono', 'telefono_instructor_seguimiento'], true)) {
            return preg_replace('/\D+/', '', $normalized) ?? $normalized;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $assoc
     * @return array{nombre: string, cargo: ?string, correo: ?string, telefono: ?string}
     */
    private function fileSupervisorSnapshot(array $assoc): array
    {
        $nombre = trim((string) ($assoc['nombre_jefe'] ?? ''));

        return [
            'nombre' => $nombre,
            'cargo' => $this->stringOrNull($assoc['cargo_jefe'] ?? null),
            'correo' => $this->stringOrNull($assoc['correo_jefe'] ?? null),
            'telefono' => $this->stringOrNull($assoc['telefono_jefe'] ?? null),
        ];
    }

    /**
     * @return array{field: string, actual: string, nuevo: string, nuevo_sub: string, nuevo_raw: string}
     */
    private function buildConflictEntry(string $field, mixed $current, mixed $incoming, array $importAssoc): array
    {
        [$nuevo, $nuevoSub] = $this->formatConflictIncomingParts($field, $incoming, $importAssoc);

        return [
            'field' => $field,
            'actual' => $this->formatConflictDisplayValue($field, $current, $importAssoc, false),
            'nuevo' => $nuevo,
            'nuevo_sub' => $nuevoSub ?? '',
            'nuevo_raw' => $this->stringifyValue($incoming),
        ];
    }

    /** @return array{0: string, 1: ?string} */
    private function formatConflictIncomingParts(string $field, mixed $value, array $importAssoc): array
    {
        if ($field === 'jefe_id') {
            return $this->formatJefeConflictParts($value, $importAssoc, true);
        }
        if ($field === 'empresa_id') {
            return $this->formatEmpresaConflictParts($value, $importAssoc, true);
        }
        if ($field === 'programa_id') {
            return $this->formatProgramaConflictParts($value, $importAssoc, true);
        }

        return [$this->formatConflictDisplayValue($field, $value, $importAssoc, true), null];
    }

    private function formatConflictDisplayValue(string $field, mixed $value, array $importAssoc, bool $fromImport): string
    {
        if ($field === 'jefe_id') {
            return $this->formatJefeConflictLabel($value, $importAssoc, $fromImport);
        }
        if ($field === 'empresa_id') {
            return $this->formatEmpresaConflictLabel($value, $importAssoc, $fromImport);
        }
        if ($field === 'programa_id') {
            return $this->formatProgramaConflictLabel($value, $importAssoc, $fromImport);
        }

        $text = $this->stringifyValue($value);
        if ($field === 'tipo_documento') {
            return $text;
        }

        return $text;
    }

    private function formatJefeConflictLabel(mixed $jefeId, array $importAssoc, bool $fromImport): string
    {
        [$main] = $this->formatJefeConflictParts($jefeId, $importAssoc, $fromImport);

        return $main;
    }

    /** @return array{0: string, 1: ?string} */
    private function formatJefeConflictParts(mixed $jefeId, array $importAssoc, bool $fromImport): array
    {
        $id = (int) $jefeId;
        $fileNombre = trim((string) ($importAssoc['nombre_jefe'] ?? ''));
        $fileCargo = trim((string) ($importAssoc['cargo_jefe'] ?? ''));
        $fileCorreo = trim((string) ($importAssoc['correo_jefe'] ?? ''));
        $fileHint = $this->buildJefeHintLine($fileNombre, $fileCargo, $fileCorreo);

        if ($id <= 0) {
            if ($fromImport && $fileHint !== '') {
                return [$fileHint, null];
            }

            return ['Sin supervisor asignado', null];
        }

        $jefe = EmpresaJefe::findById($id);
        if ($jefe === null) {
            $main = $fromImport && $fileHint !== '' ? $fileHint : ('Supervisor #' . $id);

            return [$main, $fromImport && $fileHint !== '' ? ('Vinculado en catálogo: #' . $id) : null];
        }

        $catalogLabel = $this->buildJefeHintLine(
            trim((string) ($jefe['nombre'] ?? '')),
            trim((string) ($jefe['cargo'] ?? '')),
            trim((string) ($jefe['correo'] ?? ''))
        );
        if ($catalogLabel === '') {
            $catalogLabel = 'Supervisor #' . $id;
        }

        if ($fromImport && $fileHint !== '') {
            $sub = $this->conflictCatalogSubline($fileHint, $catalogLabel) ? ('Vinculado en catálogo: ' . $catalogLabel) : null;

            return [$fileHint, $sub];
        }

        return [$catalogLabel, null];
    }

    private function buildJefeHintLine(string $nombre, string $cargo, string $correo): string
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return '';
        }

        $hint = $nombre;
        if (trim($cargo) !== '') {
            $hint .= ' — ' . trim($cargo);
        }
        if (trim($correo) !== '') {
            $hint .= ' (' . trim($correo) . ')';
        }

        return $hint;
    }

    private function conflictCatalogSubline(string $fileLine, string $catalogLine): bool
    {
        if ($catalogLine === '' || $fileLine === $catalogLine) {
            return false;
        }

        $fileKey = Normalizer::normalizeProgramComparableKey($fileLine);
        $catalogKey = Normalizer::normalizeProgramComparableKey($catalogLine);

        return $fileKey !== $catalogKey;
    }

    private function formatEmpresaConflictLabel(mixed $empresaId, array $importAssoc, bool $fromImport): string
    {
        [$main] = $this->formatEmpresaConflictParts($empresaId, $importAssoc, $fromImport);

        return $main;
    }

    /** @return array{0: string, 1: ?string} */
    private function formatEmpresaConflictParts(mixed $empresaId, array $importAssoc, bool $fromImport): array
    {
        $id = (int) $empresaId;
        $fileNombre = trim((string) ($importAssoc['empresa_entidad_coformadora'] ?? ''));

        if ($id <= 0) {
            return [$fromImport && $fileNombre !== '' ? $fileNombre : 'Sin empresa', null];
        }

        $empresa = Empresa::findById($id);
        $label = trim((string) ($empresa['nombre'] ?? ''));
        if ($label === '') {
            $label = 'Empresa #' . $id;
        }

        if ($fromImport && $fileNombre !== '') {
            $sub = $this->conflictCatalogSubline($fileNombre, $label) ? ('Vinculado en catálogo: ' . $label) : null;

            return [$fileNombre, $sub];
        }

        return [$label, null];
    }

    private function formatProgramaConflictLabel(mixed $programaId, array $importAssoc, bool $fromImport): string
    {
        [$main] = $this->formatProgramaConflictParts($programaId, $importAssoc, $fromImport);

        return $main;
    }

    /** @return array{0: string, 1: ?string} */
    private function formatProgramaConflictParts(mixed $programaId, array $importAssoc, bool $fromImport): array
    {
        $id = (int) $programaId;
        $fileNombre = trim((string) ($importAssoc['programa_formacion'] ?? ''));

        if ($id <= 0) {
            return [$fromImport && $fileNombre !== '' ? $fileNombre : 'Sin programa', null];
        }

        $programa = Programa::findById($id);
        $label = trim((string) ($programa['nombre'] ?? ''));
        $nivel = trim((string) ($programa['nivel'] ?? ''));
        if ($nivel !== '' && $label !== '') {
            $label .= ' (' . $nivel . ')';
        }
        if ($label === '') {
            $label = 'Programa #' . $id;
        }

        if ($fromImport && $fileNombre !== '') {
            $sub = $this->conflictCatalogSubline($fileNombre, $label) ? ('Texto en archivo: ' . $fileNombre) : null;

            return [$label, $sub];
        }

        return [$label, null];
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
        [$jefeGrupo, $coordinacion] = Aprendiz::normalizeJefeGrupoCoordinacion(
            (string) ($this->stringOrNull($assoc['jefe_grupo'] ?? null) ?? ''),
            (string) ($this->stringOrNull($assoc['coordinacion'] ?? null) ?? '')
        );

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
            'correo_instructor_seguimiento' => $this->stringOrNull($assoc['correo_instructor_seguimiento'] ?? null),
            'tipo_asistencia' => $this->stringOrNull($assoc['tipo_asistencia'] ?? null),
            'sugerencias_comentarios' => $this->stringOrNull($assoc['sugerencias_comentarios'] ?? null),
            'jefe_grupo' => $this->stringOrNull($jefeGrupo !== '' ? $jefeGrupo : null),
            'coordinacion' => $this->stringOrNull($coordinacion !== '' ? $coordinacion : null),
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

    /**
     * @param array<string,mixed> $results
     * @param array<string,string> $rowSummary
     * @param array{id:?int, ambiguous:bool, normalized_name:string, detected_level:?string, candidates:array<int,string>} $programaResolution
     */
    private function queueProgramaPending(
        array &$results,
        array $assoc,
        string $doc,
        string $nombre,
        array $rowSummary,
        array $programaResolution,
        string $reason
    ): void {
        $programaFuente = trim((string) ($assoc['programa_formacion'] ?? ''));
        if ($programaFuente === '') {
            $programaFuente = (string) ($programaResolution['normalized_name'] ?? '');
        }
        if ($programaFuente === '') {
            return;
        }

        $results['programa_pending_rows'][] = [
            'nombre' => $rowSummary['nombre'],
            'identificacion' => $rowSummary['identificacion'],
            'programa' => $programaFuente,
            'nivel_detectado' => (string) ($programaResolution['detected_level'] ?? ''),
            'candidatos' => (array) ($programaResolution['candidates'] ?? []),
            'motivo' => $reason,
        ];
        $this->registerProgramaPendingLink($assoc, $doc, $nombre, $programaResolution, $reason);
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
            'jefe_grupo_fuente' => trim((string) ($assoc['jefe_grupo'] ?? '')),
            'coordinacion_fuente' => trim((string) ($assoc['coordinacion'] ?? '')),
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

        $sql = 'INSERT INTO empresas (nombre, nit, direccion, ciudad, correo_org, nombre_contacto2, correo_contacto2, direccion_practica, created_at, updated_at)
                VALUES (:nombre, :nit, :direccion, :ciudad, :correo_org, :nombre_contacto2, :correo_contacto2, :direccion_practica, NOW(), NOW())';
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
            'ciudad' => $this->stringOrNull($assoc['ciudad_empresa'] ?? null),
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
                    ciudad = COALESCE(NULLIF(:ciudad, ""), ciudad),
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
                    nombre_instructor_seguimiento, telefono_instructor_seguimiento, correo_instructor_seguimiento, tipo_asistencia, sugerencias_comentarios,
                    jefe_grupo, coordinacion,
                    created_at, updated_at
                ) VALUES (
                    :nombre_completo, :tipo_documento, :numero_documento, :telefono, :correo_personal, :correo_institucional,
                    :ficha, :programa_id, :empresa_id, :jefe_id, :estado,
                    :fecha_hora_formulario, :direccion_domicilio, :ciudad_domicilio, :alternativa_ep,
                    :nombre_instructor_seguimiento, :telefono_instructor_seguimiento, :correo_instructor_seguimiento, :tipo_asistencia, :sugerencias_comentarios,
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
            'correo_instructor_seguimiento' => $data['correo_instructor_seguimiento'],
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
            correo_instructor_seguimiento = COALESCE(NULLIF(:correo_instructor_seguimiento, ""), correo_instructor_seguimiento),
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
            'correo_instructor_seguimiento' => $data['correo_instructor_seguimiento'] ?? '',
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
            'correo_instructor_seguimiento',
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
