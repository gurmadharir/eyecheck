<?php
  $role = $role ?? ($_SESSION['role'] ?? 'guest');
  $isAdmin = $role === 'admin';
  $isHealthcare = $role === 'healthcare';
  $isPatient = $role === 'patient';
?>

<div class="charts-wrapper">
  <div class="charts-top">
    <!-- Detection Results Chart -->
    <div class="chart-card">
      <h3>Detection Results</h3>
      <div id="casesWrapper">
        <canvas id="casesChart" aria-label="Detection results chart" role="img"></canvas>
      </div>
    </div>

    <?php if ($isPatient): ?>
      <!-- Ratio Distribution Chart (Only for patients) -->
      <div class="chart-card">
        <h3>Ratio Distribution</h3>
        <div id="ratioWrapper">
          <canvas id="ratioChart" aria-label="Ratio distribution chart" role="img"></canvas>
        </div>
      </div>
    <?php elseif ($isAdmin || $isHealthcare): ?>
      <!-- Gender Distribution Chart -->
      <div class="chart-card">
        <h3>Gender Distribution</h3>
        <div id="genderWrapper">
          <canvas id="genderChart" aria-label="Gender distribution chart" role="img"></canvas>
        </div>
      </div>
    <?php endif; ?>
  </div>



  <!-- Trend/Activity Chart -->
  <div class="chart-card full-width">
    <h3><?= htmlspecialchars($isPatient ? 'Upload Activity' : 'Detection Trend') ?></h3>
    <div id="trendWrapper">
      <canvas id="trendChart" aria-label="Trend chart" role="img"></canvas>
    </div>
  </div>

  <!-- Age Groups Chart (For admin and healthcare) -->
  <?php if (!$isPatient): ?>
    <div class="chart-card full-width">
      <h3>Age Group Distribution</h3>
      <div id="ageWrapper">
        <canvas id="ageChart" aria-label="Age groups chart" role="img"></canvas>
      </div>
    </div>
  <?php endif; ?>
  
  <!-- Regional Trend and Weekly Uploads (Admin or Healthcare) -->
  <?php if ($isAdmin): ?>
    <!-- Admin: Only Regional Upload Trend -->
    <div class="chart-card full-width">
      <h3>Regional Upload Trend</h3>
      <div id="regionSharedWrapper">
        <canvas id="regionSharedTrendChart" aria-label="Combined Region Trend" role="img"></canvas>
      </div>
    </div>

  <?php elseif ($isHealthcare): ?>
    <!-- Healthcare: Show Weekly Uploads first -->
    <div class="chart-card full-width">
      <h3>Weekly Uploads</h3>
      <div id="weeklyUploadWrapper">
        <canvas id="weeklyUploadChart" aria-label="Weekly Upload Chart" role="img"></canvas>
      </div>
    </div>

    <div class="chart-card full-width">
      <h3>Regional Upload Trend</h3>
      <div id="regionSharedWrapper">
        <canvas id="regionSharedTrendChart" aria-label="Combined Region Trend" role="img"></canvas>
      </div>
    </div>
  <?php endif; ?>


  <!-- Admin-Only Charts -->
  <?php if ($isAdmin): ?>
    <div class="chart-card full-width">
      <h3>User Distribution By Region</h3>
      <div id="regionUsersWrapper">
        <canvas id="regionUsersChart" aria-label="Region vs Users" role="img"></canvas>
      </div>
    </div>
    
    <div class="chart-card full-width">
      <h3>Monthly Upload Trend</h3>
      <div id="monthlyUploadWrapper">
        <canvas id="monthlyUploadChart" aria-label="Monthly Upload Trend" role="img"></canvas>
      </div>
    </div>

    <div class="chart-card full-width">
      <h3>Healthcare Worker Contributions</h3>
      <div id="workerSummaryWrapper">
        <canvas id="workerSummaryChart" aria-label="Healthcare Worker Summary" role="img"></canvas>
      </div>
    </div>
  <?php endif; ?>
</div>
