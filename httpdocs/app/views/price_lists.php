<?php
/**
 * httpdocs/app/views/price_lists.php
 * ブランド別に登録された価格表PDFを公開ページとして表示します。
 */
$groups = [];
foreach ($priceLists as $priceList) {
    $key = (string)($priceList['category_id'] ?? 'uncategorized');
    if (!isset($groups[$key])) {
        $groups[$key] = [
            'name' => localized($priceList, 'category_name', '未分類'),
            'logo_path' => $priceList['category_logo_path'] ?? '',
            'items' => [],
        ];
    }
    $groups[$key]['items'][] = $priceList;
}
?>
<section class="section container">
    <div class="section-head">
        <div>
            <p class="eyebrow">PRICE LIST</p>
            <h1><?= e(t('価格表リスト', 'Price Lists')) ?></h1>
        </div>
    </div>
    <?php if (!$groups): ?>
        <p class="muted"><?= e(t('現在公開中の価格表はありません。', 'No price lists are currently available.')) ?></p>
    <?php endif; ?>
    <div class="price-list-groups">
        <?php foreach ($groups as $group): ?>
            <section class="price-list-group">
                <header class="price-list-brand">
                    <?php if ($group['logo_path'] !== ''): ?>
                        <img src="<?= e(media_url($group['logo_path'])) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <h2><?= e($group['name']) ?></h2>
                </header>
                <div class="row g-3">
                    <?php foreach ($group['items'] as $item): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <a class="price-list-card" href="<?= e(media_url($item['pdf_path'])) ?>" target="_blank" rel="noopener">
                                <span><?= e(localized($item, 'title')) ?></span>
                                <?php if (!empty($item['published_at'])): ?><time datetime="<?= e($item['published_at']) ?>"><?= e($item['published_at']) ?></time><?php endif; ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</section>
