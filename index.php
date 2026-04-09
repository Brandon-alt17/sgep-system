<?php

declare(strict_types=1);

/**
 * Delegación al front controller en public/.
 * En producción, configure el DocumentRoot (o Alias) hacia la carpeta public/
 * y no exponga este archivo.
 */
require __DIR__ . '/public/index.php';
