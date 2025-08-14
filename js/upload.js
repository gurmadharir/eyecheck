// === EyeCheck Upload Flow (safe + null-guarded) ===
document.addEventListener("DOMContentLoaded", () => {
  // Resolve app base ('' or '/eyecheck') from current path
  const BASE = (() => {
    const m = location.pathname.match(/^\/[^/]+/);
    return m ? m[0] : "";
  })();

  // --- DOM refs ---
  let imageInput   = document.getElementById("imageInput");
  const previewImg = document.getElementById("previewImage");
  const removeBtn  = document.getElementById("removeImage");
  const spinner    = document.getElementById("loadingSpinner");
  const resultText  = document.getElementById("result");
  const form       = document.getElementById("uploadForm");
  const diagnosisInput = document.getElementById("diagnosisResult");
  const toastEl    = document.getElementById("toast");
  const roleInput  = document.querySelector('input[name="role"]');
  const captureBtn = document.getElementById("captureBtn");
  let cameraInput  = document.getElementById("cameraInput");

  // Live camera elements
  const cameraModal = document.getElementById("cameraModal");
  const liveVideo   = document.getElementById("liveVideo");
  const liveCanvas  = document.getElementById("liveCanvas");
  const snapBtn     = document.getElementById("snapBtn");
  const closeCamera = document.getElementById("closeCamera");

  // Guard critical elements
  if (!form) { console.error("uploadForm not found"); return; }
  if (!imageInput) { console.error("imageInput not found"); return; }

  // Use server handler as action to avoid accidental create.php posts
  if (!form.getAttribute("action")) {
    form.setAttribute("action", `${BASE}/eyecheck/backend/shared/upload-handler.php`.replace(`${BASE}${BASE}`, BASE));
  }

  let camStream = null;
  let currentToken = 0;

  // --- Core: process file selected or captured ---
  function processFile(file) {
    if (!file) return;

    const allowed = ["image/jpeg", "image/png", "image/gif", "image/webp"];
    if (!allowed.includes(file.type)) {
      showToast("Please select a valid image file (JPG, PNG, GIF, WEBP).", "error");
      hardReset();
      return;
    }

    // Token guards async pipeline
    const token = ++currentToken;

    // Put file into the real input so backend submit works
    const dt = new DataTransfer();
    dt.items.add(file);
    imageInput.files = dt.files;

    // Reset UI
    disableSave(true);
    resultText && (resultText.textContent = "");
    if (diagnosisInput) diagnosisInput.value = "";

    // Preview
    const reader = new FileReader();
    reader.onload = (e) => {
      if (token !== currentToken) return;
      if (previewImg) {
        previewImg.src = e.target.result;
        previewImg.style.display = "block";
      }
      if (removeBtn) removeBtn.style.display = "inline-block";
      showSpinner();
    };
    reader.readAsDataURL(file);

    // Call FastAPI predictor
    const fd = new FormData();
    fd.append("file", file);

    fetch("http://127.0.0.1:8000/predict", { method: "POST", body: fd })
    .then((r) => r.json())
    .then((data) => {
      if (token !== currentToken) return;

      // --- prepare everything but DO NOT touch the UI yet ---
      const raw = data.result || "";
      const cleaned = cleanText(raw);

      // confidence number (no %)
      const confPct = (data.confidence || "").toString().replace("%", "").trim();
      const confVal = (["Conjunctivitis","NonConjunctivitis"].includes(cleaned) && confPct && !isNaN(confPct))
        ? Number(confPct).toFixed(2)
        : "";

      // push hidden fields for PHP
      document.getElementById("confidenceValue")?.setAttribute("value", confVal);
      document.getElementById("modelVersion")?.setAttribute("value", data.model_version || "advanced-cnn-v1");

      // build display text (two lines) + color
      const accLine = confVal ? `\nAccuracy: ${confVal}%` : "";
      let displayText = raw;
      let color = "";

      if (cleaned === "Conjunctivitis") {
        displayText = `⚠ Conjunctivitis${accLine}`;
        color = "red";
      } else if (cleaned === "NonConjunctivitis") {
        displayText = `✅ Non-Conjunctivitis${accLine}`;
        color = "green";
      } else {
        // rejection/invalid → no confidence stored
        document.getElementById("confidenceValue")?.setAttribute("value", "");
      }

      // --- SHOW after 3 seconds ---
      setTimeout(() => {
        if (token !== currentToken) return;
        hideSpinner();

        if (resultText) {
          resultText.textContent = displayText;
          resultText.style.color = color;
        }

        if (!["Conjunctivitis","NonConjunctivitis"].includes(cleaned)) {
          disableSave(true);
          showToast(raw || "❌ Rejected (not a human eye)", "error");
          return;
        }

        // valid prediction
        if (diagnosisInput) diagnosisInput.value = cleaned;
        disableSave(false);
        showToast("Prediction ready. You can save.", "success");
      }, 3000); // ⏳ delay 3s AFTER response
    })
    .catch((err) => {
      if (token !== currentToken) return;
      hideSpinner();
      console.error("Prediction error:", err);
      showToast("Prediction failed. Check FastAPI server.", "error");
    });
  }

  // --- Remove/reset selection ---
  function hardReset() {
    currentToken++;
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

    stopCamera();

    if (previewImg) {
      previewImg.src = "";
      previewImg.style.display = "none";
    }
    if (removeBtn) removeBtn.style.display = "none";
    resultText && (resultText.textContent = "");
    if (diagnosisInput) diagnosisInput.value = "";
    disableSave(true);
    hideSpinner();
  }

  // --- Helpers ---
  function resetFileInput(inputEl, onChange) {
    if (!inputEl) return null;
    const fresh = inputEl.cloneNode(true);
    inputEl.parentNode.replaceChild(fresh, inputEl);
    if (onChange) fresh.addEventListener("change", onChange);
    return fresh;
  }

  function openCamera() {
    navigator.mediaDevices?.getUserMedia({ video: { facingMode: { ideal: "environment" } }, audio: false })
      .then((stream) => {
        camStream = stream;
        if (liveVideo) liveVideo.srcObject = stream;
        if (cameraModal) cameraModal.style.display = "block";
      })
      .catch((err) => {
        console.error("Camera error:", err);
        showToast("Unable to access camera. Check permissions.", "error");
      });
  }

  function stopCamera() {
    if (camStream) {
      camStream.getTracks().forEach(t => t.stop());
      camStream = null;
    }
    if (liveVideo) liveVideo.srcObject = null;
    if (cameraModal) cameraModal.style.display = "none";
  }

  function showSpinner()  { if (spinner) spinner.style.display = "block"; }
  function hideSpinner()  { if (spinner) spinner.style.display = "none"; }
  function disableSave(v) { if (saveBtn) saveBtn.disabled = !!v; }

  function showToast(message, type = "error") {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.style.backgroundColor = type === "success" ? "#2ecc71" : "#e74c3c";
    toastEl.style.opacity = "1";
    toastEl.style.pointerEvents = "auto";
    setTimeout(() => {
      toastEl.style.opacity = "0";
      toastEl.style.pointerEvents = "none";
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

  // --- Events ---
  // Image picker
  imageInput.addEventListener("change", () => processFile(imageInput.files[0]));

  // Optional camera file input (hidden)
  cameraInput?.addEventListener("change", () => processFile(cameraInput.files[0]));

  // Live camera open
  captureBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (!navigator.mediaDevices?.getUserMedia) {
      // Fallback to hidden file input
      if (cameraInput) cameraInput.click();
      else showToast("Camera not supported on this browser.", "error");
      return;
    }
    openCamera();
  });

  // Live camera snapshot
  snapBtn?.addEventListener("click", () => {
    if (!liveVideo || !liveCanvas) return;
    if (!liveVideo.videoWidth || !liveVideo.videoHeight) {
      showToast("Camera not ready yet. Please wait a second.", "error");
      return;
    }
    const w = liveVideo.videoWidth, h = liveVideo.videoHeight;
    liveCanvas.width = w; liveCanvas.height = h;
    const ctx = liveCanvas.getContext("2d");
    ctx.drawImage(liveVideo, 0, 0, w, h);
    liveCanvas.toBlob((blob) => {
      if (!blob) { showToast("Failed to capture image. Try again.", "error"); return; }
      const file = new File([blob], `capture_${Date.now()}.jpg`, { type: "image/jpeg" });
      processFile(file);
      stopCamera();
    }, "image/jpeg", 0.92);
  });

  // Close camera
  closeCamera?.addEventListener("click", stopCamera);
  window.addEventListener("beforeunload", stopCamera);

  // Remove image
  removeBtn?.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    hardReset();
  });

  // Submit (AJAX)
  const saveBtn = form.querySelector('button[type="submit"]');
  console.log("submit listener attached");
  form.addEventListener("submit", (e) => {
    e.preventDefault();

    // Validate prediction
    const raw = (diagnosisInput?.value || "").trim();
    const diagnosis = cleanText(raw);
    if (!["Conjunctivitis", "NonConjunctivitis"].includes(diagnosis)) {
      showToast("Invalid diagnosis result. Upload rejected.", "error");
      return;
    }

    const formData = new FormData(form);
    disableSave(true);

    // Always call backend handler (avoid create.php)
    const handlerUrl = `${BASE}/eyecheck/backend/shared/upload-handler.php`.replace(`${BASE}${BASE}`, BASE);

    fetch(handlerUrl, { method: "POST", body: formData })
      .then((res) => res.json())
      .then((data) => {
        console.log("Upload Response:", data);
        const type = data.success ? "success" : "error";
        showToast(data.message || (data.success ? "Saved successfully" : "Save failed"), type);
        disableSave(false);

        if (data.success) {
          setTimeout(() => {
            // Prefer server-provided redirect; fallback to role-based within BASE
            const serverUrl = (data.redirect && data.redirect.trim()) ? data.redirect.trim() : "";
            const role = (roleInput?.value || "").trim();
            const fallback = role === "patient"
              ? `${BASE}/patient/past-uploads.php`
              : `${BASE}/healthcare/patients.php`;
            const target = serverUrl || fallback;
            console.log("➡️ Navigating to:", target);
            window.location.assign(target);
          }, 800);
        }
      })
      .catch((err) => {
        console.error("Upload Error:", err);
        showToast("Upload failed.", "error");
        disableSave(false);
      });
  });
});
