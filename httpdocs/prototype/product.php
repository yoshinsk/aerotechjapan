<?php
/**
 * 商品詳細ページ（レスポンシブ／データ駆動）。
 * 旧 garage-file/<商品>.html に相当する画面を、データから自動生成する。
 *
 * 使い方:  product.php?slug=GUN125_HILUX_GR_SPORT
 */

require_once __DIR__ . '/lib/repository.php';
require_once __DIR__ . '/templates/layout.php';

$repo = new ProductRepository();
$slug = $_GET['slug'] ?? '';
$product = $slug !== '' ? $repo->find($slug) : null;

if (!$product) {
    http_response_code(404);
    layout_header('商品が見つかりません');
    echo '<p>指定された商品が見つかりませんでした。</p>';
    echo '<p><a href="index.php">商品一覧へ戻る</a></p>';
    layout_footer();
    exit;
}

$imgBase = SITE_BASE . rtrim($product['image_dir'], '/') . '/';

layout_header($product['name']);
?>

<div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
    <div>
        <h1 class="product-title mb-0"><?= e($product['name']) ?></h1>
        <div class="product-year"><?= e($product['model_year']) ?></div>
        <div class="text-secondary small"><?= e($product['category']) ?></div>
    </div>
    <a class="btn btn-outline-info btn-sm" href="admin/edit.php?slug=<?= urlencode($product['slug']) ?>">この商品を編集</a>
</div>

<?php if (!empty($product['notes'])): ?>
    <p class="text-secondary"><?= e($product['notes']) ?></p>
<?php endif; ?>

<!-- 画像ギャラリー：PC では2列、スマホでは1列に自動で並ぶ -->
<div class="row g-3 gallery mb-4">
    <?php foreach ($product['images'] as $img): ?>
        <div class="col-12 col-md-6">
            <a href="<?= e($imgBase . $img) ?>" target="_blank" rel="noopener">
                <img src="<?= e($imgBase . $img) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
            </a>
        </div>
    <?php endforeach; ?>
</div>

<!-- SPEC表 -->
<?php if (!empty($product['specs'])): ?>
    <h2 class="h5">SPEC ＆ Special Thanks</h2>
    <table class="table table-dark table-bordered spec-table">
        <tbody>
        <?php foreach ($product['specs'] as $spec): ?>
            <tr>
                <th><?= e($spec['label'] ?? '') ?></th>
                <td><?= e($spec['value'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
layout_footer();
