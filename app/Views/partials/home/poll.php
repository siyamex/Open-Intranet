<section class="home-section">
    <div class="home-section-head">
        <h2><?= icon('chart-line') ?> Poll</h2>
        <?php if ((int) $activePoll['is_anonymous'] === 1): ?><span class="badge">anonymous</span><?php endif; ?>
    </div>
    <div class="card">
        <h3 style="margin-top:0;"><?= e((string) $activePoll['question']) ?></h3>

        <?php if ($activePoll['has_voted'] || $activePoll['is_closed']): ?>
            <div class="poll-results">
                <?php foreach ($activePoll['options'] as $option): ?>
                <?php $pct = $activePoll['total_votes'] > 0 ? round(100 * (int) $option['votes'] / $activePoll['total_votes']) : 0; ?>
                <div class="poll-row">
                    <div class="poll-row-head">
                        <span><?= e((string) $option['label']) ?></span>
                        <span class="text-muted"><?= (int) $option['votes'] ?> · <?= $pct ?>%</span>
                    </div>
                    <div class="poll-bar"><span class="poll-bar-fill" style="width:0%;" data-width="<?= $pct ?>%"></span></div>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="text-muted" style="margin-bottom:0;"><?= (int) $activePoll['voters'] ?> participant(s)<?= $activePoll['is_closed'] ? ' · poll closed' : '' ?></p>
        <?php else: ?>
            <form method="post" action="<?= e(url('polls.vote', ['id' => $activePoll['id']])) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="back" value="/">
                <?php $inputType = $activePoll['type'] === 'multiple' ? 'checkbox' : 'radio'; ?>
                <?php foreach ($activePoll['options'] as $option): ?>
                <label class="form-check" style="margin-bottom:0.45rem;">
                    <input type="<?= $inputType ?>" name="options[]" value="<?= (int) $option['id'] ?>">
                    <?= e((string) $option['label']) ?>
                </label>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.5rem;">Vote</button>
                <?php if ($activePoll['closes_at'] !== null): ?>
                <span class="text-muted" style="font-size:0.82rem;"> closes <?= e(date('j M H:i', strtotime((string) $activePoll['closes_at']))) ?></span>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</section>
