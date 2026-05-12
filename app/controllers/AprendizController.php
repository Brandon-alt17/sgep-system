<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Validator;
use App\Models\Aprendiz;
use App\Models\EmpresaJefe;
use App\Models\Programa;

class AprendizController
{
    public function index(): void
    {
        $activeFilters = $this->sanitizeAprendicesFilters($_GET);
        $perPage = 10;
        $currentPage = (int) ($_GET['page'] ?? 1);
        if ($currentPage < 1) {
            $currentPage = 1;
        }
        $totalItems = Aprendiz::countFiltered($activeFilters);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        $activeFilters['page'] = $currentPage;
        $offset = ($currentPage - 1) * $perPage;
        $aprendices = Aprendiz::paginated($activeFilters, $perPage, $offset);

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
                'total' => $totalItems,
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
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'totalItems' => $totalItems,
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
            [
                'id' => 'm1',
                'label' => 'Momento 1 — Planeación',
                'estado' => 'Completado',
                'fecha' => '15/03/2025'
            ],
            [
                'id' => 'm2',
                'label' => 'Momento 2 — Seguimiento',
                'estado' => 'Incompleto',
                'fecha' => '10/04/2025'
            ],
            [
                'id' => 'm3',
                'label' => 'Momento 3 — Evaluación final',
                'estado' => 'No iniciado'
            ]
        ];

        $empresaId = (int) ($aprendiz['empresa_id'] ?? 0);
        $jefes = $empresaId > 0 ? EmpresaJefe::listByEmpresa($empresaId) : [];

        view('aprendices/show', [
            'aprendiz' => $aprendiz,
            'jefes' => $jefes,
            'momentos' => $momentos,
            'backToListUrl' => $this->aprendicesBackUrl($_GET),
            'pageToast' => $this->toastFromQuery((string) ($_GET['toast'] ?? '')),
        ]);
    }

    /** @return array{message: string, variant: string}|null */
    private function toastFromQuery(string $key): ?array
    {
        $map = [
            'info_f023_guardada' => ['message' => 'Información general F-023 guardada correctamente.', 'variant' => 'success'],
        ];

        return $map[$key] ?? null;
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        Aprendiz::update($id, $_POST);
        redirect(APP_BASE_PATH . '/aprendices/show?id=' . $id);
    }

    /** @param array<string,mixed> $input */
    private function sanitizeAprendicesFilters(array $input): array
    {
        $q = trim((string) ($input['q'] ?? ''));
        $ficha = trim((string) ($input['ficha'] ?? ''));
        $estado = trim((string) ($input['estado'] ?? ''));
        $empresaId = (int) ($input['empresa_id'] ?? 0);
        $programaId = (int) ($input['programa_id'] ?? 0);
        $from = trim((string) ($input['from'] ?? ''));
        $page = (int) ($input['page'] ?? 1);
        if (!in_array($from, ['grupos', 'empresas'], true)) {
            $from = '';
        }
        if ($page < 1) {
            $page = 1;
        }

        return [
            'q' => $q,
            'ficha' => $ficha,
            'estado' => $estado,
            'empresa_id' => $empresaId > 0 ? $empresaId : 0,
            'programa_id' => $programaId > 0 ? $programaId : 0,
            'from' => $from,
            'page' => $page,
        ];
    }

    /** @param array<string,mixed> $input */
    private function aprendicesBackUrl(array $input): string
    {
        $filters = $this->sanitizeAprendicesFilters($input);
        $query = array_filter([
            'q' => $filters['q'],
            'ficha' => $filters['ficha'],
            'estado' => $filters['estado'],
            'empresa_id' => $filters['empresa_id'] > 0 ? (string) $filters['empresa_id'] : '',
            'programa_id' => $filters['programa_id'] > 0 ? (string) $filters['programa_id'] : '',
            'page' => $filters['page'] > 1 ? (string) $filters['page'] : '',
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