<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class Programa
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM programas ORDER BY nombre ASC')->fetchAll();
    }
}
