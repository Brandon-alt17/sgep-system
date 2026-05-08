<?php include __DIR__ . '/modal-editar-perfil.php'; ?>
<?php include __DIR__ . '/modal-agendar-visitas.php'; ?>
<script src="<?= e(APP_BASE_PATH) ?>../public/js/edit-profile.js"></script>
<?php $backToListUrl = (string) ($backToListUrl ?? (APP_BASE_PATH . '/aprendices')); ?>
<?php $cardPaddedClass = str_replace('p-4', 'p-6', ui_card_classes()); ?>

<section class="bg-app-bg mb-4 flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-1 self-start" href="<?= e($backToListUrl) ?>" aria-label="Volver al listado de aprendices">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="flex w-full items-start justify-between gap-4 pl-4">
        <div class="flex flex-col gap-1">
            <h2 class="m-0 text-2xl font-semibold text-app-text">Perfil del aprendiz</h2>
            <p class="m-0 text-sm text-app-muted">Consulta, actualiza datos del aprendiz y gestiona sus documentos de seguimiento.</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <button type="button" onclick="abrirModalVisitas()"
            class="items-center <?= e(ui_button_small_classes()) ?> gap-2">
                <span class="w-4 h-4 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('calendar') ?></span>
                Programar visitas
            </button>

            <button type="button" onclick="abrirModalEditar()"
            class="items-center <?= e(ui_button_small_classes()) ?> gap-2">
                <span class="w-4 h-4 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('user-round') ?></span>
                Editar datos del aprendiz
            </button>
        </div>
    </div>
</section>



<div class="md:col-span-2 grid gap-4">
    <!-- 🧩 GRID PRINCIPAL -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 🟢 PERFIL -->
        <section class="<?= e($cardPaddedClass) ?>">
            <div class="flex mb-4">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <span class="flex items-center justify-center text-app-link w-[30px] h-[30px] [&_svg]:w-5 [&_svg]:h-5">
                        <?= ui_icon('user') ?>
                    </span>
                    <span><?= e($aprendiz['nombre_completo']) ?></span>
                </h2>
            </div>

            <div class="space-y-3 text-sm md:col-span-2 grid gap-1">
                <?php function infoRow($label, $value, $icon = null) { ?>
                    <div>
                        <!-- LABEL -->
                        <p class="text-xs text-app-muted mb-1"><?= e($label) ?></p>

                        <!-- VALOR + ICONO -->
                        <div class="flex items-center gap-2">
                            
                            <?php if ($icon): ?>
                                <span class="w-4 h-4 text-app-muted [&_svg]:w-4 [&_svg]:h-4">
                                    <?= ui_icon($icon) ?>
                                </span>
                            <?php endif; ?>

                            <p class="font-medium"><?= e($value) ?></p>
                        </div>
                    </div>
                <?php } ?>

                <?php infoRow('Documento', ($aprendiz['tipo_documento'] ?? '') . ' ' . ($aprendiz['numero_documento'] ?? ''), 'id-card'); ?>

                <?php infoRow('Teléfono', $aprendiz['telefono'] ?? 'Dato no registrado', 'phone'); ?>

                <?php infoRow('Email personal', $aprendiz['correo_personal'] ?? 'Dato no registrado', 'mail'); ?>

                <?php infoRow('Email institucional', $aprendiz['correo_institucional'] ?? 'Dato no registrado', 'mail'); ?>

                <?php infoRow('Dirección', $aprendiz['direccion_domicilio'] ?? 'Dato no registrado', 'map-pin'); ?>

                <?php infoRow('Alternativa etapa productiva', $aprendiz['alternativa_ep'] ?? 'Dato no registrado', 'briefcase'); ?>

                <?php infoRow('Grupo', $aprendiz['ficha'] ?? 'Dato no registrado', 'users'); ?>

                <?php infoRow('Instructor seguimiento', $aprendiz['nombre_instructor_seguimiento'] ?? 'Dato no registrado', 'user'); ?>

                <?php infoRow('Teléfono instructor seguimiento', $aprendiz['telefono_instructor_seguimiento'] ?? 'Dato no registrado', 'phone'); ?>

                <?php infoRow('Jefe de grupo', $aprendiz['jefe_grupo'] ?? 'Dato no registrado', 'user-check'); ?>

                <?php infoRow('Area de coordinacion', $aprendiz['coordinacion'] ?? 'Dato no registrado', 'layers'); ?>

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
            <section class="<?= e($cardPaddedClass) ?>">
                <div class="flex mb-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                        <span class=" text-app-link [&_svg]:w-5 [&_svg]:h-5">
                            <?= ui_icon('building') ?>
                        </span>
                        Empresa co-formadora
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-3">
                    <?php infoRow('Razón social', $aprendiz['empresa_nombre'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('NIT', $aprendiz['nit'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Dirección', $aprendiz['direccion'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Supervisor', $aprendiz['nombre_jefe'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Cargo', $aprendiz['cargo_jefe'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Correo supervisor', $aprendiz['correo_jefe'] ?? 'Dato no registrado', 'mail'); ?>
                    <?php infoRow('Teléfono contacto', $aprendiz['telefono_jefe'] ?? 'Dato no registrado'); ?>
                </div>
            </section>

            <!-- 🟨 MOMENTOS -->
            <section class="<?= e($cardPaddedClass) ?>">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="<?= e(ui_heading_sm_classes()) ?>">Documentos</h3>
                    <a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int)$aprendiz['id'] ?>&tipo=EX"
                       class="<?= e(ui_button_small_classes()) ?>">
                        + Agregar Momento extraordinario
                    </a>
                </div>

                <div class="space-y-3 md:col-span-2 grid max-h-[320px] gap-4 overflow-y-auto pr-1">
                    <div class="flex items-center justify-between rounded-lg border border-app-border p-4 gap-3">
                        <span class="text-sm font-medium">Información</span>
                        <a href="<?= e(APP_BASE_PATH) ?>/documentos/info?aprendiz_id=<?= (int)$aprendiz['id'] ?>"
                        class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                            <span class="w-5 h-5 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('file-spreadsheet') ?></span>
                            Abrir información
                        </a>
                    </div>
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
                                class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                                    
                                    <span class="w-5 h-5 [&_svg]:w-4 [&_svg]:h-4">
                                        <?= ui_icon('pencil') ?>
                                    </span>
                                    Editar momento
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- 🟪 ACCIONES -->
           
            <div class="grid mb:col-span-2 grid flex gap-3 mt-4">

                <a href="<?= e(APP_BASE_PATH) ?>/documentos/info?aprendiz_id=<?= (int)$aprendiz['id'] ?>"
                class="<?= e(ui_button_small_classes()) ?> flex items-center justify-center text-center gap-2">
                <span class="w-4 h-4 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('file-spreadsheet') ?></span>
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

