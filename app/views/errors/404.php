<?php partial('components/page_header', [
    'title' => 'Error 404',
    'subtitle' => 'La ruta solicitada no existe. Esta pantalla ya está preparada para un diseño futuro.',
]); ?>

<section class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
    <p class="m-0 text-sm text-gray-700">No se encontró la ruta: <code class="rounded bg-gray-100 px-2 py-0.5"><?= e((string) ($uri ?? '')) ?></code></p>
</section>
