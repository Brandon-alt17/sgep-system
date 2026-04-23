<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Programa;

class CatalogoController
{
    public function programas(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'nivel' => trim((string) ($_GET['nivel'] ?? '')),
            'modalidad' => trim((string) ($_GET['modalidad'] ?? '')),
        ];

        view('catalogo/programas', [
            'programas' => Programa::catalogo($filters),
            'filters' => $filters,
        ]);
    }

    public function grupos(): void
    {
        view('catalogo/grupos');
    }

    public function empresas(): void
    {
        view('catalogo/empresas');
    }
}
