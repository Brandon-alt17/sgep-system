<?php
declare(strict_types=1);

$jefes = (array) ($jefes ?? []);
$eid = (int) ($eid ?? 0);
$verUrl = (string) ($verUrl ?? '');
$newJefeDraft = (bool) ($newJefeDraft ?? false);
$autoEditJefeId = (int) ($autoEditJefeId ?? 0);
$viewLabelCls = (string) ($viewLabelCls ?? 'm-0 text-xs font-medium uppercase tracking-wide text-app-muted');

$showField = static function (string $value): string {
    $t = trim($value);
    return $t !== '' ? e($t) : '<span class="italic">Dato no registrado</span>';
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
        <a href="<?= e($verUrl) ?>&new_jefe=1" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
            <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('plus') ?></span>
            Nuevo jefe
        </a>
    </div>

    <?php if ($newJefeDraft): ?>
        <article class="<?= e(ui_card_classes()) ?> mt-4 min-h-[96px] border-app-accent p-6 shadow-xsSoft">
            <form method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/agregar-jefe" data-new-jefe-form>
                <input type="hidden" name="empresa_id" value="<?= $eid ?>">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="<?= e(ui_label_classes()) ?> md:col-span-2">Supervisor *
                        <input type="text" name="nombre" value="" required class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Cargo
                        <input type="text" name="cargo" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Teléfono supervisor
                        <input type="text" name="telefono" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                    <label class="<?= e(ui_label_classes()) ?> md:col-span-2">Correo supervisor
                        <input type="email" name="correo" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Contacto alternativo (nombre)
                        <input type="text" name="nombre_contacto2" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                    <label class="<?= e(ui_label_classes()) ?>">Contacto alternativo (correo)
                        <input type="email" name="correo_contacto2" value="" class="<?= e(ui_input_classes()) ?>" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </label>
                </div>
                <div class="mt-4 flex items-center justify-end gap-2 border-t border-app-borderSoft pt-3">
                    <a href="<?= e($verUrl) ?>" class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                        Cancelar
                    </a>
                    <button type="submit" class="<?= e(ui_button_icon_classes()) ?> border-green-600 bg-green-600 text-white hover:border-green-700 hover:bg-green-700 hover:text-white" aria-label="Guardar nuevo jefe">
                        <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                    </button>
                </div>
            </form>
        </article>
    <?php endif; ?>

    <?php if ($jefes === []): ?>
        <div class="mt-6 rounded-md border border-app-border bg-app-panelSubtle p-4 text-sm text-app-muted">
            No hay jefes registrados para esta empresa.
        </div>
    <?php else: ?>
        <div id="empresa-jefes-live-items" class="mt-6 space-y-6" data-live-filter-items>
            <?php foreach ($jefes as $idx => $jefe): ?>
                <?php
                $jefeId = (int) ($jefe['id'] ?? 0);
                $canInlineEdit = $jefeId > 0;
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
                    class="<?= e(ui_card_classes()) ?> min-h-[96px] p-6 transition-[border-color,box-shadow] duration-300 ease-out motion-reduce:transition-none"
                    <?= $canInlineEdit ? 'data-inline-edit-root data-live-filter-item' : 'data-live-filter-item' ?>
                    data-live-filter-text="<?= e($searchText) ?>"
                    <?= $canInlineEdit && $autoEditJefeId === $jefeId ? 'data-inline-auto-open="1"' : '' ?>
                >
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div data-inline-header>
                            <?php if (trim((string) ($jefe['cargo'] ?? '')) !== ''): ?>
                                <p class="m-0 text-xs font-semibold tracking-wide text-app-muted"><?= e((string) $jefe['cargo']) ?></p>
                            <?php endif; ?>
                            <p class="m-0 mt-1 text-lg font-semibold text-app-text"><?= $showField((string) ($jefe['nombre'] ?? ('Jefe ' . ($idx + 1)))) ?></p>
                        </div>
                        <?php if ($canInlineEdit): ?>
                            <button type="button" class="<?= e(ui_button_icon_classes()) ?>" data-inline-edit-open aria-label="Editar jefe">
                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('pencil') ?></span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div data-inline-view>
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
                    </div>

                    <?php if ($canInlineEdit): ?>
                        <div data-inline-competencia-drawer aria-expanded="false">
                            <div data-inline-competencia-drawer-inner>
                                <form id="jefe-form-<?= $jefeId ?>" method="post" action="<?= e(APP_BASE_PATH) ?>/catalogo/empresas/actualizar-jefe" data-inline-edit-form inert>
                                    <input type="hidden" name="empresa_id" value="<?= $eid ?>">
                                    <input type="hidden" name="jefe_id" value="<?= $jefeId ?>">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <label class="<?= e(ui_label_classes()) ?> md:col-span-2">Supervisor *
                                            <input type="text" name="nombre" value="<?= e((string) ($jefe['nombre'] ?? '')) ?>" required class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                        </label>
                                        <label class="<?= e(ui_label_classes()) ?>">Cargo
                                            <input type="text" name="cargo" value="<?= e((string) ($jefe['cargo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                        </label>
                                        <label class="<?= e(ui_label_classes()) ?>">Teléfono supervisor
                                            <input type="text" name="telefono" value="<?= e((string) ($jefe['telefono'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                        </label>
                                        <label class="<?= e(ui_label_classes()) ?> md:col-span-2">Correo supervisor
                                            <input type="email" name="correo" value="<?= e((string) ($jefe['correo'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                        </label>
                                        <label class="<?= e(ui_label_classes()) ?>">Contacto alternativo (nombre)
                                            <input type="text" name="nombre_contacto2" value="<?= e((string) ($jefe['nombre_contacto2'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                        </label>
                                        <label class="<?= e(ui_label_classes()) ?>">Contacto alternativo (correo)
                                            <input type="email" name="correo_contacto2" value="<?= e((string) ($jefe['correo_contacto2'] ?? '')) ?>" class="<?= e(ui_input_classes()) ?>" data-inline-input>
                                        </label>
                                    </div>
                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-app-borderSoft pt-3">
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
                                        <div class="flex items-center gap-2">
                                            <button type="submit" form="jefe-form-<?= $jefeId ?>" class="<?= e(ui_button_icon_classes()) ?> hidden border-green-600 bg-green-600 text-white hover:border-green-700 hover:bg-green-700 hover:text-white" data-inline-edit-save aria-label="Guardar jefe">
                                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('check') ?></span>
                                            </button>
                                            <button type="button" class="<?= e(ui_button_icon_classes()) ?> hidden border-rose-600 bg-rose-600 text-white hover:border-rose-700 hover:bg-rose-700 hover:text-white" data-inline-edit-cancel aria-label="Cancelar edición de jefe">
                                                <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('x') ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
