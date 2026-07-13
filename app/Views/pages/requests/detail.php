<div class="page-head">
    <h1>Request #<?= (int) $submission['id'] ?> — <?= e((string) $submission['form_title']) ?></h1>
    <p><a href="<?= e(url('requests.catalog')) ?>">&larr; Back to requests</a></p>
</div>

<div class="kudos-layout">
    <div>
        <div class="card">
            <p>
                <span class="badge status-<?= e((string) $submission['status']) ?>"><?= e(str_replace('_', ' ', (string) $submission['status'])) ?></span>
                submitted by <strong><?= e((string) $submission['submitter_name']) ?></strong>
                on <?= e(date('j M Y H:i', strtotime((string) $submission['created_at']))) ?>
            </p>
            <div class="table-wrap">
            <table class="table">
                <tbody>
                <?php foreach ($fields as $field): ?>
                    <?php if ($field['type'] === 'section') continue; ?>
                    <?php $value = $data[$field['id']] ?? null; ?>
                    <tr>
                        <th style="width:40%;"><?= e($field['label']) ?></th>
                        <td>
                            <?php if (is_array($value)): ?>
                                <a href="<?= e(url('requests.file', ['file' => $value['file']])) ?>">📎 <?= e((string) ($value['name'] ?? 'attachment')) ?></a>
                            <?php else: ?>
                                <?= $value !== null && $value !== '' ? nl2br(e((string) $value)) : '<span class="text-muted">—</span>' ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <?php if ($isApprover && !in_array($submission['status'], ['approved', 'rejected'], true)): ?>
        <div class="card">
            <h2>Decide</h2>
            <form method="post" action="<?= e(url('requests.act', ['id' => $submission['id']])) ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Note to the submitter <span class="text-muted">(optional)</span></label>
                    <input class="form-control" name="note" maxlength="500">
                </div>
                <div class="checkbox-row">
                    <?php if ($submission['status'] === 'submitted'): ?>
                    <button type="submit" name="action" value="review" class="btn btn-secondary">Start review</button>
                    <?php endif; ?>
                    <button type="submit" name="action" value="approve" class="btn btn-primary">Approve ✓</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger">Reject ✗</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <aside>
        <div class="card">
            <h2>Timeline</h2>
            <ul class="request-timeline">
                <?php foreach ($events as $event): ?>
                <li>
                    <span class="timeline-dot status-<?= e((string) $event['action']) ?>"></span>
                    <div>
                        <strong><?= e(ucfirst(str_replace('_', ' ', (string) $event['action']))) ?></strong>
                        <?php if (!empty($event['actor_name'])): ?>by <?= e((string) $event['actor_name']) ?><?php endif; ?>
                        <br><span class="text-muted" style="font-size:0.82rem;"><?= e(date('j M Y H:i', strtotime((string) $event['created_at']))) ?></span>
                        <?php if (!empty($event['note'])): ?><br><em>“<?= e((string) $event['note']) ?>”</em><?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</div>
