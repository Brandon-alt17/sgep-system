<?php

declare(strict_types=1);

use Dotenv\Dotenv;

define('BASE_PATH', dirname(__DIR__));
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}
if (class_exists(Dotenv::class) && is_readable(BASE_PATH . '/.env')) {
    Dotenv::createImmutable(BASE_PATH)->load();
}
require BASE_PATH . '/config/app.php';

/** @var array<string, string> $config */
$config = require BASE_PATH . '/config/database.php';
$dbName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($config['database'] ?? 'sgep'));
if ($dbName === '') {
    fwrite(STDERR, 'Nombre de base de datos invalido en .env' . PHP_EOL);
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    echo 'Base de datos lista: ' . $dbName . PHP_EOL;
} catch (PDOException $e) {
    fwrite(STDERR, 'No se pudo crear la base de datos: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, 'Verifique que WAMP/MySQL este activo (icono verde).' . PHP_EOL);
    exit(1);
}
