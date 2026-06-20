<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Rellena retroalimentación M3 (página 2) desde observaciones complementarias cuando están vacías.
 */
final class F023M3RetroSupport
{
    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function fillFromObservacionesIfEmpty(array $data): array
    {
        foreach ([
            ['obs' => 'obs_coformador', 'proceso' => 'm3_retro_coformador_proceso', 'desempeno' => 'm3_retro_coformador_desempeno'],
            ['obs' => 'obs_instructor', 'proceso' => 'm3_retro_instructor_proceso', 'desempeno' => 'm3_retro_instructor_desempeno'],
            ['obs' => 'obs_aprendiz', 'proceso' => 'm3_retro_aprendiz_proceso', 'desempeno' => 'm3_retro_aprendiz_desempeno'],
        ] as $pair) {
            $obs = trim((string) ($data[$pair['obs']] ?? ''));
            if ($obs === '') {
                continue;
            }

            $proceso = trim((string) ($data[$pair['proceso']] ?? ''));
            $desempeno = trim((string) ($data[$pair['desempeno']] ?? ''));
            if ($proceso !== '' || $desempeno !== '') {
                continue;
            }

            [$line1, $line2] = F023ObservationLines::split($obs);
            $data[$pair['proceso']] = $line1;
            if ($line2 !== '') {
                $data[$pair['desempeno']] = $line2;
            }
        }

        return $data;
    }
}
