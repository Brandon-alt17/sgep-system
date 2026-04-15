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

if (fileInput && trigger && form) {
  var openPicker = function () { fileInput.click(); };
  trigger.addEventListener("click", openPicker);

  var sendImport = function () {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", form.getAttribute("action") || window.location.href, true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

    xhr.upload.addEventListener("progress", function (event) {
      if (!progress || !event.lengthComputable) return;
      var percent = Math.max(1, Math.min(100, Math.round((event.loaded / event.total) * 100)));
      progress.classList.remove("hidden");

      var label = progress.querySelector("[data-import-progress-label]");
      var value = progress.querySelector("[data-import-progress-value]");
      var bar = progress.querySelector("[data-import-progress-bar]");
      if (label) label.textContent = "Subiendo archivo...";
      if (value) value.textContent = percent + "%";
      if (bar) bar.style.width = percent + "%";
    });

    xhr.addEventListener("readystatechange", function () {
      if (xhr.readyState !== 4) return;

      if (progress) {
        var label = progress.querySelector("[data-import-progress-label]");
        var value = progress.querySelector("[data-import-progress-value]");
        var bar = progress.querySelector("[data-import-progress-bar]");
        if (label) label.textContent = "Procesando importación...";
        if (value) value.textContent = "100%";
        if (bar) bar.style.width = "100%";
      }

      try {
        var data = JSON.parse(xhr.responseText || "{}");
        if (xhr.status >= 200 && xhr.status < 300 && data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        if (data.message) {
          alert(data.message);
        } else {
          alert("No se pudo completar la importación.");
        }
      } catch (e) {
        alert("Error inesperado durante la importación.");
      }
    });

    xhr.send(new FormData(form));
  };

  fileInput.addEventListener("change", function () {
    if (fileName) {
      fileName.textContent = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : "Sin archivo seleccionado";
    }
    if (fileInput.files && fileInput.files[0]) {
      if (progress) {
        progress.classList.remove("hidden");
        var label = progress.querySelector("[data-import-progress-label]");
        var value = progress.querySelector("[data-import-progress-value]");
        var bar = progress.querySelector("[data-import-progress-bar]");
        if (label) label.textContent = "Preparando carga...";
        if (value) value.textContent = "0%";
        if (bar) bar.style.width = "0%";
      }
      sendImport();
    }
  });
}
