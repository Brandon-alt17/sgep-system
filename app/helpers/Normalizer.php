<?php

declare(strict_types=1);

namespace App\Helpers;

class Normalizer
{
    public static function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            $row[$key] = is_string($value) ? trim($value) : $value;
        }
        if (!empty($row['nombre_completo'])) {
            $row['nombre_completo'] = ucwords(strtolower((string) $row['nombre_completo']));
        }
        if (!empty($row['correo_personal'])) {
            $row['correo_personal'] = strtolower((string) $row['correo_personal']);
        }
        if (!empty($row['correo_institucional'])) {
            $row['correo_institucional'] = strtolower((string) $row['correo_institucional']);
        }
        if (!empty($row['nit_empresa'])) {
            $row['nit_empresa'] = preg_replace('/\.\d+$/', '', (string) $row['nit_empresa']);
        }
        return $row;
    }
}
