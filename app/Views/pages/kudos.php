<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Kudos wall 🎉</h1>
    <p class="text-muted">Publicly appreciate a colleague.</p>
</div>

<div class="kudos-layout">
    <div>
        <div class="card">
            <h2>Send kudos</h2>
            <form method="post" action="<?= e(url('kudos.store')) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">To</label>
                        <select class="form-control searchable" name="recipient_id" required>
                            <option value="">— pick a colleague —</option>
                            <?php foreach ($people as $person): ?>
                            <option value="<?= (int) $person['id'] ?>" <?= (string) ($_GET['to'] ?? '') === (string) $person['id'] ? 'selected' : '' ?>><?= e((string) $person['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Value</label>
                        <select class="form-control" name="value_id">
                            <option value="">— optional —</option>
                            <?php foreach ($values as $value): ?>
                            <option value="<?= (int) $value['id'] ?>"><?= e((string) ($value['emoji'] ?? '')) ?> <?= e((string) $value['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Message <span class="text-muted">(max 300 chars)</span></label>
                    <textarea class="form-control" name="message" rows="2" maxlength="300" required><?= isset($_GET['wish']) ? 'Happy birthday! 🎂' : '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send kudos 🎉</button>
            </form>
        </div>

        <?php foreach ($feed as $kudos): ?>
        <div class="card kudos-card">
            <div class="kudos-head">
                <?php partial('partials/avatar', ['person' => ['name' => $kudos['sender_name'] ?? '?', 'avatar_path' => $kudos['sender_avatar']], 'size' => 34]); ?>
                <div>
                    <strong><?= e((string) ($kudos['sender_name'] ?? 'Someone')) ?></strong>
                    <span class="text-muted">→</span>
                    <a href="<?= e(url('people.show', ['id' => $kudos['recipient_uid']])) ?>"><strong><?= e((string) $kudos['recipient_name']) ?></strong></a>
                    <?php if (!empty($kudos['value_label'])): ?>
                    <span class="badge"><?= e((string) ($kudos['value_emoji'] ?? '')) ?> <?= e((string) $kudos['value_label']) ?></span>
                    <?php endif; ?>
                    <br><span class="text-muted" style="font-size:0.8rem;"><?= e(date('j M Y H:i', strtotime((string) $kudos['created_at']))) ?></span>
                </div>
            </div>
            <p class="kudos-message"><?= e((string) $kudos['message']) ?></p>
            <div class="reactions kudos-reactions" data-url="<?= e(base_url('kudos')) ?>" data-csrf="<?= e(csrf_token()) ?>">
                <?php
                $counts = [];
                $mine = [];
                foreach ($kudos['reactions'] as $reaction) {
                    $counts[$reaction['emoji']] = (int) $reaction['n'];
                    if ((int) $reaction['mine'] === 1) {
                        $mine[] = $reaction['emoji'];
                    }
                }
                ?>
                <?php foreach (['👏', '❤️', '🎉', '💯'] as $emoji): ?>
                <button type="button" class="reaction <?= in_array($emoji, $mine, true) ? 'mine' : '' ?>" data-id="<?= (int) $kudos['id'] ?>" data-emoji="<?= $emoji ?>">
                    <?= $emoji ?> <span class="reaction-count"><?= (int) ($counts[$emoji] ?? 0) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if ($feed === []): ?>
        <div class="card"><p class="text-muted" style="margin:0;">No kudos yet — be the first! 🎉</p></div>
        <?php endif; ?>
    </div>

    <aside>
        <div class="card">
            <h2>🏆 This month</h2>
            <?php if ($leaderboard === []): ?>
            <p class="text-muted">No kudos received yet this month.</p>
            <?php else: ?>
            <ol class="kudos-leaderboard">
                <?php foreach ($leaderboard as $i => $row): ?>
                <li>
                    <span class="kudos-rank"><?= ['🥇', '🥈', '🥉'][$i] ?? ($i + 1) . '.' ?></span>
                    <?php partial('partials/avatar', ['person' => $row, 'size' => 28]); ?>
                    <a href="<?= e(url('people.show', ['id' => $row['id']])) ?>"><?= e((string) $row['name']) ?></a>
                    <span class="text-muted">×<?= (int) $row['received'] ?></span>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/kudos.js')) ?>"></script>
<?php View::end(); ?>
