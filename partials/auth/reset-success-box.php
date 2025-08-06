<?php if (isset($_SESSION['show_reset_message'])): ?>
  <div class="success-message" id="successMsg">
    <div class="emoji">📧✨</div>
    <h2>Email sent!</h2>
    <p>An email has been successfully sent to your address with reset instructions. Check your inbox! 📩</p>
  </div>
  <?php unset($_SESSION['show_reset_message']); ?>
<?php endif; ?>
