<?php

declare(strict_types=1);

$items = [
    ['label' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'dashboard'],
    ['label' => 'Aprendices', 'path' => '/aprendices', 'icon' => 'aprendices'],
    ['label' => 'Importar', 'path' => '/importar', 'icon' => 'importar'],
    ['label' => 'Reportes', 'path' => '/reportes/maestro', 'icon' => 'reportes'],
    ['label' => 'Configuración', 'path' => '/documentos/generar', 'icon' => 'config'],
];
?>
<aside class="hidden border-r border-app-border bg-app-sidebar md:flex md:flex-col">
    <div class="border-b border-app-border px-[18px] py-4 font-bold text-app-brand">SGEP</div>
    <nav class="grid gap-0.5 p-2" aria-label="Navegación principal">
        <?php foreach ($items as $item): ?>
            <?php $active = str_starts_with((string) ($currentPath ?? ''), $item['path']); ?>
            <?php
            $classes = 'flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-app-muted hover:bg-app-navHover hover:no-underline';
            if ($active) {
                $classes .= ' bg-app-accentSoft font-semibold text-app-accentStrong';
            }
            ?>
            <a class="<?= e($classes) ?>" href="<?= e(APP_BASE_PATH . $item['path']) ?>">
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4 [&_svg]:fill-current"><?= ui_icon($item['icon']) ?></span>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <footer class="mt-auto border-t border-app-border px-3 py-2.5 text-[11px] text-app-muted">SENA - Regional Risaralda</footer>
</aside>
