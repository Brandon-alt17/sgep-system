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

$alertClasses = $count > 5
    ? 'rounded-[10px] border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700'
    : (string) ui_warning_card_classes();
?>
<section class="<?= e(trim($alertClasses . ' ' . $extraClasses)) ?>">
    <p class="m-0">
        Tienes <strong><?= e((string) $count) ?></strong> <?= e($subjectPlural) ?> <?= e($messageTail) ?>
        <a class="ml-1 font-semibold underline underline-offset-2 hover:no-underline" href="<?= e($manageUrl) ?>"><?= e($ctaText) ?></a>
    </p>
</section>

