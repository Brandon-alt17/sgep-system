<?php
// Asegurar que APP_BASE_PATH esté definida
if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', '');
}
// Definir funciones de UI si no existen
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

?>
<?php partial('components/page_header', [
    'title' => 'Dashboard',
    'subtitle' => 'Vista general del estado de aprendices y próximas actividades.',
]); ?>
<!-- Cards de resumen principales (3 cards por fila desde pantallas medianas) -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-8">
    <!-- Card 1: Aprendices activos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
           <div class="flex items-center justify-center w-16 h-16 bg-app-accentSoft rounded-lg">
                <span class="flex items-center justify-center text-app-accent w-10 h-10 [&_svg]:w-6 [&_svg]:h-6">   
                     <?= ui_icon('users') ?>
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= e((string)($totales['activos'] ?? 0)) ?></p>
                <p class="text-gray-500 text-sm font-medium">Aprendices activos</p>
            </div>
        </div>
    </div>
    <!-- Card 2: Por certificar -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
           <div class="flex items-center justify-center w-16 h-16 bg-app-accentSoft rounded-lg">
                <span class="flex items-center justify-center text-app-accent w-10 h-10 [&_svg]:w-6 [&_svg]:h-6">   
                    <?= ui_icon('circle-check') ?>
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= e((string)($totales['por_certificar'] ?? 0)) ?></p>
                <p class="text-gray-500 text-sm font-medium">Por certificar</p>
            </div>
        </div>
    </div>
    <!-- Card 3: En ejecución -->
    <?php
    $enEjecucion = 0;
    $pendienteIniciar = 0;
    foreach ($counts as $row) {
        if ($row['estado'] === 'En ejecución') {
            $enEjecucion = (int)$row['total'];
        }
        if ($row['estado'] === 'Pendiente por iniciar') {
            $pendienteIniciar = (int)$row['total'];
        }
    }
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
           <div class="flex items-center justify-center w-16 h-16 bg-app-accentSoft rounded-lg">
                <span class="flex items-center justify-center text-app-accent w-10 h-10 [&_svg]:w-6 [&_svg]:h-6">   
                    <?= ui_icon('briefcase') ?>
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= e((string)$enEjecucion) ?></p>
                <p class="text-gray-500 text-sm font-medium">En ejecución</p>
            </div>
        </div>
    </div>
    <!-- Card 4: Pendiente por iniciar -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-16 h-16 bg-app-accentSoft rounded-lg">
                <span class="flex items-center justify-center text-app-accent w-10 h-10 [&_svg]:w-6 [&_svg]:h-6">   
                    <?= ui_icon('clock') ?>
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= e((string)$pendienteIniciar) ?></p>
                <p class="text-gray-500 text-sm font-medium">Pendiente por iniciar</p>
            </div>
        </div>
    </div>
    <!-- Card 5: Total Empresas -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-16 h-16 bg-app-accentSoft rounded-lg">
                <span class="flex items-center justify-center text-app-accent w-10 h-10 [&_svg]:w-6 [&_svg]:h-6">   
                    <?= ui_icon('building') ?>
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= e((string)($totales['total_empresas'] ?? 0)) ?></p>
                <p class="text-gray-500 text-sm font-medium">Empresas</p>
            </div>
        </div>
    </div>
    <!-- Card 6: Total Programas de Formación -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-16 h-16 bg-app-accentSoft rounded-lg">
                <span class="flex items-center justify-center text-app-accent w-10 h-10 [&_svg]:w-6 [&_svg]:h-6">   
                    <?= ui_icon('book-open') ?>
                </span>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= e((string)($totales['total_programas'] ?? 0)) ?></p>
                <p class="text-gray-500 text-sm font-medium">Programas de formación</p>
            </div>
        </div>
    </div>
</div>
<!-- Próximas visitas programadas con contador y separación superior -->
<div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Próximas visitas programadas</h3>
            <span class="bg-green-50 text-green-600 text-sm font-semibold px-3 py-1 rounded-full">
                Total: <?= count($alerts) ?>
            </span>
        </div>
    </div>
    <div class="divide-y divide-gray-100 min-h-[300px]">
        <?php if (empty($alerts)): ?>
        <!-- Mensaje bonito cuando no hay visitas -->
        <div class="flex flex-col items-center justify-center py-12 px-6 text-center h-full">
            <h4 class="text-lg font-medium text-gray-700 mb-1">No hay visitas programadas</h4>
            <p class="text-sm text-gray-400">No hay visitas programadas en los próximos 30 días</p>
        </div>
        
        <?php else: ?>
        <?php $counter = 1; ?>
        <?php foreach ($alerts as $alert):
            $badgeColor = '';
            $badgeText = '';
            // Determinar estado de la visita
            $fechaActual = new DateTime();
            $fechaVisita = new DateTime($alert['proxima_visita']);
            $diferencia = $fechaActual->diff($fechaVisita)->days;
            if ($diferencia <= 3) {
                $badgeColor = 'bg-red-50 text-red-600';
                $badgeText = 'Próxima';
            } elseif ($diferencia <= 7) {
                $badgeColor = 'bg-yellow-50 text-yellow-600';
                $badgeText = 'Esta semana';
            } else {
                $badgeColor = 'bg-green-50 text-green-600';
                $badgeText = 'Programada';
            }
        ?>
        <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="font-bold text-gray-300 text-lg"><?= $counter++ ?>.</div>
                    <div>
                        <h4 class="text-md font-semibold text-gray-800"><?= e((string)$alert['nombre_completo']) ?></h4>
                        <p class="text-sm text-gray-500"><?= e((string)($alert['empresa'] ?? 'Empresa no especificada')) ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-800">
                        <?= e(date('d M Y', strtotime($alert['proxima_visita']))) ?>
                    </p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeColor ?>">
                        <?= $badgeText ?>
                    </span>
                    <?php if (!empty($alert['momento'])): ?>
                    <p class="text-xs text-gray-400 mt-1"><?= e((string)$alert['momento']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>