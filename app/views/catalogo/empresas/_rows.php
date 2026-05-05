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
        <?php
        $eid = (int) ($empresa['id'] ?? 0);
        $aprendicesUrl = APP_BASE_PATH . '/aprendices?' . http_build_query([
            'empresa_id' => (string) $eid,
            'from' => 'empresas',
        ]);
        ?>
        <tr>
            <td class="<?= e($tdClasses) ?> py-3 px-4 pl-6 align-top font-medium"><?= e((string) ($empresa['nombre'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 align-top font-mono text-sm"><?= e((string) ($empresa['nit'] ?? '')) ?: '—' ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 align-top"><?= e((string) ($empresa['ciudad'] ?? '')) ?: '—' ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 align-top"><?= (int) ($empresa['aprendices_count'] ?? 0) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 pr-6 align-top">
                <div class="flex flex-wrap items-center gap-2">
                    <a class="<?= e(ui_button_small_classes()) ?>" href="<?= e($aprendicesUrl) ?>">
                        Ver aprendices
                    </a>
                    <a class="<?= e(ui_button_small_classes()) ?>" href="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/editar?id=<?= $eid ?>">
                        Editar
                    </a>
                    <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/eliminar" class="inline">
                        <input type="hidden" name="empresa_id" value="<?= $eid ?>">
                        <button
                            type="submit"
                            class="<?= e(ui_button_small_classes()) ?> border-rose-200 text-rose-700 hover:border-rose-400 hover:bg-rose-50"
                            data-confirm-modal
                            data-confirm-title="Eliminar empresa"
                            data-confirm-message="¿Eliminar esta empresa? Solo se permite si no tiene aprendices vinculados."
                        >Eliminar</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
