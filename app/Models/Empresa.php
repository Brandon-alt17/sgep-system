<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class Empresa
{
    public static function findByAprendiz(int $aprendizId): ?array
    {
        $sql = 'SELECT e.* FROM empresas e INNER JOIN aprendices a ON a.empresa_id = e.id WHERE a.id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $aprendizId]);
        return $stmt->fetch() ?: null;
    }
}
