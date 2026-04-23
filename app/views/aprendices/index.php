<?php partial('components/page_header', [
    'title' => 'Aprendices',
    'actions' => '
        <a class="'.e(ui_button_small_primary_classes()).'" href="'.e(APP_BASE_PATH).'/aprendices/create">
            Nuevo aprendiz
        </a>
    '
]); ?>

<!-- Buscador -->
<section class="<?= e(ui_card_classes()) ?> mb-4">
    <div class="flex items-center gap-3 w-full">
        <!-- 🔍 Buscador -->
        <input type="text" placeholder="Buscar por nombre o documento" class="<?= e(ui_input_classes()) ?> flex-1 min-w-0">

        <div class="relative">
            <select class="<?= e(ui_select_classes()) ?>">
                <option>Todas</option>
            </select>
        </div>

        <!-- 🎛 Select 2 -->
        <div class="relative">
            <select class="<?= e(ui_select_classes()) ?>">
                <option>Todos</option>
            </select>
        </div>

    </div>
</section>

<!-- Tabla -->
<section class="<?= e(ui_card_classes()) ?>">

    <table class="<?= e(ui_table_classes()) ?>">
        
        <!-- 👇 HEADER con fondo gris como la imagen -->
        <thead class="bg-gray-100 border-b">
            <tr>
                <th class="<?= e(ui_th_classes()) ?>">Nombre completo</th>
                <th class="<?= e(ui_th_classes()) ?>">Documento</th>
                <th class="<?= e(ui_th_classes()) ?>">Empresa co-formadora</th>
                <th class="<?= e(ui_th_classes()) ?>">Ficha</th>
                <th class="<?= e(ui_th_classes()) ?>">Estado</th>
                <th class="<?= e(ui_th_classes()) ?>">Última visita</th>
                <th class="<?= e(ui_th_classes()) ?>"></th>
            </tr>
        </thead>

        <tbody>
        <?php foreach (($aprendices ?? []) as $aprendiz): ?>

            <!-- 👇 hover suave como la imagen -->
            <tr class="border-b hover:bg-gray-50 transition">

                <td class="<?= e(ui_td_classes()) ?> font-medium">
                    <?= e($aprendiz['nombre_completo']) ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?> text-gray-600">
                    <?= e($aprendiz['numero_documento']) ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?>">
                    <?= e( $aprendiz['empresa_nombre'] ?? '-') ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?> text-gray-600">
                    <?= e($aprendiz['ficha'] ?? '-') ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?>">
                    <?php
                        $estado = $aprendiz['estado'] ?? '';

                        if ($estado === 'En etapa productiva') {
                            echo '<span class="'.e(ui_badge_success_classes()).'">'.$estado.'</span>';
                        } elseif ($estado === 'Pendiente') {
                            echo '<span class="'.e(ui_badge_warning_classes()).'">'.$estado.'</span>';
                        } elseif ($estado === 'Certificado') {
                            echo '<span class="'.e(ui_badge_success_classes()).'">'.$estado.'</span>';
                        } else {
                            echo '<span class="'.e(ui_badge_error_classes()).'">'.$estado.'</span>';
                        }
                    ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?> text-gray-500">
                    <?= e($aprendiz['ultima_visita'] ?? '—') ?>
                </td>

                <td class="<?= e(ui_td_classes()) ?> text-right">
                    <a class="<?= e(ui_button_small_classes()) ?>" 
                       href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int)$aprendiz['id'] ?>">
                        Ver perfil
                    </a>
                </td>

            </tr>

        <?php endforeach; ?>
        </tbody>
    </table>

</section>