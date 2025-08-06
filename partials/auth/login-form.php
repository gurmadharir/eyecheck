<form id="login-form" class="auth-form" style="width: 350px;">
  <h2 class="animated-text animated-login">Login to continue</h2>

  <input type="text" name="username" placeholder="Username" required />

  <div class="password-group">
    <input type="password" name="password" id="login-password" placeholder="Password" required />
    <span class="toggle-password" id="toggle-password">🙈</span>
  </div>

  <div class="remember-forgot">
    <label><input type="checkbox" name="remember" /> Remember</label>
    <a href="forgot-password.php">Forgot Password?</a>
  </div>

  <button type="submit">Login</button>
</form>

  <div id="loadingOverlay">
    <div class="spinner"></div>
    <p>🔄 Processing your request... Please hold on ⏳</p>
  </div>

<!-- Toast Container -->
<?php include __DIR__ . '/../custom-toaster.php'; ?>
