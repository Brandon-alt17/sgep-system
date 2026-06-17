(function () {
  var form = document.getElementById("info-general-form");
  if (!form) return;

  var linkLeave = document.getElementById("f023-footer-leave");

  function serialize() {
    var fd = new FormData(form);
    var pairs = [];
    fd.forEach(function (val, key) {
      if (key === "aprendiz_id") return;
      pairs.push(key + "=" + String(val));
    });
    pairs.sort();
    return pairs.join("&");
  }

  var initial = serialize();
  var dirty = false;

  function setDirty(v) {
    dirty = v;
  }

  function hideLeaveFooter() {
    if (linkLeave) {
      linkLeave.classList.add("hidden");
      linkLeave.setAttribute("href", "#");
    }
  }

  function showLeaveFooter(url) {
    if (linkLeave) {
      linkLeave.setAttribute("href", url);
      linkLeave.classList.remove("hidden");
    }
    if (typeof window.sgToastShow === "function") {
      window.sgToastShow(
        "#f023-flow-toast",
        "Cambios sin guardar: guarde o pulse «Salir sin guardar» al lado.",
        "warning",
        6500
      );
    }
  }

  function refreshDirty() {
    var nowDirty = serialize() !== initial;
    setDirty(nowDirty);
    if (!nowDirty) hideLeaveFooter();
  }

  form.addEventListener(
    "input",
    function () {
      refreshDirty();
    },
    true
  );
  form.addEventListener(
    "change",
    function () {
      refreshDirty();
    },
    true
  );

  function clearDiscapacidadFields() {
    ["asistencia_nombre", "asistencia_tipo", "asistencia_contacto"].forEach(function (fieldName) {
      var input = form.querySelector('[name="' + fieldName + '"]');
      if (input) input.value = "";
    });
  }

  var discapacidadToggle = document.getElementById("toggle-discapacidad");
  if (discapacidadToggle) {
    discapacidadToggle.addEventListener("change", function () {
      if (!discapacidadToggle.checked) {
        clearDiscapacidadFields();
        refreshDirty();
      }
    });
  }

  form.addEventListener("submit", function () {
    if (discapacidadToggle && !discapacidadToggle.checked) {
      clearDiscapacidadFields();
    }
    setDirty(false);
    initial = serialize();
    hideLeaveFooter();
  });

  window.addEventListener("beforeunload", function (e) {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = "";
  });

  document.addEventListener(
    "click",
    function (e) {
      if (!dirty) return;
      var t = e.target;
      if (!(t instanceof Element)) return;
      var a = t.closest("a[href]");
      if (!a) return;
      if (a.getAttribute("data-f023-leave-ok") === "1") return;
      var href = (a.getAttribute("href") || "").trim();
      if (href === "" || href.startsWith("javascript:") || href.startsWith("mailto:")) return;
      if (href.startsWith("#")) return;
      if (a.getAttribute("download") !== null) return;

      e.preventDefault();
      e.stopPropagation();
      showLeaveFooter(a.href);
    },
    true
  );
})();
