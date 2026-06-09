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
        if ($aprendizId <= 0) {
            abort_404('/momentos/create');
        }
        $tipo = $this->normalizeTipo((string) ($_GET['tipo'] ?? 'M1'));
        $aprendiz = Aprendiz::findById($aprendizId);
        if ($aprendiz === null) {
            abort_404('/momentos/create?aprendiz_id=' . $aprendizId);
        }
        $momentoExistente = $aprendiz ? Momento::findOneByAprendizTipo($aprendizId, $tipo) : null;
        $factoresExistentes = [];
        if ($momentoExistente !== null) {
            $factoresExistentes = Momento::factoresByMomento((int) ($momentoExistente['id'] ?? 0));
        }
        $programaContenido = [];
        if ($aprendiz && !empty($aprendiz['programa_id'])) {
            $programaContenido = ProgramaContenido::competenciasConResultados((int) $aprendiz['programa_id']);
        }

        $defaultMomento = $this->buildDefaultMomentoData($aprendiz, $tipo, $momentoExistente, $programaContenido);
        $limites = require base_path('config/f023_limites.php');
        $modoEdicion = $momentoExistente !== null;
        $accion = APP_BASE_PATH . ($modoEdicion ? '/momentos/update' : '/momentos/store');
        $valueFrom = static function (array $row, string $key, string $default = ''): string {
            return trim((string) ($row[$key] ?? $default));
        };
        $factorObsPorIndice = $this->factorObservacionesPorIndice($factoresExistentes);
        $factorValoracionPorIndice = $this->factorValoracionesPorIndice($factoresExistentes);

        view('momentos/create', [
            'aprendiz' => $aprendiz,
            'tipo' => $tipo,
            'programaContenido' => $programaContenido,
            'momento' => $defaultMomento,
            'momentoExistente' => $momentoExistente,
            'factoresExistentes' => $factoresExistentes,
            'accion' => $accion,
            'modoEdicion' => $modoEdicion,
            'valueFrom' => $valueFrom,
            'maxShortText' => (int) ($limites['short_text'] ?? 160),
            'maxUrl' => (int) ($limites['url'] ?? 500),
            'maxM1Competencias' => (int) ($limites['m1_competencias'] ?? 1200),
            'maxM1Resultados' => (int) ($limites['m1_resultados'] ?? 1200),
            'maxM1Actividades' => (int) ($limites['m1_actividades'] ?? 1200),
            'maxM1Evidencias' => (int) ($limites['m1_evidencias'] ?? 1200),
            'maxM1ObsAdicionales' => (int) ($limites['m1_observaciones_adicionales'] ?? 1200),
            'maxCompromisos' => (int) ($limites['compromisos'] ?? 450),
            'maxObsInstructor' => (int) ($limites['obs_instructor'] ?? 500),
            'maxObsAprendiz' => (int) ($limites['obs_aprendiz'] ?? 500),
            'maxObsCoformador' => (int) ($limites['obs_coformador'] ?? 500),
            'maxRetroM3' => (int) ($limites['retro_m3'] ?? 1200),
            'factorObsPorIndice' => $factorObsPorIndice,
            'factorValoracionPorIndice' => $factorValoracionPorIndice,
        ]);
    }

    public function store(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        if ($aprendizId <= 0 || Aprendiz::findById($aprendizId) === null) {
            abort_404('/momentos/store');
        }
        $tipo = $this->normalizeTipo((string) ($_POST['tipo'] ?? ''));
        $_POST['tipo'] = $tipo;
        $_POST = $this->normalizeMomentoDateFields($_POST);
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
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId . '&toast=momento_guardado');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            log_error('Momento store: ' . $e->getMessage());
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId);
        }
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        if ($id <= 0 || $aprendizId <= 0) {
            redirect(APP_BASE_PATH . '/aprendices');
        }
        $momentoRow = Momento::findById($id);
        if ($momentoRow === null || (int) ($momentoRow['aprendiz_id'] ?? 0) !== $aprendizId) {
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . max(0, $aprendizId));
        }
        $_POST = $this->normalizeMomentoDateFields($_POST);
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
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId . '&toast=momento_actualizado');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            log_error('Momento update: ' . $e->getMessage());
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId);
        }
    }

    /** @param array<string,mixed> $post @return array<string,mixed> */
    private function normalizeMomentoDateFields(array $post): array
    {
        foreach ([
            'fecha_visita',
            'fecha_inicio_etapa',
            'fecha_fin_etapa',
            'fecha_arl',
            'fecha_diligenciamiento',
            'proxima_visita',
        ] as $key) {
            if (!array_key_exists($key, $post)) {
                continue;
            }
            $post[$key] = date_post_to_iso($post[$key]);
        }

        return $post;
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
            'ciudad_diligenciamiento' => '',
            'fecha_diligenciamiento' => date('Y-m-d'),
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

    /** @param list<array<string,mixed>> $factoresExistentes @return array<int,string> índices 0–12 alineados con config/factores.php */
    private function factorObservacionesPorIndice(array $factoresExistentes): array
    {
        $cfg = require base_path('config/factores.php');
        $obs = array_fill(0, 13, '');
        $byNombre = [];
        foreach ($factoresExistentes as $row) {
            $nombre = trim((string) ($row['nombre_factor'] ?? ''));
            if ($nombre !== '') {
                $byNombre[$nombre] = (string) ($row['observacion'] ?? '');
            }
        }
        foreach ($cfg['tecnicos'] as $idx => $nombre) {
            $obs[$idx] = $byNombre[$nombre] ?? '';
        }
        foreach ($cfg['actitudinales'] as $offset => $nombre) {
            $i = $offset + 8;
            $obs[$i] = $byNombre[$nombre] ?? '';
        }

        return $obs;
    }

    /** @param list<array<string,mixed>> $factoresExistentes @return array<int,string> 'S' o 'PM' por índice */
    private function factorValoracionesPorIndice(array $factoresExistentes): array
    {
        $cfg = require base_path('config/factores.php');
        $val = array_fill(0, 13, 'PM');
        $byNombre = [];
        foreach ($factoresExistentes as $row) {
            $nombre = trim((string) ($row['nombre_factor'] ?? ''));
            if ($nombre !== '') {
                $v = trim((string) ($row['valoracion'] ?? ''));
                $byNombre[$nombre] = $v === 'S' ? 'S' : 'PM';
            }
        }
        foreach ($cfg['tecnicos'] as $idx => $nombre) {
            $val[$idx] = $byNombre[$nombre] ?? 'PM';
        }
        foreach ($cfg['actitudinales'] as $offset => $nombre) {
            $i = $offset + 8;
            $val[$i] = $byNombre[$nombre] ?? 'PM';
        }

        return $val;
    }
}
