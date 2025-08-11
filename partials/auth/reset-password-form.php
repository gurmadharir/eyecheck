<form id="reset-form" class="auth-form" style="width:350px;">
  <h2 class="animated-text animated-forgot">Reset Password</h2>

  <!-- ✅ token only (no email, no role) -->
  <input type="hidden" name="token" id="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>" />

  <input type="password" name="new_password" placeholder="New Password" required minlength="8" />
  <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8" />
  <button type="submit">Reset Password</button>
</form>

<script>
  // Optional: ensure token is populated from ?token=... even if PHP didn’t fill it
  document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(location.search);
    const t = params.get('token');
    if (t) document.getElementById('token').value = t;
  });
</script>
