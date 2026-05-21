<?php

declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewPath = base_path('app/views/' . $view . '.php');
    $layoutPath = base_path('app/views/layouts/app.php');
    if (!file_exists($viewPath)) {
        http_response_code(500);
        echo 'Vista no encontrada: ' . htmlspecialchars($view);
        return;
    }
    require $layoutPath;
}

function partial(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $path = base_path('app/views/' . $view . '.php');
    if (file_exists($path)) {
        require $path;
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Convierte YYYY-MM-DD (desde BD) a dd/mm/aaaa para mostrar en inputs de texto. */
function date_iso_to_dmY(?string $iso): string
{
    $iso = trim((string) $iso);
    if ($iso === '' || preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $iso, $m) !== 1) {
        return '';
    }

    return $m[3] . '/' . $m[2] . '/' . $m[1];
}

/**
 * Normaliza fecha enviada por formulario: dd/mm/aaaa o yyyy-mm-dd → YYYY-MM-DD.
 * Cadena vacía o inválida → ''.
 */
function date_post_to_iso(mixed $value): string
{
    $s = trim((string) $value);
    if ($s === '') {
        return '';
    }
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $s, $m) === 1) {
        $d = (int) $m[1];
        $month = (int) $m[2];
        $y = (int) $m[3];
        if (!checkdate($month, $d, $y)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $y, $month, $d);
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m) === 1) {
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return '';
        }

        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }

    return '';
}

/**
 * Quita espacio en blanco al inicio de cada línea y recorta el texto completo.
 * Evita direcciones u observaciones con sangría fantasma (import / copiar-pegar).
 */
function normalize_multiline_text(string $value): string
{
    $value = preg_replace('/^[ \h]+/m', '', $value) ?? '';

    return trim($value);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function log_error(string $message): void
{
    $dir = base_path('storage/logs');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, 3, $dir . '/error.log');
}

function import_result_url(string $importId): string
{
    $importId = trim($importId);
    if ($importId === '') {
        return '';
    }

    return rtrim((string) APP_BASE_PATH, '/') . '/importar/resultado?id=' . rawurlencode($importId);
}

/**
 * @return array{fromImport: bool, importId: string, returnUrl: string}
 */
function import_nav_context(): array
{
    $from = trim((string) ($_GET['from'] ?? ''));
    $importId = trim((string) ($_GET['import_id'] ?? ''));
    if ($importId === '' && $from === 'import') {
        $importId = trim((string) ($_GET['id'] ?? ''));
    }
    if ($from !== 'import' || $importId === '') {
        return ['fromImport' => false, 'importId' => '', 'returnUrl' => ''];
    }

    return [
        'fromImport' => true,
        'importId' => $importId,
        'returnUrl' => import_result_url($importId),
    ];
}

function import_conflicts_url(string $importId, bool $fromImport = false): string
{
    $importId = trim($importId);
    if ($importId === '') {
        return '';
    }

    $url = rtrim((string) APP_BASE_PATH, '/') . '/importar/conflictos?id=' . rawurlencode($importId);
    if ($fromImport) {
        $url .= '&from=import';
    }

    return $url;
}

function import_nav_query_suffix(string $importId): string
{
    $importId = trim($importId);
    if ($importId === '') {
        return '';
    }

    return '?from=import&import_id=' . rawurlencode($importId);
}
