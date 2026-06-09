<?php

declare(strict_types=1);

?>
<div
    class="sg-download-overlay hidden"
    data-download-overlay
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="sg-download-overlay-title"
>
    <div class="sg-download-overlay__panel" role="document">
        <p id="sg-download-overlay-title" class="m-0 text-sm font-medium text-app-text" data-download-overlay-label>
            Preparando descarga...
        </p>
        <div class="mt-3 flex items-center justify-between text-xs text-app-muted">
            <span data-download-overlay-status>Iniciando...</span>
            <span data-download-overlay-percent>0%</span>
        </div>
        <div class="mt-1.5 h-1.5 w-full rounded bg-app-panelSubtle">
            <div
                class="h-full w-0 rounded bg-app-accent transition-[width] duration-200 ease-out"
                data-download-overlay-bar
            ></div>
        </div>
    </div>
</div>
