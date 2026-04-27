<?php
declare(strict_types=1);

$action = (string) ($action ?? '#');
$buttonText = (string) ($buttonText ?? 'Guardar');
$fields = (array) ($fields ?? []);
$note = trim((string) ($note ?? ''));
$cancelText = trim((string) ($cancelText ?? ''));
$cancelHref = trim((string) ($cancelHref ?? '#'));
?>
<form method="post" action="<?= e($action) ?>" class="border border-app-border bg-app-panel shadow-xsSoft" style="position: fixed; left: 0; right: 0; bottom: 0; z-index: 9999; min-height: 70px;">
    <?php foreach ($fields as $name => $value): ?>
        <input type="hidden" name="<?= e((string) $name) ?>" value="<?= e((string) $value) ?>">
    <?php endforeach; ?>
    <div class="mx-auto flex min-h-[70px] w-full items-center justify-end gap-3 px-4 py-3">
        <?php if ($note !== ''): ?>
            <p class="m-0 text-xs text-app-muted"><?= $note ?></p>
        <?php endif; ?>
        <?php if ($cancelText !== ''): ?>
            <a class="<?= e(ui_button_small_classes()) ?> self-center justify-center" href="<?= e($cancelHref) ?>"><?= e($cancelText) ?></a>
        <?php endif; ?>
        <button class="<?= e(ui_button_primary_classes()) ?> self-center justify-center text-app-textOnBrand" type="submit"><?= e($buttonText) ?></button>
    </div>
</form>

