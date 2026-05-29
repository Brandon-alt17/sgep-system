// Utilidad heredada: limita longitud de campos con data-max.
document.querySelectorAll("[data-max]").forEach(function (element) {
  var max = parseInt(element.getAttribute("data-max") || "0", 10);
  if (!max) return;
  element.addEventListener("input", function (event) {
    var value = event.target.value || "";
    if (value.length > max) event.target.value = value.substring(0, max);
  });
});

// Toast global reutilizable (cuando existe data-toast-root en la vista).
var showGlobalToast = function (message) {
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

// Textareas que ajustan su altura al contenido (atributo data-auto-resize-textarea).
var sgAutoResizeTextarea = function (el) {
  if (!el || el.tagName !== "TEXTAREA") return;
  el.style.overflow = "hidden";
  el.style.resize = "none";
  el.style.height = "auto";
  el.style.height = el.scrollHeight + "px";
};
document.addEventListener("input", function (event) {
  var t = event.target;
  if (t && t.matches && t.matches("textarea[data-auto-resize-textarea]")) {
    sgAutoResizeTextarea(t);
  }
});
window.addEventListener("load", function () {
  document.querySelectorAll("textarea[data-auto-resize-textarea]").forEach(sgAutoResizeTextarea);
});

// Desactiva sugerencias/autorrelleno del navegador en inputs de texto.
document.querySelectorAll("form").forEach(function (form) {
  if (!(form instanceof HTMLElement)) return;
  form.setAttribute("autocomplete", "off");
});

document.querySelectorAll("input, textarea").forEach(function (field) {
  if (!(field instanceof HTMLElement)) return;
  var tag = field.tagName.toLowerCase();
  if (tag === "textarea") {
    field.setAttribute("autocomplete", "new-password");
    field.setAttribute("autocorrect", "off");
    field.setAttribute("autocapitalize", "off");
    field.setAttribute("spellcheck", "false");
    return;
  }

  var input = field;
  var type = ((input.getAttribute("type") || "text") + "").toLowerCase();
  var skipTypes = ["hidden", "checkbox", "radio", "file", "submit", "button", "reset", "color", "range"];
  if (skipTypes.indexOf(type) !== -1) return;

  // "off" suele ser ignorado por algunos navegadores para historial de campos;
  // "new-password" reduce mucho las sugerencias de autocompletado.
  input.setAttribute("autocomplete", "new-password");
  input.setAttribute("autocorrect", "off");
  input.setAttribute("autocapitalize", "off");
  input.setAttribute("spellcheck", "false");
  input.setAttribute("data-lpignore", "true");
  if (
    input.closest("[data-live-filter-root]") ||
    input.closest("[data-import-conflicts-root]") ||
    input.matches("[data-remote-table-filter-q]") ||
    input.matches("[data-live-filter-input]") ||
    input.hasAttribute("data-combobox-input") ||
    input.hasAttribute("data-inline-input") ||
    input.closest("[data-inline-edit-form].hidden") ||
    input.closest(".modal-overlay")
  ) {
    return;
  }

  var typeForReadonly = ((input.getAttribute("type") || "text") + "").toLowerCase();
  if (["text", "search", "number", "email", "url", "tel"].indexOf(typeForReadonly) !== -1) {
    input.readOnly = true;
    input.addEventListener("focus", function onFocusUnlock() {
      input.readOnly = false;
      input.removeEventListener("focus", onFocusUnlock);
    });
  }
});

// Buscador local reusable: filtra items en vivo y permite limpiar con X.
document.querySelectorAll("[data-live-filter-root]").forEach(function (root) {
  var input = root.querySelector("[data-live-filter-input]");
  var clearButton = root.querySelector("[data-live-filter-clear]");
  var emptyState = root.querySelector("[data-live-filter-empty]");
  var itemsTargetSelector = (root.getAttribute("data-live-filter-items-target") || "").trim();
  var itemsContainer = null;
  if (itemsTargetSelector !== "") {
    itemsContainer = document.querySelector(itemsTargetSelector);
  } else if (root.parentElement) {
    itemsContainer = root.parentElement.querySelector("[data-live-filter-items]");
  }
  if (!input || !itemsContainer) return;

  var getItems = function () {
    return Array.prototype.slice.call(itemsContainer.querySelectorAll("[data-live-filter-item]"));
  };

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

    getItems().forEach(function (item) {
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

// Combobox reusable: input + lista filtrable para seleccionar valor.
var initComboboxes = function (scope) {
  var rootScope = scope && scope.querySelectorAll ? scope : document;
  rootScope.querySelectorAll("[data-combobox-root]").forEach(function (root) {
    if (!(root instanceof HTMLElement)) return;
    if (root.dataset.comboboxReady === "1") return;
    var nestedComboboxRoot = root.querySelector("[data-combobox-root]");
    if (nestedComboboxRoot && nestedComboboxRoot !== root) return;
    root.dataset.comboboxReady = "1";

    var hiddenInput = root.querySelector("[data-combobox-value]");
    var searchInput = root.querySelector("[data-combobox-input]");
    var clearButton = root.querySelector("[data-combobox-clear]");
    var chevron = root.querySelector("[data-combobox-chevron]");
    var menu = root.querySelector("[data-combobox-menu]");
    var emptyState = root.querySelector("[data-combobox-empty]");
    if (!hiddenInput || !searchInput || !menu) return;

    var getOptions = function () {
      return Array.prototype.slice.call(menu.querySelectorAll("[data-combobox-option]"));
    };
    var normalize = function (value) {
      var text = (value || "").toString().toLowerCase();
      if (typeof text.normalize === "function") {
        text = text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
      }
      return text.trim();
    };
    var setChevronOpen = function (open) {
      if (!chevron) return;
      chevron.classList.toggle("rotate-180", !!open);
    };
    // Misma X que el buscador (live_filter_search): visible solo con texto; chevron solo con campo vacío (como select).
    var syncClearAndChevron = function () {
      var textEmpty = (searchInput.value || "").trim() === "";
      if (clearButton) clearButton.classList.toggle("hidden", textEmpty);
      if (chevron) chevron.classList.toggle("hidden", !textEmpty);
    };
    var listEl = menu.querySelector("ul");
    var menuPlaceholder = null;
    var repositionHandler = function () {
      if (menu.classList.contains("hidden")) return;
      positionMenu();
    };
    var positionMenu = function () {
      if (menu.classList.contains("hidden")) return;
      var anchorRect = searchInput.getBoundingClientRect();
      var gap = 4;
      var spaceBelow = window.innerHeight - anchorRect.bottom - gap - 8;
      var spaceAbove = anchorRect.top - gap - 8;
      var forceDropUp = root.dataset.comboboxDropUp === "1";
      var openUp = forceDropUp || (spaceBelow < 120 && spaceAbove > spaceBelow);
      var maxList = Math.min(210, Math.max(96, openUp ? spaceAbove : spaceBelow));
      if (listEl) listEl.style.maxHeight = maxList + "px";
      menu.style.position = "fixed";
      menu.style.left = Math.max(8, anchorRect.left) + "px";
      menu.style.width = anchorRect.width + "px";
      menu.style.minWidth = anchorRect.width + "px";
      menu.style.maxWidth = anchorRect.width + "px";
      menu.style.right = "auto";
      menu.style.boxSizing = "border-box";
      menu.style.bottom = "auto";
      if (openUp) {
        menu.style.top = "auto";
        menu.style.bottom = Math.max(8, window.innerHeight - anchorRect.top + gap) + "px";
      } else {
        menu.style.bottom = "auto";
        menu.style.top = anchorRect.bottom + gap + "px";
      }
    };
    var attachMenuToBody = function () {
      if (menu.parentNode === document.body) return;
      menuPlaceholder = document.createComment("sg-combobox-menu");
      root.replaceChild(menuPlaceholder, menu);
      document.body.appendChild(menu);
    };
    var restoreMenuToRoot = function () {
      if (menuPlaceholder && menuPlaceholder.parentNode === root) {
        root.replaceChild(menu, menuPlaceholder);
        menuPlaceholder = null;
      } else if (menu.parentNode === document.body && menuPlaceholder === null) {
        root.appendChild(menu);
      }
    };
    var openMenu = function () {
      var wasHidden = menu.classList.contains("hidden");
      menu.classList.remove("hidden");
      attachMenuToBody();
      if (wasHidden) {
        window.addEventListener("scroll", repositionHandler, true);
        window.addEventListener("resize", repositionHandler);
      }
      window.requestAnimationFrame(function () {
        positionMenu();
        if (wasHidden) {
          window.requestAnimationFrame(function () {
            if (!menu.classList.contains("hidden")) {
              positionMenu();
            }
          });
        }
      });
    };
    var closeMenu = function () {
      menu.classList.add("hidden");
      menu.style.position = "";
      menu.style.left = "";
      menu.style.top = "";
      menu.style.width = "";
      menu.style.minWidth = "";
      menu.style.maxWidth = "";
      menu.style.right = "";
      menu.style.boxSizing = "";
      if (listEl) listEl.style.maxHeight = "";
      window.removeEventListener("scroll", repositionHandler, true);
      window.removeEventListener("resize", repositionHandler);
      restoreMenuToRoot();
    };
    var isPointerInsideCombobox = function () {
      var rootHovered = root.matches(":hover");
      var menuHovered = menu.matches(":hover");
      return rootHovered || menuHovered;
    };
    var openMenuIfPointerInside = function () {
      var pointerInside = isPointerInsideCombobox();
      if (!pointerInside) return;
      openMenu();
    };
    var applyFilter = function () {
      var query = normalize(searchInput.value);
      var visibleCount = 0;
      getOptions().forEach(function (opt) {
        var source = normalize(opt.getAttribute("data-search") || opt.getAttribute("data-label") || opt.textContent || "");
        var matches = query === "" || source.indexOf(query) !== -1;
        opt.classList.toggle("hidden", !matches);
        if (matches) visibleCount += 1;
      });
      if (emptyState) emptyState.classList.toggle("hidden", visibleCount > 0);
      syncClearAndChevron();
    };
    var syncLabelFromValue = function () {
      var selected = getOptions().find(function (opt) {
        return (opt.getAttribute("data-value") || "") === (hiddenInput.value || "");
      });
      searchInput.value = selected ? (selected.getAttribute("data-label") || selected.textContent || "") : "";
      syncClearAndChevron();
    };
    var clearValue = function () {
      hiddenInput.value = "";
      hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
      searchInput.value = "";
      searchInput.setCustomValidity("");
      applyFilter();
      openMenuIfPointerInside();
      searchInput.focus();
    };

    menu.addEventListener("click", function (event) {
      var target = event.target;
      if (!(target instanceof Element)) return;
      var option = target.closest("[data-combobox-option]");
      if (!option) return;
      var nextValue = option.getAttribute("data-value") || "";
      var nextLabel = option.getAttribute("data-label") || option.textContent || "";
      hiddenInput.value = nextValue;
      hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
      searchInput.value = nextLabel;
      searchInput.setCustomValidity("");
      closeMenu();
      syncClearAndChevron();
    });

    searchInput.addEventListener("focus", function () {
      if (!isPointerInsideCombobox()) {
        setChevronOpen(false);
        return;
      }
      setChevronOpen(true);
      applyFilter();
      openMenuIfPointerInside();
    });
    searchInput.addEventListener("blur", function () {
      window.setTimeout(function () {
        setChevronOpen(false);
      }, 0);
    });
    searchInput.addEventListener("input", function () {
      hiddenInput.value = "";
      searchInput.setCustomValidity("");
      applyFilter();
      openMenuIfPointerInside();
    });
    searchInput.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeMenu();
      }
    });

    if (clearButton) {
      clearButton.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        clearValue();
      });
    }

    var parentForm = root.closest("form");
    if (parentForm && parentForm.dataset.comboboxValidationBound !== "1") {
      parentForm.dataset.comboboxValidationBound = "1";
      parentForm.addEventListener("submit", function (event) {
        var requiredCombobox = parentForm.querySelector("[data-combobox-value][data-combobox-required='1']");
        if (!requiredCombobox) return;
        var requiredRoot = requiredCombobox.closest("[data-combobox-root]");
        var requiredInput = requiredRoot ? requiredRoot.querySelector("[data-combobox-input]") : null;
        if ((requiredCombobox.value || "").trim() !== "") return;
        event.preventDefault();
        if (requiredInput) {
          requiredInput.setCustomValidity("Selecciona un programa.");
          requiredInput.reportValidity();
          requiredInput.focus();
        }
      });
    }

    document.addEventListener("click", function (event) {
      var t = event.target;
      if (!(t instanceof Node)) return;
      if (root.contains(t)) return;
      if (menu.contains(t)) return;
      closeMenu();
    });
    syncLabelFromValue();
    applyFilter();
  });
};
initComboboxes(document);

// Listados: actualiza solo tbody (o nodo destino) por GET + JSON; debounce en texto; replaceState sin perder foco.
document.querySelectorAll("[data-remote-table-filter-form]").forEach(function (form) {
  var targetSelector = form.getAttribute("data-remote-table-filter-target") || "";
  var paginationSelector = form.getAttribute("data-remote-table-filter-pagination") || "";
  var counterSelector = form.getAttribute("data-remote-table-filter-counter") || "";
  var debounceMs = parseInt(form.getAttribute("data-remote-table-filter-debounce") || "350", 10);
  if (isNaN(debounceMs) || debounceMs < 0) debounceMs = 350;

  var qInput = form.querySelector("[data-remote-table-filter-q]");
  var clearButton = form.querySelector("[data-remote-table-filter-clear]");
  var target = document.querySelector(targetSelector);
  var paginationTarget = paginationSelector
    ? document.querySelector(paginationSelector)
    : null;
  var counterTarget = counterSelector
    ? document.querySelector(counterSelector)
    : null;

  if (!qInput || !target) return;

  var debounceTimer = null;
  var activeController = null;

  var refreshClearVisibility = function () {
    if (!clearButton || !qInput) return;
    clearButton.classList.toggle("hidden", (qInput.value || "").trim() === "");
  };

  var appendFormParams = function (params, formData) {
    formData.forEach(function (value, key) {
      if (typeof value !== "string") return;
      if (key === "page") return;
      var trimmed = value.trim();
      if (trimmed === "") return;
      if (key === "datos_pendientes" && trimmed === "0") return;
      params.set(key, trimmed);
    });
  };

  var buildFormActionUrl = function (includeAjax) {
    var action = form.getAttribute("action") || window.location.pathname;
    var url = new URL(action, window.location.origin);
    var params = new URLSearchParams();
    appendFormParams(params, new FormData(form));
    if (includeAjax) {
      params.set("ajax", "1");
    }
    url.search = params.toString();
    return url;
  };

  var buildFetchUrl = function () {
    return buildFormActionUrl(true).toString();
  };

  var buildBrowserUrl = function () {
    var url = buildFormActionUrl(false);
    return url.pathname + url.search;
  };

  var runFetch = function () {
    if (activeController) activeController.abort();
    activeController = new AbortController();

    var cursorPos = qInput.selectionStart;
    var cursorEnd = qInput.selectionEnd;

    form.setAttribute("aria-busy", "true");

    fetch(buildFetchUrl(), {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json"
      },
      signal: activeController.signal
    })
      .then(function (response) {
        if (!response.ok) throw new Error("La solicitud no fue exitosa");
        return response.json();
      })
      .then(function (payload) {
        if (!payload || payload.ok !== true || typeof payload.rowsHtml !== "string") {
          throw new Error("Respuesta invalida");
        }
        target.innerHTML = payload.rowsHtml;
        if (paginationTarget && typeof payload.paginationHtml === "string") {
          paginationTarget.innerHTML = payload.paginationHtml;
        }
        if (counterTarget && typeof payload.total === "number") {
          counterTarget.textContent =
            payload.total + " " + (payload.total === 1 ? "aprendiz" : "aprendices");
        }
        if (window.history && typeof window.history.replaceState === "function") {
          window.history.replaceState(null, "", buildBrowserUrl());
        }
        initComboboxes(target);
        refreshClearVisibility();
        qInput.focus();
        if (typeof cursorPos === "number" && typeof cursorEnd === "number") {
          try {
            qInput.setSelectionRange(cursorPos, cursorEnd);
          } catch (selectionError) {
            /* input type may not support selection */
          }
        }
      })
      .catch(function (error) {
        if (error && error.name === "AbortError") return;
      })
      .finally(function () {
        form.removeAttribute("aria-busy");
      });
  };

  var scheduleFetch = function () {
    if (debounceTimer) window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(runFetch, debounceMs);
  };

  qInput.addEventListener("input", function () {
    refreshClearVisibility();
    scheduleFetch();
  });

  form.addEventListener("submit", function (event) {
    event.preventDefault();
    if (debounceTimer) window.clearTimeout(debounceTimer);
    runFetch();
  });

  form.querySelectorAll("[data-remote-table-filter-change]").forEach(function (field) {
    field.addEventListener("change", function () {
      if (debounceTimer) window.clearTimeout(debounceTimer);
      runFetch();
    });
  });

  if (clearButton) {
    clearButton.addEventListener("click", function () {
      qInput.value = "";
      refreshClearVisibility();
      if (debounceTimer) window.clearTimeout(debounceTimer);
      runFetch();
      qInput.focus();
    });
  }

  var dpCheckbox = form.querySelector("[data-datos-pendientes-checkbox]");
  var dpInput = form.querySelector("[data-datos-pendientes-input]");
  if (dpCheckbox && dpInput) {
    dpCheckbox.addEventListener("change", function () {
      dpInput.value = dpCheckbox.checked ? "1" : "0";
      if (debounceTimer) window.clearTimeout(debounceTimer);
      runFetch();
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

  var showImportToast = function (message, variant) {
    var text = (message || "").trim();
    if (!text) return;
    if (typeof window.sgToastShow === "function") {
      window.sgToastShow(
        "#import-toast-root",
        text,
        variant === "error" ? "error" : "warning",
        variant === "error" ? 7000 : 6500
      );
      return;
    }
    showGlobalToast(text);
  };

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
        if (data.errors && data.errors.length) {
          showImportToast(data.errors[0], "warning");
        } else if (data.message) {
          showImportToast(data.message, "warning");
        } else {
          showImportToast("Importación fallida.", "warning");
        }
      } catch (e) {
        setProgressState("Error inesperado durante la importación", 0);
        showImportToast("Error inesperado.", "error");
      }
    });

    xhr.addEventListener("error", function () {
      setProgressState("Error de red durante la carga", 0);
      showImportToast("Error de red.", "error");
    });

    xhr.addEventListener("timeout", function () {
      setProgressState("La importación tardó demasiado", 95);
      showImportToast("Tiempo de espera agotado.", "warning");
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
  var nivelSelect = form.querySelector("[data-programa-nivel-select]");
  var nivelDisplayEl = document.querySelector("[data-programa-nivel-display]");
  var totalCompetenciasEl = document.querySelector("[data-programa-total-competencias]");
  var totalRaesEl = document.querySelector("[data-programa-total-raes]");
  var totalHorasEl = document.querySelector("[data-programa-total-horas]");
  if (!competenciasContainer || !addCompetenciaButton) return;

  var inputClass =
    "w-full rounded-lg border border-app-borderControlStrong px-3 py-2 text-sm text-app-text outline-none transition-colors duration-200 focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft bg-white";
  var labelClass = "block text-sm font-medium text-app-textSubtle";
  var tdClass =
    "h-[50px] px-2 py-2 text-left text-sm align-top transition-colors duration-200 ease-in-out";
  var raeRemoveBtnClass =
    "font-app inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-rose-300 bg-rose-50 text-rose-700 no-underline hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700";
  var trashSvg =
    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
  var plusSvg =
    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M5 12h14"/><path d="M12 5v14"/></svg>';

  var buildRaeNode = function () {
    var row = document.createElement("tr");
    row.className = "border-b border-app-borderSoft";
    row.setAttribute("data-rae-item", "");
    row.innerHTML =
      '<td class="' +
      tdClass +
      ' w-32">' +
      '<input type="text" class="' +
      inputClass +
      ' ui-monospace font-mono" placeholder="Código RAE" data-field="rae-codigo" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">' +
      "</td>" +
      '<td class="' +
      tdClass +
      '">' +
      '<textarea rows="1" class="' +
      inputClass +
      ' min-h-[2.5rem] resize-none overflow-hidden" placeholder="Descripción del RAE" data-field="rae-descripcion" data-auto-resize-textarea autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>' +
      "</td>" +
      '<td class="' +
      tdClass +
      '">' +
      '<button type="button" class="' +
      raeRemoveBtnClass +
      '" data-remove-rae aria-label="Eliminar RAE">' +
      '<span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-4 [&_svg]:w-4">' +
      trashSvg +
      "</span></button></td>";
    var descTa = row.querySelector("[data-field='rae-descripcion']");
    if (descTa) sgAutoResizeTextarea(descTa);
    return row;
  };

  var buildCompetenciaNode = function () {
    var article = document.createElement("article");
    article.className =
      "rounded-[10px] border border-app-border bg-app-panelSubtle p-6 shadow-xsSoft min-h-[96px] transition-[border-color,box-shadow] duration-300 ease-out motion-reduce:transition-none";
    article.setAttribute("data-competencia-item", "");
    article.innerHTML =
      '<div class="mb-3 w-full grid grid-cols-1 gap-3 md:grid-cols-10 md:gap-3">' +
      '  <label class="' +
      labelClass +
      ' md:col-span-10">Nombre competencia <span class="text-rose-600">*</span>' +
      '    <input type="text" class="' +
      inputClass +
      '" data-field="competencia-nombre" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">' +
      "  </label>" +
      '  <label class="' +
      labelClass +
      ' md:col-span-7">Código competencia <span class="text-rose-600">*</span>' +
      '    <input type="text" class="' +
      inputClass +
      ' ui-monospace font-mono" data-field="competencia-codigo" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">' +
      "  </label>" +
      '  <label class="' +
      labelClass +
      ' md:col-span-3">Horas competencia <span class="text-rose-600">*</span>' +
      '    <input type="number" min="0" step="1" inputmode="numeric" class="' +
      inputClass +
      '" data-field="competencia-horas" placeholder="0" autocomplete="off">' +
      "  </label>" +
      "</div>" +
      '<div class="mt-3 border-t border-app-borderSoft pt-6 flex justify-end">' +
      '  <button type="button" class="font-app inline-flex items-center justify-center gap-2 rounded-md border border-app-borderControl bg-app-panelSubtle px-[18px] py-2 text-sm font-medium text-app-muted no-underline hover:bg-app-accentSoft hover:text-app-accent" data-add-rae>' +
      '    <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-4 [&_svg]:w-4">' +
      plusSvg +
      "</span>Nuevo RAE</button>" +
      "</div>" +
      '<div class="mt-3 overflow-hidden">' +
      '  <table class="w-full border-collapse [&>tbody>tr:hover>td]:bg-app-panelHover">' +
      "    <thead><tr>" +
      '      <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Código RAE</th>' +
      '      <th class="h-[50px] px-2 text-left text-sm font-semibold text-app-muted align-middle">Resultado de aprendizaje</th>' +
      '      <th class="h-[50px] w-10 px-2 text-left text-sm font-semibold text-app-muted align-middle">Acción</th>' +
      "    </tr></thead>" +
      '    <tbody data-raes-container></tbody>' +
      "  </table>" +
      "</div>" +
      '<div class="mt-3 border-t border-app-borderSoft pt-3 flex justify-end">' +
      '  <button type="button" class="inline-flex items-center gap-2 rounded-md border border-rose-300 bg-rose-50 px-[18px] py-2 text-sm font-medium text-rose-700 no-underline transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-remove-competencia>' +
      '    <span class="inline-flex h-4 w-4 items-center justify-center [&_svg]:h-4 [&_svg]:w-4">' +
      trashSvg +
      "</span>Eliminar competencia</button>" +
      "</div>";
    var raesBody = article.querySelector("[data-raes-container]");
    if (raesBody) raesBody.appendChild(buildRaeNode());
    return article;
  };

  var syncNivelCard = function () {
    if (!nivelSelect || !nivelDisplayEl) return;
    var opt = nivelSelect.options[nivelSelect.selectedIndex];
    var val = (opt && opt.value) || "";
    nivelDisplayEl.textContent = val !== "" ? opt.text : "Nivel no seleccionado";
  };

  var refreshProgramHorasFromCompetencias = function () {
    var competencias = Array.prototype.slice.call(competenciasContainer.querySelectorAll("[data-competencia-item]"));
    var total = 0;
    competencias.forEach(function (competenciaNode) {
      var horasInput = competenciaNode.querySelector("[data-field='competencia-horas']");
      if (!horasInput) return;
      var raw = String(horasInput.value || "").trim();
      var n = parseInt(raw === "" ? "0" : raw, 10);
      if (!isNaN(n) && n >= 0) total += n;
    });
    var displayTotal = total === 0 ? "0" : String(total) + "h";
    if (totalHorasEl) totalHorasEl.textContent = displayTotal;
  };

  var refreshCounters = function () {
    var competencias = Array.prototype.slice.call(competenciasContainer.querySelectorAll("[data-competencia-item]"));
    var totalCompetencias = competencias.length;
    var totalRaes = 0;
    competencias.forEach(function (competenciaNode, compIndex) {
      var codigoInput = competenciaNode.querySelector("[data-field='competencia-codigo']");
      var nombreInput = competenciaNode.querySelector("[data-field='competencia-nombre']");
      var horasInput = competenciaNode.querySelector("[data-field='competencia-horas']");
      if (codigoInput) codigoInput.name = "competencias[" + compIndex + "][codigo]";
      if (nombreInput) nombreInput.name = "competencias[" + compIndex + "][nombre]";
      if (horasInput) horasInput.name = "competencias[" + compIndex + "][horas]";

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
    refreshProgramHorasFromCompetencias();
  };

  if (nivelSelect) {
    nivelSelect.addEventListener("change", syncNivelCard);
    syncNivelCard();
  }

  competenciasContainer.addEventListener("input", function (event) {
    var target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.getAttribute("data-field") === "competencia-horas") {
      refreshProgramHorasFromCompetencias();
    }
  });

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

  refreshCounters();
  competenciasContainer.querySelectorAll("textarea[data-auto-resize-textarea]").forEach(sgAutoResizeTextarea);
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
  var isDrawerEdit = !!form.querySelector("input[name='competencia_id'], input[name='jefe_id']");
  var competenciaDrawer = isDrawerEdit ? root.querySelector("[data-inline-competencia-drawer]") : null;
  var originalRaeRowsHtml = null;
  var originalCompetenciaSnapshot = "";
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

  var refreshInlineRaeNames = function () {
    var rows = Array.prototype.slice.call(form.querySelectorAll("[data-inline-raes-body] tr"));
    rows.forEach(function (row, rowIndex) {
      var codigoInput = row.querySelector("input[name*='[codigo]']");
      var descripcionInput = row.querySelector("[name*='[descripcion]']");
      if (codigoInput) codigoInput.name = "resultados[" + rowIndex + "][codigo]";
      if (descripcionInput) descripcionInput.name = "resultados[" + rowIndex + "][descripcion]";
    });
  };

  var competenciaSnapshot = function () {
    var trackedInputs = Array.prototype.slice.call(form.querySelectorAll("[data-inline-input]"));
    var parts = trackedInputs.map(function (input) {
      var name = input.name || "";
      var value = (input.value || "").trim();
      return name + "=" + value;
    });
    return parts.join("|");
  };

  var drawerCollapseTimer = null;
  var drawerCollapseOnEnd = null;

  var setEditingState = function (editing) {
    isEditing = editing;
    if (viewBlock) viewBlock.classList.toggle("hidden", editing);
    headerBlocks.forEach(function (headerBlock) {
      headerBlock.classList.toggle("hidden", editing);
    });
    if (competenciaDrawer) {
      if (drawerCollapseTimer) {
        window.clearTimeout(drawerCollapseTimer);
        drawerCollapseTimer = null;
      }
      if (drawerCollapseOnEnd) {
        competenciaDrawer.removeEventListener("transitionend", drawerCollapseOnEnd);
        drawerCollapseOnEnd = null;
      }
      if (editing) {
        form.removeAttribute("inert");
        window.requestAnimationFrame(function () {
          competenciaDrawer.setAttribute("aria-expanded", "true");
        });
      } else {
        competenciaDrawer.setAttribute("aria-expanded", "false");
        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
          form.setAttribute("inert", "");
        } else {
          drawerCollapseOnEnd = function (ev) {
            if (ev.target !== competenciaDrawer || ev.propertyName !== "grid-template-rows") return;
            competenciaDrawer.removeEventListener("transitionend", drawerCollapseOnEnd);
            drawerCollapseOnEnd = null;
            form.setAttribute("inert", "");
          };
          competenciaDrawer.addEventListener("transitionend", drawerCollapseOnEnd);
          drawerCollapseTimer = window.setTimeout(function () {
            drawerCollapseTimer = null;
            if (drawerCollapseOnEnd) {
              competenciaDrawer.removeEventListener("transitionend", drawerCollapseOnEnd);
              drawerCollapseOnEnd = null;
            }
            form.setAttribute("inert", "");
          }, 500);
        }
      }
    } else {
      form.classList.toggle("hidden", !editing);
    }
    openButton.classList.toggle("hidden", editing);
    saveButton.classList.toggle("hidden", !editing);
    cancelButton.classList.toggle("hidden", !editing);
    if (isDrawerEdit) {
      root.classList.toggle("sg-inline-editing-competencia", editing);
      if (editing) {
        root.style.borderColor = "rgb(10 139 129 / 1)";
        root.style.boxShadow = "0 0 0 2px rgb(232 253 251 / 1), 0 1px 2px rgba(16, 24, 40, 0.06)";
      } else {
        root.style.borderColor = "";
        root.style.boxShadow = "";
      }
    }
  };

  openButton.addEventListener("click", function () {
    document.querySelectorAll("[data-inline-edit-root].is-editing").forEach(function (otherRoot) {
      if (otherRoot === root) return;
      var otherCancel = otherRoot.querySelector("[data-inline-edit-cancel]");
      if (otherCancel) otherCancel.click();
    });

    root.classList.add("is-editing");
    var raesBody = form.querySelector("[data-inline-raes-body]");
    originalRaeRowsHtml = raesBody ? raesBody.innerHTML : null;
    refreshInlineRaeNames();
    inputs.forEach(function (input) {
      originalValues[input.name] = input.value;
    });
    originalCompetenciaSnapshot = competenciaSnapshot();

    setEditingState(true);
    var resizeRaesTextareas = function () {
      form.querySelectorAll("textarea[data-auto-resize-textarea]").forEach(sgAutoResizeTextarea);
    };
    if (competenciaDrawer) {
      var onDrawerOpened = function (ev) {
        if (ev.target !== competenciaDrawer || ev.propertyName !== "grid-template-rows") return;
        competenciaDrawer.removeEventListener("transitionend", onDrawerOpened);
        resizeRaesTextareas();
      };
      competenciaDrawer.addEventListener("transitionend", onDrawerOpened);
      window.setTimeout(function () {
        competenciaDrawer.removeEventListener("transitionend", onDrawerOpened);
        resizeRaesTextareas();
      }, 450);
    } else {
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(resizeRaesTextareas);
      });
    }
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
    var raesBody = form.querySelector("[data-inline-raes-body]");
    if (raesBody && originalRaeRowsHtml !== null) {
      raesBody.innerHTML = originalRaeRowsHtml;
    }
    refreshInlineRaeNames();
    setEditingState(false);
  });

  var addRaeButton = form.querySelector("[data-inline-add-rae]");
  if (addRaeButton) {
    addRaeButton.addEventListener("click", function () {
      var tbody = form.querySelector("[data-inline-raes-body]");
      if (!tbody) return;
      var nextIndex = tbody.querySelectorAll("tr").length;
      var row = document.createElement("tr");
      row.className = "border-b border-app-borderSoft";
      row.setAttribute("data-inline-new-rae", "1");
      row.innerHTML = '' +
        '<td class="px-2 py-3 align-top w-32">' +
        '<input type="text" name="resultados[' + nextIndex + '][codigo]" value="" class="w-full rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-sm text-app-muted outline-none transition-colors duration-200 hover:border-app-accent focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft" data-inline-input>' +
        '</td>' +
        '<td class="px-2 py-3 align-top">' +
        '<textarea rows="1" name="resultados[' + nextIndex + '][descripcion]" class="min-h-[2.5rem] w-full resize-none overflow-hidden rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-sm text-app-muted outline-none transition-colors duration-200 hover:border-app-accent focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft" data-inline-input data-auto-resize-textarea></textarea>' +
        '</td>' +
        '<td class="px-2 py-3 align-top">' +
        '<button type="button" class="font-app inline-flex h-10 w-10 items-center justify-center rounded-md border border-rose-300 bg-rose-50 text-rose-700 no-underline transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-inline-remove-rae aria-label="Eliminar RAE">' +
        '<span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></span>' +
        '</button>' +
        '</td>';
      tbody.appendChild(row);
      refreshInlineRaeNames();
      var newTa = row.querySelector("textarea[data-auto-resize-textarea]");
      if (newTa) sgAutoResizeTextarea(newTa);
      var firstInput = row.querySelector("input");
      if (firstInput) firstInput.focus();
    });
  }

  form.addEventListener("click", function (event) {
    var target = event.target;
    if (!(target instanceof Element)) return;
    var removeButton = target.closest("[data-inline-remove-rae]");
    if (!removeButton) return;

    var row = removeButton.closest("tr");
    var tbody = form.querySelector("[data-inline-raes-body]");
    if (!row || !tbody) return;

    var rows = tbody.querySelectorAll("tr");
    if (rows.length <= 1) {
      var codigoInput = row.querySelector("input[name*='[codigo]']");
      var descripcionInput = row.querySelector("[name*='[descripcion]']");
      if (codigoInput) codigoInput.value = "";
      if (descripcionInput) descripcionInput.value = "";
    } else {
      row.remove();
      refreshInlineRaeNames();
    }
  });

  form.addEventListener("submit", function (event) {
    var jefeIdField = form.querySelector("input[name='jefe_id']");
    if (jefeIdField) {
      var jefeSubmitter = event.submitter;
      if (jefeSubmitter && jefeSubmitter.hasAttribute("formaction") && (jefeSubmitter.getAttribute("formaction") || "").indexOf("/catalogo/empresas/eliminar-jefe") !== -1) {
        return;
      }

      var jefeNombreInput = form.querySelector("input[name='nombre']");
      var jefeNombre = jefeNombreInput ? (jefeNombreInput.value || "").trim() : "";
      if (jefeNombre === "") {
        event.preventDefault();
        showToast("Completa al menos el nombre del supervisor.");
        return;
      }

      if (competenciaSnapshot() === originalCompetenciaSnapshot) {
        event.preventDefault();
        showToast("No hay cambios para guardar en este jefe.");
      }
      return;
    }

    var competenciaIdField = form.querySelector("input[name='competencia_id']");
    if (!competenciaIdField) return;
    var submitter = event.submitter;
    if (submitter && submitter.hasAttribute("formaction") && (submitter.getAttribute("formaction") || "").indexOf("/catalogo/programas/eliminar-competencia") !== -1) {
      return;
    }

    var nombreInput = form.querySelector("input[name='nombre']");
    var codigoInput = form.querySelector("input[name='codigo']");
    var horasInput = form.querySelector("input[name='horas']");
    var nombre = nombreInput ? (nombreInput.value || "").trim() : "";
    var codigo = codigoInput ? (codigoInput.value || "").trim() : "";
    var horas = horasInput ? (horasInput.value || "").trim() : "";

    if (nombre === "" || codigo === "" || horas === "") {
      event.preventDefault();
      showToast("Completa nombre, código y horas de la competencia.");
      return;
    }

    var rows = Array.prototype.slice.call(form.querySelectorAll("[data-inline-raes-body] tr"));
    if (rows.length === 0) {
      event.preventDefault();
      showToast("Agrega al menos un RAE.");
      return;
    }

    var hasInvalidRae = rows.some(function (row) {
      var codigoRaeInput = row.querySelector("input[name*='[codigo]']");
      var descripcionRaeInput = row.querySelector("[name*='[descripcion]']");
      var codigoRae = codigoRaeInput ? (codigoRaeInput.value || "").trim() : "";
      var descripcionRae = descripcionRaeInput ? (descripcionRaeInput.value || "").trim() : "";
      return codigoRae === "" || descripcionRae === "";
    });
    if (hasInvalidRae) {
      event.preventDefault();
      showToast("Completa código y descripción en todos los RAEs.");
      return;
    }

    var seenCodes = {};
    var hasDuplicateCodes = rows.some(function (row) {
      var codigoRaeInput = row.querySelector("input[name*='[codigo]']");
      var codigoRae = codigoRaeInput ? (codigoRaeInput.value || "").trim().toLowerCase() : "";
      if (codigoRae === "") return false;
      if (seenCodes[codigoRae]) return true;
      seenCodes[codigoRae] = true;
      return false;
    });
    if (hasDuplicateCodes) {
      event.preventDefault();
      showToast("No se permiten códigos RAE duplicados.");
      return;
    }

    if (competenciaSnapshot() === originalCompetenciaSnapshot) {
      event.preventDefault();
      showToast("No hay cambios para guardar en esta competencia.");
    }
  });

  setEditingState(false);

  if (root.getAttribute("data-inline-auto-open") === "1") {
    openButton.click();
  }
});

// Toast de servidor: animación de entrada/salida + limpieza de query params.
document.querySelectorAll("[data-toast-root] [data-toast]").forEach(function (toast) {
  var message = (toast.getAttribute("data-toast-message") || "").trim();
  if (message === "") return;
  var textNode = toast.querySelector("p");
  if (textNode) textNode.textContent = message;
  var hideMs = parseInt(toast.getAttribute("data-toast-ms") || "3200", 10);
  if (isNaN(hideMs) || hideMs < 1200) hideMs = 3200;
  window.requestAnimationFrame(function () {
    toast.classList.add("sg-toast-visible");
  });
  window.setTimeout(function () {
    toast.classList.remove("sg-toast-visible");
  }, hideMs);

  if (window.history && typeof window.history.replaceState === "function") {
    try {
      var cleanUrl = new URL(window.location.href);
      cleanUrl.searchParams.delete("toast");
      cleanUrl.searchParams.delete("saved");
      cleanUrl.searchParams.delete("edit_competencia");
      cleanUrl.searchParams.delete("edit_jefe");
      cleanUrl.searchParams.delete("new_jefe");
      window.history.replaceState(null, "", cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);
    } catch (e) {
      // no-op
    }
  }
});

// Nueva competencia (borrador): RAEs dinámicos sin persistir hasta guardar.
document.querySelectorAll("[data-new-competencia-form]").forEach(function (form) {
  var tbody = form.querySelector("[data-new-competencia-raes-body]");
  var addButton = form.querySelector("[data-new-competencia-add-rae]");
  if (!tbody || !addButton) return;

  var refreshNames = function () {
    Array.prototype.slice.call(tbody.querySelectorAll("tr")).forEach(function (row, idx) {
      var codigoInput = row.querySelector("input[name*='[codigo]']");
      var descripcionInput = row.querySelector("[name*='[descripcion]']");
      if (codigoInput) codigoInput.name = "resultados[" + idx + "][codigo]";
      if (descripcionInput) descripcionInput.name = "resultados[" + idx + "][descripcion]";
    });
  };
  var existingCodes = [];
  try {
    existingCodes = JSON.parse(form.getAttribute("data-existing-competencia-codes") || "[]");
  } catch (e) {
    existingCodes = [];
  }

  addButton.addEventListener("click", function () {
    var idx = tbody.querySelectorAll("tr").length;
    var row = document.createElement("tr");
    row.className = "border-b border-app-borderSoft";
    row.innerHTML = '' +
      '<td class="px-2 py-3 align-top w-32"><input type="text" name="resultados[' + idx + '][codigo]" value="" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" class="w-full rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-sm text-app-muted outline-none transition-colors duration-200 hover:border-app-accent focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft"></td>' +
      '<td class="px-2 py-3 align-top"><textarea rows="1" name="resultados[' + idx + '][descripcion]" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false" class="min-h-[2.5rem] w-full resize-none overflow-hidden rounded-lg border border-app-borderControlStrong bg-white px-3 py-2 text-sm text-app-muted outline-none transition-colors duration-200 hover:border-app-accent focus:border-app-accent focus:ring-2 focus:ring-app-accentSoft" data-auto-resize-textarea></textarea></td>' +
      '<td class="px-2 py-3 align-top"><button type="button" class="font-app inline-flex h-10 w-10 items-center justify-center rounded-md border border-rose-300 bg-rose-50 text-rose-700 no-underline transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-700" data-new-competencia-remove-rae aria-label="Eliminar RAE"><span class="inline-flex h-4 w-4 [&_svg]:h-4 [&_svg]:w-4"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></span></button></td>';
    tbody.appendChild(row);
    refreshNames();
    var addedTa = row.querySelector("textarea[data-auto-resize-textarea]");
    if (addedTa) sgAutoResizeTextarea(addedTa);
  });

  form.addEventListener("click", function (event) {
    var target = event.target;
    if (!(target instanceof Element)) return;
    var removeButton = target.closest("[data-new-competencia-remove-rae]");
    if (!removeButton) return;
    var row = removeButton.closest("tr");
    if (!row) return;
    var rows = tbody.querySelectorAll("tr");
    if (rows.length <= 1) {
      var codigoInput = row.querySelector("input[name*='[codigo]']");
      var descripcionInput = row.querySelector("[name*='[descripcion]']");
      if (codigoInput) codigoInput.value = "";
      if (descripcionInput) descripcionInput.value = "";
    } else {
      row.remove();
      refreshNames();
    }
  });

  form.addEventListener("submit", function (event) {
    var nombreInput = form.querySelector("input[name='nombre']");
    var codigoInput = form.querySelector("input[name='codigo']");
    var horasInput = form.querySelector("input[name='horas']");
    var nombre = nombreInput ? (nombreInput.value || "").trim() : "";
    var codigo = codigoInput ? (codigoInput.value || "").trim() : "";
    var horas = horasInput ? (horasInput.value || "").trim() : "";

    if (nombre === "" || codigo === "" || horas === "") {
      event.preventDefault();
      showGlobalToast("Completa nombre, código y horas de la competencia.");
      return;
    }

    var rows = Array.prototype.slice.call(tbody.querySelectorAll("tr"));
    if (rows.length === 0) {
      event.preventDefault();
      showGlobalToast("Agrega al menos un RAE.");
      return;
    }

    var hasInvalidRae = rows.some(function (row) {
      var codigoRaeInput = row.querySelector("input[name*='[codigo]']");
      var descripcionRaeInput = row.querySelector("[name*='[descripcion]']");
      var codigoRae = codigoRaeInput ? (codigoRaeInput.value || "").trim() : "";
      var descripcionRae = descripcionRaeInput ? (descripcionRaeInput.value || "").trim() : "";
      return codigoRae === "" || descripcionRae === "";
    });
    if (hasInvalidRae) {
      event.preventDefault();
      showGlobalToast("Completa código y descripción en todos los RAEs.");
      return;
    }

    var seenCodes = {};
    var hasDuplicateCodes = rows.some(function (row) {
      var codigoRaeInput = row.querySelector("input[name*='[codigo]']");
      var codigoRae = codigoRaeInput ? (codigoRaeInput.value || "").trim().toLowerCase() : "";
      if (codigoRae === "") return false;
      if (seenCodes[codigoRae]) return true;
      seenCodes[codigoRae] = true;
      return false;
    });
    if (hasDuplicateCodes) {
      event.preventDefault();
      showGlobalToast("No se permiten códigos RAE duplicados.");
      return;
    }

    var competenciaCode = codigo.toLowerCase();
    var duplicateCompetenciaCode = existingCodes.some(function (existingCode) {
      return ((existingCode || "") + "").trim().toLowerCase() === competenciaCode;
    });
    if (duplicateCompetenciaCode) {
      event.preventDefault();
      showGlobalToast("No se permiten códigos de competencia duplicados.");
    }
  });
});

document.querySelectorAll("[data-new-jefe-form]").forEach(function (form) {
  form.addEventListener("submit", function (event) {
    var nombreInput = form.querySelector("input[name='nombre']");
    var nombre = nombreInput ? (nombreInput.value || "").trim() : "";
    if (nombre === "") {
      event.preventDefault();
      showGlobalToast("Completa al menos el nombre del supervisor.");
    }
  });
});
