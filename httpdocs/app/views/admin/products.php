<?php
/**
 * httpdocs/app/views/admin/products.php
 * 商品一覧と検索フォームを管理画面へ描画します。
 */
?>
<h1>商品管理</h1>
<div class="admin-actions">
    <a class="button" href="<?= e(url('/admin/product-edit')) ?>">商品を追加</a>
</div>
<form class="admin-form" method="get" action="<?= e(url('/admin/products')) ?>">
    <label>検索
        <input name="q" value="<?= e($keyword) ?>" placeholder="商品名・slug">
    </label>
</form>
<table class="admin-table">
    <thead><tr><th>ID</th><th>商品名</th><th>カテゴリ</th><th>状態</th><th>更新</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $product): ?>
        <tr>
            <td><?= e($product['id']) ?></td>
            <td><?= e($product['name_ja']) ?><br><span class="muted"><?= e($product['slug']) ?></span></td>
            <td><?= e($product['category_name_ja'] ?? '-') ?></td>
            <td><?= e($product['status']) ?></td>
            <td><?= e($product['updated_at']) ?></td>
            <td><a href="<?= e(url('/admin/product-edit?id=' . $product['id'])) ?>">編集</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
