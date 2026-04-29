<?php
declare(strict_types=1);

$programas = (array) ($programas ?? []);
$defaultTdClasses = function_exists('ui_td_classes')
    ? ui_td_classes()
    : 'border-b border-app-borderSoft px-2 text-left text-sm align-middle transition-colors duration-200 ease-in-out';
$tdClasses = (string) ($tdClasses ?? $defaultTdClasses);

if (!function_exists('normalize_programa_nombre_for_view')) {
    function normalize_programa_nombre_for_view(string $name): string
    {
        $value = trim($name);
        if ($value === '') {
            return $value;
        }

        // Inserta espacios en camelCase/PascalCase y reemplaza separadores comunes.
        $value = preg_replace('/(?<=\p{Ll})(\p{Lu})/u', ' $1', $value) ?? $value;
        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        return function_exists('mb_convert_case')
            ? mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8')
            : ucwords($lower);
    }
}
?>
<?php if ($programas === []): ?>
    <tr>
        <td class="<?= e($tdClasses) ?> py-4 text-center" colspan="4">No hay programas registrados.</td>
    </tr>
<?php else: ?>
    <?php foreach ($programas as $programa): ?>
        <?php
        $horasTotal = trim((string) ($programa['horas_total'] ?? ''));
        $horasDisplay = $horasTotal !== '' ? $horasTotal . ' h' : 'N/D';
        $nombrePrograma = normalize_programa_nombre_for_view((string) ($programa['nombre'] ?? ''));
        $codigoPrograma = trim((string) ($programa['codigo'] ?? ''));
        ?>
        <tr>
            <td class="<?= e($tdClasses) ?> py-3 px-4 pl-6 align-top"><span class="ui-monospace font-mono tracking-wide"><?= e($codigoPrograma) ?></span></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 whitespace-normal break-words leading-6 align-top"><?= e($nombrePrograma) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 align-top"><?= e((string) ($programa['nivel'] ?? '')) ?></td>
            <td class="<?= e($tdClasses) ?> py-3 px-4 pr-6 align-top"><?= e($horasDisplay) ?></td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>

