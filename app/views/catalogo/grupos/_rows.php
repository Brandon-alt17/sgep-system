<?php
declare(strict_types=1);

$grupos = (array) ($grupos ?? []);
$defaultTdClasses = function_exists('ui_td_classes')
    ? ui_td_classes()
    : 'border-b border-app-borderSoft px-2 text-left text-sm align-middle transition-colors duration-200 ease-in-out';
$tdClasses = (string) ($tdClasses ?? $defaultTdClasses);
?>
<?php if ($grupos === []): ?>
    <tr>
        <td class="<?= e($tdClasses) ?> py-4 text-center" colspan="4">No hay grupos (fichas) registrados.</td>
    </tr>
<?php else: ?>
    <?php foreach ($grupos as $grupo): ?>
        <?php
        $ficha = (string) ($grupo['ficha'] ?? '');
        $pid = $grupo['programa_id'] ?? null;
        $qAprendices = ['ficha' => $ficha];
        if ($pid !== null && (int) $pid > 0) {
            $qAprendices['programa_id'] = (string) (int) $pid;
        }
        $aprendicesUrl = APP_BASE_PATH . '/aprendices?' . http_build_query($qAprendices);
        ?>
        <tr>
            <td class="<?= e($tdClasses) ?> py-3 px-4 pl-6 align-top font-mono"><?= e($ficha) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 whitespace-normal break-words leading-6 align-top"><?= e((string) ($grupo['programa_nombre'] ?? '')) ?: '—' ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 align-top"><?= (int) ($grupo['aprendices_count'] ?? 0) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 pr-6 align-top">
                <a class="<?= e(ui_button_small_classes()) ?>" href="<?= e($aprendicesUrl) ?>">
                    Ver aprendices
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
