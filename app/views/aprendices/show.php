<?php include __DIR__ . '/modal-editar-perfil.php'; ?>
<?php include __DIR__ . '/modal-agendar-visitas.php'; ?>
<script src="<?= e(APP_BASE_PATH) ?>/js/date-input-dmy.js"></script>
<script src="<?= e(APP_BASE_PATH) ?>/js/edit-profile.js"></script>
<?php $backToListUrl = (string) ($backToListUrl ?? (APP_BASE_PATH . '/aprendices')); ?>
<?php $cardPaddedClass = str_replace('p-4', 'p-6', ui_card_classes()); ?>

<section class="bg-app-bg mb-4 flex flex-row items-start gap-2">
    <a class="<?= e(ui_button_icon_classes()) ?> mt-1 self-start" href="<?= e($backToListUrl) ?>" aria-label="Volver al listado de aprendices">
        <span class="inline-flex h-3.5 w-3.5 [&_svg]:h-3.5 [&_svg]:w-3.5"><?= ui_icon('arrow') ?></span>
    </a>
    <div class="flex w-full items-start justify-between gap-4 pl-4">
        <div class="flex flex-col gap-1">
            <h2 class="m-0 text-2xl font-semibold text-app-text">Perfil del aprendiz</h2>
            <p class="m-0 text-sm text-app-muted">Consulta, actualiza datos del aprendiz y gestiona sus documentos de seguimiento.</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <button type="button" onclick="abrirModalVisitas()"
                class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                <span class="w-4 h-4 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('calendar') ?></span>
                Programar visitas
            </button>

            <button type="button" onclick="abrirModalEditar()"
                class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                <span class="w-4 h-4 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('user-round') ?></span>
                Editar datos del aprendiz
            </button>
        </div>
    </div>
</section>



<div class="md:col-span-2 grid gap-4">
    <!-- 🧩 GRID PRINCIPAL -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- 🟢 PERFIL -->
        <section class="<?= e($cardPaddedClass) ?>">
            <div class="flex mb-4">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <span class="flex items-center justify-center text-app-link w-[30px] h-[30px] [&_svg]:w-5 [&_svg]:h-5">
                        <?= ui_icon('user') ?>
                    </span>
                    <span><?= e($aprendiz['nombre_completo']) ?></span>
                </h2>
            </div>

            <div class="space-y-3 text-sm md:col-span-2 grid gap-1">
                <?php
                function infoRow(string $label, mixed $rawValue, ?string $icon = null): void
                {
                    $display = trim((string) $rawValue);
                    $isMissing = $display === '';
                    if ($isMissing) {
                        $display = 'Dato no registrado';
                    }
                    $valueClass = $isMissing
                        ? 'font-medium italic text-gray-500'
                        : 'font-medium text-app-text';
                    $iconClass = $isMissing
                        ? 'w-4 h-4 shrink-0 text-gray-400 [&_svg]:w-4 [&_svg]:h-4'
                        : 'w-4 h-4 shrink-0 text-app-muted [&_svg]:w-4 [&_svg]:h-4';
                    ?>
                    <div>
                        <p class="text-xs text-app-muted mb-1"><?= e($label) ?></p>
                        <div class="flex items-center gap-2">
                            <?php if ($icon): ?>
                                <span class="<?= e($iconClass) ?>">
                                    <?= ui_icon($icon) ?>
                                </span>
                            <?php endif; ?>
                            <p class="<?= e($valueClass) ?>"><?= e($display) ?></p>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <?php
                $documentoDisplay = trim(($aprendiz['tipo_documento'] ?? '') . ' ' . ($aprendiz['numero_documento'] ?? ''));
                infoRow('Documento', $documentoDisplay, 'id-card');
                infoRow('Teléfono', $aprendiz['telefono'] ?? '', 'phone');
                infoRow('Email personal', $aprendiz['correo_personal'] ?? '', 'mail');
                infoRow('Correo institucional', $aprendiz['correo_institucional'] ?? '', 'mail');
                infoRow('Dirección', $aprendiz['direccion_domicilio'] ?? '', 'map-pin');
                infoRow('Alternativa etapa productiva', $aprendiz['alternativa_ep'] ?? '', 'briefcase');
                infoRow('Programa de formación', $aprendiz['programa_nombre'] ?? '', 'graduation-cap');
                infoRow('Grupo', $aprendiz['ficha'] ?? '', 'users');
                infoRow('Instructor seguimiento', $aprendiz['nombre_instructor_seguimiento'] ?? '', 'user');
                infoRow('Teléfono instructor seguimiento', $aprendiz['telefono_instructor_seguimiento'] ?? '', 'phone');
                infoRow('Jefe de grupo', $aprendiz['jefe_grupo'] ?? '', 'user-check');
                infoRow('Área de coordinación', $aprendiz['coordinacion'] ?? '', 'layers');
                infoRow('Estado ARL', $aprendiz['estado_arl_label'] ?? '', 'circle-check');
                infoRow('ARL', $aprendiz['arl'] ?? '', 'briefcase');
                ?>

                <div class="pt-2">
                    <span class="<?= e(ui_badge_success_classes()) ?>">
                        <?= e($aprendiz['estado']) ?>
                    </span>
                </div>
            </div>
        </section>

        <!-- 🔵 DERECHA -->
        <div class="md:col-span-2 space-y-6">
            <!-- 🏢 EMPRESA -->
            <section class="<?= e($cardPaddedClass) ?>">
                <?php
                $empresaCardTitulo = trim((string) ($aprendiz['empresa_nombre'] ?? ''));
                if ($empresaCardTitulo === '') {
                    $empresaCardTitulo = 'Sin empresa co-formadora';
                }
                ?>
                <div class="flex mb-6">
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                        <span class="text-app-link [&_svg]:w-5 [&_svg]:h-5">
                            <?= ui_icon('building') ?>
                        </span>
                        <?= e($empresaCardTitulo) ?>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-3">
                    <?php
                    infoRow('NIT', $aprendiz['nit'] ?? '');
                    infoRow('Dirección', $aprendiz['direccion'] ?? '');
                    infoRow('Supervisor', $aprendiz['nombre_jefe'] ?? '');
                    infoRow('Cargo', $aprendiz['cargo_jefe'] ?? '');
                    infoRow('Teléfono contacto', $aprendiz['telefono_jefe'] ?? '');
                    infoRow('Correo supervisor', $aprendiz['correo_jefe'] ?? '', 'mail');
                    ?>
                </div>
            </section>

            <!-- Documentos / momentos F-023 -->
            <section class="<?= e($cardPaddedClass) ?>">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h3 class="<?= e(ui_heading_sm_classes()) ?> m-0">Documentos</h3>
                    <a href="<?= e(APP_BASE_PATH) ?>/momentos/create?aprendiz_id=<?= (int) $aprendiz['id'] ?>&tipo=EX"
                       class="<?= e(ui_button_small_classes()) ?>">
                        + Agregar Momento extraordinario
                    </a>
                </div>

                <?php
                $docMomentoRowClass = 'flex h-[70px] flex-wrap items-center justify-between gap-3 rounded-lg border border-app-border px-4';
                $docListClass = 'grid gap-4 pr-1 md:col-span-2';
                // Scroll solo si hay momento extraordinario (más de M1, M2 y M3).
                if (count($momentos) > 3) {
                    $docListClass .= ' max-h-[336px] overflow-y-auto';
                }
                ?>
                <div class="<?= e($docListClass) ?>">
                    <div class="<?= $docMomentoRowClass ?>">
                        <span class="text-sm font-medium text-app-text">Información</span>
                        <a href="<?= e(APP_BASE_PATH) ?>/documentos/info?aprendiz_id=<?= (int) $aprendiz['id'] ?>"
                           class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                            <span class="w-5 h-5 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('file-spreadsheet') ?></span>
                            Abrir información
                        </a>
                    </div>
                    <?php foreach ($momentos as $m): ?>
                        <?php
                        $badge = ui_badge_error_classes();
                        if ($m['estado'] === 'Completado') {
                            $badge = ui_badge_success_classes();
                        } elseif ($m['estado'] === 'Incompleto') {
                            $badge = ui_badge_warning_classes();
                        }
                        ?>
                        <div class="<?= $docMomentoRowClass ?>">
                            <span class="min-w-0 text-sm font-medium text-app-text"><?= e($m['label']) ?></span>
                            <div class="flex flex-wrap items-center gap-3">
                                <?php if (!empty($m['fecha'])): ?>
                                    <span class="text-xs text-app-muted"><?= e($m['fecha']) ?></span>
                                <?php endif; ?>
                                <span class="<?= e($badge) ?>"><?= e($m['estado']) ?></span>
                                <?php
                                $editUrl = APP_BASE_PATH . '/momentos/create?aprendiz_id=' . (int) $aprendiz['id'] . '&tipo=' . rawurlencode((string) $m['tipo']);
                                if (($m['tipo'] ?? '') === 'EX' && (int) ($m['id'] ?? 0) > 0) {
                                    $editUrl .= '&momento_id=' . (int) $m['id'];
                                }
                                ?>
                                <a href="<?= e($editUrl) ?>"
                                   class="<?= e(ui_button_small_classes()) ?> inline-flex items-center gap-2">
                                    <span class="w-5 h-5 [&_svg]:w-4 [&_svg]:h-4"><?= ui_icon('pencil') ?></span>
                                    Editar momento
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </section>

            <div class="mt-4 flex flex-col items-stretch gap-3">
                <a href="<?= e(APP_BASE_PATH) ?>/documentos/generar?aprendiz_id=<?= (int) $aprendiz['id'] ?>"
                   class="font-app flex w-full items-center justify-center gap-2 rounded-lg border border-app-accent bg-app-accent px-4 py-3 text-sm font-semibold text-app-textOnBrand shadow-sm transition-colors duration-200 hover:border-app-accentHover hover:bg-app-accentHover hover:no-underline">
                    <span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><?= ui_icon('file-spreadsheet') ?></span>
                    Generar documento GFPI-F-023
                </a>
                <a href="<?= e(APP_BASE_PATH) ?>/reportes/maestro"
                   class="text-center text-sm font-medium text-app-link no-underline hover:text-app-accent hover:underline">
                    Ver en reporte general →
                </a>
            </div>

        </div>
    </div>
</div>

<?php
$pageToast = $pageToast ?? null;
$toastMessage = '';
$toastVariant = 'success';
if (is_array($pageToast) && trim((string) ($pageToast['message'] ?? '')) !== '') {
    $toastMessage = (string) $pageToast['message'];
    $toastVariant = (string) ($pageToast['variant'] ?? 'success');
}
partial('components/toast', [
    'message' => $toastMessage,
    'variant' => $toastVariant,
    'toastRootId' => 'aprendiz-page-toast',
    'positionClass' => 'bottom-6 left-4 right-4 z-[65] max-w-none sm:bottom-6 sm:left-auto sm:right-6 sm:max-w-sm',
]);
?>
<script src="<?= e(APP_BASE_PATH) ?>/js/ui-toast.js"></script>
