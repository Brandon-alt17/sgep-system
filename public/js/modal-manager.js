function openModal(id) {
  var modal = document.getElementById("modal-" + id);
  if (!modal) return;
  modal.classList.remove("hidden");
  modal.classList.add("flex");
  document.body.style.overflow = "hidden";
}

function closeModal(id) {
  var modal = document.getElementById("modal-" + id);
  if (!modal) return;
  modal.classList.add("hidden");
  modal.classList.remove("flex");
  if (!document.querySelector('[id^="modal-"]:not(.hidden)')) {
    document.body.style.overflow = "";
  }
}

function setConflictRowState(row, action) {
  if (!row) return;
  row.setAttribute("data-conflict-selected", action);
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
      action: action
    })
  }).then(function (response) {
    if (!response.ok) throw new Error("No se pudo resolver el conflicto.");
    return response.json();
  }).then(function (data) {
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
    }
    return data;
  }).catch(function () {
    var status = row.querySelector("[data-conflict-status]");
    if (status) {
      status.textContent = "Error";
    }
    alert("No se pudo guardar la decisión del conflicto.");
  });
}

(function () {
  document.addEventListener("click", function (event) {
    var openTarget = event.target.closest("[data-modal-open]");
    if (openTarget) {
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
      modal.querySelectorAll('[data-conflict-row][data-conflict-modal="' + modalId + '"]').forEach(function (row) {
        setConflictRowState(row, action);
        resolveConflictRow(row, action);
      });
      return;
    }

    var conflictActionButton = event.target.closest("[data-conflict-action-button]");
    if (conflictActionButton) {
      var row = conflictActionButton.closest("[data-conflict-row]");
      if (!row) return;
      var rowAction = conflictActionButton.getAttribute("data-conflict-action-button") === "new" ? "new" : "current";
      setConflictRowState(row, rowAction);
      resolveConflictRow(row, rowAction);
      return;
    }

    var overlay = event.target.closest("[data-modal-overlay]");
    if (overlay && event.target === overlay) {
      closeModal(overlay.getAttribute("data-modal-overlay"));
    }
  });

  document.addEventListener("keydown", function (event) {
    if (event.key !== "Escape") return;
    var openModals = document.querySelectorAll('[id^="modal-"]:not(.hidden)');
    if (!openModals.length) return;
    var topModal = openModals[openModals.length - 1];
    var id = topModal.id.replace("modal-", "");
    closeModal(id);
  });
})();
