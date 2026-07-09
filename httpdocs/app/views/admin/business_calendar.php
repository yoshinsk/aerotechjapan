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

<section class="admin-panel calendar-editor" data-calendar-editor data-calendar-ai-endpoint="<?= e(url('/admin/ai-translate')) ?>" data-calendar-ai-csrf="<?= e(csrf_token()) ?>">
    <form class="admin-form" method="post" action="<?= e(url('/admin/business-calendar?month=' . $current)) ?>">
        <?= csrf_field() ?>
        <div class="calendar-editor-toolbar" data-calendar-status-toolbar>
            <?php foreach ($statusLabels as $status => $label): ?>
                <button class="button <?= $status === '' ? 'secondary' : '' ?>" type="button" data-calendar-apply-status="<?= e($status) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
            <button class="button secondary" type="button" data-calendar-clear-selection>選択解除</button>
        </div>
        <p class="admin-help">日付をクリックして選択し、営業状態やイベント情報を編集してください。最後に保存すると反映されます。</p>
        <h2><?= e($calendarMonth['label']) ?></h2>
        <div class="business-calendar-grid admin-calendar-grid">
            <?php foreach (['日', '月', '火', '水', '木', '金', '土'] as $weekday): ?>
                <div class="calendar-weekday"><?= e($weekday) ?></div>
            <?php endforeach; ?>
            <?php for ($blank = 0; $blank < (int)$calendarMonth['first_weekday']; $blank++): ?>
                <div class="calendar-day is-blank"></div>
            <?php endfor; ?>
            <?php foreach ($calendarMonth['days'] as $day): ?>
                <button class="calendar-day status-<?= e($day['status']) ?> <?= $day['is_today'] ? 'is-today' : '' ?> <?= $day['has_event'] ? 'has-event' : '' ?>" type="button" data-calendar-date="<?= e($day['date']) ?>" data-calendar-status="<?= e($day['override_status']) ?>" data-calendar-base-status="<?= e($day['base_status']) ?>">
                    <span class="calendar-day-number"><?= e($day['day']) ?></span>
                    <span class="calendar-day-status" data-calendar-status-label><?= e($statusLabels[$day['status']] ?? '') ?></span>
                    <?php if ($day['holiday_name'] !== ''): ?><span class="calendar-day-note"><?= e($day['holiday_name']) ?></span><?php endif; ?>
                    <span class="calendar-day-event" data-calendar-event-badge <?= $day['has_event'] ? '' : 'hidden' ?>>イベント</span>
                </button>
                <input type="hidden" name="status[<?= e($day['date']) ?>]" value="<?= e($day['override_status']) ?>" data-calendar-input="<?= e($day['date']) ?>">
                <input type="hidden" name="note_ja[<?= e($day['date']) ?>]" value="<?= e($day['note_ja']) ?>">
                <input type="hidden" name="note_en[<?= e($day['date']) ?>]" value="<?= e($day['note_en']) ?>">
            <?php endforeach; ?>
        </div>
        <div class="calendar-event-editor">
            <h3>イベント情報</h3>
            <p class="admin-help">日付をクリックすると、その日のイベント名とURLを編集できます。URLがある場合は公開ページで別ウィンドウのリンクになります。</p>
            <div class="calendar-event-empty" data-calendar-event-empty>編集する日付を選択してください。</div>
            <?php foreach ($calendarMonth['days'] as $day): ?>
                <div class="calendar-event-panel" data-calendar-event-panel="<?= e($day['date']) ?>" hidden>
                    <h4><?= e(date('Y年n月j日', strtotime($day['date']))) ?></h4>
                    <div class="calendar-event-fields">
                        <label>
                            イベント名（日本語）
                            <input name="event_name_ja[<?= e($day['date']) ?>]" value="<?= e($day['event_name_ja']) ?>" data-calendar-event-name="<?= e($day['date']) ?>" data-calendar-event-ja>
                        </label>
                        <label>
                            イベント名（英語・任意）
                            <input name="event_name_en[<?= e($day['date']) ?>]" value="<?= e($day['event_name_en']) ?>" data-calendar-event-name="<?= e($day['date']) ?>" data-calendar-event-en>
                        </label>
                        <label class="calendar-event-url-field">
                            URL（任意）
                            <input name="event_url[<?= e($day['date']) ?>]" value="<?= e($day['event_url']) ?>" placeholder="https://example.com/">
                        </label>
                        <div class="calendar-event-ai">
                            <button class="button secondary" type="button" data-calendar-event-ai>AIで英訳</button>
                            <button class="button danger" type="button" data-calendar-event-delete>イベントを削除</button>
                            <span data-calendar-event-ai-status>日本語イベント名を入力すると英語欄へ反映できます。</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="button" type="submit">保存</button>
    </form>
</section>
