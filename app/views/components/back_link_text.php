<?php
declare(strict_types=1);

$url = trim((string) ($url ?? ''));
if ($url === '') {
    return;
}

$label = trim((string) ($label ?? 'Volver a importación'));
$extraClasses = trim((string) ($extraClasses ?? 'mb-4'));
?>
<a
    href="<?= e($url) ?>"
    class="<?= e($extraClasses) ?> inline-block text-sm font-medium text-app-link no-underline hover:underline"
>
    ← <?= e($label) ?>
</a>
