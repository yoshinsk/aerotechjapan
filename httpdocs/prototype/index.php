<?php
/**
 * 試作版トップ。カテゴリ別に商品を一覧表示する。
 */

require_once __DIR__ . '/lib/repository.php';
require_once __DIR__ . '/templates/layout.php';

$repo = new ProductRepository();
$all = $repo->all();

// 表示順：正式カテゴリ → 最後に未分類
$sections = [];
foreach (canonical_categories() as $cat) {
    $items = products_in_category($all, $cat);
    if ($items) {
        $sections[$cat] = $items;
    }
}
$uncat = products_in_category($all, '未分類');
if ($uncat) {
    $sections['未分類'] = $uncat;
}

layout_header('商品一覧（試作版）', '*');
?>

<h1 class="h4 mb-4">商品一覧 <span class="text-secondary small">（全 <?= count($all) ?> 商品 / 試作版）</span></h1>

<?php foreach ($sections as $cat => $items): ?>
    <section class="mb-5">
        <h2 class="h5 border-bottom border-secondary pb-2 d-flex justify-content-between">
            <span><?= e($cat) ?></span>
            <a class="small" href="category.php?cat=<?= urlencode($cat) ?>"><?= count($items) ?> 件 ›</a>
        </h2>
        <div class="row g-3">
            <?php foreach (array_slice($items, 0, 6) as $p): ?>
                <?php render_product_card($p); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php
layout_footer();
