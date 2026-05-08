<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Models\Aprendiz;
use App\Models\Momento;
use App\Models\ProgramaContenido;

class MomentoController
{
    private const TIPOS_VALIDOS = ['M1', 'M2', 'M3', 'EX'];

    public function create(): void
    {
        $aprendizId = (int) ($_GET['aprendiz_id'] ?? 0);
        $tipo = $this->normalizeTipo((string) ($_GET['tipo'] ?? 'M1'));
        $aprendiz = Aprendiz::findById($aprendizId);
        $momentoExistente = $aprendiz ? Momento::findOneByAprendizTipo($aprendizId, $tipo) : null;
        $factoresExistentes = [];
        if ($momentoExistente !== null) {
            $factoresExistentes = Momento::factoresByMomento((int) ($momentoExistente['id'] ?? 0));
        }
        $programaContenido = [];
        if ($aprendiz && !empty($aprendiz['programa_id'])) {
            $programaContenido = ProgramaContenido::competenciasConResultados((int) $aprendiz['programa_id']);
        }

        $defaultMomento = $this->buildDefaultMomentoData($aprendiz ?? [], $tipo, $momentoExistente, $programaContenido);
        view('momentos/create', [
            'aprendiz' => $aprendiz,
            'tipo' => $tipo,
            'programaContenido' => $programaContenido,
            'momento' => $defaultMomento,
            'momentoExistente' => $momentoExistente,
            'factoresExistentes' => $factoresExistentes,
        ]);
    }

    public function store(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        $tipo = $this->normalizeTipo((string) ($_POST['tipo'] ?? ''));
        $_POST['tipo'] = $tipo;
        if (in_array($tipo, ['M1', 'M2', 'M3'], true) && Momento::existsTipo($aprendizId, $tipo)) {
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId);
        }
        if ($tipo === 'M3' && !Momento::existsTipo($aprendizId, 'M2')) {
            redirect(APP_BASE_PATH . '/momentos/create?aprendiz_id=' . $aprendizId . '&tipo=M3');
        }
        $limites = require base_path('config/f023_limites.php');
        $_POST = $this->applyTextLimits($_POST, $limites);
        if ($tipo === 'EX') {
            $_POST['numero_visita'] = Momento::nextExtraNumero($aprendizId);
        }

        $fechaVisita = trim((string) ($_POST['fecha_visita'] ?? ''));
        if ($fechaVisita === '') {
            $fechaVisita = trim((string) ($_POST['fecha_fin_etapa'] ?? ''));
        }
        if ($fechaVisita === '') {
            $fechaVisita = trim((string) ($_POST['fecha_inicio_etapa'] ?? ''));
        }
        if ($fechaVisita === '') {
            $fechaVisita = date('Y-m-d');
        }
        $_POST['fecha_visita'] = $fechaVisita;

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $id = Momento::create($_POST);
            $factores = $_POST['factores'] ?? [];
            foreach ((array) $factores as $f) {
                if (!is_array($f)) {
                    continue;
                }
                $nombreFactor = trim((string) ($f['nombre_factor'] ?? ''));
                $tipoFactor = trim((string) ($f['tipo_factor'] ?? ''));
                if ($nombreFactor === '' || $tipoFactor === '') {
                    continue;
                }
                $pdo->prepare('INSERT INTO factores_valoracion (momento_id, tipo_factor, nombre_factor, valoracion, observacion) VALUES (:momento_id, :tipo_factor, :nombre_factor, :valoracion, :observacion)')
                    ->execute([
                        'momento_id' => $id,
                        'tipo_factor' => $tipoFactor,
                        'nombre_factor' => $nombreFactor,
                        'valoracion' => ($f['valoracion'] ?? '') === 'S' ? 'S' : 'PM',
                        'observacion' => $f['observacion'] ?? '',
                    ]);
            }

            $proximaVisita = trim((string) ($_POST['proxima_visita'] ?? ''));
            if ($proximaVisita !== '') {
                $pdo->prepare('UPDATE aprendices SET proxima_visita = :proxima_visita, updated_at = NOW() WHERE id = :id')
                    ->execute([
                        'proxima_visita' => $proximaVisita,
                        'id' => $aprendizId,
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
        $limites = require base_path('config/f023_limites.php');
        $_POST = $this->applyTextLimits($_POST, $limites);
        $_POST['tipo'] = $this->normalizeTipo((string) ($_POST['tipo'] ?? ''));
        $fechaVisita = trim((string) ($_POST['fecha_visita'] ?? ''));
        if ($fechaVisita === '') {
            $fechaVisita = trim((string) ($_POST['fecha_fin_etapa'] ?? ''));
        }
        if ($fechaVisita === '') {
            $fechaVisita = trim((string) ($_POST['fecha_inicio_etapa'] ?? ''));
        }
        if ($fechaVisita === '') {
            $fechaVisita = date('Y-m-d');
        }
        $_POST['fecha_visita'] = $fechaVisita;
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            Momento::update($id, $_POST);
            $pdo->prepare('DELETE FROM factores_valoracion WHERE momento_id = :momento_id')
                ->execute(['momento_id' => $id]);
            foreach ((array) ($_POST['factores'] ?? []) as $f) {
                if (!is_array($f)) {
                    continue;
                }
                $nombreFactor = trim((string) ($f['nombre_factor'] ?? ''));
                $tipoFactor = trim((string) ($f['tipo_factor'] ?? ''));
                if ($nombreFactor === '' || $tipoFactor === '') {
                    continue;
                }
                $pdo->prepare('INSERT INTO factores_valoracion (momento_id, tipo_factor, nombre_factor, valoracion, observacion) VALUES (:momento_id, :tipo_factor, :nombre_factor, :valoracion, :observacion)')
                    ->execute([
                        'momento_id' => $id,
                        'tipo_factor' => $tipoFactor,
                        'nombre_factor' => $nombreFactor,
                        'valoracion' => ($f['valoracion'] ?? '') === 'S' ? 'S' : 'PM',
                        'observacion' => $f['observacion'] ?? '',
                    ]);
            }

            $proximaVisita = trim((string) ($_POST['proxima_visita'] ?? ''));
            if ($proximaVisita !== '') {
                $pdo->prepare('UPDATE aprendices SET proxima_visita = :proxima_visita, updated_at = NOW() WHERE id = :id')
                    ->execute([
                        'proxima_visita' => $proximaVisita,
                        'id' => $aprendizId,
                    ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId);
    }

    private function normalizeTipo(string $tipo): string
    {
        $normalized = strtoupper(trim($tipo));
        if (!in_array($normalized, self::TIPOS_VALIDOS, true)) {
            return 'M1';
        }
        return $normalized;
    }

    private function buildDefaultMomentoData(array $aprendiz, string $tipo, ?array $momentoExistente, array $programaContenido): array
    {
        if ($momentoExistente !== null) {
            return $momentoExistente;
        }

        $primerResultado = '';
        $primerCompetencia = '';
        if ($tipo === 'M1' && $programaContenido !== []) {
            $primerCompetencia = trim((string) ($programaContenido[0]['nombre'] ?? ''));
            $primerResultado = trim((string) ($programaContenido[0]['resultados'][0]['descripcion'] ?? ''));
        }

        return [
            'fecha_inicio_etapa' => null,
            'fecha_fin_etapa' => null,
            'fecha_arl' => $aprendiz['arl_fecha_afiliacion'] ?? null,
            'numero_poliza_arl' => (string) ($aprendiz['arl_nombre'] ?? ''),
            'horario' => '',
            'enlace_grabacion' => '',
            'fecha_visita' => null,
            'modalidad' => (string) ($aprendiz['modalidad'] ?? 'Presencial'),
            'proxima_visita' => $aprendiz['proxima_visita'] ?? null,
            'ciudad_diligenciamiento' => (string) ($aprendiz['direccion'] ?? ''),
            'fecha_diligenciamiento' => null,
            'modalidad_diligenciamiento' => '',
            'numero_visitas_realizadas' => null,
            'm1_competencias' => $primerCompetencia,
            'm1_resultados' => $primerResultado,
            'm1_actividades' => '',
            'm1_evidencias' => '',
            'm1_observaciones_adicionales' => '',
            'obs_instructor' => '',
            'obs_aprendiz' => '',
            'obs_coformador' => '',
            'juicio_final' => 'Aprobado',
            'm3_retro_coformador_proceso' => '',
            'm3_retro_coformador_desempeno' => '',
            'm3_retro_instructor_proceso' => '',
            'm3_retro_instructor_desempeno' => '',
            'm3_retro_aprendiz_proceso' => '',
            'm3_retro_aprendiz_desempeno' => '',
        ];
    }

    private function applyTextLimits(array $data, array $limites): array
    {
        $limitShortText = (int) ($limites['short_text'] ?? 160);
        $limitUrl = (int) ($limites['url'] ?? 500);

        $fieldLimits = [
            'numero_poliza_arl' => $limitShortText,
            'horario' => $limitShortText,
            'enlace_grabacion' => $limitUrl,
            'ciudad_diligenciamiento' => $limitShortText,
            'obs_instructor' => (int) ($limites['obs_instructor'] ?? 500),
            'obs_aprendiz' => (int) ($limites['obs_aprendiz'] ?? 500),
            'obs_coformador' => (int) ($limites['obs_coformador'] ?? 500),
            'm1_competencias' => (int) ($limites['m1_competencias'] ?? 1200),
            'm1_resultados' => (int) ($limites['m1_resultados'] ?? 1200),
            'm1_actividades' => (int) ($limites['m1_actividades'] ?? 1200),
            'm1_evidencias' => (int) ($limites['m1_evidencias'] ?? 1200),
            'm1_observaciones_adicionales' => (int) ($limites['m1_observaciones_adicionales'] ?? 1200),
            'm3_retro_coformador_proceso' => (int) ($limites['retro_m3'] ?? 1200),
            'm3_retro_coformador_desempeno' => (int) ($limites['retro_m3'] ?? 1200),
            'm3_retro_instructor_proceso' => (int) ($limites['retro_m3'] ?? 1200),
            'm3_retro_instructor_desempeno' => (int) ($limites['retro_m3'] ?? 1200),
            'm3_retro_aprendiz_proceso' => (int) ($limites['retro_m3'] ?? 1200),
            'm3_retro_aprendiz_desempeno' => (int) ($limites['retro_m3'] ?? 1200),
        ];

        foreach ($fieldLimits as $field => $limit) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value === '') {
                $data[$field] = '';
                continue;
            }
            $data[$field] = mb_substr($value, 0, $limit);
        }

        foreach ((array) ($data['factores'] ?? []) as $idx => $factor) {
            if (!is_array($factor)) {
                continue;
            }
            $obs = trim((string) ($factor['observacion'] ?? ''));
            $data['factores'][$idx]['observacion'] = $obs === ''
                ? ''
                : mb_substr($obs, 0, (int) ($limites['compromisos'] ?? 450));
        }

        return $data;
    }
}
