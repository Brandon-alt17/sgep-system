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
