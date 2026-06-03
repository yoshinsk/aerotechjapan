<?php
/**
 * 試作版トップ（商品一覧）。
 * 登録済みの商品をカード形式で並べる。
 */

require_once __DIR__ . '/lib/repository.php';
require_once __DIR__ . '/templates/layout.php';

$repo = new ProductRepository();
$products = $repo->all();

layout_header('商品一覧（試作版）');
?>

<h1 class="h4 mb-3">商品一覧 <span class="text-secondary small">（試作版）</span></h1>

<div class="row g-3">
    <?php foreach ($products as $p): ?>
        <?php
            $thumb = '';
            if (!empty($p['images'])) {
                $thumb = SITE_BASE . rtrim($p['image_dir'], '/') . '/' . $p['images'][0];
            }
        ?>
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card bg-dark border-secondary h-100">
                <?php if ($thumb): ?>
                    <a href="product.php?slug=<?= urlencode($p['slug']) ?>">
                        <img src="<?= e($thumb) ?>" class="card-img-top" alt="<?= e($p['name']) ?>" loading="lazy">
                    </a>
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="h6 card-title">
                        <a href="product.php?slug=<?= urlencode($p['slug']) ?>" class="stretched-link text-decoration-none">
                            <?= e($p['name']) ?>
                        </a>
                    </h2>
                    <div class="text-secondary small"><?= e($p['model_year']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
layout_footer();
