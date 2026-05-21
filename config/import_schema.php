<?php

declare(strict_types=1);

/**
 * Esquema esperado del Excel de seguimiento (exportación Google Forms).
 * Debe alinearse con config/import_mapping.php y public/templates/plantilla_seguimiento.xlsx.
 *
 * @return array{
 *   required_columns: int,
 *   max_bytes: int,
 *   extensions: list<string>,
 *   critical_headers: array<int, list<string>>,
 * }
 */
return [
    'required_columns' => 30,
    'max_bytes' => 5 * 1024 * 1024,
    'extensions' => ['xlsx', 'xls', 'csv'],
    'critical_headers' => [
        0 => ['marca temporal', 'marca de tiempo', 'fecha hora'],
        1 => ['documento de identidad', 'documento identidad'],
        3 => ['programa de formacion', 'programa formacion'],
        6 => ['nombre completo'],
        13 => ['empresa', 'coformadora', 'entidad coformadora'],
    ],
];
