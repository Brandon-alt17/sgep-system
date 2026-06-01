<?php partial('components/page_header', [
    'title' => 'Nuevo aprendiz',
    'subtitle' => 'Ingresa los datos del aprendiz.',
]); ?>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/aprendices"
      class="<?= e(ui_card_classes()) ?> p-6 space-y-6 max-w-5xl mx-auto">

    <!-- DATOS DEL APRENDIZ -->
    <div class="space-y-5">
        <div class="grid gap-3 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Nombre completo *</label>
                <input type="text" name="nombre_completo" required
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Tipo de documento *</label>
                <select name="tipo_documento" required
                        class="<?= e(ui_input_classes()) ?> h-10 text-sm w-full">
                    <option value="">Seleccionar...</option>
                    <option>C.C.</option>
                    <option>T.I.</option>
                    <option>C.E.</option>
                    <option>PPT</option>
                    <option>Pasaporte</option>
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-500">N° documento *</label>
                <input type="text" name="numero_documento" required
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm w-full">
            </div>

            <div>
                <label class="text-xs text-gray-500">Teléfono</label>
                <input type="text" name="telefono"
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Email personal</label>
                <input type="email" name="correo_personal"
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Email institucional</label>
                <input type="email" name="correo_institucional"
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Dirección</label>
                <input type="text" name="direccion"
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <!-- NUEVO: Ciudad de domicilio -->
            <div>
                <label class="text-xs text-gray-500">Ciudad de domicilio</label>
                <input type="text" name="ciudad_domicilio"
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>
        
            <div>
                <label class="text-xs text-gray-500">Ficha *</label>
                <input type="text" name="ficha" required
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <!-- NUEVO: Jefe de grupo -->
            <div>
                <label class="text-xs text-gray-500">Jefe de grupo</label>
                <input type="text" name="jefe_grupo"
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Fecha registro Sofía Plus</label>
                <input type="text" name="fecha_registro" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off"
                       class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Alternativa etapa productiva</label>
                <select name="alternativa"
                        class="<?= e(ui_input_classes()) ?> h-10 text-sm w-full">
                    <option>Contrato de aprendizaje</option>
                    <option>Pasantía</option>
                    <option>Proyecto productivo</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 🔘 BOTONES -->
    <div class="flex justify-end items-center gap-3 pt-6 border-t">
        <a href="<?= e(APP_BASE_PATH) ?>/aprendices"
           class="text-sm text-gray-500 hover:text-gray-700 h-10 flex items-center">
            Cancelar
        </a>
        <button type="submit"
                class="<?= e(ui_button_primary_classes()) ?> h-10 px-6 text-sm flex items-center">
            Crear aprendiz
        </button>
    </div>
</form>