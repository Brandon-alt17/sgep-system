<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Models\Aprendiz;
use App\Models\Momento;
use App\Models\ProgramaContenido;

class MomentoController
{
    public function create(): void
    {
        $aprendizId = (int) ($_GET['aprendiz_id'] ?? 0);
        $tipo = (string) ($_GET['tipo'] ?? 'M1');
        $aprendiz = Aprendiz::findById($aprendizId);
        $programaContenido = [];
        if ($aprendiz && !empty($aprendiz['programa_id'])) {
            $programaContenido = ProgramaContenido::competenciasConResultados((int) $aprendiz['programa_id']);
        }
        view('momentos/create', ['aprendiz' => $aprendiz, 'tipo' => $tipo, 'programaContenido' => $programaContenido]);
    }

    public function store(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        $tipo = (string) ($_POST['tipo'] ?? '');
        if (in_array($tipo, ['M1', 'M2', 'M3'], true) && Momento::existsTipo($aprendizId, $tipo)) {
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId);
        }
        if ($tipo === 'M3' && !Momento::existsTipo($aprendizId, 'M2')) {
            redirect(APP_BASE_PATH . '/momentos/create?aprendiz_id=' . $aprendizId . '&tipo=M3');
        }
        if (in_array($tipo, ['M2', 'M3', 'EX'], true) && count((array) ($_POST['factores'] ?? [])) !== 13) {
            redirect(APP_BASE_PATH . '/momentos/create?aprendiz_id=' . $aprendizId . '&tipo=' . $tipo);
        }
        $limites = require base_path('config/f023_limites.php');
        $_POST['obs_instructor'] = mb_substr((string) ($_POST['obs_instructor'] ?? ''), 0, $limites['obs_instructor']);
        if ($tipo === 'EX') {
            $_POST['numero_visita'] = Momento::nextExtraNumero($aprendizId);
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $id = Momento::create($_POST);
            $factores = $_POST['factores'] ?? [];
            foreach ($factores as $f) {
                $pdo->prepare('INSERT INTO factores_valoracion (momento_id, tipo_factor, nombre_factor, valoracion, observacion) VALUES (:momento_id, :tipo_factor, :nombre_factor, :valoracion, :observacion)')
                    ->execute([
                        'momento_id' => $id,
                        'tipo_factor' => $f['tipo_factor'] ?? 'tecnico',
                        'nombre_factor' => $f['nombre_factor'] ?? '',
                        'valoracion' => $f['valoracion'] ?? 'PM',
                        'observacion' => $f['observacion'] ?? '',
                    ]);
            }
            if ($tipo === 'M3') {
                $estado = (($_POST['juicio_final'] ?? '') === 'Aprobado') ? 'Por certificar' : 'Pendiente por comité';
                Aprendiz::updateEstado($aprendizId, $estado, 'Cambio automático por M3');
            }
            $pdo->commit();
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        Momento::update($id, $_POST);
        redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId);
    }
}
