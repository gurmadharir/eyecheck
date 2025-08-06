// theme.js
document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("themeToggle");
    const isDark = localStorage.getItem("theme") === "dark";
  
    if (isDark) document.body.classList.add("dark");
  
    if (themeToggle) {
      themeToggle.checked = isDark;
  
      themeToggle.addEventListener("change", () => {
        if (themeToggle.checked) {
          document.body.classList.add("dark");
          localStorage.setItem("theme", "dark");
        } else {
          document.body.classList.remove("dark");
          localStorage.setItem("theme", "light");
        }
      });
    }
  });
  