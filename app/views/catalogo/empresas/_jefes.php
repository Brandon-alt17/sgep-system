<?php
declare(strict_types=1);

$jefes = (array) ($jefes ?? []);
$eid = (int) ($eid ?? 0);
$newJefeDraft = (bool) ($newJefeDraft ?? false);
$autoEditJefeId = (int) ($autoEditJefeId ?? 0);
$viewLabelCls = (string) ($viewLabelCls ?? 'm-0 text-xs font-medium uppercase tracking-wide text-app-muted');

$showField = static function (string $value): string {
    $t = trim($value);
    return $t !== '' ? e($t) : '<span class="italic">Dato no registrado</span>';
};

$renderJefeModal = static function (
    string $modalId,
    string $title,
    string $formAction,
    array $jefe,
    int $empresaId,
    bool $isCreate,
    ?int $jefeId = null
): void {
    $closeId = $modalId;
    ?>
    <div
        id="modal-<?= e($modalId) ?>"
        class="modal-overlay hidden"
        data-modal-overlay="<?= e($modalId) ?>"
    >
        <div
            class="modal-panel max-w-2xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="jefe-modal-title-<?= e($modalId) ?>"
        >
            <div class="mb-4 flex shrink-0 items-center justify-between gap-3">
                <h3 id="jefe-modal-title-<?= e($modalId) ?>" class="m-0 text-lg font-semibold text-app-text"><?= e($title) ?></h3>
                <button
                    type="button"
                    class="<?= e(ui_button_icon_classes()) ?>"
                    data-modal-close="<?= e($closeId) ?>"
                    aria-label="Cerrar"
                >
                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                </button>
            </div>
            <form
                method="post"
                action="<?= e($formAction) ?>"
                class="min-h-0 flex-1 overflow-y-auto"
                data-jefe-form
            >
                <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
                <?php if ($jefeId !== null && $jefeId > 0): ?>
                    <input type="hidden" name="jefe_id" value="<?= $jefeId ?>">
                <?php endif; ?>
                <?php partial('catalogo/empresas/_jefe_form_fields', [
                    'jefe' => $jefe,
                    'autocompleteOff' => $isCreate,
                ]); ?>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-app-borderSoft pt-4">
                    <?php if (!$isCreate && $jefeId !== null && $jefeId > 0): ?>
                        <button
                            type="submit"
                            formaction="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/eliminar-jefe"
                            formmethod="post"
                            class="inline-flex items-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-[18px] py-2 text-sm font-medium text-rose-700 no-underline transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700"
                            data-confirm-modal
                            data-confirm-title="Eliminar jefe"
                            data-confirm-message="¿Eliminar este jefe? Solo se permite si no tiene aprendices vinculados."
                        >
                            <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('trash-2') ?></span>
                            Eliminar jefe
                        </button>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <div class="flex items-center gap-2">
                        <button type="button" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2" data-modal-close="<?= e($closeId) ?>">
                            <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="<?= e(ui_button_icon_classes()) ?> border-green-600 bg-green-600 text-white hover:border-green-700 hover:bg-green-700 hover:text-white"
                            aria-label="<?= $isCreate ? 'Guardar nuevo jefe' : 'Guardar jefe' ?>"
                        >
                            <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
};

?>

<?php if ($jefes !== []): ?>
    <?php partial('components/live_filter_search', [
        'items_target' => '#empresa-jefes-live-items',
        'placeholder' => 'Buscar por nombre, cargo o correo',
        'aria_label' => 'Buscar jefe por nombre, cargo o correo',
        'variant' => 'card',
        'class' => 'mt-6',
    ]); ?>
<?php endif; ?>

<section class="<?= e(ui_card_classes()) ?> mt-6 p-6">
    <div class="flex items-center justify-between gap-2">
        <h4 class="m-0 text-md font-semibold text-app-text">Jefes y contacto alternativo</h4>
        <button type="button" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2" data-modal-open="empresa-jefe-nuevo">
            <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('plus') ?></span>
            Nuevo jefe
        </button>
    </div>

    <?php if ($jefes === []): ?>
        <div class="mt-6 rounded-md border border-app-border bg-app-panelSubtle p-4 text-sm text-app-muted">
            No hay jefes registrados para esta empresa.
        </div>
    <?php else: ?>
        <div id="empresa-jefes-live-items" class="mt-6 space-y-6" data-live-filter-items>
            <?php foreach ($jefes as $idx => $jefe): ?>
                <?php
                $jefeId = (int) ($jefe['id'] ?? 0);
                $canEdit = $jefeId > 0;
                $searchParts = [
                    (string) ($jefe['nombre'] ?? ''),
                    (string) ($jefe['cargo'] ?? ''),
                    (string) ($jefe['correo'] ?? ''),
                    (string) ($jefe['telefono'] ?? ''),
                    (string) ($jefe['nombre_contacto2'] ?? ''),
                    (string) ($jefe['correo_contacto2'] ?? ''),
                ];
                $searchText = trim(implode(' ', array_filter(array_map(
                    static fn ($value): string => trim((string) $value),
                    $searchParts
                ))));
                ?>
                <article
                    class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6"
                    data-live-filter-item
                    data-live-filter-text="<?= e($searchText) ?>"
                >
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div>
                            <?php if (trim((string) ($jefe['cargo'] ?? '')) !== ''): ?>
                                <p class="m-0 text-xs font-semibold tracking-wide text-app-muted"><?= e((string) $jefe['cargo']) ?></p>
                            <?php endif; ?>
                            <p class="m-0 mt-1 text-lg font-semibold text-app-text"><?= $showField((string) ($jefe['nombre'] ?? ('Jefe ' . ($idx + 1)))) ?></p>
                        </div>
                        <?php if ($canEdit): ?>
                            <button
                                type="button"
                                class="<?= e(ui_button_icon_classes()) ?>"
                                data-modal-open="empresa-jefe-<?= $jefeId ?>"
                                aria-label="Editar jefe"
                            >
                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('pencil') ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <p class="<?= e($viewLabelCls) ?>">Correo supervisor</p>
                            <p class="mt-1 break-all text-base text-app-text"><?= $showField((string) ($jefe['correo'] ?? '')) ?></p>
                        </div>
                        <div>
                            <p class="<?= e($viewLabelCls) ?>">Teléfono supervisor</p>
                            <p class="mt-1 text-base text-app-text"><?= $showField((string) ($jefe['telefono'] ?? '')) ?></p>
                        </div>
                        <div>
                            <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (nombre)</p>
                            <p class="mt-1 text-base text-app-text"><?= $showField((string) ($jefe['nombre_contacto2'] ?? '')) ?></p>
                        </div>
                        <div>
                            <p class="<?= e($viewLabelCls) ?>">Contacto alternativo (correo)</p>
                            <p class="mt-1 break-all text-base text-app-text"><?= $showField((string) ($jefe['correo_contacto2'] ?? '')) ?></p>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
$renderJefeModal(
    'empresa-jefe-nuevo',
    'Nuevo jefe',
    APP_BASE_PATH . '/catalogo/empresas/agregar-jefe',
    [],
    $eid,
    true
);
foreach ($jefes as $jefe) {
    $jefeId = (int) ($jefe['id'] ?? 0);
    if ($jefeId <= 0) {
        continue;
    }
    $nombre = trim((string) ($jefe['nombre'] ?? ''));
    $title = $nombre !== '' ? 'Editar: ' . $nombre : 'Editar jefe';
    $renderJefeModal(
        'empresa-jefe-' . $jefeId,
        $title,
        APP_BASE_PATH . '/catalogo/empresas/actualizar-jefe',
        $jefe,
        $eid,
        false,
        $jefeId
    );
}
?>

<?php if ($newJefeDraft || $autoEditJefeId > 0): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalKey = <?= $newJefeDraft
            ? json_encode('empresa-jefe-nuevo', JSON_THROW_ON_ERROR)
            : json_encode('empresa-jefe-' . $autoEditJefeId, JSON_THROW_ON_ERROR) ?>;
        var trigger = document.querySelector('[data-modal-open="' + modalKey + '"]');
        if (trigger) trigger.click();
    });
    </script>
<?php endif; ?>
