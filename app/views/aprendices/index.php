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
        <div class="relative flex-1 min-w-0">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-app-muted [&_svg]:h-4 [&_svg]:w-4">
                <?= ui_icon('search') ?>
            </span>
            <input type="text" placeholder="Buscar por nombre o documento" class="<?= e(ui_input_classes()) ?> flex-1 min-w-0 pl-10">
        </div>

        <div class="<?= e(ui_select_wrapper_classes()) ?> w-64 shrink-0">
            <select class="<?= e(ui_select_classes()) ?>">
                <option>Todas</option>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </div>

        <!-- 🎛 Select 2 -->
        <div class="<?= e(ui_select_wrapper_classes()) ?> w-64 shrink-0">
            <select class="<?= e(ui_select_classes()) ?>">
                <option>Todos</option>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </div>

    </div>
</section>

<!-- Tabla -->
<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden">

    <table class="<?= e(ui_table_classes()) ?>">
        
        <!-- 👇 HEADER con fondo gris como la imagen -->
        <thead class="bg-app-panelSubtle border-b">
            <tr>
                <th class="<?= e(ui_th_classes()) ?>">Nombre completo</th>
                <th class="<?= e(ui_th_classes()) ?>">Documento</th>
                <th class="<?= e(ui_th_classes()) ?>">Empresa</th>
                <th class="<?= e(ui_th_classes()) ?>">Grupo</th>
                <th class="<?= e(ui_th_classes()) ?>">Estado</th>
                <th class="<?= e(ui_th_classes()) ?>">Última visita</th>
                <th class="<?= e(ui_th_classes()) ?>"></th>
            </tr>
        </thead>

        <tbody>
        <?php if (empty($aprendices)): ?>
            <tr class="border-b bg-app-panelSubtle/40">
                <td class="<?= e(ui_td_classes()) ?> align-middle text-center text-app-muted" colspan="7">Sin aprendices registrados aún</td>
            </tr>
        <?php else: ?>
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

                <td class="<?= e(ui_td_classes()) ?>">
                    <a class="<?= e(ui_button_small_classes()) ?>" 
                       href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int)$aprendiz['id'] ?>">
                        Ver perfil
                    </a>
                </td>

            </tr>

        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

</section>