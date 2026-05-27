<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ImportHistory;
use App\Helpers\Validator;
use App\Models\Aprendiz;
use App\Models\EmpresaJefe;
use App\Models\Momento;
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

        $importReturnUrl = $activeFilters['from'] === 'import' && $activeFilters['import_id'] !== ''
            ? import_result_url($activeFilters['import_id'])
            : '';

        $fromImportContext = $activeFilters['from'] === 'import' && $activeFilters['import_id'] !== '';
        $conflictImportId = $fromImportContext
            ? $activeFilters['import_id']
            : (string) ((ImportHistory::latestImport()['id'] ?? '') ?: '');
        $conflictsCount = ImportHistory::countConflictAprendices(
            $conflictImportId !== '' ? $conflictImportId : null
        );
        $conflictsManageUrl = import_conflicts_url($conflictImportId, $fromImportContext);

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
            'conflictsCount' => $conflictsCount,
            'conflictsManageUrl' => $conflictsManageUrl,
            'importReturnUrl' => $importReturnUrl,
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
        
        $momentosRows = Momento::findByAprendiz($id);
        $momentos = $this->buildMomentosCards($momentosRows);

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

    /**
     * Construye la lista de tarjetas «Documentos» garantizando que siempre
     * aparezcan M1, M2 y M3 (como «No iniciado» si no existen en BD) y
     * añadiendo al final cualquier momento extraordinario registrado.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array{tipo: string, label: string, fecha: string, estado: string}>
     */
    private function buildMomentosCards(array $rows): array
    {
        $realByTipo = [];
        $extras = [];
        foreach ($rows as $row) {
            $tipo = (string) ($row['tipo'] ?? '');
            if (in_array($tipo, ['M1', 'M2', 'M3'], true) && !isset($realByTipo[$tipo])) {
                $realByTipo[$tipo] = $row;
                continue;
            }
            if ($tipo === 'EX') {
                $extras[] = $row;
            }
        }

        $cards = [];
        foreach (['M1', 'M2', 'M3'] as $tipo) {
            $cards[] = isset($realByTipo[$tipo])
                ? Momento::toPerfilCard($realByTipo[$tipo])
                : Momento::placeholderPerfilCard($tipo);
        }
        foreach ($extras as $row) {
            $cards[] = Momento::toPerfilCard($row);
        }

        return $cards;
    }

    /** @return array{message: string, variant: string}|null */
    private function toastFromQuery(string $key): ?array
    {
        $map = [
            'info_f023_guardada' => ['message' => 'Información general F-023 guardada correctamente.', 'variant' => 'success'],
            'momento_guardado' => ['message' => 'Momento guardado correctamente.', 'variant' => 'success'],
            'momento_actualizado' => ['message' => 'Momento actualizado correctamente.', 'variant' => 'success'],
        ];

        return $map[$key] ?? null;
    }

    public function update(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        Aprendiz::update($id, $_POST);
        redirect(APP_BASE_PATH . '/aprendices/show?id=' . $id);
    }

    public function updateVisitas(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int) ($_POST['aprendiz_id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'error' => 'ID de aprendiz inválido'], JSON_UNESCAPED_UNICODE);

                return;
            }

            $success = Aprendiz::saveVisitas($id, $_POST);
            if ($success) {
                echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar la base de datos'], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /** @param array<string,mixed> $input */
    private function sanitizeAprendicesFilters(array $input): array
    {
        $q = trim((string) ($input['q'] ?? ''));
        $ficha = trim((string) ($input['ficha'] ?? ''));
        $estado = trim((string) ($input['estado'] ?? ''));
        $empresaId = (int) ($input['empresa_id'] ?? 0);
        $programaId = (int) ($input['programa_id'] ?? 0);
        $datosPendientes = ((string) ($input['datos_pendientes'] ?? '')) === '1';
        $from = trim((string) ($input['from'] ?? ''));
        $importId = trim((string) ($input['import_id'] ?? ''));
        $page = (int) ($input['page'] ?? 1);
        if (!in_array($from, ['grupos', 'empresas', 'import'], true)) {
            $from = '';
        }
        if ($from !== 'import') {
            $importId = '';
        } elseif ($importId === '') {
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
            'datos_pendientes' => $datosPendientes,
            'from' => $from,
            'import_id' => $importId,
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
            'datos_pendientes' => $filters['datos_pendientes'] ? '1' : '',
            'page' => $filters['page'] > 1 ? (string) $filters['page'] : '',
            'from' => $filters['from'],
            'import_id' => $filters['import_id'],
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