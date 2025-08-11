
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('reset-form');

  // ✅ Reusable toast function
  function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.style.display = 'block';
    toast.style.opacity = '1';

    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => {
        toast.style.display = 'none';
      }, 300); // match transition
    }, 3000);
  }

  if (!form) {
    showToast('Form not found.');
    return;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button');
    submitBtn.disabled = true;

    const formData = new FormData(form);

    try {
      const res = await fetch('/eyecheck/backend/auth/reset-password.php', {
        method: 'POST',
        body: formData
      });

      const text = await res.text(); // Catch raw response
      console.log("Raw response:", text);

      let result;
      try {
        result = JSON.parse(text);
      } catch (parseError) {
        throw new Error("Invalid JSON from server.");
      }

      submitBtn.disabled = false;

      if (result.success) {
        document.querySelector('.auth-card').innerHTML = `
          <div class="success-message" id="successMsg">
            <div class="emoji">🔒✅</div>
            <h2>Password Reset</h2>
            <p>${result.message}</p>
            <br>
            <a href="login.php" class="logout-btn">Go to Login</a>
          </div>
        `;
      } else {
        showToast(result.message || 'Something went wrong.');
      }
    } catch (err) {
      submitBtn.disabled = false;
      showToast(err.message || 'Network error. Please try again.');
    }
  });
});
