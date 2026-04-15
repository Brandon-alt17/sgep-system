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
var dropzone = document.querySelector("[data-import-dropzone]");

if (fileInput && trigger && dropzone) {
  var openPicker = function () { fileInput.click(); };
  trigger.addEventListener("click", openPicker);
  dropzone.addEventListener("click", openPicker);

  fileInput.addEventListener("change", function () {
    if (fileName) fileName.textContent = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : "Sin archivo seleccionado";
  });
}
