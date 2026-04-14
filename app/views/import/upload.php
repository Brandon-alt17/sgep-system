<?php
/**
 * Vista: Importación masiva de aprendices (Excel).
 *
 * Contrato esperado con el controlador (futuro):
 * - $historial: listado de importaciones previas (archivo, fecha, registros, estado).
 *   Mientras no exista tabla/API, se usa array vacío y se muestra estado vacío.
 */
$historial = $historial ?? [];
$tieneHistorial = is_array($historial) && $historial !== [];
?>
<style>
  /* ========== Alcance: solo pantalla Importar datos ========== */
  .import-page {
    --sena-green: #009b4d;
    --sena-blue: #004b87;
    --sena-orange: #f39200;
    --surface: #f8fafc;
    --card: #ffffff;
    --border: #e2e8f0;
    --text: #0f172a;
    --text-muted: #64748b;
    --radius: 12px;
    --shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    max-width: 1200px;
    margin: 0 auto;
    color: var(--text);
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  }

  .import-page__title {
    margin: 0 0 0.35rem;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .import-page__lead {
    margin: 0 0 1.5rem;
    font-size: 0.95rem;
    color: var(--text-muted);
  }

  .import-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }

  @media (min-width: 900px) {
    .import-grid {
      grid-template-columns: 1fr 1fr;
      align-items: start;
    }
  }

  .import-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.25rem 1.35rem;
  }

  .import-card__head {
    margin-bottom: 1rem;
  }

  .import-card__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 600;
  }

  .import-card__subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.8rem;
    color: var(--text-muted);
  }

  /* Zona de carga: arrastrar / clic */
  .import-dropzone {
    position: relative;
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    padding: 2rem 1rem;
    text-align: center;
    transition: border-color 0.2s, background 0.2s;
  }

  .import-dropzone--drag {
    border-color: var(--sena-green);
    background: rgba(0, 155, 77, 0.06);
  }

  .import-dropzone__icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 0.75rem;
    color: var(--sena-blue);
  }

  .import-dropzone__text {
    margin: 0 0 1rem;
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.45;
  }

  .import-dropzone__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    align-items: center;
  }

  .import-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.55rem 1.1rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.15s, box-shadow 0.15s;
  }

  .import-btn:focus-visible {
    outline: 2px solid var(--sena-blue);
    outline-offset: 2px;
  }

  .import-btn--primary {
    background: var(--sena-green);
    color: #fff;
  }

  .import-btn--primary:hover {
    filter: brightness(1.05);
  }

  .import-btn--secondary {
    background: #fff;
    color: var(--sena-blue);
    border: 1px solid var(--border);
  }

  .import-btn--secondary:hover {
    background: var(--surface);
  }

  .import-btn--secondary[aria-disabled="true"] {
    opacity: 0.55;
    cursor: not-allowed;
    pointer-events: none;
  }

  .import-file-input {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
    pointer-events: none;
  }

  .import-instructions ol {
    margin: 0;
    padding-left: 1.25rem;
    font-size: 0.9rem;
    line-height: 1.55;
    color: var(--text);
  }

  .import-instructions li {
    margin-bottom: 0.5rem;
  }

  .import-template-block {
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
  }

  .import-template-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
  }

  /* Historial */
  .import-history {
    margin-top: 1.5rem;
  }

  .import-history__head {
    margin-bottom: 1rem;
  }

  .import-history__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 600;
  }

  .import-history__subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.8rem;
    color: var(--text-muted);
  }

  .import-table-wrap {
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--card);
  }

  .import-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }

  .import-table th,
  .import-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border);
  }

  .import-table th {
    background: var(--surface);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted);
  }

  .import-table tr:last-child td {
    border-bottom: none;
  }

  .import-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .import-badge--ok {
    background: rgba(16, 185, 129, 0.15);
    color: #047857;
  }

  .import-badge--partial {
    background: rgba(251, 191, 36, 0.2);
    color: #b45309;
  }

  .import-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--text-muted);
    font-size: 0.9rem;
  }

  .import-filename {
    font-size: 0.8rem;
    color: var(--sena-blue);
    margin-top: 0.75rem;
    word-break: break-all;
  }
</style>

<section class="import-page" aria-labelledby="importar-datos-titulo">
  <h1 class="import-page__title" id="importar-datos-titulo">Importar datos</h1>
  <p class="import-page__lead">
    Cargue el archivo Excel con la información de aprendices en etapa productiva. Las columnas deben coincidir en orden y
    contenido con la plantilla oficial de seguimiento.
  </p>

  <div class="import-grid">
    <!-- Tarjeta: carga de archivo -->
    <article class="import-card" aria-labelledby="card-cargar-titulo">
      <header class="import-card__head">
        <h2 class="import-card__title" id="card-cargar-titulo">Cargar archivo</h2>
        <p class="import-card__subtitle">Formato aceptado: .xlsx, .xls o .csv — máximo 5&nbsp;MB</p>
      </header>

      <form
        id="form-importar"
        method="post"
        action="<?= e(APP_BASE_PATH) ?>/importar"
        enctype="multipart/form-data"
        novalidate
      >
        <!-- Campo real enviado al servidor (nombre acordado con ImportacionController) -->
        <input
          class="import-file-input"
          type="file"
          name="archivo"
          id="importar-archivo"
          accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
          required
        />

        <div
          class="import-dropzone"
          id="import-dropzone"
          role="button"
          tabindex="0"
          aria-describedby="dropzone-help"
        >
          <svg class="import-dropzone__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <p class="import-dropzone__text" id="dropzone-help">
            Arrastre el archivo Excel aquí o haga clic para seleccionar.<br />
            Solo archivos Excel o CSV compatibles con la plantilla.
          </p>
          <div class="import-dropzone__actions">
            <button type="button" class="import-btn import-btn--primary" id="import-btn-elegir">
              Seleccionar archivo
            </button>
          </div>
          <p class="import-filename" id="import-nombre-archivo" hidden></p>
        </div>

        <div class="import-dropzone__actions" style="margin-top: 1rem;">
          <button type="submit" class="import-btn import-btn--primary" id="import-btn-enviar">
            Importar al sistema
          </button>
        </div>
      </form>
    </article>

    <!-- Tarjeta: instrucciones y plantilla -->
    <article class="import-card" aria-labelledby="card-instrucciones-titulo">
      <header class="import-card__head">
        <h2 class="import-card__title" id="card-instrucciones-titulo">Instrucciones</h2>
        <p class="import-card__subtitle">Pasos recomendados antes de cargar el archivo</p>
      </header>

      <div class="import-instructions">
        <ol>
          <li>Descargue o solicite la plantilla oficial de seguimiento de aprendices en etapa productiva.</li>
          <li>Complete las columnas según el orden definido en el sistema (primera fila = encabezados).</li>
          <li>Suba el archivo en el área de carga y pulse <strong>Importar al sistema</strong>.</li>
          <li>Revise el resumen en la siguiente pantalla y corrija filas con error si el sistema las reporta.</li>
        </ol>
      </div>

      <div class="import-template-block">
        <p class="import-template-label">Plantilla disponible</p>
        <!-- Enlace real cuando exista un archivo público (p. ej. public/plantilla_seguimiento.xlsx) -->
        <a
          class="import-btn import-btn--secondary"
          href="#"
          aria-disabled="true"
          title="Defina la ruta pública de la plantilla cuando esté disponible"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v9m0-9l3 3m-3-3L9 15M12 3v3" />
          </svg>
          Plantilla de seguimiento
        </a>
      </div>
    </article>
  </div>

  <!-- Historial: datos desde $historial cuando el back-end los exponga -->
  <section class="import-history import-card" aria-labelledby="historial-titulo">
    <header class="import-history__head">
      <h2 class="import-history__title" id="historial-titulo">Historial de importaciones</h2>
      <p class="import-history__subtitle">Últimas cargas registradas en el sistema</p>
    </header>

    <div class="import-table-wrap">
      <?php if (!$tieneHistorial): ?>
        <p class="import-empty">
          Aún no hay importaciones registradas en base de datos.<br />
          <small>Cuando exista una tabla o API de historial, el controlador puede pasar <code>$historial</code> a esta vista.</small>
        </p>
      <?php else: ?>
        <table class="import-table">
          <thead>
            <tr>
              <th scope="col">Archivo</th>
              <th scope="col">Fecha</th>
              <th scope="col">Registros</th>
              <th scope="col">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($historial as $fila): ?>
              <tr>
                <td><?= e((string) ($fila['archivo'] ?? '')) ?></td>
                <td><?= e((string) ($fila['fecha'] ?? '')) ?></td>
                <td><?= e((string) ($fila['registros'] ?? '')) ?></td>
                <td>
                  <?php
                  $estado = (string) ($fila['estado'] ?? '');
                  $clase = $estado === 'Parcial' ? 'import-badge--partial' : 'import-badge--ok';
                  $etiqueta = $estado !== '' ? $estado : '—';
                  ?>
                  <span class="import-badge <?= e($clase) ?>"><?= e($etiqueta) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </section>
</section>

<script>
(function () {
  "use strict";
  var maxBytes = 5 * 1024 * 1024;
  var input = document.getElementById("importar-archivo");
  var dropzone = document.getElementById("import-dropzone");
  var btnElegir = document.getElementById("import-btn-elegir");
  var nombreArchivo = document.getElementById("import-nombre-archivo");
  var form = document.getElementById("form-importar");

  if (!input || !dropzone || !form) {
    return;
  }

  function setNombreArchivo(file) {
    if (!nombreArchivo) {
      return;
    }
    if (file && file.name) {
      nombreArchivo.textContent = "Seleccionado: " + file.name;
      nombreArchivo.hidden = false;
    } else {
      nombreArchivo.textContent = "";
      nombreArchivo.hidden = true;
    }
  }

  function validarTamano(file) {
    if (file.size > maxBytes) {
      alert("El archivo supera el máximo permitido (5 MB).");
      input.value = "";
      setNombreArchivo(null);
      return false;
    }
    return true;
  }

  btnElegir.addEventListener("click", function (e) {
    e.stopPropagation();
    input.click();
  });

  input.addEventListener("change", function () {
    var f = input.files && input.files[0];
    if (f && !validarTamano(f)) {
      return;
    }
    setNombreArchivo(f || null);
  });

  ["dragenter", "dragover"].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.add("import-dropzone--drag");
    });
  });

  ["dragleave", "drop"].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropzone.classList.remove("import-dropzone--drag");
    });
  });

  dropzone.addEventListener("drop", function (e) {
    var files = e.dataTransfer && e.dataTransfer.files;
    if (!files || !files.length) {
      return;
    }
    var f = files[0];
    if (!validarTamano(f)) {
      return;
    }
    var dt = new DataTransfer();
    dt.items.add(f);
    input.files = dt.files;
    setNombreArchivo(f);
  });

  dropzone.addEventListener("click", function (e) {
    if (btnElegir.contains(e.target)) {
      return;
    }
    input.click();
  });

  dropzone.addEventListener("keydown", function (e) {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      input.click();
    }
  });

  form.addEventListener("submit", function (e) {
    if (!input.files || !input.files.length) {
      e.preventDefault();
      alert("Seleccione un archivo antes de importar.");
      return;
    }
    if (!validarTamano(input.files[0])) {
      e.preventDefault();
    }
  });
})();
</script>
