#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Verifica que LibreOffice esté disponible para exportar F-023 a PDF.
 *
 * Uso: php scripts/check_pdf_converter.php
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

if (!function_exists('env')) {
    require_once BASE_PATH . '/app/helpers/helpers.php';
}

if (class_exists(Dotenv\Dotenv::class) && is_readable(BASE_PATH . '/.env')) {
    Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
}

require BASE_PATH . '/app/exports/F023DocxToPdf.php';

use App\Exports\F023DocxToPdf;

$message = F023DocxToPdf::availabilityMessage();
if ($message === null) {
    echo "OK: motor PDF disponible.\n";
    exit(0);
}

echo "ERROR: {$message}\n";
echo "Configure F023_LIBREOFFICE_PATH en .env o instale LibreOffice en el PATH.\n";
exit(1);
