<div class="page-head">
    <h1>Requests</h1>
    <p class="text-muted">Submit HR/IT requests and track their approval.</p>
</div>

<?php if ($pendingApprovals !== []): ?>
<div class="card" style="border-left:4px solid var(--color-warning);">
    <h2>Waiting for your approval (<?= count($pendingApprovals) ?>)</h2>
    <ul class="gazette-list">
        <?php foreach ($pendingApprovals as $submission): ?>
        <li>
            <span class="badge status-<?= e((string) $submission['status']) ?>"><?= e(str_replace('_', ' ', (string) $submission['status'])) ?></span>
            <a class="gazette-title" href="<?= e(url('requests.detail', ['id' => $submission['id']])) ?>">
                <?= e((string) $submission['form_title']) ?> — <?= e((string) $submission['submitter_name']) ?>
            </a>
            <span class="text-muted"><?= e(date('j M H:i', strtotime((string) $submission['created_at']))) ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <h2>Request catalog</h2>
    <?php if ($forms === []): ?>
    <p class="text-muted">No request forms have been published yet.</p>
    <?php else: ?>
    <div class="theme-gallery">
        <?php foreach ($forms as $form): ?>
        <a class="card theme-card" href="<?= e(url('requests.show', ['slug' => $form['slug']])) ?>" style="color:var(--color-text);">
            <h3 style="margin:0;"><?= icon('file-text') ?> <?= e((string) $form['title']) ?></h3>
            <?php if (!empty($form['description'])): ?><p class="text-muted" style="margin:0.4rem 0 0;"><?= e((string) $form['description']) ?></p><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>My requests</h2>
    <?php if ($mine === []): ?>
    <p class="text-muted">You haven't submitted anything yet.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>#</th><th>Form</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($mine as $submission): ?>
        <tr>
            <td>#<?= (int) $submission['id'] ?></td>
            <td><?= e((string) $submission['form_title']) ?></td>
            <td><span class="badge status-<?= e((string) $submission['status']) ?>"><?= e(str_replace('_', ' ', (string) $submission['status'])) ?></span></td>
            <td><?= e(date('j M Y H:i', strtotime((string) $submission['created_at']))) ?></td>
            <td><a class="btn btn-secondary btn-sm" href="<?= e(url('requests.detail', ['id' => $submission['id']])) ?>">Timeline</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
