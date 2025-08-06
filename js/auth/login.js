document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('login-form');
  const passwordInput = document.getElementById('login-password');
  const toggleBtn = document.getElementById('toggle-password');
  const loadingOverlay = document.getElementById('loadingOverlay');
  const toast = document.getElementById('toast');

  if (!form) {
    console.error("❌ Login form not found.");
    return;
  }

  // 👁️ Toggle password visibility
  if (toggleBtn && passwordInput) {
    toggleBtn.addEventListener('click', () => {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      toggleBtn.textContent = isHidden ? '👁️' : '🙈';
    });
  }

  // 🔐 Login form submission
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const username = form.username.value.trim();
    const password = form.password.value.trim();

    if (!username || !password) {
      showToast('All fields are required.');
      return;
    }

    const formData = new FormData(form);
    formData.set('remember', form.remember?.checked ? '1' : '0');

    const start = Date.now();
    loadingOverlay.style.display = 'flex';

    try {
      const res = await fetch('/eyecheck/backend/auth/login.php', {
        method: 'POST',
        body: formData,
      });

      const result = await res.json();
      const delay = Math.max(0, 3000 - (Date.now() - start));

      if (!result.success) {
        setTimeout(() => {
          loadingOverlay.style.display = 'none';
          showToast(result.message || 'Login failed.');
        }, delay);
        return;
      }

      // ✅ Login success
      setTimeout(() => {
        loadingOverlay.style.display = 'none';
        showToast('Login successful! Redirecting...🚀', true);
        setTimeout(() => {
          window.location.href = result.redirect;
        }, 1500);
      }, delay);

    } catch (err) {
      const delay = Math.max(0, 3000 - (Date.now() - start));
      setTimeout(() => {
        loadingOverlay.style.display = 'none';
        console.error('❌ Login error:', err);
        showToast('Something went wrong. Try again.');
      }, delay);
    }
  });

  function showToast(message, isSuccess = false) {
    if (!toast) return;

    toast.textContent = message;
    toast.style.backgroundColor = isSuccess ? '#2ecc71' : '#e74c3c'; // green or red
    toast.style.opacity = 1;
    toast.style.pointerEvents = 'auto';
    toast.style.transform = 'translateY(0)';

    setTimeout(() => {
      toast.style.opacity = 0;
      toast.style.pointerEvents = 'none';
      toast.style.transform = 'translateY(20px)';
    }, 3500);
  }


});
