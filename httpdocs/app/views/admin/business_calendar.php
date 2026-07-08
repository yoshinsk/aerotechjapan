<?php
/**
 * httpdocs/app/views/admin/business_calendar.php
 * 営業日カレンダーの月表示と、日別例外ステータス編集UIを描画します。
 */
$year = (int)$calendarMonth['year'];
$month = (int)$calendarMonth['month'];
$current = sprintf('%04d-%02d', $year, $month);
$prev = (new DateTimeImmutable($current . '-01'))->modify('-1 month')->format('Y-m');
$next = (new DateTimeImmutable($current . '-01'))->modify('+1 month')->format('Y-m');
?>
<header class="admin-page-head">
    <div>
        <p class="eyebrow">BUSINESS CALENDAR</p>
        <h1>営業日カレンダー</h1>
    </div>
    <div class="admin-actions">
        <a class="button secondary" href="<?= e(url('/admin/business-calendar?month=' . $prev)) ?>">前月</a>
        <a class="button secondary" href="<?= e(url('/admin/business-calendar?month=' . $next)) ?>">翌月</a>
    </div>
</header>
<?php if ($saved): ?><div class="notice">保存しました。</div><?php endif; ?>

<section class="admin-panel calendar-editor" data-calendar-editor>
    <form class="admin-form" method="post" action="<?= e(url('/admin/business-calendar?month=' . $current)) ?>">
        <?= csrf_field() ?>
        <div class="calendar-editor-toolbar" data-calendar-status-toolbar>
            <?php foreach ($statusLabels as $status => $label): ?>
                <button class="button <?= $status === '' ? 'secondary' : '' ?>" type="button" data-calendar-apply-status="<?= e($status) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
        <p class="admin-help">日付をクリックして選択し、上の状態ボタンを押してください。最後に保存すると反映されます。</p>
        <h2><?= e($calendarMonth['label']) ?></h2>
        <div class="business-calendar-grid admin-calendar-grid">
            <?php foreach (['日', '月', '火', '水', '木', '金', '土'] as $weekday): ?>
                <div class="calendar-weekday"><?= e($weekday) ?></div>
            <?php endforeach; ?>
            <?php for ($blank = 0; $blank < (int)$calendarMonth['first_weekday']; $blank++): ?>
                <div class="calendar-day is-blank"></div>
            <?php endfor; ?>
            <?php foreach ($calendarMonth['days'] as $day): ?>
                <button class="calendar-day status-<?= e($day['status']) ?> <?= $day['is_today'] ? 'is-today' : '' ?>" type="button" data-calendar-date="<?= e($day['date']) ?>" data-calendar-status="<?= e($day['override_status']) ?>" data-calendar-base-status="<?= e($day['base_status']) ?>">
                    <span class="calendar-day-number"><?= e($day['day']) ?></span>
                    <span class="calendar-day-status" data-calendar-status-label><?= e($statusLabels[$day['status']] ?? '') ?></span>
                    <?php if ($day['holiday_name'] !== ''): ?><span class="calendar-day-note"><?= e($day['holiday_name']) ?></span><?php endif; ?>
                </button>
                <input type="hidden" name="status[<?= e($day['date']) ?>]" value="<?= e($day['override_status']) ?>" data-calendar-input="<?= e($day['date']) ?>">
            <?php endforeach; ?>
        </div>
        <button class="button" type="submit">保存</button>
    </form>
</section>
