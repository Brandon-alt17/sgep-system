<?php partial('components/page_header', [
    'title' => 'Catálogo - Importar programa (PDF)',
    'subtitle' => 'Extracción semi-automática de competencias y resultados con revisión antes de guardar.',
]); ?>

<?php if (!empty($error ?? '')): ?>
    <section class="<?= e(ui_warning_card_classes()) ?> mb-4"><?= e((string) $error) ?></section>
<?php endif; ?>

<section class="<?= e(ui_card_classes()) ?>">
    <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/programas/importar/analizar" enctype="multipart/form-data" class="space-y-4">
        <label class="<?= e(ui_label_classes()) ?>">
            Archivo del programa (PDF o TXT)
            <input type="file" name="archivo_pdf" accept=".pdf,.txt" required class="<?= e(ui_input_classes()) ?>">
        </label>
        <button class="<?= e(ui_button_primary_classes()) ?>" type="submit">Analizar archivo</button>
    </form>
</section>
