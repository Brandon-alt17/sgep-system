<?php

declare(strict_types=1);

$items = [
    ['label' => 'Dashboard', 'path' => '/dashboard', 'icon' => 'dashboard'],
    ['label' => 'Aprendices', 'path' => '/aprendices', 'icon' => 'users'],
    ['label' => 'Importar', 'path' => '/importar', 'icon' => 'upload'],
    ['label' => 'Reportes', 'path' => '/reportes/maestro', 'icon' => 'file-spreadsheet'],
    ['label' => 'Configuración', 'path' => '/documentos/generar', 'icon' => 'settings'],
];
?>
<aside class="hidden border-r border-app-border bg-app-sidebar md:flex md:flex-col">
    <div class="border-b border-app-border px-[24px] py-[18.5px] text-lg font-bold leading-none text-app-brand">SGEP</div>
    <nav class="grid gap-1 p-3" aria-label="Navegación principal">
        <?php foreach ($items as $item): ?>
            <?php
            $normalizedCurrent = rtrim((string) ($currentPath ?? '/'), '/') ?: '/';
            $normalizedItem = rtrim((string) $item['path'], '/') ?: '/';
            $active = $normalizedItem === '/'
                ? ($normalizedCurrent === '/' || $normalizedCurrent === '/dashboard')
                : ($normalizedCurrent === $normalizedItem || str_starts_with($normalizedCurrent, $normalizedItem . '/'));
            ?>
            <?php
            // Transición suave en fondo y color (hover y activo).
            $classes = 'relative flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-medium text-sm transition-colors duration-200 ease-out';
            if ($active) {
                // Seleccionado: texto + icono (currentColor) en accentStrong; hover no cambia fondo (misma clase que bg activo).
                $classes .= ' bg-app-accentSoft font-semibold text-app-accentStrong hover:bg-app-accentSoft hover:text-app-accentStrong hover:no-underline';
            } else {
                // Solo ítems inactivos: fondo y color al pasar el mouse.
                $classes .= ' text-app-muted hover:bg-app-navHover hover:text-app-text hover:no-underline';
            }
            ?>
            <a class="<?= e($classes) ?>" href="<?= e(APP_BASE_PATH . $item['path']) ?>">
                <?php if ($active): ?>
                    <!-- Linea vertical que indica el item activo -->
                    <span class="absolute left-0 top-1/2 h-full w-1 -translate-y-1/2 rounded-l bg-app-accentStrong" aria-hidden="true"></span>
                <?php endif; ?>
                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon($item['icon']) ?></span>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <footer class="mt-auto border-t border-app-border px-3 py-2.5 text-[11px] text-app-muted">SENA - Regional Risaralda</footer>
</aside>
