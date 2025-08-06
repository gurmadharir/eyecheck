<form id="reset-form" class="auth-form" style="width: 350px;">
  <h2 class="animated-text animated-forgot">Reset Password</h2>
  <input type="hidden" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>" />
  <input type="hidden" name="role" value="patient" />
  <input type="password" name="new_password" placeholder="New Password" required />
  <input type="password" name="confirm_password" placeholder="Confirm New Password" required />
  <button type="submit">Reset Password</button>
</form>

