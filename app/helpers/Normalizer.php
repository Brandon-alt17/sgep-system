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
        foreach (['correo_personal', 'correo_institucional', 'correo_electronico_personal', 'correo_electronico_institucional'] as $emailKey) {
            if (!empty($row[$emailKey])) {
                $row[$emailKey] = strtolower((string) $row[$emailKey]);
            }
        }
        if (!empty($row['nit_empresa'])) {
            $row['nit_empresa'] = preg_replace('/\.\d+$/', '', (string) $row['nit_empresa']);
        }
        return $row;
    }
}
