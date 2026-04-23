<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Programa;

class CatalogoController
{
    public function programas(): void
    {
        view('catalogo/programas', [
            'programas' => Programa::catalogo(),
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
