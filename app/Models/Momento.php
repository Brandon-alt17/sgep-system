<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class Momento
{
    public static function findByAprendiz(int $aprendizId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM momentos WHERE aprendiz_id = :id ORDER BY created_at ASC');
        $stmt->execute(['id' => $aprendizId]);
        return $stmt->fetchAll();
    }

    public static function existsTipo(int $aprendizId, string $tipo): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM momentos WHERE aprendiz_id = :id AND tipo = :tipo');
        $stmt->execute(['id' => $aprendizId, 'tipo' => $tipo]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function nextExtraNumero(int $aprendizId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM momentos WHERE aprendiz_id = :id AND tipo = "EX"');
        $stmt->execute(['id' => $aprendizId]);
        return ((int) $stmt->fetchColumn()) + 1;
    }

    public static function findOneByAprendizTipo(int $aprendizId, string $tipo): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM momentos WHERE aprendiz_id = :aprendiz_id AND tipo = :tipo ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([
            'aprendiz_id' => $aprendizId,
            'tipo' => $tipo,
        ]);
        return $stmt->fetch() ?: null;
    }

    public static function factoresByMomento(int $momentoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT tipo_factor, nombre_factor, valoracion, observacion FROM factores_valoracion WHERE momento_id = :momento_id ORDER BY id ASC'
        );
        $stmt->execute(['momento_id' => $momentoId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO momentos (
                    aprendiz_id,
                    tipo,
                    numero_visita,
                    fecha_visita,
                    modalidad,
                    obs_instructor,
                    obs_aprendiz,
                    obs_coformador,
                    juicio_final,
                    fecha_inicio_etapa,
                    fecha_fin_etapa,
                    fecha_arl,
                    numero_poliza_arl,
                    horario,
                    enlace_grabacion,
                    ciudad_diligenciamiento,
                    fecha_diligenciamiento,
                    modalidad_diligenciamiento,
                    numero_visitas_realizadas,
                    m1_competencias,
                    m1_resultados,
                    m1_actividades,
                    m1_evidencias,
                    m1_observaciones_adicionales,
                    m3_retro_coformador_proceso,
                    m3_retro_coformador_desempeno,
                    m3_retro_instructor_proceso,
                    m3_retro_instructor_desempeno,
                    m3_retro_aprendiz_proceso,
                    m3_retro_aprendiz_desempeno,
                    created_at,
                    updated_at
                )
                VALUES (
                    :aprendiz_id,
                    :tipo,
                    :numero_visita,
                    :fecha_visita,
                    :modalidad,
                    :obs_instructor,
                    :obs_aprendiz,
                    :obs_coformador,
                    :juicio_final,
                    :fecha_inicio_etapa,
                    :fecha_fin_etapa,
                    :fecha_arl,
                    :numero_poliza_arl,
                    :horario,
                    :enlace_grabacion,
                    :ciudad_diligenciamiento,
                    :fecha_diligenciamiento,
                    :modalidad_diligenciamiento,
                    :numero_visitas_realizadas,
                    :m1_competencias,
                    :m1_resultados,
                    :m1_actividades,
                    :m1_evidencias,
                    :m1_observaciones_adicionales,
                    :m3_retro_coformador_proceso,
                    :m3_retro_coformador_desempeno,
                    :m3_retro_instructor_proceso,
                    :m3_retro_instructor_desempeno,
                    :m3_retro_aprendiz_proceso,
                    :m3_retro_aprendiz_desempeno,
                    NOW(),
                    NOW()
                )';
        $pdo = Database::connection();
        $pdo->prepare($sql)->execute([
            'aprendiz_id' => $data['aprendiz_id'],
            'tipo' => $data['tipo'],
            'numero_visita' => $data['numero_visita'] ?? null,
            'fecha_visita' => $data['fecha_visita'] ?? null,
            'modalidad' => $data['modalidad'] ?? null,
            'obs_instructor' => $data['obs_instructor'] ?? null,
            'obs_aprendiz' => $data['obs_aprendiz'] ?? null,
            'obs_coformador' => $data['obs_coformador'] ?? null,
            'juicio_final' => $data['juicio_final'] ?? null,
            'fecha_inicio_etapa' => $data['fecha_inicio_etapa'] ?? null,
            'fecha_fin_etapa' => $data['fecha_fin_etapa'] ?? null,
            'fecha_arl' => $data['fecha_arl'] ?? null,
            'numero_poliza_arl' => $data['numero_poliza_arl'] ?? null,
            'horario' => $data['horario'] ?? null,
            'enlace_grabacion' => $data['enlace_grabacion'] ?? null,
            'ciudad_diligenciamiento' => $data['ciudad_diligenciamiento'] ?? null,
            'fecha_diligenciamiento' => $data['fecha_diligenciamiento'] ?? null,
            'modalidad_diligenciamiento' => $data['modalidad_diligenciamiento'] ?? null,
            'numero_visitas_realizadas' => $data['numero_visitas_realizadas'] ?? null,
            'm1_competencias' => $data['m1_competencias'] ?? null,
            'm1_resultados' => $data['m1_resultados'] ?? null,
            'm1_actividades' => $data['m1_actividades'] ?? null,
            'm1_evidencias' => $data['m1_evidencias'] ?? null,
            'm1_observaciones_adicionales' => $data['m1_observaciones_adicionales'] ?? null,
            'm3_retro_coformador_proceso' => $data['m3_retro_coformador_proceso'] ?? null,
            'm3_retro_coformador_desempeno' => $data['m3_retro_coformador_desempeno'] ?? null,
            'm3_retro_instructor_proceso' => $data['m3_retro_instructor_proceso'] ?? null,
            'm3_retro_instructor_desempeno' => $data['m3_retro_instructor_desempeno'] ?? null,
            'm3_retro_aprendiz_proceso' => $data['m3_retro_aprendiz_proceso'] ?? null,
            'm3_retro_aprendiz_desempeno' => $data['m3_retro_aprendiz_desempeno'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE momentos
                SET
                    fecha_visita=:fecha_visita,
                    modalidad=:modalidad,
                    obs_instructor=:obs_instructor,
                    obs_aprendiz=:obs_aprendiz,
                    obs_coformador=:obs_coformador,
                    juicio_final=:juicio_final,
                    fecha_inicio_etapa=:fecha_inicio_etapa,
                    fecha_fin_etapa=:fecha_fin_etapa,
                    fecha_arl=:fecha_arl,
                    numero_poliza_arl=:numero_poliza_arl,
                    horario=:horario,
                    enlace_grabacion=:enlace_grabacion,
                    ciudad_diligenciamiento=:ciudad_diligenciamiento,
                    fecha_diligenciamiento=:fecha_diligenciamiento,
                    modalidad_diligenciamiento=:modalidad_diligenciamiento,
                    numero_visitas_realizadas=:numero_visitas_realizadas,
                    m1_competencias=:m1_competencias,
                    m1_resultados=:m1_resultados,
                    m1_actividades=:m1_actividades,
                    m1_evidencias=:m1_evidencias,
                    m1_observaciones_adicionales=:m1_observaciones_adicionales,
                    m3_retro_coformador_proceso=:m3_retro_coformador_proceso,
                    m3_retro_coformador_desempeno=:m3_retro_coformador_desempeno,
                    m3_retro_instructor_proceso=:m3_retro_instructor_proceso,
                    m3_retro_instructor_desempeno=:m3_retro_instructor_desempeno,
                    m3_retro_aprendiz_proceso=:m3_retro_aprendiz_proceso,
                    m3_retro_aprendiz_desempeno=:m3_retro_aprendiz_desempeno,
                    updated_at=NOW()
                WHERE id=:id';
        Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'fecha_visita' => $data['fecha_visita'] ?? null,
            'modalidad' => $data['modalidad'] ?? null,
            'obs_instructor' => $data['obs_instructor'] ?? null,
            'obs_aprendiz' => $data['obs_aprendiz'] ?? null,
            'obs_coformador' => $data['obs_coformador'] ?? null,
            'juicio_final' => $data['juicio_final'] ?? null,
            'fecha_inicio_etapa' => $data['fecha_inicio_etapa'] ?? null,
            'fecha_fin_etapa' => $data['fecha_fin_etapa'] ?? null,
            'fecha_arl' => $data['fecha_arl'] ?? null,
            'numero_poliza_arl' => $data['numero_poliza_arl'] ?? null,
            'horario' => $data['horario'] ?? null,
            'enlace_grabacion' => $data['enlace_grabacion'] ?? null,
            'ciudad_diligenciamiento' => $data['ciudad_diligenciamiento'] ?? null,
            'fecha_diligenciamiento' => $data['fecha_diligenciamiento'] ?? null,
            'modalidad_diligenciamiento' => $data['modalidad_diligenciamiento'] ?? null,
            'numero_visitas_realizadas' => $data['numero_visitas_realizadas'] ?? null,
            'm1_competencias' => $data['m1_competencias'] ?? null,
            'm1_resultados' => $data['m1_resultados'] ?? null,
            'm1_actividades' => $data['m1_actividades'] ?? null,
            'm1_evidencias' => $data['m1_evidencias'] ?? null,
            'm1_observaciones_adicionales' => $data['m1_observaciones_adicionales'] ?? null,
            'm3_retro_coformador_proceso' => $data['m3_retro_coformador_proceso'] ?? null,
            'm3_retro_coformador_desempeno' => $data['m3_retro_coformador_desempeno'] ?? null,
            'm3_retro_instructor_proceso' => $data['m3_retro_instructor_proceso'] ?? null,
            'm3_retro_instructor_desempeno' => $data['m3_retro_instructor_desempeno'] ?? null,
            'm3_retro_aprendiz_proceso' => $data['m3_retro_aprendiz_proceso'] ?? null,
            'm3_retro_aprendiz_desempeno' => $data['m3_retro_aprendiz_desempeno'] ?? null,
        ]);
    }
}
