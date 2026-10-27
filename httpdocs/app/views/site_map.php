<?php
/**
 * httpdocs/app/views/site_map.php
 * メーカー別に車種を検索し、紐付く現行商品ページへ案内します。
 */
$groups = [];
foreach ($entries as $entry) {
    $groups[$entry['group_name']][] = $entry;
}
?>
<section class="section container directory-page" data-site-map>
    <div class="section-head">
        <div>
            <p class="eyebrow">PRICE LIST DIRECTORY</p>
            <h1><?= e(t('車種別サイトマップ', 'Site Map')) ?></h1>
            <p class="lead"><?= e(t('メーカーから車種を探し、現行の商品ページへ移動できます。', 'Find vehicles by manufacturer and open the current product page.')) ?></p>
        </div>
    </div>
    <div class="directory-controls">
        <label for="site-map-search"><?= e(t('車種名・型式で検索', 'Search by vehicle or model')) ?></label>
        <input id="site-map-search" type="search" placeholder="<?= e(t('例：PRIUS、ZC33S、LANCER', 'e.g. PRIUS, ZC33S, LANCER')) ?>" data-site-map-search>
        <div class="directory-filter-list" data-site-map-filters></div>
    </div>
    <p class="directory-result"><span data-site-map-count><?= count($entries) ?></span> <?= e(t('件を表示', 'results')) ?></p>
    <div class="site-map-groups" data-site-map-groups>
        <?php foreach ($groups as $groupName => $groupEntries): ?>
            <section class="site-map-group" data-site-map-group="<?= e($groupName) ?>">
                <div class="site-map-group-head">
                    <h2><?= e($groupName) ?></h2>
                    <span><?= count($groupEntries) ?> <?= e(t('車種', 'VEHICLES')) ?></span>
                </div>
                <div class="site-map-grid">
                    <?php foreach ($groupEntries as $entry): ?>
                        <?php $hasProduct = !empty($entry['product_id']); ?>
                        <article class="site-map-card" data-site-map-entry="<?= e($entry['title'] . ' ' . $entry['detail'] . ' ' . $groupName) ?>">
                            <?php if ($hasProduct): ?><a href="<?= e(url('/products/' . $entry['product_slug'])) ?>" class="site-map-card-link"><?php endif; ?>
                                <?php if ($entry['product_image'] !== ''): ?>
                                    <img src="<?= e(media_url($entry['product_image'])) ?>" alt="<?= e($entry['title']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="site-map-card-image" aria-hidden="true"></div>
                                <?php endif; ?>
                                <div class="site-map-card-body">
                                    <h3><?= e($entry['title']) ?></h3>
                                    <?php if ($entry['detail'] !== ''): ?><p><?= e($entry['detail']) ?></p><?php endif; ?>
                                    <span><?= e($hasProduct ? t('現行商品ページを見る', 'View product') : t('商品ページを準備中', 'Product page is being prepared')) ?></span>
                                </div>
                            <?php if ($hasProduct): ?></a><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <p class="muted" data-site-map-empty hidden><?= e(t('該当する車種がありません。', 'No vehicles found.')) ?></p>
</section>
