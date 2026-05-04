<?php
declare(strict_types=1);

$message = trim((string) ($message ?? ''));
?>
<section class="pointer-events-none fixed bottom-6 right-6 z-[65] w-full max-w-sm px-4 sm:px-0" data-toast-root>
    <div class="sg-toast" data-toast data-toast-message="<?= e($message) ?>" role="status" aria-live="polite">
        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-app-text text-white [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('check') ?></span>
        <p class="m-0 text-sm text-app-text"><?= e($message) ?></p>
    </div>
</section>
