<?php
/**
 * httpdocs/app/views/admin/products.php
 * 商品一覧をカテゴリタブ、検索フォーム、編集用テーブルとして描画します。
 */
$products = $products ?? [];
$keyword = trim((string)($keyword ?? ''));
$categories = $categories ?? [];
$categoryCounts = $categoryCounts ?? ['all' => count($products), 'uncategorized' => 0, 'categories' => []];
$activeCategory = (string)($activeCategory ?? 'all');
$statusFilter = (string)($statusFilter ?? 'active');
$validCategoryKeys = ['all' => true, 'uncategorized' => true];
foreach ($categories as $category) {
    $validCategoryKeys[(string)$category['id']] = true;
}
if (!isset($validCategoryKeys[$activeCategory])) {
    $activeCategory = 'all';
}
$buildProductsUrl = static function (string $category, string $search = '', ?string $status = null) use ($statusFilter): string {
    $params = [];
    if ($category !== 'all') {
        $params['category'] = $category;
    }
    if ($search !== '') {
        $params['q'] = $search;
    }
    $status = $status ?? $statusFilter;
    if ($status !== 'active') {
        $params['status'] = $status;
    }
    $query = http_build_query($params);
    return url('/admin/products' . ($query !== '' ? '?' . $query : ''));
};
$categoryCount = static function (int $categoryId) use ($categoryCounts): int {
    return (int)($categoryCounts['categories'][$categoryId] ?? 0);
};
$uncategorizedCount = (int)($categoryCounts['uncategorized'] ?? 0);
$statusLabels = [
    'published' => ['公開', 'text-bg-success'],
    'draft' => ['下書き', 'text-bg-secondary'],
    'deleted' => ['削除済み', 'text-bg-danger'],
];
?>
<header class="admin-page-head">
    <div>
        <p class="eyebrow">PRODUCT CMS</p>
        <h1>商品管理</h1>
    </div>
    <a class="btn btn-danger admin-primary-action" href="<?= e(url('/admin/product-edit')) ?>">商品を追加</a>
</header>

<section class="admin-panel admin-products-panel">
    <?php if (isset($_GET['deleted'])): ?><div class="notice">商品を削除済みに移動しました。</div><?php endif; ?>
    <?php if (isset($_GET['permanent_deleted'])): ?><div class="notice">商品を完全削除しました。</div><?php endif; ?>
    <ul class="nav nav-pills admin-status-tabs" aria-label="商品状態">
        <li class="nav-item"><a class="nav-link <?= $statusFilter === 'active' ? 'active' : '' ?>" href="<?= e($buildProductsUrl($activeCategory, $keyword, 'active')) ?>">通常商品</a></li>
        <li class="nav-item"><a class="nav-link <?= $statusFilter === 'deleted' ? 'active' : '' ?>" href="<?= e($buildProductsUrl($activeCategory, $keyword, 'deleted')) ?>">削除済み</a></li>
        <li class="nav-item"><a class="nav-link <?= $statusFilter === 'all' ? 'active' : '' ?>" href="<?= e($buildProductsUrl($activeCategory, $keyword, 'all')) ?>">全件</a></li>
    </ul>
    <form class="admin-search row g-2 align-items-end" method="get" action="<?= e(url('/admin/products')) ?>">
        <?php if ($activeCategory !== 'all'): ?>
            <input type="hidden" name="category" value="<?= e($activeCategory) ?>">
        <?php endif; ?>
        <?php if ($statusFilter !== 'active'): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <?php endif; ?>
        <div class="col-12 col-lg">
            <label class="form-label" for="product-search">検索</label>
            <div class="input-group">
                <span class="input-group-text">商品名 / slug</span>
                <input id="product-search" class="form-control" name="q" value="<?= e($keyword) ?>" placeholder="例: HILUX">
            </div>
        </div>
        <div class="col-12 col-lg-auto admin-search-actions">
            <button class="btn btn-outline-light" type="submit">検索</button>
            <?php if ($keyword !== ''): ?>
                <a class="btn btn-link" href="<?= e($buildProductsUrl($activeCategory)) ?>">解除</a>
            <?php endif; ?>
        </div>
    </form>

    <ul class="nav nav-pills admin-category-tabs flex-nowrap overflow-auto" role="tablist" aria-label="商品カテゴリ">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeCategory === 'all' ? 'active' : '' ?>" href="<?= e($buildProductsUrl('all', $keyword)) ?>">
                すべて <span class="badge"><?= e((string)($categoryCounts['all'] ?? 0)) ?></span>
            </a>
        </li>
        <?php foreach ($categories as $category): ?>
            <?php
            $categoryKey = (string)$category['id'];
            $isActive = $activeCategory === $categoryKey;
            ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $isActive ? 'active' : '' ?>" href="<?= e($buildProductsUrl($categoryKey, $keyword)) ?>">
                    <?= e($category['name_ja']) ?> <span class="badge"><?= e((string)$categoryCount((int)$category['id'])) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
        <?php if ($uncategorizedCount > 0 || $activeCategory === 'uncategorized'): ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?= $activeCategory === 'uncategorized' ? 'active' : '' ?>" href="<?= e($buildProductsUrl('uncategorized', $keyword)) ?>">
                    カテゴリ未設定 <span class="badge"><?= e((string)$uncategorizedCount) ?></span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="table-responsive">
        <table class="table table-hover align-middle admin-table admin-product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>画像</th>
                    <th>商品名</th>
                    <th>カテゴリ</th>
                    <th>状態</th>
                    <th>更新</th>
                    <th class="text-end">操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <?php $status = $statusLabels[$product['status']] ?? [$product['status'], 'text-bg-dark']; ?>
                <tr>
                    <td class="admin-product-id"><?= e($product['id']) ?></td>
                    <td>
                        <img class="admin-product-thumb" src="<?= e(media_url($product['main_image'] ?? null)) ?>" alt="" loading="lazy">
                    </td>
                    <td>
                        <strong><?= e($product['name_ja']) ?></strong>
                        <div class="muted"><?= e($product['slug']) ?></div>
                    </td>
                    <td><?= e($product['category_name_ja'] ?? '未分類') ?></td>
                    <td><span class="badge <?= e($status[1]) ?>"><?= e($status[0]) ?></span></td>
                    <td><time datetime="<?= e($product['updated_at']) ?>"><?= e($product['updated_at']) ?></time></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-info" href="<?= e(url('/admin/product-edit?id=' . $product['id'])) ?>">編集</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
                <tr>
                    <td class="text-center muted" colspan="7">該当する商品はありません。</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
