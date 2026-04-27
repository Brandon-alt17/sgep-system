<div class="p-6 space-y-6">

    <!-- HEADER -->
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-sm text-gray-500">Resumen general del sistema</p>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- Aprendices -->
        <div class="bg-white rounded-xl shadow p-5 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Aprendices activos</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $totalAprendices ?></h2>
            </div>
            <div class="bg-blue-100 text-blue-600 p-3 rounded-lg">
                <i data-lucide="users"></i>
            </div>
        </div>

        <!-- Visitas -->
        <div class="bg-white rounded-xl shadow p-5 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Visitas esta semana</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $visitasSemana ?></h2>
            </div>
            <div class="bg-yellow-100 text-yellow-600 p-3 rounded-lg">
                <i data-lucide="map-pin"></i>
            </div>
        </div>

        <!-- Por certificar -->
        <div class="bg-white rounded-xl shadow p-5 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Por certificar</p>
                <h2 class="text-2xl font-bold text-gray-800"><?= $porCertificar ?></h2>
            </div>
            <div class="bg-green-100 text-green-600 p-3 rounded-lg">
                <i data-lucide="award"></i>
            </div>
        </div>
    </div>

    <!-- ALERTAS / ESTADOS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Conteo por estado -->
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Estado de aprendices</h3>

            <div class="space-y-3">
                <?php foreach ($conteoEstados as $estado => $cantidad): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600"><?= $estado ?></span>
                        <span class="font-semibold text-gray-800"><?= $cantidad ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Resumen rápido -->
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Resumen rápido</h3>

            <ul class="space-y-3 text-sm text-gray-600">
                <li>📌 Tienes <strong><?= $visitasPendientes ?></strong> visitas pendientes</li>
                <li>📌 <strong><?= $momentosPendientes ?></strong> momentos sin completar</li>
                <li>📌 <strong><?= $aprendicesAtrasados ?></strong> aprendices con retraso</li>
            </ul>
        </div>
    </div>

    <!-- VISITAS -->
    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-700">Próximas visitas</h3>
            <a href="/visitas" class="text-sm text-blue-600 hover:underline">Ver todas</a>
        </div>

        <div class="space-y-3">
            <?php foreach ($visitas as $v): ?>
                <div class="flex justify-between items-center border rounded-lg p-4 hover:bg-gray-50 transition">

                    <div>
                        <p class="font-medium text-gray-800"><?= $v['nombre'] ?></p>
                        <p class="text-sm text-gray-500"><?= $v['empresa'] ?></p>
                    </div>

                    <div class="text-right space-y-1">
                        <span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-full">
                            <?= date('d M Y', strtotime($v['fecha'])) ?>
                        </span>

                        <?php if ($v['estado'] === 'pendiente'): ?>
                            <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full">
                                Pendiente
                            </span>
                        <?php else: ?>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                Al día
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>