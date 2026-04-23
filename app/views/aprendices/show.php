<?php partial('components/page_header', [
    'title' => 'Perfil del aprendiz',
    'subtitle' => '',
]); ?>

<div class="space-y-6 md:col-span-2 grid gap-4">
    <div class="inline-flex items-end justify-between mb-4">
        <!-- 🔙 VOLVER -->
        <a href="<?= e(APP_BASE_PATH) ?>/aprendices"
        class="items-center text-sm text-app-link hover:underline">
            ← Listado
        </a>

        <a href="<?= e(APP_BASE_PATH) ?>/aprendices/edit?id=<?= (int)$aprendiz['id'] ?>"
        class="<?= e(ui_button_small_classes()) ?> inline-flex items-end gap-2">
            
            <span class="w-4 h-4 [&_svg]:w-4 [&_svg]:h-4">
                <?= ui_icon('user-round') ?>
            </span>
            Editar datos del aprendiz
        </a>
    </div>

    <!-- 🧩 GRID PRINCIPAL -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 🟢 PERFIL -->
        <section class="<?= e(ui_card_classes()) ?>">
            <div class="flex mb-4">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <span class="flex items-center justify-center text-app-link w-[30px] h-[30px] [&_svg]:w-5 [&_svg]:h-5">
                        <?= ui_icon('user') ?>
                    </span>
                    <span><?= e($aprendiz['nombre_completo']) ?></span>
                </h2>
            </div>

            <div class="space-y-3 text-sm md:col-span-2 grid gap-4">
                <?php function infoRow($label, $value) { ?>
                    <div>
                        <p class="text-xs text-app-muted"><?= e($label) ?></p>
                        <p class="font-medium"><?= e($value) ?></p>
                    </div>
                <?php } ?>

                <?php infoRow('Documento',($aprendiz['tipo_documento'] ?? '') . ' ' . ($aprendiz['numero_documento'] ?? '')); ?>
                <?php infoRow('Teléfono', $aprendiz['telefono'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Email personal', $aprendiz['correo_personal'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Email institucional', $aprendiz['correo_institucional'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Dirección', $aprendiz['direccion_domicilio'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Alternativa etapa productiva', $aprendiz['alternativa_ep'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Grupo', $aprendiz['ficha'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Instructor seguimiento', $aprendiz['nombre_instructor_seguimiento'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Teléfono instructor seguimiento', $aprendiz['telefono_instructor_seguimiento'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Jefe de grupo', $aprendiz['jefe_grupo'] ?? 'Dato no registrado'); ?>
                <?php infoRow('Area de coordinacion', $aprendiz['coordinacion'] ?? 'Dato no registrado'); ?>

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
                <div class="inline-flex items-end gap-3">
                    <span class="text-app-link w-[30px] h-[30px] flex items-end [&_svg]:w-5 [&_svg]:h-5">
                        <?= ui_icon('building') ?>
                    </span>
                    <h3 class="<?= e(ui_heading_sm_classes()) ?>">
                        Empresa co-formadora
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-3">
                    <?php infoRow('Razón social', $aprendiz['empresa_nombre'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('NIT', $aprendiz['nit'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Dirección', $aprendiz['direccion'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Supervisor', $aprendiz['nombre_jefe'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Cargo', $aprendiz['cargo_jefe'] ?? 'Dato no registrado'); ?>
                    <?php infoRow('Teléfono contacto', $aprendiz['telefono_jefe'] ?? 'Dato no registrado'); ?>
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

<!-- MODAL EDITAR -->
<div id="modal-editar-aprendiz" class="hidden fixed inset-0 z-50 flex items-center justify-center">

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50"></div>

    <!-- Contenido -->
    <div id="modal-content" class="relative bg-white rounded-2xl shadow-lg w-full max-w-4xl p-6 transition-all duration-300">

        <!-- Header -->
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-lg font-semibold">
                    Editar datos — <?= e($aprendiz['nombre_completo']) ?>
                </h2>
                <p class="text-sm text-app-muted">
                    Actualiza la información personal del aprendiz y de la empresa co-formadora.
                </p>
            </div>

            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                ✕
            </button>
        </div>

        <!-- Tabs -->
        <div class="flex gap-4 border-b mb-4">
            <button onclick="cambiarTab('aprendiz')" id="tab-aprendiz"
                class="pb-2 border-b-2 border-app-link font-medium">
                Datos del aprendiz
            </button>

            <button onclick="cambiarTab('empresa')" id="tab-empresa"
                class="pb-2 text-app-muted">
                Empresa co-formadora
            </button>
        </div>

        <!-- FORM -->
        <form method="POST" action="<?= e(APP_BASE_PATH) ?>/aprendices/update">

            <!-- ===================== -->
            <!-- DATOS APRENDIZ -->
            <!-- ===================== -->
            <div id="contenido-aprendiz" class="grid grid-cols-2 gap-4">

                <input type="hidden" name="id" value="<?= (int)$aprendiz['id'] ?>">

                <div>
                    <label class="text-sm text-app-muted">Nombre completo</label>
                    <input type="text" name="nombre_completo" value="<?= e($aprendiz['nombre_completo']) ?>" class="input">
                </div>

                <div>
                    <label class="text-sm text-app-muted">Tipo de documento</label>
                    <input type="text" name="tipo_documento" value="<?= e($aprendiz['tipo_documento']) ?>" class="input">
                </div>

                <div>
                    <label class="text-sm text-app-muted">N° documento</label>
                    <input type="text" name="numero_documento" value="<?= e($aprendiz['numero_documento']) ?>" class="input">
                </div>

                <div>
                    <label class="text-sm text-app-muted">Teléfono</label>
                    <input type="text" name="telefono" value="<?= e($aprendiz['telefono']) ?>" class="input">
                </div>

                <!-- agrega los demás campos -->
            </div>

            <!-- ===================== -->
            <!-- DATOS EMPRESA -->
            <!-- ===================== -->
            <div id="contenido-empresa" class="hidden grid grid-cols-2 gap-4">

                <div>
                    <label class="text-sm text-app-muted">Nombre</label>
                    <input type="text" name="empresa_nombre" value="<?= e($aprendiz['empresa_nombre']) ?>" class="input">
                </div>

                <div>
                    <label class="text-sm text-app-muted">NIT</label>
                    <input type="text" name="nit" value="<?= e($aprendiz['nit']) ?>" class="input">
                </div>

                <div>
                    <label class="text-sm text-app-muted">Dirección</label>
                    <input type="text" name="direccion" value="<?= e($aprendiz['direccion']) ?>" class="input">
                </div>

                <div>
                    <label class="text-sm text-app-muted">Supervisor</label>
                    <input type="text" name="nombre_jefe" value="<?= e($aprendiz['nombre_jefe']) ?>" class="input">
                </div>

                <!-- demás campos -->
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModal()" class="text-sm text-gray-500">
                    Cancelar
                </button>

                <button type="submit" class="<?= e(ui_button_classes()) ?>">
                    Guardar cambios
                </button>
            </div>

        </form>
    </div>
</div>