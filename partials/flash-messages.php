<?php if (isset($_SESSION['success'])): ?>
  <div class="flash-message success">
    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
  </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="flash-message error">
    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
  </div>
<?php endif; ?>
