document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('register-form');
  const submitBtn = form.querySelector('button[type="submit"]');
  const overlay = document.getElementById('loadingOverlay');
  const toggleBtn = document.getElementById('toggle-password');
  const passwordInput = form.querySelector('input[name="password"]');

  // ✅ Toggle password visibility
  toggleBtn?.addEventListener('click', () => {
    const isHidden = passwordInput.type === 'password';
    passwordInput.type = isHidden ? 'text' : 'password';
    toggleBtn.textContent = isHidden ? '👁️' : '🙈';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const fullName = form.querySelector('input[name="full_name"]').value.trim();
    const username = form.querySelector('input[name="username"]').value.trim();
    const email = form.querySelector('input[name="email"]').value.trim();
    const password = passwordInput.value.trim();  // ✅ already selected above

    if (!/^[a-zA-Z\s]+$/.test(fullName)) {
      showToast("Full name must only contain letters and spaces.");
      return;
    }

    if (password.length < 6) {
      showToast("Password must be at least 6 characters long.");
      return;
    }

    const weak = ['123456', 'password', '123456789', 'qwerty', 'abc123'];
    if (weak.includes(password.toLowerCase())) {
      showToast("Weak password! Choose a stronger one.");
      return;
    }

    submitBtn.disabled = true;
    overlay.style.display = 'flex';

    const formData = new FormData(form);

    try {
      const res = await fetch('/eyecheck/backend/auth/register.php', {
        method: 'POST',
        body: formData
      });

      const text = await res.text();
      let result;
      try {
        result = JSON.parse(text);
      } catch (err) {
        showToast("Invalid response from server.");
        console.error("❌ Failed to parse JSON:", err);
        overlay.style.display = 'none';
        submitBtn.disabled = false;
        return;
      }

      if (result.success) {
        document.body.innerHTML = `
          <div class="auth-container">
            <div class="success-message" id="successMsg">
              <div class="emoji">📧✨</div>
              <h2>Email verification link sent!</h2>
              <p>An email has been successfully sent to your address with reset instructions. Check your inbox! 📩</p>
            </div>
          </div>
        `;
      } else {
        overlay.style.display = 'none';
        submitBtn.disabled = false;
        showToast(result.message || 'Registration failed.');
      }
    } catch (err) {
      console.error(err);
      overlay.style.display = 'none';
      submitBtn.disabled = false;
      showToast('Something went wrong.');
    }
  });
});

// ✅ Toast function
function showToast(message, color = '#e74c3c') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.style.backgroundColor = color;
  toast.style.opacity = 1;
  toast.style.pointerEvents = 'auto';

  setTimeout(() => {
    toast.style.opacity = 0;
    toast.style.pointerEvents = 'none';
  }, 4000);
}
