<form id="register-form" class="auth-form" style="width: 350px;">
  <h2 class="animated-text animated-signup">Create Account</h2>
  <input type="text" name="full_name" placeholder="Full Name" required />
  <input type="text" name="username" placeholder="Username" required />
  <input type="email" name="email" placeholder="Email" required />

  <div class="password-group">
    <input type="password" name="password" placeholder="Password" required />
    <span class="toggle-password" id="toggle-password">🙈</span>
  </div>
  
  <button type="submit">Sign Up</button>
  <p>Already have an account? <a href="login.php">Sign in</a></p>
</form>

<!-- Toast -->
<?php include '../partials/custom-toaster.php'; ?>
