// Utilidad heredada: limita longitud de campos con data-max.
document.querySelectorAll("[data-max]").forEach(function (element) {
  var max = parseInt(element.getAttribute("data-max") || "0", 10);
  if (!max) return;
  element.addEventListener("input", function (event) {
    var value = event.target.value || "";
    if (value.length > max) event.target.value = value.substring(0, max);
  });
});

// UI de carga: click en el panel abre selector y muestra el nombre del archivo.
var fileInput = document.querySelector("[data-import-input]");
var fileName = document.querySelector("[data-import-filename]");
var trigger = document.querySelector("[data-import-trigger]");
var form = document.querySelector("[data-import-form]");
var progress = document.querySelector("[data-import-progress]");
var dropzone = document.querySelector("[data-import-dropzone]");

if (fileInput && trigger && form) {
  var openPicker = function () { fileInput.click(); };
  trigger.addEventListener("click", openPicker);

  var setProgressState = function (labelText, percent) {
    if (!progress) return;
    progress.classList.remove("hidden");
    var label = progress.querySelector("[data-import-progress-label]");
    var value = progress.querySelector("[data-import-progress-value]");
    var bar = progress.querySelector("[data-import-progress-bar]");
    var safePercent = Math.max(0, Math.min(100, Math.round(percent || 0)));
    if (label) label.textContent = labelText;
    if (value) value.textContent = safePercent + "%";
    if (bar) bar.style.width = safePercent + "%";
  };

  var sendImport = function () {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", form.getAttribute("action") || window.location.href, true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.timeout = 300000;

    setProgressState("Preparando carga...", 0);

    xhr.upload.addEventListener("progress", function (event) {
      if (!progress || !event.lengthComputable) return;
      // Reserva el tramo final para el procesamiento del servidor.
      var uploadPercent = Math.round((event.loaded / event.total) * 95);
      var percent = Math.max(1, Math.min(95, uploadPercent));
      setProgressState("Subiendo archivo...", percent);
    });

    xhr.upload.addEventListener("load", function () {
      setProgressState("Procesando importación...", 95);
    });

    xhr.addEventListener("load", function () {
      try {
        var data = JSON.parse(xhr.responseText || "{}");
        if (xhr.status >= 200 && xhr.status < 300 && data.redirect) {
          setProgressState("Importación completada", 100);
          window.location.href = data.redirect;
          return;
        }
        setProgressState("No se pudo completar la importación", 0);
        if (data.message) {
          alert(data.message);
        } else {
          alert("No se pudo completar la importación.");
        }
      } catch (e) {
        setProgressState("Error inesperado durante la importación", 0);
        alert("Error inesperado durante la importación.");
      }
    });

    xhr.addEventListener("error", function () {
      setProgressState("Error de red durante la carga", 0);
      alert("Error de red durante la importación.");
    });

    xhr.addEventListener("timeout", function () {
      setProgressState("La importación tardó demasiado", 95);
      alert("La importación está tardando más de lo esperado. Intente nuevamente.");
    });

    xhr.send(new FormData(form));
  };

  fileInput.addEventListener("change", function () {
    if (fileName) {
      fileName.textContent = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : "Sin archivo seleccionado";
    }
    if (fileInput.files && fileInput.files[0]) {
      setProgressState("Preparando carga...", 0);
      sendImport();
    }
  });

  if (dropzone) {
    var dragDepth = 0;
    var activateDropzone = function () {
      dropzone.classList.add("bg-app-panelHover");
    };
    var deactivateDropzone = function () {
      dropzone.classList.remove("bg-app-panelHover");
    };

    dropzone.addEventListener("dragenter", function (event) {
      event.preventDefault();
      dragDepth += 1;
      activateDropzone();
    });

    dropzone.addEventListener("dragover", function (event) {
      event.preventDefault();
      event.dataTransfer.dropEffect = "copy";
      activateDropzone();
    });

    dropzone.addEventListener("dragleave", function (event) {
      event.preventDefault();
      dragDepth = Math.max(0, dragDepth - 1);
      if (dragDepth === 0) deactivateDropzone();
    });

    dropzone.addEventListener("drop", function (event) {
      event.preventDefault();
      dragDepth = 0;
      deactivateDropzone();

      var files = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files : null;
      if (!files || !files.length) return;

      fileInput.files = files;
      fileInput.dispatchEvent(new Event("change", { bubbles: true }));
    });
  }
}

// Sidebar catalogo: colapsable con persistencia y animacion de chevron.
(function () {
  var toggle = document.querySelector("[data-catalog-toggle]");
  var submenu = document.querySelector("[data-catalog-submenu]");
  var chevron = document.querySelector("[data-catalog-chevron]");
  if (!toggle || !submenu || !chevron) return;

  var storageKey = "sidebar.catalogo.open";
  var isExpanded = toggle.getAttribute("aria-expanded") === "true";
  var saved = localStorage.getItem(storageKey);
  if (saved === "true" || saved === "false") {
    isExpanded = saved === "true";
  }

  var applyState = function (expanded) {
    toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
    submenu.classList.toggle("hidden", !expanded);
    chevron.classList.toggle("rotate-180", expanded);
  };

  applyState(isExpanded);

  toggle.addEventListener("click", function () {
    isExpanded = !(toggle.getAttribute("aria-expanded") === "true");
    applyState(isExpanded);
    localStorage.setItem(storageKey, isExpanded ? "true" : "false");
  });
})();
