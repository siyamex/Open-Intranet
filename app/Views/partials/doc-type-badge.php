<?php
$ext = strtolower(pathinfo((string) ($doc['original_name'] ?? ''), PATHINFO_EXTENSION));
$colors = [
    'pdf' => '#dc2626', 'docx' => '#2563eb', 'xlsx' => '#16a34a', 'pptx' => '#ea580c',
    'png' => '#7c3aed', 'jpg' => '#7c3aed', 'jpeg' => '#7c3aed', 'zip' => '#64748b',
];
?>
<span class="doc-badge" style="background: <?= e($colors[$ext] ?? '#64748b') ?>;"><?= e(strtoupper($ext ?: 'FILE')) ?></span>
