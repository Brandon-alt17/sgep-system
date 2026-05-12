<?php
declare(strict_types=1);

$message = trim((string) ($message ?? ''));
$variant = (string) ($variant ?? 'success');
$isError = $variant === 'error';
$isWarning = $variant === 'warning';
$positionClass = trim((string) ($positionClass ?? 'bottom-6 right-6 z-[65]'));
$toastClass = 'sg-toast';
if ($isError) {
    $toastClass .= ' sg-toast--error';
} elseif ($isWarning) {
    $toastClass .= ' sg-toast--warning';
}
$toastMs = $isError ? '7000' : ($isWarning ? '4200' : '3200');
$toastRootId = trim((string) ($toastRootId ?? ''));
?>
<section<?= $toastRootId !== '' ? ' id="' . e($toastRootId) . '"' : '' ?> class="pointer-events-none fixed <?= e($positionClass) ?> w-full max-w-sm px-4 sm:px-0" data-toast-root>
    <div class="<?= e($toastClass) ?>" data-toast data-toast-message="<?= e($message) ?>" data-toast-ms="<?= e($toastMs) ?>" role="<?= $isError ? 'alert' : 'status' ?>" aria-live="<?= $isError ? 'assertive' : 'polite' ?>">
        <?php if ($isError): ?>
            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-rose-600 text-white [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('circle-alert') ?></span>
        <?php elseif ($isWarning): ?>
            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('triangle-alert') ?></span>
        <?php else: ?>
            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('check') ?></span>
        <?php endif; ?>
        <p class="m-0 max-h-48 min-w-0 flex-1 overflow-y-auto text-sm text-app-text"><?= e($message) ?></p>
    </div>
</section>
