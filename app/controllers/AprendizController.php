<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Models\Aprendiz;
use App\Models\Programa;

class AprendizController
{
    public function index(): void
    {
        $aprendices = Aprendiz::paginated($_GET);
        view('aprendices/index', ['aprendices' => $aprendices]);
    }

    public function create(): void
    {
        view('aprendices/create', ['programas' => Programa::all()]);
    }

    public function store(): void
    {
        $errors = Validator::required($_POST, ['nombre_completo', 'tipo_documento', 'numero_documento']);
        if ($errors !== []) {
            view('aprendices/create', ['errors' => $errors, 'programas' => Programa::all()]);
            return;
        }
        Aprendiz::create($_POST);
        redirect(APP_BASE_PATH . '/aprendices');
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $aprendiz = Aprendiz::findById($id);
        if ($aprendiz === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/aprendices/show?id=' . $id]);
            return;
        }
        view('aprendices/show', ['aprendiz' => $aprendiz]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        Aprendiz::update($id, $_POST);
        redirect(APP_BASE_PATH . '/aprendices/show?id=' . $id);
    }
}
