<?php
$fullName = $user['full_name'] ?? '';
$username = $user['username'] ?? '';
$createdAt = !empty($user['created_at']) ? date("d F Y", strtotime($user['created_at'])) : 'Unknown';
$role = $_SESSION['role'] ?? 'patient';
$editingUserId = $_SESSION['user_id'] ?? 0;

$role = $_SESSION['role'] ?? 'patient';
$defaultImage = '/eyecheck/assets/images/user.webp';

if (!empty($user['profile_image'])) {
    $safeImage = basename($user['profile_image']); // ✅ avoid path traversal
    $path = "/eyecheck/$role/uploads/profile/$safeImage";
    $absPath = $_SERVER['DOCUMENT_ROOT'] . $path;

    $imageSrc = (file_exists($absPath) && is_file($absPath)) ? $path : $defaultImage;
} else {
    $imageSrc = $defaultImage;
}

?>

<form class="edit-profile-card" id="editProfileForm" enctype="multipart/form-data">
  <input type="hidden" name="user_id" value="<?php echo (int) $editingUserId; ?>" />

  <input type="text" name="full_name" class="profile-input name"
         value="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
         placeholder="Full Name" style="margin-top: 12px; width: 90%;" required
         pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" />

 <input type="text" name="username" class="profile-input username"
       style="margin-top: 12px; width: 80%;"
       value="<?php echo '@' . strtolower(htmlspecialchars(ltrim($username, '@'), ENT_QUOTES, 'UTF-8')); ?>"
       placeholder="@username" required 
       pattern="^@?[a-zA-Z0-9.]{3,20}$"
       title="Username must be 3–20 characters, using only letters, numbers, or dots." />

  <div class="profile-image" style="margin-top: 16px;">
    <img id="displayImage" src="<?php echo $imageSrc; ?>" alt="User Image"
         onerror="this.src='/eyecheck/assets/images/user.webp'" />
    <label class="upload-icon" for="uploadInput"><i class="fas fa-camera"></i></label>
    <input type="file" id="uploadInput" name="profile_image" accept="image/*" hidden />
  </div>

  <button type="submit" class="upload-photo-btn" style="margin-top: 16px;">Update Profile</button>

  <div class="upload-hint">
    Upload a new avatar. Larger image will be resized automatically.<br />
    Maximum upload size is <b>1 MB</b>.
  </div>

  <p class="member-since">Member Since: <b><?php echo $createdAt; ?></b></p>
</form>

<!-- Toast Container -->
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

<script src="/eyecheck/js/edit-profile.js"></script>
