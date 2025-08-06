// ✅ Show toast message from sessionStorage (used after page reload)
document.addEventListener("DOMContentLoaded", () => {
  const toast = document.getElementById("toast");
  const toastMsg = document.getElementById("toastMessage");

  const message = sessionStorage.getItem("toastMsg");
  const type = sessionStorage.getItem("toastType");

  if (message && toast && toastMsg) {
    toastMsg.textContent = message;
    toast.classList.remove("danger", "success", "error");
    toast.classList.add(type || "danger");
    toast.classList.add("show");

    setTimeout(() => {
      toast.classList.remove("show");
      sessionStorage.removeItem("toastMsg");
      sessionStorage.removeItem("toastType");
    }, 3000);
  }
});

// ✅ Reusable utility to show toast manually
function showToast(message, type = "danger") {
  const toast = document.getElementById("toast");
  const toastMsg = document.getElementById("toastMessage");

  if (toast && toastMsg) {
    toastMsg.textContent = message;
    toast.classList.remove("danger", "success", "error");
    toast.classList.add(type);
    toast.classList.add("show");

    setTimeout(() => {
      toast.classList.remove("show");
    }, 3000);
  }
}
