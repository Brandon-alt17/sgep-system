/**
 * Append-only combobox helpers for Momento 1 (competencias / resultados del programa).
 * Expects roots with [data-m1-programa-append-root] and [data-m1-programa-append-target] (CSS selector for textarea).
 * Runs after app.js combobox init; listens to hidden [data-combobox-value] change.
 */
(function () {
  function appendCommaSeparated(textarea, piece) {
    piece = (piece || "").toString().trim();
    if (!piece || !textarea) return;
    var max = parseInt(textarea.getAttribute("maxlength") || "0", 10) || 0;
    var cur = (textarea.value || "").trim();
    var sep = cur === "" ? "" : ", ";
    var next = cur + sep + piece;
    if (max && next.length > max) return;
    textarea.value = next;
    textarea.dispatchEvent(new Event("input", { bubbles: true }));
  }

  function resetComboboxUi(root) {
    var hidden = root.querySelector("[data-combobox-value]");
    var search = root.querySelector("[data-combobox-input]");
    var clearBtn = root.querySelector("[data-combobox-clear]");
    var chevron = root.querySelector("[data-combobox-chevron]");
    if (hidden) hidden.value = "";
    if (search) {
      search.value = "";
      search.dispatchEvent(new Event("input", { bubbles: true }));
    }
    if (clearBtn) clearBtn.classList.add("hidden");
    if (chevron) chevron.classList.remove("hidden");
  }

  document.querySelectorAll("[data-m1-programa-append-root]").forEach(function (root) {
    if (!(root instanceof HTMLElement)) return;
    var targetSel = root.getAttribute("data-m1-programa-append-target");
    if (!targetSel) return;
    var textarea = document.querySelector(targetSel);
    var hidden = root.querySelector("[data-combobox-value]");
    if (!textarea || textarea.tagName !== "TEXTAREA" || !hidden) return;
    hidden.addEventListener("change", function () {
      var v = (hidden.value || "").trim();
      if (!v) return;
      appendCommaSeparated(textarea, v);
      resetComboboxUi(root);
    });
  });
})();
