<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Datos consolidados para el F-023 (perfil + programa + empresa + tabla aprendiz_info_general).
 */
final class F023InfoData
{
    /**
     * @param array<string,mixed> $aprendiz
     * @param array<string,mixed>|null $programa
     * @param array<string,mixed>|null $empresa
     * @param array<string,mixed> $guardada fila aprendiz_info_general
     * @return array<string,string>
     */
    public static function build(array $aprendiz, ?array $programa, ?array $empresa, array $guardada): array
    {
        $valor = static function (array $prioritario, string $k, mixed $fallback = ''): string {
            $actual = trim((string) ($prioritario[$k] ?? ''));
            if ($actual !== '') {
                return $actual;
            }

            return trim((string) $fallback);
        };

        $modalidadTexto = $valor($guardada, 'modalidad_formacion', (string) ($programa['modalidad'] ?? ''));
        $modalidadMarcas = self::modalidadCheckmarks($modalidadTexto);

        $out = [
            'regional' => $valor($guardada, 'regional', 'Risaralda'),
            'centro_formacion' => $valor($guardada, 'centro_formacion', 'Diseño e Innovación Tecnológica Industrial'),
            'nivel_formativo' => $valor($guardada, 'nivel_formativo', (string) ($programa['nivel'] ?? '')),
            'programa_formacion' => $valor($guardada, 'programa_formacion', (string) ($programa['nombre'] ?? '')),
            'numero_grupo' => $valor($guardada, 'numero_grupo', (string) ($aprendiz['ficha'] ?? '')),
            'modalidad_formacion' => $modalidadTexto,
            'modalidad_presencial' => $modalidadMarcas['presencial'],
            'modalidad_virtual' => $modalidadMarcas['virtual'],
            'modalidad_distancia' => $modalidadMarcas['distancia'],
            'estrategia_formativa' => $valor($guardada, 'estrategia_formativa', 'Dual'),
            'fecha_fin_etapa_lectiva' => $valor($guardada, 'fecha_fin_etapa_lectiva', ''),
            'fecha_registro_sofiaplus' => $valor($guardada, 'fecha_registro_sofiaplus', ''),
            'asistencia_nombre' => $valor($guardada, 'asistencia_nombre', ''),
            'asistencia_tipo' => $valor($guardada, 'asistencia_tipo', (string) ($aprendiz['tipo_asistencia'] ?? '')),
            'asistencia_contacto' => $valor($guardada, 'asistencia_contacto', ''),
            'nombre_completo' => trim((string) ($aprendiz['nombre_completo'] ?? '')),
            'tipo_documento' => trim((string) ($aprendiz['tipo_documento'] ?? '')),
            'numero_documento' => trim((string) ($aprendiz['numero_documento'] ?? '')),
            'telefono' => trim((string) ($aprendiz['telefono'] ?? '')),
            'direccion_domicilio' => trim((string) ($aprendiz['direccion_domicilio'] ?? '')),
            'correo_personal' => trim((string) ($aprendiz['correo_personal'] ?? '')),
            'correo_institucional' => trim((string) ($aprendiz['correo_institucional'] ?? '')),
            'alternativa_ep' => trim((string) ($aprendiz['alternativa_ep'] ?? '')),
            'nombre_instructor_seguimiento' => trim((string) ($aprendiz['nombre_instructor_seguimiento'] ?? '')),
            'telefono_instructor_seguimiento' => trim((string) ($aprendiz['telefono_instructor_seguimiento'] ?? '')),
            'correo_instructor_seguimiento' => trim((string) ($aprendiz['correo_instructor_seguimiento'] ?? '')),
            'empresa_nombre' => trim((string) ($empresa['nombre'] ?? ($aprendiz['empresa_nombre'] ?? ''))),
            'empresa_direccion' => trim((string) ($empresa['direccion'] ?? ($aprendiz['direccion'] ?? ''))),
            'empresa_nit' => trim((string) ($empresa['nit'] ?? ($aprendiz['nit'] ?? ''))),
            'empresa_correo' => trim((string) ($empresa['correo_org'] ?? '')),
            'jefe_nombre' => trim((string) ($aprendiz['nombre_jefe'] ?? '')),
            'jefe_cargo' => trim((string) ($aprendiz['cargo_jefe'] ?? '')),
            'jefe_telefono' => trim((string) ($aprendiz['telefono_jefe'] ?? '')),
            'jefe_correo' => trim((string) ($aprendiz['correo_jefe'] ?? '')),
            'contacto2_nombre' => trim((string) ($aprendiz['nombre_contacto2_jefe'] ?? ($empresa['nombre_contacto2'] ?? ''))),
            'contacto2_correo' => trim((string) ($aprendiz['correo_contacto2_jefe'] ?? ($empresa['correo_contacto2'] ?? ''))),
            // Teléfono institucional del otro contacto: fila en el F-023; sin columna en BD por ahora.
            'contacto2_telefono' => '',
        ];
        foreach (['estrategia_formativa', 'direccion_domicilio', 'empresa_direccion'] as $mk) {
            $out[$mk] = normalize_multiline_text((string) ($out[$mk] ?? ''));
        }

        return $out;
    }

    /**
     * Marca con «X» la casilla correspondiente en la plantilla Word (Presencial / Virtual / A distancia).
     *
     * @return array{presencial: string, virtual: string, distancia: string}
     */
    public static function modalidadCheckmarks(string $raw): array
    {
        $s = mb_strtolower(trim($raw), 'UTF-8');
        $empty = ['presencial' => '', 'virtual' => '', 'distancia' => ''];
        if ($s === '') {
            return $empty;
        }

        if (str_contains($s, 'virtual')) {
            return ['presencial' => '', 'virtual' => 'X', 'distancia' => ''];
        }
        if (str_contains($s, 'distanc') || str_contains($s, 'a distancia')) {
            return ['presencial' => '', 'virtual' => '', 'distancia' => 'X'];
        }
        if (str_contains($s, 'presencial')) {
            return ['presencial' => 'X', 'virtual' => '', 'distancia' => ''];
        }

        return $empty;
    }

    /** @return list<string> claves obligatorias alineadas con documentos/info.php */
    public static function requiredKeysForCompleteness(): array
    {
        return [
            'programa_formacion',
            'nivel_formativo',
            'numero_grupo',
            'modalidad_formacion',
            'nombre_completo',
            'tipo_documento',
            'numero_documento',
            'telefono',
            'correo_personal',
            'correo_institucional',
            'nombre_instructor_seguimiento',
            'empresa_nombre',
            'empresa_nit',
            'jefe_nombre',
        ];
    }
}
