<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Employee Directory</h1>
</div>

<div class="filter-bar" id="dir-filters"
     data-api="<?= e(url('directory.api')) ?>">
    <input class="form-control" type="search" id="dir-q" placeholder="Search people, titles, skills…" style="min-width:260px;" autofocus>
    <select class="form-control" id="dir-department">
        <option value="">All departments</option>
        <?php foreach ($departments as $d): ?>
            <?php if ($d['parent_id'] === null): ?>
            <option value="<?= (int) $d['id'] ?>"><?= e((string) $d['name']) ?></option>
            <?php foreach ($departments as $c): ?>
                <?php if ((int) ($c['parent_id'] ?? 0) === (int) $d['id']): ?>
                <option value="<?= (int) $c['id'] ?>">&nbsp;&nbsp;— <?= e((string) $c['name']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
    <select class="form-control" id="dir-location">
        <option value="">All locations</option>
        <?php foreach ($locations as $loc): ?>
        <option value="<?= e((string) $loc) ?>"><?= e((string) $loc) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-control" id="dir-role">
        <option value="">All roles</option>
        <?php foreach ($roles as $r): ?>
        <option value="<?= (int) $r['id'] ?>"><?= e((string) $r['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <span style="flex:1;"></span>
    <button type="button" class="btn btn-secondary btn-sm" id="dir-view-toggle">Table view</button>
</div>

<div class="az-bar" id="dir-az">
    <button type="button" class="az-letter active" data-letter="">All</button>
    <?php foreach (range('A', 'Z') as $letter): ?>
    <button type="button" class="az-letter" data-letter="<?= $letter ?>"><?= $letter ?></button>
    <?php endforeach; ?>
</div>

<div id="dir-status" class="text-muted" style="margin-bottom:0.75rem;"></div>
<div id="dir-results" class="dir-grid" aria-live="polite"></div>
<nav class="pagination" id="dir-pagination"></nav>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/directory.js')) ?>"></script>
<?php View::end(); ?>
