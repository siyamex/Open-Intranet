<?php
/**
 * Reusable avatar: expects $person (array with name + avatar_path) and
 * optional $size (px, default 36).
 */
$size = $size ?? 36;
$initials = '';
foreach (array_slice(preg_split('/\s+/', trim((string) ($person['name'] ?? ''))) ?: [], 0, 2) as $word) {
    $initials .= mb_strtoupper(mb_substr($word, 0, 1));
}
$avatarFile = $person['avatar_path'] ?? null;
?>
<?php if (is_string($avatarFile) && $avatarFile !== ''): ?>
<img class="avatar" src="<?= e(url('avatar', ['file' => basename($avatarFile)])) ?>" alt="<?= e((string) ($person['name'] ?? '')) ?>"
     style="width:<?= (int) $size ?>px;height:<?= (int) $size ?>px;" loading="lazy">
<?php else: ?>
<span class="avatar avatar-initials" style="width:<?= (int) $size ?>px;height:<?= (int) $size ?>px;font-size:<?= (int) round($size * 0.4) ?>px;"
      aria-hidden="true"><?= e($initials ?: '?') ?></span>
<?php endif; ?>
