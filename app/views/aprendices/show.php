<?php partial('components/page_header', [
    'title' => 'Perfil del aprendiz',
    'subtitle' => 'Gestione datos personales, registro de momentos y generación documental.',
]); ?>

<section class="mb-4 rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
    <p class="m-0 text-sm"><strong>Nombre:</strong> <?= e((string) $aprendiz['nombre_completo']) ?></p>
    <p class="m-0 mt-1 text-sm"><strong>Documento:</strong> <?= e((string) $aprendiz['numero_documento']) ?></p>
    <p class="m-0 mt-1 text-sm"><strong>Estado:</strong> <?= e((string) $aprendiz['estado']) ?></p>
</section>

<h3 class="mb-2 mt-0 text-[15px] font-semibold">Actualizar perfil</h3>
<form method="post" action="<?= e(APP_BASE_PATH) ?>/aprendices/update" class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
    <input type="hidden" name="id" value="<?= (int) $aprendiz['id'] ?>">
    <div class="grid gap-4 md:grid-cols-2">
        <label class="block text-sm font-medium text-gray-700">Nombre
            <input type="text" name="nombre_completo" value="<?= e((string) $aprendiz['nombre_completo']) ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </label>
        <label class="block text-sm font-medium text-gray-700">Teléfono
            <input type="text" name="telefono" value="<?= e((string) ($aprendiz['telefono'] ?? '')) ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </label>
        <label class="block text-sm font-medium text-gray-700">Correo personal
            <input type="email" name="correo_personal" value="<?= e((string) ($aprendiz['correo_personal'] ?? '')) ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </label>
        <label class="block text-sm font-medium text-gray-700">Correo institucional
            <input type="email" name="correo_institucional" value="<?= e((string) ($aprendiz['correo_institucional'] ?? '')) ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </label>
        <label class="block text-sm font-medium text-gray-700 md:col-span-2">Estado
            <input type="text" name="estado" value="<?= e((string) $aprendiz['estado']) ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </label>
    </div>
    <button type="submit" class="mt-4 inline-flex rounded-md border border-app-accent bg-app-accent px-4 py-2 text-sm font-medium text-white hover:bg-[#0e8f82]">Actualizar</button>
</form>

<section class="mt-4 rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
    <h3 class="mb-2 mt-0 text-[15px] font-semibold">Momentos</h3>
    <ul class="m-0 list-disc space-y-1 pl-5 text-sm">
        <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M1">Registrar M1</a></li>
        <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M2">Registrar M2</a></li>
        <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=M3">Registrar M3</a></li>
        <li><a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=EX">Registrar Extraordinario</a></li>
    </ul>
</section>

<section class="mt-4 rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
    <h3 class="mb-2 mt-0 text-[15px] font-semibold">Documentos</h3>
    <a class="inline-flex rounded-md border border-app-accent bg-app-accent px-4 py-2 text-sm font-medium text-white no-underline hover:bg-[#0e8f82]" href="<?= e(APP_BASE_PATH) ?>/documentos/generar?aprendiz_id=<?= (int) $aprendiz['id'] ?>">Generar F-023</a>
</section>
