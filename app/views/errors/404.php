<?php partial('components/page_header', [
    'title' => 'Error 404',
    'subtitle' => 'La ruta solicitada no existe. Esta pantalla ya está preparada para un diseño futuro.',
]); ?>

<section class="<?= e(ui_card_classes()) ?>">
    <?php if (!empty($message)): ?>
        <p class="m-0 mb-3 text-sm text-app-text"><?= e((string) $message) ?></p>
    <?php endif; ?>
    <p class="m-0 text-sm text-app-textSubtle">No se encontró la ruta: <code class="rounded bg-app-panelSubtle px-2 py-0.5"><?= e((string) ($uri ?? '')) ?></code></p>
</section>
