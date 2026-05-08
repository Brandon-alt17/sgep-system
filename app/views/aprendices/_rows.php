<?php
declare(strict_types=1);

$aprendices = (array) ($aprendices ?? []);
$activeFilters = (array) ($activeFilters ?? []);

$query = array_filter([
    'q' => trim((string) ($activeFilters['q'] ?? '')),
    'ficha' => trim((string) ($activeFilters['ficha'] ?? '')),
    'estado' => trim((string) ($activeFilters['estado'] ?? '')),
    'empresa_id' => (int) ($activeFilters['empresa_id'] ?? 0) > 0 ? (string) (int) $activeFilters['empresa_id'] : '',
    'programa_id' => (int) ($activeFilters['programa_id'] ?? 0) > 0 ? (string) (int) $activeFilters['programa_id'] : '',
    'page' => (int) ($activeFilters['page'] ?? 0) > 1 ? (string) (int) $activeFilters['page'] : '',
    'from' => trim((string) ($activeFilters['from'] ?? '')),
], static fn ($v): bool => (string) $v !== '');
$profileFiltersQuery = $query === [] ? '' : '&' . http_build_query($query);
?>
<?php if ($aprendices === []): ?>
    <tr id="empty-server-row">
        <td class="<?= e(ui_td_classes()) ?> text-app-muted align-middle py-8 text-center" colspan="7">Sin aprendices que coincidan con los filtros.</td>
    </tr>
<?php else: ?>
    <?php foreach ($aprendices as $aprendiz): ?>
        <tr class="border-b transition hover:bg-gray-50">
            <td class="<?= e(ui_td_classes()) ?> font-medium" data-field="nombre">
                <?= e((string) ($aprendiz['nombre_completo'] ?? '')) ?>
            </td>
            <td class="<?= e(ui_td_classes()) ?> text-gray-600" data-field="documento">
                <?= e((string) ($aprendiz['numero_documento'] ?? '')) ?>
            </td>
            <td class="<?= e(ui_td_classes()) ?>" data-field="empresa">
                <?= e((string) ($aprendiz['empresa_nombre'] ?? '-')) ?>
            </td>
            <td class="<?= e(ui_td_classes()) ?> text-gray-600" data-field="ficha">
                <?= e((string) ($aprendiz['ficha'] ?? '-')) ?>
            </td>
            <td class="<?= e(ui_td_classes()) ?>" data-field="estado">
                <?php
                $estado = (string) ($aprendiz['estado'] ?? '');
                $badgeClass = match ($estado) {
                    'En ejecución',
                    'Aplazada',
                    'Finalizada',
                    'Certificado',
                    'Pendiente por comité' => ui_badge_success_classes(),
                    'Pendiente por iniciar' => ui_badge_warning_classes(),
                    default => ui_badge_error_classes(),
                };
                ?>
                <span class="<?= e($badgeClass) ?>"><?= e($estado) ?></span>
            </td>
            <td class="<?= e(ui_td_classes()) ?> text-gray-500" data-field="ultima_visita">
                <?= e((string) ($aprendiz['ultima_visita'] ?? '—')) ?>
            </td>
            <td class="<?= e(ui_td_classes()) ?>">
                <a class="<?= e(ui_button_small_classes()) ?>"
                   href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int) ($aprendiz['id'] ?? 0) ?><?= e($profileFiltersQuery) ?>">
                    Ver perfil
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
