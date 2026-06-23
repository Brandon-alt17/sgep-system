(function () {
  function onlyDigits(s) {
    return (s || "").replace(/\D/g, "");
  }

  function formatFromDigits(digits) {
    if (digits.length <= 2) return digits;
    if (digits.length <= 4) return digits.slice(0, 2) + "/" + digits.slice(2);
    return digits.slice(0, 2) + "/" + digits.slice(2, 4) + "/" + digits.slice(4, 8);
  }

  function isValidDmY(str) {
    var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec((str || "").trim());
    if (!m) return false;
    var d = parseInt(m[1], 10);
    var mo = parseInt(m[2], 10);
    var y = parseInt(m[3], 10);
    if (y < 1900 || y > 2100) return false;
    if (mo < 1 || mo > 12) return false;
    var dim = new Date(y, mo, 0).getDate();
    if (d < 1 || d > dim) return false;
    return true;
  }

  function toastInvalid() {
    if (typeof window.sgToastShow === "function") {
      window.sgToastShow("#f023-flow-toast", "Revise la fecha (use día/mes/año, ej. 05/11/2026).", "warning", 4800);
    }
  }

  function bind(el) {
    if (!(el instanceof HTMLInputElement) || el.type !== "text") return;

    el.addEventListener("input", function () {
      var d = onlyDigits(el.value).slice(0, 8);
      var next = formatFromDigits(d);
      if (next !== el.value) {
        var pos = next.length;
        el.value = next;
        try {
          el.setSelectionRange(pos, pos);
        } catch (e) {
          /* no-op */
        }
      }
      el.setCustomValidity("");
    });

    el.addEventListener("blur", function () {
      var v = (el.value || "").trim();
      if (v === "") {
        el.setCustomValidity("");
        return;
      }
      if (!isValidDmY(v)) {
        toastInvalid();
      } else {
        el.setCustomValidity("");
      }
    });
  }

  window.sgBindDateInputDmy = bind;
  document.querySelectorAll('input[type="text"][data-date-input="dmy"]').forEach(bind);

  document.addEventListener(
    "submit",
    function (ev) {
      var form = ev.target;
      if (!(form instanceof HTMLFormElement)) return;
      var fields = form.querySelectorAll('input[type="text"][data-date-input="dmy"]');
      for (var i = 0; i < fields.length; i++) {
        var inp = fields[i];
        if (!(inp instanceof HTMLInputElement)) continue;
        var v = (inp.value || "").trim();
        if (v !== "" && !isValidDmY(v)) {
          ev.preventDefault();
          toastInvalid();
          inp.focus();
          return;
        }
      }
    },
    true
  );
})();
