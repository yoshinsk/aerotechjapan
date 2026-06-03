<?php
/**
 * カテゴリ別の商品一覧。
 * 使い方:  category.php?cat=DIRect
 */

require_once __DIR__ . '/lib/repository.php';
require_once __DIR__ . '/templates/layout.php';

$repo = new ProductRepository();
$all = $repo->all();

$cat = $_GET['cat'] ?? '';
$valid = array_merge(canonical_categories(), ['未分類']);
if (!in_array($cat, $valid, true)) {
    http_response_code(404);
    layout_header('カテゴリが見つかりません');
    echo '<p>指定されたカテゴリが見つかりませんでした。</p>';
    echo '<p><a href="index.php">商品一覧へ戻る</a></p>';
    layout_footer();
    exit;
}

$items = products_in_category($all, $cat);

layout_header($cat, $cat);
?>

<h1 class="h4 mb-4"><?= e($cat) ?> <span class="text-secondary small">（<?= count($items) ?> 件）</span></h1>

<?php if (!$items): ?>
    <p class="text-secondary">このカテゴリには商品がありません。</p>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($items as $p): ?>
            <?php render_product_card($p); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
layout_footer();
