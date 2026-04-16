<?php partial('components/page_header', [
    'title' => 'Perfil del aprendiz',
    'subtitle' => 'Gestione datos personales, registro de momentos y generación documental.',
]); ?>

<section class="mb-4 <?= e(ui_card_classes()) ?>">
    <p class="m-0 text-sm"><strong>Nombre:</strong> <?= e((string) $aprendiz['nombre_completo']) ?></p>
    <p class="m-0 mt-1 text-sm"><strong>Documento:</strong> <?= e((string) $aprendiz['numero_documento']) ?></p>
    <p class="m-0 mt-1 text-sm"><strong>Estado:</strong> <?= e((string) $aprendiz['estado']) ?></p>
</section>

<h3 class="<?= e(ui_heading_sm_classes()) ?>">Actualizar perfil</h3>
<form method="post" action="<?= e(APP_BASE_PATH) ?>/aprendices/update" class="<?= e(ui_card_classes()) ?>">
    <input type="hidden" name="id" value="<?= (int) $aprendiz['id'] ?>">
    <div class="grid gap-4 md:grid-cols-2">
        <label class="<?= e(ui_label_classes()) ?>">Nombre
            <input type="text" name="nombre_completo" value="<?= e((string) $aprendiz['nombre_completo']) ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Teléfono
            <input type="text" name="telefono" value="<?= e((string) ($aprendiz['telefono'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Correo personal
            <input type="email" name="correo_personal" value="<?= e((string) ($aprendiz['correo_personal'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?>">Correo institucional
            <input type="email" name="correo_institucional" value="<?= e((string) ($aprendiz['correo_institucional'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
        <label class="<?= e(ui_label_classes()) ?> md:col-span-2">Estado
            <input type="text" name="estado" value="<?= e((string) $aprendiz['estado']) ?>" class="<?= e(ui_input_classes()) ?>">
        </label>
    </div>
    <button type="submit" class="mt-4 <?= e(ui_button_primary_classes()) ?>">Actualizar</button>
</form>

<section class="<?= e(ui_card_mt_classes()) ?>">
    <h3 class="<?= e(ui_heading_sm_classes()) ?>">Momentos</h3>
    <ul class="m-0 list-disc space-y-1 pl-5 text-sm">
        <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M1">Registrar M1</a></li>
        <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M2">Registrar M2</a></li>
        <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M3">Registrar M3</a></li>
        <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=EX">Registrar Extraordinario</a></li>
    </ul>
</section>

<section class="<?= e(ui_card_mt_classes()) ?>">
    <h3 class="<?= e(ui_heading_sm_classes()) ?>">Documentos</h3>
    <a class="<?= e(ui_button_primary_classes()) ?>" href="<?= e(APP_BASE_PATH) ?>/documentos/generar?aprendiz_id=<?= (int) $aprendiz['id'] ?>">Generar F-023</a>
</section>
