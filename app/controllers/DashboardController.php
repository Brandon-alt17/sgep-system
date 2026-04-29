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
            
            // Verificar si la conexión funciona
            if (!$pdo) {
                throw new \Exception("Error de conexión a la base de datos");
            }
            
            // Query de conteo con try-catch específico
            try {
                $counts = $pdo->query('SELECT estado, COUNT(*) AS total FROM aprendices GROUP BY estado')->fetchAll();
            } catch (PDOException $e) {
                error_log("Error en query de conteo: " . $e->getMessage());
                $counts = [];
            }
            
            // Query de alertas con try-catch específico
            try {
                $alerts = $pdo->query('SELECT a.id, a.nombre_completo, m.proxima_visita
                                       FROM aprendices a
                                       JOIN momentos m ON m.aprendiz_id = a.id
                                       WHERE a.estado != "Aplazada"
                                         AND m.proxima_visita BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                                       ORDER BY m.proxima_visita ASC')->fetchAll();
            } catch (PDOException $e) {
                error_log("Error en query de alertas: " . $e->getMessage());
                $alerts = [];
            }
            
            // Verificar si la función view existe
            if (!function_exists('view')) {
                throw new \Exception("La función 'view' no está definida");
            }
            
            view('dashboard', ['counts' => $counts, 'alerts' => $alerts]);
            
        } catch (\Exception $e) {
            error_log("DashboardController error: " . $e->getMessage());
            // Mostrar error en lugar de página en blanco
            die("Error en Dashboard: " . $e->getMessage());
        }
    }
}