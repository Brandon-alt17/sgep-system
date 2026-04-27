<?php
declare(strict_types=1);

$title = (string) ($title ?? 'Cargar archivo');
$description = (string) ($description ?? '');
$action = (string) ($action ?? '#');
$inputName = (string) ($inputName ?? 'archivo');
$accept = (string) ($accept ?? '');
$required = (bool) ($required ?? true);
$autoSend = (bool) ($autoSend ?? true);
$dropzonePrompt = (string) ($dropzonePrompt ?? 'Arrastre el archivo aquí o haga clic para seleccionar');
$dropzoneHint = (string) ($dropzoneHint ?? '');
$triggerText = (string) ($triggerText ?? 'Seleccionar archivo');
$emptyFilenameText = (string) ($emptyFilenameText ?? 'Sin archivo seleccionado');
$submitText = (string) ($submitText ?? 'Enviar');
$showSubmit = (bool) ($showSubmit ?? false);
$showProgress = (bool) ($showProgress ?? false);
?>
<article class="<?= e(ui_card_surface_classes()) ?>">
    <div class="<?= e(ui_card_header_classes()) ?>">
        <div class="<?= e(ui_card_header_stack_classes()) ?>">
            <h3 class="<?= e(ui_card_title_classes()) ?>"><?= e($title) ?></h3>
            <?php if ($description !== ''): ?>
                <p class="<?= e(ui_card_description_classes()) ?>"><?= e($description) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="<?= e(ui_card_body_classes()) ?>">
        <form
            method="post"
            action="<?= e($action) ?>"
            enctype="multipart/form-data"
            data-import-form
            data-import-autosend="<?= $autoSend ? 'true' : 'false' ?>"
            class="space-y-3"
        >
            <input class="sr-only" data-import-input type="file" name="<?= e($inputName) ?>"<?= $accept !== '' ? ' accept="' . e($accept) . '"' : '' ?><?= $required ? ' required' : '' ?>>
            <div class="upload-dropzone-dashed grid min-h-[122px] place-items-center rounded-lg bg-app-panel p-10 text-center transition-colors duration-150 cursor-pointer" data-import-dropzone>
                <div>
                    <div class="mx-auto mb-3 h-[40px] w-[40px] text-app-muted [&_svg]:h-[40px] [&_svg]:w-[40px]"><?= ui_icon('upload') ?></div>
                    <strong class="text-sm font-medium"><?= e($dropzonePrompt) ?></strong>
                    <?php if ($dropzoneHint !== ''): ?>
                        <small class="mt-0.5 block text-xs text-app-mutedSoft"><?= e($dropzoneHint) ?></small>
                    <?php endif; ?>
                    <button type="button" class="mt-2 <?= e(ui_button_small_classes()) ?> text-app-text" data-import-trigger><?= e($triggerText) ?></button>
                    <small class="mt-0.5 block text-xs text-app-mutedSoft" data-import-filename><?= e($emptyFilenameText) ?></small>
                </div>
            </div>
            <?php if ($showProgress): ?>
                <div class="mt-3 hidden" data-import-progress>
                    <div class="mb-1 flex items-center justify-between text-xs text-app-muted">
                        <span data-import-progress-label>Preparando carga...</span>
                        <span data-import-progress-value>0%</span>
                    </div>
                    <div class="h-1.5 w-full rounded bg-app-panelSubtle">
                        <div class="h-full w-0 rounded bg-app-accent" data-import-progress-bar></div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($showSubmit): ?>
                <button class="<?= e(ui_button_primary_classes()) ?>" type="submit"><?= e($submitText) ?></button>
            <?php endif; ?>
        </form>
    </div>
</article>

