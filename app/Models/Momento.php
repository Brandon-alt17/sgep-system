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

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO momentos (aprendiz_id, tipo, numero_visita, fecha_visita, modalidad, proxima_visita, obs_instructor, obs_aprendiz, obs_coformador, juicio_final, created_at, updated_at)
                VALUES (:aprendiz_id, :tipo, :numero_visita, :fecha_visita, :modalidad, :proxima_visita, :obs_instructor, :obs_aprendiz, :obs_coformador, :juicio_final, NOW(), NOW())';
        $pdo = Database::connection();
        $pdo->prepare($sql)->execute([
            'aprendiz_id' => $data['aprendiz_id'],
            'tipo' => $data['tipo'],
            'numero_visita' => $data['numero_visita'] ?? null,
            'fecha_visita' => $data['fecha_visita'],
            'modalidad' => $data['modalidad'] ?? null,
            'proxima_visita' => $data['proxima_visita'] ?? null,
            'obs_instructor' => $data['obs_instructor'] ?? null,
            'obs_aprendiz' => $data['obs_aprendiz'] ?? null,
            'obs_coformador' => $data['obs_coformador'] ?? null,
            'juicio_final' => $data['juicio_final'] ?? null,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sql = 'UPDATE momentos SET fecha_visita=:fecha_visita, modalidad=:modalidad, proxima_visita=:proxima_visita, obs_instructor=:obs_instructor, obs_aprendiz=:obs_aprendiz, obs_coformador=:obs_coformador, juicio_final=:juicio_final, updated_at=NOW() WHERE id=:id';
        Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'fecha_visita' => $data['fecha_visita'],
            'modalidad' => $data['modalidad'] ?? null,
            'proxima_visita' => $data['proxima_visita'] ?? null,
            'obs_instructor' => $data['obs_instructor'] ?? null,
            'obs_aprendiz' => $data['obs_aprendiz'] ?? null,
            'obs_coformador' => $data['obs_coformador'] ?? null,
            'juicio_final' => $data['juicio_final'] ?? null,
        ]);
    }
}
