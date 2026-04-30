// Utilidad heredada: limita longitud de campos con data-max.
document.querySelectorAll("[data-max]").forEach(function (element) {
  var max = parseInt(element.getAttribute("data-max") || "0", 10);
  if (!max) return;
  element.addEventListener("input", function (event) {
    var value = event.target.value || "";
    if (value.length > max) event.target.value = value.substring(0, max);
  });
});

// Buscador local reusable: filtra items en vivo y permite limpiar con X.
document.querySelectorAll("[data-live-filter-root]").forEach(function (root) {
  var input = root.querySelector("[data-live-filter-input]");
  var clearButton = root.querySelector("[data-live-filter-clear]");
  var emptyState = root.querySelector("[data-live-filter-empty]");
  var itemsContainer = root.parentElement ? root.parentElement.querySelector("[data-live-filter-items]") : null;
  if (!input || !itemsContainer) return;

  var items = Array.prototype.slice.call(itemsContainer.querySelectorAll("[data-live-filter-item]"));
  var normalize = function (value) {
    var text = (value || "").toString().toLowerCase();
    if (typeof text.normalize === "function") {
      text = text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }
    return text.trim();
  };

  var applyFilter = function () {
    var query = normalize(input.value);
    var visibleCount = 0;

    items.forEach(function (item) {
      var source = normalize(item.getAttribute("data-live-filter-text") || item.textContent || "");
      var matches = query === "" || source.indexOf(query) !== -1;
      item.classList.toggle("hidden", !matches);
      if (matches) visibleCount += 1;
    });

    if (clearButton) {
      clearButton.classList.toggle("hidden", query === "");
    }
    if (emptyState) {
      emptyState.classList.toggle("hidden", visibleCount > 0 || query === "");
    }
  };

  input.addEventListener("input", applyFilter);
  if (clearButton) {
    clearButton.addEventListener("click", function () {
      input.value = "";
      applyFilter();
      input.focus();
    });
  }
});

// UI de carga reusable: soporta autosend (XHR) o envio manual.
document.querySelectorAll("[data-import-form]").forEach(function (form) {
  var fileInput = form.querySelector("[data-import-input]");
  var fileName = form.querySelector("[data-import-filename]");
  var trigger = form.querySelector("[data-import-trigger]");
  var progress = form.querySelector("[data-import-progress]");
  var dropzone = form.querySelector("[data-import-dropzone]");
  var autoSend = form.getAttribute("data-import-autosend") !== "false";
  if (!fileInput || !trigger) return;

  var openPicker = function () { fileInput.click(); };
  trigger.addEventListener("click", function (event) {
    event.preventDefault();
    openPicker();
  });

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
    if (!autoSend || !(fileInput.files && fileInput.files[0])) return;
    // Cuando no hay barra de progreso, se usa submit nativo (p.ej. importar programa -> vista de revisión HTML).
    if (!progress) {
      form.submit();
      return;
    }
    setProgressState("Preparando carga...", 0);
    sendImport();
  });

  if (dropzone) {
    var dragDepth = 0;
    var activateDropzone = function () {
      dropzone.classList.add("bg-app-panelHover");
    };
    var deactivateDropzone = function () {
      dropzone.classList.remove("bg-app-panelHover");
    };

    dropzone.addEventListener("click", function (event) {
      if (event.target && event.target.closest("[data-import-trigger]")) return;
      openPicker();
    });

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
});

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

// Filtros de listado: auto-submit sin boton.
document.querySelectorAll("[data-auto-filter-form]").forEach(function (filterForm) {
  var debounceTimer = null;
  var activeController = null;
  var useAjax = filterForm.getAttribute("data-auto-filter-ajax") === "true";
  var targetSelector = filterForm.getAttribute("data-auto-filter-target") || "";
  var mainInput = filterForm.querySelector("[data-auto-filter-main-input]");
  var clearButton = filterForm.querySelector("[data-auto-filter-clear]");

  var runClassicSubmit = function () {
    if (typeof filterForm.requestSubmit === "function") {
      filterForm.requestSubmit();
      return;
    }
    filterForm.submit();
  };

  var runAjaxSubmit = function () {
    if (!targetSelector) {
      runClassicSubmit();
      return;
    }
    var target = document.querySelector(targetSelector);
    if (!target) {
      runClassicSubmit();
      return;
    }

    var action = filterForm.getAttribute("action") || window.location.pathname;
    var url = new URL(action, window.location.origin);
    var formData = new FormData(filterForm);
    var params = new URLSearchParams();
    formData.forEach(function (value, key) {
      if (typeof value === "string") params.append(key, value);
    });
    params.set("ajax", "1");
    url.search = params.toString();

    if (activeController) activeController.abort();
    activeController = new AbortController();

    filterForm.setAttribute("aria-busy", "true");
    fetch(url.toString(), {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Accept": "application/json"
      },
      signal: activeController.signal
    }).then(function (response) {
      if (!response.ok) throw new Error("No se pudo consultar");
      return response.json();
    }).then(function (payload) {
      if (!payload || payload.ok !== true || typeof payload.rowsHtml !== "string") {
        throw new Error("Respuesta invalida");
      }
      target.innerHTML = payload.rowsHtml;
      if (window.history && typeof window.history.replaceState === "function") {
        var browserUrl = new URL(action, window.location.origin);
        browserUrl.search = new URLSearchParams(formData).toString();
        window.history.replaceState(null, "", browserUrl.pathname + browserUrl.search);
      }
    }).catch(function (error) {
      if (error && error.name === "AbortError") return;
      runClassicSubmit();
    }).finally(function () {
      filterForm.removeAttribute("aria-busy");
    });
  };

  var submitForm = function () {
    if (useAjax) {
      runAjaxSubmit();
      return;
    }
    runClassicSubmit();
  };

  var refreshClearButtonState = function () {
    if (!mainInput || !clearButton) return;
    clearButton.classList.toggle("hidden", (mainInput.value || "").trim() === "");
  };

  filterForm.querySelectorAll("[data-auto-filter-change]").forEach(function (field) {
    field.addEventListener("change", submitForm);
  });

  filterForm.querySelectorAll("[data-auto-filter-input]").forEach(function (field) {
    field.addEventListener("input", function () {
      refreshClearButtonState();
      if (debounceTimer) window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(submitForm, 350);
    });
  });

  if (clearButton && mainInput) {
    clearButton.addEventListener("click", function () {
      mainInput.value = "";
      refreshClearButtonState();
      submitForm();
      mainInput.focus();
    });
    refreshClearButtonState();
  }
});

// Selects: anima chevron al enfocar/abrir.
document.querySelectorAll("[data-select-chevron]").forEach(function (chevron) {
  var wrapper = chevron.closest(".relative");
  if (!wrapper) return;
  var select = wrapper.querySelector("select");
  if (!select) return;

  var setOpen = function (open) {
    chevron.classList.toggle("rotate-180", open);
  };

  select.addEventListener("focus", function () { setOpen(true); });
  select.addEventListener("blur", function () { setOpen(false); });
  select.addEventListener("change", function () { setOpen(false); });
});

// Select custom reutilizable: estilo consistente cross-browser.
document.querySelectorAll(".js-custom-select").forEach(function (wrapper) {
  var select = wrapper.querySelector("select");
  if (!select || select.dataset.customized === "true") return;
  if (select.multiple) return;
  select.dataset.customized = "true";

  var existingChevron = wrapper.querySelector("[data-select-chevron]");
  if (existingChevron) existingChevron.remove();

  select.classList.add("sr-only");
  select.tabIndex = -1;

  var trigger = document.createElement("button");
  trigger.type = "button";
  trigger.className = "flex w-full items-center justify-between rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-left text-sm text-app-muted outline-none transition-colors duration-200 hover:border-app-accent focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft";
  trigger.setAttribute("aria-haspopup", "listbox");
  trigger.setAttribute("aria-expanded", "false");

  var label = document.createElement("span");
  label.className = "truncate";

  var chevron = document.createElement("span");
  chevron.className = "ml-3 inline-flex h-4 w-4 shrink-0 text-app-muted transition-transform duration-200 ease-in-out";
  chevron.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>';

  trigger.appendChild(label);
  trigger.appendChild(chevron);

  var menu = document.createElement("div");
  menu.className = "absolute left-0 right-0 z-40 mt-1 hidden overflow-hidden rounded-lg border border-app-borderControlStrong bg-white shadow-xsSoft";

  var list = document.createElement("ul");
  list.className = "max-h-64 overflow-auto py-1";
  list.setAttribute("role", "listbox");

  var optionButtons = [];
  var renderOptions = function () {
    list.innerHTML = "";
    optionButtons = [];
    Array.prototype.forEach.call(select.options, function (opt, index) {
      var item = document.createElement("li");
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "w-full px-3 py-2 text-left text-sm text-app-muted transition-colors duration-150 hover:bg-app-accentSoft hover:text-app-text";
      btn.textContent = opt.textContent || "";
      btn.dataset.value = opt.value;
      btn.setAttribute("role", "option");
      btn.setAttribute("aria-selected", opt.selected ? "true" : "false");
      btn.dataset.index = String(index);
      item.appendChild(btn);
      list.appendChild(item);
      optionButtons.push(btn);
    });
  };

  var syncTriggerLabel = function () {
    var selectedOption = select.options[select.selectedIndex];
    label.textContent = selectedOption ? selectedOption.textContent || "" : "";
    optionButtons.forEach(function (btn) {
      var isSelected = btn.dataset.value === select.value;
      btn.setAttribute("aria-selected", isSelected ? "true" : "false");
      btn.classList.toggle("bg-app-accentSoft", isSelected);
      btn.classList.toggle("text-app-text", isSelected);
      btn.classList.toggle("font-semibold", isSelected);
    });
  };

  var openMenu = function () {
    syncMenuWidth();
    menu.classList.remove("hidden");
    trigger.setAttribute("aria-expanded", "true");
    chevron.classList.add("rotate-180");
  };

  var closeMenu = function () {
    menu.classList.add("hidden");
    trigger.setAttribute("aria-expanded", "false");
    chevron.classList.remove("rotate-180");
  };

  trigger.addEventListener("click", function () {
    if (menu.classList.contains("hidden")) {
      openMenu();
      return;
    }
    closeMenu();
  });

  trigger.addEventListener("keydown", function (event) {
    if (event.key === "ArrowDown" || event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      openMenu();
      var current = optionButtons.find(function (btn) { return btn.dataset.value === select.value; });
      (current || optionButtons[0])?.focus();
    }
  });

  list.addEventListener("click", function (event) {
    var target = event.target;
    if (!(target instanceof HTMLButtonElement)) return;
    var nextValue = target.dataset.value;
    if (typeof nextValue !== "string") return;
    if (select.value !== nextValue) {
      select.value = nextValue;
      select.dispatchEvent(new Event("change", { bubbles: true }));
    }
    syncTriggerLabel();
    closeMenu();
    trigger.focus();
  });

  list.addEventListener("keydown", function (event) {
    var target = event.target;
    if (!(target instanceof HTMLButtonElement)) return;
    var currentIndex = parseInt(target.dataset.index || "-1", 10);
    if (event.key === "Escape") {
      event.preventDefault();
      closeMenu();
      trigger.focus();
      return;
    }
    if (event.key === "ArrowDown") {
      event.preventDefault();
      var next = optionButtons[Math.min(optionButtons.length - 1, currentIndex + 1)];
      if (next) next.focus();
      return;
    }
    if (event.key === "ArrowUp") {
      event.preventDefault();
      var prev = optionButtons[Math.max(0, currentIndex - 1)];
      if (prev) prev.focus();
      return;
    }
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      target.click();
    }
  });

  document.addEventListener("click", function (event) {
    if (!wrapper.contains(event.target)) closeMenu();
  });

  renderOptions();
  syncTriggerLabel();
  var syncMenuWidth = function () {
    var width = trigger.getBoundingClientRect().width;
    if (!width) return;
    menu.style.width = width + "px";
    menu.style.minWidth = width + "px";
  };
  syncMenuWidth();
  window.addEventListener("resize", syncMenuWidth);

  menu.appendChild(list);
  wrapper.appendChild(trigger);
  wrapper.appendChild(menu);
});

// Editor de programas: permite agregar/eliminar competencias y RAEs.
document.querySelectorAll("[data-programa-editor]").forEach(function (form) {
  var competenciasContainer = form.querySelector("[data-competencias-container]");
  var addCompetenciaButton = form.querySelector("[data-add-competencia]");
  var horasLectivaInput = form.querySelector("[data-programa-horas-lectiva]");
  var horasProductivaInput = form.querySelector("[data-programa-horas-productiva]");
  var horasTotalInput = form.querySelector("[data-programa-horas-total-input]");
  var totalCompetenciasEl = document.querySelector("[data-programa-total-competencias]");
  var totalRaesEl = document.querySelector("[data-programa-total-raes]");
  var totalHorasEl = document.querySelector("[data-programa-total-horas]");
  if (!competenciasContainer || !addCompetenciaButton) return;

  var buildRaeNode = function () {
    var row = document.createElement("div");
    row.className = "grid gap-2 md:grid-cols-[160px_1fr_auto]";
    row.setAttribute("data-rae-item", "");
    row.innerHTML = '' +
      '<input type="text" class="w-full rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-sm text-app-muted outline-none transition-colors duration-200 focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft hover:border-app-accent" placeholder="Código RAE" data-field="rae-codigo">' +
      '<input type="text" class="w-full rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-sm text-app-muted outline-none transition-colors duration-200 focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft hover:border-app-accent" placeholder="Descripción del RAE" data-field="rae-descripcion">' +
      '<button type="button" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-app-border text-sm text-app-muted hover:border-app-accent hover:text-app-text transition-colors duration-200 ease-in-out" data-remove-rae aria-label="Eliminar RAE">&times;</button>';
    return row;
  };

  var buildCompetenciaNode = function () {
    var article = document.createElement("article");
    article.className = "rounded-xl border border-app-border bg-app-panel p-4 shadow-xsSoft bg-app-panelSubtle";
    article.setAttribute("data-competencia-item", "");
    article.innerHTML = '' +
      '<div class="grid gap-3 md:grid-cols-2">' +
      '  <label class="text-sm font-medium text-app-text">Código competencia' +
      '    <input type="text" class="w-full rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-sm text-app-muted outline-none transition-colors duration-200 focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft hover:border-app-accent" data-field="competencia-codigo">' +
      '  </label>' +
      '  <label class="text-sm font-medium text-app-text">Nombre competencia' +
      '    <input type="text" class="w-full rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-sm text-app-muted outline-none transition-colors duration-200 focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft hover:border-app-accent" data-field="competencia-nombre">' +
      '  </label>' +
      '</div>' +
      '<div class="mt-4 space-y-2" data-raes-container></div>' +
      '<div class="mt-4 flex gap-2">' +
      '  <button type="button" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-app-border text-sm text-app-muted hover:border-app-accent hover:text-app-text transition-colors duration-200 ease-in-out px-3" data-add-rae>Nuevo RAE</button>' +
      '  <button type="button" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-app-border text-sm text-app-muted hover:border-app-accent hover:text-app-text transition-colors duration-200 ease-in-out px-3" data-remove-competencia>Eliminar competencia</button>' +
      '</div>';
    article.querySelector("[data-raes-container]").appendChild(buildRaeNode());
    return article;
  };

  var updateHours = function () {
    var lectiva = parseInt((horasLectivaInput && horasLectivaInput.value) || "0", 10);
    var productiva = parseInt((horasProductivaInput && horasProductivaInput.value) || "0", 10);
    if (isNaN(lectiva)) lectiva = 0;
    if (isNaN(productiva)) productiva = 0;
    var total = lectiva + productiva;
    var totalText = total > 0 ? String(total) : "N/D";
    if (horasTotalInput) horasTotalInput.value = totalText;
    if (totalHorasEl) totalHorasEl.textContent = total > 0 ? totalText + "h" : "N/D";
  };

  var refreshCounters = function () {
    var competencias = Array.prototype.slice.call(competenciasContainer.querySelectorAll("[data-competencia-item]"));
    var totalCompetencias = competencias.length;
    var totalRaes = 0;
    competencias.forEach(function (competenciaNode, compIndex) {
      var codigoInput = competenciaNode.querySelector("[data-field='competencia-codigo']");
      var nombreInput = competenciaNode.querySelector("[data-field='competencia-nombre']");
      if (codigoInput) codigoInput.name = "competencias[" + compIndex + "][codigo]";
      if (nombreInput) nombreInput.name = "competencias[" + compIndex + "][nombre]";

      var raes = Array.prototype.slice.call(competenciaNode.querySelectorAll("[data-rae-item]"));
      raes.forEach(function (raeNode, raeIndex) {
        var raeCodigo = raeNode.querySelector("[data-field='rae-codigo']");
        var raeDescripcion = raeNode.querySelector("[data-field='rae-descripcion']");
        if (raeCodigo) raeCodigo.name = "competencias[" + compIndex + "][resultados][" + raeIndex + "][codigo]";
        if (raeDescripcion) raeDescripcion.name = "competencias[" + compIndex + "][resultados][" + raeIndex + "][descripcion]";
      });
      totalRaes += raes.length;
    });

    if (totalCompetenciasEl) totalCompetenciasEl.textContent = String(totalCompetencias);
    if (totalRaesEl) totalRaesEl.textContent = String(totalRaes);
  };

  if (horasLectivaInput) horasLectivaInput.addEventListener("input", updateHours);
  if (horasProductivaInput) horasProductivaInput.addEventListener("input", updateHours);

  addCompetenciaButton.addEventListener("click", function () {
    competenciasContainer.appendChild(buildCompetenciaNode());
    refreshCounters();
  });

  competenciasContainer.addEventListener("click", function (event) {
    var target = event.target;
    if (!(target instanceof HTMLElement)) return;

    var addRaeButton = target.closest("[data-add-rae]");
    if (addRaeButton) {
      var competencia = addRaeButton.closest("[data-competencia-item]");
      if (!competencia) return;
      var raesContainer = competencia.querySelector("[data-raes-container]");
      if (!raesContainer) return;
      raesContainer.appendChild(buildRaeNode());
      refreshCounters();
      return;
    }

    var removeRaeButton = target.closest("[data-remove-rae]");
    if (removeRaeButton) {
      var raeNode = removeRaeButton.closest("[data-rae-item]");
      if (!raeNode) return;
      var parentContainer = raeNode.parentElement;
      raeNode.remove();
      if (parentContainer && parentContainer.querySelectorAll("[data-rae-item]").length === 0) {
        parentContainer.appendChild(buildRaeNode());
      }
      refreshCounters();
      return;
    }

    var removeCompetenciaButton = target.closest("[data-remove-competencia]");
    if (removeCompetenciaButton) {
      var compNode = removeCompetenciaButton.closest("[data-competencia-item]");
      if (!compNode) return;
      compNode.remove();
      if (competenciasContainer.querySelectorAll("[data-competencia-item]").length === 0) {
        competenciasContainer.appendChild(buildCompetenciaNode());
      }
      refreshCounters();
    }
  });

  if (competenciasContainer.querySelectorAll("[data-competencia-item]").length === 0) {
    competenciasContainer.appendChild(buildCompetenciaNode());
  }

  updateHours();
  refreshCounters();
});

// Edicion puntual en vista "ver programa" (pencil -> check/x).
document.querySelectorAll("[data-inline-edit-root]").forEach(function (root) {
  var openButton = root.querySelector("[data-inline-edit-open]");
  var saveButton = root.querySelector("[data-inline-edit-save]");
  var cancelButton = root.querySelector("[data-inline-edit-cancel]");
  var inputs = Array.prototype.slice.call(root.querySelectorAll("[data-inline-input]"));
  var form = root.querySelector("[data-inline-edit-form]");
  var viewBlock = root.querySelector("[data-inline-view]");
  var headerBlocks = Array.prototype.slice.call(root.querySelectorAll("[data-inline-header]"));
  if (!openButton || !cancelButton || !saveButton || inputs.length === 0 || !form) return;

  var originalValues = {};
  var isEditing = false;
  var showToast = function (message) {
    var toastRoot = document.querySelector("[data-toast-root]");
    var toast = toastRoot ? toastRoot.querySelector("[data-toast]") : null;
    if (!toast) return;
    var textNode = toast.querySelector("p");
    if (textNode) textNode.textContent = message;
    toast.classList.add("sg-toast-visible");
    window.clearTimeout(toast.__hideTimer);
    toast.__hideTimer = window.setTimeout(function () {
      toast.classList.remove("sg-toast-visible");
    }, 3200);
  };

  var setEditingState = function (editing) {
    isEditing = editing;
    root.classList.toggle("border-app-borderControlStrong", editing);
    root.classList.toggle("shadow-xsSoft", editing);
    if (viewBlock) viewBlock.classList.toggle("hidden", editing);
    headerBlocks.forEach(function (headerBlock) {
      headerBlock.classList.toggle("hidden", editing);
    });
    form.classList.toggle("hidden", !editing);
    openButton.classList.toggle("hidden", editing);
    saveButton.classList.toggle("hidden", !editing);
    cancelButton.classList.toggle("hidden", !editing);
  };

  openButton.addEventListener("click", function () {
    document.querySelectorAll("[data-inline-edit-root].is-editing").forEach(function (otherRoot) {
      if (otherRoot === root) return;
      var otherCancel = otherRoot.querySelector("[data-inline-edit-cancel]");
      if (otherCancel) otherCancel.click();
    });

    root.classList.add("is-editing");
    inputs.forEach(function (input) {
      originalValues[input.name] = input.value;
    });
    setEditingState(true);
    showToast("Modo edición activado.");
  });

  cancelButton.addEventListener("click", function () {
    if (isEditing) {
      inputs.forEach(function (input) {
        if (Object.prototype.hasOwnProperty.call(originalValues, input.name)) {
          input.value = originalValues[input.name];
        }
      });
    }
    root.classList.remove("is-editing");
    setEditingState(false);
    showToast("Edición cancelada. No se guardaron cambios.");
  });

  setEditingState(false);

  if (root.getAttribute("data-inline-auto-open") === "1") {
    openButton.click();
  }
});

// Toast de servidor: animación de salida automática.
document.querySelectorAll("[data-toast-root] [data-toast].sg-toast-visible").forEach(function (toast) {
  window.setTimeout(function () {
    toast.classList.remove("sg-toast-visible");
  }, 3200);
});
