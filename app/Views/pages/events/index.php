<?php use App\Core\View; ?>
<div class="page-head">
    <h1>Events</h1>
</div>

<div class="card" id="calendar"
     data-api="<?= e(url('events.api')) ?>"
     data-feed="<?= e(url('events.feed', ['userId' => \App\Core\Auth::id(), 'token' => \App\Controllers\EventController::feedToken((int) \App\Core\Auth::id())])) ?>">
    <div class="filter-bar" style="margin-bottom:0.75rem;">
        <button type="button" class="btn btn-secondary btn-sm" id="cal-prev">&larr;</button>
        <strong id="cal-title" style="min-width:170px; text-align:center;"></strong>
        <button type="button" class="btn btn-secondary btn-sm" id="cal-next">&rarr;</button>
        <button type="button" class="btn btn-secondary btn-sm" id="cal-today">Today</button>
        <span style="flex:1;"></span>
        <div class="tabs" style="margin:0; border:none;">
            <button type="button" class="tab active" data-cal-view="month">Month</button>
            <button type="button" class="tab" data-cal-view="week">Week</button>
            <button type="button" class="tab" data-cal-view="list">List</button>
        </div>
    </div>
    <div id="cal-body"><p class="text-muted">Loading…</p></div>
    <p class="form-hint" style="margin-top:0.75rem;">
        📅 Personal calendar feed (paste into Outlook/Google Calendar):
        <code data-select-onclick style="cursor:pointer;"><span id="cal-feed-url"></span></code>
    </p>
</div>

<?php View::start('scripts'); ?>
<script src="<?= e(asset('js/calendar.js')) ?>"></script>
<?php View::end(); ?>
