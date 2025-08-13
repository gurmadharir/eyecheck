<?php
// Controller + View for last N months printable report (default 3)
require_once __DIR__ . '/../backend/helpers/auth-check.php';
requireRole('admin');
require_once __DIR__ . '/../backend/admin/report/report-data.php';

// -------- Input --------
$type   = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$months = max(1, (int)($_GET['months'] ?? 3));

// Optional: default type to 'healthcare' if omitted
if (!$type) { $type = 'healthcare'; }

if (!in_array($type, ['healthcare', 'patient'], true) || $id <= 0) {
  http_response_code(400);
  echo "Bad request. Use ?type=healthcare&id=ID or ?type=patient&id=ID";
  exit;
}

// -------- Data --------
list($context, $rows, $summary, $distinctPatients) = fetch_report_bundle($type, $id, $months);

// Column renderers (DRY)
$columns = get_report_columns($type);

// Ensure we render a thumbnail for the Image column (constrained by CSS)
if (!function_exists('image_url')) {
  function image_url($p){
    if (!$p) return '';
    if (preg_match('~^(https?:)?//~i', $p) || str_starts_with($p, '/')) return $p;
    $p = str_replace(['..','\\'], ['','/'], $p);
    return '/eyecheck/' . ltrim($p, '/');
  }
}
if (isset($columns['Image'])) {
  $columns['Image'] = function($r){
    $src = isset($r['image_path']) ? image_url($r['image_path']) : '';
    if (!$src) return '<span class="muted">-</span>';
    $esc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
    return '<img src="'.$esc.'" alt="Upload" class="thumb" loading="lazy" onerror="this.style.display=\'none\'">';
  };
}

// Title
$title = sprintf($type === 'healthcare' ? 'Healthcare – Last %d Months' : 'Patient – Last %d Months', $months);

// ---- pagination (server-side, slice the fetched array) ----
$per  = max(5, min(50, (int)($_GET['per'] ?? 10))); // 5..50 per page
$p    = max(1, (int)($_GET['p'] ?? 1));
$total = count($rows);
$totalPages = max(1, (int)ceil($total / $per));
$p = min($p, $totalPages);
$offset = ($p - 1) * $per;
$rowsPage = array_slice($rows, $offset, $per);

function pagination_links($type, $id, $months, $p, $totalPages, $per){
  if ($totalPages <= 1) return '';
  $base = "/eyecheck/admin/print-report.php?type={$type}&id={$id}&months={$months}&per={$per}";
  $html = '<nav class="uploads-pagination noprint" aria-label="Uploads pagination">';
  for ($i=1; $i <= $totalPages; $i++){
    $active = ($i === $p) ? 'active' : '';
    $html .= "<a class=\"$active\" href=\"{$base}&p={$i}\">{$i}</a>";
  }
  $html .= '</nav>';
  return $html;
}
$paginationHtml = pagination_links($type, $id, $months, $p, $totalPages, $per);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= e($title) ?></title>

<!-- Theme bootstrap CSS init -->
<script src="../js/theme-init.js"></script>

<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap FIRST, then your CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="/eyecheck/css/print-report.css">
</head>
<body>

<!-- Fixed toolbar -->
<div class="toolbar noprint">
  <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.close()">Close</button>
  <div class="d-flex align-items-center">
    <button id="themeToggle"
            type="button"
            class="btn btn-outline-secondary rounded-circle p-0 d-inline-flex align-items-center justify-content-center theme-icon-btn"
            aria-label="Toggle theme">
      <i class="bi bi-moon-stars"></i>
    </button>
    <button type="button" class="btn btn-primary btn-sm ms-2" onclick="window.print()">
      <i class="bi bi-printer-fill"></i> Print
    </button>
  </div>
</div>

<!-- Page shell: fixed top row + scrollable uploads -->
<div id="printReport" class="page-shell">

  <!-- Two-row grid: titles (row 1) + cards (row 2) -->
  <div class="top-row">
    <div class="top-left-title">
      <h1><?= e($title) ?></h1>
    </div>
    <div class="top-right-title">
      <h2>📊 Summary</h2>
    </div>

    <div class="top-left-card">
      <?php if ($type === 'healthcare'): ?>
        <?php
          renderKeyValGrid([
            '👤 Full Name'  => e($context['full_name']),
            '🔑 Username'   => e($context['username']),
            '✉️ Email'      => e($context['email']),
            '📍 Region'     => e($context['healthcare_region'] ?? '-'),
            '🗓️ Registered' => tdt($context['created_at']),
            '🆔 User ID'    => '#'.(int)$context['id'],
          ]);
        ?>
      <?php else: ?>
        <?php
          renderKeyValGrid([
            '🧍 Name'        => e($context['name']),
            '🚻 Gender'      => e($context['gender']),
            '🎂 DOB (Age)'   => tdate($context['dob']).' ('.e(age($context['dob'])).')',
            '🗺️ Region/Town' => e($context['region'].' / '.$context['town']),
            '📞 Contact'     => e($context['contact']),
            '🆔 Patient ID'  => '#'.(int)$context['id'],
          ]);
        ?>
      <?php endif; ?>
    </div>

    <div class="top-right-card">
      <div class="card grid">
        <div><strong>Total Uploads:</strong> <?= (int)$summary['total_uploads'] ?></div>
        <div><strong>Active Window:</strong> <?= tdt($summary['first_upload']) ?> → <?= tdt($summary['last_upload']) ?></div>
        <?php if ($type === 'healthcare'): ?>
          <div><strong>Distinct Patients:</strong> <?= (int)$distinctPatients ?></div>
        <?php endif; ?>
        <div>
          <strong>Diagnosis Breakdown:</strong>
          <?php foreach (['Conjunctivitis','NonConjunctivitis'] as $k): ?>
            <span class="badge"><?= e($k) ?>: <?= (int)($summary['diag'][$k] ?? 0) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Scrollable uploads pane -->
  <section class="uploads-pane">
    <div class="uploads-title">
      <h2>🗂️ Uploads (Last <?= (int)$months ?> Months)</h2>
    </div>

    <div class="uploads-scroll">
      <?php renderTable($rowsPage, $columns); ?>
      <?= $paginationHtml ?>
    </div>
  </section>
</div>

<script src="../js/theme-toggle.js" defer></script>
</body>
</html>
