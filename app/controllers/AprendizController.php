<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Helpers\Validator;
use App\Models\Aprendiz;
use App\Models\Programa;
use App\Models\Empresa;

class AprendizController
{
    public function index(): void
    {
        $activeFilters = $this->sanitizeAprendicesFilters($_GET);
        $aprendices = Aprendiz::paginated($activeFilters, 500, 0);

        if ($this->isAjaxFilterRequest()) {
            partial('components/ui');
            ob_start();
            partial('aprendices/_rows', [
                'aprendices' => $aprendices,
                'activeFilters' => $activeFilters,
            ]);
            $rowsHtml = (string) ob_get_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'rowsHtml' => $rowsHtml,
                'total' => count($aprendices),
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        view('aprendices/index', [
            'aprendices' => $aprendices,
            'fichasOptions' => Aprendiz::getUniqueFichas(),
            'estadosOptions' => Aprendiz::getUniqueEstados(),
            'programasOptions' => Programa::all(),
            'empresasOptions' => \App\Models\Empresa::catalogo(),
            'activeFilters' => $activeFilters,
            'initialFicha' => $activeFilters['ficha'],
            'initialEstado' => $activeFilters['estado'],
            'initialQ' => $activeFilters['q'],
        ]);
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
        $momentos = [
            ['id' => 'm1', 'label' => 'Momento 1 — Planeación', 'estado' => 'Completado', 'fecha' => '15/03/2025'],
            ['id' => 'm2', 'label' => 'Momento 2 — Seguimiento', 'estado' => 'Incompleto', 'fecha' => '10/04/2025'],
            ['id' => 'm3', 'label' => 'Momento 3 — Evaluación final', 'estado' => 'No iniciado']
        ];
        $jefes = [];
        view('aprendices/show', [
            'aprendiz' => $aprendiz,
            'jefes' => $jefes,
            'momentos' => $momentos,
            'backToListUrl' => $this->aprendicesBackUrl($_GET),
        ]);
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        // Actualizar aprendiz
        Aprendiz::update($id, $_POST);

        // Actualizar empresa
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);

        if ($empresaId > 0) {
            Empresa::update($empresaId, [
                'nombre' => $_POST['empresa_nombre'] ?? null,
                'nit' => $_POST['nit'] ?? null,
                'direccion' => $_POST['direccion'] ?? null,
                'ciudad' => $_POST['ciudad'] ?? null,
                'correo_org' => $_POST['correo_org'] ?? null,
                'nombre_contacto2' => $_POST['nombre_contacto2'] ?? null,
                'correo_contacto2' => $_POST['correo_contacto2'] ?? null,
                'direccion_practica' => $_POST['direccion_practica'] ?? null,
                'nombre_jefe' => $_POST['nombre_jefe'] ?? null,
                'cargo_jefe' => $_POST['cargo_jefe'] ?? null,
                'telefono_jefe' => $_POST['telefono_jefe'] ?? null,
                'correo_jefe' => $_POST['correo_jefe'] ?? null,
            ]);
        }

        redirect(APP_BASE_PATH . '/aprendices/show?id=' . $id);
    }

    /**
     * Método actualizado para usar el modelo Aprendiz::saveVisitas
     */
    public function updateVisitas(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int) ($_POST['aprendiz_id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'error' => 'ID de aprendiz inválido']);
                return;
            }

            // Llamamos al modelo que ya contiene la lógica de validación y cálculo de fecha
            $success = Aprendiz::saveVisitas($id, $_POST);
            
            if ($success) {
                echo json_encode(['ok' => true]);
            } else {
                echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar la base de datos']);
            }
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    private function sanitizeAprendicesFilters(array $input): array
    {
        $q = trim((string) ($input['q'] ?? ''));
        $ficha = trim((string) ($input['ficha'] ?? ''));
        $estado = trim((string) ($input['estado'] ?? ''));
        $empresaId = (int) ($input['empresa_id'] ?? 0);
        $programaId = (int) ($input['programa_id'] ?? 0);
        $from = trim((string) ($input['from'] ?? ''));
        if (!in_array($from, ['grupos', 'empresas'], true)) {
            $from = '';
        }
        return [
            'q' => $q,
            'ficha' => $ficha,
            'estado' => $estado,
            'empresa_id' => $empresaId > 0 ? $empresaId : 0,
            'programa_id' => $programaId > 0 ? $programaId : 0,
            'from' => $from,
        ];
    }

    private function aprendicesBackUrl(array $input): string
    {
        $filters = $this->sanitizeAprendicesFilters($input);
        $query = array_filter([
            'q' => $filters['q'],
            'ficha' => $filters['ficha'],
            'estado' => $filters['estado'],
            'empresa_id' => $filters['empresa_id'] > 0 ? (string) $filters['empresa_id'] : '',
            'programa_id' => $filters['programa_id'] > 0 ? (string) $filters['programa_id'] : '',
            'from' => $filters['from'],
        ], static fn ($v): bool => (string) $v !== '');

        $base = APP_BASE_PATH . '/aprendices';
        return $query === [] ? $base : $base . '?' . http_build_query($query);
    }

    private function isAjaxFilterRequest(): bool
    {
        $isXmlHttpRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $isAjaxQueryFlag = ((string) ($_GET['ajax'] ?? '')) === '1';
        return $isAjaxQueryFlag || $isXmlHttpRequest;
    }
}