<?php
declare(strict_types=1);

$empresas = (array) ($empresas ?? []);
$defaultTdClasses = function_exists('ui_td_classes')
    ? ui_td_classes()
    : 'border-b border-app-borderSoft px-2 text-left text-sm align-middle transition-colors duration-200 ease-in-out';
$tdClasses = (string) ($tdClasses ?? $defaultTdClasses);
?>
<?php if ($empresas === []): ?>
    <tr>
        <td class="<?= e($tdClasses) ?> py-4 text-center" colspan="5">No hay empresas registradas.</td>
    </tr>
<?php else: ?>
    <?php foreach ($empresas as $empresa): ?>
        <?php $eid = (int) ($empresa['id'] ?? 0); ?>
        <tr>
            <td class="<?= e($tdClasses) ?> py-3 px-4 pl-6 align-top font-medium"><?= e((string) ($empresa['nombre'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 align-top font-mono text-sm"><?= e((string) ($empresa['nit'] ?? '')) ?: '—' ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 align-top"><?= e((string) ($empresa['ciudad'] ?? '')) ?: '—' ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 align-top"><?= (int) ($empresa['aprendices_count'] ?? 0) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 pr-6 align-top">
                <a class="<?= e(ui_button_small_classes()) ?>" href="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/ver?id=<?= $eid ?>">
                    Información empresa
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
