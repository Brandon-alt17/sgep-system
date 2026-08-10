<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exports\F023M3RetroSupport;
use App\Helpers\Database;
use App\Helpers\Normalizer;
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
        $momentoId = (int) ($_GET['momento_id'] ?? 0);
        if ($tipo === 'EX' && $momentoId > 0) {
            $momentoExistente = Momento::findById($momentoId);
            if ($momentoExistente === null || (int) ($momentoExistente['aprendiz_id'] ?? 0) !== $aprendizId) {
                abort_404('/momentos/create?aprendiz_id=' . $aprendizId . '&tipo=EX&momento_id=' . $momentoId);
            }
        } else {
            $momentoExistente = $tipo === 'EX' ? null : Momento::findOneByAprendizTipo($aprendizId, $tipo);
        }
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
            'recM1Competencias' => (int) ($limites['m1_competencias_recomendado'] ?? 0),
            'recM1Resultados' => (int) ($limites['m1_resultados_recomendado'] ?? 0),
            'recM1Actividades' => (int) ($limites['m1_actividades_recomendado'] ?? 0),
            'recM1Evidencias' => (int) ($limites['m1_evidencias_recomendado'] ?? 0),
            'recM1ObsAdicionales' => (int) ($limites['m1_observaciones_adicionales_recomendado'] ?? 0),
            'maxCompromisos' => (int) ($limites['compromisos'] ?? 450),
            'maxObsInstructor' => (int) ($limites['obs_instructor'] ?? 500),
            'maxObsAprendiz' => (int) ($limites['obs_aprendiz'] ?? 500),
            'maxObsCoformador' => (int) ($limites['obs_coformador'] ?? 500),
            'recObsInstructor' => (int) ($limites['obs_instructor_recomendado'] ?? 0),
            'recObsAprendiz' => (int) ($limites['obs_aprendiz_recomendado'] ?? 0),
            'recObsCoformador' => (int) ($limites['obs_coformador_recomendado'] ?? 0),
            'maxMotivoEx' => (int) ($limites['motivo_seguimiento_extraordinario'] ?? 500),
            'recMotivoEx' => (int) ($limites['motivo_seguimiento_extraordinario_recomendado'] ?? 0),
            'maxRetroM3' => (int) ($limites['retro_m3'] ?? 1200),
            'recRetroM3' => (int) ($limites['retro_m3_recomendado'] ?? 0),
            'factorObsPorIndice' => $factorObsPorIndice,
            'factorValoracionPorIndice' => $factorValoracionPorIndice,
            'error' => (string) ($_GET['error'] ?? ''),
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
            redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId . '&toast=momento_ya_existe');
        }
        if ($tipo === 'M3' && !Momento::existsTipo($aprendizId, 'M2')) {
            redirect(APP_BASE_PATH . '/momentos/create?aprendiz_id=' . $aprendizId . '&tipo=M3&error=momento_requiere_m2');
        }
        if (in_array($tipo, ['M2', 'M3', 'EX'], true) && count((array) ($_POST['factores'] ?? [])) !== 13) {
            redirect(APP_BASE_PATH . '/momentos/create?aprendiz_id=' . $aprendizId . '&tipo=' . $tipo . '&error=factores_incompletos');
        }
        if ($tipo === 'M3') {
            $_POST = F023M3RetroSupport::fillFromObservacionesIfEmpty($_POST);
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

            if (in_array($tipo, ['M1', 'M2', 'M3'], true)) {
                Aprendiz::markMomentoCompletado($aprendizId, $tipo);
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
        if ((string) ($momentoRow['tipo'] ?? '') === 'M3') {
            $_POST = F023M3RetroSupport::fillFromObservacionesIfEmpty($_POST);
        }
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

            $tipoActualizado = (string) ($momentoRow['tipo'] ?? '');
            if (in_array($tipoActualizado, ['M1', 'M2', 'M3'], true)) {
                Aprendiz::markMomentoCompletado($aprendizId, $tipoActualizado);
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
            'fecha_seguimiento_anterior',
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
            return $this->normalizeMomentoM1TextFields($momentoExistente);
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
            'fecha_seguimiento_anterior' => null,
            'motivo_seguimiento_extraordinario' => '',
            'modalidad' => (string) ($aprendiz['modalidad'] ?? 'Presencial'),
            'proxima_visita' => $aprendiz['proxima_visita'] ?? null,
            'ciudad_diligenciamiento' => '',
            'fecha_diligenciamiento' => date('Y-m-d'),
            'modalidad_diligenciamiento' => '',
            'numero_visitas_realizadas' => null,
            'm1_competencias' => Normalizer::normalizeCommaListSentenceCase($primerCompetencia),
            'm1_resultados' => Normalizer::normalizeCommaListSentenceCase($primerResultado),
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

        // Campos cortos (no pasan por F023DynamicFontScale): siguen con tope duro real, es
        // validación razonable de longitud, no una defensa contra desborde de página.
        $shortFieldLimits = [
            'numero_poliza_arl' => $limitShortText,
            'horario' => $limitShortText,
            'enlace_grabacion' => $limitUrl,
            'ciudad_diligenciamiento' => $limitShortText,
        ];
        foreach ($shortFieldLimits as $field => $limit) {
            $value = trim((string) ($data[$field] ?? ''));
            $data[$field] = $value === '' ? '' : mb_substr($value, 0, $limit);
        }

        // Campos largos: los límites de config/f023_limites.php son solo indicativos (contador
        // del formulario + punto donde F023DynamicFontScale reduce la letra al generar el
        // documento) — ya no truncan el texto ingresado por el usuario.
        $longTextFields = [
            'motivo_seguimiento_extraordinario',
            'obs_instructor',
            'obs_aprendiz',
            'obs_coformador',
            'm1_competencias',
            'm1_resultados',
            'm1_actividades',
            'm1_evidencias',
            'm1_observaciones_adicionales',
            'm3_retro_coformador_proceso',
            'm3_retro_coformador_desempeno',
            'm3_retro_instructor_proceso',
            'm3_retro_instructor_desempeno',
            'm3_retro_aprendiz_proceso',
            'm3_retro_aprendiz_desempeno',
        ];
        foreach ($longTextFields as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value !== '' && in_array($field, ['m1_competencias', 'm1_resultados'], true)) {
                $value = Normalizer::normalizeCommaListSentenceCase($value);
            }
            $data[$field] = $value;
        }

        foreach ((array) ($data['factores'] ?? []) as $idx => $factor) {
            if (!is_array($factor)) {
                continue;
            }
            $data['factores'][$idx]['observacion'] = trim((string) ($factor['observacion'] ?? ''));
        }

        return $data;
    }

    /** @param array<string,mixed> $data */
    private function normalizeMomentoM1TextFields(array $data): array
    {
        foreach (['m1_competencias', 'm1_resultados'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $data[$field] = Normalizer::normalizeCommaListSentenceCase(trim((string) ($data[$field] ?? '')));
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
