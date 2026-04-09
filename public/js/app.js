document.querySelectorAll("[data-max]").forEach(function (element) {
  var max = parseInt(element.getAttribute("data-max") || "0", 10);
  if (!max) {
    return;
  }
  element.addEventListener("input", function (event) {
    var value = event.target.value || "";
    if (value.length > max) {
      event.target.value = value.substring(0, max);
    }
  });
});
