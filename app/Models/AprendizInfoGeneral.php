<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class AprendizInfoGeneral
{
    public static function findByAprendizId(int $aprendizId): ?array
    {
        if ($aprendizId <= 0) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM aprendiz_info_general WHERE aprendiz_id = :aprendiz_id LIMIT 1');
        $stmt->execute(['aprendiz_id' => $aprendizId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function upsertByAprendizId(int $aprendizId, array $data): void
    {
        if ($aprendizId <= 0) {
            return;
        }

        $row = self::bindableRow($data);
        $row['aprendiz_id'] = $aprendizId;

        $sql = 'INSERT INTO aprendiz_info_general (
                    aprendiz_id,
                    regional,
                    centro_formacion,
                    nivel_formativo,
                    programa_formacion,
                    numero_grupo,
                    modalidad_formacion,
                    estrategia_formativa,
                    fecha_fin_etapa_lectiva,
                    fecha_registro_sofiaplus,
                    asistencia_nombre,
                    asistencia_tipo,
                    asistencia_contacto,
                    created_at,
                    updated_at
                ) VALUES (
                    :aprendiz_id,
                    :regional,
                    :centro_formacion,
                    :nivel_formativo,
                    :programa_formacion,
                    :numero_grupo,
                    :modalidad_formacion,
                    :estrategia_formativa,
                    :fecha_fin_etapa_lectiva,
                    :fecha_registro_sofiaplus,
                    :asistencia_nombre,
                    :asistencia_tipo,
                    :asistencia_contacto,
                    NOW(),
                    NOW()
                )
                ON DUPLICATE KEY UPDATE
                    regional = VALUES(regional),
                    centro_formacion = VALUES(centro_formacion),
                    nivel_formativo = VALUES(nivel_formativo),
                    programa_formacion = VALUES(programa_formacion),
                    numero_grupo = VALUES(numero_grupo),
                    modalidad_formacion = VALUES(modalidad_formacion),
                    estrategia_formativa = VALUES(estrategia_formativa),
                    fecha_fin_etapa_lectiva = VALUES(fecha_fin_etapa_lectiva),
                    fecha_registro_sofiaplus = VALUES(fecha_registro_sofiaplus),
                    asistencia_nombre = VALUES(asistencia_nombre),
                    asistencia_tipo = VALUES(asistencia_tipo),
                    asistencia_contacto = VALUES(asistencia_contacto),
                    updated_at = NOW()';

        Database::connection()->prepare($sql)->execute($row);
    }

    private static function bindableRow(array $data): array
    {
        $nullableString = static function (mixed $value): ?string {
            $value = trim((string) $value);
            return $value === '' ? null : $value;
        };

        $nullableDate = static function (mixed $value): ?string {
            $iso = date_post_to_iso($value);

            return $iso === '' ? null : $iso;
        };

        return [
            'regional' => $nullableString($data['regional'] ?? null),
            'centro_formacion' => $nullableString($data['centro_formacion'] ?? null),
            'nivel_formativo' => $nullableString($data['nivel_formativo'] ?? null),
            'programa_formacion' => $nullableString($data['programa_formacion'] ?? null),
            'numero_grupo' => $nullableString($data['numero_grupo'] ?? null),
            'modalidad_formacion' => $nullableString($data['modalidad_formacion'] ?? null),
            'estrategia_formativa' => $nullableString(normalize_multiline_text((string) ($data['estrategia_formativa'] ?? ''))),
            'fecha_fin_etapa_lectiva' => $nullableDate($data['fecha_fin_etapa_lectiva'] ?? null),
            'fecha_registro_sofiaplus' => $nullableDate($data['fecha_registro_sofiaplus'] ?? null),
            'asistencia_nombre' => $nullableString($data['asistencia_nombre'] ?? null),
            'asistencia_tipo' => $nullableString($data['asistencia_tipo'] ?? null),
            'asistencia_contacto' => $nullableString($data['asistencia_contacto'] ?? null),
        ];
    }
}
