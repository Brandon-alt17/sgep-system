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
