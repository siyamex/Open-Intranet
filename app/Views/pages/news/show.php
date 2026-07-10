<?php use App\Core\View; ?>
<article class="article">
    <?php if (!$isLive): ?>
    <div class="card" style="border-left:4px solid var(--color-warning);">
        <strong>Preview</strong> — this post is <?= e((string) $post['status']) ?> and not visible to employees yet.
    </div>
    <?php endif; ?>

    <?php if (!empty($post['cover_path'])): ?>
    <div class="article-hero">
        <img src="<?= e(url('news.media', ['file' => basename((string) $post['cover_path'])])) ?>" alt="">
    </div>
    <?php endif; ?>

    <?php if (!empty($post['category_name'])): ?>
    <span class="chip" style="background: <?= e((string) ($post['category_color'] ?? '#4f46e5')) ?>;"><?= e((string) $post['category_name']) ?></span>
    <?php endif; ?>
    <h1 class="article-title"><?= e((string) $post['title']) ?></h1>
    <div class="news-meta" style="margin-bottom:1.5rem;">
        <?php partial('partials/avatar', ['person' => ['name' => $post['author_name'] ?? 'Unknown', 'avatar_path' => $post['author_avatar'] ?? null], 'size' => 32]); ?>
        <span>
            <?php if (!empty($post['author_uid'])): ?><a href="<?= e(url('people.show', ['id' => $post['author_uid']])) ?>"><?= e((string) $post['author_name']) ?></a>
            <?php else: ?><?= e((string) ($post['author_name'] ?? 'Unknown')) ?><?php endif; ?>
        </span>
        <span class="text-muted">· <?= $post['published_at'] !== null ? e(date('j F Y', strtotime((string) $post['published_at']))) : 'not published' ?> · <?= (int) $post['views'] ?> views</span>
    </div>

    <div class="article-body">
        <?= $post['body'] /* sanitized server-side on save */ ?>
    </div>

    <?php if ($reactionsEnabled && $isLive): ?>
    <div class="reactions" id="reactions"
         data-url="<?= e(url('news.react', ['slug' => $post['slug']])) ?>" data-csrf="<?= e(csrf_token()) ?>">
        <?php foreach (['👍', '🎉', '❤️', '💡', '😄'] as $emoji): ?>
        <button type="button" class="reaction <?= in_array($emoji, $myReactions, true) ? 'mine' : '' ?>" data-emoji="<?= $emoji ?>">
            <?= $emoji ?> <span class="reaction-count"><?= (int) ($reactions[$emoji] ?? 0) ?></span>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="article-author card">
        <?php partial('partials/avatar', ['person' => ['name' => $post['author_name'] ?? 'Unknown', 'avatar_path' => $post['author_avatar'] ?? null], 'size' => 56]); ?>
        <div>
            <strong><?= e((string) ($post['author_name'] ?? 'Unknown')) ?></strong>
            <p class="text-muted" style="margin:0;"><?= e((string) ($post['author_title'] ?? '')) ?></p>
        </div>
    </div>

    <nav class="article-nav">
        <?php if ($prev !== null): ?>
        <a class="article-nav-link" href="<?= e(url('news.show', ['slug' => $prev['slug']])) ?>">&larr; <?= e((string) $prev['title']) ?></a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($next !== null): ?>
        <a class="article-nav-link" style="text-align:right;" href="<?= e(url('news.show', ['slug' => $next['slug']])) ?>"><?= e((string) $next['title']) ?> &rarr;</a>
        <?php endif; ?>
    </nav>

    <?php if ($commentsEnabled && $isLive): ?>
    <section id="comments" class="card">
        <h2>Comments (<?= count($comments) ?>)</h2>
        <?php foreach ($comments as $comment): ?>
        <div class="comment">
            <?php partial('partials/avatar', ['person' => ['name' => $comment['user_name'], 'avatar_path' => $comment['user_avatar']], 'size' => 32]); ?>
            <div class="comment-body">
                <div><strong><?= e((string) $comment['user_name']) ?></strong>
                <span class="text-muted" style="font-size:0.8rem;"><?= e(date('j M Y H:i', strtotime((string) $comment['created_at']))) ?></span></div>
                <p><?= nl2br(e((string) $comment['body'])) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
        <form method="post" action="<?= e(url('news.comment', ['slug' => $post['slug']])) ?>" style="margin-top:1rem;">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="body">Add a comment</label>
                <textarea class="form-control" id="body" name="body" rows="3" required maxlength="2000"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Post comment</button>
        </form>
    </section>
    <?php endif; ?>
</article>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/news.js')) ?>"></script>
<?php View::end(); ?>
