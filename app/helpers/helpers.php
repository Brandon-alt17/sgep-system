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

/** Asegura texto UTF-8 válido (p. ej. nombres desde MySQL). */
function export_ensure_utf8(string $value): string
{
    if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
        return $value;
    }
    $converted = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');

    return is_string($converted) ? $converted : $value;
}

/** Quita caracteres no válidos en nombres de archivo (Windows / descargas HTTP). Conserva tildes. */
function export_filename_safe(string $value): string
{
    $value = export_ensure_utf8(trim($value));
    if ($value === '') {
        return '';
    }
    $value = preg_replace('/[\\\\\/:*?"<>|]/u', '', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return trim($value);
}

function f023_export_basename(string $nombreCompleto, string $numeroGrupo, string $extension): string
{
    $extension = strtolower(ltrim(trim($extension), '.'));
    $nombre = export_filename_safe($nombreCompleto);
    $grupo = export_filename_safe($numeroGrupo);
    if ($nombre === '') {
        $nombre = 'Aprendiz';
    }
    if ($grupo === '') {
        $grupo = 'sin_grupo';
    }
    $base = $nombre . ' ' . $grupo;
    if (mb_strlen($base) > 200) {
        $base = mb_substr($base, 0, 200);
    }
    $base = rtrim($base, '. ');

    return $base . '.' . $extension;
}

/** Cabecera Content-Disposition con soporte UTF-8 (tildes y eñes en la descarga). */
function content_disposition_attachment(string $filename): string
{
    $filename = trim($filename);
    $asciiFallback = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?? $filename;
    $asciiFallback = str_replace(['"', '\\'], '_', $asciiFallback);
    if ($asciiFallback === '') {
        $asciiFallback = 'descarga';
    }

    return 'attachment; filename="' . $asciiFallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);
}

function resolve_unique_storage_path(string $directory, string $basename): string
{
    $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $basename;
    if (!is_file($path)) {
        return $path;
    }
    $info = pathinfo($basename);
    $name = (string) ($info['filename'] ?? 'archivo');
    $ext = isset($info['extension']) && $info['extension'] !== '' ? '.' . $info['extension'] : '';
    for ($i = 2; $i <= 99; $i++) {
        $candidate = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $name . ' (' . $i . ')' . $ext;
        if (!is_file($candidate)) {
            return $candidate;
        }
    }

    return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $name . '_' . time() . $ext;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function abort_404(string $uri = ''): never
{
    http_response_code(404);
    view('errors/404', ['uri' => $uri]);
    exit;
}

function app_handle_exception(Throwable $e): void
{
    if (http_response_code() < 400) {
        http_response_code(500);
    }
    log_error($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    $debugMessage = (defined('APP_DEBUG') && APP_DEBUG) ? $e->getMessage() : null;
    if (!headers_sent()) {
        try {
            view('errors/500', ['message' => $debugMessage]);

            return;
        } catch (Throwable) {
            // layout o vista no disponible
        }
    }

    echo 'Error interno del servidor.';
}

function log_error(string $message): void
{
    $dir = base_path('storage/logs');
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = $dir . '/error.log';

    // Rotación simple: nadie purga este archivo a mano, así que sin esto crece sin límite en
    // producción. Al superar ~5 MB se conserva un único respaldo (.1) y se reinicia el activo.
    $maxBytes = 5 * 1024 * 1024;
    if (is_file($file) && filesize($file) > $maxBytes) {
        @rename($file, $file . '.1');
    }

    error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, 3, $file);
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

function reporte_maestro_adjust_formula_row(string $formula, int $sourceRow, int $targetRow): string
{
    if ($sourceRow === $targetRow || $formula === '' || $formula[0] !== '=') {
        return $formula;
    }

    $row = preg_quote((string) $sourceRow, '/');
    $pattern = '/\b([A-Z]{1,3})' . $row . '\b/';

    $adjusted = preg_replace_callback(
        $pattern,
        static fn (array $matches): string => $matches[1] . (string) $targetRow,
        $formula
    );

    return $adjusted ?? $formula;
}

/**
 * Nombre canónico del programa: catálogo (PDF/manual confirmado) antes que textos de importación o formularios.
 *
 * @param array<string, mixed>|null $programa
 * @param list<mixed> $fallbackSources
 */
function programa_nombre_canonical(?array $programa, array $fallbackSources = []): string
{
    $catalog = trim((string) ($programa['nombre'] ?? ''));
    if ($catalog !== '') {
        return $catalog;
    }

    foreach ($fallbackSources as $source) {
        $value = trim((string) $source);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

/**
 * Metadatos de programa: el registro en `programas` prevalece sobre resúmenes de importación.
 *
 * @param array<string, mixed> $programa
 * @param array<string, mixed> $importMeta
 * @return array<string, mixed>
 */
function programa_meta_canonical(array $programa, array $importMeta = []): array
{
    return array_merge($importMeta, [
        'codigo' => trim((string) ($programa['codigo'] ?? '')),
        'nombre' => trim((string) ($programa['nombre'] ?? '')),
        'nivel' => trim((string) ($programa['nivel'] ?? '')),
        'modalidad' => trim((string) ($programa['modalidad'] ?? '')),
    ]);
}
