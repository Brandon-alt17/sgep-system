<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use PDOException;

class DashboardController
{
    public function index(): void
    {
        try {
            $pdo = Database::connection();

            if (!$pdo) {
                throw new \Exception("Error de conexión a la base de datos");
            }

            // =========================
            // 1. Conteo por estados
            // =========================
            try {
                $counts = $pdo->query("
                    SELECT estado, COUNT(*) AS total 
                    FROM aprendices 
                    GROUP BY estado
                ")->fetchAll();
            } catch (PDOException $e) {
                error_log("Error en query de conteo: " . $e->getMessage());
                $counts = [];
            }

            // =========================
            // 2. Totales para cards
            // =========================
            $totales = [
                'activos' => (int) $pdo->query("
                    SELECT COUNT(*) 
                    FROM aprendices 
                    WHERE estado IN ('En ejecución', 'Pendiente por iniciar', 'Pendiente por comité')
                ")->fetchColumn(),

                'por_certificar' => (int) $pdo->query("
                    SELECT COUNT(*) 
                    FROM aprendices 
                    WHERE estado = 'Por certificar'
                ")->fetchColumn(),

                'total_empresas' => (int) $pdo->query("
                    SELECT COUNT(*) FROM empresas
                ")->fetchColumn(),

                'total_programas' => (int) $pdo->query("
                    SELECT COUNT(*) FROM programas
                ")->fetchColumn(),
            ];

            // =========================
            // 3. Próximas visitas
            // =========================
            try {
                $alerts = $pdo->query("
                    SELECT 
                        a.id,
                        a.nombre_completo,
                        e.nombre AS empresa,
                        m.proxima_visita,
                        m.momento
                    FROM aprendices a
                    LEFT JOIN empresas e ON e.id = a.empresa_id
                    JOIN momentos m ON m.aprendiz_id = a.id
                    WHERE a.estado != 'Aplazada'
                      AND m.proxima_visita BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                    ORDER BY m.proxima_visita ASC
                ")->fetchAll();
            } catch (PDOException $e) {
                error_log("Error en query de alertas: " . $e->getMessage());
                $alerts = [];
            }

            // =========================
            // 4. Render
            // =========================
            view('dashboard', [
                'counts' => $counts,
                'alerts' => $alerts,
                'totales' => $totales
            ]);

        } catch (\Exception $e) {
            error_log("DashboardController error: " . $e->getMessage());
            die("Error en Dashboard: " . $e->getMessage());
        }
    }
}