<?php
declare(strict_types=1);

$message = trim((string) ($message ?? ''));
$variant = (string) ($variant ?? 'success');
$isError = $variant === 'error';
?>
<section class="pointer-events-none fixed bottom-6 right-6 z-[65] w-full max-w-sm px-4 sm:px-0" data-toast-root>
    <div class="sg-toast<?= $isError ? ' sg-toast--error' : '' ?>" data-toast data-toast-message="<?= e($message) ?>" data-toast-ms="<?= $isError ? '7000' : '3200' ?>" role="<?= $isError ? 'alert' : 'status' ?>" aria-live="<?= $isError ? 'assertive' : 'polite' ?>">
        <?php if ($isError): ?>
            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-600 text-white [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('circle-alert') ?></span>
        <?php else: ?>
            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-app-text text-white [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('check') ?></span>
        <?php endif; ?>
        <p class="m-0 max-h-48 min-w-0 flex-1 overflow-y-auto text-sm text-app-text"><?= e($message) ?></p>
    </div>
</section>
