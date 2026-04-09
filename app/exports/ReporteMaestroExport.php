<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteMaestroExport
{
    public function export(array $filters = []): string
    {
        $rows = $this->query($filters);
        $sheet = (new Spreadsheet())->getActiveSheet();
        $headers = ['Documento', 'Nombre', 'Estado', 'Programa', 'Empresa', 'Próxima visita'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }
        $line = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $line, $row['numero_documento'] ?? '');
            $sheet->setCellValue('B' . $line, $row['nombre_completo'] ?? '');
            $sheet->setCellValue('C' . $line, $row['estado'] ?? '');
            $sheet->setCellValue('D' . $line, $row['programa'] ?? '');
            $sheet->setCellValue('E' . $line, $row['empresa'] ?? '');
            $sheet->setCellValue('F' . $line, $row['proxima_visita'] ?? '');
            $line++;
        }
        $path = base_path('storage/documents/reporte_maestro_' . time() . '.xlsx');
        (new Xlsx($sheet->getParent()))->save($path);
        return $path;
    }

    private function query(array $filters): array
    {
        $sql = 'SELECT a.numero_documento, a.nombre_completo, a.estado, p.nombre AS programa, e.nombre AS empresa, m.proxima_visita
                FROM aprendices a
                LEFT JOIN programas p ON p.id = a.programa_id
                LEFT JOIN empresas e ON e.id = a.empresa_id
                LEFT JOIN momentos m ON m.aprendiz_id = a.id';
        $where = [];
        $params = [];
        if (!empty($filters['estado'])) {
            $where[] = 'a.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
