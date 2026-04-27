<section class="bg-app-bg flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-2.5 self-start" href="<?= e(APP_BASE_PATH) ?>/catalogo/programas" aria-label="Volver a programas">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5 "><?= ui_icon('arrow') ?></span>
    </a>
    <div class="mb-3 flex flex-col gap-2 pl-4">
        <h2 class="m-0 text-2xl font-semibold text-app-text">Importar programa</h2>
        <p class="m-0 text-sm text-app-muted">Extracción semi-automática de competencias y resultados con revisión antes de guardar.</p>
    </div>
</section>

<?php if (!empty($error ?? '')): ?>
    <section class="<?= e(ui_warning_card_classes()) ?> mb-4"><?= e((string) $error) ?></section>
<?php endif; ?>

<section class="mt-4">
    <?php
    partial('components/upload_dropzone_card', array_merge(
        ui_upload_dropzone_preset('programa'),
        ['action' => APP_BASE_PATH . '/catalogo/programas/importar/analizar']
    ));
    ?>
</section>
