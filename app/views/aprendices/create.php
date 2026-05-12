<?php partial('components/page_header', [
    'title' => 'Nuevo aprendiz',
    'subtitle' => 'Ingresa los datos del aprendiz y de la empresa co-formadora.',
]); ?>

<form method="post" action="<?= e(APP_BASE_PATH) ?>/aprendices"
      class="<?= e(ui_card_classes()) ?> p-6 space-y-6 max-w-5xl mx-auto">

    <!-- 🔘 TABS MODERNOS -->
    <div class="flex justify-center">

        <!-- 👤 APRENDIZ -->
        <button type="button" id="tab-aprendiz"
            class="tab-btn flex p-6 justify-center items-center py-3 text-sm font-medium border border-gray-700 text-primary bg-white transition">
            Datos del aprendiz
        </button>

        <!-- 🏢 EMPRESA -->
        <button type="button" id="tab-empresa"
            class="tab-btn flex p-6 justify-center items-center py-3 text-sm font-medium border border-gray-700 text-gray-500 bg-gray-50 transition">
            Empresa co-formadora
        </button>

    </div>

    <!-- ===================== -->
    <!-- 👤 APRENDIZ -->
    <!-- ===================== -->
    <div id="contenido-aprendiz" class="space-y-5">

        <h3 class="text-sm font-semibold text-gray-700">
            Información personal
        </h3>

        <div class="grid gap-3 md:grid-cols-2">

            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Nombre completo *</label>
                <input type="text" name="nombre_completo" required
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                    <label class="text-xs text-gray-500">Tipo documento</label>
                    <select name="tipo_documento"
                        class="<?= e(ui_input_classes()) ?> h-10 text-sm w-full">
                        <option>C.C.</option>
                        <option>T.I.</option>
                        <option>C.E.</option>
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
        </div>

        <h3 class="text-sm font-semibold text-gray-700 pt-2">
            Información académica
        </h3>

        <div class="grid gap-3 md:grid-cols-2">

            <div>
                <label class="text-xs text-gray-500">Ficha *</label>
                <input type="text" name="ficha"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Fecha registro Sofía Plus</label>
                <input type="text" name="fecha_registro" value="" placeholder="dd/mm/aaaa" title="Formato día/mes/año (dd/mm/aaaa)" inputmode="numeric" maxlength="10" spellcheck="false" autocomplete="off"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Alternativa etapa productiva</label>
                <select name="alternativa"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
                    <option>Contrato de aprendizaje</option>
                    <option>Pasantía</option>
                    <option>Proyecto productivo</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ===================== -->
    <!-- 🏢 EMPRESA -->
    <!-- ===================== -->
    <div id="contenido-empresa" class="space-y-5 hidden">

        <h3 class="text-sm font-semibold text-gray-700">
            Información de la empresa
        </h3>

        <div class="grid gap-3 md:grid-cols-2">

            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Razón social</label>
                <input type="text" name="razon_social"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">NIT</label>
                <input type="text" name="nit"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Dirección</label>
                <input type="text" name="direccion_empresa"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Supervisor</label>
                <input type="text" name="supervisor"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500">Cargo del supervisor</label>
                <input type="text" name="cargo_supervisor"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
            </div>

            <div class="md:col-span-2">
                <label class="text-xs text-gray-500">Teléfono de contacto</label>
                <input type="text" name="telefono_empresa"
                    class="<?= e(ui_input_classes()) ?> h-10 text-sm">
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

<!-- ⚡ TABS SCRIPT -->
<script>
const tabAprendiz = document.getElementById('tab-aprendiz');
const tabEmpresa = document.getElementById('tab-empresa');

const contAprendiz = document.getElementById('contenido-aprendiz');
const contEmpresa = document.getElementById('contenido-empresa');

function activarAprendiz() {
    contAprendiz.classList.remove('hidden');
    contEmpresa.classList.add('hidden');

    tabAprendiz.classList.add('border-primary','text-primary','bg-white');
    tabAprendiz.classList.remove('text-gray-500','bg-gray-50');

    tabEmpresa.classList.remove('border-primary','text-primary','bg-white');
    tabEmpresa.classList.add('text-gray-500','bg-gray-50','border-transparent');
}

function activarEmpresa() {
    contEmpresa.classList.remove('hidden');
    contAprendiz.classList.add('hidden');

    tabEmpresa.classList.add('border-primary','text-primary','bg-white');
    tabEmpresa.classList.remove('text-gray-500','bg-gray-50');

    tabAprendiz.classList.remove('border-primary','text-primary','bg-white');
    tabAprendiz.classList.add('text-gray-500','bg-gray-50','border-transparent');
}

tabAprendiz.onclick = activarAprendiz;
tabEmpresa.onclick = activarEmpresa;
</script>