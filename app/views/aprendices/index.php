<?php
declare(strict_types=1);

$aprendices = (array) ($aprendices ?? []);
$fichasOptions = (array) ($fichasOptions ?? []);
$estadosOptions = (array) ($estadosOptions ?? []);
$initialFicha = trim((string) ($initialFicha ?? ''));
$initialEstado = trim((string) ($initialEstado ?? ''));
$initialQ = trim((string) ($initialQ ?? ''));

partial('components/page_header', [
    'title' => 'Aprendices',
    'actions' => '
        <a class="' . e(ui_button_small_primary_classes()) . '" href="' . e(APP_BASE_PATH) . '/aprendices/create">
            Nuevo aprendiz
        </a>
    ',
]);
?>

<section class="<?= e(ui_card_classes()) ?> mb-4">
    <div class="flex w-full flex-wrap items-center gap-3">
        <input
            type="text"
            id="search-input"
            value="<?= e($initialQ) ?>"
            placeholder="Buscar por nombre o documento"
            class="<?= e(ui_input_classes()) ?> min-w-0 flex-1"
            autocomplete="off"
        >

        <div class="<?= e(ui_select_wrapper_classes()) ?> w-full shrink-0 md:w-64">
            <select id="ficha-filter" class="<?= e(ui_select_classes()) ?>">
                <option value="">Todas las fichas</option>
                <?php foreach ($fichasOptions as $f): ?>
                    <?php if ($f === null || $f === '') {
                        continue;
                    } ?>
                    <option value="<?= e((string) $f) ?>" <?= (string) $f === $initialFicha ? 'selected' : '' ?>><?= e((string) $f) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </div>

        <div class="<?= e(ui_select_wrapper_classes()) ?> w-full shrink-0 md:w-64">
            <select id="estado-filter" class="<?= e(ui_select_classes()) ?>">
                <option value="">Todos los estados</option>
                <?php foreach ($estadosOptions as $st): ?>
                    <?php if ($st === null || $st === '') {
                        continue;
                    } ?>
                    <option value="<?= e((string) $st) ?>" <?= (string) $st === $initialEstado ? 'selected' : '' ?>><?= e((string) $st) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="<?= e(ui_select_chevron_classes()) ?>" data-select-chevron aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </div>
    </div>
</section>

<section class="<?= e(ui_card_classes()) ?> !p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table id="aprendices-table" class="<?= e(ui_table_classes()) ?>">
            <thead class="border-b bg-app-panelSubtle">
                <tr>
                    <th class="<?= e(ui_th_classes()) ?>">Nombre completo</th>
                    <th class="<?= e(ui_th_classes()) ?>">Documento</th>
                    <th class="<?= e(ui_th_classes()) ?>">Empresa</th>
                    <th class="<?= e(ui_th_classes()) ?>">Grupo</th>
                    <th class="<?= e(ui_th_classes()) ?>">Estado</th>
                    <th class="<?= e(ui_th_classes()) ?>">Última visita</th>
                    <th class="<?= e(ui_th_classes()) ?>"></th>
                </tr>
            </thead>
            <tbody id="table-body">
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
                               href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int) ($aprendiz['id'] ?? 0) ?>">
                                Ver perfil
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr id="no-client-results-row" class="hidden">
                <td class="<?= e(ui_td_classes()) ?> text-app-muted align-middle py-8 text-center" colspan="7">Ningún resultado con los filtros actuales.</td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
  function filtrarTabla() {
    var busqueda = (document.getElementById("search-input") && document.getElementById("search-input").value || "").toLowerCase().trim();
    var fichaSel = (document.getElementById("ficha-filter") && document.getElementById("ficha-filter").value) || "";
    var estadoSel = (document.getElementById("estado-filter") && document.getElementById("estado-filter").value) || "";
    var tbody = document.getElementById("table-body");
    if (!tbody) return;

    var noClient = document.getElementById("no-client-results-row");
    if (noClient) noClient.classList.add("hidden");

    var filas = tbody.querySelectorAll("tr");
    var visibles = 0;
    var hayFilasDatos = false;

    filas.forEach(function (fila) {
      if (fila.id === "no-client-results-row" || fila.id === "empty-server-row") return;

      var celdas = fila.querySelectorAll("td");
      if (celdas.length < 7) return;

      hayFilasDatos = true;

      var nombre = (celdas[0].textContent || "").toLowerCase();
      var documento = celdas[1].textContent || "";
      var ficha = (celdas[3].textContent || "").trim();
      var spanEstado = celdas[4].querySelector("span");
      var estado = spanEstado ? (spanEstado.textContent || "").trim() : (celdas[4].textContent || "").trim();

      var okBusq = busqueda === "" || nombre.indexOf(busqueda) !== -1 || documento.toLowerCase().indexOf(busqueda) !== -1;
      var okFicha = fichaSel === "" || ficha === fichaSel;
      var okEst = estadoSel === "" || estado === estadoSel;

      if (okBusq && okFicha && okEst) {
        fila.style.display = "";
        visibles++;
      } else {
        fila.style.display = "none";
      }
    });

    if (noClient && hayFilasDatos && visibles === 0) {
      noClient.classList.remove("hidden");
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    var searchInput = document.getElementById("search-input");
    var fichaFilter = document.getElementById("ficha-filter");
    var estadoFilter = document.getElementById("estado-filter");

    if (searchInput) searchInput.addEventListener("input", filtrarTabla);
    if (fichaFilter) fichaFilter.addEventListener("change", filtrarTabla);
    if (estadoFilter) estadoFilter.addEventListener("change", filtrarTabla);

    filtrarTabla();
  });
})();
</script>
