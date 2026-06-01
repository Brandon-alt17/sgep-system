<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;

class ReporteMaestroData
{
    /**
     * @param array{estado?: string, ficha?: string, programa_id?: int|string} $filters
     * @return list<array<string, mixed>>
     */
    public static function rows(array $filters = []): array
    {
        $raw = self::fetchBaseRows($filters);
        if ($raw === []) {
            return [];
        }

        $ids = array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $raw);
        $reporteByAprendiz = self::loadReporteCampos($ids);
        $momentosByAprendiz = self::loadMomentosFlags($ids);

        $out = [];
        $index = 0;
        foreach ($raw as $row) {
            $index++;
            $aprendizId = (int) ($row['id'] ?? 0);
            $rc = $reporteByAprendiz[$aprendizId] ?? [];
            $mom = $momentosByAprendiz[$aprendizId] ?? [];
            $out[] = self::enrichRow($row, $index, $rc, $mom);
        }

        return $out;
    }

    /**
     * @param array{estado?: string, ficha?: string, programa_id?: int|string} $filters
     * @return list<array<string, mixed>>
     */
    private static function fetchBaseRows(array $filters): array
    {
        $sql = '
            SELECT
                a.id,
                a.nombre_completo,
                a.tipo_documento,
                a.numero_documento,
                a.telefono,
                a.correo_personal,
                a.correo_institucional,
                a.ficha,
                a.programa_id,
                a.empresa_id,
                a.estado,
                a.proxima_visita,
                a.created_at,
                a.updated_at,
                a.fecha_hora_formulario,
                a.direccion_domicilio,
                a.ciudad_domicilio,
                a.alternativa_ep,
                a.nombre_instructor_seguimiento,
                a.telefono_instructor_seguimiento,
                a.tipo_asistencia,
                a.sugerencias_comentarios,
                a.jefe_grupo,
                a.coordinacion,
                a.jefe_id,
                p.codigo AS codigo_programa,
                p.nombre AS programa_formacion,
                p.nivel,
                p.modalidad AS modalidad_programa_db,
                e.nombre AS empresa,
                e.direccion AS direccion_empresa,
                e.ciudad,
                j.nombre AS jefe_nombre,
                j.telefono AS jefe_telefono,
                j.correo AS jefe_correo,
                j.nombre_contacto2 AS jefe_contacto2_nombre,
                j.correo_contacto2 AS jefe_contacto2_correo
            FROM aprendices a
            LEFT JOIN programas p ON p.id = a.programa_id
            LEFT JOIN empresas e ON e.id = a.empresa_id
            LEFT JOIN empresa_jefes j ON j.id = a.jefe_id
            WHERE 1=1
        ';
        $params = [];

        $estado = trim((string) ($filters['estado'] ?? ''));
        if ($estado !== '') {
            $sql .= ' AND a.estado = :estado';
            $params['estado'] = $estado;
        }

        $ficha = trim((string) ($filters['ficha'] ?? ''));
        if ($ficha !== '') {
            $sql .= ' AND a.ficha = :ficha';
            $params['ficha'] = $ficha;
        }

        $programaId = (int) ($filters['programa_id'] ?? 0);
        if ($programaId > 0) {
            $sql .= ' AND a.programa_id = :programa_id';
            $params['programa_id'] = $programaId;
        }

        $sql .= ' ORDER BY a.nombre_completo ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param list<int> $aprendizIds
     * @return array<int, array<string, string>>
     */
    private static function loadReporteCampos(array $aprendizIds): array
    {
        $aprendizIds = array_values(array_filter($aprendizIds, static fn (int $id): bool => $id > 0));
        if ($aprendizIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($aprendizIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT aprendiz_id, campo, valor FROM reporte_campos WHERE aprendiz_id IN ($placeholders)"
        );
        $stmt->execute($aprendizIds);

        $out = [];
        while ($row = $stmt->fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $aid = (int) ($row['aprendiz_id'] ?? 0);
            $campo = trim((string) ($row['campo'] ?? ''));
            if ($aid <= 0 || $campo === '') {
                continue;
            }
            $out[$aid][$campo] = trim((string) ($row['valor'] ?? ''));
        }

        return $out;
    }

    /**
     * @param list<int> $aprendizIds
     * @return array<int, array{M1: bool, M2: bool, M3: bool}>
     */
    private static function loadMomentosFlags(array $aprendizIds): array
    {
        $aprendizIds = array_values(array_filter($aprendizIds, static fn (int $id): bool => $id > 0));
        if ($aprendizIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($aprendizIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT aprendiz_id, tipo FROM momentos WHERE aprendiz_id IN ($placeholders)"
        );
        $stmt->execute($aprendizIds);

        $out = [];
        while ($row = $stmt->fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $aid = (int) ($row['aprendiz_id'] ?? 0);
            $tipo = (string) ($row['tipo'] ?? '');
            if ($aid <= 0 || !in_array($tipo, ['M1', 'M2', 'M3'], true)) {
                continue;
            }
            $out[$aid][$tipo] = true;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, string> $rc
     * @param array{M1?: bool, M2?: bool, M3?: bool} $mom
     * @return array<string, mixed>
     */
    private static function enrichRow(array $row, int $index, array $rc, array $mom): array
    {
        $aprendizId = (int) ($row['id'] ?? 0);
        $hasM1 = !empty($mom['M1']);
        $hasM2 = !empty($mom['M2']);
        $hasM3 = !empty($mom['M3']);

        $contactoNombre = trim((string) ($row['jefe_nombre'] ?? ''));
        if ($contactoNombre === '') {
            $contactoNombre = trim((string) ($row['jefe_contacto2_nombre'] ?? ''));
        }

        $correoInstructor = self::rc($rc, 'correo_instructor');
        if ($correoInstructor === '') {
            $correoInstructor = trim((string) ($row['correo_institucional'] ?? ''));
        }

        $modalidadPractica = self::rc($rc, 'modalidad_practica');
        if ($modalidadPractica === '') {
            $modalidadPractica = trim((string) ($row['alternativa_ep'] ?? ''));
        }

        $enriched = [
            'id' => $aprendizId,
            'num_aprendiz' => $index,
            'num_por_grupo' => self::rc($rc, 'num_por_grupo'),
            'ficha' => trim((string) ($row['ficha'] ?? '')),
            'programa_formacion' => trim((string) ($row['programa_formacion'] ?? '')),
            'codigo_programa' => trim((string) ($row['codigo_programa'] ?? '')),
            'nivel' => trim((string) ($row['nivel'] ?? '')),
            'modalidad_programa' => self::rc($rc, 'modalidad_programa')
                ?: trim((string) ($row['modalidad_programa_db'] ?? '')),
            'fecha_inicio_plataforma' => self::rc($rc, 'fecha_inicio_plataforma')
                ?: self::formatDate($row['created_at'] ?? null),
            'inicio_etapa_productiva' => self::rc($rc, 'inicio_etapa_productiva')
                ?: self::formatDate($row['fecha_hora_formulario'] ?? null),
            'fecha_fin_plataforma' => self::rc($rc, 'fecha_fin_plataforma')
                ?: self::formatDate($row['updated_at'] ?? null),
            'instructor_jefe' => self::rc($rc, 'instructor_jefe')
                ?: trim((string) ($row['jefe_grupo'] ?? '')),
            'acuerdo_007' => self::rcBool($rc, 'acuerdo_007'),
            'acuerdo_009' => self::rcBool($rc, 'acuerdo_009'),
            'vencimiento_terminos' => self::rcBool($rc, 'vencimiento_terminos'),
            'inicio_18_meses' => self::rcBool($rc, 'inicio_18_meses'),
            'inicio_12_meses' => self::rcBool($rc, 'inicio_12_meses'),
            'semaforo_vencimiento' => self::rc($rc, 'semaforo_vencimiento'),
            'nombre' => trim((string) ($row['nombre_completo'] ?? '')),
            'tipo_documento' => trim((string) ($row['tipo_documento'] ?? '')),
            'identificacion' => trim((string) ($row['numero_documento'] ?? '')),
            'celular' => trim((string) ($row['telefono'] ?? '')),
            'direccion_domicilio' => trim((string) ($row['direccion_domicilio'] ?? '')),
            'correo' => trim((string) ($row['correo_personal'] ?? ''))
                ?: trim((string) ($row['correo_institucional'] ?? '')),
            'modalidad_practica' => $modalidadPractica,
            'referencia_modalidad' => $modalidadPractica,
            'fecha_aval_modalidad' => self::rc($rc, 'fecha_aval_modalidad')
                ?: self::formatDate($row['fecha_hora_formulario'] ?? null),
            'estado_arl' => self::rc($rc, 'estado_arl'),
            'arl' => self::rc($rc, 'arl'),
            'fecha_afiliacion_arl' => self::rc($rc, 'fecha_afiliacion_arl'),
            'fecha_inicio_etapa' => self::rc($rc, 'fecha_inicio_etapa')
                ?: self::formatDate($row['created_at'] ?? null),
            'fecha_fin_etapa' => self::rc($rc, 'fecha_fin_etapa')
                ?: self::formatDate($row['updated_at'] ?? null),
            'empresa' => trim((string) ($row['empresa'] ?? '')),
            'direccion_empresa' => trim((string) ($row['direccion_empresa'] ?? '')),
            'ciudad' => trim((string) ($row['ciudad'] ?? '')),
            'contacto_empresa' => $contactoNombre,
            'telefono_contacto' => trim((string) ($row['jefe_telefono'] ?? '')),
            'correo_contacto' => trim((string) ($row['jefe_correo'] ?? ''))
                ?: trim((string) ($row['jefe_contacto2_correo'] ?? '')),
            'estado_etapa' => self::rc($rc, 'estado_etapa')
                ?: trim((string) ($row['estado'] ?? '')),
            'llamados_atencion' => self::rc($rc, 'llamados_atencion'),
            'otros_novedad' => self::rc($rc, 'otros_novedad')
                ?: self::rc($rc, 'cambio_modalidad'),
            'cambio_modalidad' => self::rc($rc, 'cambio_modalidad'),
            'comite_evaluacion' => self::rc($rc, 'comite_evaluacion'),
            'reingreso_vencimiento' => self::rcBool($rc, 'reingreso_vencimiento'),
            'observaciones_novedad' => self::rc($rc, 'observaciones_novedad')
                ?: trim((string) ($row['sugerencias_comentarios'] ?? '')),
            'doc_gfpi_165' => self::rcBool($rc, 'doc_gfpi_165'),
            'doc_momento_1' => self::rcBool($rc, 'doc_momento_1') || $hasM1,
            'doc_bitacora_1' => self::rcBool($rc, 'doc_bitacora_1') || $hasM1,
            'doc_bitacora_2' => self::rcBool($rc, 'doc_bitacora_2') || $hasM1,
            'doc_bitacora_3' => self::rcBool($rc, 'doc_bitacora_3') || $hasM2,
            'doc_bitacora_4' => self::rcBool($rc, 'doc_bitacora_4') || $hasM2,
            'doc_bitacora_5' => self::rcBool($rc, 'doc_bitacora_5'),
            'doc_bitacora_6' => self::rcBool($rc, 'doc_bitacora_6'),
            'doc_momento_final' => self::rcBool($rc, 'doc_momento_final') || $hasM3,
            'cert_doc_identidad' => self::rcBool($rc, 'cert_doc_identidad'),
            'cert_paz_salvo' => self::rcBool($rc, 'cert_paz_salvo'),
            'cert_gfpi_023' => self::rcBool($rc, 'cert_gfpi_023'),
            'cert_bitacoras' => self::rcBool($rc, 'cert_bitacoras'),
            'cert_cumplimiento' => self::rcBool($rc, 'cert_cumplimiento'),
            'cert_ape' => self::rcBool($rc, 'cert_ape'),
            'cert_carnet' => self::rcBool($rc, 'cert_carnet'),
            'cert_saber_tyt' => self::rcBool($rc, 'cert_saber_tyt'),
            'fecha_entrega_admin' => self::rc($rc, 'fecha_entrega_admin'),
            'estado_aprendiz' => self::rc($rc, 'estado_aprendiz')
                ?: trim((string) ($row['estado'] ?? '')),
            'observaciones_cert' => self::rc($rc, 'observaciones_cert'),
            'instructor_asignado' => self::rc($rc, 'instructor_asignado')
                ?: trim((string) ($row['nombre_instructor_seguimiento'] ?? '')),
            'telefono_instructor' => self::rc($rc, 'telefono_instructor')
                ?: trim((string) ($row['telefono_instructor_seguimiento'] ?? '')),
            'correo_instructor' => $correoInstructor,
        ];

        return $enriched;
    }

    /** @param array<string, string> $rc */
    private static function rc(array $rc, string $key): string
    {
        return trim($rc[$key] ?? '');
    }

    /** @param array<string, string> $rc */
    private static function rcBool(array $rc, string $key): bool
    {
        if (!array_key_exists($key, $rc)) {
            return false;
        }
        $v = strtolower(trim($rc[$key]));
        if ($v === '' || $v === '0' || $v === 'false' || $v === 'no') {
            return false;
        }

        return true;
    }

    private static function formatDate(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        return date_iso_to_dmY($raw);
    }

    /**
     * Valor listo para escribir en Excel según tipo de campo.
     *
     * @param array<string, mixed> $row
     */
    public static function cellValue(array $row, string $key, array $mapConfig): mixed
    {
        if (in_array($key, $mapConfig['boolean_keys'] ?? [], true)) {
            return !empty($row[$key]) ? 'X' : '';
        }

        if (in_array($key, $mapConfig['date_keys'] ?? [], true)) {
            $v = trim((string) ($row[$key] ?? ''));

            return $v !== '' ? $v : '';
        }

        if ($key === 'reingreso_vencimiento') {
            return !empty($row[$key]) ? 'Sí' : 'No';
        }

        return $row[$key] ?? '';
    }
}
