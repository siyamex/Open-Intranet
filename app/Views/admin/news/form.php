<?php use App\Core\View; ?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <p><a href="<?= e(url('admin.news')) ?>">&larr; Back to news</a>
    <?php if ($post !== null && $previewToken !== null): ?>
        · <a href="<?= e(url('news.show', ['slug' => $post['slug']]) . '?preview=' . $previewToken) ?>" target="_blank" rel="noopener">Preview as employee ↗</a>
    <?php endif; ?>
    </p>
</div>

<form method="post" enctype="multipart/form-data" id="news-form"
      action="<?= $post === null ? e(url('admin.news.store')) : e(url('admin.news.update', ['id' => $post['id']])) ?>">
    <?= csrf_field() ?>
    <?php if ($post !== null): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <div class="editor-layout">
        <div class="card">
            <div class="form-group">
                <label class="form-label" for="news-title">Title</label>
                <input class="form-control" id="news-title" name="title" required maxlength="255"
                       value="<?= e((string) old('title', $post['title'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="news-slug">Slug <span class="text-muted">(auto-generated, editable)</span></label>
                <input class="form-control" id="news-slug" name="slug" maxlength="255" pattern="[a-z0-9-]*"
                       value="<?= e((string) old('slug', $post['slug'] ?? '')) ?>" <?= $post !== null ? 'data-touched="1"' : '' ?>>
            </div>
            <div class="form-group">
                <label class="form-label" for="news-excerpt">Excerpt</label>
                <textarea class="form-control" id="news-excerpt" name="excerpt" rows="2" maxlength="500"><?= e((string) old('excerpt', $post['excerpt'] ?? '')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Body</label>
                <div class="wysiwyg" id="wysiwyg"
                     data-upload-url="<?= e(url('admin.news.upload-image')) ?>" data-csrf="<?= e(csrf_token()) ?>">
                    <div class="wysiwyg-toolbar" role="toolbar">
                        <button type="button" data-cmd="formatBlock" data-value="h2" title="Heading 2">H2</button>
                        <button type="button" data-cmd="formatBlock" data-value="h3" title="Heading 3">H3</button>
                        <span class="wt-sep"></span>
                        <button type="button" data-cmd="bold" title="Bold"><strong>B</strong></button>
                        <button type="button" data-cmd="italic" title="Italic"><em>I</em></button>
                        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                        <span class="wt-sep"></span>
                        <button type="button" data-cmd="insertUnorderedList" title="Bullet list">•≡</button>
                        <button type="button" data-cmd="insertOrderedList" title="Numbered list">1≡</button>
                        <button type="button" data-cmd="formatBlock" data-value="blockquote" title="Quote">❝</button>
                        <button type="button" data-cmd="formatBlock" data-value="pre" title="Code block">&lt;/&gt;</button>
                        <span class="wt-sep"></span>
                        <button type="button" data-action="link" title="Insert link">🔗</button>
                        <button type="button" data-action="image" title="Insert image">🖼</button>
                        <button type="button" data-action="table" title="Insert table">▦</button>
                        <span class="wt-sep"></span>
                        <button type="button" data-cmd="undo" title="Undo">↺</button>
                        <button type="button" data-cmd="redo" title="Redo">↻</button>
                    </div>
                    <div class="wysiwyg-area article-body" id="wysiwyg-area" contenteditable="true"><?= $post['body'] ?? '' /* sanitized on save */ ?></div>
                    <input type="file" id="wysiwyg-image-input" accept="image/*" hidden>
                </div>
                <textarea name="body" id="news-body" hidden></textarea>
            </div>
        </div>

        <aside class="editor-side">
            <div class="card">
                <h3>Publish</h3>
                <?php $currentStatus = (string) old('status', $post['status'] ?? 'draft'); ?>
                <div class="form-group">
                    <label class="form-label" for="news-status">Status</label>
                    <select class="form-control" id="news-status" name="status">
                        <option value="draft" <?= $currentStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <?php if ($canPublish): ?>
                        <option value="published" <?= in_array($currentStatus, ['published', 'scheduled'], true) ? 'selected' : '' ?>>Publish</option>
                        <option value="archived" <?= $currentStatus === 'archived' ? 'selected' : '' ?>>Archived</option>
                        <?php endif; ?>
                    </select>
                    <?php if (!$canPublish): ?><p class="form-hint">You can save drafts; an editor with publish rights takes it live.</p><?php endif; ?>
                </div>
                <?php if ($canPublish): ?>
                <div class="form-group" id="schedule-group">
                    <label class="form-label" for="news-published-at">Schedule <span class="text-muted">(leave empty = publish now)</span></label>
                    <input class="form-control" type="datetime-local" id="news-published-at" name="published_at"
                           value="<?= $post !== null && $post['published_at'] !== null && $post['status'] === 'scheduled' ? e(date('Y-m-d\TH:i', strtotime((string) $post['published_at']))) : '' ?>">
                </div>
                <?php endif; ?>
                <label class="form-check" style="margin-bottom:1rem;">
                    <input type="checkbox" name="allow_comments" value="1" <?= (int) old('allow_comments', $post['allow_comments'] ?? 1) === 1 ? 'checked' : '' ?>>
                    Allow comments
                </label>
                <button type="submit" class="btn btn-primary btn-block">Save</button>
            </div>

            <div class="card">
                <h3>Category</h3>
                <div class="form-group">
                    <select class="form-control" name="category_id" id="news-category">
                        <option value="">— none —</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) old('category_id', $post['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex; gap:0.4rem;">
                    <input class="form-control" id="new-category-name" placeholder="New category…" style="flex:1;">
                    <button type="button" class="btn btn-secondary btn-sm" id="add-category"
                            data-url="<?= e(url('admin.news.category.store')) ?>">Add</button>
                </div>
            </div>

            <div class="card">
                <h3>Cover image</h3>
                <div class="cover-preview" id="cover-preview">
                    <?php if ($post !== null && !empty($post['cover_path'])): ?>
                        <img src="<?= e(url('news.media', ['file' => basename((string) $post['cover_path'])])) ?>" alt="">
                    <?php else: ?>
                        <span class="text-muted">16:9 preview</span>
                    <?php endif; ?>
                </div>
                <input class="form-control" type="file" name="cover" id="news-cover" accept="image/jpeg,image/png,image/webp">
                <p class="form-hint">Shown as a 16:9 hero; re-encoded server-side.</p>
            </div>
        </aside>
    </div>
</form>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/editor.js')) ?>"></script>
<?php View::end(); ?>
