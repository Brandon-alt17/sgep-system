<?php

declare(strict_types=1);

$userName = trim((string) ($userName ?? 'Usuario '));
$nameParts = preg_split('/\s+/', $userName) ?: [];
$initials = '';
foreach (array_slice($nameParts, 0, 2) as $part) {
    $initials .= strtoupper(substr((string) $part, 0, 1));
}
if ($initials === '') {
    $initials = 'US';
}
?>
<header class="z-40 flex shrink-0 items-center border-b border-app-border bg-app-topbar px-[18px]">
    <div class="ml-auto flex items-center gap-2 text-xs text-app-textSubtle">
        <span class="inline-flex h-[22px] w-[22px] items-center justify-center rounded-full bg-app-accent text-[10px] text-app-textOnBrand"><?= e($initials) ?></span>
        <span><?= e($userName) ?></span>
    </div>
</header>
