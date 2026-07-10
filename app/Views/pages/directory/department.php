<div class="page-head">
    <h1><?= e((string) $department['name']) ?></h1>
</div>

<?php if (!empty($department['head_id'])): ?>
<div class="card" style="max-width:560px;">
    <h2>Department head</h2>
    <div style="display:flex; align-items:center; gap:0.75rem;">
        <?php partial('partials/avatar', ['person' => ['name' => $department['head_name'], 'avatar_path' => $department['head_avatar']], 'size' => 48]); ?>
        <div>
            <a href="<?= e(url('people.show', ['id' => $department['head_id']])) ?>"><strong><?= e((string) $department['head_name']) ?></strong></a>
            <p class="text-muted" style="margin:0;"><?= e((string) ($department['head_title'] ?? '')) ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($children !== []): ?>
<div class="card" style="max-width:560px;">
    <h2>Sub-departments</h2>
    <ul style="list-style:none; margin:0; padding:0;">
        <?php foreach ($children as $child): ?>
        <li style="padding:0.35rem 0;">
            <a href="<?= e(url('directory.department', ['id' => $child['id']])) ?>"><?= e((string) $child['name']) ?></a>
            <span class="text-muted">(<?= (int) $child['member_count'] ?> people)</span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <h2>Members (<?= count($members) ?>)</h2>
    <?php if ($members === []): ?>
    <p class="text-muted">No members yet.</p>
    <?php else: ?>
    <div class="dir-grid">
        <?php foreach ($members as $m): ?>
        <div class="card dir-card" style="margin:0;">
            <a href="<?= e(url('people.show', ['id' => $m['id']])) ?>"><?php partial('partials/avatar', ['person' => $m, 'size' => 56]); ?></a>
            <a class="dir-name" href="<?= e(url('people.show', ['id' => $m['id']])) ?>"><?= e((string) $m['name']) ?></a>
            <span class="text-muted dir-title"><?= e((string) ($m['job_title'] ?? '')) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
