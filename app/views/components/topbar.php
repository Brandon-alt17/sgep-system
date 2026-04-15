<?php

declare(strict_types=1);

$title = $title ?? 'Módulo';
?>
<header class="flex items-center justify-between border-b border-app-border bg-[#f7f8fa] px-[18px]">
    <h1 class="m-0 text-base font-semibold text-app-text"><?= e((string) $title) ?></h1>
    <div class="flex items-center gap-2 text-xs text-gray-700">
        <span class="inline-flex h-[22px] w-[22px] items-center justify-center rounded-full bg-app-accent text-[10px] text-white">CM</span>
        <span>Carlos Mendoza</span>
    </div>
</header>
