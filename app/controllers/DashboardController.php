<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;

class DashboardController
{
    public function index(): void
    {
        $pdo = Database::connection();
        $counts = $pdo->query('SELECT estado, COUNT(*) AS total FROM aprendices GROUP BY estado')->fetchAll();
        $alerts = $pdo->query('SELECT a.id, a.nombre_completo, m.proxima_visita
                               FROM aprendices a
                               JOIN momentos m ON m.aprendiz_id = a.id
                               WHERE a.estado != "Aplazada"
                                 AND m.proxima_visita BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                               ORDER BY m.proxima_visita ASC')->fetchAll();
        view('dashboard', ['counts' => $counts, 'alerts' => $alerts]);
    }
}
