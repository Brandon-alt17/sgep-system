<?php

declare(strict_types=1);

$password = (string) (getenv('SGEP_DB_PASS') ?? '');

foreach ([3306, 3307] as $port) {
    try {
        new PDO(
            sprintf('mysql:host=127.0.0.1;port=%d', $port),
            'root',
            $password,
            [PDO::ATTR_TIMEOUT => 2]
        );
        echo $port;
        exit(0);
    } catch (Throwable) {
        continue;
    }
}

echo '3306';
