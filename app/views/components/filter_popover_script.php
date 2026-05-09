<script>
(function () {
  if (window.__appFilterPopoverInit) return;
  window.__appFilterPopoverInit = true;

  var popovers = [];
  document.querySelectorAll("[data-filter-popover-trigger]").forEach(function (trigger) {
    var popoverId = trigger.getAttribute("aria-controls");
    if (!popoverId) return;
    var popover = document.getElementById(popoverId);
    if (!popover) return;
    popovers.push({ trigger: trigger, popover: popover });
  });

  if (!popovers.length) return;

  var closeItem = function (item) {
    item.popover.classList.add("hidden");
    item.trigger.setAttribute("aria-expanded", "false");
  };

  var openItem = function (item) {
    item.popover.classList.remove("hidden");
    item.trigger.setAttribute("aria-expanded", "true");
  };

  var closeAll = function () {
    popovers.forEach(closeItem);
  };

  popovers.forEach(function (item) {
    item.trigger.addEventListener("click", function (event) {
      event.preventDefault();
      var isOpen = item.trigger.getAttribute("aria-expanded") === "true";
      closeAll();
      if (!isOpen) openItem(item);
    });
  });

  document.addEventListener("click", function (event) {
    var shouldKeepOpen = popovers.some(function (item) {
      return item.popover.contains(event.target) || item.trigger.contains(event.target);
    });
    if (!shouldKeepOpen) closeAll();
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") closeAll();
  });
})();
</script>
