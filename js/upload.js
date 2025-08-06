document.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imageInput");
  const previewImage = document.getElementById("previewImage");
  const removeImageBtn = document.getElementById("removeImage");
  const loadingSpinner = document.getElementById("loadingSpinner");
  const resultBox = document.getElementById("result");
  const form = document.getElementById("uploadForm");
  const saveBtn = form.querySelector('button[type="submit"]');
  const diagnosisInput = document.getElementById("diagnosisResult");
  const toast = document.getElementById("toast");
  const roleInput = document.querySelector('input[name="role"]');

  imageInput.addEventListener("change", () => {
    const file = imageInput.files[0];
    if (!file) return;

    saveBtn.disabled = true;
    resultBox.textContent = "";
    diagnosisInput.value = "";

    const reader = new FileReader();
    reader.onload = (e) => {
      previewImage.src = e.target.result;
      previewImage.style.display = "block";
      removeImageBtn.style.display = "inline-block";
      showSpinner();
    };
    reader.readAsDataURL(file);

    const formData = new FormData();
    formData.append("file", file);

    fetch("http://127.0.0.1:8000/predict", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        hideSpinner();
        const rawResult = data.result || "";
        const cleaned = cleanText(rawResult);
        resultBox.textContent = rawResult;
        diagnosisInput.value = cleaned;

        if (!["Conjunctivitis", "NonConjunctivitis"].includes(cleaned)) {
          saveBtn.disabled = true;
          showToast("Invalid prediction. Please try a clear eye image.", "error");
        } else {
          saveBtn.disabled = false;
          showToast("Prediction ready. You can save.", "success");
        }
      })
      .catch((err) => {
        hideSpinner();
        console.error("🔥 Prediction error:", err);
        showToast("Prediction failed. Check FastAPI server.", "error");
      });
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const raw = diagnosisInput.value.trim();
    const diagnosis = cleanText(raw);
    if (!["Conjunctivitis", "NonConjunctivitis"].includes(diagnosis)) {
      showToast("Invalid diagnosis result. Upload rejected.", "error");
      return;
    }


    const formData = new FormData(form);
    saveBtn.disabled = true;

    fetch("/eyecheck/backend/shared/upload-handler.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        console.log("💾 Upload Response:", data);
        const type = data.success ? "success" : "error";
        showToast(data.message || "Saved successfully", type);
        saveBtn.disabled = false;

        if (data.success) {
          setTimeout(() => {
            const role = roleInput?.value?.trim() || "";
            if (role === "patient") {
              window.location.href = "/eyecheck/patient/past-uploads.php";
            } else if (role === "healthcare") {
              window.location.href = "/eyecheck/healthcare/patients.php";
            }
          }, 1000); // wait 1s to show toast
        }
      })
      .catch((err) => {
        console.error("💥 Upload Error:", err);
        showToast("Upload failed.", "error");
        saveBtn.disabled = false;
      });
  });

  removeImageBtn.addEventListener("click", () => {
    imageInput.value = "";
    previewImage.src = "";
    previewImage.style.display = "none";
    removeImageBtn.style.display = "none";
    resultBox.textContent = "";
    diagnosisInput.value = "";
    saveBtn.disabled = true;
  });

  function showSpinner() {
    loadingSpinner.style.display = "block";
  }

  function hideSpinner() {
    loadingSpinner.style.display = "none";
  }

  function showToast(message, type = "error") {
    if (!toast) return;
    toast.textContent = message;
    toast.style.backgroundColor = type === "success" ? "#2ecc71" : "#e74c3c";
    toast.style.opacity = "1";
    toast.style.pointerEvents = "auto";
    setTimeout(() => {
      toast.style.opacity = "0";
      toast.style.pointerEvents = "none";
    }, 4000);
  }

  function cleanText(str) {
    if (!str || typeof str !== "string") return "";
    return str
      .normalize("NFKD")
      .replace(/[\p{Emoji_Presentation}\p{Emoji}\uFE0F\u200D\u2060]/gu, "")
      .replace(/[^a-zA-Z]/g, "")
      .trim();
  }
});
