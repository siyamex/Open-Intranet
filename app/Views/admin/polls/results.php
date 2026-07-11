<div class="page-head">
    <h1><?= e((string) $poll['question']) ?></h1>
    <p><a href="<?= e(url('admin.polls')) ?>">&larr; Back to polls</a>
       · <a href="<?= e(url('admin.polls.results', ['id' => $poll['id']]) . '?export=csv') ?>">Export CSV</a></p>
</div>

<div class="card" style="max-width:640px;">
    <p class="text-muted"><?= (int) $poll['voters'] ?> participant(s) · <?= (int) $poll['total_votes'] ?> vote(s)
        <?= (int) $poll['is_anonymous'] === 1 ? '· anonymous (individual voters are not stored)' : '' ?></p>
    <div class="poll-results">
        <?php foreach ($poll['options'] as $option): ?>
        <?php $pct = $poll['total_votes'] > 0 ? round(100 * (int) $option['votes'] / $poll['total_votes']) : 0; ?>
        <div class="poll-row">
            <div class="poll-row-head">
                <span><?= e((string) $option['label']) ?></span>
                <span class="text-muted"><?= (int) $option['votes'] ?> · <?= $pct ?>%</span>
            </div>
            <div class="poll-bar"><span class="poll-bar-fill" style="width:0%;" data-width="<?= $pct ?>%"></span></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
