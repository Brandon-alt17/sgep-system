<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Imports\AprendicesImport;

class ImportacionController
{
    public function upload(): void
    {
        view('import/upload');
    }

    public function process(): void
    {
        if (empty($_FILES['archivo']['tmp_name'])) {
            view('import/preview', ['resultado' => ['errors' => ['Debe seleccionar un archivo.']]]);
            return;
        }
        $resultado = (new AprendicesImport())->import($_FILES['archivo']['tmp_name']);
        view('import/preview', ['resultado' => $resultado]);
    }
}
