document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('forgot-form');
  const overlay = document.getElementById('loadingOverlay');
  const submitBtn = form.querySelector('button');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    submitBtn.disabled = true;
    overlay.style.display = 'flex';

    const formData = new FormData(form);

    try {
        const res = await fetch('/eyecheck/backend/auth/forgot-password.php', {
        method: 'POST',
        body: formData
      });

      const result = await res.json();
      overlay.style.display = 'none';
      submitBtn.disabled = false;

     if (result.success) {
      document.querySelector('.auth-card').innerHTML = `
        <div class="success-message" id="successMsg">
          <div class="emoji">📧✨</div>
          <h2>Reset link sent!</h2>
          <p>${result.message}</p>
        </div>
      `;
    }
    else {
        // Inline fallback message
        form.insertAdjacentHTML('beforeend', `
          <p style="color:#e74c3c; margin-top:12px;">${result.message || 'Something went wrong.'}</p>
        `);
      }
    } catch (err) {
      overlay.style.display = 'none';
      submitBtn.disabled = false;
      form.insertAdjacentHTML('beforeend', `
        <p style="color:#e74c3c; margin-top:12px;">Something went wrong.</p>
      `);
    }
  });
});
