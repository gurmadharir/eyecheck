document.addEventListener("DOMContentLoaded", () => {
  let imageInput  = document.getElementById("imageInput");
  const previewImage = document.getElementById("previewImage");
  const removeImageBtn = document.getElementById("removeImage");
  const loadingSpinner = document.getElementById("loadingSpinner");
  const resultBox = document.getElementById("result");
  const form = document.getElementById("uploadForm");
  const saveBtn = form.querySelector('button[type="submit"]');
  const diagnosisInput = document.getElementById("diagnosisResult");
  const toast = document.getElementById("toast");
  const roleInput = document.querySelector('input[name="role"]');
  const captureBtn  = document.getElementById("captureBtn");
  let cameraInput = document.getElementById("cameraInput");
  // === Live camera (getUserMedia) ===
  const cameraModal = document.getElementById("cameraModal");
  const liveVideo   = document.getElementById("liveVideo");
  const liveCanvas  = document.getElementById("liveCanvas");
  const snapBtn     = document.getElementById("snapBtn");
  const closeCamera = document.getElementById("closeCamera");
  let camStream = null;
  let currentToken = 0;


  // === Factor your file handling into a function so both inputs use it ===
  function processFile(file) {
    if (!file) return;

    const allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
    if (!allowedTypes.includes(file.type)) {
      showToast("Please select a valid image file (JPG, PNG, GIF, WEBP).", "error");
      hardResetSelection();
      return;
    }

    // Bump token to identify this run
    const token = ++currentToken;

    // Put file into the real input so backend submit works
    const dt = new DataTransfer();
    dt.items.add(file);
    imageInput.files = dt.files;

    saveBtn.disabled = true;
    resultBox.textContent = "";
    diagnosisInput.value = "";

    const reader = new FileReader();
    reader.onload = (e) => {
      if (token !== currentToken) return;          // user removed/replaced meanwhile
      previewImage.src = e.target.result;
      previewImage.style.display = "block";
      removeImageBtn.style.display = "inline-block";
      showSpinner();
    };
    reader.readAsDataURL(file);

    const formData = new FormData();
    formData.append("file", file);

    fetch("http://127.0.0.1:8000/predict", { method: "POST", body: formData })
      .then((res) => res.json())
      .then((data) => {
        if (token !== currentToken) return;

        const rawResult = data.result || "";
        const cleaned = cleanText(rawResult);
        diagnosisInput.value = cleaned;

        // Show loader first
        showSpinner();
        resultBox.textContent = ""; // keep empty for now

        setTimeout(() => {
          hideSpinner();
          resultBox.textContent = rawResult;

          if (!["Conjunctivitis", "NonConjunctivitis"].includes(cleaned)) {
            saveBtn.disabled = true;
            showToast(rawResult || "Invalid prediction. Please try a clear human eye image.", "error");
            return;
          }

          saveBtn.disabled = false;
          showToast("Prediction ready. You can save.", "success");
        }, 3000); // 3 second delay
      })
      .catch((err) => {
        if (token !== currentToken) return;
        hideSpinner();
        console.error("🔥 Prediction error:", err);
        showToast("Prediction failed. Check FastAPI server.", "error");
      });
  }

  removeImageBtn.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    hardResetSelection();
  });

  function hardResetSelection() {
    currentToken++;

    // Replace inputs with fresh nodes (no residual file)
    imageInput = resetFileInput(
      document.getElementById("imageInput"),
      () => processFile(imageInput.files[0])
    );
    if (cameraInput) {
      cameraInput = resetFileInput(
        document.getElementById("cameraInput"),
        () => processFile(cameraInput.files[0])
      );
    }

    // Stop camera & close modal
    stopCamera?.();

    // Clear UI
    previewImage.src = "";
    previewImage.style.display = "none";
    removeImageBtn.style.display = "none";
    resultBox.textContent = "";
    diagnosisInput.value = "";
    saveBtn.disabled = true;

    document.body.classList.remove("has-image");
    hideSpinner();
  }

  
  // Helper
  function resetFileInput(inputEl, onChangeHandler) {
    if (!inputEl) return null;                 // guard if element missing
    const newEl = inputEl.cloneNode(true);     // keeps id/name/accept/required
    inputEl.parentNode.replaceChild(newEl, inputEl);
    if (onChangeHandler) newEl.addEventListener("change", onChangeHandler);
    return newEl;
  }
    
  async function openCamera() {
    try {
      // Prefer back camera on mobile; desktop will pick any camera
      camStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: "environment" } },
        audio: false
      });
      liveVideo.srcObject = camStream;
      cameraModal.style.display = "block";
    } catch (err) {
      console.error("Camera error:", err);
      showToast("Unable to access camera. Check permissions.", "error");
    }
  }

  function stopCamera() {
    if (camStream) {
      camStream.getTracks().forEach(t => t.stop());
      camStream = null;
    }
    liveVideo.srcObject = null;
    cameraModal.style.display = "none";
  }

  // Open modal on Take Photo (live)
  captureBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    // If getUserMedia not supported, fallback to the hidden camera input trick
    if (!navigator.mediaDevices?.getUserMedia) {
      const fallback = document.getElementById("cameraInput");
      if (fallback) fallback.click();
      else showToast("Camera not supported on this browser.", "error");
      return;
    }
    openCamera();
  });

// Capture current frame → File → your processFile()
snapBtn?.addEventListener("click", async () => {
  if (!liveVideo.videoWidth || !liveVideo.videoHeight) {
    showToast("Camera not ready yet. Please wait a second.", "error");
    return;
  }

  const w = liveVideo.videoWidth;
  const h = liveVideo.videoHeight;
  liveCanvas.width = w;
  liveCanvas.height = h;
  const ctx = liveCanvas.getContext("2d");
  ctx.drawImage(liveVideo, 0, 0, w, h);

  liveCanvas.toBlob((blob) => {
    if (!blob) {
      showToast("Failed to capture image. Try again.", "error");
      return;
    }
    const file = new File([blob], `capture_${Date.now()}.jpg`, { type: "image/jpeg" });
    processFile(file);           // 🔁 re-use your existing pipeline
    stopCamera();                // close modal & stop camera
  }, "image/jpeg", 0.92);
});

  // Close modal
  closeCamera?.addEventListener("click", stopCamera);
  // Safety: stop camera if user navigates away
  window.addEventListener("beforeunload", stopCamera);

  // Use processFile for BOTH sources
  imageInput.addEventListener("change", () => processFile(imageInput.files[0]));

  // 📸 Take Photo: open the hidden camera input
  cameraInput.addEventListener("change", () => processFile(cameraInput.files[0]));


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