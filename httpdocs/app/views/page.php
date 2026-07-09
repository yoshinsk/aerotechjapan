<?php
/**
 * httpdocs/app/views/page.php
 * CMSで編集された固定ページを公開画面へ描画します。
 */
$businessCalendarPrev = '';
$businessCalendarNext = '';
if (!empty($businessCalendarMonths)) {
    $firstCalendarMonth = $businessCalendarMonths[0];
    $businessCalendarDate = new DateTimeImmutable(sprintf('%04d-%02d-01', $firstCalendarMonth['year'], $firstCalendarMonth['month']));
    $businessCalendarPrev = $businessCalendarDate->modify('-1 month')->format('Y-m');
    $businessCalendarNext = $businessCalendarDate->modify('+1 month')->format('Y-m');
}
?>
<article class="section narrow container">
    <p class="eyebrow">AERO TECH JAPAN</p>
    <h1><?= e(localized($page, 'title')) ?></h1>
    <div class="rich-content"><?= render_rich_text(localized($page, 'body')) ?></div>
    <?php if (!empty($businessCalendarMonths)): ?>
        <section class="business-calendar-section" id="business-calendar">
            <h2><?= e(t('営業日カレンダー', 'Business Calendar')) ?></h2>
            <p class="muted"><?= e(t('通常定休日: 日曜日・祝日', 'Regular holidays: Sundays and Japanese national holidays')) ?></p>
            <div class="calendar-month-nav" aria-label="<?= e(t('表示月を変更', 'Change month')) ?>">
                <a class="button secondary" href="<?= e(url('/page/about?calendar_month=' . rawurlencode($businessCalendarPrev) . '#business-calendar')) ?>"><?= e(t('前月', 'Previous')) ?></a>
                <span><?= e($businessCalendarMonths[0]['label']) ?> - <?= e($businessCalendarMonths[count($businessCalendarMonths) - 1]['label']) ?></span>
                <a class="button secondary" href="<?= e(url('/page/about?calendar_month=' . rawurlencode($businessCalendarNext) . '#business-calendar')) ?>"><?= e(t('翌月', 'Next')) ?></a>
            </div>
            <div class="business-calendar-months">
                <?php foreach ($businessCalendarMonths as $month): ?>
                    <article class="business-calendar-month">
                        <h3><?= e($month['label']) ?></h3>
                        <div class="business-calendar-grid">
                            <?php foreach (['日', '月', '火', '水', '木', '金', '土'] as $weekday): ?>
                                <div class="calendar-weekday"><?= e($weekday) ?></div>
                            <?php endforeach; ?>
                            <?php for ($blank = 0; $blank < (int)$month['first_weekday']; $blank++): ?>
                                <div class="calendar-day is-blank"></div>
                            <?php endfor; ?>
                            <?php foreach ($month['days'] as $day): ?>
                                <div class="calendar-day status-<?= e($day['status']) ?> <?= $day['is_today'] ? 'is-today' : '' ?> <?= $day['has_event'] ? 'has-event' : '' ?>">
                                    <span class="calendar-day-number"><?= e($day['day']) ?></span>
                                    <span class="calendar-day-status"><?= e($businessStatusLabels[$day['status']] ?? '') ?></span>
                                    <?php if ($day['holiday_name'] !== ''): ?><span class="calendar-day-note"><?= e($day['holiday_name']) ?></span><?php endif; ?>
                                    <?php if ($day['has_event']): ?><span class="calendar-day-event"><?= e(t('イベント', 'Event')) ?></span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="calendar-events">
                            <h4><?= e(t('イベント', 'Events')) ?></h4>
                            <?php if (!empty($month['events'])): ?>
                                <ul>
                                    <?php foreach ($month['events'] as $event): ?>
                                        <?php $eventName = localized($event, 'event_name'); ?>
                                        <?php if ($eventName === ''): ?><?php continue; ?><?php endif; ?>
                                        <li>
                                            <time datetime="<?= e($event['date']) ?>"><?= e(date('n/j', strtotime($event['date']))) ?></time>
                                            <?php if ($event['event_url'] !== ''): ?>
                                                <a href="<?= e($event['event_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($eventName) ?></a>
                                            <?php else: ?>
                                                <span><?= e($eventName) ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="muted"><?= e(t('現在表示中の月にイベント予定はありません。', 'No events are listed for this month.')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>
