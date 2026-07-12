<?php use App\Core\View; ?>
<div class="page-head">
    <h1><?= e($title) ?></h1>
    <p><a href="<?= e(url('wiki.space', ['slug' => $space['slug']])) ?>">&larr; <?= e((string) $space['name']) ?></a></p>
</div>

<form method="post" action="<?= e(url('wiki.save', ['slug' => $space['slug']])) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="page_id" value="<?= $page !== null ? (int) $page['id'] : 0 ?>">

    <div class="card">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Title</label>
                <input class="form-control" name="title" required maxlength="255" value="<?= e((string) ($page['title'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Parent page</label>
                <select class="form-control" name="parent_id">
                    <option value="">— top level —</option>
                    <?php foreach ($pages as $candidate): ?>
                        <?php if ($page !== null && (int) $candidate['id'] === (int) $page['id']) continue; ?>
                        <option value="<?= (int) $candidate['id'] ?>" <?= $page !== null && (int) ($page['parent_id'] ?? 0) === (int) $candidate['id'] ? 'selected' : '' ?>><?= e((string) $candidate['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Page owner</label>
                <select class="form-control searchable" name="owner_id">
                    <?php foreach ($people as $person): ?>
                    <option value="<?= (int) $person['id'] ?>" <?= (int) ($page['owner_id'] ?? \App\Core\Auth::id()) === (int) $person['id'] ? 'selected' : '' ?>><?= e((string) $person['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Review due <span class="text-muted">(owner gets a reminder)</span></label>
                <input class="form-control" type="date" name="review_due" value="<?= e((string) ($page['review_due'] ?? '')) ?>">
            </div>
        </div>

        <div class="wiki-editor" data-preview-url="<?= e(url('wiki.preview')) ?>" data-csrf="<?= e(csrf_token()) ?>">
            <div>
                <label class="form-label">Markdown</label>
                <textarea class="form-control wiki-md" name="body_md" id="wiki-md" rows="22"
                          placeholder="# Heading&#10;&#10;Write **markdown** here…"><?= e((string) ($page['body_md'] ?? '')) ?></textarea>
                <p class="form-hint"># heading · **bold** · *italic* · `code` · ``` block · - list · 1. list · &gt; quote · [link](https://…) · ---</p>
            </div>
            <div>
                <label class="form-label">Preview</label>
                <div class="card article-body wiki-body wiki-preview" id="wiki-preview" style="min-height:420px;"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save page</button>
    </div>
</form>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/wiki.js')) ?>"></script>
<?php View::end(); ?>
