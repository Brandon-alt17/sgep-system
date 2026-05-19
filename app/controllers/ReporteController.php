<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exports\ReporteMaestroExport;
use App\Helpers\Database;

class ReporteController
{
    public function index(): void
    {
        $sql = "
            SELECT 
                -- TABLA APRENDICES
                a.id,
                a.nombre_completo,
                a.tipo_documento,
                a.numero_documento,
                a.telefono,
                a.correo_personal,
                a.correo_institucional,
                a.ficha,
                a.programa_id,
                a.empresa_id,
                a.estado,
                a.proxima_visita,
                a.created_at,
                a.updated_at,
                a.fecha_hora_formulario,
                a.direccion_domicilio,
                a.ciudad_domicilio,
                a.alternativa_ep,
                a.nombre_instructor_seguimiento,
                a.telefono_instructor_seguimiento,
                a.tipo_asistencia,
                a.sugerencias_comentarios,
                a.jefe_grupo,
                a.coordinacion,
                a.jefe_id,

                -- PROGRAMA
                p.codigo AS codigo_programa,
                p.nombre AS programa_formacion,
                p.nivel,

                --- EMPRESA
                e.nombre AS empresa,
                e.direccion AS direccion_empresa,
                e.ciudad,
                e.nombre_contacto2 AS contacto,
                e.correo_org AS telefono_contacto,
                e.correo_contacto2 AS correo_contacto,

                -- REPORTE
                rc.updated_at AS reporte_actualizado

            FROM aprendices a

            LEFT JOIN programas p 
                ON p.id = a.programa_id

            LEFT JOIN empresas e 
                ON e.id = a.empresa_id

            LEFT JOIN reporte_campos rc 
                ON rc.aprendiz_id = a.id

            ORDER BY a.nombre_completo ASC
        ";

        $rows = Database::connection()
            ->query($sql)
            ->fetchAll();

        /*
        |--------------------------------------------------------------------------
        | DATOS ESTÁTICOS TEMPORALES
        |--------------------------------------------------------------------------
        | Como muchos campos todavía no existen en la BD,
        | aquí se agregan valores por defecto para que
        | la tabla NO genere errores.
        |--------------------------------------------------------------------------
        */

        foreach ($rows as &$row) {

            // G1
            $row['num_aprendiz'] = $row['id'] ?? '';
            $row['num_por_grupo'] = rand(1, 30);

            $row['modalidad_programa'] = 'Presencial';

            $row['fecha_inicio_plataforma'] =
                $row['created_at'] ?? '';

            $row['fecha_fin_plataforma'] =
                $row['updated_at'] ?? '';

            $row['instructor_jefe'] =
                $row['jefe_grupo'] ?? 'Sin asignar';

            // G2
            $row['acuerdo_007'] = true;
            $row['acuerdo_009'] = false;
            $row['inicio_18_meses'] = true;
            $row['inicio_12_meses'] = false;
            $row['vencimiento_terminos'] = false;

            // G3
            $row['nombre'] =
                $row['nombre_completo'] ?? '';

            $row['identificacion'] =
                $row['numero_documento'] ?? '';

            $row['celular'] =
                $row['telefono'] ?? '';

            $row['correo'] =
                $row['correo_personal']
                ?? $row['correo_institucional']
                ?? '';

            $row['modalidad_practica'] =
                $row['alternativa_ep'] ?? 'Contrato';

            $row['fecha_aval_modalidad'] =
                $row['fecha_hora_formulario'] ?? '';

            $row['estado_arl'] = 'Afiliado';
            $row['arl'] = 'SURA';

            // G4
            $row['fecha_inicio_etapa'] =
                $row['created_at'] ?? '';

            $row['fecha_fin_etapa'] =
                $row['updated_at'] ?? '';

            $row['contacto_empresa'] =
                $row['contacto'] ?? '';

            $row['estado_etapa'] =
                $row['estado'] ?? '';

            $row['reingreso_vencimiento'] = false;

            $row['cambio_modalidad'] = 'Ninguno';

            $row['observaciones_novedad'] =
                $row['sugerencias_comentarios']
                ?? '';

            // G5
            $row['doc_gfpi_165'] = true;
            $row['doc_momento_1'] = true;
            $row['doc_bitacora_1'] = true;
            $row['doc_bitacora_2'] = true;
            $row['doc_bitacora_3'] = false;
            $row['doc_bitacora_4'] = false;
            $row['doc_bitacora_5'] = false;
            $row['doc_bitacora_6'] = false;
            $row['doc_momento_final'] = false;

            // G6
            $row['cert_doc_identidad'] = true;
            $row['cert_paz_salvo'] = false;
            $row['cert_gfpi_023'] = false;
            $row['cert_bitacoras'] = false;
            $row['cert_cumplimiento'] = false;
            $row['cert_ape'] = false;
            $row['cert_carnet'] = false;
            $row['cert_saber_tyt'] = false;

            $row['fecha_entrega_admin'] = '';

            $row['estado_aprendiz'] =
                $row['estado'] ?? 'En formación';

            $row['observaciones_cert'] = '';

            // G7
            $row['instructor_asignado'] =
                $row['nombre_instructor_seguimiento']
                ?? '';

            $row['telefono_instructor'] =
                $row['telefono_instructor_seguimiento']
                ?? '';

            $row['correo_instructor'] =
                $row['correo_institucional']
                ?? '';
        }

        view('reports/maestro', [
            'rows' => $rows
        ]);
    }

    public function update(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);

        $campo = (string) ($_POST['campo'] ?? '');

        $valor = (string) ($_POST['valor'] ?? '');

        if ($campo === '') {
            redirect(APP_BASE_PATH . '/reportes/maestro');
        }

        $sql = "
            INSERT INTO reporte_campos (
                aprendiz_id,
                campo,
                valor,
                updated_at
            )
            VALUES (
                :aprendiz_id,
                :campo,
                :valor,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                valor = VALUES(valor),
                updated_at = NOW()
        ";

        Database::connection()
            ->prepare($sql)
            ->execute([
                'aprendiz_id' => $aprendizId,
                'campo' => $campo,
                'valor' => $valor,
            ]);

        redirect(APP_BASE_PATH . '/reportes/maestro');
    }

    public function export(): void
    {
        $path = (new ReporteMaestroExport())
            ->export($_GET);

        header(
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        header(
            'Content-Disposition: attachment; filename="' .
            basename($path) .
            '"'
        );

        readfile($path);

        exit;
    }
}