<?php
declare(strict_types=1);
?>
<div id="modal-confirm-action" class="hidden fixed inset-0 z-50 items-center justify-center p-4" data-modal-overlay="confirm-action">
    <div class="absolute inset-0 bg-app-text/40"></div>
    <div class="relative w-full max-w-md rounded-[10px] border border-app-border bg-app-panel p-6 shadow-xsSoft">
        <div class="min-w-0">
            <div class="flex items-center justify-between gap-3">
                <h3 class="m-0 text-lg font-semibold text-app-text" data-confirm-title>Confirmar acción</h3>
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-700 [&_svg]:h-5 [&_svg]:w-5">
                    <?= ui_icon('circle-alert') ?>
                </span>
            </div>
            <p class="mt-2 text-sm text-app-muted" data-confirm-message>¿Deseas continuar?</p>
        </div>
        <div class="mt-6 flex items-center justify-end gap-2">
            <button type="button" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2" data-modal-close="confirm-action" data-confirm-cancel>
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                Cancelar
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-md border border-rose-600 bg-rose-600 px-[18px] py-2 text-sm font-medium text-white no-underline transition-colors duration-200 hover:border-rose-700 hover:bg-rose-700 hover:text-white" data-confirm-accept>
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                Aceptar
            </button>
        </div>
    </div>
</div>
