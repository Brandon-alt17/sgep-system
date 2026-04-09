<?php

declare(strict_types=1);

namespace App\Helpers;

class Validator
{
    public static function required(array $input, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            $value = $input[$field] ?? null;
            if ($value === null || trim((string) $value) === '') {
                $errors[$field] = 'Este campo es obligatorio.';
            }
        }
        return $errors;
    }
}
