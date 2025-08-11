<?php if (!isset($_SESSION['show_reset_message'])): ?>
  <form id="forgot-form" class="auth-form" style="max-width: 350px; padding: 30px;" method="POST">
    <h2 class="animated-text animated-forgot">Forgot your password?</h2>
    <input type="email" name="email" placeholder="Enter your email" required />
    <input type="hidden" name="role" value="<?php echo $role; ?>" />
    <button type="submit">Send link</button>
    <p>Back to <a href="login.php">Sign In</a></p>
  </form>
<?php else: ?>
  <?php include 'reset-success-box.php'; ?>
<?php endif; ?>
