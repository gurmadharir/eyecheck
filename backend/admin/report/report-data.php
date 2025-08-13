<?php
require_once __DIR__ . '/../../../config/db.php';

// ---------- Utilities ----------
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tdt($s){ if(!$s) return '-'; $d=new DateTime($s); return $d->format('Y-m-d H:i'); }
function tdate($s){ if(!$s) return '-'; $d=new DateTime($s); return $d->format('Y-m-d'); }
function age($dob){
  if(!$dob) return '-';
  try{ $b=new DateTime($dob); return $b->diff(new DateTime())->y . ' yrs'; }catch(Exception){ return '-'; }
}
function runQuery(PDO $pdo, string $sql, array $params=[]){
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st;
}

// Publicly served images (adjust if you gate images)
function image_url($p){
  if (!$p) return '';
  if (preg_match('~^(https?:)?//~i', $p) || str_starts_with($p, '/')) return $p;
  $p = str_replace(['..','\\'], ['','/'], $p);
  return '/eyecheck/' . ltrim($p, '/');
}

// Normalize diagnosis labels into the two buckets we care about
function norm_diag(?string $k): ?string {
  if ($k === null) return null;
  $k = trim($k);
  if ($k === '') return null;

  // Common aliases → NonConjunctivitis
  if (strcasecmp($k, 'Negative') === 0) return 'NonConjunctivitis';
  if (strcasecmp($k, 'Non Conjunctivitis') === 0) return 'NonConjunctivitis';

  // Canonical
  if (strcasecmp($k, 'Conjunctivitis') === 0) return 'Conjunctivitis';
  if (strcasecmp($k, 'NonConjunctivitis') === 0) return 'NonConjunctivitis';

  // Anything else: ignore in the 2-bucket summary
  return null;
}

// Resolve a patient by either patients.id OR patients.user_id
function resolve_patient_row(PDO $pdo, int $id): ?array {
  // 1) Try exact patient id match
  $row = runQuery($pdo, "
      SELECT id, user_id, name, gender, dob, region, contact, town, created_at
      FROM patients
      WHERE id = :id
      LIMIT 1
  ", [':id'=>$id])->fetch(PDO::FETCH_ASSOC);
  if ($row) return $row;

  // 2) Fall back to user_id match (patients.user_id = :id)
  $row = runQuery($pdo, "
      SELECT id, user_id, name, gender, dob, region, contact, town, created_at
      FROM patients
      WHERE user_id = :id
      LIMIT 1
  ", [':id'=>$id])->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

// ---------- Core fetch (DRY) ----------
function fetch_report_bundle(string $type, int $id, int $months){
  global $pdo;

  // NOTE: MySQL allows binding a numeric placeholder inside INTERVAL in most setups.
  // If your driver/version complains, compute DATE_SUB server-side via NOW() - INTERVAL ... MONTH inline.
  $windowSql = "pu.created_at >= DATE_SUB(NOW(), INTERVAL :months MONTH)";

  if ($type === 'healthcare') {
    // context (healthcare user)
    $ctx = runQuery($pdo, "
      SELECT id, full_name, username, email, healthcare_region, created_at
      FROM users
      WHERE id = :id AND role='healthcare'
      LIMIT 1
    ", [':id'=>$id])->fetch(PDO::FETCH_ASSOC);

    if (!$ctx) { http_response_code(404); exit('Healthcare user not found.'); }

    // rows
    $rows = runQuery($pdo, "
      SELECT 
        pu.id, pu.created_at, pu.diagnosis_result, pu.model_version, pu.confidence, pu.image_path,
        p.id AS patient_id, p.name AS patient_name, p.gender AS patient_gender, p.dob AS patient_dob,
        p.region AS patient_region, p.contact AS patient_contact, p.town AS patient_town
      FROM patient_uploads pu
      LEFT JOIN patients p ON p.id = pu.patient_id
      WHERE pu.uploaded_by = :id AND $windowSql
      ORDER BY pu.created_at DESC
    ", [':id'=>$id, ':months'=>$months])->fetchAll(PDO::FETCH_ASSOC);

    // summary (2 buckets)
    $summaryRow = runQuery($pdo, "
      SELECT COUNT(*) AS total, MIN(pu.created_at) AS first_upload, MAX(pu.created_at) AS last_upload
      FROM patient_uploads pu
      WHERE pu.uploaded_by = :id AND $windowSql
    ", [':id'=>$id, ':months'=>$months])->fetch(PDO::FETCH_ASSOC) ?: [];

    $summary = [
      'total_uploads' => (int)($summaryRow['total'] ?? 0),
      'first_upload'  => $summaryRow['first_upload'] ?? null,
      'last_upload'   => $summaryRow['last_upload'] ?? null,
      'diag'          => ['Conjunctivitis'=>0, 'NonConjunctivitis'=>0],
    ];

    $diag = runQuery($pdo, "
      SELECT pu.diagnosis_result AS k, COUNT(*) AS c
      FROM patient_uploads pu
      WHERE pu.uploaded_by = :id AND $windowSql
      GROUP BY pu.diagnosis_result
    ", [':id'=>$id, ':months'=>$months]);

    while($d = $diag->fetch(PDO::FETCH_ASSOC)){
      $bucket = norm_diag($d['k'] ?? null);
      if ($bucket && isset($summary['diag'][$bucket])) {
        $summary['diag'][$bucket] += (int)$d['c'];
      }
    }

    // distinct patients
    $distinctPatients = 0;
    if ($rows) {
      $ids = [];
      foreach($rows as $r){ if (!empty($r['patient_id'])) $ids[$r['patient_id']] = true; }
      $distinctPatients = count($ids);
    }

    return [$ctx, $rows, $summary, $distinctPatients];
  }

  // ------- patient report: accept patients.id OR users.id -------
  $patient = resolve_patient_row($pdo, $id);
  if (!$patient) { http_response_code(404); exit('Patient not found.'); }
  $patientId = (int)$patient['id'];

  // context from resolved patient row
  $ctx = [
    'id'        => $patient['id'],
    'name'      => $patient['name'],
    'gender'    => $patient['gender'],
    'dob'       => $patient['dob'],
    'region'    => $patient['region'],
    'contact'   => $patient['contact'],
    'town'      => $patient['town'],
    'created_at'=> $patient['created_at'],
  ];

  // rows for the resolved patient id
  $rows = runQuery($pdo, "
    SELECT 
      pu.id, pu.created_at, pu.diagnosis_result, pu.model_version, pu.confidence, pu.image_path,
      u.full_name AS uploader_name, u.healthcare_region AS uploader_region
    FROM patient_uploads pu
    LEFT JOIN users u ON u.id = pu.uploaded_by
    WHERE pu.patient_id = :pid AND $windowSql
    ORDER BY pu.created_at DESC
  ", [':pid'=>$patientId, ':months'=>$months])->fetchAll(PDO::FETCH_ASSOC);

  // summary (2 buckets)
  $summaryRow = runQuery($pdo, "
    SELECT COUNT(*) AS total, MIN(pu.created_at) AS first_upload, MAX(pu.created_at) AS last_upload
    FROM patient_uploads pu
    WHERE pu.patient_id = :pid AND $windowSql
  ", [':pid'=>$patientId, ':months'=>$months])->fetch(PDO::FETCH_ASSOC) ?: [];

  $summary = [
    'total_uploads' => (int)($summaryRow['total'] ?? 0),
    'first_upload'  => $summaryRow['first_upload'] ?? null,
    'last_upload'   => $summaryRow['last_upload'] ?? null,
    'diag'          => ['Conjunctivitis'=>0, 'NonConjunctivitis'=>0],
  ];

  $diag = runQuery($pdo, "
    SELECT pu.diagnosis_result AS k, COUNT(*) AS c
    FROM patient_uploads pu
    WHERE pu.patient_id = :pid AND $windowSql
    GROUP BY pu.diagnosis_result
  ", [':pid'=>$patientId, ':months'=>$months]);

  while($d = $diag->fetch(PDO::FETCH_ASSOC)){
    $bucket = norm_diag($d['k'] ?? null);
    if ($bucket && isset($summary['diag'][$bucket])) {
      $summary['diag'][$bucket] += (int)$d['c'];
    }
  }

  return [$ctx, $rows, $summary, 0];
}

// ---------- Column builders (DRY) ----------
function get_report_columns(string $type): array {
  if ($type === 'healthcare') {
    return [
      '#'              => fn($r,$i)=>$i+1,
      '🕒 Date'        => fn($r)=>tdt($r['created_at']),
      '🧍 Patient'     => fn($r)=>e($r['patient_name'] ?? '-'),
      '🚻 Gender'      => fn($r)=>e($r['patient_gender'] ?? '-'),
      '🎂 DOB (Age)'   => fn($r)=>tdate($r['patient_dob'] ?? null).' ('.e(age($r['patient_dob'] ?? null)).')',
      '🗺️ Region/Town' => fn($r)=>e(($r['patient_region'] ?? '-') . ' / ' . ($r['patient_town'] ?? '-')),
      '📞 Contact'     => fn($r)=>e($r['patient_contact'] ?? '-'),
      '🧪 Diagnosis'   => fn($r)=>e($r['diagnosis_result'] ?? '-'),
      '📈 Confidence'  => fn($r)=>is_null($r['confidence'])?'-':e(number_format((float)$r['confidence'],2)).'%',
      '🤖 Model'       => fn($r)=>e($r['model_version'] ?? '-'),
      '👁️ Image'       => function($r){
        $src = image_url($r['image_path'] ?? '');
        if (!$src) return '<span class="muted">-</span>';
        $alt = e(($r['patient_name'] ?? 'Eye')." – ".($r['diagnosis_result'] ?? ''));
        return '<img src="'.e($src).'" alt="'.$alt.'" class="thumb" loading="lazy" onerror="this.outerHTML=\'<span class=&quot;muted&quot;>missing</span>\'">';
      },
    ];
  }

  // PATIENT — removed 👨‍⚕️ Uploaded By and 📍 Uploader Region
  return [
    '#'               => fn($r,$i)=>$i+1,
    '🕒 Date'         => fn($r)=>tdt($r['created_at']),
    '🧪 Diagnosis'    => fn($r)=>e($r['diagnosis_result'] ?? '-'),
    '📈 Confidence'   => fn($r)=>is_null($r['confidence'])?'-':e(number_format((float)$r['confidence'],2)).'%',
    '🤖 Model'        => fn($r)=>e($r['model_version'] ?? '-'),
    '👁️ Image'        => function($r){
      $src = image_url($r['image_path'] ?? '');
      if (!$src) return '<span class="muted">-</span>';
      $alt = e("Eye – ".($r['diagnosis_result'] ?? ''));
      return '<img src="'.e($src).'" alt="'.$alt.'" class="thumb" loading="lazy" onerror="this.outerHTML=\'<span class=&quot;muted&quot;>missing</span>\'">';
    },
  ];
}



// ---------- View helpers ----------
function renderKeyValGrid(array $pairs){
  echo '<div class="card grid">';
  foreach($pairs as $k=>$v){
    echo '<div><strong>'.e($k).':</strong> '.$v.'</div>';
  }
  echo '</div>';
}

function renderTable(array $rows, array $columns){
  echo '<div class="card row"><table><thead><tr>';
  foreach($columns as $label => $_){ echo '<th>'.e($label).'</th>'; }
  echo '</tr></thead><tbody>';
  if (empty($rows)){
    echo '<tr><td colspan="'.count($columns).'" class="muted">No uploads in the selected window.</td></tr>';
  } else {
    foreach($rows as $i=>$r){
      echo '<tr>';
      foreach($columns as $label => $renderer){
        $val = $renderer($r, $i);
        echo '<td>'.(is_string($val) ? $val : e((string)$val)).'</td>';
      }
      echo '</tr>';
    }
  }
  echo '</tbody></table></div>';
}
