<?php
$targetUserId = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user_id'] ?? 0);
$sessionRole = $_SESSION['role'] ?? 'patient';

// ✅ Detect if admin is editing someone else
$isAdminEditing = (
  $sessionRole === 'admin' &&
  isset($_GET['id']) &&
  is_numeric($_GET['id']) &&
  (int)$_GET['id'] !== (int)$_SESSION['user_id']
);

$action = "/eyecheck/backend/auth/change-password.php";
if ($isAdminEditing) {
    $action .= '?id=' . intval($_GET['id']);
}
?>


<div class="change-password-container">
  <div class="illustration">
    <i class="fas fa-shield-alt fa-6x password-icon"></i>
  </div>

  <div class="form-area">
    <h2>Change Password</h2>

    <form class="change-password-form auth-form" method="POST" action="<?php echo htmlspecialchars($action); ?>">
      <input type="hidden" name="user_id" value="<?php echo (int) $targetUserId; ?>">

      <?php if (!$isAdminEditing): ?>
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="current_password" placeholder="Current Password" required />
        </div>
      <?php endif; ?>

      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="new_password" placeholder="New Password" required />
      </div>

      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required />
      </div>

      <div class="form-buttons">
        <button type="button" class="btn-outline" onclick="window.history.back()">Close</button>
        <button type="submit" class="btn-primary">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- Toast UI -->
<div id="toast" style="
  position: fixed;
  bottom: 20px;
  right: 20px;
  background-color: #e74c3c;
  color: #fff;
  padding: 12px 18px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.4s ease-in-out;
  z-index: 9999;
"></div>

<script>
const form = document.querySelector('.change-password-form');
const toast = document.getElementById('toast');

// Toast message function
function showToast(message, type = 'error') {
  toast.textContent = message;
  toast.style.backgroundColor = type === 'success' ? '#27ae60' : '#e74c3c';
  toast.style.opacity = '1';
  toast.style.pointerEvents = 'auto';
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.pointerEvents = 'none';
  }, 3000);
}

// Form submit via AJAX
form?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(form);

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      body: formData,
      credentials: 'include' // ✅ includes session cookies
    });

    const raw = await response.text();
    let result;

    try {
      result = JSON.parse(raw);
    } catch (e) {
      console.error('Raw response:', raw);
      showToast("Unexpected server response. Possibly a redirect.");
      return;
    }

    showToast(result.message, result.success ? 'success' : 'error');

    if (result.success && result.redirect) {
      setTimeout(() => window.location.href = result.redirect, 1200);
    }

  } catch (err) {
    console.error("Network error:", err);
    showToast("Something went wrong. Please try again.");
  }
});
</script>
