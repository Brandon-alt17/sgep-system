<?php
declare(strict_types=1);

$programas = (array) ($programas ?? []);
$defaultTdClasses = function_exists('ui_td_classes')
    ? ui_td_classes()
    : 'border-b border-app-borderSoft px-2 text-left text-sm align-middle transition-colors duration-200 ease-in-out';
$tdClasses = (string) ($tdClasses ?? $defaultTdClasses);
?>
<?php if ($programas === []): ?>
    <tr>
        <td class="<?= e($tdClasses) ?> py-4 text-center" colspan="5">No hay programas registrados.</td>
    </tr>
<?php else: ?>
    <?php foreach ($programas as $programa): ?>
        <tr>
            <td class="<?= e($tdClasses) ?> py-3 align-top"><?= e((string) ($programa['codigo'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 whitespace-normal break-words leading-6 align-top"><?= e((string) ($programa['nombre'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 align-top"><?= e((string) ($programa['nivel'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 align-top">-</td>
            <td class="<?= e($tdClasses) ?> py-3 align-top"><?= e((string) ($programa['modalidad'] ?? '')) ?></td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>

