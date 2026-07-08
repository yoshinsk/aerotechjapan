<?php
/**
 * httpdocs/app/views/page.php
 * CMSで編集された固定ページを公開画面へ描画します。
 */
?>
<article class="section narrow container">
    <p class="eyebrow">AERO TECH JAPAN</p>
    <h1><?= e(localized($page, 'title')) ?></h1>
    <div class="rich-content"><?= render_rich_text(localized($page, 'body')) ?></div>
    <?php if (!empty($businessCalendarMonths)): ?>
        <section class="business-calendar-section">
            <h2><?= e(t('営業日カレンダー', 'Business Calendar')) ?></h2>
            <p class="muted"><?= e(t('通常定休日: 日曜日・祝日', 'Regular holidays: Sundays and Japanese national holidays')) ?></p>
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
                                <div class="calendar-day status-<?= e($day['status']) ?> <?= $day['is_today'] ? 'is-today' : '' ?>">
                                    <span class="calendar-day-number"><?= e($day['day']) ?></span>
                                    <span class="calendar-day-status"><?= e($businessStatusLabels[$day['status']] ?? '') ?></span>
                                    <?php if ($day['holiday_name'] !== ''): ?><span class="calendar-day-note"><?= e($day['holiday_name']) ?></span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>
