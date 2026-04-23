<?php

declare(strict_types=1);
?>

<section class="mb-4">

    <!-- Contenedor flex -->
    <div class="flex justify-between items-center">

        <!-- Título + subtítulo -->
        <div>
            <h2 class="m-0 text-2xl font-semibold">
                <?= e((string) ($title ?? '')) ?>
            </h2>

            <?php if (!empty($subtitle)): ?>
                <p class="mt-2 max-w-[720px] text-sm text-app-muted">
                    <?= e((string) $subtitle) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Acciones (botón) -->
        <?php if (!empty($actions)): ?>
            <div>
                <?= $actions ?>
            </div>
        <?php endif; ?>

    </div>

</section>