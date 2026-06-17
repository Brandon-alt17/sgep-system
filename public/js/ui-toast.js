/**
 * Muestra un toast dentro de un contenedor [data-toast-root] existente (misma estructura que components/toast.php).
 * @param {string} rootSelector - ej. '#f023-flow-toast'
 * @param {string} message
 * @param {'success'|'warning'|'error'} variant
 * @param {number} [ms]
 */
window.sgToastShow = function (rootSelector, message, variant, ms) {
  var root = document.querySelector(rootSelector);
  if (!root) return;
  var toast = root.querySelector("[data-toast]");
  if (!toast) return;
  var p = toast.querySelector("p");
  if (p) p.textContent = message || "";

  toast.classList.remove("sg-toast--error", "sg-toast--warning");
  if (variant === "error") toast.classList.add("sg-toast--error");
  else if (variant === "warning") toast.classList.add("sg-toast--warning");

  var hideMs = typeof ms === "number" && ms >= 1200 ? ms : variant === "error" ? 7000 : variant === "warning" ? 5000 : 3600;

  toast.classList.add("sg-toast-visible");
  window.clearTimeout(toast.__sgHide);
  toast.__sgHide = window.setTimeout(function () {
    toast.classList.remove("sg-toast-visible");
  }, hideMs);
};

/**
 * Toast de la aplicación: usa el contenedor global (#sg-app-toast-root) o el primero disponible.
 * @param {string} message
 * @param {'success'|'warning'|'error'} [variant]
 * @param {number} [ms]
 */
window.sgToastNotify = function (message, variant, ms) {
  var text = (message || "").trim();
  if (!text || typeof window.sgToastShow !== "function") return;

  var root = document.getElementById("sg-app-toast-root");
  if (root) {
    window.sgToastShow("#sg-app-toast-root", text, variant || "success", ms);
    return;
  }

  var anyRoot = document.querySelector("[data-toast-root][id]");
  if (anyRoot) {
    window.sgToastShow("#" + anyRoot.id, text, variant || "success", ms);
  }
};
