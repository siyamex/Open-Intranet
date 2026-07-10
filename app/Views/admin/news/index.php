<div class="page-head">
    <h1>News</h1>
</div>

<div class="card">
    <div class="filter-bar">
        <?php foreach (['' => 'All', 'draft' => 'Drafts', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived'] as $key => $label): ?>
        <a class="btn btn-sm <?= $status === $key ? 'btn-primary' : 'btn-secondary' ?>"
           href="<?= e(url('admin.news') . ($key !== '' ? '?status=' . $key : '')) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <span style="flex:1;"></span>
        <a class="btn btn-primary" href="<?= e(url('admin.news.create')) ?>">New post</a>
    </div>

    <div class="table-wrap">
    <table class="table">
        <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Published</th><th>Views</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($posts as $post): ?>
        <tr>
            <td>
                <?php if ((int) $post['is_pinned'] === 1): ?>📌 <?php endif; ?>
                <strong><?= e((string) $post['title']) ?></strong>
            </td>
            <td><?= e((string) ($post['category_name'] ?? '—')) ?></td>
            <td><?= e((string) ($post['author_name'] ?? '—')) ?></td>
            <td><span class="badge status-<?= e((string) $post['status']) ?>"><?= e((string) $post['status']) ?></span></td>
            <td><?= $post['published_at'] !== null ? e(date('j M Y H:i', strtotime((string) $post['published_at']))) : '—' ?></td>
            <td><?= (int) $post['views'] ?></td>
            <td style="white-space:nowrap;">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('admin.news.edit', ['id' => $post['id']])) ?>">Edit</a>
                <?php if ($canPublish): ?>
                <form method="post" action="<?= e(url('admin.news.pin', ['id' => $post['id']])) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm"><?= (int) $post['is_pinned'] === 1 ? 'Unpin' : 'Pin' ?></button>
                </form>
                <?php if ($post['status'] !== 'archived'): ?>
                <form method="post" action="<?= e(url('admin.news.archive', ['id' => $post['id']])) ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">Archive</button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
                <form method="post" action="<?= e(url('admin.news.destroy', ['id' => $post['id']])) ?>" style="display:inline;"
                      onsubmit="return confirm('Delete this post permanently?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($posts === []): ?>
        <tr><td colspan="7" class="text-muted">No posts<?= $status !== '' ? " with status '{$status}'" : '' ?>.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
