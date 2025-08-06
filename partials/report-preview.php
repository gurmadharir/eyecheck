<?php
if (!isset($patient)) return;

require_once __DIR__ . '/../backend/shared/diagnosis-utils.php';
$diagData = formatDiagnosis($patient['diagnosis_result']);
$cleanDiag = strtolower(str_replace([' ', '_', '-'], '', $patient['diagnosis_result'] ?? ''));
?>
<div class="report-modal" id="reportModal">
  <div class="report-wrapper">
    <button class="close-report-btn" onclick="closeReportModal()">×</button>
    <div class="report-preview">
      <div class="report-brand">
        <div class="report-logo">
          <img src="/eyecheck/assets/images/logo.png" alt="EyeCheck Logo" />
          <h1>EYECHECK</h1>
        </div>
        <div class="report-contact">
          <p>
            456 Visionary St., Suite 200<br />
            Sightville, ST 12845<br />
            📞 (128) 456-7880<br />
            ✉️ info@eyecheck.com
          </p>
        </div>
      </div>

      <hr />
      <h2 class="report-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <span>📋 DIAGNOSTIC REPORT</span>
        <span style="font-size: 14px; color: #666; font-weight: normal;">
          <?= date('F j, Y – g:i A') ?>
        </span>
      </h2>

      <div style="text-align:center; margin: 20px 0;">
        <img src="/eyecheck/<?= htmlspecialchars($data['image_path']) ?>" alt="Uploaded Eye Image"
             style="width: 100%; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.2);" />
      </div>

      <table class="report-table">
        <tr><th>👤 Name</th><td><?= htmlspecialchars($patient['name']) ?></td></tr>
        <tr><th>📍 Address</th><td><?= htmlspecialchars($patient['town']) ?></td></tr>
        <tr><th>🌍 Region</th><td><?= htmlspecialchars($patient['region']) ?></td></tr>
        <tr><th>🙻 Gender</th><td><?= htmlspecialchars($patient['gender']) ?></td></tr>
        <tr>
          <th>🎂 Date of Birth</th>
          <td><?= !empty($patient['dob']) ? htmlspecialchars(date('F j, Y', strtotime($patient['dob']))) : 'Not Provided' ?></td>
        </tr>
        <tr>
          <th>🔬 Diagnosis</th>
          <td><span style="<?= $diagData['style'] ?>"><?= htmlspecialchars($diagData['label']) ?></span></td>
        </tr>
      </table>

      <div class="consultation">
        <strong>💡 CONSULTATION:</strong>
        <p>
          <?php if ($cleanDiag === 'conjunctivitis'): ?>
            🧪 Diagnosis: Conjunctivitis.<br>
            💊 Treatment: Recommend antibiotic eye drops and hygiene.<br>
            🗓️ Follow-up: Required if no improvement.
          <?php elseif ($cleanDiag === 'nonconjunctivitis'): ?>
            ✅ Result: No conjunctivitis detected.<br>
            😊 No treatment needed. Monitor for symptoms.
          <?php else: ?>
            ⏳ Diagnosis is pending. Await model or physician feedback.
          <?php endif; ?>
        </p>
      </div>

      <div class="signature-block">
        <p class="signed">🖊️ Dr. Jonathan Smith</p>
        <p><strong>Dr. Jonathan Smith</strong><br />Ophthalmologist</p>
      </div>

      <hr />
      <p class="footer-note">
        ⚠️ This report is for informational purposes only and does not replace professional medical advice.
      </p>

      <div style="text-align: right; margin-top: 20px;">
        <button class="print-btn" onclick="window.print()">Print</button>
      </div>
    </div>
  </div>
</div>
