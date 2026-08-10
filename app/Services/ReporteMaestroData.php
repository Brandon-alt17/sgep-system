<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Imports\ImportScalar;

class ReporteMaestroData
{
    /**
     * @param array{estado?: string, ficha?: string, programa_id?: int|string, q?: string} $filters
     * @return list<array<string, mixed>>
     */
    public static function rows(array $filters = []): array
    {
        self::migrateOrphanedReporteCampos();

        $raw = self::fetchBaseRows($filters);
        if ($raw === []) {
            return [];
        }

        $ids = array_map(static fn (array $r): int => (int) ($r['id'] ?? 0), $raw);
        $documentos = array_values(array_unique(array_filter(array_map(
            static fn (array $r): string => trim((string) ($r['numero_documento'] ?? '')),
            $raw
        ))));
        $reporteByAprendiz = self::loadReporteCampos($ids);
        $momentosByAprendiz = self::loadMomentosFlags($ids);
        $modalidadInfoByAprendiz = self::loadModalidadFormacionByAprendiz($ids);
        $modalidadImportByDocumento = self::loadModalidadFuenteImportByDocumento($documentos);
        $jefeImportByDocumento = self::loadImportJefeGrupoByDocumento($documentos);
        $raw = self::sortRowsByGrupo($raw);

        $out = [];
        $index = 0;
        $currentFicha = null;
        $numEnGrupo = 0;
        foreach ($raw as $row) {
            $index++;
            $ficha = trim((string) ($row['ficha'] ?? ''));
            if ($ficha !== $currentFicha) {
                $currentFicha = $ficha;
                $numEnGrupo = 0;
            }
            $numEnGrupo++;
            $numPorGrupo = $ficha !== '' ? $numEnGrupo : '';

            $aprendizId = (int) ($row['id'] ?? 0);
            $rc = $reporteByAprendiz[$aprendizId] ?? [];
            $mom = $momentosByAprendiz[$aprendizId] ?? [];
            $doc = trim((string) ($row['numero_documento'] ?? ''));
            $importJefe = $jefeImportByDocumento[$doc] ?? ['jefe_grupo' => '', 'coordinacion' => ''];
            $out[] = self::enrichRow(
                $row,
                $index,
                $rc,
                $mom,
                $numPorGrupo,
                $modalidadInfoByAprendiz[$aprendizId] ?? '',
                $modalidadImportByDocumento[$doc] ?? '',
                $importJefe['jefe_grupo'] ?? '',
                $importJefe['coordinacion'] ?? ''
            );
        }

        return $out;
    }

    /**
     * @param array{estado?: string, ficha?: string, programa_id?: int|string, q?: string} $filters
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
                a.correo_instructor_seguimiento,
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

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $sql .= ' AND (a.nombre_completo LIKE :q_nombre OR a.numero_documento LIKE :q_documento)';
            $likeQ = '%' . $q . '%';
            $params['q_nombre'] = $likeQ;
            $params['q_documento'] = $likeQ;
        }

        self::appendActiveReportVisibilitySql($sql, $params, $filters);

        $sql .= ' ORDER BY a.ficha ASC, a.nombre_completo ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Oculta aprendices finalizados del reporte activo salvo que se pidan explícitamente.
     *
     * @param array{mostrar_finalizados?: bool|int|string, aprendiz_id?: int|string} $filters
     * @param array<string, mixed> $params
     */
    private static function appendActiveReportVisibilitySql(string &$sql, array &$params, array $filters): void
    {
        if (!empty($filters['mostrar_finalizados'])) {
            return;
        }

        $highlightId = (int) ($filters['aprendiz_id'] ?? 0);
        $activeClause = "(a.estado IS NULL OR a.estado <> 'Finalizada')
            AND NOT EXISTS (
                SELECT 1 FROM reporte_campos rc_f
                WHERE rc_f.aprendiz_id = a.id
                  AND rc_f.campo = 'estado_aprendiz'
                  AND rc_f.valor = 'Finalizado'
            )";

        if ($highlightId > 0) {
            $sql .= ' AND (a.id = :highlight_aprendiz_id OR (' . $activeClause . '))';
            $params['highlight_aprendiz_id'] = $highlightId;

            return;
        }

        $sql .= ' AND ' . $activeClause;
    }

    /**
     * @param list<int> $aprendizIds
     * @return array<int, string>
     */
    private static function loadModalidadFormacionByAprendiz(array $aprendizIds): array
    {
        $aprendizIds = array_values(array_filter($aprendizIds, static fn (int $id): bool => $id > 0));
        if ($aprendizIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($aprendizIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT aprendiz_id, modalidad_formacion
             FROM aprendiz_info_general
             WHERE aprendiz_id IN ($placeholders)"
        );
        $stmt->execute($aprendizIds);

        $out = [];
        while ($row = $stmt->fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $aid = (int) ($row['aprendiz_id'] ?? 0);
            $modalidad = trim((string) ($row['modalidad_formacion'] ?? ''));
            if ($aid > 0 && $modalidad !== '') {
                $out[$aid] = $modalidad;
            }
        }

        return $out;
    }

    /**
     * Modalidad capturada en la importación (columna modalidad_formacion del Excel).
     *
     * @param list<string> $documentos
     * @return array<string, string> clave = número de documento
     */
    private static function loadModalidadFuenteImportByDocumento(array $documentos): array
    {
        $documentos = array_values(array_unique(array_filter(array_map(
            static fn (mixed $doc): string => trim((string) $doc),
            $documentos
        ))));
        if ($documentos === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($documentos), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT pep.numero_documento, pep.modalidad_fuente
             FROM programa_enlaces_pendientes pep
             INNER JOIN (
                 SELECT numero_documento, MAX(id) AS max_id
                 FROM programa_enlaces_pendientes
                 WHERE numero_documento IN ($placeholders)
                 GROUP BY numero_documento
             ) latest ON latest.max_id = pep.id"
        );
        $stmt->execute($documentos);

        $out = [];
        while ($row = $stmt->fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $doc = trim((string) ($row['numero_documento'] ?? ''));
            $modalidad = trim((string) ($row['modalidad_fuente'] ?? ''));
            if ($doc !== '' && $modalidad !== '') {
                $out[$doc] = $modalidad;
            }
        }

        return $out;
    }

    /**
     * Jefe de grupo / coordinación capturados en la importación.
     *
     * @param list<string> $documentos
     * @return array<string, array{jefe_grupo: string, coordinacion: string}>
     */
    private static function loadImportJefeGrupoByDocumento(array $documentos): array
    {
        $documentos = array_values(array_unique(array_filter(array_map(
            static fn (mixed $doc): string => trim((string) $doc),
            $documentos
        ))));
        if ($documentos === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($documentos), '?'));
        try {
            $stmt = Database::connection()->prepare(
                "SELECT pep.numero_documento, pep.jefe_grupo_fuente, pep.coordinacion_fuente
                 FROM programa_enlaces_pendientes pep
                 INNER JOIN (
                     SELECT numero_documento, MAX(id) AS max_id
                     FROM programa_enlaces_pendientes
                     WHERE numero_documento IN ($placeholders)
                     GROUP BY numero_documento
                 ) latest ON latest.max_id = pep.id"
            );
            $stmt->execute($documentos);
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        while ($row = $stmt->fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $doc = trim((string) ($row['numero_documento'] ?? ''));
            if ($doc === '') {
                continue;
            }
            $out[$doc] = [
                'jefe_grupo' => ImportScalar::clean($row['jefe_grupo_fuente'] ?? null),
                'coordinacion' => ImportScalar::clean($row['coordinacion_fuente'] ?? null),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, string> $rc
     */
    private static function resolveInstructorJefe(
        array $rc,
        array $row,
        string $importJefeGrupo,
        string $importCoordinacion
    ): string {
        $manual = self::rc($rc, 'instructor_jefe');
        if ($manual !== '') {
            return $manual;
        }

        $jefeGrupo = ImportScalar::clean($row['jefe_grupo'] ?? null);
        if ($jefeGrupo !== '') {
            return $jefeGrupo;
        }

        $coordinacion = ImportScalar::clean($row['coordinacion'] ?? null);
        if ($coordinacion !== '') {
            return $coordinacion;
        }

        if ($importJefeGrupo !== '') {
            return $importJefeGrupo;
        }

        return $importCoordinacion;
    }

    /**
     * @param array<string, string> $rc
     */
    private static function resolveModalidadPrograma(
        array $rc,
        array $row,
        string $infoGeneralModalidad,
        string $importModalidad
    ): string {
        $manual = self::rc($rc, 'modalidad_programa');
        if ($manual !== '') {
            return $manual;
        }
        if ($infoGeneralModalidad !== '') {
            return $infoGeneralModalidad;
        }
        $programa = trim((string) ($row['modalidad_programa_db'] ?? ''));
        if ($programa !== '') {
            return $programa;
        }

        return $importModalidad;
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
     * @return array<int, array{M1?: bool, M2?: bool, M3?: bool, fecha_inicio_etapa?: string, fecha_fin_etapa?: string}>
     */
    private static function loadMomentosFlags(array $aprendizIds): array
    {
        $aprendizIds = array_values(array_filter($aprendizIds, static fn (int $id): bool => $id > 0));
        if ($aprendizIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($aprendizIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT aprendiz_id, tipo, fecha_inicio_etapa, fecha_fin_etapa FROM momentos WHERE aprendiz_id IN ($placeholders)"
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
            if ($tipo === 'M1') {
                $out[$aid]['fecha_inicio_etapa'] = trim((string) ($row['fecha_inicio_etapa'] ?? ''));
                $out[$aid]['fecha_fin_etapa'] = trim((string) ($row['fecha_fin_etapa'] ?? ''));
            }
        }

        return $out;
    }

    /**
     * Agrupa aprendices por ficha (grupo) y ordena alfabéticamente dentro de cada grupo.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private static function sortRowsByGrupo(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $fichaA = trim((string) ($a['ficha'] ?? ''));
            $fichaB = trim((string) ($b['ficha'] ?? ''));
            if ($fichaA === '' && $fichaB !== '') {
                return 1;
            }
            if ($fichaA !== '' && $fichaB === '') {
                return -1;
            }
            $byFicha = strcmp($fichaA, $fichaB);
            if ($byFicha !== 0) {
                return $byFicha;
            }

            return strcmp(
                (string) ($a['nombre_completo'] ?? ''),
                (string) ($b['nombre_completo'] ?? '')
            );
        });

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, string> $rc
     * @param array{M1?: bool, M2?: bool, M3?: bool, fecha_inicio_etapa?: string, fecha_fin_etapa?: string} $mom
     * @param int|string $numPorGrupo
     * @return array<string, mixed>
     */
    private static function enrichRow(
        array $row,
        int $index,
        array $rc,
        array $mom,
        int|string $numPorGrupo,
        string $infoGeneralModalidad = '',
        string $importModalidad = '',
        string $importJefeGrupo = '',
        string $importCoordinacion = ''
    ): array {
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
            $correoInstructor = trim((string) ($row['correo_instructor_seguimiento'] ?? ''));
        }

        $modalidadPractica = self::rc($rc, 'modalidad_practica');
        if ($modalidadPractica === '') {
            $modalidadPractica = trim((string) ($row['alternativa_ep'] ?? ''));
        }

        $enriched = [
            'id' => $aprendizId,
            'num_aprendiz' => $index,
            'num_por_grupo' => $numPorGrupo === '' ? '' : (string) $numPorGrupo,
            'ficha' => trim((string) ($row['ficha'] ?? '')),
            'programa_formacion' => trim((string) ($row['programa_formacion'] ?? '')),
            'codigo_programa' => trim((string) ($row['codigo_programa'] ?? '')),
            'nivel' => trim((string) ($row['nivel'] ?? '')),
            'modalidad_programa' => self::resolveModalidadPrograma(
                $rc,
                $row,
                $infoGeneralModalidad,
                $importModalidad
            ),
            'fecha_inicio_plataforma' => self::rcDateDmY($rc, 'fecha_inicio_plataforma'),
            'inicio_etapa_productiva' => self::rcDateDmY($rc, 'inicio_etapa_productiva'),
            'fecha_fin_plataforma' => self::rcDateDmY($rc, 'fecha_fin_plataforma'),
            'instructor_jefe' => self::resolveInstructorJefe(
                $rc,
                $row,
                $importJefeGrupo,
                $importCoordinacion
            ),
            ...self::reglamentoFields($rc, $row),
            'nombre' => trim((string) ($row['nombre_completo'] ?? '')),
            'tipo_documento' => trim((string) ($row['tipo_documento'] ?? '')),
            'identificacion' => trim((string) ($row['numero_documento'] ?? '')),
            'celular' => trim((string) ($row['telefono'] ?? '')),
            'direccion_domicilio' => trim((string) ($row['direccion_domicilio'] ?? '')),
            'correo' => trim((string) ($row['correo_personal'] ?? ''))
                ?: trim((string) ($row['correo_institucional'] ?? '')),
            'modalidad_practica' => $modalidadPractica,
            'referencia_modalidad' => $modalidadPractica,
            'fecha_aval_modalidad' => self::rcDateDmY($rc, 'fecha_aval_modalidad'),
            'estado_arl' => self::formatEstadoArlExport(self::rc($rc, 'estado_arl')),
            'arl' => self::rc($rc, 'arl'),
            // Sin fallback a created_at/updated_at: esas son marcas de tiempo del registro del
            // aprendiz en la base de datos (cuándo se creó/modificó la fila), no una fecha real de
            // etapa productiva — usarlas como último recurso hacía que la celda mostrara
            // efectivamente "hoy" (o la fecha del último guardado) para cualquier aprendiz sin
            // fecha manual ni Momento 1 registrado. Si no hay ninguna fuente real, debe quedar vacía.
            'fecha_inicio_etapa' => self::rc($rc, 'fecha_inicio_etapa') !== ''
                ? self::rcDateDmY($rc, 'fecha_inicio_etapa')
                : self::formatDate($mom['fecha_inicio_etapa'] ?? null),
            'fecha_fin_etapa' => self::rc($rc, 'fecha_fin_etapa') !== ''
                ? self::rcDateDmY($rc, 'fecha_fin_etapa')
                : self::formatDate($mom['fecha_fin_etapa'] ?? null),
            'empresa' => trim((string) ($row['empresa'] ?? '')),
            'direccion_empresa' => trim((string) ($row['direccion_empresa'] ?? '')),
            'ciudad' => self::rc($rc, 'ciudad')
                ?: trim((string) ($row['ciudad'] ?? '')),
            'contacto_empresa' => $contactoNombre,
            'telefono_contacto' => trim((string) ($row['jefe_telefono'] ?? '')),
            'correo_contacto' => trim((string) ($row['jefe_correo'] ?? ''))
                ?: trim((string) ($row['jefe_contacto2_correo'] ?? '')),
            'estado_etapa' => self::resolveEstadoEtapa($rc, $row),
            'llamados_atencion' => self::rc($rc, 'llamados_atencion'),
            'otros_novedad' => self::rc($rc, 'otros_novedad')
                ?: self::rc($rc, 'cambio_modalidad'),
            'cambio_modalidad' => self::rc($rc, 'cambio_modalidad'),
            'comite_evaluacion' => self::rc($rc, 'comite_evaluacion'),
            'reingreso_vencimiento' => self::formatReingresoVencimiento(self::rc($rc, 'reingreso_vencimiento')),
            'observaciones_novedad' => self::resolveObservacionesNovedad($rc),
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
    private static function resolveObservacionesNovedad(array $rc): string
    {
        $novedad = self::rc($rc, 'observaciones_novedad');
        if ($novedad !== '') {
            return $novedad;
        }

        return self::rc($rc, 'observaciones_cert');
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function resolveEstadoEtapa(array $rc, array $row): string
    {
        foreach (['estado_etapa'] as $key) {
            $value = self::rc($rc, $key);
            if ($value !== '' && !self::isInvalidEstadoEtapaValue($value)) {
                return $value;
            }
        }

        $estado = trim((string) ($row['estado'] ?? ''));
        if ($estado !== '' && !self::isInvalidEstadoEtapaValue($estado)) {
            return $estado;
        }

        return '';
    }

    private static function isInvalidEstadoEtapaValue(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, ['no', '0', 'false', 'n/a', 'ninguno'], true);
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

    /** @param array<string, string> $rc */
    private static function rcDateDmY(array $rc, string $key): string
    {
        $iso = date_post_to_iso(self::rc($rc, $key));
        if ($iso === '') {
            return '';
        }

        return date_iso_to_dmY($iso);
    }

    /**
     * @param list<string> $keys vacío = todos los campos guardados del aprendiz
     * @return array<string, string>
     */
    public static function camposForAprendiz(int $aprendizId, array $keys = []): array
    {
        $aprendizId = self::resolveAprendizId($aprendizId);
        if ($aprendizId <= 0) {
            return [];
        }

        $loaded = self::loadReporteCampos([$aprendizId]);
        $all = $loaded[$aprendizId] ?? [];
        if ($keys === []) {
            return $all;
        }

        $out = [];
        foreach ($keys as $key) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            $out[$key] = $all[$key] ?? '';
        }

        return $out;
    }

    /**
     * @return array{allowed: list<string>, aliases: array<string, string>, boolKeys: list<string>}
     */
    private static function editableCampoConfig(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        /** @var array<string, mixed> $map */
        $map = require base_path('config/reporte_maestro_map.php');
        $allowed = array_keys((array) ($map['columns'] ?? []));
        $allowed = array_merge($allowed, [
            'cambio_modalidad',
            'observaciones_novedad',
            'arl',
            'referencia_modalidad',
            'llamados_atencion',
            'otros_novedad',
            'comite_evaluacion',
            'vencimiento_terminos',
        ]);

        $cache = [
            'allowed' => $allowed,
            'aliases' => [
                'fecha_entrega' => 'fecha_entrega_admin',
                'observaciones' => 'observaciones_cert',
                'reingreso' => 'reingreso_vencimiento',
            ],
            'boolKeys' => array_values((array) ($map['boolean_keys'] ?? [])),
        ];

        return $cache;
    }

    public static function isEditableCampo(string $rawKey): bool
    {
        return self::resolveEditableCampoKey($rawKey) !== null;
    }

    public static function resolveEditableCampoKey(string $rawKey): ?string
    {
        $config = self::editableCampoConfig();
        $key = $config['aliases'][$rawKey] ?? $rawKey;

        return in_array($key, $config['allowed'], true) ? $key : null;
    }

    /**
     * Persiste campos editables del panel lateral (switches, certificación, novedades).
     *
     * @param array<string, mixed> $campos claves del formulario (con alias de UI)
     */
    public static function persistCampos(int $aprendizId, array $campos): void
    {
        $aprendizId = self::resolveAprendizId($aprendizId);
        if ($aprendizId <= 0 || $campos === []) {
            return;
        }

        self::normalizeReglamentoCampos($campos);

        $config = self::editableCampoConfig();
        $allowed = $config['allowed'];
        $aliases = $config['aliases'];
        $boolKeys = $config['boolKeys'];

        $stmt = Database::connection()->prepare(
            'INSERT INTO reporte_campos (aprendiz_id, campo, valor, updated_at)
             VALUES (:aprendiz_id, :campo, :valor, NOW())
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()'
        );

        foreach ($campos as $rawKey => $value) {
            if (!is_string($rawKey) && !is_int($rawKey)) {
                continue;
            }
            $key = $aliases[(string) $rawKey] ?? (string) $rawKey;
            if (!in_array($key, $allowed, true)) {
                continue;
            }

            $stored = self::normalizeStoredCampoValor($key, $value, $boolKeys);
            if ($key === 'estado_arl') {
                $stored = self::normalizeEstadoArl($stored);
            }
            $stmt->execute([
                'aprendiz_id' => $aprendizId,
                'campo' => $key,
                'valor' => $stored,
            ]);
        }

        self::syncAprendizEstadoFromReporte($aprendizId, $campos);
    }

    /**
     * @param array<string, mixed> $campos
     */
    private static function syncAprendizEstadoFromReporte(int $aprendizId, array $campos): void
    {
        if (!array_key_exists('estado_aprendiz', $campos)) {
            return;
        }

        $estadoReporte = trim((string) $campos['estado_aprendiz']);
        if ($estadoReporte !== 'Finalizado') {
            return;
        }

        Database::connection()->prepare(
            'UPDATE aprendices SET estado = :estado, updated_at = NOW() WHERE id = :id'
        )->execute([
            'estado' => 'Finalizada',
            'id' => $aprendizId,
        ]);
    }

    /**
     * Reasigna filas guardadas con cédula en lugar del id interno del aprendiz.
     */
    private static function migrateOrphanedReporteCampos(): void
    {
        try {
            Database::connection()->exec(
                'UPDATE reporte_campos rc
                 INNER JOIN aprendices a ON a.numero_documento = CAST(rc.aprendiz_id AS CHAR)
                 SET rc.aprendiz_id = a.id
                 WHERE NOT EXISTS (
                     SELECT 1 FROM aprendices valid WHERE valid.id = rc.aprendiz_id
                 )'
            );
        } catch (\Throwable $e) {
            log_error('Reporte maestro migrate campos: ' . $e->getMessage());
        }
    }

    /**
     * Acepta id interno o número de documento (datos legacy del panel lateral).
     */
    private static function resolveAprendizId(int $rawId): int
    {
        if ($rawId <= 0) {
            return 0;
        }

        $pdo = Database::connection();
        $byPk = $pdo->prepare('SELECT id FROM aprendices WHERE id = :id LIMIT 1');
        $byPk->execute(['id' => $rawId]);
        if ($byPk->fetchColumn()) {
            return $rawId;
        }

        $byDoc = $pdo->prepare('SELECT id FROM aprendices WHERE numero_documento = :doc LIMIT 1');
        $byDoc->execute(['doc' => (string) $rawId]);
        $resolved = (int) ($byDoc->fetchColumn() ?: 0);

        return $resolved > 0 ? $resolved : $rawId;
    }

    /**
     * @param list<string> $boolKeys
     */
    private static function normalizeStoredCampoValor(string $key, mixed $value, array $boolKeys): string
    {
        if (in_array($key, $boolKeys, true)) {
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            $v = strtolower(trim((string) $value));
            if ($v === '' || $v === '0' || $v === 'false' || $v === 'no') {
                return '0';
            }

            return '1';
        }

        return trim((string) $value);
    }

    public static function formatReingresoVencimiento(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return '';
        }

        $lower = mb_strtolower($v, 'UTF-8');
        if (in_array($lower, ['1', 'true', 'sí', 'si', 'yes'], true)) {
            return 'Sí';
        }
        if (in_array($lower, ['0', 'false', 'no'], true)) {
            return '';
        }

        return $v;
    }

    public static function normalizeEstadoArl(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return '';
        }

        $lower = mb_strtolower($v, 'UTF-8');
        if (
            $lower === 'en espera'
            || $lower === 'en espera de afiliación'
            || $lower === 'en espera de afiliacion'
        ) {
            return 'En espera de afiliación';
        }

        /** @var list<string> $options */
        $options = require base_path('config/estado_arl_options.php');
        foreach ($options as $option) {
            if ($lower === mb_strtolower($option, 'UTF-8')) {
                return $option;
            }
        }

        return $v;
    }

    public static function formatEstadoArlExport(string $value): string
    {
        $v = self::normalizeEstadoArl($value);

        return $v === '' ? '' : mb_strtoupper($v, 'UTF-8');
    }

    /**
     * @param array<string, string> $rc
     * @param array<string, mixed> $row
     * @return array{
     *   acuerdo_007: bool,
     *   acuerdo_009: bool,
     *   fecha_fin_plataforma: string,
     *   vencimiento_terminos: string,
     *   semaforo_vencimiento: string
     * }
     */
    public static function reglamentoFields(array $rc, array $row): array
    {
        $fechaFin = self::rcDateDmY($rc, 'fecha_fin_plataforma');
        $acuerdo007 = self::rcBool($rc, 'acuerdo_007');
        $acuerdo009 = self::rcBool($rc, 'acuerdo_009');
        if ($acuerdo007 && $acuerdo009) {
            $acuerdo009 = false;
        }

        // Si hay un vencimiento guardado manualmente (el usuario lo ajustó en el drawer), respetarlo
        // tal cual — solo se recalcula automáticamente cuando no hay un ajuste manual registrado.
        $vencimientoManual = self::rc($rc, 'vencimiento_terminos');
        $vencimientoIso = $vencimientoManual !== '' ? date_post_to_iso($vencimientoManual) : '';
        if ($vencimientoIso === '') {
            $vencimientoIso = self::computeVencimientoTerminosIso($fechaFin, $acuerdo007, $acuerdo009);
        }

        return [
            'acuerdo_007' => $acuerdo007,
            'acuerdo_009' => $acuerdo009,
            'fecha_fin_plataforma' => $fechaFin,
            'vencimiento_terminos' => $vencimientoIso !== '' ? date_iso_to_dmY($vencimientoIso) : '',
            'semaforo_vencimiento' => self::computeSemaforoVencimiento($vencimientoIso),
        ];
    }

  public static function computeVencimientoTerminosIso(string $fechaFinPlataforma, bool $acuerdo007, bool $acuerdo009): string
    {
        if (!$acuerdo007 && !$acuerdo009) {
            return '';
        }

        $iso = date_post_to_iso($fechaFinPlataforma);
        if ($iso === '') {
            return '';
        }

        $months = $acuerdo007 ? 18 : 12;

        try {
            $base = new \DateTimeImmutable($iso);

            return $base->modify('+' . $months . ' months')->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    public static function computeSemaforoVencimiento(string $vencimientoIso): string
    {
        if (trim($vencimientoIso) === '') {
            return 'SIN FECHA';
        }

        try {
            $vencimiento = new \DateTimeImmutable($vencimientoIso);
            $hoy = new \DateTimeImmutable('today');
            if ($vencimiento < $hoy) {
                return '🔴 VENCIDO';
            }

            $dias = (int) $hoy->diff($vencimiento)->format('%r%a');
            if ($dias < 90) {
                return '🟠 PRÓXIMO';
            }

            return '🟢 VIGENTE';
        } catch (\Throwable) {
            return 'SIN FECHA';
        }
    }

    /**
     * @param array<string, mixed> $campos
     */
    private static function normalizeReglamentoCampos(array &$campos): void
    {
        if (array_key_exists('reglamento_acuerdo', $campos)) {
            $seleccion = trim((string) $campos['reglamento_acuerdo']);
            $campos['acuerdo_007'] = $seleccion === '007';
            $campos['acuerdo_009'] = $seleccion === '009';
            unset($campos['reglamento_acuerdo']);
        }

        $acuerdo007 = self::valueToBool($campos['acuerdo_007'] ?? false);
        $acuerdo009 = self::valueToBool($campos['acuerdo_009'] ?? false);

        if ($acuerdo007) {
            $campos['acuerdo_007'] = true;
            $campos['acuerdo_009'] = false;
        } elseif ($acuerdo009) {
            $campos['acuerdo_007'] = false;
            $campos['acuerdo_009'] = true;
        } else {
            $campos['acuerdo_007'] = false;
            $campos['acuerdo_009'] = false;
        }
    }

    private static function valueToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $v = strtolower(trim((string) $value));

        return $v !== '' && $v !== '0' && $v !== 'false' && $v !== 'no';
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
            return self::formatReingresoVencimiento((string) ($row[$key] ?? ''));
        }

        if ($key === 'estado_arl') {
            return self::formatEstadoArlExport((string) ($row[$key] ?? ''));
        }

        return $row[$key] ?? '';
    }
}
