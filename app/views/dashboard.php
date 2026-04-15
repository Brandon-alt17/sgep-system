<?php partial('components/page_header', [
    'title' => 'Dashboard',
    'subtitle' => 'Vista general del estado de aprendices y próximas actividades.',
]); ?>

<section class="grid gap-4 lg:grid-cols-[2fr_1fr]">
    <article class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
        <h3 class="mb-2 mt-0 text-[15px] font-semibold">Conteo por estado</h3>
        <table class="mt-2.5 w-full border-collapse">
            <thead>
            <tr>
                <th class="border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted">Estado</th>
                <th class="border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($counts ?? []) as $row): ?>
                <tr>
                    <td class="border-b border-app-border px-2 py-2.5 text-left text-xs"><?= e((string) $row['estado']) ?></td>
                    <td class="border-b border-app-border px-2 py-2.5 text-left text-xs"><?= e((string) $row['total']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>

    <article class="rounded-[10px] border border-app-border bg-app-panel p-4 shadow-xsSoft">
        <h3 class="mb-2 mt-0 text-[15px] font-semibold">Próximas visitas (30 días)</h3>
        <table class="mt-2.5 w-full border-collapse">
            <thead>
            <tr>
                <th class="border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted">Aprendiz</th>
                <th class="border-b border-app-border px-2 py-2.5 text-left text-xs font-semibold text-app-muted">Fecha</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($alerts ?? []) as $alert): ?>
                <tr>
                    <td class="border-b border-app-border px-2 py-2.5 text-left text-xs"><a href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int) $alert['id'] ?>"><?= e((string) $alert['nombre_completo']) ?></a></td>
                    <td class="border-b border-app-border px-2 py-2.5 text-left text-xs"><?= e((string) $alert['proxima_visita']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
</section>
