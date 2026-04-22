<?php partial('components/page_header', [
    'title' => 'Perfil del aprendiz',
    'subtitle' => '',
]); ?>

<div class="space-y-6 md:col-span-2 grid gap-4">
    <!-- 🔙 VOLVER -->
    <a href="<?= e(APP_BASE_PATH) ?>/aprendices"
       class="items-center text-sm text-app-link hover:underline">
        ← Listado
    </a>

    <!-- 🧩 GRID PRINCIPAL -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 🟢 PERFIL -->
        <section class="<?= e(ui_card_classes()) ?>">
            <h2 class="text-lg font-semibold mb-4">
                <?= e($aprendiz['nombre_completo']) ?>
            </h2>

            <div class="space-y-3 text-sm md:col-span-2 grid gap-4">
                <?php function infoRow($label, $value) { ?>
                    <div>
                        <p class="text-xs text-app-muted"><?= e($label) ?></p>
                        <p class="font-medium"><?= e($value) ?></p>
                    </div>
                <?php } ?>

                <?php infoRow('Documento', $aprendiz['numero_documento']); ?>
                <?php infoRow('Teléfono', $aprendiz['telefono'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Email personal', $aprendiz['correo_personal'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Email institucional', $aprendiz['correo_institucional'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Dirección', $aprendiz['direccion'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Alternativa etapa productiva', $aprendiz['estado'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Registro Sofia Plus', $aprendiz['fecha_registro'] ?? 'Dato no registrado'); ?>

                <div class="pt-2">
                    <span class="<?= e(ui_badge_success_classes()) ?>">
                        <?= e($aprendiz['estado']) ?>
                    </span>
                </div>
            </div>
        </section>

        <!-- 🔵 DERECHA -->
        <div class="md:col-span-2 space-y-6">
            <!-- 🏢 EMPRESA -->
            <section class="<?= e(ui_card_classes()) ?>">
                <h3 class="<?= e(ui_heading_sm_classes()) ?>">
                    Empresa co-formadora
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-3">
                    <?php infoRow('Razón social', $aprendiz['empresa'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('NIT', $aprendiz['empresa_nit'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Dirección', $aprendiz['empresa_direccion'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Supervisor', $aprendiz['supervisor'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Cargo', $aprendiz['supervisor_cargo'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Teléfono contacto', $aprendiz['supervisor_telefono'] ?? 'Dato no registrado'); ?>
                </div>
            </section>

            <!-- 🟨 MOMENTOS -->
            <section class="mt-4 <?= e(ui_card_classes()) ?>">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="<?= e(ui_heading_sm_classes()) ?>">Momentos</h3>
                    <a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int)$aprendiz['id'] ?>&tipo=EX"
                       class="<?= e(ui_button_small_classes()) ?>">
                        + Agregar Momento extraordinario
                    </a>
                </div>

                <div class="space-y-3 md:col-span-2 grid gap-4">
                    <?php foreach ($momentos as $m): ?>
                        <?php
                        $badge = ui_badge_error_classes();
                        if ($m['estado'] === 'Completado') $badge = ui_badge_success_classes();
                        elseif ($m['estado'] === 'Incompleto') $badge = ui_badge_warning_classes();
                        ?>
                        <div class="flex items-center justify-between rounded-lg border border-app-border p-4 gap-3">
                            <span class="text-sm font-medium">
                                <?= e($m['label']) ?>
                            </span>
                            <div class="flex items-center gap-3">
                                <?php if (!empty($m['fecha'])): ?>
                                    <span class="text-xs text-app-muted">
                                        <?= e($m['fecha']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="<?= e($badge) ?>">
                                    <?= e($m['estado']) ?>
                                </span>
                                <a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int)$aprendiz['id'] ?>&tipo=<?= e($m['id']) ?>"
                                    class="<?= e(ui_button_small_classes()) ?>">
                                    Editar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- 🟪 ACCIONES -->
           
            <div class="grid mb:col-span-2 grid flex gap-3 mt-4">

                <a href="<?= e(APP_BASE_PATH) ?>/documentos/generar?aprendiz_id=<?= (int)$aprendiz['id'] ?>"
                class="<?= e(ui_button_small_classes()) ?> flex items-center justify-center text-center">
                    Generar documento GFPI-F-023
                </a>

                <a href="<?= e(APP_BASE_PATH) ?>/reportes/maestro" 
                class="text-sm text-app-accent hover:underline flex items-center justify-center text-center">
                    Ver en reporte general →
                </a>

            </div>
        </div>
    </div>
</div>