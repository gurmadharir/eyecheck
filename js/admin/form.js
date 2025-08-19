document.addEventListener('DOMContentLoaded', function () {
  const toast = document.getElementById('toast');
  const toastData = document.getElementById('toast-data');

  if (toast && toastData) {
    const message = toastData.dataset.message;
    const type = toastData.dataset.type;
    const redirect = toastData.dataset.redirect;

    toast.textContent = message;
    toast.style.backgroundColor = type === 'success' ? '#27ae60' : '#e74c3c';
    toast.style.opacity = '1';
    toast.style.pointerEvents = 'auto';

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.pointerEvents = 'none';
      if (type === 'success' && redirect) {
        window.location.href = redirect;
      }
    }, 3000);
  }

  toggleRegion();
  wireAutoUsername();
});

const roleSelect = document.getElementById('role');
const regionGroup = document.getElementById('region-group');
const regionSelect = document.getElementById('region-select');

function toggleRegion() {
  if (roleSelect?.value === 'healthcare') {
    regionGroup?.classList.remove('hidden');
    regionSelect?.setAttribute('required', 'required');
  } else {
    regionGroup?.classList.add('hidden');
    regionSelect?.removeAttribute('required');
  }
}

roleSelect?.addEventListener('change', toggleRegion);

const form = document.querySelector('.staff-form');
const toast = document.getElementById('toast');
const submitBtn = document.getElementById('submitBtn');

function showToast(message, type = 'error') {
  toast.textContent = message;
  toast.style.backgroundColor = type === 'success' ? '#27ae60' : '#e74c3c';
  toast.style.opacity = '1';
  toast.style.pointerEvents = 'auto';

  return new Promise(resolve => {
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.pointerEvents = 'none';
      resolve();
    }, 3000);
  });
}

form?.addEventListener('submit', async (e) => {
  e.preventDefault();
  submitBtn.disabled = true;
  const formData = new FormData(form);

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });

    const raw = await response.text();
    let result;

    try {
      result = JSON.parse(raw);
    } catch (e) {
      console.error("Unexpected response:", raw);
      await showToast("Unexpected server response.");
      submitBtn.disabled = false;
      return;
    }

    await showToast(result.message, result.success ? 'success' : 'error');

    if (result.success && result.redirect) {
      window.location.href = result.redirect;
    } else {
      submitBtn.disabled = false;
    }

  } catch (err) {
    console.error("Network error:", err);
    await showToast("Something went wrong. Please try again.");
    submitBtn.disabled = false;
  }
});

// ---------- Auto-username UX ----------
function wireAutoUsername() {
  const fullNameEl = document.getElementById('full_name');
  const usernameEl = document.getElementById('username');
  const autoChk = document.getElementById('auto-username');
  const modeEl = document.getElementById('username_mode');

  if (!fullNameEl || !usernameEl || !modeEl) return;

  // Detect edit screen by presence of hidden id input
  const isEdit = !!document.querySelector('input[name="id"]');

  function firstNameSlug(name) {
    if (!name) return '';
    const first = name.trim().split(/\s+/)[0] || '';
    const ascii = first.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    return ascii.toLowerCase().replace(/[^a-z0-9]/g, '');
  }

  function tryAutofill() {
    if (isEdit) return; // don't auto-change on edit
    if (autoChk && !autoChk.checked) return;
    const slug = firstNameSlug(fullNameEl.value);
    usernameEl.value = slug;
  }

  // initialize
  if (!isEdit && autoChk && autoChk.checked && !usernameEl.value) {
    tryAutofill();
  }

  fullNameEl.addEventListener('input', tryAutofill);

  // manual override when typing username
  usernameEl.addEventListener('input', () => {
    if (autoChk && autoChk.checked) {
      autoChk.checked = false;
      modeEl.value = 'manual';
    }
  });

  autoChk?.addEventListener('change', () => {
    if (autoChk.checked) {
      modeEl.value = 'auto';
      tryAutofill();
    } else {
      modeEl.value = 'manual';
    }
  });
}
