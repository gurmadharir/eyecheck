const form = document.querySelector('.edit-profile-card');
const input = document.getElementById('uploadInput');
const img = document.getElementById('displayImage');
const toast = document.getElementById('toast');

// ✅ Initial values for change detection
let originalName = form.full_name.value.trim();
let originalUsername = form.username.value.toLowerCase().replace(/^@/, '').trim();
let originalImageSrc = img.src;

// ✅ Image preview handler
input?.addEventListener('change', function () {
  const file = this.files?.[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
});

// ✅ Toast message display
function showToast(message, type = 'error') {
  toast.textContent = message;
  toast.style.backgroundColor = type === 'success' ? '#27ae60' : '#e74c3c';
  toast.style.opacity = '1';
  toast.style.pointerEvents = 'auto';

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.pointerEvents = 'none';
  }, 3000);
}

// ✅ Form submission handler
form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const newName = form.full_name.value.trim();
  const newUsername = form.username.value.toLowerCase().replace(/^@/, '').trim();
  const imageChanged = form.profile_image.files.length > 0;

  // ✅ Don't compare base64 with URL
  const hasChanges =
    newName !== originalName ||
    newUsername !== originalUsername ||
    imageChanged;

  if (!hasChanges) {
    showToast("No changes detected.");
    return;
  }

  const formData = new FormData(form);

  try {
    const response = await fetch('/eyecheck/backend/profile/update-profile.php', {
      method: 'POST',
      body: formData,
    });

    const contentType = response.headers.get('content-type');
    const isJson = contentType && contentType.includes('application/json');

    let result = null;

    try {
      result = isJson ? await response.json() : null;
    } catch (parseError) {
      console.error("JSON parse error:", parseError);
      showToast("Invalid server response. Check console.", 'error');
      return;
    }

    if (!response.ok) {
      showToast(result?.message || 'Update failed.');
      return;
    }

    if (result?.success) {
      showToast(result.message, 'success');

      // ✅ Update reference state
      originalName = newName;
      originalUsername = newUsername;
      if (imageChanged) originalImageSrc = img.src;

      // ✅ Optional redirect
      if (result.redirect) {
        setTimeout(() => {
          window.location.href = result.redirect;
        }, 1200);
      }
    } else {
      showToast(result?.message || "Update failed.");
    }

  } catch (error) {
    console.error("Update error:", error);
    showToast("Network or script error. See console.");
  }
});
