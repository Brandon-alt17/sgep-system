<?php partial('components/page_header', [
    'title' => 'Error del servidor',
    'subtitle' => 'Ocurrió un problema al procesar la solicitud. Los datos no se modificaron de forma parcial en esta pantalla.',
]); ?>

<section class="<?= e(ui_card_classes()) ?> space-y-3">
    <p class="m-0 text-sm text-app-text">
        Si el problema persiste, revise que WAMP/MySQL esté activo y consulte el registro en
        <code class="rounded bg-app-panelSubtle px-2 py-0.5">storage/logs/error.log</code>.
    </p>
    <?php if (!empty($message) && defined('APP_DEBUG') && APP_DEBUG): ?>
        <p class="m-0 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
            <?= e((string) $message) ?>
        </p>
    <?php endif; ?>
    <p class="m-0">
        <a class="text-sm font-medium text-app-link no-underline hover:underline" href="<?= e(APP_BASE_PATH) ?>/dashboard">Volver al inicio</a>
    </p>
</section>
