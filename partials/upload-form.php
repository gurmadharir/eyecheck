<?php
// Ensure all expected variables exist
$readonly = $readonly ?? false;
$existing_name = $existing_name ?? '';
$existing_contact = $existing_contact ?? '';
$existing_home_town = $existing_home_town ?? '';
$existing_gender = $existing_gender ?? '';
$existing_dob = $existing_dob ?? '';
$existing_region = $existing_region ?? '';
$readonlyAttr = $readonly ? 'readonly' : '';
$readonlyClass = $readonly ? 'readonly-input' : '';
$disabledAttr = $readonly ? 'disabled' : '';
?>

<div class="charts-wrapper">
  <div class="chart-card">
    <h3>Patient Information</h3>

    <form id="uploadForm" action="/eyecheck/backend/patients/create.php" method="post" enctype="multipart/form-data">
      <div class="form-grid">
        <!-- Name -->
        <div class="form-group">
          <label for="name">Patient Name</label>
          <input type="text" name="name" id="name" placeholder="Enter full name"
            class="<?= $readonlyClass ?>"
            value="<?= htmlspecialchars($existing_name) ?>" <?= $readonlyAttr ?> required />
        </div>

        <!-- Contact -->
        <div class="form-group">
          <label for="contact">Contact</label>
          <input type="text" name="contact" id="contact" placeholder="Phone or email"
            class="<?= $readonlyClass ?>"
            value="<?= htmlspecialchars($existing_contact) ?>" <?= $readonlyAttr ?>
            pattern="^(\+?[0-9\s\-]{7,20}|[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})$"
            title="Enter a valid phone number or email address" required 
          />
        </div>

        <!-- Address -->
        <div class="form-group">
          <label for="home_town">Address</label>
          <input type="text" name="home_town" id="home_town" placeholder="Patient's home town"
            class="<?= $readonlyClass ?>"
            value="<?= htmlspecialchars($existing_home_town) ?>" <?= $readonlyAttr ?> required />
        </div>

        <!-- Gender --> 
        <div class="form-group">
          <label for="gender">Gender</label>
          <select name="gender" id="gender"
            class="<?= $readonlyClass ?>" <?= $disabledAttr ?> required>
            <option value="" disabled <?= ($existing_gender === '') ? 'selected' : '' ?>>Select gender</option>
            <option value="Female" <?= ($existing_gender === 'Female') ? 'selected' : '' ?>>Female</option>
            <option value="Male" <?= ($existing_gender === 'Male') ? 'selected' : '' ?>>Male</option>
          </select>
        </div>

        <!-- DOB -->
        <div class="form-group dob-group">
          <label for="dob">Date of Birth</label>
          <div class="dob-wrapper">
            <input type="date" name="dob" id="dob"
              class="<?= $readonlyClass ?>"
              min="1900-01-01"
              max="<?= htmlspecialchars(date('Y-m-d', strtotime('-1 month'))) ?>"
              value="<?= htmlspecialchars($existing_dob) ?>" <?= $readonlyAttr ?> required />
            <i class="fas fa-calendar-alt calendar-icon-right"></i>
          </div>
        </div>

        <!-- Region -->
        <div class="form-group">
          <label for="region">Region</label>
          <select name="region" id="region"
            class="<?= $readonlyClass ?>" <?= $disabledAttr ?> required>
            <option value="" disabled>Select region</option>
            <?php
            $regions = [
              "Banadir", "Bay", "Bakool", "Gedo", "Hiiraan",
              "Middle Shabelle", "Lower Shabelle", "Middle Juba", "Lower Juba",
              "Galgaduud", "Mudug", "Nugal", "Bari"
            ];
            foreach ($regions as $reg) {
              $selected = ($existing_region === $reg) ? 'selected' : '';
              echo "<option value='$reg' $selected>$reg</option>";
            }
            ?>
          </select>
        </div>
      </div>

      <!-- Eye Image Upload -->
      <div class="form-group full-width image-upload-row">
        <label>Upload Eye Image</label>
        
        <div class="upload-box" id="dropArea">
          <!-- Browse to Upload -->
          <label class="browse-label">
            <i class="fas fa-cloud-upload-alt upload-icon"></i>
            <p>Drag & Drop or <span>Browse</span> to Upload</p>
            <input type="file" name="eye_image" id="imageInput" accept="image/*" required />
          </label>

          <!-- Take Photo Button -->
          <button id="captureBtn" type="button" class="take-photo-btn">📸 Take Photo</button>
        </div>
      </div>

      <!-- Camera Modal -->
      <div id="cameraModal" class="camera-modal" style="display:none;">
        <div class="camera-modal__backdrop"></div>
        <div class="camera-modal__content">
          <h4>Take a Photo</h4>
          <video id="liveVideo" autoplay playsinline></video>
          <div class="camera-actions">
            <button type="button" id="snapBtn" class="use-photo-btn">📸 Capture</button>
            <button type="button" id="closeCamera" class="cancel-photo-btn">Cancel</button>
          </div>
          <canvas id="liveCanvas" style="display:none;"></canvas>
        </div>
      </div>


      <!-- Hidden field for prediction -->
      <input type="hidden" name="diagnosis_result" id="diagnosisResult" />

      <!-- Preview section -->
      <div id="preview" class="image-preview">
        <img id="previewImage" src="" alt="Preview" style="display: none;" />
        <button id="removeImage" type="button" style="display: none;">Remove</button>
        <i id="loadingSpinner" class="fas fa-spinner fa-spin" style="display: none; margin-top: 15px;"></i>

        <div id="result" style="margin-top: 15px; font-size: 1rem; font-weight: 500;"></div>
      </div>

      <!-- hidden role  -->
      <input type="hidden" name="role" value="<?= $role ?>" />


      <button class="upload-btn" type="submit" disabled>Save</button>
    </form>
  </div>
</div>
