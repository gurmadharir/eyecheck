// Click-to-toggle filter dropdown (mobile-safe)
document.addEventListener("DOMContentLoaded", () => {
  const filterBtn = document.querySelector(".filter-btn");
  const dropdown = document.getElementById("filterDropdown");

  filterBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    dropdown.classList.toggle("show");
    const expanded = filterBtn.getAttribute("aria-expanded") === "true";
    filterBtn.setAttribute("aria-expanded", String(!expanded));
  });

  document.addEventListener("click", (e) => {
    if (!dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
      dropdown.classList.remove("show");
      filterBtn.setAttribute("aria-expanded", "false");
    }
  });
});