<?php
$role = $role ?? ($_SESSION['role'] ?? 'guest');
?>

<div class="stats-grid">
  <?php if ($role === 'admin'): ?>
    <div class="stats-grid admin-layout">
      <!-- First row (5 cards) -->
      <div class="stats-row top-row">
        <div class="stat-card"><p>Total Patients</p><span id="statTotalPatients">0</span></div>
        <div class="stat-card"><p>Healthcare Users</p><span id="statHealthcareUsers">0</span></div>
        <div class="stat-card"><p>Regions Covered</p><span id="statRegions">0</span></div>
        <div class="stat-card">
          <p style="margin:0; font-weight:bold; display:flex; align-items:center;">
            Positivity Rate
            <span title="Percentage of uploads classified as 'Conjunctivitis'"
                  style="margin-left:6px; cursor:help; font-size:14px; color:#bbb;">ℹ️</span>
          </p>
          <span id="statDetectRate" style="font-size:1.5rem; font-weight:bold;">0%</span>
        </div>
        <div class="stat-card"><p>Model Accuracy</p><span id="statModelAccuracy">0%</span></div>
      </div>

      <!-- Second row (3 cards) -->
      <div class="stats-row bottom-row">
        <div class="stat-card"><p>Most Active Worker</p><span id="mostActiveWorker">-</span></div>
        <div class="stat-card" id="cardRepeatedPositives"><p>Repeated Positives</p><h2 id="statRepeatedPositives">-</h2></div>
        <div class="stat-card"><p>Latest Upload</p><span id="statLatest">-</span></div>
      </div>
    </div>

  <?php elseif ($role === 'healthcare'): ?>
    <div class="stats-grid">
      <div class="stat-card">
        <p>Total Patients</p>
        <span id="statTotal">0</span>
      </div>

      <div class="stat-card">
        <p>Regions Covered</p>
        <span id="statRegions">0</span>
      </div>

      <div class="stat-card">
        <p>Average Age</p>
        <span id="statAverageAge">0</span>
      </div>

      <div class="stat-card">
        <p>Overall Confidence</p>
        <span id="statAvgConfidence">0%</span>
      </div>

      <div class="stat-card">
        <p>Latest Upload</p>
        <span id="statLatest">-</span>
      </div>
    </div>

  <?php elseif ($role === 'patient'): ?>
    <div class="stats-grid">
      <div class="stat-card">
        <p>Total Uploads</p>
        <span id="statTotal">0</span>
      </div>

      <div class="stat-card">
        <p>Upload Frequency</p>
        <span id="statLatest">-</span>
      </div>

      <div class="stat-card">
        <p>Latest Diagnosis</p>
        <span id="statRegions">-</span>
      </div>

      <div class="stat-card">
        <p>Overall Confidence</p>
        <span id="statAvgConfidence">0%</span>
      </div>
    </div>
  <?php endif; ?>
</div>
