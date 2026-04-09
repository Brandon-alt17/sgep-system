<?php

declare(strict_types=1);

date_default_timezone_set((string) env('APP_TIMEZONE', 'America/Bogota'));

define('APP_NAME', (string) env('APP_NAME', 'SGEP'));
define('APP_URL', (string) env('APP_URL', 'http://localhost/sgep'));
define('APP_BASE_PATH', (string) env('APP_BASE_PATH', '/sgep'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL));
define('APP_LOCALE', 'es_CO');
