<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

class Aprendiz
{
    public const ESTADOS_VALIDOS = [
        'Pendiente por iniciar',
        'En ejecución',
        'Aplazada',
        'Finalizada',
        'Por certificar',
        'Certificado',
        'Pendiente por comité',
    ];

    public static function paginated(array $filters, int $limit = 25, int $offset = 0): array
    {
        $pdo = Database::connection();
        ['where' => $where, 'params' => $params] = self::buildFiltersWhere($filters);

        $sql = '
            SELECT 
                a.*,
                e.nombre AS empresa_nombre,
                e.nit
            FROM aprendices a
            LEFT JOIN empresas e ON a.empresa_id = e.id
        ';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countFiltered(array $filters): int
    {
        $pdo = Database::connection();
        ['where' => $where, 'params' => $params] = self::buildFiltersWhere($filters);

        $sql = 'SELECT COUNT(*) AS total FROM aprendices a';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private static function buildFiltersWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['estado'])) {
            $where[] = 'a.estado = :estado';
            $params['estado'] = $filters['estado'];
        }

        if (!empty($filters['ficha'])) {
            $where[] = 'a.ficha = :ficha';
            $params['ficha'] = $filters['ficha'];
        }

        $empresaId = (int) ($filters['empresa_id'] ?? 0);
        if ($empresaId > 0) {
            $where[] = 'a.empresa_id = :empresa_id';
            $params['empresa_id'] = $empresaId;
        }

        $programaId = (int) ($filters['programa_id'] ?? 0);
        if ($programaId > 0) {
            $where[] = 'a.programa_id = :programa_id';
            $params['programa_id'] = $programaId;
        }

        if (!empty($filters['q'])) {
            $where[] = '(a.nombre_completo LIKE :q_nombre OR a.numero_documento LIKE :q_documento)';
            $likeQ = '%' . $filters['q'] . '%';
            $params['q_nombre'] = $likeQ;
            $params['q_documento'] = $likeQ;
        }

        if (!empty($filters['datos_pendientes'])) {
            $nullOrEmpty = static fn (string $col): string => "($col IS NULL OR $col = \"\")";
            $nullOrZero = static fn (string $col): string => "($col IS NULL OR $col = 0)";
            $where[] = '('
                . implode(' OR ', [
                    $nullOrZero('a.empresa_id'),
                    $nullOrZero('a.programa_id'),
                    $nullOrZero('a.jefe_id'),
                    $nullOrEmpty('a.ficha'),
                    $nullOrEmpty('a.telefono'),
                    $nullOrEmpty('a.correo_personal'),
                    $nullOrEmpty('a.correo_institucional'),
                    $nullOrEmpty('a.direccion_domicilio'),
                    $nullOrEmpty('a.ciudad_domicilio'),
                    $nullOrEmpty('a.alternativa_ep'),
                    $nullOrEmpty('a.nombre_instructor_seguimiento'),
                    $nullOrEmpty('a.telefono_instructor_seguimiento'),
                    $nullOrEmpty('a.tipo_asistencia'),
                    $nullOrEmpty('a.jefe_grupo'),
                    $nullOrEmpty('a.coordinacion'),
                ])
                . ')';
        }

        return ['where' => $where, 'params' => $params];
    }

    // Agregar método para obtener valores únicos de fichas
    public static function getUniqueFichas(): array
    {
        $stmt = Database::connection()->prepare('SELECT DISTINCT ficha FROM aprendices WHERE ficha IS NOT NULL AND ficha != "" ORDER BY ficha');
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'ficha');
    }

    // Agregar método para obtener valores únicos de estados
    public static function getUniqueEstados(): array
    {
        $stmt = Database::connection()->prepare('SELECT DISTINCT estado FROM aprendices WHERE estado IS NOT NULL ORDER BY estado');
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'estado');
    }

    public static function findByCedula(string $cedula): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM aprendices WHERE numero_documento = :cedula');
        $stmt->execute(['cedula' => $cedula]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO aprendices (
            nombre_completo, tipo_documento, numero_documento, telefono,
            correo_personal, correo_institucional, direccion_domicilio, ciudad_domicilio,
            jefe_grupo, alternativa_ep, fecha_registro, ficha, estado, created_at, updated_at
        ) VALUES (
            :nombre_completo, :tipo_documento, :numero_documento, :telefono,
            :correo_personal, :correo_institucional, :direccion_domicilio, :ciudad_domicilio,
            :jefe_grupo, :alternativa_ep, :fecha_registro, :ficha, :estado, NOW(), NOW()
        )';
        
        $pdo = Database::connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre_completo'      => trim((string) ($data['nombre_completo'] ?? '')),
            'tipo_documento'       => trim((string) ($data['tipo_documento'] ?? '')),
            'numero_documento'     => trim((string) ($data['numero_documento'] ?? '')),
            'telefono'             => self::nullableTrim($data['telefono'] ?? null),
            'correo_personal'      => self::nullableTrim($data['correo_personal'] ?? null),
            'correo_institucional' => self::nullableTrim($data['correo_institucional'] ?? null),
            'direccion_domicilio'  => self::nullableTrim($data['direccion'] ?? null),
            'ciudad_domicilio'     => self::nullableTrim($data['ciudad_domicilio'] ?? null),
            'jefe_grupo'           => self::nullableTrim($data['jefe_grupo'] ?? null),
            'alternativa_ep'       => self::nullableTrim($data['alternativa'] ?? null),
            'fecha_registro'       => self::nullableTrim($data['fecha_registro'] ?? null),
            'ficha'                => self::nullableTrim($data['ficha'] ?? null),
            'estado'               => trim((string) ($data['estado'] ?? 'Pendiente por iniciar')),
        ]);
        
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $empresaId = (int) ($data['empresa_id'] ?? 0);
        if ($empresaId <= 0) {
            $stmt = Database::connection()->prepare('SELECT empresa_id FROM aprendices WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            $empresaId = (int) ($row['empresa_id'] ?? 0);
        }

        $jefeIdResolved = self::resolveJefeIdFromUpdatePayload($empresaId, $data);

        $direccionDom = normalize_multiline_text((string) ($data['direccion_domicilio'] ?? ''));
        if (mb_strlen($direccionDom) > 255) {
            $direccionDom = mb_substr($direccionDom, 0, 255);
        }

        $coordinacionRaw = trim((string) ($data['coordinacion'] ?? $data['area_coordinacion'] ?? ''));
        $jefeGrupo = trim((string) ($data['jefe_grupo'] ?? ''));
        $coordinacion = $coordinacionRaw;

        $sql = 'UPDATE aprendices SET
            nombre_completo=:nombre_completo,
            tipo_documento=:tipo_documento,
            numero_documento=:numero_documento,
            telefono=:telefono,
            correo_personal=:correo_personal,
            correo_institucional=:correo_institucional,
            direccion_domicilio=:direccion_domicilio,
            alternativa_ep=:alternativa_ep,
            ficha=:ficha,
            nombre_instructor_seguimiento=:nombre_instructor_seguimiento,
            telefono_instructor_seguimiento=:telefono_instructor_seguimiento,
            estado=:estado,
            jefe_id=:jefe_id,
            coordinacion=:coordinacion,
            jefe_grupo=:jefe_grupo,
            updated_at=NOW()
            WHERE id=:id';
        Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'nombre_completo' => trim((string) ($data['nombre_completo'] ?? '')),
            'tipo_documento' => trim((string) ($data['tipo_documento'] ?? '')),
            'numero_documento' => trim((string) ($data['numero_documento'] ?? '')),
            'telefono' => self::nullableTrim($data['telefono'] ?? null),
            'correo_personal' => self::nullableTrim($data['correo_personal'] ?? null),
            'correo_institucional' => self::nullableTrim($data['correo_institucional'] ?? null),
            'direccion_domicilio' => $direccionDom === '' ? null : $direccionDom,
            'alternativa_ep' => self::nullableTrim($data['alternativa_ep'] ?? null),
            'ficha' => self::nullableTrim($data['ficha'] ?? null),
            'nombre_instructor_seguimiento' => self::nullableTrim($data['nombre_instructor_seguimiento'] ?? null),
            'telefono_instructor_seguimiento' => self::nullableTrim($data['telefono_instructor_seguimiento'] ?? null),
            'estado' => trim((string) ($data['estado'] ?? 'Pendiente por iniciar')),
            'coordinacion' => $coordinacion === '' ? null : $coordinacion,
            'jefe_grupo' => $jefeGrupo === '' ? null : $jefeGrupo,
            'jefe_id' => $jefeIdResolved,
        ]);

        if ($empresaId > 0) {
            $emp = Empresa::findById($empresaId);
            if ($emp !== null) {
                $nombreEmp = trim((string) ($data['empresa_nombre'] ?? ''));
                if ($nombreEmp === '') {
                    $nombreEmp = trim((string) ($emp['nombre'] ?? ''));
                }
                $direccionEmp = normalize_multiline_text((string) ($data['direccion'] ?? (string) ($emp['direccion'] ?? '')));
                if (mb_strlen($direccionEmp) > 180) {
                    $direccionEmp = mb_substr($direccionEmp, 0, 180);
                }
                Empresa::update($empresaId, [
                    'nombre' => $nombreEmp !== '' ? $nombreEmp : (string) ($emp['nombre'] ?? 'Empresa'),
                    'nit' => array_key_exists('nit', $data) ? $data['nit'] : ($emp['nit'] ?? ''),
                    'direccion' => $direccionEmp === '' ? null : $direccionEmp,
                    'ciudad' => $emp['ciudad'] ?? '',
                    'correo_org' => $emp['correo_org'] ?? '',
                    'nombre_contacto2' => $emp['nombre_contacto2'] ?? '',
                    'correo_contacto2' => $emp['correo_contacto2'] ?? '',
                    'direccion_practica' => $emp['direccion_practica'] ?? '',
                ]);
            }
        }
    }

    /**
     * Evita guardar el mismo texto en jefe de grupo y área de coordinación
     * (suele ocurrir en importaciones con columnas repetidas).
     *
     * @return array{0: string, 1: string}
     */
    public static function normalizeJefeGrupoCoordinacion(string $jefeGrupo, string $coordinacion): array
    {
        $jefeGrupo = trim($jefeGrupo);
        $coordinacion = trim($coordinacion);

        if ($jefeGrupo !== '' && $coordinacion !== '' && self::coordComparableKey($jefeGrupo) === self::coordComparableKey($coordinacion)) {
            $coordinacion = '';
        }

        return [$jefeGrupo, $coordinacion];
    }

    private static function coordComparableKey(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return $normalized;
    }

    private static function nullableTrim(mixed $value): ?string
    {
        $s = trim((string) ($value ?? ''));

        return $s === '' ? null : $s;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveJefeIdFromUpdatePayload(int $empresaId, array $data): ?int
    {
        $jefeIdRaw = trim((string) ($data['jefe_id'] ?? ''));

        if ($jefeIdRaw === '' || $jefeIdRaw === '0') {
            return null;
        }

        if ($jefeIdRaw === '__new__') {
            if ($empresaId <= 0) {
                return null;
            }

            return EmpresaJefe::findOrCreate($empresaId, [
                'nombre' => $data['nombre_jefe'] ?? '',
                'cargo' => $data['cargo_jefe'] ?? '',
                'correo' => $data['correo_jefe'] ?? '',
                'telefono' => $data['telefono_jefe'] ?? '',
                'nombre_contacto2' => $data['nombre_contacto2_jefe'] ?? '',
                'correo_contacto2' => $data['correo_contacto2_jefe'] ?? '',
            ]);
        }

        $jefeId = (int) $jefeIdRaw;
        if ($jefeId <= 0) {
            return null;
        }

        $jefe = EmpresaJefe::findById($jefeId);
        if ($jefe === null) {
            return null;
        }
        if ($empresaId > 0 && (int) ($jefe['empresa_id'] ?? 0) !== $empresaId) {
            return null;
        }

        return $jefeId;
    }

    public static function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                a.*,
                p.nombre AS programa_nombre,
                e.nombre AS empresa_nombre,
                e.nit,
                e.direccion,
                j.nombre AS nombre_jefe,
                j.cargo AS cargo_jefe,
                j.telefono AS telefono_jefe,
                j.correo AS correo_jefe,
                j.nombre_contacto2 AS nombre_contacto2_jefe,
                j.correo_contacto2 AS correo_contacto2_jefe
            FROM aprendices a
            LEFT JOIN programas p ON p.id = a.programa_id
            LEFT JOIN empresas e ON a.empresa_id = e.id
            LEFT JOIN empresa_jefes j ON a.jefe_id = j.id
            WHERE a.id = :id
        ";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function updateEstado(int $id, string $nuevoEstado, ?string $motivo = null): void
    {
        if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
            throw new \InvalidArgumentException('Estado no válido.');
        }
        $pdo = Database::connection();
        $actual = self::findById($id);
        $alreadyInTx = $pdo->inTransaction();
        if (!$alreadyInTx) {
            $pdo->beginTransaction();
        }
        try {
            $pdo->prepare('UPDATE aprendices SET estado=:estado, updated_at=NOW() WHERE id=:id')
                ->execute(['estado' => $nuevoEstado, 'id' => $id]);
            $pdo->prepare('INSERT INTO estados_historial (aprendiz_id, estado_anterior, estado_nuevo, motivo, created_at) VALUES (:id, :anterior, :nuevo, :motivo, NOW())')
                ->execute([
                    'id' => $id,
                    'anterior' => $actual['estado'] ?? null,
                    'nuevo' => $nuevoEstado,
                    'motivo' => $motivo,
                ]);
            if (!$alreadyInTx) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if (!$alreadyInTx) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Próxima visita a mostrar en listados: primer momento pendiente con fecha programada.
     */
    public static function resolveProximaVisitaFromProgramadas(
        ?string $visitaM1,
        ?string $visitaM2,
        ?string $visitaM3,
        bool $m1Completada,
        bool $m2Completada,
    ): ?string {
        $d1 = self::nullableDateString($visitaM1);
        $d2 = self::nullableDateString($visitaM2);
        $d3 = self::nullableDateString($visitaM3);

        if (!$m1Completada && $d1 !== null) {
            return $d1;
        }
        if ($m1Completada && !$m2Completada && $d2 !== null) {
            return $d2;
        }
        if ($m1Completada && $m2Completada && $d3 !== null) {
            return $d3;
        }

        return null;
    }

    /**
     * Guarda las visitas programadas desde el modal de agendamiento (fechas en dd/mm/aaaa o ISO).
     *
     * @param array<string, mixed> $data
     */
    public static function saveVisitas(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }
        $pdo = Database::connection();

        $m1Completada = isset($data['completado_momento1']);
        $m2Completada = isset($data['completado_momento2']);

        $d1 = self::nullableDateString(date_post_to_iso($data['fecha_momento1'] ?? ''));
        $d2 = self::nullableDateString(date_post_to_iso($data['fecha_momento2'] ?? ''));
        $d3 = self::nullableDateString(date_post_to_iso($data['fecha_momento3'] ?? ''));

        $proximaVisita = self::resolveProximaVisitaFromProgramadas($d1, $d2, $d3, $m1Completada, $m2Completada);

        $sql = 'UPDATE aprendices SET
                    visita_programada_m1 = :visita_programada_m1,
                    visita_programada_m2 = :visita_programada_m2,
                    visita_programada_m3 = :visita_programada_m3,
                    modalidad_visita_m1 = :modalidad_visita_m1,
                    modalidad_visita_m2 = :modalidad_visita_m2,
                    modalidad_visita_m3 = :modalidad_visita_m3,
                    visita_m1_completada = :visita_m1_completada,
                    visita_m2_completada = :visita_m2_completada,
                    proxima_visita = :proxima_visita,
                    updated_at = NOW()
                WHERE id = :id';

        return $pdo->prepare($sql)->execute([
            'visita_programada_m1' => $d1,
            'visita_programada_m2' => $d2,
            'visita_programada_m3' => $d3,
            'modalidad_visita_m1' => self::normalizeModalidadVisita($data['modalidad_momento1'] ?? null),
            'modalidad_visita_m2' => self::normalizeModalidadVisita($data['modalidad_momento2'] ?? null),
            'modalidad_visita_m3' => self::normalizeModalidadVisita($data['modalidad_momento3'] ?? null),
            'visita_m1_completada' => $m1Completada ? 1 : 0,
            'visita_m2_completada' => $m2Completada ? 1 : 0,
            'proxima_visita' => $proximaVisita,
            'id' => $id,
        ]);
    }

    private static function nullableDateString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function normalizeModalidadVisita(mixed $value): ?string
    {
        $modalidad = trim((string) $value);
        if ($modalidad === '') {
            return null;
        }

        return strcasecmp($modalidad, 'Virtual') === 0 ? 'Virtual' : 'Presencial';
    }
}