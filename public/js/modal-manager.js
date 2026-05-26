function portalModalOverlaysToBody() {
  document.querySelectorAll(".modal-overlay[id^='modal-']").forEach(function (modal) {
    if (modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
  });
}

function openModal(id) {
  if (!id) return;
  portalModalOverlaysToBody();
  var modal = document.getElementById("modal-" + id);
  if (!modal) return;
  modal.classList.remove("hidden");
  document.body.style.overflow = "hidden";
}

function closeModal(id) {
  if (!id) return;
  var modal = document.getElementById("modal-" + id);
  if (!modal) return;
  modal.classList.add("hidden");
  if (!document.querySelector(".modal-overlay:not(.hidden)")) {
    document.body.style.overflow = "";
  }
}

function setConflictRowState(row, action) {
  if (!row) return;
  row.setAttribute("data-conflict-selected", action);
  row.setAttribute("data-conflict-resolution", action === "new" ? "new" : "current");
  row.querySelectorAll("[data-conflict-action-button]").forEach(function (button) {
    var isActive = button.getAttribute("data-conflict-action-button") === action;
    if (isActive) {
      button.classList.add("ring-2", "ring-offset-1", "ring-app-accent");
      button.setAttribute("aria-pressed", "true");
    } else {
      button.classList.remove("ring-2", "ring-offset-1", "ring-app-accent");
      button.setAttribute("aria-pressed", "false");
    }
  });
}

function isConflictRowResolved(row) {
  if (!row) return false;
  var status = row.querySelector("[data-conflict-status]");
  if (!status) return false;
  var text = (status.textContent || "").trim();
  return text === "Aplicado" || text === "Sin cambios";
}

function showImportConflictToast(message) {
  var text = (message || "").trim();
  if (!text) return;
  if (typeof window.sgToastShow === "function") {
    var roots = ["#import-conflictos-toast-root", "#import-preview-toast-root"];
    for (var i = 0; i < roots.length; i++) {
      if (document.querySelector(roots[i])) {
        window.sgToastShow(roots[i], text, "success", 5200);
        return;
      }
    }
  }
  if (typeof window.showGlobalToast === "function") {
    window.showGlobalToast(text);
  }
}

function applyImportConflictsRemaining(remaining) {
  var count = Math.max(0, parseInt(String(remaining), 10) || 0);
  var countEl = document.getElementById("import-conflicts-count");
  if (countEl) {
    countEl.textContent = String(count);
  }
  var alertSection = document.getElementById("import-conflicts-alert");
  if (alertSection) {
    if (count === 0) {
      alertSection.classList.add("hidden");
    } else {
      alertSection.classList.remove("hidden");
    }
  }
  var searchCard = document.querySelector("[data-import-conflicts-root] form");
  var tableSection = document.getElementById("tabla-conflictos");
  var tbody = document.getElementById("import-conflicts-tbody");
  var rowsOnPage = tbody
    ? tbody.querySelectorAll("[data-conflict-aprendiz-row]").length
    : 0;
  if (count === 0) {
    if (searchCard) searchCard.classList.add("hidden");
    if (tableSection) tableSection.classList.add("hidden");
    var doneSection = document.getElementById("import-conflicts-all-done");
    if (doneSection) doneSection.classList.remove("hidden");
    return;
  }
  var doneSection = document.getElementById("import-conflicts-all-done");
  if (doneSection) doneSection.classList.add("hidden");
  if (searchCard) searchCard.classList.remove("hidden");
  if (tableSection) tableSection.classList.remove("hidden");
  if (rowsOnPage === 0) {
    window.location.reload();
  }
}

function updateImportConflictsCount() {
  var tbody = document.getElementById("import-conflicts-tbody");
  if (!tbody) return;
  applyImportConflictsRemaining(
    tbody.querySelectorAll("[data-conflict-aprendiz-row]").length
  );
}

function collectConflictResolutions(modal) {
  var resolutions = [];
  if (!modal) return resolutions;
  modal.querySelectorAll("[data-conflict-row]").forEach(function (row) {
    var field = row.getAttribute("data-conflict-field") || "";
    if (!field) return;
    var encoded = row.getAttribute("data-conflict-value") || "";
    var value = "";
    try {
      value = encoded ? decodeURIComponent(encoded) : "";
    } catch (e) {
      value = "";
    }
    resolutions.push({
      field: field,
      action: row.getAttribute("data-conflict-resolution") || "new",
      value: value
    });
  });
  return resolutions;
}

function persistConflictAprendizResolved(aprendizId, resolutions) {
  var root = document.querySelector("[data-import-conflicts-root]");
  if (!root) return Promise.resolve(null);
  var importId = (root.getAttribute("data-import-id") || "").trim();
  var aprendizKey = parseInt(String(aprendizId), 10);
  if (!importId || !aprendizKey) return Promise.resolve(null);

  return fetch((window.APP_BASE_PATH || "") + "/importar/conflicto/completado", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest"
    },
    body: JSON.stringify({
      import_id: importId,
      aprendiz_id: aprendizKey,
      resolutions: resolutions || []
    })
  }).then(function (response) {
    if (!response || !response.ok) return null;
    return response.json();
  });
}

function onAprendizConflictsResolved(modalId, modal) {
  if (!modalId || !modal) return;
  var aprendizKey = modalId.replace(/^conflict-/, "");
  var listRow = document.querySelector(
    '[data-conflict-aprendiz-row][data-aprendiz-id="' + aprendizKey + '"]'
  );
  var resolutions = collectConflictResolutions(modal);

  persistConflictAprendizResolved(aprendizKey, resolutions)
    .then(function (data) {
      if (!data || !data.ok) {
        alert("No se pudo registrar la resolución en el historial de importación.");
        return;
      }
      closeModal(modalId);
      if (listRow) {
        listRow.remove();
      }
      if (typeof data.remaining === "number") {
        applyImportConflictsRemaining(data.remaining);
      } else {
        updateImportConflictsCount();
      }
      showImportConflictToast("Conflictos del aprendiz resueltos");
      if (modal) {
        modal.remove();
      }
    })
    .catch(function () {
      alert("No se pudo registrar la resolución en el historial de importación.");
    });
}

function checkConflictModalComplete(modalId) {
  var modal = document.getElementById("modal-" + modalId);
  if (!modal) return;
  var rows = modal.querySelectorAll(
    '[data-conflict-row][data-conflict-modal="' + modalId + '"]'
  );
  if (!rows.length) return;
  for (var i = 0; i < rows.length; i++) {
    if (!isConflictRowResolved(rows[i])) {
      return;
    }
  }
  onAprendizConflictsResolved(modalId, modal);
}

function resolveConflictRow(row, action) {
  var aprendizId = parseInt(row.getAttribute("data-aprendiz-id") || "0", 10);
  var field = row.getAttribute("data-conflict-field") || "";
  var encodedValue = row.getAttribute("data-conflict-value") || "";
  var value = "";
  try {
    value = encodedValue ? decodeURIComponent(encodedValue) : "";
  } catch (e) {
    value = "";
  }
  if (!aprendizId || !field) return Promise.resolve();

  var modalId = row.getAttribute("data-conflict-modal") || "";
  var modal = modalId ? document.getElementById("modal-" + modalId) : null;
  var incomingJefeId = modal ? parseInt(modal.getAttribute("data-incoming-jefe-id") || "0", 10) : 0;
  if (field === "jefe_id" && incomingJefeId <= 0) {
    var parsedJefe = parseInt(String(value), 10);
    if (parsedJefe > 0) {
      incomingJefeId = parsedJefe;
    }
  }

  return fetch((window.APP_BASE_PATH || "") + "/importar/conflicto/resolver", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest"
    },
    body: JSON.stringify({
      aprendiz_id: aprendizId,
      field: field,
      value: value,
      action: action,
      incoming_jefe_id: incomingJefeId > 0 ? incomingJefeId : null
    })
  })
    .then(function (response) {
      if (!response.ok) throw new Error("No se pudo resolver el conflicto.");
      return response.json();
    })
    .then(function (data) {
      if (!data || data.ok !== true) throw new Error("No se pudo resolver el conflicto.");
      var status = row.querySelector("[data-conflict-status]");
      if (status) {
        status.textContent = data.updated ? "Aplicado" : "Sin cambios";
      }
      if (action === "new" && data.updated) {
        var currentText = row.querySelector("[data-conflict-current-text]");
        var newText = row.querySelector("[data-conflict-new-text]");
        if (currentText && newText) {
          currentText.textContent = newText.textContent || "";
        }
        var newSub = row.querySelector(".conflict-value__sub");
        if (newSub) {
          newSub.remove();
        }
      }
      row.setAttribute("data-conflict-resolved", "1");
      row.setAttribute("data-conflict-resolution", action);
      return data;
    })
    .catch(function () {
      var status = row.querySelector("[data-conflict-status]");
      if (status) {
        status.textContent = "Error";
      }
      alert("No se pudo guardar la decisión del conflicto.");
      throw new Error("conflict-resolve-failed");
    });
}

function afterConflictRowsResolved(modalId) {
  checkConflictModalComplete(modalId);
}

(function () {
  portalModalOverlaysToBody();

  var pendingConfirmTrigger = null;
  var handleConfirmAction = function () {
    var trigger = pendingConfirmTrigger;
    pendingConfirmTrigger = null;
    closeModal("confirm-action");
    if (!trigger) return;

    if (trigger instanceof HTMLButtonElement && trigger.type === "submit" && trigger.form) {
      if (typeof trigger.form.requestSubmit === "function") {
        trigger.form.requestSubmit(trigger);
      } else {
        trigger.form.submit();
      }
      return;
    }

    if (trigger instanceof HTMLAnchorElement && trigger.href) {
      window.location.href = trigger.href;
      return;
    }

    trigger.click();
  };

  document.addEventListener("click", function (event) {
    var confirmTarget = event.target.closest("[data-confirm-modal]");
    if (confirmTarget) {
      event.preventDefault();
      pendingConfirmTrigger = confirmTarget;
      var modal = document.getElementById("modal-confirm-action");
      if (!modal) return;
      var titleNode = modal.querySelector("[data-confirm-title]");
      var messageNode = modal.querySelector("[data-confirm-message]");
      if (titleNode) titleNode.textContent = confirmTarget.getAttribute("data-confirm-title") || "Confirmar acción";
      if (messageNode) messageNode.textContent = confirmTarget.getAttribute("data-confirm-message") || "¿Deseas continuar?";
      openModal("confirm-action");
      return;
    }

    var confirmAccept = event.target.closest("[data-confirm-accept]");
    if (confirmAccept) {
      event.preventDefault();
      handleConfirmAction();
      return;
    }

    var confirmCancel = event.target.closest("[data-confirm-cancel]");
    if (confirmCancel) {
      event.preventDefault();
      pendingConfirmTrigger = null;
      closeModal("confirm-action");
      return;
    }

    var openTarget = event.target.closest("[data-modal-open]");
    if (openTarget) {
      event.preventDefault();
      openModal(openTarget.getAttribute("data-modal-open"));
      return;
    }

    var closeTarget = event.target.closest("[data-modal-close]");
    if (closeTarget) {
      closeModal(closeTarget.getAttribute("data-modal-close"));
      return;
    }

    var conflictBulkTarget = event.target.closest("[data-conflict-bulk]");
    if (conflictBulkTarget) {
      var mode = conflictBulkTarget.getAttribute("data-conflict-bulk");
      var modalId = conflictBulkTarget.getAttribute("data-conflict-modal");
      if (!modalId) return;
      var modal = document.getElementById("modal-" + modalId);
      if (!modal) return;
      var action = mode === "new" ? "new" : "current";
      var rows = Array.prototype.slice.call(
        modal.querySelectorAll('[data-conflict-row][data-conflict-modal="' + modalId + '"]')
      ).filter(function (row) {
        return !row.hasAttribute("data-conflict-selected");
      });
      rows.forEach(function (row) {
        setConflictRowState(row, action);
      });
      Promise.all(rows.map(function (row) {
        return resolveConflictRow(row, action);
      }))
        .then(function () {
          afterConflictRowsResolved(modalId);
        })
        .catch(function () {});
      return;
    }

    var conflictActionButton = event.target.closest("[data-conflict-action-button]");
    if (conflictActionButton) {
      var row = conflictActionButton.closest("[data-conflict-row]");
      if (!row) return;
      var rowAction = conflictActionButton.getAttribute("data-conflict-action-button") === "new" ? "new" : "current";
      var modalIdSingle = row.getAttribute("data-conflict-modal") || "";
      setConflictRowState(row, rowAction);
      resolveConflictRow(row, rowAction)
        .then(function () {
          afterConflictRowsResolved(modalIdSingle);
        })
        .catch(function () {});
      return;
    }

    var overlay = event.target.closest("[data-modal-overlay]");
    if (overlay && event.target === overlay) {
      if (overlay.getAttribute("data-modal-overlay") === "confirm-action") {
        pendingConfirmTrigger = null;
      }
      closeModal(overlay.getAttribute("data-modal-overlay"));
    }
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Enter" || event.key === " ") {
      var conflictListRow = event.target.closest("[data-conflict-aprendiz-row][data-modal-open]");
      if (conflictListRow && event.target === conflictListRow) {
        event.preventDefault();
        openModal(conflictListRow.getAttribute("data-modal-open"));
        return;
      }
    }

    if (event.key !== "Escape") return;
    var openModals = document.querySelectorAll('[id^="modal-"]:not(.hidden)');
    if (!openModals.length) return;
    var topModal = openModals[openModals.length - 1];
    var id = topModal.id.replace("modal-", "");
    if (id === "confirm-action") {
      pendingConfirmTrigger = null;
    }
    closeModal(id);
  });
})();
