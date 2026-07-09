<?php
/**
 * httpdocs/app/views/home.php
 * トップページとしてヒーロー、カテゴリ、注目商品、ニュースを描画します。
 */
$heroProduct = $featuredProducts[0] ?? $latestProducts[0] ?? null;
$heroImage = $heroProduct['main_image'] ?? 'img/news-img/newsimg20240809_1.jpg';
$heroCalendarStatusShort = [
    BusinessCalendar::STATUS_OPEN => '',
    BusinessCalendar::STATUS_CLOSED => t('休', 'Closed'),
    BusinessCalendar::STATUS_AM_CLOSED => t('午前休', 'AM'),
    BusinessCalendar::STATUS_PM_CLOSED => t('午後休', 'PM'),
];
$heroCalendarPrev = '';
$heroCalendarNext = '';
if (!empty($businessCalendarMonth)) {
    $heroCalendarDate = new DateTimeImmutable(sprintf('%04d-%02d-01', $businessCalendarMonth['year'], $businessCalendarMonth['month']));
    $heroCalendarPrev = $heroCalendarDate->modify('-1 month')->format('Y-m');
    $heroCalendarNext = $heroCalendarDate->modify('+1 month')->format('Y-m');
}
?>
<section class="hero">
    <img class="hero-bg" src="<?= e(media_url($heroImage)) ?>" alt="">
    <div class="hero-content">
        <div class="hero-copy">
            <p class="eyebrow">AERO PARTS / BODY KIT / OEM</p>
            <h1><?= e(localized($home ?? [], 'title', 'AERO TECH JAPAN')) ?></h1>
            <p><?= e(excerpt(localized($home ?? [], 'body', t('エアロパーツ、ボディキット、OEM製作まで。現場で作り込む日本発のカスタムパーツブランドです。', 'Japanese aero parts, body kits, and OEM production built for real vehicles.')), 180)) ?></p>
            <div class="actions">
                <a class="button" href="<?= e(url('/products')) ?>"><?= e(t('製品を見る', 'View products')) ?></a>
                <a class="button secondary" href="<?= e(url('/contact')) ?>"><?= e(t('問い合わせる', 'Contact us')) ?></a>
            </div>
        </div>
        <?php if (!empty($businessCalendarMonth)): ?>
            <aside class="hero-calendar" id="home-business-calendar" aria-label="<?= e(t('今月の営業日カレンダー', 'This month business calendar')) ?>">
                <div class="hero-calendar-head">
                    <div>
                        <p class="eyebrow">Business Calendar</p>
                        <h2><?= e($businessCalendarMonth['label']) ?></h2>
                    </div>
                    <div class="hero-calendar-side">
                        <span><?= e(t('日曜・祝日定休', 'Closed Sundays / holidays')) ?></span>
                        <div class="hero-calendar-nav" aria-label="<?= e(t('表示月を変更', 'Change month')) ?>">
                            <a href="<?= e(url('/?calendar_month=' . rawurlencode($heroCalendarPrev) . '#home-business-calendar')) ?>" aria-label="<?= e(t('前月', 'Previous month')) ?>">‹</a>
                            <a href="<?= e(url('/?calendar_month=' . rawurlencode($heroCalendarNext) . '#home-business-calendar')) ?>" aria-label="<?= e(t('翌月', 'Next month')) ?>">›</a>
                        </div>
                    </div>
                </div>
                <div class="hero-calendar-grid">
                    <?php foreach (['日', '月', '火', '水', '木', '金', '土'] as $weekday): ?>
                        <div class="hero-calendar-weekday"><?= e($weekday) ?></div>
                    <?php endforeach; ?>
                    <?php for ($blank = 0; $blank < (int)$businessCalendarMonth['first_weekday']; $blank++): ?>
                        <div class="hero-calendar-day is-blank"></div>
                    <?php endfor; ?>
                    <?php foreach ($businessCalendarMonth['days'] as $day): ?>
                        <?php $shortStatus = $heroCalendarStatusShort[$day['status']] ?? ''; ?>
                        <div class="hero-calendar-day status-<?= e($day['status']) ?> <?= $day['is_today'] ? 'is-today' : '' ?> <?= $day['has_event'] ? 'has-event' : '' ?>">
                            <span class="hero-calendar-number"><?= e($day['day']) ?></span>
                            <?php if ($day['has_event']): ?><span class="hero-calendar-event-dot" aria-label="<?= e(t('イベントあり', 'Event')) ?>"></span><?php endif; ?>
                            <?php if ($shortStatus !== ''): ?><span class="hero-calendar-status"><?= e($shortStatus) ?></span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="hero-calendar-legend">
                    <span><i class="legend-open"></i><?= e(t('営業日', 'Open')) ?></span>
                    <span><i class="legend-closed"></i><?= e(t('休日', 'Closed')) ?></span>
                    <span><i class="legend-half"></i><?= e(t('午前休・午後休', 'Half day')) ?></span>
                </div>
                <?php if (!empty($businessCalendarMonth['events'])): ?>
                    <div class="hero-calendar-events">
                        <p><?= e(t('イベント', 'Events')) ?></p>
                        <ul>
                            <?php foreach ($businessCalendarMonth['events'] as $event): ?>
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
                    </div>
                <?php endif; ?>
            </aside>
        <?php endif; ?>
    </div>
</section>

<section class="section container-fluid">
    <div class="section-head row align-items-end g-3">
        <div>
            <p class="eyebrow">Brands</p>
            <h2><?= e(t('カテゴリ', 'Categories')) ?></h2>
        </div>
        <a class="button secondary" href="<?= e(url('/products')) ?>"><?= e(t('すべて表示', 'All products')) ?></a>
    </div>
    <div class="category-strip row row-cols-2 row-cols-lg-4 g-2">
        <?php foreach ($categories as $category): ?>
            <div class="col">
                <a class="category-chip h-100" href="<?= e(url('/category/' . $category['slug'])) ?>">
                    <strong><?= e(localized($category, 'name')) ?></strong>
                    <span><?= e(excerpt(localized($category, 'description'), 55)) ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container-fluid">
    <div class="section-head">
        <div>
            <p class="eyebrow">Products</p>
            <h2><?= e(t('注目製品', 'Featured products')) ?></h2>
        </div>
    </div>
    <div class="grid row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        <?php foreach (($featuredProducts ?: $latestProducts) as $product): ?>
            <div class="col"><?php require APP_ROOT . '/views/partials/product_card.php'; ?></div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container-fluid">
    <div class="section-head">
        <div>
            <p class="eyebrow">News</p>
            <h2><?= e(t('最新情報', 'Latest news')) ?></h2>
        </div>
        <a class="button secondary" href="<?= e(url('/news')) ?>"><?= e(t('一覧へ', 'View all')) ?></a>
    </div>
    <div class="grid row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        <?php foreach ($newsPosts as $post): ?>
            <div class="col">
                <article class="news-card card h-100">
                    <?php if (!empty($post['image_path']) && is_image_path($post['image_path'])): ?>
                        <img class="card-img-top" src="<?= e(media_url($post['image_path'])) ?>" alt="<?= e(localized($post, 'title')) ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="card-meta"><?= e(date('Y.m.d', strtotime($post['published_at']))) ?></div>
                        <h3 class="card-title"><a href="<?= e(url('/news/' . $post['slug'])) ?>"><?= e(localized($post, 'title')) ?></a></h3>
                        <p class="muted"><?= e(excerpt(localized($post, 'body'), 100)) ?></p>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>
