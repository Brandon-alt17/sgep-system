<?php
declare(strict_types=1);

$count = max(0, (int) ($count ?? 0));
if ($count <= 0) {
    return;
}

$manageUrl = (string) ($manageUrl ?? '#');
$subjectPlural = trim((string) ($subjectPlural ?? 'registros'));
$messageTail = trim((string) ($messageTail ?? 'pendientes de enlazar.'));
$ctaText = trim((string) ($ctaText ?? 'Gestionar ahora'));
$extraClasses = trim((string) ($extraClasses ?? ''));

$alertClasses = ui_pending_enlace_alert_section_classes($count);
?>
<section class="<?= e(trim($alertClasses . ' ' . $extraClasses)) ?>">
    <p class="m-0">
        Tienes <strong><?= e((string) $count) ?></strong> <?= e($subjectPlural) ?> <?= e($messageTail) ?>
        <a class="ml-1 font-semibold underline underline-offset-2 hover:no-underline" href="<?= e($manageUrl) ?>"><?= e($ctaText) ?></a>
    </p>
</section>

