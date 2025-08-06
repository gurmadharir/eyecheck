<?php
function formatDiagnosis($raw) {
  $rawDiag = trim($raw ?? '');
  $cleanDiag = strtolower(str_replace([' ', '_', '-'], '', $rawDiag));

  $label = match ($cleanDiag) {
    'conjunctivitis' => 'Conjunctivitis',
    'nonconjunctivitis', 'negative' => 'Non Conjunctivitis',
    default => ucfirst($rawDiag)
  };

  $style = match ($cleanDiag) {
    'conjunctivitis' => 'color: #e53935; font-weight: bold;',
    'nonconjunctivitis', 'negative' => 'color: #43a047; font-weight: bold;',
    default => 'font-weight: bold;'
  };

  return ['label' => $label, 'style' => $style];
}
